<?php

declare(strict_types=1);

namespace App\Services\Seller;

use App\Enums\FulfilmentType;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\StockMovementType;
use App\Enums\StoreStatus;
use App\Models\Announcement;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SellerDashboardService
{
    protected const CACHE_TTL = 180; // 3-minute cache for fast response while staying fresh

    /**
     * Resolve date range boundaries and comparison periods.
     *
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon, 4: string, 5: string}
     */
    public function parseDateRange(string $range = '30d', ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        if ($range === 'yesterday') {
            $curStart = Carbon::yesterday()->startOfDay();
            $curEnd = Carbon::yesterday()->endOfDay();
            $prevStart = Carbon::yesterday()->subDay()->startOfDay();
            $prevEnd = Carbon::yesterday()->subDay()->endOfDay();
            $label = 'Yesterday (' . $curStart->format('d M') . ')';
            $key = 'yesterday';
        } elseif ($range === '7d') {
            $curStart = Carbon::now()->subDays(6)->startOfDay();
            $curEnd = Carbon::now()->endOfDay();
            $prevStart = Carbon::now()->subDays(13)->startOfDay();
            $prevEnd = Carbon::now()->subDays(7)->endOfDay();
            $label = 'Last 7 Days';
            $key = '7d';
        } elseif ($range === 'custom' && $startDate && $endDate) {
            $curStart = Carbon::parse($startDate)->startOfDay();
            $curEnd = Carbon::parse($endDate)->endOfDay();
            $daysDiff = max(1, (int) $curStart->diffInDays($curEnd));
            $prevStart = (clone $curStart)->subDays($daysDiff);
            $prevEnd = (clone $curStart)->subSecond();
            $label = $curStart->format('d M') . ' - ' . $curEnd->format('d M Y');
            $key = "custom_{$curStart->format('Ymd')}_{$curEnd->format('Ymd')}";
        } else {
            // Default 30 Days
            $curStart = Carbon::now()->subDays(29)->startOfDay();
            $curEnd = Carbon::now()->endOfDay();
            $prevStart = Carbon::now()->subDays(59)->startOfDay();
            $prevEnd = Carbon::now()->subDays(30)->endOfDay();
            $label = 'Last 30 Days';
            $key = '30d';
        }

        return [$curStart, $curEnd, $prevStart, $prevEnd, $label, $key];
    }

    /**
     * Retrieve complete dashboard payload with seller & store isolation.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(
        Seller $seller,
        ?int $storeId = null,
        string $range = '30d',
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        [$curStart, $curEnd, $prevStart, $prevEnd, $rangeLabel, $rangeKey] = $this->parseDateRange($range, $startDate, $endDate);

        $storeKey = $storeId ? "store_{$storeId}" : 'all_stores';
        $cacheKey = "seller_dash_{$seller->id}_{$storeKey}_{$rangeKey}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($seller, $storeId, $range, $startDate, $endDate, $curStart, $curEnd, $prevStart, $prevEnd, $rangeLabel) {
            return [
                'kpi' => $this->getKpiStats($seller, $storeId, $curStart, $curEnd, $prevStart, $prevEnd),
                'businessInsights' => $this->getBusinessInsights($seller, $storeId, $curStart, $curEnd, $prevStart, $prevEnd),
                'todo' => $this->getTodoStats($seller, $storeId),
                'dispatchPerformance' => $this->getDispatchPerformance($seller, $storeId),
                'charts' => $this->getChartsData($seller, $storeId, $range, $startDate, $endDate),
                'actionOrders' => $this->getOrdersNeedingAction($seller, $storeId),
                'lowStock' => $this->getLowStockItems($seller, $storeId),
                'settlement' => $this->getSettlementOverview($seller),
                'reviews' => $this->getRecentReviews($seller),
                'storeStatus' => $this->getStoreStatusInfo($seller, $storeId),
                'checklist' => $this->getOnboardingChecklist($seller),
                'announcements' => $this->getAnnouncements($seller),
                'rangeLabel' => $rangeLabel,
                'range' => $range,
                'startDate' => $curStart->format('Y-m-d'),
                'endDate' => $curEnd->format('Y-m-d'),
            ];
        });
    }

    /**
     * Compute 8 core KPI cards for ALLZONE Seller Dashboard.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getKpiStats(
        Seller $seller,
        ?int $storeId,
        Carbon $curStart,
        Carbon $curEnd,
        Carbon $prevStart,
        Carbon $prevEnd
    ): array {
        $baseQuery = FulfilmentOrder::query()->where('seller_id', $seller->id);
        if ($storeId) {
            $baseQuery->where('store_id', $storeId);
        }

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Today's Sales
        $todaySales = (float) (clone $baseQuery)
            ->whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('seller_payable');

        $yesterdaySales = (float) (clone $baseQuery)
            ->whereDate('created_at', $yesterday)
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('seller_payable');

        $todaySalesChange = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100.0 : 0.0);

        // 2. Total Orders (in selected period)
        $currentOrdersCount = (clone $baseQuery)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $previousOrdersCount = (clone $baseQuery)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $ordersChange = $previousOrdersCount > 0
            ? round((($currentOrdersCount - $previousOrdersCount) / $previousOrdersCount) * 100, 1)
            : ($currentOrdersCount > 0 ? 100.0 : 0.0);

        // 3. Quick Orders (10-30 min Delivery SLA)
        $quickOrdersCount = (clone $baseQuery)
            ->where('fulfilment_type', FulfilmentType::QUICK)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $quickShare = $currentOrdersCount > 0
            ? round(($quickOrdersCount / $currentOrdersCount) * 100, 1)
            : 0.0;

        // 4. Online Orders (Standard Courier / Shiprocket)
        $onlineOrdersCount = (clone $baseQuery)
            ->where('fulfilment_type', FulfilmentType::STANDARD)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // 5. Pending Orders
        $pendingCount = (clone $baseQuery)
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'processing'])
            ->count();

        // 6. Delivered Orders (in selected period)
        $deliveredCount = (clone $baseQuery)
            ->where('status', 'delivered')
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->count();

        // 7. Returns (in selected period)
        $returnsCount = (clone $baseQuery)
            ->whereIn('status', ['returned', 'cancelled'])
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->count();

        $returnRate = $currentOrdersCount > 0
            ? round(($returnsCount / $currentOrdersCount) * 100, 1)
            : 0.0;

        // 8. Low Stock Items count
        $lowStockQuery = Inventory::query()
            ->whereHas('location', function ($q) use ($seller, $storeId) {
                $q->where('seller_id', $seller->id);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->lowStock();

        $lowStockCount = $lowStockQuery->count();

        // Out of stock
        $outOfStockCount = (int) Inventory::query()
            ->whereHas('location', function ($q) use ($seller, $storeId) {
                $q->where('seller_id', $seller->id);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= 0')
            ->count();

        // Ready to ship
        $readyToShipCount = (int) (clone $baseQuery)
            ->whereIn('status', ['ready', 'ready_to_ship'])
            ->count();

        // Dispatch health calculation from real dispatches
        $totalDispatched = (clone $baseQuery)->whereIn('status', ['dispatched', 'delivered'])->count();
        $onTimeDispatched = (clone $baseQuery)->whereIn('status', ['dispatched', 'delivered'])->whereNull('cancelled_at')->count();
        $dispatchHealthPct = $totalDispatched > 0 ? (int) round(($onTimeDispatched / $totalDispatched) * 100) : null;

        // In Transit count for marketplace shipping
        $inTransitCount = (int) (clone $baseQuery)
            ->whereIn('status', ['dispatched', 'in_transit'])
            ->count();

        return [
            'today_sales' => [
                'label' => "Today's Sales",
                'value' => $todaySales,
                'formatted_value' => format_currency($todaySales),
                'change' => ($todaySalesChange >= 0 ? "+{$todaySalesChange}%" : "{$todaySalesChange}%") . " vs yesterday",
                'change_type' => $todaySalesChange >= 0 ? 'up' : 'down',
                'badge' => 'Gross Earnings',
                'color' => 'emerald',
                'url' => route('seller.payments.index'),
            ],
            'total_orders' => [
                'label' => 'Total Orders',
                'value' => $currentOrdersCount,
                'change' => ($ordersChange >= 0 ? "+{$ordersChange}%" : "{$ordersChange}%") . " vs prior period",
                'change_type' => $ordersChange >= 0 ? 'up' : 'down',
                'badge' => 'Volume',
                'color' => 'primary',
                'url' => route('seller.orders.index'),
            ],
            'ready_to_dispatch' => [
                'label' => 'Ready to Dispatch',
                'value' => $readyToShipCount,
                'change' => 'Packed & Labeled',
                'change_type' => 'up',
                'badge' => 'Courier Pickup',
                'color' => 'indigo',
                'url' => route('seller.orders.index', ['status' => 'ready']),
            ],
            'shipped_orders' => [
                'label' => 'In Transit',
                'value' => $inTransitCount,
                'change' => 'With Courier Partner',
                'change_type' => 'neutral',
                'badge' => 'Dispatched',
                'color' => 'sky',
                'url' => route('seller.orders.index', ['status' => 'shipped']),
            ],
            'quick_orders' => [
                'label' => 'Quick Orders',
                'value' => $quickOrdersCount,
                'change' => "{$quickShare}% of volume",
                'change_type' => 'neutral',
                'badge' => '⚡ 10-30m SLA',
                'color' => 'amber',
                'url' => route('seller.quick.live'),
            ],
            'online_orders' => [
                'label' => 'Online Orders',
                'value' => $onlineOrdersCount,
                'change' => 'Shiprocket Courier',
                'change_type' => 'neutral',
                'badge' => 'Marketplace',
                'color' => 'sky',
                'url' => route('seller.orders.index', ['tab' => 'online']),
            ],
            'pending_orders' => [
                'label' => 'Pending Orders',
                'value' => $pendingCount,
                'change' => $pendingCount > 0 ? 'Action required' : 'Queue clear',
                'change_type' => $pendingCount > 0 ? 'down' : 'up',
                'badge' => $pendingCount > 0 ? 'Urgent' : 'All Clear',
                'color' => $pendingCount > 0 ? 'rose' : 'primary',
                'url' => route('seller.orders.index', ['status' => 'pending']),
            ],
            'delivered_orders' => [
                'label' => 'Delivered Orders',
                'value' => $deliveredCount,
                'change' => 'Fulfilled',
                'change_type' => 'up',
                'badge' => 'Completed',
                'color' => 'emerald',
                'url' => route('seller.orders.index', ['status' => 'delivered']),
            ],
            'returned_orders' => [
                'label' => 'Returns',
                'value' => $returnsCount,
                'change' => "{$returnRate}% return rate",
                'change_type' => $returnRate > 5 ? 'down' : 'neutral',
                'badge' => 'Reverse Logistics',
                'color' => 'amber',
                'url' => route('seller.returns.index'),
            ],
            'low_stock' => [
                'label' => 'Low Stock Items',
                'value' => $lowStockCount,
                'change' => $lowStockCount > 0 ? 'Restock needed' : 'Healthy inventory',
                'change_type' => $lowStockCount > 0 ? 'down' : 'up',
                'badge' => 'Catalog Alert',
                'color' => $lowStockCount > 0 ? 'rose' : 'emerald',
                'url' => route('seller.inventory.index', ['stock_status' => 'low_stock']),
            ],
            'out_of_stock' => [
                'label' => 'Out of Stock',
                'value' => $outOfStockCount,
                'url' => route('seller.inventory.index', ['stock_status' => 'out_of_stock']),
            ],
            'ready_to_ship' => [
                'label' => 'Download Labels',
                'value' => $readyToShipCount,
                'url' => route('seller.orders.index', ['status' => 'ready']),
            ],
            'dispatch_health' => [
                'percentage' => $dispatchHealthPct,
                'total_dispatched' => $totalDispatched,
                'on_time_dispatched' => $onTimeDispatched,
            ],
        ];
    }

    /**
     * Compute Section 4: Business Insights.
     *
     * @return array<string, mixed>
     */
    public function getBusinessInsights(
        Seller $seller,
        ?int $storeId,
        Carbon $curStart,
        Carbon $curEnd,
        Carbon $prevStart,
        Carbon $prevEnd
    ): array {
        $baseQuery = FulfilmentOrder::query()->where('seller_id', $seller->id);
        if ($storeId) {
            $baseQuery->where('store_id', $storeId);
        }

        // Current period metrics
        $curOrders = (clone $baseQuery)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $curSales = (float) (clone $baseQuery)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $curRevenue = (float) (clone $baseQuery)
            ->whereBetween('created_at', [$curStart, $curEnd])
            ->whereNotIn('status', ['cancelled'])
            ->sum('seller_payable');

        // Prior period metrics
        $prevOrders = (clone $baseQuery)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->whereNotIn('status', ['cancelled'])
            ->count();

        $prevSales = (float) (clone $baseQuery)
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $ordersChange = $prevOrders > 0
            ? round((($curOrders - $prevOrders) / $prevOrders) * 100, 1)
            : ($curOrders > 0 ? 100.0 : 0.0);

        $salesChange = $prevSales > 0
            ? round((($curSales - $prevSales) / $prevSales) * 100, 1)
            : ($curSales > 0 ? 100.0 : 0.0);

        // Product views tracking: In ALLZONE, if no view tracking table exists, we return 0 and tracked=false
        $views = 0;
        $tracked = false;
        $conversionRate = $views > 0 ? round(($curOrders / $views) * 100, 2) . '%' : '0.0%';

        return [
            'total_orders' => $curOrders,
            'total_sales' => $curSales,
            'formatted_total_sales' => format_currency($curSales),
            'revenue' => $curRevenue,
            'formatted_revenue' => format_currency($curRevenue),
            'views' => $views,
            'views_tracked' => $tracked,
            'conversion_rate' => $conversionRate,
            'orders_change' => $ordersChange,
            'sales_change' => $salesChange,
        ];
    }

    /**
     * Compute Section 3: To-Do List (Action Items & Operational Queues).
     *
     * @return array<string, int>
     */
    public function getTodoStats(Seller $seller, ?int $storeId = null): array
    {
        $baseOrders = FulfilmentOrder::query()->where('seller_id', $seller->id);
        if ($storeId) {
            $baseOrders->where('store_id', $storeId);
        }

        $baseInventory = Inventory::query()->whereHas('location', function ($q) use ($seller, $storeId) {
            $q->where('seller_id', $seller->id);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        });

        return [
            'pending_orders' => (clone $baseOrders)
                ->whereIn('status', ['placed', 'store_accepted', 'packing', 'processing'])
                ->count(),
            'orders_ready_to_process' => (clone $baseOrders)
                ->whereIn('status', ['placed', 'store_accepted'])
                ->count(),
            'shipping_labels_pending' => (clone $baseOrders)
                ->whereIn('status', ['ready', 'ready_to_ship'])
                ->count(),
            'out_of_stock_products' => (clone $baseInventory)
                ->whereRaw('(quantity_on_hand - quantity_reserved) <= 0')
                ->count(),
            'low_stock_products' => (clone $baseInventory)
                ->whereRaw('(quantity_on_hand - quantity_reserved) > 0 AND (quantity_on_hand - quantity_reserved) <= low_stock_threshold')
                ->count(),
            'pending_catalog_approvals' => Product::query()
                ->where('seller_id', $seller->id)
                ->where('status', ProductStatus::PENDING_REVIEW->value)
                ->count(),
            'open_support_tickets' => SupportTicket::query()
                ->where(function ($q) use ($seller) {
                    $q->where('seller_id', $seller->id)
                      ->orWhere('user_id', $seller->user_id);
                })
                ->whereIn('status', ['open', 'in_progress', 'waiting_customer'])
                ->count(),
        ];
    }

    /**
     * Compute Section 6: Dispatch Performance.
     *
     * @return array<string, mixed>
     */
    public function getDispatchPerformance(Seller $seller, ?int $storeId = null): array
    {
        $baseOrders = FulfilmentOrder::query()->where('seller_id', $seller->id);
        if ($storeId) {
            $baseOrders->where('store_id', $storeId);
        }

        $awaitingProcessing = (clone $baseOrders)
            ->whereIn('status', ['placed', 'store_accepted'])
            ->count();

        $awaitingShipment = (clone $baseOrders)
            ->whereIn('status', ['packing', 'ready', 'ready_to_ship'])
            ->count();

        $inTransit = (clone $baseOrders)
            ->whereIn('status', ['dispatched', 'in_transit'])
            ->count();

        // Delayed shipments: unfulfilled orders placed more than 24h ago
        $delayedShipments = (clone $baseOrders)
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'ready', 'ready_to_ship'])
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->count();

        $delivered = (clone $baseOrders)
            ->where('status', 'delivered')
            ->count();

        $totalDispatched = (clone $baseOrders)
            ->whereIn('status', ['dispatched', 'delivered'])
            ->count();

        $onTimeDispatched = (clone $baseOrders)
            ->whereIn('status', ['dispatched', 'delivered'])
            ->whereNull('cancelled_at')
            ->count();

        $onTimeRate = $totalDispatched > 0
            ? round(($onTimeDispatched / $totalDispatched) * 100, 1)
            : null;

        return [
            'awaiting_processing' => $awaitingProcessing,
            'awaiting_shipment' => $awaitingShipment,
            'in_transit' => $inTransit,
            'delayed_shipments' => $delayedShipments,
            'delivered_orders' => $delivered,
            'total_dispatched' => $totalDispatched,
            'on_time_dispatched' => $onTimeDispatched,
            'on_time_rate' => $onTimeRate,
        ];
    }

    /**
     * Compute Chart.js real data packages based on the active date filter.
     *
     * @return array<string, mixed>
     */
    public function getChartsData(
        Seller $seller,
        ?int $storeId = null,
        string $range = '30d',
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        [$curStart, $curEnd] = $this->parseDateRange($range, $startDate, $endDate);

        $baseOrdersQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$curStart, $curEnd]);

        if ($storeId) {
            $baseOrdersQuery->where('store_id', $storeId);
        }

        // Daily time-series for the selected range
        $daysCount = max(1, (int) $curStart->diffInDays($curEnd) + 1);

        $dailyRaw = (clone $baseOrdersQuery)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('DATE(created_at) as order_date, SUM(seller_payable) as revenue, COUNT(*) as orders_count')
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->keyBy('order_date');

        $salesLabels = [];
        $salesValues = [];
        $ordersValues = [];

        // Build continuous timeline
        $cursor = (clone $curStart);
        while ($cursor->lte($curEnd)) {
            $key = $cursor->format('Y-m-d');
            $salesLabels[] = $cursor->format('d M');
            $salesValues[] = isset($dailyRaw[$key]) ? (float) $dailyRaw[$key]->revenue : 0.0;
            $ordersValues[] = isset($dailyRaw[$key]) ? (int) $dailyRaw[$key]->orders_count : 0;
            $cursor->addDay();
        }

        // Orders by Channel (Quick vs Online vs Pickup)
        $channelRaw = (clone $baseOrdersQuery)
            ->selectRaw('fulfilment_type, COUNT(*) as count, SUM(seller_payable) as revenue')
            ->groupBy('fulfilment_type')
            ->pluck('count', 'fulfilment_type')
            ->toArray();

        $channelData = [
            'labels' => ['Quick Commerce (10-30m)', 'Online Marketplace', 'Store Pickup'],
            'data' => [
                $channelRaw[FulfilmentType::QUICK->value] ?? ($channelRaw['quick'] ?? 0),
                $channelRaw[FulfilmentType::STANDARD->value] ?? ($channelRaw['standard'] ?? 0),
                $channelRaw[FulfilmentType::PICKUP->value] ?? ($channelRaw['pickup'] ?? 0),
            ],
            'colors' => ['#f59e0b', '#6366f1', '#10b981'],
        ];

        // Top 5 Products by Revenue / Volume
        $topProducts = OrderItem::query()
            ->whereHas('fulfilmentOrder', function ($q) use ($seller, $storeId, $curStart, $curEnd) {
                $q->where('seller_id', $seller->id)
                    ->whereBetween('created_at', [$curStart, $curEnd])
                    ->whereNotIn('status', ['cancelled']);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->selectRaw('product_id, title, SUM(qty) as total_qty, SUM(total) as total_revenue')
            ->groupBy('product_id', 'title')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $topProductLabels = [];
        $topProductRevenues = [];
        foreach ($topProducts as $p) {
            $topProductLabels[] = mb_substr($p->title ?? 'Item #' . $p->product_id, 0, 20) . '...';
            $topProductRevenues[] = (float) $p->total_revenue;
        }

        // Revenue by Store (for multi-store sellers)
        $stores = $seller->stores()->get();
        $isMultiStore = $stores->count() > 1;
        $storeRevenueData = ['labels' => [], 'data' => []];

        if ($isMultiStore) {
            $storeRevenueRaw = FulfilmentOrder::query()
                ->where('seller_id', $seller->id)
                ->whereBetween('created_at', [$curStart, $curEnd])
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw('store_id, SUM(seller_payable) as revenue')
                ->groupBy('store_id')
                ->pluck('revenue', 'store_id')
                ->toArray();

            foreach ($stores as $s) {
                $storeRevenueData['labels'][] = $s->name;
                $storeRevenueData['data'][] = (float) ($storeRevenueRaw[$s->id] ?? 0.0);
            }
        }

        return [
            'sales_trend' => [
                'labels' => $salesLabels,
                'sales' => $salesValues,
                'orders' => $ordersValues,
            ],
            'channel' => $channelData,
            'top_products' => [
                'labels' => $topProductLabels,
                'data' => $topProductRevenues,
            ],
            'is_multi_store' => $isMultiStore,
            'revenue_by_store' => $storeRevenueData,
        ];
    }

    /**
     * Retrieve live orders requiring immediate store acceptance or packing action.
     */
    public function getOrdersNeedingAction(Seller $seller, ?int $storeId = null, int $limit = 6): Collection
    {
        $query = FulfilmentOrder::query()
            ->with(['order', 'items.variant.product', 'store.location'])
            ->where('seller_id', $seller->id)
            ->whereIn('status', ['placed', 'store_accepted', 'packing', 'confirmed'])
            ->orderBy('created_at', 'asc')
            ->limit($limit);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->get();
    }

    /**
     * Retrieve list of low-stock inventory variants with available count and location.
     */
    public function getLowStockItems(Seller $seller, ?int $storeId = null, int $limit = 6): Collection
    {
        $query = Inventory::query()
            ->with(['variant.product', 'location'])
            ->whereHas('location', function ($q) use ($seller, $storeId) {
                $q->where('seller_id', $seller->id);
                if ($storeId) {
                    $q->where('store_id', $storeId);
                }
            })
            ->lowStock()
            ->orderByRaw('(quantity_on_hand - quantity_reserved) asc')
            ->limit($limit);

        return $query->get();
    }

    /**
     * Settlement overview: accrued unpaid balance, next payout schedule, and primary bank.
     *
     * @return array<string, mixed>
     */
    public function getSettlementOverview(Seller $seller): array
    {
        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();

        $accruedBalance = (float) FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->sum('seller_payable');

        $recentSettlements = SellerSettlement::query()
            ->where('seller_id', $seller->id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $nextPayoutDate = Carbon::now()->next(Carbon::WEDNESDAY)->format('l, d M Y');

        return [
            'accrued_balance' => $accruedBalance,
            'formatted_accrued' => format_currency($accruedBalance),
            'next_payout_date' => $nextPayoutDate,
            'primary_bank' => $primaryBank,
            'recent_settlements' => $recentSettlements,
        ];
    }

    /**
     * Recent customer reviews on products owned by this seller.
     */
    public function getRecentReviews(Seller $seller, int $limit = 4): Collection
    {
        return Review::query()
            ->with(['product', 'user'])
            ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Store status info and operational hours for the selected or default store.
     *
     * @return array<string, mixed>
     */
    public function getStoreStatusInfo(Seller $seller, ?int $storeId = null): array
    {
        $store = $storeId ? $seller->stores()->find($storeId) : $seller->stores()->first();

        if (!$store) {
            return [
                'store' => null,
                'is_open' => false,
                'today_timings' => 'No store initialized',
            ];
        }

        $store->loadMissing('timings');
        $todayDayOfWeek = Carbon::now()->dayOfWeek;
        $todayTiming = $store->timings->firstWhere('day_of_week', $todayDayOfWeek);

        $timingLabel = '07:00 AM - 11:00 PM (15m SLA)';
        if ($todayTiming) {
            if ($todayTiming->is_closed) {
                $timingLabel = 'Closed Today (Holiday / Off)';
            } elseif ($todayTiming->is24Hours()) {
                $timingLabel = 'Open 24 Hours (Live 24x7)';
            } elseif ($todayTiming->opens_at && $todayTiming->closes_at) {
                $timingLabel = Carbon::parse($todayTiming->opens_at)->format('h:i A') . ' - ' . Carbon::parse($todayTiming->closes_at)->format('h:i A');
            }
        }

        return [
            'store' => $store,
            'is_open' => $store->status === StoreStatus::ACTIVE,
            'today_timings' => $timingLabel,
        ];
    }

    /**
     * Store onboarding readiness checklist items.
     *
     * @return array{items: array<int, array<string, mixed>>, completed_count: int, total_count: int, progress: int}
     */
    public function getOnboardingChecklist(Seller $seller): array
    {
        $hasProducts = $seller->products()->exists();
        $hasStock = Inventory::query()->whereHas('location', fn ($q) => $q->where('seller_id', $seller->id))->where('quantity_on_hand', '>', 0)->exists();
        $hasBank = $seller->bankAccounts()->exists();
        $hasPickup = $seller->pickupLocations()->exists();
        $isApproved = $seller->status === SellerStatus::APPROVED;

        $items = [
            [
                'title' => 'Bank Account Verification',
                'description' => 'Configure your active bank account for weekly payouts.',
                'done' => (bool) $hasBank,
                'url' => route('seller.settings.bank'),
            ],
            [
                'title' => 'Add First Product',
                'description' => 'Publish SKU with attributes, photos, and MRP.',
                'done' => $hasProducts,
                'url' => route('seller.products.create'),
            ],
            [
                'title' => 'Set Stock & Inventory',
                'description' => 'Assign inventory on hand at your warehouse pickup location.',
                'done' => $hasStock,
                'url' => route('seller.inventory.index'),
            ],
            [
                'title' => 'Pickup Warehouse Location',
                'description' => 'Configure dispatch address for courier pickups.',
                'done' => (bool) $hasPickup,
                'url' => route('seller.warehouse.index'),
            ],
            [
                'title' => 'Supplier Account Active',
                'description' => 'Approved to receive national marketplace customer orders.',
                'done' => (bool) $isApproved,
                'url' => route('seller.dashboard'),
            ],
        ];

        $completed = count(array_filter($items, fn ($i) => $i['done']));
        $total = count($items);
        $progress = (int) round(($completed / $total) * 100);

        return [
            'items' => $items,
            'completed_count' => $completed,
            'total_count' => $total,
            'progress' => $progress,
        ];
    }

    /**
     * Announcements from admin to sellers (Section 8 & 2).
     *
     * @return array<int, array<string, string>>
     */
    public function getAnnouncements(?Seller $seller = null): array
    {
        $query = Announcement::query()->published();

        if ($seller) {
            $query->forSeller($seller);
        }

        $announcements = $query->latest('published_at')->take(3)->get();

        return $announcements->map(function (Announcement $a) {
            return [
                'id' => (string) $a->id,
                'badge' => $a->category_label,
                'badge_color' => $a->category_badge_color,
                'title' => $a->title,
                'description' => $a->summary ?: \Illuminate\Support\Str::limit(strip_tags($a->content), 120),
                'date' => $a->published_at ? $a->published_at->diffForHumans() : 'Recently',
                'url' => route('seller.notices.show', $a),
            ];
        })->all();
    }

    /**
     * Invalidate cached dashboard metrics for this seller.
     */
    public function clearCache(int $sellerId): void
    {
        $ranges = ['yesterday', '7d', '30d', '90d', 'all'];
        foreach ($ranges as $r) {
            Cache::forget("seller_dash_{$sellerId}_all_stores_{$r}");
        }
    }
}
