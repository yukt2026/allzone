<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\Seller;
use App\Services\Admin\AdminOrderService;
use App\Services\Order\OrderPdfService;
use App\Services\Order\OrderStateMachine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        protected AdminOrderService $orderService,
        protected OrderStateMachine $stateMachine,
        protected OrderPdfService $pdfService
    ) {}

    /**
     * Shared orders query builder supporting searching, filtering, and export.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildOrdersQuery(Request $request)
    {
        return $this->orderService->buildOrdersQuery($request->all());
    }

    /**
     * Global Orders Registry supporting all 10 tabs and search filters.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $tab = $request->query('status', $request->query('tab', 'all'));
        $filters = array_merge($request->all(), ['status' => $tab]);

        $orders = $this->orderService->buildOrdersQuery($filters)->paginate(15)->withQueryString();
        $tabCounts = $this->orderService->getOrderTabCounts();
        $metrics = $this->orderService->getOrderMetrics();
        $sellers = Seller::orderBy('business_name')->get();

        return view('admin.orders.index', [
            'orders' => $orders,
            'metrics' => $metrics,
            'tabCounts' => $tabCounts,
            'sellers' => $sellers,
            'status' => $tab,
            'tab' => $tab,
            'paymentStatus' => $request->query('payment_status', 'all'),
            'paymentMethod' => $request->query('payment_method', 'all'),
            'fulfilmentType' => $request->query('fulfilment_type', 'all'),
            'dateRange' => $request->query('date_range', 'all'),
            'fromDate' => $request->query('from_date', ''),
            'toDate' => $request->query('to_date', ''),
            'sellerId' => $request->query('seller_id', ''),
            'search' => $request->query('search', ''),
        ]);
    }

    /**
     * Stream CSV export of orders matching active filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Order::class);

        return $this->orderService->exportOrdersCsv($request->all());
    }

    /**
     * Master Order Detail Console with complete multi-vendor packages, Shiprocket, and tracking.
     */
    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->loadMissing([
            'customer.user',
            'customerAddress',
            'payments',
            'fulfilmentOrders.items.variant.product.media',
            'fulfilmentOrders.store',
            'fulfilmentOrders.seller.user',
            'fulfilmentOrders.shipments.tracking',
            'fulfilmentOrders.shipments.rtoCases',
            'fulfilmentOrders.items.returns.refund',
            'fulfilmentOrders.statusHistory.changedByUser',
            'statusHistory.changedByUser',
            'fraudAssessment',
        ]);

        // Tax calculation breakdown (Intra-state CGST+SGST vs Inter-state IGST)
        $deliveryState = strtolower(trim((string) ($order->address_snapshot['state'] ?? '')));
        $taxTotal = (float) $order->tax;

        return view('admin.orders.show', [
            'order' => $order,
            'deliveryState' => $deliveryState,
            'taxTotal' => $taxTotal,
        ]);
    }

    /**
     * Admin Force Override Order Status with mandatory reason.
     */
    public function overrideStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('override', $order);

        $request->validate([
            'status' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $newStatus = OrderStatus::from($request->input('status'));
        $reason = (string) $request->input('reason');

        $this->stateMachine->adminOverride($order, $newStatus, $reason, (int) Auth::id());

        return back()->with('success', "Order #{$order->order_number} status overridden to {$newStatus->label()} successfully.");
    }

    /**
     * Add administrative internal note.
     */
    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $timestamp = now()->format('d M Y, h:i A');
        $adminName = Auth::user()?->name ?? 'Admin';
        $newNote = "[{$timestamp} by {$adminName}]: " . trim((string) $request->input('note'));

        $existingNotes = $order->notes ? $order->notes . "\n\n" : '';
        $order->notes = $existingNotes . $newNote;
        $order->save();

        // Also record in status history as internal note
        OrderStatusHistory::create([
            'orderable_type' => Order::class,
            'orderable_id' => $order->id,
            'from_status' => $order->status->value,
            'to_status' => $order->status->value,
            'note' => "[INTERNAL NOTE]: " . trim((string) $request->input('note')),
            'changed_by' => (int) Auth::id(),
        ]);

        return back()->with('success', 'Internal note added to order.');
    }

    /**
     * Admin cancel full order with mandatory reason.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->orderService->cancelOrder($order, $validated['reason'], Auth::user());

        return back()->with('success', "Order #{$order->order_number} cancelled successfully.");
    }

    /**
     * Cancel an individual sub-order package.
     */
    public function cancelSubOrder(Request $request, FulfilmentOrder $fulfilmentOrder): RedirectResponse
    {
        $this->authorize('cancel', $fulfilmentOrder->order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->orderService->cancelSubOrder($fulfilmentOrder, $validated['reason'], Auth::user());

        return back()->with('success', "Sub-Order #{$fulfilmentOrder->fulfilment_order_number} cancelled.");
    }

    /**
     * Resolve an order dispute.
     */
    public function resolveDispute(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('override', $order);

        $validated = $request->validate([
            'resolution' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->orderService->resolveDispute($order, $validated['resolution'], Auth::user());

        return back()->with('success', "Dispute resolution recorded for Order #{$order->order_number}.");
    }

    /**
     * Approve customer return request from order view.
     */
    public function approveReturn(Request $request, Order $order, OrderReturn $return): RedirectResponse
    {
        $this->authorize('override', $order);

        $return->update([
            'status' => \App\Enums\ReturnStatus::APPROVED,
            'approved_at' => now(),
            'admin_notes' => 'Approved by Order Administration on ' . now()->format('d M Y'),
        ]);

        return back()->with('success', "Return Request #{$return->return_number} approved.");
    }

    /**
     * Download Tax Invoice PDF from Admin.
     */
    public function invoice(Order $order): Response
    {
        $this->authorize('view', $order);

        return $this->pdfService->generateInvoicePdf($order);
    }
}
