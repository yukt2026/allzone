<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\QCStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\RtoCase;
use App\Models\Shipment;
use App\Services\Order\ReturnService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnController extends Controller
{
    public function __construct(
        protected ReturnService $returnService
    ) {}

    /**
     * Display seller returns management console.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized. Merchant seller profile required.');
        }

        $tab = $request->query('tab', $request->query('view'));
        $search = $request->query('search');
        $status = $request->query('status');

        if (!$tab) {
            $tab = 'returns';
        }

        // 1. Core Summary Metrics
        $deliveredOrders = FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->count();

        $customerReturns = OrderReturn::forSeller($seller->id)
            ->where('type', '!=', 'rto')
            ->count();

        $courierRto = Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
            ->count() + OrderReturn::forSeller($seller->id)->where('type', 'rto')->count();

        $totalDispatched = FulfilmentOrder::where('seller_id', $seller->id)
            ->whereNotIn('status', ['placed', 'store_accepted', 'packing', 'confirmed', 'cancelled', 'rejected'])
            ->count();

        $customerReturnRate = $deliveredOrders > 0 ? round(($customerReturns / $deliveredOrders) * 100, 1) : 0.0;
        $courierRtoRate = $totalDispatched > 0 ? round(($courierRto / $totalDispatched) * 100, 1) : 0.0;

        $reverseShippingCosts = (float) Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
            ->sum('charges');

        $metrics = [
            'delivered_orders' => $deliveredOrders,
            'customer_returns' => $customerReturns,
            'customer_return_rate' => $customerReturnRate,
            'courier_rto' => $courierRto,
            'courier_rto_rate' => $courierRtoRate,
            'reverse_shipping_costs' => $reverseShippingCosts,
            'total_returns' => $customerReturns,
            'pending_approval' => OrderReturn::forSeller($seller->id)->where('status', ReturnStatus::REQUESTED)->count(),
            'in_transit' => OrderReturn::forSeller($seller->id)->whereIn('status', [
                ReturnStatus::REVERSE_PICKUP_SCHEDULED,
                ReturnStatus::PICKUP_SCHEDULED,
                ReturnStatus::PICKED_UP,
            ])->count(),
            'qc_pending' => OrderReturn::forSeller($seller->id)->where('status', ReturnStatus::RECEIVED)->count(),
            'qc_passed' => OrderReturn::forSeller($seller->id)->where('status', ReturnStatus::QC_PASSED)->count(),
            'refunded' => OrderReturn::forSeller($seller->id)->where('status', ReturnStatus::REFUNDED)->count(),
        ];

        // 2. Daily Trends Dataset (Last 14 days)
        $returnTrends = [];
        $rtoTrends = [];
        $trendDates = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $trendDates[] = $day->format('d M');

            $retCount = OrderReturn::forSeller($seller->id)
                ->where('type', '!=', 'rto')
                ->whereDate('created_at', $day)
                ->count();

            $rtoCount = Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
                ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                ->whereDate('updated_at', $day)
                ->count() + OrderReturn::forSeller($seller->id)->where('type', 'rto')->whereDate('created_at', $day)->count();

            $returnTrends[] = $retCount;
            $rtoTrends[] = $rtoCount;
        }
        $trends = [
            'labels' => $trendDates,
            'returns' => $returnTrends,
            'rto' => $rtoTrends,
        ];

        // 3. Product-wise Return Performance
        $productPerformance = $this->buildProductPerformance($seller->id);

        // 4. Handle Customer Returns List (Tab: 'returns' or 'tracking')
        $returnsQuery = OrderReturn::query()
            ->forSeller($seller->id)
            ->where('type', '!=', 'rto')
            ->with(['orderItem.variant.product', 'orderItem.fulfilmentOrder.order.user', 'pickupShipment.tracking', 'refund'])
            ->latest('id');

        if ($status && $status !== 'all') {
            if ($status === 'in_transit') {
                $returnsQuery->whereIn('status', [
                    ReturnStatus::REVERSE_PICKUP_SCHEDULED,
                    ReturnStatus::PICKUP_SCHEDULED,
                    ReturnStatus::PICKED_UP,
                ]);
            } else {
                $returnsQuery->where('status', $status);
            }
        }

        if ($search && ($tab === 'returns' || $tab === 'tracking')) {
            $returnsQuery->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhere('pickup_waybill', 'like', "%{$search}%")
                  ->orWhereHas('orderItem', fn($iq) => $iq->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                  ->orWhereHas('orderItem.fulfilmentOrder.order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%"));
            });
        }

        // 5. Handle Courier RTO List (Tab: 'rto')
        $rtoQuery = Shipment::query()
            ->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
            ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
            ->with(['fulfilmentOrder.order.customerAddress', 'fulfilmentOrder.items.variant.product', 'rtoCases', 'tracking'])
            ->latest('updated_at');

        if ($search && $tab === 'rto') {
            $rtoQuery->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('awb', 'like', "%{$search}%")
                  ->orWhereHas('fulfilmentOrder', fn($foq) => $foq->where('fulfilment_order_number', 'like', "%{$search}%"))
                  ->orWhereHas('fulfilmentOrder.order', fn($oq) => $oq->where('order_number', 'like', "%{$search}%"))
                  ->orWhereHas('fulfilmentOrder.items', fn($iq) => $iq->where('title', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        // Handle CSV Export
        if ($request->query('export') === 'csv') {
            return $this->export($request);
        }

        $returns = $returnsQuery->paginate(15, ['*'], 'returns_page')->withQueryString();
        $rtoShipments = $rtoQuery->paginate(15, ['*'], 'rto_page')->withQueryString();

        return view('seller.returns.index', compact(
            'metrics',
            'trends',
            'productPerformance',
            'returns',
            'rtoShipments',
            'tab',
            'search',
            'status'
        ));
    }

    /**
     * Build product-wise return and RTO metrics.
     */
    protected function buildProductPerformance(int $sellerId): array
    {
        $productPerformance = [];
        $items = OrderItem::whereHas('fulfilmentOrder', fn($fo) => $fo->where('seller_id', $sellerId))
            ->with(['variant.product', 'fulfilmentOrder.shipment', 'returns'])
            ->get()
            ->groupBy(fn($item) => $item->product_id ?? $item->variant?->product_id ?? $item->title);

        foreach ($items as $prodKey => $orderItems) {
            $first = $orderItems->first();
            $product = $first->product ?? $first->variant?->product;
            $deliveredUnits = $orderItems->filter(fn($i) => $i->fulfilmentOrder?->status === 'delivered')->sum('qty');
            $returnsList = $orderItems->flatMap(fn($i) => $i->returns);
            $customerReturnsCount = $returnsList->where('type', '!=', 'rto')->count();
            
            $rtoCount = $orderItems->filter(fn($i) => 
                in_array($i->fulfilmentOrder?->shipment?->status, [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                || $i->returns->where('type', 'rto')->isNotEmpty()
            )->count();

            $returnRate = $deliveredUnits > 0 ? round(($customerReturnsCount / $deliveredUnits) * 100, 1) : 0.0;
            $totalShipped = max(1, $deliveredUnits + $rtoCount);
            $rtoRate = round(($rtoCount / $totalShipped) * 100, 1);

            $reasons = $returnsList->pluck('reason')->filter()->countBy()->sortDesc()->take(2)->keys()->all();

            $productPerformance[] = [
                'id' => $product?->id,
                'title' => $product?->title ?? $first->title,
                'sku' => $first->sku ?? $first->variant?->sku ?? 'N/A',
                'thumbnail' => $product?->thumbnail,
                'delivered_orders' => $deliveredUnits,
                'customer_returns' => $customerReturnsCount,
                'customer_return_rate' => $returnRate,
                'rto_count' => $rtoCount,
                'rto_rate' => $rtoRate,
                'reasons' => !empty($reasons) ? implode(', ', $reasons) : 'None reported',
            ];
        }

        return $productPerformance;
    }

    /**
     * Display return request detail screen.
     */
    public function show(OrderReturn $return): View
    {
        $this->authorizeSeller($return);

        $return->load(['orderItem.variant.product', 'orderItem.fulfilmentOrder.order.user', 'pickupShipment.tracking', 'refund']);

        return view('seller.returns.show', compact('return'));
    }

    /**
     * Merchant approves the return request.
     */
    public function approve(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        $validated = $request->validate([
            'seller_notes' => ['nullable', 'string', 'max:500'],
            'auto_schedule_pickup' => ['nullable', 'boolean'],
        ]);

        try {
            $this->returnService->approve(
                $return,
                $validated['seller_notes'] ?? null,
                Auth::user(),
                $request->boolean('auto_schedule_pickup', true)
            );

            return back()->with('success', "Return #{$return->return_number} has been approved.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Merchant rejects the return request with mandatory remarks.
     */
    public function reject(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->returnService->reject($return, $validated['reason'], Auth::user());

            return back()->with('success', "Return #{$return->return_number} has been rejected.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Schedule reverse pickup manually if not auto-scheduled.
     */
    public function schedulePickup(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        try {
            $this->returnService->scheduleReversePickup($return, Auth::user());

            return back()->with('success', "Reverse pickup scheduled. Waybill: {$return->pickup_waybill}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark returned package received at facility.
     */
    public function receive(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        try {
            $this->returnService->markReceived($return, Auth::user());

            return back()->with('success', "Return package marked as received at inspection hub.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Record Quality Control (QC) inspection outcome.
     */
    public function recordQc(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        $validated = $request->validate([
            'outcome' => ['required', 'in:passed,failed'],
            'qc_notes' => ['nullable', 'string', 'max:1000'],
            'is_resellable' => ['nullable', 'boolean'],
        ]);

        try {
            $this->returnService->recordQcOutcome(
                $return,
                $validated['outcome'],
                $validated['qc_notes'] ?? null,
                $request->boolean('is_resellable', true),
                Auth::user()
            );

            return back()->with('success', "Quality inspection outcome recorded successfully.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Execute final financial refund upon QC pass.
     */
    public function refund(Request $request, OrderReturn $return): RedirectResponse
    {
        $this->authorizeSeller($return);

        if ($return->status === ReturnStatus::REFUNDED || $return->refund_id) {
            return back()->with('error', "Refund has already been processed for Return #{$return->return_number}.");
        }

        try {
            $this->returnService->processRefund($return, null, Auth::user());

            return back()->with('success', "Refund of ₹{$return->refund_amount} processed successfully.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export returns list as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403);
        }

        $type = $request->query('tab', $request->query('type', 'returns'));
        $filename = ($type === 'rto' ? 'rto' : 'returns') . "-export-seller-{$seller->id}-" . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($seller, $type) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            if ($type === 'rto') {
                fputcsv($handle, ['Order Number', 'Sub-order Package #', 'Item', 'SKU', 'Courier', 'AWB Number', 'RTO Reason', 'Status', 'Reverse Charges', 'Date']);
                Shipment::whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $seller->id))
                    ->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                    ->with(['fulfilmentOrder.order', 'fulfilmentOrder.items.variant', 'rtoCases'])
                    ->chunk(100, function ($shipments) use ($handle) {
                        foreach ($shipments as $s) {
                            $fo = $s->fulfilmentOrder;
                            $firstItem = $fo?->items?->first();
                            $rtoCase = $s->rtoCases?->first();
                            fputcsv($handle, [
                                $fo?->order?->order_number ?? 'N/A',
                                $fo?->fulfilment_order_number ?? 'N/A',
                                $firstItem?->title ?? 'N/A',
                                $firstItem?->sku ?? $firstItem?->variant?->sku ?? 'N/A',
                                $s->courier,
                                $s->awb ?? 'Pending',
                                $rtoCase?->reason ?? 'Delivery failed after maximum attempts',
                                $s->status->label(),
                                number_format((float)$s->charges, 2, '.', ''),
                                $s->updated_at->toDateTimeString(),
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, ['Return Number', 'Order Number', 'Sub-order Package #', 'Item', 'SKU', 'Qty', 'Refund Amount', 'Return Reason', 'Status', 'QC Status', 'Waybill', 'Date']);
                OrderReturn::forSeller($seller->id)
                    ->where('type', '!=', 'rto')
                    ->with(['orderItem.fulfilmentOrder.order', 'orderItem.variant'])
                    ->chunk(100, function ($returns) use ($handle) {
                        foreach ($returns as $r) {
                            $item = $r->orderItem;
                            $fo = $item?->fulfilmentOrder;
                            fputcsv($handle, [
                                $r->return_number,
                                $fo?->order?->order_number ?? 'N/A',
                                $fo?->fulfilment_order_number ?? 'N/A',
                                $item?->title ?? 'N/A',
                                $item?->sku ?? $item?->variant?->sku ?? 'N/A',
                                $item?->qty ?? 1,
                                number_format((float)($r->refund_amount ?? $item?->total ?? 0), 2, '.', ''),
                                $r->reason,
                                $r->status->value,
                                $r->qc_status->value,
                                $r->pickup_waybill ?? 'Pending',
                                $r->created_at->toDateTimeString(),
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Enforce multi-tenant seller ownership.
     */
    protected function authorizeSeller(OrderReturn $return): void
    {
        $seller = Auth::user()->seller;
        if (!$seller || $return->orderItem?->fulfilmentOrder?->seller_id !== $seller->id) {
            abort(403, 'Unauthorized. This return does not belong to your merchant account.');
        }
    }
}
