<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\FulfilmentOrder;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\RtoCase;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessDashboardController extends Controller
{
    /**
     * Display the seller business analytics dashboard with real metrics and charts.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        // 1. Resolve date range filter
        [$startDate, $endDate, $range, $rangeLabel] = $this->resolveDateRange($request);
        $tab = (string) $request->query('tab', 'top_selling');
        $storeId = $request->query('store_id');

        // 2. Base query for seller's fulfilment orders (prevent duplicate counting of split orders)
        $ordersQuery = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId) {
            $ordersQuery->where('store_id', (int) $storeId);
        }

        // 3. Core Order & Sales Aggregates
        $totalOrders = (clone $ordersQuery)->count();
        $distinctOrders = (clone $ordersQuery)->distinct('order_id')->count('order_id');

        $grossSales = (float) (clone $ordersQuery)
            ->whereNotIn('status', ['cancelled'])
            ->sum('subtotal');

        $netRevenue = (float) (clone $ordersQuery)
            ->whereNotIn('status', ['cancelled'])
            ->sum('seller_payable');

        $aov = $totalOrders > 0 ? round($grossSales / $totalOrders, 2) : 0.00;

        $deliveredOrders = (clone $ordersQuery)->where('status', 'delivered')->count();
        $deliveredSales = (float) (clone $ordersQuery)->where('status', 'delivered')->sum('subtotal');
        $cancelledOrders = (clone $ordersQuery)->where('status', 'cancelled')->count();

        $deliveryRate = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0.0;
        $cancellationRate = $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 1) : 0.0;

        // 4. Returns & RTOs
        $returnsCount = OrderReturn::whereHas('orderItem.fulfilmentOrder', function ($q) use ($seller, $startDate, $endDate, $storeId) {
            $q->where('seller_id', $seller->id)->whereBetween('created_at', [$startDate, $endDate]);
            if ($storeId) {
                $q->where('store_id', (int) $storeId);
            }
        })->count();

        $returnRate = $deliveredOrders > 0
            ? round(($returnsCount / $deliveredOrders) * 100, 2)
            : ($totalOrders > 0 ? round(($returnsCount / $totalOrders) * 100, 2) : 0.00);

        $rtoCount = RtoCase::whereHas('shipment.fulfilmentOrder', function ($q) use ($seller, $startDate, $endDate, $storeId) {
            $q->where('seller_id', $seller->id)->whereBetween('created_at', [$startDate, $endDate]);
            if ($storeId) {
                $q->where('store_id', (int) $storeId);
            }
        })->count();

        $rtoRate = $totalOrders > 0 ? round(($rtoCount / $totalOrders) * 100, 2) : 0.00;

        // 5. Order Status Distribution Breakdown
        $statusDistribution = [
            'pending' => (clone $ordersQuery)->whereIn('status', ['pending', 'placed'])->count(),
            'processing' => (clone $ordersQuery)->whereIn('status', ['accepted', 'packed', 'ready'])->count(),
            'in_transit' => (clone $ordersQuery)->whereIn('status', ['dispatched', 'in_transit', 'out_for_delivery'])->count(),
            'delivered' => $deliveredOrders,
            'cancelled' => $cancelledOrders,
            'returned' => $returnsCount,
            'rto' => $rtoCount,
        ];

        // 6. Payment & Settlement Summary
        $commissionQuery = CommissionEntry::query()
            ->forSeller($seller->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $settlementSummary = [
            'gross_ledger' => (float) (clone $commissionQuery)->sum('base_amount'),
            'commission_deducted' => (float) (clone $commissionQuery)->sum('commission'),
            'shipping_deducted' => (float) (clone $commissionQuery)->sum('shipping_adjustment'),
            'net_payable' => (float) (clone $commissionQuery)->sum('net_payable'),
            'settled' => (float) (clone $commissionQuery)->whereHas('settlementItem.settlement', fn ($q) => $q->whereIn('status', ['paid', 'settled']))->sum('net_payable'),
            'pending' => (float) (clone $commissionQuery)->where(function ($q) {
                $q->whereDoesntHave('settlementItem')
                  ->orWhereHas('settlementItem.settlement', fn ($sq) => $sq->whereIn('status', ['pending', 'processing']));
            })->sum('net_payable'),
            'on_hold' => (float) (clone $commissionQuery)->whereHas('settlementItem.settlement', fn ($q) => $q->where('status', 'on_hold'))->sum('net_payable'),
        ];

        // 7. Inventory & Catalog Summary
        $allSellerProducts = Product::where('seller_id', $seller->id)->with('variants');
        $totalProducts = (clone $allSellerProducts)->count();
        $activeProducts = (clone $allSellerProducts)->where('status', 'active')->count();

        // 8. Daily Trend Data (Real points for Chart plotting)
        $dailyTrends = $this->buildDailyTrends($seller->id, $startDate, $endDate, $storeId ? (int) $storeId : null);

        // 9. Top-Selling Products in Date Range
        $topSelling = OrderItem::query()
            ->whereHas('fulfilmentOrder', function ($q) use ($seller, $startDate, $endDate, $storeId) {
                $q->where('seller_id', $seller->id)->whereBetween('created_at', [$startDate, $endDate]);
                if ($storeId) {
                    $q->where('store_id', (int) $storeId);
                }
            })
            ->select('product_id', DB::raw('SUM(qty) as total_units'), DB::raw('SUM(total) as total_revenue'), DB::raw('COUNT(id) as orders_count'))
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->with(['product.media', 'product.category'])
            ->take(10)
            ->get();

        // 10. Low-Performing Products in Date Range (Products with stock but 0 or < 3 sales)
        $lowPerforming = Product::where('seller_id', $seller->id)
            ->where('status', 'active')
            ->whereDoesntHave('orderItems', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['variants', 'media', 'category'])
            ->latest('id')
            ->take(10)
            ->get();

        $metrics = [
            'total_orders' => $totalOrders,
            'distinct_orders' => $distinctOrders,
            'gross_sales' => $grossSales,
            'net_revenue' => $netRevenue,
            'aov' => $aov,
            'delivered_orders' => $deliveredOrders,
            'delivered_sales' => $deliveredSales,
            'cancelled_orders' => $cancelledOrders,
            'delivery_rate' => $deliveryRate,
            'cancellation_rate' => $cancellationRate,
            'returns_count' => $returnsCount,
            'return_rate' => $returnRate,
            'rto_count' => $rtoCount,
            'rto_rate' => $rtoRate,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
        ];

        return view('seller.business-dashboard.index', compact(
            'seller',
            'range',
            'rangeLabel',
            'startDate',
            'endDate',
            'tab',
            'metrics',
            'statusDistribution',
            'settlementSummary',
            'dailyTrends',
            'topSelling',
            'lowPerforming'
        ));
    }

    /**
     * Provide JSON chart dataset for Alpine.js or asynchronous UI refreshes.
     */
    public function chartData(Request $request): JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        [$startDate, $endDate, $range, $rangeLabel] = $this->resolveDateRange($request);
        $storeId = $request->query('store_id');

        $dailyTrends = $this->buildDailyTrends($seller->id, $startDate, $endDate, $storeId ? (int) $storeId : null);

        return response()->json([
            'range' => $range,
            'label' => $rangeLabel,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'trends' => $dailyTrends,
        ]);
    }

    /**
     * Export verified business analytics report as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        [$startDate, $endDate, $range, $rangeLabel] = $this->resolveDateRange($request);
        $storeId = $request->query('store_id');

        $query = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['order.customer', 'items'])
            ->latest('id');

        if ($storeId) {
            $query->where('store_id', (int) $storeId);
        }

        $filename = 'allzone-analytics-report-' . $startDate->format('Ymd') . '-to-' . $endDate->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date',
                'Fulfilment Order #',
                'Parent Order #',
                'Status',
                'Items Count',
                'Gross Sales (INR)',
                'Commission Amount (INR)',
                'Net Seller Payable (INR)',
                'Delivered At',
            ]);

            $query->chunk(200, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->fulfilment_order_number,
                        $order->order?->order_number ?? 'N/A',
                        strtoupper((string) $order->status),
                        $order->items->count(),
                        number_format((float) $order->subtotal, 2, '.', ''),
                        number_format((float) $order->commission_amount, 2, '.', ''),
                        number_format((float) $order->seller_payable, 2, '.', ''),
                        $order->delivered_at ? $order->delivered_at->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Resolve date range filter from request parameters.
     */
    private function resolveDateRange(Request $request): array
    {
        $range = (string) $request->query('range', 'last_7');

        switch ($range) {
            case 'yesterday':
                $startDate = Carbon::yesterday()->startOfDay();
                $endDate = Carbon::yesterday()->endOfDay();
                $label = 'Yesterday (' . $startDate->format('d M') . ')';
                break;

            case 'last_30':
            case 'last_30_days':
                $range = 'last_30';
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $label = 'Last 30 Days (' . $startDate->format('d M') . ' - ' . $endDate->format('d M') . ')';
                break;

            case 'custom':
                $startDate = $request->query('start_date')
                    ? Carbon::parse((string) $request->query('start_date'))->startOfDay()
                    : Carbon::now()->subDays(6)->startOfDay();
                $endDate = $request->query('end_date')
                    ? Carbon::parse((string) $request->query('end_date'))->endOfDay()
                    : Carbon::now()->endOfDay();

                if ($startDate->gt($endDate)) {
                    $temp = $startDate;
                    $startDate = $endDate->copy()->startOfDay();
                    $endDate = $temp->copy()->endOfDay();
                }
                $label = 'Custom (' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') . ')';
                break;

            case 'last_7':
            case 'last_7_days':
            default:
                $range = 'last_7';
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $label = 'Last 7 Days (' . $startDate->format('d M') . ' - ' . $endDate->format('d M') . ')';
                break;
        }

        return [$startDate, $endDate, $range, $label];
    }

    /**
     * Build day-by-day trend points strictly from database records.
     */
    private function buildDailyTrends(int $sellerId, Carbon $startDate, Carbon $endDate, ?int $storeId): array
    {
        $period = CarbonPeriod::create($startDate->copy()->startOfDay(), '1 day', $endDate->copy()->endOfDay());
        $trends = [];

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayQuery = FulfilmentOrder::where('seller_id', $sellerId)
                ->whereBetween('created_at', [$dayStart, $dayEnd]);

            if ($storeId) {
                $dayQuery->where('store_id', $storeId);
            }

            $ordersCount = (clone $dayQuery)->count();
            $grossSales = (float) (clone $dayQuery)->whereNotIn('status', ['cancelled'])->sum('subtotal');
            $netPayable = (float) (clone $dayQuery)->whereNotIn('status', ['cancelled'])->sum('seller_payable');
            $deliveredCount = (clone $dayQuery)->where('status', 'delivered')->count();

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d M'),
                'day_name' => $date->format('D'),
                'orders' => $ordersCount,
                'sales' => $grossSales,
                'revenue' => $netPayable,
                'delivered' => $deliveredCount,
            ];
        }

        return $trends;
    }
}
