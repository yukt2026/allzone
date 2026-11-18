<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\CommissionRuleScope;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPricingCommissionService
{
    /**
     * Compute platform-wide pricing, commission, and financial ledger summary metrics.
     *
     * @return array<string, mixed>
     */
    public function getSummaryMetrics(): array
    {
        $standardEntries = CommissionEntry::where('entry_type', 'standard');
        $reversalEntries = CommissionEntry::where('entry_type', 'reversal');

        $grossSales = (float) $standardEntries->sum('base_amount');
        $standardCommission = (float) $standardEntries->sum('commission');
        $standardTax = (float) $standardEntries->sum('tax_on_commission');
        $standardGatewayFee = (float) $standardEntries->sum('gateway_fee');
        $standardNetPayable = (float) $standardEntries->sum('net_payable');

        $reversalSales = abs((float) $reversalEntries->sum('base_amount'));
        $reversalCommission = abs((float) $reversalEntries->sum('commission'));
        $reversalTax = abs((float) $reversalEntries->sum('tax_on_commission'));
        $reversalNet = abs((float) $reversalEntries->sum('net_payable'));

        $rulesCount = CommissionRule::count();
        $activeRulesCount = CommissionRule::where('is_active', true)->count();

        return [
            'gross_sales' => $grossSales,
            'net_sales' => max(0.0, $grossSales - $reversalSales),
            'platform_commission' => $standardCommission,
            'net_commission' => max(0.0, $standardCommission - $reversalCommission),
            'tax_on_commission' => $standardTax,
            'net_tax' => max(0.0, $standardTax - $reversalTax),
            'gateway_fee_deductions' => $standardGatewayFee,
            'seller_payable' => $standardNetPayable,
            'net_seller_payable' => max(0.0, $standardNetPayable - $reversalNet),
            'refund_adjustments' => $reversalSales,
            'total_fee_deductions' => $standardCommission + $standardTax + $standardGatewayFee,
            'total_rules' => $rulesCount,
            'active_rules' => $activeRulesCount,
        ];
    }

    /**
     * Build query for commission rules with scope, category, seller, product filters.
     *
     * @param array<string, mixed> $filters
     */
    public function buildCommissionRulesQuery(array $filters = []): Builder
    {
        $query = CommissionRule::query()
            ->with(['category', 'seller', 'product'])
            ->orderByDesc('priority')
            ->latest('id');

        if (!empty($filters['scope']) && $filters['scope'] !== 'all') {
            $query->where('scope', $filters['scope']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== 'all') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('category', fn(Builder $cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn(Builder $sq) => $sq->where('business_name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn(Builder $pq) => $pq->where('title', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Build query for commission history (CommissionEntry ledger).
     *
     * @param array<string, mixed> $filters
     */
    public function buildCommissionHistoryQuery(array $filters = []): Builder
    {
        $query = CommissionEntry::query()
            ->with([
                'fulfilmentOrder.seller',
                'fulfilmentOrder.order.customer.user',
                'rule',
            ])
            ->latest('id');

        if (!empty($filters['entry_type']) && $filters['entry_type'] !== 'all') {
            $query->where('entry_type', $filters['entry_type']);
        }

        if (!empty($filters['seller_id'])) {
            $query->whereHas('fulfilmentOrder', fn(Builder $fq) => $fq->where('seller_id', $filters['seller_id']));
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('fulfilmentOrder', function (Builder $fq) use ($search) {
                      $fq->where('fulfilment_order_number', 'like', "%{$search}%")
                         ->orWhereHas('order', fn(Builder $oq) => $oq->where('order_number', 'like', "%{$search}%"))
                         ->orWhereHas('seller', fn(Builder $sq) => $sq->where('business_name', 'like', "%{$search}%"));
                  });
            });
        }

        return $query;
    }

    /**
     * Retrieve all current platform charges and pricing control settings.
     *
     * @return array<string, mixed>
     */
    public function getPricingControls(): array
    {
        return [
            // Platform Charges
            'default_commission_pct' => (float) Setting::get('default_commission_pct', 10.00),
            'commission_gst_pct' => (float) Setting::get('commission_gst_pct', 18.00),
            'payment_gateway_fee_pct' => (float) Setting::get('payment_gateway_fee_pct', 2.00),
            'gateway_fee_borne_by_seller' => (bool) Setting::get('gateway_fee_borne_by_seller', true),
            'pass_delivery_fee_to_seller' => (bool) Setting::get('pass_delivery_fee_to_seller', false),
            'fixed_platform_fee_per_order' => (float) Setting::get('fixed_platform_fee_per_order', 0.00),

            // Pricing Controls & Safeguards
            'min_product_price' => (float) Setting::get('min_product_price', 10.00),
            'max_product_price' => (float) Setting::get('max_product_price', 500000.00),
            'max_discount_percentage' => (float) Setting::get('max_discount_percentage', 80.00),
            'max_coupon_discount_pct' => (float) Setting::get('max_coupon_discount_pct', 50.00),
            'max_coupon_discount_flat' => (float) Setting::get('max_coupon_discount_flat', 1000.00),
            'require_admin_approval_for_large_price_drop' => (bool) Setting::get('require_admin_approval_for_large_price_drop', true),
        ];
    }

    /**
     * Update platform pricing controls and fee settings with audit logging.
     *
     * @param array<string, mixed> $data
     */
    public function updatePricingControls(array $data, User $admin): void
    {
        $validKeys = [
            'default_commission_pct',
            'commission_gst_pct',
            'payment_gateway_fee_pct',
            'gateway_fee_borne_by_seller',
            'pass_delivery_fee_to_seller',
            'fixed_platform_fee_per_order',
            'min_product_price',
            'max_product_price',
            'max_discount_percentage',
            'max_coupon_discount_pct',
            'max_coupon_discount_flat',
            'require_admin_approval_for_large_price_drop',
        ];

        foreach ($validKeys as $key) {
            if (array_key_exists($key, $data)) {
                $val = is_bool($data[$key]) ? ($data[$key] ? '1' : '0') : (string) $data[$key];
                Setting::set($key, $val);
            }
        }

        if (function_exists('activity')) {
            activity('pricing_settings')
                ->causedBy($admin)
                ->withProperties(['updated_settings' => array_intersect_key($data, array_flip($validKeys))])
                ->log("Admin #{$admin->id} updated platform pricing and fee controls.");
        }
    }

    /**
     * Build query for price change audit history.
     *
     * @param array<string, mixed> $filters
     */
    public function buildPriceHistoryQuery(array $filters = []): Builder
    {
        $query = ProductPriceHistory::query()
            ->with([
                'variant.product',
                'seller',
                'user',
            ])
            ->latest('id');

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        if (!empty($filters['change_type']) && $filters['change_type'] !== 'all') {
            $query->where('change_type', $filters['change_type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhereHas('variant', function (Builder $vq) use ($search) {
                      $vq->where('sku', 'like', "%{$search}%")
                         ->orWhereHas('product', fn(Builder $pq) => $pq->where('title', 'like', "%{$search}%"));
                  })
                  ->orWhereHas('seller', fn(Builder $sq) => $sq->where('business_name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Stream financial reconciliation report CSV.
     *
     * @param array<string, mixed> $filters
     */
    public function exportFinancialReportCsv(array $filters = []): StreamedResponse
    {
        $query = $this->buildCommissionHistoryQuery($filters);
        $filename = 'allzone-financial-ledger-report-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Entry ID',
                'Timestamp',
                'Entry Type',
                'Fulfilment Order #',
                'Parent Order #',
                'Merchant Name',
                'Base Gross Sales (₹)',
                'Platform Commission Pre-Tax (₹)',
                'GST on Commission (18%) (₹)',
                'Payment Gateway Fee (₹)',
                'Shipping Adjustment (₹)',
                'Net Seller Payable (₹)',
                'Commission Rule',
                'Accounting Notes',
            ]);

            $query->chunk(200, function ($entries) use ($handle) {
                foreach ($entries as $e) {
                    $fo = $e->fulfilmentOrder;
                    $order = $fo?->order;
                    $seller = $fo?->seller;

                    fputcsv($handle, [
                        $e->id,
                        $e->created_at->format('Y-m-d H:i:s'),
                        strtoupper($e->entry_type),
                        $fo?->fulfilment_order_number ?? 'N/A',
                        $order?->order_number ?? 'N/A',
                        $seller?->business_name ?? 'N/A',
                        number_format((float) $e->base_amount, 2, '.', ''),
                        number_format((float) $e->commission, 2, '.', ''),
                        number_format((float) $e->tax_on_commission, 2, '.', ''),
                        number_format((float) $e->gateway_fee, 2, '.', ''),
                        number_format((float) $e->shipping_adjustment, 2, '.', ''),
                        number_format((float) $e->net_payable, 2, '.', ''),
                        $e->rule?->name ?? 'System Default Tier',
                        $e->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
