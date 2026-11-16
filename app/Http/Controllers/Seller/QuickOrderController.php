<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\FulfilmentType;
use App\Http\Controllers\Controller;
use App\Jobs\Cargo\CreateShipmentJob;
use App\Models\FulfilmentOrder;
use App\Models\Store;
use App\Services\Order\OrderStateMachine;
use App\Services\QuickCommerce\QuickCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuickOrderController extends Controller
{
    public function __construct(
        protected OrderStateMachine $stateMachine,
        protected QuickCommerceService $quickService
    ) {}

    /**
     * Realtime Incoming Order Screen (/seller/orders/quick-live).
     */
    public function liveConsole(Request $request): View
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            abort(403, 'Seller profile required.');
        }

        $stores = $seller->stores()->get();
        $storeId = $request->query('store_id') ?: (session('seller_active_store_id') ?: session('active_store_id'));
        $store = $storeId ? $stores->firstWhere('id', (int) $storeId) : $stores->first();
        if ($store) {
            session(['seller_active_store_id' => $store->id]);
        }

        $activeQuickOrders = FulfilmentOrder::query()
            ->with(['order.customerAddress', 'items.variant.product', 'shipment.tracking'])
            ->where('seller_id', $seller->id)
            ->where('fulfilment_type', FulfilmentType::QUICK)
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'ready', 'picked_up', 'in_transit', 'out_for_delivery'])
            ->latest('id')
            ->get();

        $initialOrders = $activeQuickOrders->map(function ($fo) {
            $timeout = $fo->store ? $fo->store->effectiveQuickTimeout() : 120;
            $elapsed = now()->diffInSeconds($fo->created_at ?? now());
            $remaining = max(0, $timeout - $elapsed);

            $customerName = $fo->order?->customerAddress?->name
                ?? ($fo->order?->address_snapshot['name'] ?? null)
                ?? ($fo->order?->customer?->user?->name ?? 'Customer');

            $customerAddress = $fo->order?->customerAddress?->line1
                ?? ($fo->order?->address_snapshot['address_line_1'] ?? ($fo->order?->address_snapshot['line1'] ?? ''));

            return [
                'id' => $fo->id,
                'order_number' => $fo->fulfilment_order_number,
                'parent_order_number' => $fo->order?->order_number,
                'status' => $fo->status,
                'status_label' => ucwords(str_replace('_', ' ', $fo->status)),
                'items_count' => $fo->items->sum('qty'),
                'items' => $fo->items->map(fn ($it) => [
                    'title' => $it->title,
                    'qty' => $it->qty,
                    'variant_name' => $it->variant_name,
                ]),
                'subtotal' => (float) $fo->subtotal,
                'delivery_fee' => (float) $fo->delivery_fee,
                'total' => (float) $fo->total_amount,
                'elapsed_seconds' => $elapsed,
                'remaining_seconds' => $remaining,
                'customer_name' => $customerName,
                'customer_address' => $customerAddress,
                'courier' => $fo->shipment ? [
                    'name' => $fo->shipment->courier ?? 'Shiprocket Courier',
                    'awb' => $fo->shipment->awb,
                    'status' => $fo->shipment->status?->value ?? (string) $fo->shipment->status,
                ] : null,
            ];
        });

        return view('seller.orders.quick-live', [
            'store' => $store,
            'stores' => $stores,
            'initialOrders' => $initialOrders,
            'timeoutSeconds' => $store ? $store->effectiveQuickTimeout() : 120,
        ]);
    }

    /**
     * Polling fallback endpoint for live quick orders console.
     */
    public function pendingOrdersApi(Request $request): JsonResponse
    {
        $seller = Auth::user()->seller;
        if (!$seller) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $storeId = $request->query('store_id') ?: (session('seller_active_store_id') ?: session('active_store_id'));

        $orders = FulfilmentOrder::query()
            ->with(['order.customerAddress', 'items.variant.product', 'shipment.tracking'])
            ->where('seller_id', $seller->id)
            ->where('fulfilment_type', FulfilmentType::QUICK)
            ->when($storeId, fn ($q) => $q->where('store_id', (int) $storeId))
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'ready', 'picked_up', 'in_transit', 'out_for_delivery'])
            ->latest('id')
            ->get()
            ->map(function ($fo) {
                $timeout = $fo->store ? $fo->store->effectiveQuickTimeout() : 120;
                $elapsed = now()->diffInSeconds($fo->created_at ?? now());
                $remaining = max(0, $timeout - $elapsed);

                $customerName = $fo->order?->customerAddress?->name
                    ?? ($fo->order?->address_snapshot['name'] ?? null)
                    ?? ($fo->order?->customer?->user?->name ?? 'Customer');

                $customerAddress = $fo->order?->customerAddress?->line1
                    ?? ($fo->order?->address_snapshot['address_line_1'] ?? ($fo->order?->address_snapshot['line1'] ?? ''));

                return [
                    'id' => $fo->id,
                    'order_number' => $fo->fulfilment_order_number,
                    'parent_order_number' => $fo->order?->order_number,
                    'status' => $fo->status,
                    'status_label' => ucwords(str_replace('_', ' ', $fo->status)),
                    'items_count' => $fo->items->sum('qty'),
                    'items' => $fo->items->map(fn ($it) => [
                        'title' => $it->title,
                        'qty' => $it->qty,
                        'variant_name' => $it->variant_name,
                    ]),
                    'subtotal' => (float) $fo->subtotal,
                    'delivery_fee' => (float) $fo->delivery_fee,
                    'total' => (float) $fo->total_amount,
                    'elapsed_seconds' => $elapsed,
                    'remaining_seconds' => $remaining,
                    'customer_name' => $customerName,
                    'customer_address' => $customerAddress,
                    'courier' => $fo->shipment ? [
                        'name' => $fo->shipment->courier ?? 'Shiprocket Courier',
                        'awb' => $fo->shipment->awb,
                        'status' => $fo->shipment->status?->value ?? (string) $fo->shipment->status,
                    ] : null,
                ];
            });

        return response()->json([
            'count' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    /**
     * Accept incoming quick commerce order.
     */
    public function accept(Request $request, FulfilmentOrder $fulfilmentOrder): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($fulfilmentOrder);

        try {
            $this->stateMachine->transitionFulfilmentOrder(
                $fulfilmentOrder,
                'store_accepted',
                'Order accepted by store staff',
                Auth::id()
            );

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Order accepted!']);
            }

            return back()->with('success', "Order #{$fulfilmentOrder->fulfilment_order_number} accepted!");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject incoming quick commerce order (auto triggers rerouting to next eligible store).
     */
    public function reject(Request $request, FulfilmentOrder $fulfilmentOrder): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($fulfilmentOrder);

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $reason = $request->input('reason', 'Store rejected order due to item unavailability / high load');

        try {
            $this->stateMachine->transitionFulfilmentOrder(
                $fulfilmentOrder,
                'rejected',
                $reason,
                Auth::id()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order rejected and auto-rerouted to fallback location.',
                ]);
            }

            return back()->with('notice', "Order #{$fulfilmentOrder->fulfilment_order_number} rejected and rerouted.");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark package packing started.
     */
    public function pack(Request $request, FulfilmentOrder $fulfilmentOrder): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($fulfilmentOrder);

        try {
            $this->stateMachine->transitionFulfilmentOrder(
                $fulfilmentOrder,
                'packing',
                'Staff is packing items',
                Auth::id()
            );

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Order marked packing!']);
            }

            return back()->with('success', 'Order marked packing!');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark package packed and ready for pickup (submits order to Shiprocket for courier assignment).
     */
    public function ready(Request $request, FulfilmentOrder $fulfilmentOrder): JsonResponse|RedirectResponse
    {
        $this->authorizeOrder($fulfilmentOrder);

        try {
            $this->stateMachine->transitionFulfilmentOrder(
                $fulfilmentOrder,
                'ready',
                'Order packed and ready for Shiprocket courier pickup',
                Auth::id()
            );

            // Automatically submit eligible package to Shiprocket
            $fulfilmentOrder->refresh();
            if (!$fulfilmentOrder->shipment) {
                try {
                    CreateShipmentJob::dispatch($fulfilmentOrder);
                } catch (\Throwable $shipErr) {
                    report($shipErr);
                }
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Order marked ready and submitted to Shiprocket!']);
            }

            return back()->with('success', 'Order marked ready and submitted to Shiprocket!');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    protected function authorizeOrder(FulfilmentOrder $fo): void
    {
        $seller = Auth::user()->seller;
        if (!$seller || $fo->seller_id !== $seller->id) {
            abort(403, 'Unauthorized access to this order.');
        }
    }
}

