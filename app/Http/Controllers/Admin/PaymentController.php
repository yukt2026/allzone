<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Services\Admin\AdminFinancialService;
use App\Services\Payment\PaymentService;
use App\Services\Settlement\SettlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected AdminFinancialService $financialService,
        protected SettlementService $settlementService
    ) {}

    /**
     * Shared query builder for payments table and CSV exports.
     */
    private function buildPaymentsQuery(Request $request): Builder
    {
        return $this->financialService->buildPaymentsQuery($request);
    }

    /**
     * Shared query builder for refunds table and CSV exports.
     */
    private function buildRefundsQuery(Request $request): Builder
    {
        return $this->financialService->buildRefundsQuery($request);
    }

    /**
     * Display centralized financial dashboard and transaction console.
     */
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'payments');
        $validTabs = ['payments', 'cod', 'refunds', 'webhooks', 'sellers', 'settlements', 'reports'];
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'payments';
        }

        $metrics = $this->financialService->getDashboardMetrics((string) $request->query('period', '30d'));

        // Load tab-specific data
        $payments = null;
        $codRemittances = null;
        $refunds = null;
        $webhookLogs = null;
        $sellerFinancials = null;
        $settlements = null;
        $sellers = null;

        switch ($tab) {
            case 'payments':
                $payments = $this->financialService->buildPaymentsQuery($request)->paginate(20)->withQueryString();
                break;
            case 'cod':
                $codRemittances = $this->financialService->buildCodRemittancesQuery($request)->paginate(20)->withQueryString();
                $sellers = Seller::whereIn('status', [\App\Enums\SellerStatus::APPROVED, 'approved', 'active'])->orderBy('business_name')->get();
                break;
            case 'refunds':
                $refunds = $this->financialService->buildRefundsQuery($request)->paginate(20)->withQueryString();
                break;
            case 'webhooks':
                $webhookLogs = $this->financialService->buildWebhookLogsQuery($request)->paginate(20)->withQueryString();
                break;
            case 'sellers':
                $sellerFinancials = $this->financialService->buildSellerFinancialsQuery($request);
                break;
            case 'settlements':
                $settlementsQuery = SellerSettlement::query()->with(['seller', 'items'])->latest('id');
                if ($status = $request->query('status')) {
                    if ($status !== 'all') {
                        $settlementsQuery->where('status', $status);
                    }
                }
                if ($sellerId = $request->query('seller_id')) {
                    $settlementsQuery->where('seller_id', $sellerId);
                }
                if ($search = trim((string) $request->query('search'))) {
                    $settlementsQuery->where(function ($q) use ($search) {
                        $q->where('settlement_number', 'like', "%{$search}%")
                          ->orWhere('reference', 'like', "%{$search}%")
                          ->orWhereHas('seller', fn($sq) => $sq->where('business_name', 'like', "%{$search}%"));
                    });
                }
                $settlements = $settlementsQuery->paginate(15)->withQueryString();
                $sellers = Seller::whereIn('status', [\App\Enums\SellerStatus::APPROVED, 'approved', 'active'])->orderBy('business_name')->get();
                break;
            case 'reports':
                // Metrics array already has all trends
                break;
        }

        // For backward compatibility with tests expecting $metrics on admin.payments.index
        $metrics['total_volume'] = $metrics['captured_online_volume'] + $metrics['total_cod_volume'];
        $metrics['paid_count'] = $metrics['online_count'];
        $metrics['refunded_amount'] = $metrics['total_refunds_amount'];
        $metrics['cod_count'] = (int) Payment::where('method', PaymentMethod::COD)->count();
        $metrics['pending_count'] = (int) Payment::whereIn('status', [PaymentStatus::PENDING, PaymentStatus::COD_PENDING])->count();
        $metrics['failed_count'] = (int) Payment::where('status', PaymentStatus::FAILED)->count();

        // Always provide payments paginator if not set so legacy view elements or tests don't throw
        if (!$payments && $tab !== 'payments') {
            $payments = $this->financialService->buildPaymentsQuery($request)->paginate(20)->withQueryString();
        }

        return view('admin.payments.index', compact(
            'tab',
            'metrics',
            'payments',
            'codRemittances',
            'refunds',
            'webhookLogs',
            'sellerFinancials',
            'settlements',
            'sellers'
        ));
    }

    /**
     * Display individual payment details with gateway audit log.
     */
    public function show(Payment $payment): View
    {
        $payment->load(['order.customer.user', 'order.fulfilmentOrders', 'refunds', 'reconciler']);

        $totalRefunded = (float) $payment->refunds()->where('status', RefundStatus::PROCESSED)->sum('amount');
        $refundableBalance = max(0.00, round((float) $payment->amount - $totalRefunded, 2));

        return view('admin.payments.show', compact('payment', 'totalRefunded', 'refundableBalance'));
    }

    /**
     * Reconcile a single payment manually.
     */
    public function reconcile(Request $request, Payment $payment): RedirectResponse
    {
        $notes = $request->input('notes');
        $this->financialService->reconcilePayment($payment, (int) Auth::id(), $notes);

        if (function_exists('activity')) {
            activity('financial_reconciliation')
                ->performedOn($payment)
                ->causedBy(Auth::user())
                ->withProperties(['payment_id' => $payment->id, 'notes' => $notes])
                ->log("Payment #{$payment->id} marked Reconciled");
        }

        return back()->with('success', "Payment #{$payment->id} successfully reconciled.");
    }

    /**
     * Batch reconcile payments.
     */
    public function reconcileBatch(Request $request): RedirectResponse
    {
        $paymentIds = $request->input('payment_ids');
        if (is_string($paymentIds)) {
            $paymentIds = array_filter(array_map('intval', explode(',', $paymentIds)));
        }

        $count = $this->financialService->batchReconcile((int) Auth::id(), $paymentIds);

        return back()->with('success', "{$count} payments successfully reconciled.");
    }

    /**
     * Return Razorpay webhook JSON payload for modal inspection.
     */
    public function webhookPayload(PaymentWebhookLog $webhookLog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'log' => $webhookLog,
        ]);
    }

    /**
     * Process refund on a paid or partially refunded payment.
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $refund = $this->paymentService->processRefund(
                $payment,
                (float) $validated['amount'],
                $validated['reason']
            );

            if (function_exists('activity')) {
                activity('payment')
                    ->performedOn($payment)
                    ->causedBy(auth()->user())
                    ->withProperties(['refund_id' => $refund->id, 'amount' => $validated['amount']])
                    ->log("Refund of " . format_currency((float)$validated['amount']) . " initiated for Payment #{$payment->id}");
            }

            return back()->with('success', "Refund of " . format_currency((float)$validated['amount']) . " processed successfully.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display list of all refunds with filter and metric cards.
     */
    public function refunds(Request $request): View
    {
        $refunds = $this->buildRefundsQuery($request)->paginate(20)->withQueryString();

        $metrics = [
            'total_refunded' => (float) Refund::where('status', RefundStatus::PROCESSED)->sum('amount'),
            'total_refunds_count' => Refund::count(),
            'pending_refunds_count' => Refund::where('status', RefundStatus::PENDING)->count(),
            'processed_count' => Refund::where('status', RefundStatus::PROCESSED)->count(),
        ];

        return view('admin.payments.refunds', compact('refunds', 'metrics'));
    }

    /**
     * Export payments ledger as CSV respecting current filters.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->financialService->exportPaymentsCsv($request);
    }

    /**
     * Export comprehensive financial summary report CSV.
     */
    public function exportFinancials(Request $request): StreamedResponse
    {
        return $this->financialService->exportFinancialSummaryCsv($request);
    }

    /**
     * Export refunds ledger as CSV respecting current filters.
     */
    public function exportRefunds(Request $request): StreamedResponse
    {
        $fileName = 'refunds-' . now()->format('Ymd-His') . '.csv';

        return response()->stream(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Refund ID',
                'Payment ID',
                'Order #',
                'Customer Name',
                'Reason',
                'Amount (INR)',
                'Status',
                'Gateway Refund ID',
                'Processed At',
                'Created At',
            ]);

            $this->buildRefundsQuery($request)->chunk(200, function ($refunds) use ($handle) {
                foreach ($refunds as $rf) {
                    fputcsv($handle, [
                        '#' . $rf->id,
                        '#' . $rf->payment_id,
                        $rf->payment?->order?->order_number ?? 'N/A',
                        $rf->payment?->order?->customer?->user?->name ?? 'N/A',
                        $rf->reason,
                        number_format((float) $rf->amount, 2, '.', ''),
                        $rf->status instanceof RefundStatus ? $rf->status->label() : (string) $rf->status,
                        $rf->gateway_refund_id ?? 'N/A',
                        $rf->processed_at?->toDateTimeString() ?? '',
                        $rf->created_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
