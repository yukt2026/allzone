<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\QCStatus;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Models\Seller;
use App\Services\Admin\AdminReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnController extends Controller
{
    public function __construct(
        protected AdminReturnService $adminReturnService
    ) {}

    /**
     * Display centralized Returns, RTO, and Refund dashboard and registry.
     */
    public function index(Request $request): View
    {
        $statusTab = $request->query('status', 'all');
        $viewTab = $request->query('view', 'returns'); // 'returns', 'rto', 'analytics'

        $metrics = $this->adminReturnService->getReturnMetrics();
        $returnReasons = $this->adminReturnService->getReturnReasonsDistribution();
        $productTrends = $this->adminReturnService->getProductReturnTrends(6);

        $sellers = Seller::orderBy('business_name')->get(['id', 'business_name', 'legal_name']);

        if ($viewTab === 'rto' || $statusTab === 'rto') {
            $rtoShipments = $this->adminReturnService->buildRtoQuery()->paginate(15)->withQueryString();
            $returns = null;
        } else {
            $returns = $this->adminReturnService->buildReturnsQuery($request->all())->paginate(15)->withQueryString();
            $rtoShipments = null;
        }

        return view('admin.returns.index', compact(
            'metrics',
            'returns',
            'rtoShipments',
            'returnReasons',
            'productTrends',
            'sellers',
            'statusTab',
            'viewTab'
        ));
    }

    /**
     * Detailed Return & Dispute Inspection Console.
     */
    public function show(OrderReturn $return): View
    {
        $return->load([
            'orderItem.variant.product.media',
            'orderItem.fulfilmentOrder.order.customer.user',
            'orderItem.fulfilmentOrder.order.payments',
            'orderItem.fulfilmentOrder.seller.user',
            'orderItem.fulfilmentOrder.store',
            'pickupShipment.tracking',
            'refund',
        ]);

        return view('admin.returns.show', compact('return'));
    }

    /**
     * Approve return request with optional Shiprocket reverse pickup.
     */
    public function approve(Request $request, OrderReturn $return): RedirectResponse
    {
        $notes = $request->input('notes') ?? $request->input('remarks') ?? 'Return approved by administrator.';
        if (strlen((string) $notes) < 3) {
            $notes = 'Return approved by administrator.';
        }

        try {
            $this->adminReturnService->approveReturn(
                $return,
                (string) $notes,
                Auth::user(),
                $request->boolean('schedule_pickup', $request->boolean('schedule_reverse_pickup', true))
            );

            return back()->with('success', "Return #{$return->return_number} has been approved.");
        } catch (\Throwable $e) {
            return back()->with('error', "Approval failed: " . $e->getMessage());
        }
    }

    /**
     * Reject return request with mandatory reason.
     */
    public function reject(Request $request, OrderReturn $return): RedirectResponse
    {
        $reason = $request->input('reason') ?? $request->input('notes') ?? $request->input('rejection_reason');

        if (!$reason || strlen(trim((string) $reason)) < 3) {
            return back()->with('error', 'A valid rejection reason must be provided (minimum 3 characters).');
        }

        try {
            $this->adminReturnService->rejectReturn($return, (string) $reason, Auth::user());

            return back()->with('success', "Return #{$return->return_number} has been rejected.");
        } catch (\Throwable $e) {
            return back()->with('error', "Rejection failed: " . $e->getMessage());
        }
    }

    /**
     * Request additional information or photo evidence from customer.
     */
    public function requestInfo(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'instructions' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        try {
            $this->adminReturnService->requestAdditionalInfo($return, $validated['instructions'], Auth::user());

            return back()->with('success', "Information request recorded for Return #{$return->return_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', "Action failed: " . $e->getMessage());
        }
    }

    /**
     * Record Quality Control inspection outcome.
     */
    public function recordQc(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'in:passed,failed'],
            'qc_notes' => ['nullable', 'string', 'max:1000'],
            'is_resellable' => ['nullable', 'boolean'],
        ]);

        try {
            $this->adminReturnService->recordQcOutcome(
                $return,
                $validated['outcome'],
                $validated['qc_notes'] ?? null,
                $request->boolean('is_resellable', true),
                Auth::user()
            );

            return back()->with('success', "QC outcome ({$validated['outcome']}) recorded for Return #{$return->return_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', "QC record failed: " . $e->getMessage());
        }
    }

    /**
     * Initiate eligible refund through Razorpay (Online) or verified Payout (COD/Manual).
     */
    public function initiateRefund(Request $request, OrderReturn $return): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->hasAnyRole(['super_admin', 'admin', 'finance_admin', 'finance_manager']) && !$user->can('payments.refund'))) {
            abort(403, 'Unauthorized. Only Finance Managers and Super Administrators can execute refunds.');
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:1'],
            'mode' => ['nullable', 'string', 'in:original,wallet,cod_payout'],
            'payout_reference' => ['nullable', 'string', 'max:100'],
            'confirm_cod_payout' => ['nullable', 'boolean'],
            'is_resellable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->adminReturnService->processRefund($return, $validated, Auth::user());

            return back()->with('success', "Refund of ₹{$return->refund_amount} executed successfully for Return #{$return->return_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', "Refund processing failed: " . $e->getMessage());
        }
    }

    /**
     * Update return status manually with audit log.
     */
    public function updateStatus(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'note' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $newStatus = ReturnStatus::from($validated['status']);
            $this->adminReturnService->updateReturnStatus($return, $newStatus, $validated['note'], Auth::user());

            return back()->with('success', "Return status updated to {$newStatus->label()}.");
        } catch (\Throwable $e) {
            return back()->with('error', "Status update failed: " . $e->getMessage());
        }
    }

    /**
     * Admin Dispute Override (Approve / Reject / Change decision).
     */
    public function override(Request $request, OrderReturn $return): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        if ($validated['action'] === 'approve') {
            return $this->approve($request, $return);
        }

        return $this->reject($request, $return);
    }

    /**
     * Admin Force-Refund Override (Bypasses delays or executes immediate refund).
     */
    public function forceRefund(Request $request, OrderReturn $return): RedirectResponse
    {
        return $this->initiateRefund($request, $return);
    }

    /**
     * Export Returns CSV report.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'allzone-returns-report-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Return Number',
                'Order Number',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Seller',
                'Item Title',
                'SKU',
                'Quantity',
                'Refund Amount',
                'Refund Mode',
                'Return Reason',
                'Status',
                'QC Status',
                'Resellable',
                'Shiprocket Waybill',
                'Created At',
                'Refunded At',
            ]);

            OrderReturn::with([
                'orderItem.fulfilmentOrder.seller',
                'orderItem.fulfilmentOrder.order.customer.user',
            ])->chunk(150, function ($returns) use ($handle) {
                foreach ($returns as $r) {
                    $item = $r->orderItem;
                    $order = $item?->fulfilmentOrder?->order;
                    $user = $order?->customer?->user ?? $order?->user;
                    $seller = $item?->fulfilmentOrder?->seller;

                    fputcsv($handle, [
                        $r->return_number,
                        $order?->order_number ?? 'N/A',
                        $user?->name ?? 'N/A',
                        $user?->email ?? 'N/A',
                        $user?->phone ?? 'N/A',
                        $seller?->business_name ?? $seller?->legal_name ?? 'N/A',
                        $item?->title ?? 'N/A',
                        $item?->sku ?? 'N/A',
                        $r->qty ?? $item?->qty ?? 1,
                        $r->refund_amount ?? $item?->total ?? 0,
                        $r->refund_mode ?? 'original',
                        $r->reason,
                        $r->status->value,
                        $r->qc_status->value,
                        $r->is_resellable ? 'Yes' : 'No',
                        $r->pickup_waybill ?? 'N/A',
                        $r->created_at->toDateTimeString(),
                        $r->refunded_at?->toDateTimeString() ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
