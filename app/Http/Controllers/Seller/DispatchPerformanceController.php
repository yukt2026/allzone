<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\FulfilmentType;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Services\Cargo\Contracts\LogisticsProvider;
use App\Services\Cargo\ShiprocketClient;
use App\Services\Order\OrderStateMachine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispatchPerformanceController extends Controller
{
    public function __construct(
        protected LogisticsProvider $logisticsProvider,
        protected ShiprocketClient $shiprocketClient,
        protected OrderStateMachine $stateMachine
    ) {}

    /**
     * Display Dispatch Performance & Shiprocket Shipping Dashboard.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Merchant account required.');
        }

        $dateFilter = $request->query('date_range', $request->query('date_filter', '30d'));
        $startDateParam = $request->query('start_date');
        $endDateParam = $request->query('end_date');
        $tab = $request->query('tab', 'all');
        $search = $request->query('search');

        // Resolve Date Boundaries
        [$start, $end] = $this->resolveDateRange($dateFilter, $startDateParam, $endDateParam);

        // 1. Base query for seller's fulfilment orders within date range
        $foDateQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$start, $end]);

        // 2. Base query for seller's shipments within date range
        $shpDateQuery = Shipment::query()
            ->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereBetween('created_at', [$start, $end]);

        // 3. Compute the 10 Actual Database-driven Metrics
        $pendingDispatch = (clone $foDateQuery)
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed'])
            ->count();

        $readyToShip = (clone $foDateQuery)
            ->whereIn('status', ['ready', 'ready_to_ship'])
            ->count();

        $shipmentCreated = (clone $shpDateQuery)
            ->where('status', ShipmentStatus::MANIFEST_CREATED)
            ->count();

        $pickupPending = (clone $shpDateQuery)
            ->where(function ($q) {
                $q->where('pickup_status', 'pending')
                  ->orWhere('status', ShipmentStatus::MANIFEST_CREATED);
            })
            ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED, ShipmentStatus::DELIVERED])
            ->count();

        $pickedUp = (clone $shpDateQuery)
            ->where('status', ShipmentStatus::PICKED_UP)
            ->count();

        $inTransit = (clone $shpDateQuery)
            ->whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])
            ->count();

        $delivered = (clone $foDateQuery)
            ->where('status', 'delivered')
            ->count();

        // Delayed shipments: SLA breach (> 24 hours from placed without dispatch, or past estimated delivery)
        $delayedShipments = (clone $foDateQuery)
            ->where('created_at', '<', now()->subHours(24))
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'ready', 'ready_to_ship'])
            ->count();

        $failedDelivery = (clone $shpDateQuery)
            ->whereIn('status', [ShipmentStatus::FAILED, ShipmentStatus::NDR_RAISED])
            ->count();

        $rtoShipments = (clone $shpDateQuery)
            ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
            ->count();

        $metrics = [
            'pending_dispatch' => $pendingDispatch,
            'ready_to_ship' => $readyToShip,
            'shipment_created' => $shipmentCreated,
            'pickup_pending' => $pickupPending,
            'picked_up' => $pickedUp,
            'in_transit' => $inTransit,
            'delivered' => $delivered,
            'delayed_shipments' => $delayedShipments,
            'failed_delivery' => $failedDelivery,
            'rto_shipments' => $rtoShipments,
        ];

        // Overall Dispatch Health & Performance KPIs
        $totalProcessed = (clone $foDateQuery)->count();
        $totalDispatched = (clone $foDateQuery)->whereNotIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'cancelled', 'rejected'])->count();
        $onTimeDispatches = max(0, $totalDispatched - $delayedShipments);
        $onTimeDispatchRate = $totalDispatched > 0 ? round(($onTimeDispatches / $totalDispatched) * 100, 1) : 100.0;
        $deliverySuccessRate = $totalProcessed > 0 ? round(($delivered / $totalProcessed) * 100, 1) : 0.0;
        $rtoRate = $totalDispatched > 0 ? round(($rtoShipments / $totalDispatched) * 100, 1) : 0.0;

        $kpis = [
            'total_orders' => $totalProcessed,
            'total_dispatched' => $totalDispatched,
            'on_time_rate' => $onTimeDispatchRate,
            'delivery_rate' => $deliverySuccessRate,
            'rto_rate' => $rtoRate,
        ];

        // 4. Daily Chart Trends Dataset
        $chartData = $this->buildChartTrends($seller->id, $start, $end);

        // 5. Order-level Details Table Query
        $tableQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$start, $end])
            ->with([
                'order.customer.user',
                'order.customerAddress',
                'shipment.tracking',
                'shipment.ndrCases',
                'items.variant.product',
                'store',
            ]);

        // Filter by Tab
        $tableQuery = $this->applyTabFilter($tableQuery, $tab);

        // Filter by Search
        if ($search) {
            $tableQuery->where(function ($q) use ($search) {
                $q->where('fulfilment_order_number', 'like', "%{$search}%")
                  ->orWhereHas('order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%")->orWhere('address_snapshot', 'like', "%{$search}%"))
                  ->orWhereHas('shipment', fn($sq) => $sq->where('awb', 'like', "%{$search}%")->orWhere('shipment_number', 'like', "%{$search}%")->orWhere('courier', 'like', "%{$search}%"))
                  ->orWhereHas('items', fn($iq) => $iq->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        // Handle CSV Export
        if ($request->query('export') === 'csv') {
            return $this->exportCsv($tableQuery, $seller->id);
        }

        $records = $tableQuery->latest('id')->paginate(15)->withQueryString();

        return view('seller.dispatch.index', compact(
            'metrics',
            'kpis',
            'chartData',
            'records',
            'dateFilter',
            'startDateParam',
            'endDateParam',
            'tab',
            'search'
        ));
    }

    /**
     * Check available Shiprocket couriers, rates, and ETD for an order.
     */
    public function checkRates(Request $request, FulfilmentOrder $fulfilmentOrder): JsonResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $weightKg = (float) $request->input('weight', 0.5);
        if ($weightKg <= 0) {
            $weightKg = 0.5;
        }

        $originPincode = (string) ($fulfilmentOrder->store?->pincode ?? '560038');
        $destPincode = (string) ($fulfilmentOrder->order?->customerAddress?->pincode ?? $fulfilmentOrder->order?->address_snapshot['pincode'] ?? '560001');
        $isCod = $fulfilmentOrder->order?->payment_method?->value === 'cod';

        try {
            $serviceability = $this->shiprocketClient->checkServiceability(
                $originPincode,
                $destPincode,
                $weightKg,
                $isCod
            );

            $couriers = $serviceability['data']['available_courier_companies'] ?? [];

            return response()->json([
                'success' => true,
                'origin_pincode' => $originPincode,
                'dest_pincode' => $destPincode,
                'weight' => $weightKg,
                'couriers' => $couriers,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Shiprocket rates: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate package dimensions/weight and create official Shiprocket shipment consignment.
     */
    public function createShipment(Request $request, FulfilmentOrder $fulfilmentOrder): RedirectResponse|JsonResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403, 'Unauthorized access to order.');
        }

        $validated = $request->validate([
            'length' => ['required', 'numeric', 'min:0.5', 'max:200'],
            'breadth' => ['required', 'numeric', 'min:0.5', 'max:200'],
            'height' => ['required', 'numeric', 'min:0.5', 'max:200'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'courier_id' => ['nullable', 'integer'],
            'courier_name' => ['nullable', 'string', 'max:100'],
        ]);

        // Duplicate shipment prevention: check if an active shipment exists
        $existing = Shipment::where('fulfilment_order_id', $fulfilmentOrder->id)
            ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED])
            ->first();

        if ($existing && $existing->awb) {
            $msg = "Shipment already exists with AWB: {$existing->awb} ({$existing->courier}). Duplicate creation prevented.";
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'shipment' => $existing]);
            }
            return back()->with('info', $msg);
        }

        try {
            $options = [
                'length' => (float) $validated['length'],
                'breadth' => (float) $validated['breadth'],
                'height' => (float) $validated['height'],
                'weight' => (float) $validated['weight'],
                'courier_id' => !empty($validated['courier_id']) ? (int) $validated['courier_id'] : null,
                'courier_name' => $validated['courier_name'] ?? null,
            ];

            $result = $this->logisticsProvider->createShipment($fulfilmentOrder, $options);
            $shipment = $result['shipment'] ?? null;

            // Transition FO status step-by-step according to state machine rules
            try {
                if ($fulfilmentOrder->status === 'placed') {
                    $firstStep = $fulfilmentOrder->fulfilment_type === FulfilmentType::QUICK ? 'store_accepted' : 'confirmed';
                    $this->stateMachine->transitionFulfilmentOrder($fulfilmentOrder, $firstStep, 'Accepted for dispatch', Auth::id());
                    $fulfilmentOrder->refresh();
                }

                if (in_array($fulfilmentOrder->status, ['confirmed', 'store_accepted'])) {
                    $packStep = $fulfilmentOrder->fulfilment_type === FulfilmentType::QUICK ? 'packing' : 'packed';
                    $this->stateMachine->transitionFulfilmentOrder($fulfilmentOrder, $packStep, 'Packed with dimensions & weight', Auth::id());
                    $fulfilmentOrder->refresh();
                }

                if (in_array($fulfilmentOrder->status, ['packed', 'packing'])) {
                    $readyStep = $fulfilmentOrder->fulfilment_type === FulfilmentType::QUICK ? 'ready' : 'ready_to_ship';
                    $this->stateMachine->transitionFulfilmentOrder($fulfilmentOrder, $readyStep, "Shiprocket consignment created: AWB {$shipment?->awb}", Auth::id());
                    $fulfilmentOrder->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning("Could not transition FO status after shipment: " . $e->getMessage());
            }

            $successMsg = "Shiprocket shipment created successfully! AWB: {$shipment?->awb} via {$shipment?->courier}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'shipment' => $shipment,
                ]);
            }

            return back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            Log::error("Shiprocket shipment creation failed for FO #{$fulfilmentOrder->id}: " . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', "Shiprocket Error: " . $e->getMessage());
        }
    }

    /**
     * Synchronize real-time tracking events from Shiprocket API.
     */
    public function syncTracking(Shipment $shipment): RedirectResponse
    {
        $this->authorizeShipment($shipment);

        if (!$shipment->awb) {
            return back()->with('error', 'Cannot track a shipment without an assigned AWB.');
        }

        try {
            $trackingData = $this->logisticsProvider->track($shipment->awb);

            // Record latest tracking event if available
            $currentStatus = $trackingData['current_status'] ?? $trackingData['status'] ?? null;
            if ($currentStatus) {
                ShipmentTracking::create([
                    'shipment_id' => $shipment->id,
                    'status' => strtolower(str_replace(' ', '_', (string) $currentStatus)),
                    'location' => $trackingData['current_location'] ?? 'Hub In Transit',
                    'event_time' => now(),
                    'raw' => $trackingData,
                ]);
            }

            return back()->with('success', "Live tracking milestones updated for AWB: {$shipment->awb}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Tracking sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Schedule carrier warehouse / store pickup with Shiprocket.
     */
    public function schedulePickup(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeShipment($shipment);

        $validated = $request->validate([
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'time_slot' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $this->logisticsProvider->requestPickup($shipment, $validated);

            $shipment->pickup_status = 'scheduled';
            $shipment->save();

            return back()->with('success', "Carrier pickup scheduled successfully for AWB: {$shipment->awb}");
        } catch (\Throwable $e) {
            return back()->with('error', 'Pickup scheduling failed: ' . $e->getMessage());
        }
    }

    /**
     * Export Dispatch Performance & Shipment Records as CSV.
     */
    protected function exportCsv($query, int $sellerId): StreamedResponse
    {
        $filename = "dispatch-performance-export-{$sellerId}-" . now()->format('Ymd-His') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Order ID',
                'Sub-order Package #',
                'Order Placed Date',
                'Dispatch SLA Deadline',
                'SLA Compliance',
                'Package Status',
                'Shiprocket Shipment #',
                'Shiprocket Courier',
                'AWB Number',
                'Weight (kg)',
                'Freight Charges (INR)',
                'Pickup Status',
                'Destination City',
                'Destination State',
                'Pincode',
                'Payment Mode',
            ]);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $fo) {
                    $order = $fo->order;
                    $shipment = $fo->shipment;
                    $addr = is_array($order?->address_snapshot) ? $order->address_snapshot : [];
                    $expectedDispatch = $fo->created_at->addHours(24);
                    $isOverdue = $expectedDispatch->isPast() && in_array($fo->status, ['placed', 'store_accepted', 'packing', 'confirmed', 'ready', 'ready_to_ship']);

                    fputcsv($handle, [
                        $order?->order_number ?? 'N/A',
                        $fo->fulfilment_order_number,
                        $fo->created_at->format('Y-m-d H:i:s'),
                        $expectedDispatch->format('Y-m-d H:i:s'),
                        $isOverdue ? 'Delayed (SLA Overdue)' : 'On-Time',
                        ucwords(str_replace('_', ' ', $fo->status)),
                        $shipment?->shipment_number ?? 'Pending',
                        $shipment?->courier ?? 'Shiprocket Surface',
                        $shipment?->awb ?? 'Pending AWB',
                        $shipment?->weight ?? '0.50',
                        number_format((float) ($shipment?->charges ?? 85.00), 2, '.', ''),
                        ucfirst($shipment?->pickup_status ?? 'Pending'),
                        $addr['city'] ?? 'N/A',
                        $addr['state'] ?? 'N/A',
                        $addr['pincode'] ?? 'N/A',
                        strtoupper($order?->payment_method?->value ?? 'PREPAID'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Resolve date filters into Carbon range.
     */
    protected function resolveDateRange(string $dateFilter, ?string $startDate, ?string $endDate): array
    {
        return match ($dateFilter) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            '7d' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()->endOfDay()],
            '30d' => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
            'custom' => [
                $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subDays(30)->startOfDay(),
                $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay(),
            ],
            default => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()->endOfDay()],
        };
    }

    /**
     * Apply status tab filtering.
     */
    protected function applyTabFilter($query, string $tab)
    {
        return match ($tab) {
            'pending' => $query->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed']),
            'ready_to_ship' => $query->whereIn('status', ['ready', 'ready_to_ship']),
            'manifested' => $query->whereHas('shipment', fn($sq) => $sq->where('status', ShipmentStatus::MANIFEST_CREATED)),
            'in_transit' => $query->whereHas('shipment', fn($sq) => $sq->whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])),
            'delivered' => $query->where('status', 'delivered'),
            'delayed' => $query->where('created_at', '<', now()->subHours(24))->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'ready', 'ready_to_ship']),
            'ndr_failed' => $query->whereHas('shipment', fn($sq) => $sq->whereIn('status', [ShipmentStatus::FAILED, ShipmentStatus::NDR_RAISED])),
            'rto' => $query->whereHas('shipment', fn($sq) => $sq->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])),
            default => $query,
        };
    }

    /**
     * Build daily chart dataset.
     */
    protected function buildChartTrends(int $sellerId, Carbon $start, Carbon $end): array
    {
        $dates = [];
        $dispatchedCounts = [];
        $deliveredCounts = [];
        $delayedCounts = [];

        $diffDays = $start->diffInDays($end);
        $stepDays = $diffDays > 30 ? ceil($diffDays / 15) : 1;

        $current = $start->copy();
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->addDays($stepDays - 1)->endOfDay();
            if ($dayEnd > $end) {
                $dayEnd = $end->copy();
            }

            $dateLabel = $current->format('d M');
            $dates[] = $dateLabel;

            $dispatched = FulfilmentOrder::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereNotIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'cancelled', 'rejected'])
                ->count();

            $delivered = FulfilmentOrder::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('status', 'delivered')
                ->count();

            $delayed = FulfilmentOrder::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->where('created_at', '<', now()->subHours(24))
                ->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'ready', 'ready_to_ship'])
                ->count();

            $dispatchedCounts[] = $dispatched;
            $deliveredCounts[] = $delivered;
            $delayedCounts[] = $delayed;

            $current->addDays((int) $stepDays);
        }

        return [
            'labels' => $dates,
            'dispatched' => $dispatchedCounts,
            'delivered' => $deliveredCounts,
            'delayed' => $delayedCounts,
        ];
    }

    /**
     * Authorize shipment belongs to authenticated seller.
     */
    protected function authorizeShipment(Shipment $shipment): void
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $shipment->fulfilmentOrder?->seller_id !== (int) $seller->id) {
            abort(403, 'Unauthorized access to this shipment.');
        }
    }
}
