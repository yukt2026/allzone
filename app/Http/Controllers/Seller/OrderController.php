<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\FulfilmentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Services\Order\OrderPdfService;
use App\Services\Order\OrderStateMachine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected OrderPdfService $pdfService
    ) {}

    /**
     * Map logical order tabs to database fulfilment order statuses.
     */
    protected function mapTabToStatuses(string $tab): array
    {
        return match ($tab) {
            'pending' => ['placed'],
            'on_hold' => ['on_hold'],
            'processing' => ['store_accepted', 'packing', 'packed', 'confirmed'],
            'ready_to_ship', 'ready' => ['ready', 'ready_to_ship'],
            'shipped' => ['dispatched', 'in_transit', 'picked_up', 'out_for_delivery', 'rider_assigned'],
            'delivered' => ['delivered'],
            'cancelled' => ['cancelled', 'rejected'],
            'returned' => ['returned', 'returned_to_store', 'delivery_failed'],
            default => [],
        };
    }

    /**
     * Display seller orders list with tabs, filters, search, and CSV export.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Unauthorized seller account.');
        }

        $rawTab = $request->query('tab');
        $rawStatus = $request->query('status');
        $rawChannel = $request->query('channel');

        $tab = $rawStatus ?: ($rawTab ?: 'all');
        $channel = $rawChannel ?: 'all';

        // Support channel passed via tab (e.g. tab=quick, tab=online, tab=standard, tab=pickup)
        if (in_array($rawTab, ['quick', 'online', 'standard', 'pickup'], true) && !$rawChannel) {
            $channel = $rawTab;
            $tab = $rawStatus ?: 'all';
        }
        $paymentMethod = $request->query('payment_method');
        $paymentStatus = $request->query('payment_status');
        $dateFilter = $request->query('date_range', $request->query('date_filter', 'all'));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $storeId = $request->query('store_id');
        $search = $request->query('search');

        // Base query scoped strictly to authenticated seller
        $baseQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->with([
                'order.customer.user',
                'order.payments',
                'items.variant.product',
                'store',
                'shipment.tracking',
            ]);

        // Calculate counts for all 9 tabs
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereIn('status', ['placed'])->count(),
            'on_hold' => (clone $baseQuery)->where('status', 'on_hold')->count(),
            'processing' => (clone $baseQuery)->whereIn('status', ['store_accepted', 'packing', 'packed', 'confirmed'])->count(),
            'ready_to_ship' => (clone $baseQuery)->whereIn('status', ['ready', 'ready_to_ship'])->count(),
            'shipped' => (clone $baseQuery)->whereIn('status', ['dispatched', 'in_transit', 'picked_up', 'out_for_delivery', 'rider_assigned'])->count(),
            'delivered' => (clone $baseQuery)->where('status', 'delivered')->count(),
            'cancelled' => (clone $baseQuery)->whereIn('status', ['cancelled', 'rejected'])->count(),
            'returned' => (clone $baseQuery)->whereIn('status', ['returned', 'returned_to_store', 'delivery_failed'])->count(),
        ];

        // Apply Tab / Status Filter
        $tabStatuses = $this->mapTabToStatuses($tab);
        if (!empty($tabStatuses)) {
            $baseQuery->whereIn('status', $tabStatuses);
        }

        // Apply Channel Filter (Quick vs Standard Cargo vs Pickup)
        if ($channel === 'quick') {
            $baseQuery->where('fulfilment_type', FulfilmentType::QUICK);
        } elseif ($channel === 'standard' || $channel === 'online') {
            $baseQuery->where('fulfilment_type', FulfilmentType::STANDARD);
        } elseif ($channel === 'pickup') {
            $baseQuery->where('fulfilment_type', FulfilmentType::PICKUP);
        }

        // Apply Payment Method Filter
        if ($paymentMethod && $paymentMethod !== 'all') {
            if ($paymentMethod === 'cod') {
                $baseQuery->whereHas('order', fn($oq) => $oq->where('payment_method', PaymentMethod::COD));
            } elseif ($paymentMethod === 'prepaid') {
                $baseQuery->whereHas('order', fn($oq) => $oq->where('payment_method', '!=', PaymentMethod::COD));
            } else {
                $baseQuery->whereHas('order', fn($oq) => $oq->where('payment_method', $paymentMethod));
            }
        }

        // Apply Payment Status Filter
        if ($paymentStatus && $paymentStatus !== 'all') {
            $baseQuery->whereHas('order', fn($oq) => $oq->where('payment_status', $paymentStatus));
        }

        // Apply Date Range Filter
        if ($dateFilter === 'today') {
            $baseQuery->where('created_at', '>=', Carbon::today()->startOfDay());
        } elseif ($dateFilter === 'yesterday') {
            $baseQuery->whereBetween('created_at', [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
            ]);
        } elseif ($dateFilter === '7d') {
            $baseQuery->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay());
        } elseif ($dateFilter === '30d') {
            $baseQuery->where('created_at', '>=', Carbon::now()->subDays(30)->startOfDay());
        } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        // Apply Store Filter (Multi-store isolation)
        if ($storeId) {
            $baseQuery->where('store_id', (int) $storeId);
        }

        // Apply Search Filter (Order #, Sub-order #, SKU, Item Title, Customer, AWB)
        if ($search) {
            $search = trim((string) $search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('fulfilment_order_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                           ->orWhere('address_snapshot', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function ($iq) use ($search) {
                        $iq->where('title', 'like', "%{$search}%")
                           ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('shipments', function ($sq) use ($search) {
                        $sq->where('awb', 'like', "%{$search}%");
                    });
            });
        }

        // Handle CSV Export
        if ($request->query('export') === 'csv' || $request->query('export') === 'excel') {
            return $this->exportCsv($baseQuery);
        }

        $orders = $baseQuery->latest('id')->paginate(15)->withQueryString();
        $stores = Store::where('seller_id', $seller->id)->get();

        return view('seller.orders.index', compact(
            'orders',
            'tab',
            'channel',
            'paymentMethod',
            'paymentStatus',
            'dateFilter',
            'startDate',
            'endDate',
            'storeId',
            'search',
            'counts',
            'stores'
        ));
    }

    /**
     * Export permitted order data as a structured CSV.
     */
    protected function exportCsv($query): StreamedResponse
    {
        $filename = 'orders-export-' . now()->format('Y-m-d-His') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Order ID',
                'Sub-order Package #',
                'Order Date',
                'Product Items',
                'SKUs',
                'Total Qty',
                'Amount (INR)',
                'Payment Method',
                'Payment Status',
                'Shipment Status',
                'AWB Number',
                'Delivery City',
                'Delivery State',
                'Pincode',
                'Current Status',
            ]);

            $query->chunk(100, function ($orders) use ($handle) {
                foreach ($orders as $fo) {
                    $order = $fo->order;
                    $addr = is_array($order?->address_snapshot) ? $order->address_snapshot : [];

                    $itemTitles = $fo->items->pluck('title')->implode(' | ');
                    $itemSkus = $fo->items->pluck('sku')->implode(' | ');
                    $totalQty = $fo->items->sum('qty');

                    $payMethod = $order?->payment_method ? (is_object($order->payment_method) ? $order->payment_method->value : (string)$order->payment_method) : 'prepaid';
                    $payStatus = $order?->payment_status ? (is_object($order->payment_status) ? $order->payment_status->value : (string)$order->payment_status) : 'pending';
                    $awb = $fo->shipment?->awb ?? 'Pending';

                    fputcsv($handle, [
                        $order?->order_number ?? 'N/A',
                        $fo->fulfilment_order_number,
                        $fo->created_at->format('Y-m-d H:i:s'),
                        $itemTitles,
                        $itemSkus,
                        $totalQty,
                        number_format((float) $fo->total_amount, 2, '.', ''),
                        strtoupper($payMethod),
                        ucfirst($payStatus),
                        $fo->shipment ? 'Shipped' : 'Awaiting Courier Allocation',
                        $awb,
                        $addr['city'] ?? 'N/A',
                        $addr['state'] ?? 'N/A',
                        $addr['pincode'] ?? 'N/A',
                        ucwords(str_replace('_', ' ', $fo->status)),
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
     * Show detailed fulfilment order management console.
     */
    public function show(FulfilmentOrder $fulfilmentOrder): View
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403, 'Unauthorized access to this order.');
        }

        $fulfilmentOrder->loadMissing([
            'order.customer.user',
            'order.payments',
            'items.variant.product',
            'store',
            'inventoryLocation',
            'shipment.tracking',
            'statusHistory.changedByUser',
        ]);

        $allowedNext = $this->stateMachine->getAllowedNextFulfilmentStatuses($fulfilmentOrder);

        $storeStaff = $fulfilmentOrder->store_id 
            ? StoreStaff::where('store_id', $fulfilmentOrder->store_id)->with('user')->get() 
            : collect();

        return view('seller.orders.show', [
            'fo' => $fulfilmentOrder,
            'allowedNext' => $allowedNext,
            'storeStaff' => $storeStaff,
        ]);
    }

    /**
     * Perform state machine transition (Accept, Pack, Ready, Ship, etc.).
     */
    public function transition(Request $request, FulfilmentOrder $fulfilmentOrder): RedirectResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'target_status' => 'required|string',
            'note' => 'nullable|string|max:255',
        ]);

        $target = $request->input('target_status');
        $note = $request->input('note');

        try {
            $this->stateMachine->transitionFulfilmentOrder(
                $fulfilmentOrder,
                $target,
                $note,
                Auth::id()
            );

            return back()->with('success', "Order #{$fulfilmentOrder->fulfilment_order_number} transitioned to " . ucwords(str_replace('_', ' ', $target)) . " successfully!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Fast accept order from list or detail.
     */
    public function accept(FulfilmentOrder $fulfilmentOrder): RedirectResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $targetStatus = $fulfilmentOrder->fulfilment_type === FulfilmentType::QUICK ? 'store_accepted' : 'confirmed';

        try {
            $this->stateMachine->transitionFulfilmentOrder($fulfilmentOrder, $targetStatus, 'Seller accepted order', Auth::id());
            return back()->with('success', "Order #{$fulfilmentOrder->fulfilment_order_number} accepted and moved to processing.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark order ready for shipping (generates Shiprocket courier consignment).
     */
    public function readyToShip(FulfilmentOrder $fulfilmentOrder): RedirectResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403);
        }

        $targetStatus = $fulfilmentOrder->fulfilment_type === FulfilmentType::QUICK ? 'ready' : 'ready_to_ship';

        try {
            $this->stateMachine->transitionFulfilmentOrder($fulfilmentOrder, $targetStatus, 'Seller marked package ready to ship', Auth::id());
            return back()->with('success', "Order #{$fulfilmentOrder->fulfilment_order_number} marked ready to ship. Courier allocation and label dispatched.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download Packing Slip PDF.
     */
    public function packingSlip(FulfilmentOrder $fulfilmentOrder): Response
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403);
        }

        return $this->pdfService->generatePackingSlipPdf($fulfilmentOrder);
    }

    /**
     * Download Tax Invoice PDF.
     */
    public function invoice(FulfilmentOrder $fulfilmentOrder): Response
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403);
        }

        if (!$fulfilmentOrder->order) {
            abort(404, 'Parent order not found.');
        }

        return $this->pdfService->generateInvoicePdf($fulfilmentOrder->order);
    }

    /**
     * Download Shipping Label PDF.
     */
    public function shippingLabel(FulfilmentOrder $fulfilmentOrder): Response
    {
        $seller = Auth::user()->seller;
        if (!$seller || (int) $fulfilmentOrder->seller_id !== (int) $seller->id) {
            abort(403);
        }

        return $this->pdfService->generateShippingLabelPdf($fulfilmentOrder);
    }

    /**
     * API for Topbar audio alert & pending orders live badge polling.
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $seller = Auth::user()?->seller;
        if (!$seller) {
            return response()->json(['pending_count' => 0, 'new_orders' => 0]);
        }

        $pendingCount = FulfilmentOrder::where('seller_id', $seller->id)
            ->whereIn('status', ['placed', 'store_accepted'])
            ->count();

        // Orders placed in the last 60 seconds
        $freshPlaced = FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', 'placed')
            ->where('created_at', '>=', now()->subSeconds(45))
            ->count();

        return response()->json([
            'pending_count' => $pendingCount,
            'has_fresh' => $freshPlaced > 0,
            'timestamp' => now()->timestamp,
        ]);
    }
}
