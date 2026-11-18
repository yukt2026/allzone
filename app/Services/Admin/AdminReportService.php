<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminReportService
{
    /**
     * Resolve start and end dates from preset or explicit inputs.
     *
     * @param array<string, mixed> $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDates(array $filters): array
    {
        $preset = $filters['preset'] ?? null;

        if ($preset === 'today') {
            return [now()->startOfDay(), now()->endOfDay()];
        }
        if ($preset === '7d') {
            return [now()->subDays(6)->startOfDay(), now()->endOfDay()];
        }
        if ($preset === '30d') {
            return [now()->subDays(29)->startOfDay(), now()->endOfDay()];
        }
        if ($preset === 'this_month') {
            return [now()->startOfMonth()->startOfDay(), now()->endOfDay()];
        }
        if ($preset === 'last_month') {
            return [now()->subMonth()->startOfMonth()->startOfDay(), now()->subMonth()->endOfMonth()->endOfDay()];
        }

        $start = !empty($filters['start_date'])
            ? Carbon::parse((string) $filters['start_date'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = !empty($filters['end_date'])
            ? Carbon::parse((string) $filters['end_date'])->endOfDay()
            : now()->endOfDay();

        if ($start->gt($end)) {
            $start = (clone $end)->subDays(30)->startOfDay();
        }

        return [$start, $end];
    }

    /**
     * Top-level KPI overview across all marketplace operations.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getSummaryMetrics(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        // Gross GMV (Avoid double counting split packages)
        $orderQuery = Order::whereBetween('created_at', [$start, $end])
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                  ->orWhere('status', '!=', OrderStatus::CANCELLED);
            });

        if (!empty($filters['seller_id'])) {
            $orderQuery->whereHas('fulfilmentOrders', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }

        $grossGmv = (float) (clone $orderQuery)->sum('total');
        $ordersCount = (clone $orderQuery)->count();

        // Refunds
        $refundQuery = Refund::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['processed', 'completed', 'success']);
        $totalRefunds = (float) $refundQuery->sum('amount');
        if ($totalRefunds <= 0 && Schema::hasTable('returns')) {
            $totalRefunds = (float) OrderReturn::whereBetween('created_at', [$start, $end])
                ->where('status', 'refunded')
                ->sum('refund_amount');
        }

        // Net Sales = Gross GMV - Refunds
        $netSales = max(0.00, $grossGmv - $totalRefunds);

        // Platform Revenue = Net Commission
        $commissionQuery = CommissionEntry::whereBetween('created_at', [$start, $end]);
        if (!empty($filters['seller_id'])) {
            $commissionQuery->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }
        $platformRevenue = (float) $commissionQuery->sum('commission');

        // Seller Payable = Total Net Payable to sellers
        $sellerPayableQuery = FulfilmentOrder::whereBetween('created_at', [$start, $end]);
        if (!empty($filters['seller_id'])) {
            $sellerPayableQuery->where('seller_id', $filters['seller_id']);
        }
        $sellerPayable = (float) $sellerPayableQuery->sum('seller_payable');
        if ($sellerPayable <= 0) {
            $sellerPayable = max(0.00, $grossGmv - $platformRevenue - $totalRefunds);
        }

        // Collected Payments
        $paymentQuery = Payment::whereBetween('created_at', [$start, $end])
            ->whereIn('status', [PaymentStatus::PAID, PaymentStatus::AUTHORIZED]);
        $collectedPayments = (float) $paymentQuery->sum('amount');

        return compact(
            'grossGmv',
            'netSales',
            'platformRevenue',
            'sellerPayable',
            'collectedPayments',
            'totalRefunds',
            'ordersCount'
        );
    }

    /**
     * 1. Sales Performance Report.
     */
    public function getSalesReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = Order::query()->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->whereHas('fulfilmentOrders', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }
        if (!empty($filters['order_status'])) {
            $query->where('status', $filters['order_status']);
        } else {
            $query->where(function ($q) {
                $q->where('payment_status', 'paid')
                  ->orWhere('status', '!=', OrderStatus::CANCELLED);
            });
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $totalRevenue = (float) (clone $query)->sum('total');
        $totalOrders = (clone $query)->count();
        $totalDeliveryFees = (float) (clone $query)->sum('delivery_fee');
        $totalTax = (float) (clone $query)->sum('tax');
        $totalDiscounts = (float) (clone $query)->sum('discount');
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.00;

        $daily = (clone $query)
            ->selectRaw('DATE(created_at) as date, count(*) as order_count, sum(total) as gross, sum(delivery_fee) as delivery_fee, sum(subtotal) as net, sum(discount) as total_discount')
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) desc')
            ->get();

        return compact(
            'totalRevenue',
            'totalOrders',
            'totalDeliveryFees',
            'totalTax',
            'totalDiscounts',
            'avgOrderValue',
            'daily'
        );
    }

    /**
     * 2. Orders & Fulfilment Channels Report.
     */
    public function getOrdersReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $orderQuery = Order::whereBetween('created_at', [$start, $end]);
        if (!empty($filters['seller_id'])) {
            $orderQuery->whereHas('fulfilmentOrders', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }
        if (!empty($filters['order_status'])) {
            $orderQuery->where('status', $filters['order_status']);
        }
        if (!empty($filters['payment_status'])) {
            $orderQuery->where('payment_status', $filters['payment_status']);
        }

        $byStatus = (clone $orderQuery)
            ->selectRaw('status, count(*) as count, sum(total) as total')
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $statusVal = $row->status instanceof \BackedEnum ? $row->status->value : (string) ($row->status ?? 'unknown');
                $label = $row->status instanceof OrderStatus ? $row->status->label() : ucfirst(str_replace('_', ' ', $statusVal));
                return (object) [
                    'status' => $statusVal,
                    'label' => $label,
                    'count' => (int) $row->count,
                    'total' => (float) $row->total,
                ];
            });

        $byMethod = (clone $orderQuery)
            ->selectRaw('payment_method, count(*) as count, sum(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($row) {
                $methodVal = $row->payment_method instanceof \BackedEnum ? $row->payment_method->value : (string) ($row->payment_method ?? 'N/A');
                $label = $row->payment_method instanceof \App\Enums\PaymentMethod ? $row->payment_method->label() : strtoupper($methodVal);
                return (object) [
                    'payment_method' => $methodVal,
                    'label' => $label,
                    'count' => (int) $row->count,
                    'total' => (float) $row->total,
                ];
            });

        $foQuery = FulfilmentOrder::whereBetween('created_at', [$start, $end]);
        if (!empty($filters['seller_id'])) {
            $foQuery->where('seller_id', $filters['seller_id']);
        }

        $byFulfilment = (clone $foQuery)
            ->selectRaw('fulfilment_type, count(*) as count, sum(subtotal) as total')
            ->groupBy('fulfilment_type')
            ->get()
            ->map(function ($row) {
                $typeVal = $row->fulfilment_type instanceof \BackedEnum ? $row->fulfilment_type->value : (string) ($row->fulfilment_type ?? 'standard');
                $label = match ($typeVal) {
                    'quick' => '⚡ Quick Commerce',
                    'pickup' => '🏬 Store Pickup',
                    default => '🚚 Standard Cargo',
                };
                return (object) [
                    'fulfilment_type' => $typeVal,
                    'label' => $label,
                    'count' => (int) $row->count,
                    'total' => (float) $row->total,
                ];
            });

        $totalOrderCount = (clone $orderQuery)->count();
        $totalOrderValue = (float) (clone $orderQuery)->sum('total');

        $recentOrders = (clone $orderQuery)
            ->with(['customer.user', 'fulfilmentOrders.seller'])
            ->latest('id')
            ->limit(15)
            ->get();

        return compact('byStatus', 'byMethod', 'byFulfilment', 'totalOrderCount', 'totalOrderValue', 'recentOrders');
    }

    /**
     * 3. Seller Performance Leaderboard Report.
     */
    public function getSellersReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $sellerQuery = Seller::query();
        if (!empty($filters['seller_id'])) {
            $sellerQuery->where('id', $filters['seller_id']);
        }

        $sellers = $sellerQuery
            ->withCount(['fulfilmentOrders as period_orders_count' => fn($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['fulfilmentOrders as period_gmv' => fn($q) => $q->whereBetween('created_at', [$start, $end])], 'subtotal')
            ->withSum(['fulfilmentOrders as period_commission' => fn($q) => $q->whereBetween('created_at', [$start, $end])], 'commission_amount')
            ->withSum(['fulfilmentOrders as period_payable' => fn($q) => $q->whereBetween('created_at', [$start, $end])], 'seller_payable')
            ->get()
            ->map(function ($s) use ($start, $end) {
                $gmv = (float) ($s->period_gmv ?? 0);
                $comm = (float) ($s->period_commission ?? 0);
                $ordersCount = (int) ($s->period_orders_count ?? 0);
                $payable = (float) ($s->period_payable ?? ($gmv - $comm));

                // Return metrics for seller
                $returnsCount = 0;
                if (Schema::hasTable('returns')) {
                    $returnsCount = OrderReturn::whereHas('orderItem.fulfilmentOrder', fn($q) => $q->where('seller_id', $s->id))
                        ->whereBetween('created_at', [$start, $end])
                        ->count();
                }
                $returnRate = $ordersCount > 0 ? round(($returnsCount / $ordersCount) * 100, 1) : 0.0;

                return [
                    'id' => $s->id,
                    'name' => $s->business_name,
                    'status' => $s->status?->value ?? (string) $s->status,
                    'orders_count' => $ordersCount,
                    'gross_gmv' => $gmv,
                    'commission' => $comm,
                    'net_payout' => $payable,
                    'returns_count' => $returnsCount,
                    'return_rate' => $returnRate,
                ];
            })
            ->sortByDesc('gross_gmv')
            ->values()
            ->all();

        $totalSellersCount = count($sellers);
        $totalSellersGmv = array_sum(array_column($sellers, 'gross_gmv'));
        $totalSellersCommission = array_sum(array_column($sellers, 'commission'));

        return compact('sellers', 'totalSellersCount', 'totalSellersGmv', 'totalSellersCommission');
    }

    /**
     * 4. Customer Growth & Lifetime Value Report.
     */
    public function getCustomersReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $totalCustomers = Customer::count();
        $newCustomers = Customer::whereBetween('created_at', [$start, $end])->count();

        // Active customers with at least one order in period
        $activeCustomers = Customer::whereHas('orders', fn($q) => $q->whereBetween('created_at', [$start, $end]))->count();

        // Repeat customers with 2+ orders in period
        $repeatCustomers = DB::table('orders')
            ->select('customer_id')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('customer_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();

        $repeatRate = $activeCustomers > 0 ? round(($repeatCustomers / $activeCustomers) * 100, 1) : 0.0;

        // Top customers leaderboard
        $topCustomers = Customer::with('user')
            ->whereHas('orders', fn($q) => $q->whereBetween('created_at', [$start, $end]))
            ->withCount(['orders as period_orders' => fn($q) => $q->whereBetween('created_at', [$start, $end])])
            ->withSum(['orders as period_spent' => fn($q) => $q->whereBetween('created_at', [$start, $end])], 'total')
            ->orderByDesc('period_spent')
            ->limit(20)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->user?->name ?? 'Customer #' . $c->id,
                'email' => $c->user?->email ?? 'N/A',
                'orders_count' => (int) $c->period_orders,
                'total_spent' => (float) ($c->period_spent ?? 0),
                'registered_at' => $c->created_at?->format('Y-m-d') ?? 'N/A',
            ]);

        return compact(
            'totalCustomers',
            'newCustomers',
            'activeCustomers',
            'repeatCustomers',
            'repeatRate',
            'topCustomers'
        );
    }

    /**
     * 5. Product Performance & Turnover Report.
     */
    public function getProductsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('sellers', 'products.seller_id', '=', 'sellers.id')
            ->selectRaw('
                products.id,
                products.title,
                categories.name as category_name,
                sellers.business_name as seller_name,
                SUM(order_items.qty) as units_sold,
                SUM(order_items.total) as total_revenue,
                AVG(order_items.price) as avg_price
            ')
            ->whereBetween('order_items.created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->where('products.seller_id', $filters['seller_id']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }
        if (!empty($filters['product_search'])) {
            $term = $filters['product_search'];
            $query->where(function ($q) use ($term) {
                $q->where('products.title', 'like', "%{$term}%")
                  ->orWhere('order_items.sku', 'like', "%{$term}%");
            });
        }

        $products = $query
            ->groupBy('products.id', 'products.title', 'categories.name', 'sellers.business_name')
            ->orderByDesc('total_revenue')
            ->limit(50)
            ->get();

        $totalUnitsSold = (int) $products->sum('units_sold');
        $totalProductRevenue = (float) $products->sum('total_revenue');

        return compact('products', 'totalUnitsSold', 'totalProductRevenue');
    }

    /**
     * 6. Category Performance Report.
     */
    public function getCategoriesReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $aggregates = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('products.category_id, SUM(order_items.qty) as units, SUM(order_items.total) as revenue')
            ->whereBetween('order_items.created_at', [$start, $end])
            ->groupBy('products.category_id')
            ->get()
            ->keyBy('category_id');

        $categories = Category::all()->map(function ($cat) use ($aggregates) {
            $agg = $aggregates->get($cat->id);
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'units' => $agg ? (int) $agg->units : 0,
                'revenue' => $agg ? (float) $agg->revenue : 0.00,
            ];
        })
        ->sortByDesc('revenue')
        ->values()
        ->all();

        $totalCategoriesCount = count($categories);
        $totalUnits = array_sum(array_column($categories, 'units'));
        $totalCategoryRevenue = array_sum(array_column($categories, 'revenue'));

        return compact('categories', 'totalCategoriesCount', 'totalUnits', 'totalCategoryRevenue');
    }

    /**
     * 7. Centralized Inventory & Stock Valuation Report.
     */
    public function getInventoryReport(array $filters): array
    {
        $query = Inventory::with(['variant.product.category', 'variant.product.seller', 'location']);

        if (!empty($filters['seller_id'])) {
            $query->whereHas('variant.product', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('variant.product', fn($q) => $q->where('category_id', $filters['category_id']));
        }

        $stockStatus = $filters['stock_status'] ?? null;
        if ($stockStatus === 'low_stock') {
            $query->whereRaw('quantity_on_hand <= low_stock_threshold AND quantity_on_hand > 0');
        } elseif ($stockStatus === 'out_of_stock') {
            $query->where('quantity_on_hand', '<=', 0);
        } elseif ($stockStatus === 'in_stock') {
            $query->whereRaw('quantity_on_hand > low_stock_threshold');
        }

        $totalSkus = ProductVariant::count();
        $totalOnHand = (int) Inventory::sum('quantity_on_hand');
        $totalReserved = (int) Inventory::sum('quantity_reserved');
        $totalAvailable = max(0, $totalOnHand - $totalReserved);

        $lowStockCount = Inventory::whereRaw('quantity_on_hand <= low_stock_threshold AND quantity_on_hand > 0')->count();
        $outOfStockCount = Inventory::where('quantity_on_hand', '<=', 0)->count();

        $items = $query->latest('updated_at')->paginate(25)->withQueryString();

        return compact(
            'totalSkus',
            'totalOnHand',
            'totalReserved',
            'totalAvailable',
            'lowStockCount',
            'outOfStockCount',
            'items'
        );
    }

    /**
     * 8. Returns & RTO Management Report.
     */
    public function getReturnsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = OrderReturn::with(['orderItem.product', 'orderItem.fulfilmentOrder.seller'])
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->whereHas('orderItem.fulfilmentOrder', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }

        $totalReturns = (clone $query)->count();
        $approvedReturns = (clone $query)->where('status', 'approved')->count();
        $rejectedReturns = (clone $query)->where('status', 'rejected')->count();
        $pendingQc = (clone $query)->whereIn('status', ['received', 'in_transit'])->count();
        $refundedReturns = (clone $query)->where('status', 'refunded')->count();
        $totalRefundAmount = (float) (clone $query)->where('status', 'refunded')->sum('refund_amount');

        // Reasons breakdown
        $reasons = (clone $query)
            ->selectRaw('reason, count(*) as count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get();

        $items = (clone $query)->latest('id')->paginate(20)->withQueryString();

        return compact(
            'totalReturns',
            'approvedReturns',
            'rejectedReturns',
            'pendingQc',
            'refundedReturns',
            'totalRefundAmount',
            'reasons',
            'items'
        );
    }

    /**
     * 9. Seller Claims & Disputes Report.
     */
    public function getClaimsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = SellerClaim::with(['seller', 'order'])
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        $totalClaims = (clone $query)->count();
        $openClaims = (clone $query)->whereIn('status', ['open', 'under_review'])->count();
        $approvedClaims = (clone $query)->where('status', 'approved')->count();
        $rejectedClaims = (clone $query)->where('status', 'rejected')->count();
        $closedClaims = (clone $query)->where('status', 'closed')->count();

        $totalClaimed = (float) (clone $query)->sum('claimed_amount');
        $totalApproved = (float) (clone $query)->whereIn('status', ['approved', 'closed'])->sum('approved_amount');

        $items = (clone $query)->latest('id')->paginate(20)->withQueryString();

        return compact(
            'totalClaims',
            'openClaims',
            'approvedClaims',
            'rejectedClaims',
            'closedClaims',
            'totalClaimed',
            'totalApproved',
            'items'
        );
    }

    /**
     * 10. Customer Payments & Reconciliation Report.
     */
    public function getPaymentsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = Payment::with(['order.customer.user'])
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['payment_status'])) {
            $query->where('status', $filters['payment_status']);
        }

        $totalPayments = (clone $query)->count();
        $capturedPayments = (clone $query)->whereIn('status', [PaymentStatus::PAID, PaymentStatus::AUTHORIZED])->count();
        $pendingPayments = (clone $query)->where('status', PaymentStatus::PENDING)->count();
        $failedPayments = (clone $query)->where('status', PaymentStatus::FAILED)->count();

        $totalCapturedAmount = (float) (clone $query)->whereIn('status', [PaymentStatus::PAID, PaymentStatus::AUTHORIZED])->sum('amount');

        // By Payment Gateway / Method
        $byMethod = (clone $query)
            ->selectRaw('method, count(*) as count, sum(amount) as total')
            ->groupBy('method')
            ->get();

        $items = (clone $query)->latest('id')->paginate(25)->withQueryString();

        return compact(
            'totalPayments',
            'capturedPayments',
            'pendingPayments',
            'failedPayments',
            'totalCapturedAmount',
            'byMethod',
            'items'
        );
    }

    /**
     * 11. Customer Refunds Report.
     */
    public function getRefundsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = Refund::with(['payment.order', 'fulfilmentOrder.seller'])
            ->whereBetween('created_at', [$start, $end]);

        $totalRefunds = (clone $query)->count();
        $processedRefunds = (clone $query)->whereIn('status', ['processed', 'completed', 'success'])->count();
        $pendingRefunds = (clone $query)->where('status', 'pending')->count();
        $totalRefundAmount = (float) (clone $query)->whereIn('status', ['processed', 'completed', 'success'])->sum('amount');

        $items = (clone $query)->latest('id')->paginate(25)->withQueryString();

        return compact(
            'totalRefunds',
            'processedRefunds',
            'pendingRefunds',
            'totalRefundAmount',
            'items'
        );
    }

    /**
     * 12. Platform Commission & Tax Disbursements Report.
     */
    public function getCommissionReport(array $filters, bool $paginate = true): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = CommissionEntry::with(['fulfilmentOrder.order', 'fulfilmentOrder.seller', 'order'])
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->whereHas('fulfilmentOrder', fn($q) => $q->where('seller_id', $filters['seller_id']));
        }

        $totalBaseGmv = (float) (clone $query)->sum('base_amount');
        $totalCommission = (float) (clone $query)->sum('commission');
        $totalTax = (float) (clone $query)->sum('tax_on_commission');
        $totalSettled = (float) (clone $query)->sum('net_payable');

        $entries = $paginate ? (clone $query)->latest('id')->paginate(20)->withQueryString() : (clone $query)->latest('id')->get();

        return compact('entries', 'totalBaseGmv', 'totalCommission', 'totalTax', 'totalSettled');
    }

    /**
     * 13. Seller Settlements Ledger Report.
     */
    public function getSettlementsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = SellerSettlement::with('seller')
            ->whereBetween('created_at', [$start, $end]);

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        $totalSettlements = (clone $query)->count();
        $paidSettlements = (clone $query)->whereIn('status', ['paid', 'settled'])->count();
        $pendingSettlements = (clone $query)->where('status', 'pending')->count();
        $failedSettlements = (clone $query)->where('status', 'failed')->count();

        $totalGross = (float) (clone $query)->sum('gross');
        $totalDeductions = (float) (clone $query)->sum('commission');
        $totalNetPaid = (float) (clone $query)->whereIn('status', ['paid', 'settled'])->sum('net');

        $items = (clone $query)->latest('id')->paginate(20)->withQueryString();

        return compact(
            'totalSettlements',
            'paidSettlements',
            'pendingSettlements',
            'failedSettlements',
            'totalGross',
            'totalDeductions',
            'totalNetPaid',
            'items'
        );
    }

    /**
     * 14. Promotional Campaigns & Discount Report.
     */
    public function getPromotionsReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $query = Campaign::whereBetween('created_at', [$start, $end]);

        $totalCampaigns = (clone $query)->count();
        $activeCampaigns = (clone $query)->where('is_active', true)->count();
        $totalUsageCount = (int) (clone $query)->sum('usage_count');

        $items = (clone $query)->latest('id')->paginate(20)->withQueryString();

        return compact(
            'totalCampaigns',
            'activeCampaigns',
            'totalUsageCount',
            'items'
        );
    }

    /**
     * 15. Tax & GST Reconciliation Report (Preserved for compatibility).
     */
    public function getTaxReport(array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                  ->orWhere('status', '!=', OrderStatus::CANCELLED);
            });

        $grossRevenue = (float) (clone $orders)->sum('total');
        $productTax = (float) (clone $orders)->sum('tax');

        $commissionTax = (float) CommissionEntry::whereBetween('created_at', [$start, $end])->sum('tax_on_commission');
        $totalTaxCollected = $productTax + $commissionTax;

        $recentOrdersWithTax = Order::with('customer.user')
            ->whereBetween('created_at', [$start, $end])
            ->where('tax', '>', 0)
            ->latest('id')
            ->limit(10)
            ->get();

        return compact('grossRevenue', 'productTax', 'commissionTax', 'totalTaxCollected', 'recentOrdersWithTax');
    }

    /**
     * Get standardized export data (title, headings, rows) for CSV / XLSX / PDF.
     *
     * @return array{title: string, headings: array<string>, rows: array<array<mixed>>}
     */
    public function getExportData(string $type, array $filters): array
    {
        [$start, $end] = $this->resolveDates($filters);
        $period = $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d');

        switch ($type) {
            case 'orders':
                $data = $this->getOrdersReport($filters);
                $headings = ['Status / Dimension', 'Category / Method', 'Count', 'Total Value (INR)'];
                $rows = [];
                foreach ($data['byStatus'] as $row) {
                    $rows[] = ['Order Status', $row->label, $row->count, number_format((float) $row->total, 2, '.', '')];
                }
                foreach ($data['byMethod'] as $row) {
                    $rows[] = ['Payment Method', $row->label, $row->count, number_format((float) $row->total, 2, '.', '')];
                }
                foreach ($data['byFulfilment'] as $row) {
                    $rows[] = ['Fulfilment Channel', $row->label, $row->count, number_format((float) $row->total, 2, '.', '')];
                }
                return [
                    'title' => 'Orders & Fulfilment Channels Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'sellers':
                $data = $this->getSellersReport($filters);
                $headings = ['Seller ID', 'Business Name', 'Status', 'Orders Fulfilled', 'Gross GMV (INR)', 'Commission (INR)', 'Net Earnings (INR)', 'Return Rate (%)'];
                $rows = [];
                foreach ($data['sellers'] as $s) {
                    $rows[] = [
                        $s['id'],
                        $s['name'],
                        $s['status'],
                        $s['orders_count'],
                        number_format((float) $s['gross_gmv'], 2, '.', ''),
                        number_format((float) $s['commission'], 2, '.', ''),
                        number_format((float) $s['net_payout'], 2, '.', ''),
                        $s['return_rate'] . '%',
                    ];
                }
                return [
                    'title' => 'Seller Performance Leaderboard (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'customers':
                $data = $this->getCustomersReport($filters);
                $headings = ['Customer ID', 'Name', 'Email', 'Orders Count', 'Total Spent (INR)', 'Registration Date'];
                $rows = [];
                foreach ($data['topCustomers'] as $c) {
                    $rows[] = [
                        $c['id'],
                        $c['name'],
                        $c['email'],
                        $c['orders_count'],
                        number_format((float) $c['total_spent'], 2, '.', ''),
                        $c['registered_at'],
                    ];
                }
                return [
                    'title' => 'Customer Growth & LTV Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'products':
                $data = $this->getProductsReport($filters);
                $headings = ['Product ID', 'Product Title', 'Category', 'Seller', 'Units Sold', 'Gross Revenue (INR)', 'Average Price (INR)'];
                $rows = [];
                foreach ($data['products'] as $p) {
                    $rows[] = [
                        $p->id,
                        $p->title,
                        $p->category_name,
                        $p->seller_name,
                        $p->units_sold,
                        number_format((float) $p->total_revenue, 2, '.', ''),
                        number_format((float) $p->avg_price, 2, '.', ''),
                    ];
                }
                return [
                    'title' => 'Product Sales Performance Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'categories':
                $data = $this->getCategoriesReport($filters);
                $headings = ['Category ID', 'Category Name', 'Units Dispatched', 'Gross Revenue (INR)'];
                $rows = [];
                foreach ($data['categories'] as $c) {
                    $rows[] = [
                        $c['id'] ?? 'N/A',
                        $c['name'],
                        $c['units'],
                        number_format((float) $c['revenue'], 2, '.', ''),
                    ];
                }
                return [
                    'title' => 'Category Turnover & Demand Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'inventory':
                $data = $this->getInventoryReport($filters);
                $headings = ['SKU', 'Product Title', 'Seller', 'Available Stock', 'Reserved Stock', 'On-Hand Stock', 'Low-Stock Limit'];
                $rows = [];
                foreach ($data['items'] as $inv) {
                    $rows[] = [
                        $inv->variant?->sku ?? 'N/A',
                        $inv->variant?->product?->title ?? 'N/A',
                        $inv->variant?->product?->seller?->business_name ?? 'N/A',
                        max(0, $inv->quantity_on_hand - $inv->quantity_reserved),
                        $inv->quantity_reserved,
                        $inv->quantity_on_hand,
                        $inv->low_stock_threshold,
                    ];
                }
                return [
                    'title' => 'Marketplace Inventory Status Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'returns':
                $data = $this->getReturnsReport($filters);
                $headings = ['Return Number', 'Product', 'Seller', 'Reason', 'Status', 'Refund Amount (INR)', 'Requested Date'];
                $rows = [];
                foreach ($data['items'] as $ret) {
                    $rows[] = [
                        $ret->return_number,
                        $ret->orderItem?->product?->title ?? 'N/A',
                        $ret->orderItem?->fulfilmentOrder?->seller?->business_name ?? 'N/A',
                        $ret->reason,
                        $ret->status,
                        number_format((float) $ret->refund_amount, 2, '.', ''),
                        $ret->created_at?->format('Y-m-d') ?? 'N/A',
                    ];
                }
                return [
                    'title' => 'Returns & RTO Performance Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'claims':
                $data = $this->getClaimsReport($filters);
                $headings = ['Claim Number', 'Seller', 'Order #', 'Claim Type', 'Claimed Amount (INR)', 'Approved Amount (INR)', 'Status', 'Date'];
                $rows = [];
                foreach ($data['items'] as $clm) {
                    $rows[] = [
                        $clm->claim_number,
                        $clm->seller?->business_name ?? 'N/A',
                        $clm->order?->order_number ?? 'N/A',
                        $clm->claim_type instanceof \BackedEnum ? $clm->claim_type->value : (string) $clm->claim_type,
                        number_format((float) $clm->claimed_amount, 2, '.', ''),
                        number_format((float) $clm->approved_amount, 2, '.', ''),
                        $clm->status,
                        $clm->created_at?->format('Y-m-d') ?? 'N/A',
                    ];
                }
                return [
                    'title' => 'Seller Claims & Disputes Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'payments':
                $data = $this->getPaymentsReport($filters);
                $headings = ['Payment ID', 'Order #', 'Method', 'Gateway', 'Amount (INR)', 'Status', 'Reconciliation', 'Timestamp'];
                $rows = [];
                foreach ($data['items'] as $p) {
                    $rows[] = [
                        $p->id,
                        $p->order?->order_number ?? 'N/A',
                        $p->method,
                        $p->gateway,
                        number_format((float) $p->amount, 2, '.', ''),
                        $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
                        $p->reconciliation_status instanceof \BackedEnum ? $p->reconciliation_status->value : (string) ($p->reconciliation_status ?? 'unreconciled'),
                        $p->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                    ];
                }
                return [
                    'title' => 'Customer Payments Ledger (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'refunds':
                $data = $this->getRefundsReport($filters);
                $headings = ['Refund ID', 'Order #', 'Seller', 'Gateway Refund ID', 'Amount (INR)', 'Reason', 'Status', 'Date'];
                $rows = [];
                foreach ($data['items'] as $ref) {
                    $rows[] = [
                        $ref->id,
                        $ref->payment?->order?->order_number ?? 'N/A',
                        $ref->fulfilmentOrder?->seller?->business_name ?? 'N/A',
                        $ref->gateway_refund_id ?? 'N/A',
                        number_format((float) $ref->amount, 2, '.', ''),
                        $ref->reason ?? 'Customer Return',
                        $ref->status,
                        $ref->created_at?->format('Y-m-d') ?? 'N/A',
                    ];
                }
                return [
                    'title' => 'Customer Refunds Audit Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'commission':
                $data = $this->getCommissionReport($filters, false);
                $headings = ['Entry ID', 'Timestamp', 'Order #', 'Merchant Name', 'Base Order GMV (INR)', 'Rate %', 'Commission Pre-Tax (INR)', 'GST on Commission (INR)', 'Net Payable (INR)'];
                $rows = [];
                foreach ($data['entries'] as $entry) {
                    $rows[] = [
                        $entry->id,
                        $entry->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                        $entry->order?->order_number ?? 'N/A',
                        $entry->fulfilmentOrder?->seller?->business_name ?? 'N/A',
                        number_format((float) $entry->base_amount, 2, '.', ''),
                        ($entry->rate ?? 0) . '%',
                        number_format((float) $entry->commission, 2, '.', ''),
                        number_format((float) $entry->tax_on_commission, 2, '.', ''),
                        number_format((float) $entry->net_payable, 2, '.', ''),
                    ];
                }
                return [
                    'title' => 'Platform Commission Ledger (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'settlements':
                $data = $this->getSettlementsReport($filters);
                $headings = ['Settlement #', 'Seller', 'Period Start', 'Period End', 'Gross (INR)', 'Commission (INR)', 'Net Payable (INR)', 'Status', 'Bank UTR Reference'];
                $rows = [];
                foreach ($data['items'] as $s) {
                    $rows[] = [
                        $s->settlement_number,
                        $s->seller?->business_name ?? 'N/A',
                        $s->period_start?->format('Y-m-d') ?? 'N/A',
                        $s->period_end?->format('Y-m-d') ?? 'N/A',
                        number_format((float) $s->gross, 2, '.', ''),
                        number_format((float) $s->commission, 2, '.', ''),
                        number_format((float) $s->net, 2, '.', ''),
                        $s->status?->value ?? (string) $s->status,
                        $s->reference ?? 'Pending Bank Transfer',
                    ];
                }
                return [
                    'title' => 'Seller Settlements & Disbursements Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'promotions':
                $data = $this->getPromotionsReport($filters);
                $headings = ['Campaign ID', 'Name', 'Type', 'Discount Value', 'Start Date', 'End Date', 'Usage Count', 'Status'];
                $rows = [];
                foreach ($data['items'] as $p) {
                    $rows[] = [
                        $p->id,
                        $p->name,
                        $p->type,
                        $p->discount_value,
                        $p->starts_at?->format('Y-m-d') ?? 'N/A',
                        $p->ends_at?->format('Y-m-d') ?? 'N/A',
                        $p->usage_count,
                        $p->is_active ? 'Active' : 'Inactive',
                    ];
                }
                return [
                    'title' => 'Promotions & Campaigns Performance Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            case 'tax':
                $data = $this->getTaxReport($filters);
                $headings = ['Tax Metric', 'Amount (INR)'];
                $rows = [
                    ['Gross Paid Platform Revenue', number_format((float) $data['grossRevenue'], 2, '.', '')],
                    ['Merchant GST Collected (Product Tax)', number_format((float) $data['productTax'], 2, '.', '')],
                    ['Platform Commission GST (18%)', number_format((float) $data['commissionTax'], 2, '.', '')],
                    ['Total Tax Liability / Collected', number_format((float) $data['totalTaxCollected'], 2, '.', '')],
                ];
                return [
                    'title' => 'Tax & GST Accounting Reconciliation (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];

            default: // sales
                $data = $this->getSalesReport($filters);
                $headings = ['Date', 'Orders Count', 'Gross Revenue (INR)', 'Delivery Fees (INR)', 'Net Cart Subtotal (INR)', 'Discounts (INR)'];
                $rows = [];
                foreach ($data['daily'] as $row) {
                    $rows[] = [
                        $row->date,
                        $row->order_count,
                        number_format((float) $row->gross, 2, '.', ''),
                        number_format((float) $row->delivery_fee, 2, '.', ''),
                        number_format((float) $row->net, 2, '.', ''),
                        number_format((float) ($row->total_discount ?? 0), 2, '.', ''),
                    ];
                }
                return [
                    'title' => 'Daily Sales Performance Report (' . $period . ')',
                    'headings' => $headings,
                    'rows' => $rows,
                ];
        }
    }
}
