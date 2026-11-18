<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CODRemittanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\RefundStatus;
use App\Enums\SettlementStatus;
use App\Models\CodRemittance;
use App\Models\CommissionEntry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFinancialService
{
    /**
     * Compute comprehensive financial metrics.
     */
    public function getDashboardMetrics(?string $period = '30d'): array
    {
        $startDate = match ($period) {
            'yesterday' => Carbon::yesterday()->startOfDay(),
            '7d' => Carbon::now()->subDays(7)->startOfDay(),
            '30d' => Carbon::now()->subDays(30)->startOfDay(),
            '90d' => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        // Online Payments (Razorpay)
        $capturedOnlineVolume = (float) Payment::query()
            ->where('status', PaymentStatus::PAID)
            ->where('method', '!=', PaymentMethod::COD)
            ->sum('amount');

        $onlineCount = Payment::query()
            ->where('status', PaymentStatus::PAID)
            ->where('method', '!=', PaymentMethod::COD)
            ->count();

        // COD Metrics
        $totalCodVolume = (float) Payment::query()
            ->where('method', PaymentMethod::COD)
            ->where('status', PaymentStatus::PAID)
            ->sum('amount');

        $codCollectedDoorstep = (float) CodRemittance::query()
            ->whereIn('status', [CODRemittanceStatus::COLLECTED, CODRemittanceStatus::REMITTED])
            ->sum('amount');

        $codUnremittedAmount = (float) CodRemittance::query()
            ->where('status', CODRemittanceStatus::COLLECTED)
            ->sum('amount');

        $codRemittedAmount = (float) CodRemittance::query()
            ->where('status', CODRemittanceStatus::REMITTED)
            ->sum('amount');

        // Platform Revenue from Commission Entries
        $platformCommissionEarned = (float) CommissionEntry::query()
            ->where('entry_type', 'standard')
            ->sum('commission');

        $commissionGstEarned = (float) CommissionEntry::query()
            ->where('entry_type', 'standard')
            ->sum('tax_on_commission');

        $netPlatformRevenue = round($platformCommissionEarned + $commissionGstEarned, 2);

        // Seller Payables
        $unsettledSellerPayable = (float) CommissionEntry::query()
            ->where('entry_type', 'standard')
            ->sum('net_payable') - (float) SellerSettlement::query()
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->sum('net');

        $settledSellerPayable = (float) SellerSettlement::query()
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->sum('net');

        // Refunds
        $totalRefundsAmount = (float) Refund::query()
            ->where('status', RefundStatus::PROCESSED)
            ->sum('amount');

        $totalRefundsCount = Refund::query()
            ->where('status', RefundStatus::PROCESSED)
            ->count();

        // Reconciliation
        $reconciledCount = Payment::query()
            ->where('reconciliation_status', ReconciliationStatus::RECONCILED)
            ->count();

        $unreconciledCount = Payment::query()
            ->where('reconciliation_status', ReconciliationStatus::UNRECONCILED)
            ->count();

        $discrepancyCount = Payment::query()
            ->where('reconciliation_status', ReconciliationStatus::DISCREPANCY)
            ->count();

        // Webhook Logs
        $webhookTotal = PaymentWebhookLog::query()->count();
        $webhookProcessed = PaymentWebhookLog::query()->where('status', 'processed')->count();
        $webhookSuccessRate = $webhookTotal > 0 ? round(($webhookProcessed / $webhookTotal) * 100, 1) : 100.0;

        // Daily Collection Trend (Past 14 Days)
        $dailyTrend = Payment::query()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->where('status', PaymentStatus::PAID)
            ->where('created_at', '>=', Carbon::now()->subDays(14)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Monthly Collection (Past 12 Months)
        $monthlyCollections = Payment::query()
            ->selectRaw('strftime("%Y-%m", created_at) as month, SUM(amount) as total, COUNT(*) as count')
            ->where('status', PaymentStatus::PAID)
            ->where('created_at', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'captured_online_volume' => $capturedOnlineVolume,
            'online_count' => $onlineCount,
            'total_cod_volume' => $totalCodVolume,
            'cod_collected_doorstep' => $codCollectedDoorstep,
            'cod_unremitted_amount' => $codUnremittedAmount,
            'cod_remitted_amount' => $codRemittedAmount,
            'platform_commission_earned' => $platformCommissionEarned,
            'commission_gst_earned' => $commissionGstEarned,
            'net_platform_revenue' => $netPlatformRevenue,
            'unsettled_seller_payable' => max(0.00, round($unsettledSellerPayable, 2)),
            'settled_seller_payable' => $settledSellerPayable,
            'total_refunds_amount' => $totalRefundsAmount,
            'total_refunds_count' => $totalRefundsCount,
            'reconciled_count' => $reconciledCount,
            'unreconciled_count' => $unreconciledCount,
            'discrepancy_count' => $discrepancyCount,
            'webhook_total' => $webhookTotal,
            'webhook_success_rate' => $webhookSuccessRate,
            'daily_trend' => $dailyTrend,
            'monthly_collections' => $monthlyCollections,
        ];
    }

    /**
     * Build Payments Query with filters.
     */
    public function buildPaymentsQuery(Request $request): Builder
    {
        $query = Payment::query()
            ->with(['order.customer.user', 'refunds', 'reconciler'])
            ->latest('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $isExplicitId = str_starts_with($search, '#') && is_numeric(substr($search, 1));
            if ($isExplicitId) {
                $id = (int) substr($search, 1);
                $query->where(function (Builder $q) use ($id) {
                    $q->where('id', $id)->orWhere('order_id', $id);
                });
            } else {
                $query->where(function (Builder $q) use ($search) {
                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search);
                    }
                    $q->orWhere('gateway_payment_id', 'like', "%{$search}%")
                      ->orWhere('gateway_order_id', 'like', "%{$search}%")
                      ->orWhere('gateway_signature', 'like', "%{$search}%")
                      ->orWhereHas('order', fn(Builder $oq) => $oq->where('order_number', 'like', "%{$search}%"))
                      ->orWhereHas('order.customer.user', function (Builder $uq) use ($search) {
                          $uq->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
        }

        if ($gateway = $request->query('gateway')) {
            if ($gateway !== 'all') {
                $query->where('gateway', $gateway);
            }
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($method = $request->query('method')) {
            if ($method !== 'all') {
                $query->where('method', $method);
            }
        }

        if ($reconciliation = $request->query('reconciliation')) {
            if ($reconciliation !== 'all') {
                $query->where('reconciliation_status', $reconciliation);
            }
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Build COD Remittances Query.
     */
    public function buildCodRemittancesQuery(Request $request): Builder
    {
        $query = CodRemittance::query()
            ->with(['seller', 'shipment.fulfilmentOrder.order.customer.user'])
            ->latest('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn($sq) => $sq->where('business_name', 'like', "%{$search}%"))
                  ->orWhereHas('shipment', fn($shq) => $shq->where('awb', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($sellerId = $request->query('seller_id')) {
            $query->where('seller_id', $sellerId);
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Build Refunds Query.
     */
    public function buildRefundsQuery(Request $request): Builder
    {
        $query = Refund::query()
            ->with(['payment.order.customer.user', 'fulfilmentOrder.seller'])
            ->latest('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $isExplicitId = str_starts_with($search, '#') && is_numeric(substr($search, 1));
            if ($isExplicitId) {
                $id = (int) substr($search, 1);
                $query->where(function (Builder $q) use ($id) {
                    $q->where('id', $id)->orWhere('payment_id', $id);
                });
            } else {
                $query->where(function (Builder $q) use ($search) {
                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search)->orWhere('payment_id', (int) $search);
                    }
                    $q->orWhere('gateway_refund_id', 'like', "%{$search}%")
                      ->orWhere('reason', 'like', "%{$search}%")
                      ->orWhereHas('payment.order', fn(Builder $oq) => $oq->where('order_number', 'like', "%{$search}%"))
                      ->orWhereHas('payment.order.customer.user', fn(Builder $uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            }
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Build Razorpay Webhook Logs Query.
     */
    public function buildWebhookLogsQuery(Request $request): Builder
    {
        $query = PaymentWebhookLog::query()->latest('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                  ->orWhere('gateway_order_id', 'like', "%{$search}%")
                  ->orWhere('signature', 'like', "%{$search}%");
            });
        }

        if ($event = $request->query('event')) {
            if ($event !== 'all') {
                $query->where('event', $event);
            }
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        return $query;
    }

    /**
     * Build Seller Financials Aggregation.
     */
    public function buildSellerFinancialsQuery(Request $request): Collection
    {
        $query = Seller::query()
            ->with(['bankAccounts'])
            ->whereIn('status', [\App\Enums\SellerStatus::APPROVED, 'approved', 'active']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sellers = $query->get();

        return $sellers->map(function (Seller $seller) {
            $grossSales = (float) CommissionEntry::query()
                ->whereHas('fulfilmentOrder', fn($foq) => $foq->where('seller_id', $seller->id))
                ->where('entry_type', 'standard')
                ->sum('base_amount');

            $commissionDeducted = (float) CommissionEntry::query()
                ->whereHas('fulfilmentOrder', fn($foq) => $foq->where('seller_id', $seller->id))
                ->where('entry_type', 'standard')
                ->sum('commission');

            $taxDeducted = (float) CommissionEntry::query()
                ->whereHas('fulfilmentOrder', fn($foq) => $foq->where('seller_id', $seller->id))
                ->where('entry_type', 'standard')
                ->sum('tax_on_commission');

            $refundAdjustments = (float) CommissionEntry::query()
                ->whereHas('fulfilmentOrder', fn($foq) => $foq->where('seller_id', $seller->id))
                ->where('entry_type', 'reversal')
                ->sum('net_payable');

            $netPayableTotal = (float) CommissionEntry::query()
                ->whereHas('fulfilmentOrder', fn($foq) => $foq->where('seller_id', $seller->id))
                ->where('entry_type', 'standard')
                ->sum('net_payable') - $refundAdjustments;

            $paidSettlements = (float) SellerSettlement::query()
                ->where('seller_id', $seller->id)
                ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
                ->sum('net');

            $pendingSettlements = (float) SellerSettlement::query()
                ->where('seller_id', $seller->id)
                ->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::APPROVED, SettlementStatus::PROCESSING])
                ->sum('net');

            $outstandingBalance = max(0.00, round($netPayableTotal - $paidSettlements, 2));

            return [
                'seller' => $seller,
                'gross_sales' => $grossSales,
                'commission_deducted' => $commissionDeducted,
                'tax_deducted' => $taxDeducted,
                'refund_adjustments' => $refundAdjustments,
                'net_payable_total' => $netPayableTotal,
                'paid_settlements' => $paidSettlements,
                'pending_settlements' => $pendingSettlements,
                'outstanding_balance' => $outstandingBalance,
            ];
        });
    }

    /**
     * Reconcile a single payment manually.
     */
    public function reconcilePayment(Payment $payment, int $adminId, ?string $notes = null): Payment
    {
        $payment->reconciliation_status = ReconciliationStatus::RECONCILED;
        $payment->reconciled_at = now();
        $payment->reconciled_by = $adminId;
        if ($notes) {
            $payment->reconciliation_notes = $notes;
        }
        $payment->save();

        return $payment;
    }

    /**
     * Batch reconcile eligible payments.
     */
    public function batchReconcile(int $adminId, ?array $paymentIds = null): int
    {
        $query = Payment::query()->where('status', PaymentStatus::PAID);

        if (!empty($paymentIds)) {
            $query->whereIn('id', $paymentIds);
        } else {
            $query->whereIn('reconciliation_status', [
                ReconciliationStatus::UNRECONCILED,
                ReconciliationStatus::PENDING,
            ]);
        }

        return $query->update([
            'reconciliation_status' => ReconciliationStatus::RECONCILED,
            'reconciled_at' => now(),
            'reconciled_by' => $adminId,
            'reconciliation_notes' => 'Batch reconciled via Admin Financial Hub.',
        ]);
    }

    /**
     * Export payments ledger as CSV.
     */
    public function exportPaymentsCsv(Request $request): StreamedResponse
    {
        $fileName = 'payments-' . now()->format('Ymd-His') . '.csv';

        return response()->stream(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Payment ID',
                'Parent Order #',
                'Customer Name',
                'Customer Email',
                'Gateway',
                'Method',
                'Amount (INR)',
                'Currency',
                'Status',
                'Reconciliation Status',
                'Gateway Payment ID',
                'Gateway Order ID',
                'Gateway Signature',
                'Verified At',
                'Reconciled At',
                'Created At',
            ]);

            $this->buildPaymentsQuery($request)->chunk(200, function ($payments) use ($handle) {
                foreach ($payments as $p) {
                    fputcsv($handle, [
                        '#' . $p->id,
                        $p->order?->order_number ?? 'N/A',
                        $p->order?->customer?->user?->name ?? 'Guest',
                        $p->order?->customer?->user?->email ?? 'N/A',
                        strtoupper((string) $p->gateway),
                        $p->method instanceof PaymentMethod ? $p->method->label() : (string) $p->method,
                        number_format((float) $p->amount, 2, '.', ''),
                        $p->currency ?? 'INR',
                        $p->status instanceof PaymentStatus ? $p->status->label() : (string) $p->status,
                        $p->reconciliation_status instanceof ReconciliationStatus ? $p->reconciliation_status->label() : (string) $p->reconciliation_status,
                        $p->gateway_payment_id ?? '',
                        $p->gateway_order_id ?? '',
                        $p->gateway_signature ?? '',
                        $p->verified_at?->toDateTimeString() ?? '',
                        $p->reconciled_at?->toDateTimeString() ?? '',
                        $p->created_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export comprehensive financial report CSV.
     */
    public function exportFinancialSummaryCsv(Request $request): StreamedResponse
    {
        $date = now()->format('Y-m-d');
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"ALLZONE-Financial-Summary-{$date}.csv\"",
        ];

        return response()->stream(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'Seller ID',
                'Store Name',
                'Legal Name',
                'Gross Sales (INR)',
                'Platform Commission (INR)',
                '18% GST on Commission (INR)',
                'Refund Deductions (INR)',
                'Net Seller Payable (INR)',
                'Settled Paid (INR)',
                'Pending Settlements (INR)',
                'Outstanding Balance (INR)',
            ]);

            $sellerFinancials = $this->buildSellerFinancialsQuery($request);
            foreach ($sellerFinancials as $row) {
                fputcsv($handle, [
                    $row['seller']->id,
                    $row['seller']->business_name,
                    $row['seller']->legal_name,
                    number_format($row['gross_sales'], 2, '.', ''),
                    number_format($row['commission_deducted'], 2, '.', ''),
                    number_format($row['tax_deducted'], 2, '.', ''),
                    number_format($row['refund_adjustments'], 2, '.', ''),
                    number_format($row['net_payable_total'], 2, '.', ''),
                    number_format($row['paid_settlements'], 2, '.', ''),
                    number_format($row['pending_settlements'], 2, '.', ''),
                    number_format($row['outstanding_balance'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
