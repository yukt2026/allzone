<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\RtoCase;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardService
{
    protected const CACHE_TTL = 180; // 3-minute cache for freshness

    /**
     * Get platform-wide KPI metrics with trend comparisons vs previous period.
     *
     * @return array<string, mixed>
     */
    public function getTopStats(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_dash_top_stats_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->parsePeriodDates($range, $customStart, $customEnd);

            // 1. Total GMV / Gross Sales
            $currentGmv = (float) Order::whereBetween('created_at', [$currentStart, $currentEnd])
                ->where('status', '!=', OrderStatus::CANCELLED)
                ->sum('total');

            $previousGmv = (float) Order::whereBetween('created_at', [$previousStart, $previousEnd])
                ->where('status', '!=', OrderStatus::CANCELLED)
                ->sum('total');

            // 2. Total Orders
            $currentOrders = Order::whereBetween('created_at', [$currentStart, $currentEnd])->count();
            $previousOrders = Order::whereBetween('created_at', [$previousStart, $previousEnd])->count();

            // 3. Sellers
            $totalSellers = Seller::count();
            $activeSellers = Seller::where('status', SellerStatus::APPROVED)->count();
            $newSellersThisPeriod = Seller::where('status', SellerStatus::APPROVED)
                ->whereBetween('approved_at', [$currentStart, $currentEnd])
                ->count();
            $pendingSellers = Seller::whereIn('status', [SellerStatus::PENDING, SellerStatus::KYC_SUBMITTED])->count();

            // 4. Customers
            $totalCustomers = Customer::count();
            $activeCustomers = $totalCustomers;
            $newCustomersThisPeriod = Customer::whereBetween('created_at', [$currentStart, $currentEnd])->count();

            // 5. Products
            $totalProducts = Product::count();
            $activeProducts = Product::where('status', ProductStatus::ACTIVE)->count();
            $pendingProducts = Product::where('status', ProductStatus::PENDING_REVIEW)->count();

            // 6. Orders Breakdown
            $pendingOrders = Order::whereIn('status', [OrderStatus::PLACED, OrderStatus::PENDING])->count();
            $shippedOrders = Order::whereIn('status', [OrderStatus::PROCESSING, OrderStatus::READY_FOR_PICKUP, OrderStatus::OUT_FOR_DELIVERY])->count();
            $deliveredOrders = Order::where('status', OrderStatus::DELIVERED)->count();
            $cancelledOrders = Order::where('status', OrderStatus::CANCELLED)->count();

            // 7. Returns and RTOs
            $returnsCount = OrderReturn::count();
            $rtoCount = RtoCase::count();

            // 8. Quick Commerce Orders
            $currentQuickOrders = FulfilmentOrder::where('fulfilment_type', FulfilmentType::QUICK)
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->count();

            $previousQuickOrders = FulfilmentOrder::where('fulfilment_type', FulfilmentType::QUICK)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count();

            // 9. Financial Precision & Commissions
            $currentCommission = (float) CommissionEntry::whereBetween('created_at', [$currentStart, $currentEnd])
                ->sum('commission');

            $previousCommission = (float) CommissionEntry::whereBetween('created_at', [$previousStart, $previousEnd])
                ->sum('commission');

            $sellerPayable = (float) CommissionEntry::whereBetween('created_at', [$currentStart, $currentEnd])
                ->sum('net_payable');

            $pendingSettlements = (float) SellerSettlement::whereIn('status', [
                \App\Enums\SettlementStatus::PENDING,
                \App\Enums\SettlementStatus::PROCESSING,
            ])->sum('net');

            // 10. Open Support Tickets
            $openTickets = SupportTicket::whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])->count();

            return [
                'total_gmv' => [
                    'value' => $currentGmv,
                    'formatted' => '₹' . number_format($currentGmv, 2),
                    'trend' => $this->calculatePercentageTrend($currentGmv, $previousGmv),
                ],
                'total_orders' => [
                    'value' => $currentOrders,
                    'formatted' => number_format($currentOrders),
                    'trend' => $this->calculatePercentageTrend((float) $currentOrders, (float) $previousOrders),
                ],
                'active_sellers' => [
                    'value' => $activeSellers,
                    'formatted' => number_format($activeSellers),
                    'trend' => ['text' => "+{$newSellersThisPeriod} this period", 'type' => 'up'],
                ],
                'active_customers' => [
                    'value' => $activeCustomers,
                    'formatted' => number_format($activeCustomers),
                    'trend' => ['text' => "+{$newCustomersThisPeriod} new", 'type' => 'up'],
                ],
                'pending_seller_approvals' => [
                    'value' => $pendingSellers,
                    'formatted' => number_format($pendingSellers),
                    'trend' => ['text' => $pendingSellers > 0 ? 'Action required' : 'Queue clear', 'type' => $pendingSellers > 0 ? 'down' : 'neutral'],
                ],
                'pending_product_reviews' => [
                    'value' => $pendingProducts,
                    'formatted' => number_format($pendingProducts),
                    'trend' => ['text' => $pendingProducts > 0 ? 'Awaiting audit' : 'All clear', 'type' => $pendingProducts > 0 ? 'down' : 'neutral'],
                ],
                'quick_orders' => [
                    'value' => $currentQuickOrders,
                    'formatted' => number_format($currentQuickOrders),
                    'trend' => $this->calculatePercentageTrend((float) $currentQuickOrders, (float) $previousQuickOrders),
                ],
                'platform_commission' => [
                    'value' => $currentCommission,
                    'formatted' => '₹' . number_format($currentCommission, 2),
                    'trend' => $this->calculatePercentageTrend($currentCommission, $previousCommission),
                ],
                // Platform Overview Specifics
                'total_customers' => $totalCustomers,
                'total_sellers' => $totalSellers,
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'total_orders_count' => $currentOrders,
                'pending_orders' => $pendingOrders,
                'shipped_orders' => $shippedOrders,
                'delivered_orders' => $deliveredOrders,
                'cancelled_orders' => $cancelledOrders,
                'returns_count' => $returnsCount,
                'rto_count' => $rtoCount,
                'gross_sales' => $currentGmv,
                'seller_payable' => $sellerPayable,
                'pending_settlements' => $pendingSettlements,
                'open_tickets' => $openTickets,
            ];
        });
    }

    /**
     * Chart 1: Sales & Orders trend (Dual Axis).
     */
    public function getSalesAndOrdersTrend(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_chart_sales_trend_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            $days = $start->diffInDays($end) + 1;
            $format = $days <= 2 ? 'H:00' : 'M d';
            $sqlGroup = $days <= 2 ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';

            $driver = DB::connection()->getDriverName();
            $dateExpr = $driver === 'sqlite'
                ? "strftime('{$sqlGroup}', created_at)"
                : "DATE_FORMAT(created_at, '{$sqlGroup}')";

            $records = Order::selectRaw("
                {$dateExpr} as period,
                COALESCE(SUM(total), 0) as gmv,
                COUNT(id) as order_count
            ")
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

            $labels = [];
            $gmvData = [];
            $orderData = [];

            if ($days <= 2) {
                $current = $start->copy()->startOfHour();
                while ($current <= $end) {
                    $key = $current->format('Y-m-d H:00:00');
                    $labels[] = $current->format('H:00');
                    $gmvData[] = isset($records[$key]) ? (float) $records[$key]->gmv : 0.00;
                    $orderData[] = isset($records[$key]) ? (int) $records[$key]->order_count : 0;
                    $current->addHour();
                }
            } else {
                $current = $start->copy()->startOfDay();
                while ($current <= $end) {
                    $key = $current->format('Y-m-d');
                    $labels[] = $current->format('M d');
                    $gmvData[] = isset($records[$key]) ? (float) $records[$key]->gmv : 0.00;
                    $orderData[] = isset($records[$key]) ? (int) $records[$key]->order_count : 0;
                    $current->addDay();
                }
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Gross Merchandise Value (₹)',
                        'data' => $gmvData,
                        'borderColor' => '#059669', // Emerald branding
                        'backgroundColor' => 'rgba(5, 150, 105, 0.08)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Order Volume',
                        'data' => $orderData,
                        'borderColor' => '#0f172a', // Slate 900
                        'backgroundColor' => 'transparent',
                        'borderDash' => [4, 4],
                        'tension' => 0.35,
                        'yAxisID' => 'y1',
                    ],
                ],
            ];
        });
    }

    /**
     * Chart 2: Orders by Fulfilment Type (Donut).
     */
    public function getOrdersByType(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_chart_order_types_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            $counts = FulfilmentOrder::selectRaw("fulfilment_type, COUNT(id) as count")
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('fulfilment_type')
                ->pluck('count', 'fulfilment_type')
                ->toArray();

            $standard = $counts['standard'] ?? 0;
            $quick = $counts['quick'] ?? 0;
            $pickup = $counts['pickup'] ?? 0;

            return [
                'labels' => ['Standard Delivery', 'Quick Commerce (10-30m)', 'Store Pickup'],
                'datasets' => [
                    [
                        'data' => [$standard, $quick, $pickup],
                        'backgroundColor' => ['#059669', '#0284c7', '#0f172a'],
                        'borderWidth' => 0,
                    ],
                ],
            ];
        });
    }

    /**
     * Chart 3: Revenue by Category (Horizontal Bar).
     */
    public function getRevenueByCategory(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_chart_category_revenue_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            $records = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('COALESCE(categories.name, "Uncategorized") as category_name, SUM(order_items.total) as revenue')
                ->whereBetween('order_items.created_at', [$start, $end])
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->limit(7)
                ->get();

            $labels = [];
            $data = [];

            foreach ($records as $row) {
                $labels[] = $row->category_name;
                $data[] = (float) $row->revenue;
            }

            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Category Revenue (₹)',
                        'data' => $data,
                        'backgroundColor' => '#059669',
                        'borderRadius' => 6,
                    ],
                ],
            ];
        });
    }

    /**
     * Chart 4: Top 10 Sellers (Strictly Real Data, Zero Simulated Sparklines).
     */
    public function getTopSellers(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_chart_top_sellers_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            $salesSubquery = DB::table('fulfilment_orders')
                ->selectRaw('seller_id, COALESCE(SUM(subtotal), 0) as total_sales, COUNT(id) as order_count')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('seller_id');

            $sellers = Seller::with('user')
                ->leftJoinSub($salesSubquery, 'sales', function ($join) {
                    $join->on('sellers.id', '=', 'sales.seller_id');
                })
                ->select('sellers.*', DB::raw('COALESCE(sales.total_sales, 0) as total_sales'), DB::raw('COALESCE(sales.order_count, 0) as order_count'))
                ->where('sellers.status', SellerStatus::APPROVED)
                ->orderByDesc('total_sales')
                ->limit(10)
                ->get();

            return $sellers->map(function ($s) {
                // Real 7-day order points from actual fulfilment_orders records
                $sparkline = [];
                for ($i = 6; $i >= 0; $i--) {
                    $dStart = Carbon::now()->subDays($i)->startOfDay();
                    $dEnd = Carbon::now()->subDays($i)->endOfDay();
                    $sparkline[] = FulfilmentOrder::where('seller_id', $s->id)
                        ->whereBetween('created_at', [$dStart, $dEnd])
                        ->count();
                }

                return [
                    'id' => $s->id,
                    'business_name' => $s->business_name,
                    'owner_name' => $s->user?->name ?? 'Merchant',
                    'seller_type' => $s->seller_type->label(),
                    'total_sales' => (float) $s->total_sales,
                    'formatted_sales' => '₹' . number_format((float) $s->total_sales, 2),
                    'order_count' => (int) $s->order_count,
                    'sparkline' => $sparkline,
                ];
            })->toArray();
        });
    }

    /**
     * Chart 5: Order Status Funnel.
     */
    public function getOrderStatusFunnel(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): array
    {
        $cacheKey = "admin_chart_order_funnel_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            $placed = Order::whereBetween('created_at', [$start, $end])->count();
            $confirmed = Order::whereBetween('created_at', [$start, $end])
                ->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::READY_FOR_PICKUP, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])
                ->count();
            $processing = Order::whereBetween('created_at', [$start, $end])
                ->whereIn('status', [OrderStatus::PROCESSING, OrderStatus::READY_FOR_PICKUP, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])
                ->count();
            $dispatched = Order::whereBetween('created_at', [$start, $end])
                ->whereIn('status', [OrderStatus::READY_FOR_PICKUP, OrderStatus::OUT_FOR_DELIVERY, OrderStatus::DELIVERED])
                ->count();
            $delivered = Order::whereBetween('created_at', [$start, $end])
                ->where('status', OrderStatus::DELIVERED)
                ->count();

            return [
                'labels' => ['1. Placed', '2. Confirmed', '3. Processing', '4. In Transit / Out', '5. Delivered'],
                'datasets' => [
                    [
                        'label' => 'Orders in Funnel',
                        'data' => [$placed, $confirmed, $processing, $dispatched, $delivered],
                        'backgroundColor' => ['#94a3b8', '#64748b', '#0284c7', '#d97706', '#059669'],
                        'borderRadius' => 6,
                    ],
                ],
            ];
        });
    }

    /**
     * Top-Selling Products across Platform in Period.
     */
    public function getTopSellingProducts(string $range = '30d', ?string $customStart = null, ?string $customEnd = null, int $limit = 10): array
    {
        $cacheKey = "admin_top_selling_products_{$range}_" . ($customStart ?? 'none') . '_' . ($customEnd ?? 'none') . "_{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($range, $customStart, $customEnd, $limit) {
            [$start, $end] = $this->parsePeriodDates($range, $customStart, $customEnd);

            return OrderItem::query()
                ->whereBetween('created_at', [$start, $end])
                ->select('product_id', DB::raw('SUM(qty) as units_sold'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(id) as orders_count'))
                ->groupBy('product_id')
                ->orderByDesc('revenue')
                ->with(['product.category', 'product.seller'])
                ->limit($limit)
                ->get()
                ->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'title' => $item->product?->title ?? 'Product #' . $item->product_id,
                    'category' => $item->product?->category?->name ?? 'General',
                    'seller_name' => $item->product?->seller?->business_name ?? 'Merchant',
                    'units_sold' => (int) $item->units_sold,
                    'revenue' => (float) $item->revenue,
                    'orders_count' => (int) $item->orders_count,
                ])
                ->toArray();
        });
    }

    /**
     * Operational Widgets Data.
     */
    public function getWidgetsData(): array
    {
        return Cache::remember('admin_dash_widgets', self::CACHE_TTL, function () {
            // 1. Recent Orders with customers & items
            $recentOrders = Order::with(['customer.user', 'fulfilmentOrders'])
                ->latest()
                ->limit(6)
                ->get();

            // 2. Pending KYC Queue
            $pendingKycSellers = Seller::with('user')
                ->whereIn('status', [SellerStatus::PENDING, SellerStatus::KYC_SUBMITTED])
                ->latest()
                ->limit(5)
                ->get();

            // 3. Open Support Tickets
            $openTickets = SupportTicket::with('user')
                ->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])
                ->latest()
                ->limit(5)
                ->get();

            // 4. Settlements Due This Week
            $dueSettlements = SellerSettlement::with('seller')
                ->where('status', \App\Enums\SettlementStatus::PENDING)
                ->latest()
                ->limit(4)
                ->get();

            $totalPendingPayout = (float) SellerSettlement::where('status', \App\Enums\SettlementStatus::PENDING)->sum('net');

            // 5. System Health
            $queueBacklog = (int) DB::table('jobs')->count();
            $failedJobs = (int) DB::table('failed_jobs')->count();

            // 6. Flagged or Suspended Sellers
            $flaggedSellers = Seller::with('user')
                ->where('status', SellerStatus::SUSPENDED)
                ->latest()
                ->limit(4)
                ->get();

            return [
                'recent_orders' => $recentOrders,
                'pending_kyc' => $pendingKycSellers,
                'open_tickets' => $openTickets,
                'due_settlements' => $dueSettlements,
                'total_pending_payout' => $totalPendingPayout,
                'queue_backlog' => $queueBacklog,
                'failed_jobs' => $failedJobs,
                'flagged_sellers' => $flaggedSellers,
            ];
        });
    }

    /**
     * Export dashboard summary as CSV.
     */
    public function exportSummaryCsv(string $range = '30d', ?string $customStart = null, ?string $customEnd = null): StreamedResponse
    {
        $stats = $this->getTopStats($range, $customStart, $customEnd);
        $topSellers = $this->getTopSellers($range, $customStart, $customEnd);
        $categoryRevenue = $this->getRevenueByCategory($range, $customStart, $customEnd);
        $topProducts = $this->getTopSellingProducts($range, $customStart, $customEnd);

        $filename = "allzone_dashboard_summary_" . date('Y_m_d_His') . ".csv";

        return new StreamedResponse(function () use ($stats, $topSellers, $categoryRevenue, $topProducts, $range) {
            $handle = fopen('php://output', 'w');

            // Header info
            fputcsv($handle, [setting('name', 'ALLZONE') . ' Marketplace - Executive Dashboard Summary']);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s'), 'Period Range', strtoupper($range)]);
            fputcsv($handle, []);

            // KPI Metrics
            fputcsv($handle, ['--- KEY PERFORMANCE INDICATORS ---']);
            fputcsv($handle, ['Metric Name', 'Current Period Value', 'Trend vs Previous Period']);
            fputcsv($handle, ['Total Platform GMV', $stats['total_gmv']['formatted'], $stats['total_gmv']['trend']['text']]);
            fputcsv($handle, ['Total Order Count', $stats['total_orders']['formatted'], $stats['total_orders']['trend']['text']]);
            fputcsv($handle, ['Active Approved Sellers', $stats['active_sellers']['formatted'], $stats['active_sellers']['trend']['text']]);
            fputcsv($handle, ['Total Customer Base', $stats['active_customers']['formatted'], $stats['active_customers']['trend']['text']]);
            fputcsv($handle, ['Pending KYC Approvals', $stats['pending_seller_approvals']['formatted'], $stats['pending_seller_approvals']['trend']['text']]);
            fputcsv($handle, ['Pending Product Reviews', $stats['pending_product_reviews']['formatted'], $stats['pending_product_reviews']['trend']['text']]);
            fputcsv($handle, ['Platform Commission Earned', $stats['platform_commission']['formatted'], $stats['platform_commission']['trend']['text']]);
            fputcsv($handle, ['Gross Sales (INR)', number_format($stats['gross_sales'], 2), '']);
            fputcsv($handle, ['Seller Payable (INR)', number_format($stats['seller_payable'], 2), '']);
            fputcsv($handle, ['Pending Settlements (INR)', number_format($stats['pending_settlements'], 2), '']);
            fputcsv($handle, ['Customer Returns Count', $stats['returns_count'], '']);
            fputcsv($handle, ['Courier RTO Count', $stats['rto_count'], '']);
            fputcsv($handle, ['Open Support Tickets', $stats['open_tickets'], '']);
            fputcsv($handle, []);

            // Category Breakdown
            fputcsv($handle, ['--- REVENUE BY CATEGORY ---']);
            fputcsv($handle, ['Category Name', 'Total Sales Volume (INR)']);
            foreach ($categoryRevenue['labels'] as $index => $catName) {
                fputcsv($handle, [$catName, number_format($categoryRevenue['datasets'][0]['data'][$index] ?? 0, 2)]);
            }
            fputcsv($handle, []);

            // Top Sellers
            fputcsv($handle, ['--- TOP 10 MERCHANTS BY REVENUE ---']);
            fputcsv($handle, ['Merchant Business Name', 'Owner', 'Store Model', 'Order Count', 'Total GMV (INR)']);
            foreach ($topSellers as $seller) {
                fputcsv($handle, [
                    $seller['business_name'],
                    $seller['owner_name'],
                    $seller['seller_type'],
                    $seller['order_count'],
                    number_format($seller['total_sales'], 2),
                ]);
            }
            fputcsv($handle, []);

            // Top Products
            fputcsv($handle, ['--- TOP-SELLING PRODUCTS ---']);
            fputcsv($handle, ['Product Name', 'Category', 'Seller', 'Units Sold', 'Total Revenue (INR)']);
            foreach ($topProducts as $p) {
                fputcsv($handle, [
                    $p['title'],
                    $p['category'],
                    $p['seller_name'],
                    $p['units_sold'],
                    number_format($p['revenue'], 2),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Calculate percentage change.
     */
    protected function calculatePercentageTrend(float $current, float $previous): array
    {
        if ($previous == 0.0) {
            return [
                'text' => $current > 0 ? '+100% vs prev' : '0.0% vs prev',
                'type' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        $sign = $change > 0 ? '+' : '';

        return [
            'text' => "{$sign}{$change}% vs prev",
            'type' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Parse date range string into [start, end, prevStart, prevEnd].
     */
    public function parsePeriodDates(string $range, ?string $customStart = null, ?string $customEnd = null): array
    {
        $end = Carbon::now();

        switch ($range) {
            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->startOfDay();
                break;

            case 'today':
                $start = Carbon::today();
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->startOfDay();
                break;

            case '7d':
            case 'last_7':
            case 'last_7_days':
                $start = Carbon::now()->subDays(7);
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->subDays(7);
                break;

            case 'custom':
                $start = $customStart ? Carbon::parse($customStart)->startOfDay() : Carbon::now()->subDays(7)->startOfDay();
                $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : Carbon::now()->endOfDay();
                if ($start->gt($end)) {
                    $temp = $start;
                    $start = $end->copy()->startOfDay();
                    $end = $temp->copy()->endOfDay();
                }
                $days = $start->diffInDays($end) ?: 1;
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->subDays($days);
                break;

            case '90d':
                $start = Carbon::now()->subDays(90);
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->subDays(90);
                break;

            case '30d':
            case 'last_30':
            case 'last_30_days':
            default:
                $start = Carbon::now()->subDays(30);
                $prevEnd = $start->copy()->subSecond();
                $prevStart = $prevEnd->copy()->subDays(30);
                break;
        }

        return [$start, $end, $prevStart, $prevEnd];
    }
}
