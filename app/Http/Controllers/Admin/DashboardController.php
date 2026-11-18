<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboardService
    ) {}

    /**
     * Display the Admin Central Command SaaS Dashboard.
     */
    public function index(Request $request): View
    {
        $range = (string) $request->query('range', '30d');
        if (!in_array($range, ['yesterday', 'today', '7d', '30d', '90d', 'custom'], true)) {
            $range = '30d';
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $stats = $this->dashboardService->getTopStats($range, $startDate, $endDate);
        $widgets = $this->dashboardService->getWidgetsData();
        $topSellers = $this->dashboardService->getTopSellers($range, $startDate, $endDate);
        $topProducts = $this->dashboardService->getTopSellingProducts($range, $startDate, $endDate, 10);

        // Actionable counters for quick actions bar
        $quickActions = [
            'pending_sellers' => Seller::whereIn('status', [SellerStatus::PENDING, SellerStatus::KYC_SUBMITTED])->count(),
            'pending_products' => Product::where('status', ProductStatus::PENDING_REVIEW)->count(),
            'open_disputes' => SellerClaim::whereIn('status', [ClaimStatus::OPEN, ClaimStatus::UNDER_REVIEW, ClaimStatus::MORE_INFO_NEEDED])->count(),
            'pending_settlements' => SellerSettlement::where('status', SettlementStatus::PENDING)->count(),
            'pending_ads' => \App\Models\SellerAdCampaign::where('status', 'pending_approval')->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'widgets' => $widgets,
            'topSellers' => $topSellers,
            'topProducts' => $topProducts,
            'activeRange' => $range,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'quickActions' => $quickActions,
        ]);
    }

    /**
     * AJAX endpoint returning JSON data for Chart.js and dynamic widgets.
     */
    public function chartData(Request $request): JsonResponse
    {
        $range = (string) $request->query('range', '30d');
        if (!in_array($range, ['yesterday', 'today', '7d', '30d', '90d', 'custom'], true)) {
            $range = '30d';
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $chartType = (string) $request->query('chart', 'all');

        $data = match ($chartType) {
            'sales_orders' => $this->dashboardService->getSalesAndOrdersTrend($range, $startDate, $endDate),
            'order_types' => $this->dashboardService->getOrdersByType($range, $startDate, $endDate),
            'category_revenue' => $this->dashboardService->getRevenueByCategory($range, $startDate, $endDate),
            'top_sellers' => $this->dashboardService->getTopSellers($range, $startDate, $endDate),
            'top_products' => $this->dashboardService->getTopSellingProducts($range, $startDate, $endDate),
            'status_funnel' => $this->dashboardService->getOrderStatusFunnel($range, $startDate, $endDate),
            'stats' => $this->dashboardService->getTopStats($range, $startDate, $endDate),
            default => [
                'stats' => $this->dashboardService->getTopStats($range, $startDate, $endDate),
                'sales_orders' => $this->dashboardService->getSalesAndOrdersTrend($range, $startDate, $endDate),
                'order_types' => $this->dashboardService->getOrdersByType($range, $startDate, $endDate),
                'category_revenue' => $this->dashboardService->getRevenueByCategory($range, $startDate, $endDate),
                'top_sellers' => $this->dashboardService->getTopSellers($range, $startDate, $endDate),
                'top_products' => $this->dashboardService->getTopSellingProducts($range, $startDate, $endDate),
                'status_funnel' => $this->dashboardService->getOrderStatusFunnel($range, $startDate, $endDate),
            ],
        };

        return response()->json([
            'success' => true,
            'range' => $range,
            'chart' => $chartType,
            'data' => $data,
        ]);
    }

    /**
     * Stream CSV export of executive dashboard metrics.
     */
    public function exportSummary(Request $request): StreamedResponse
    {
        $range = (string) $request->query('range', '30d');
        if (!in_array($range, ['yesterday', 'today', '7d', '30d', '90d', 'custom'], true)) {
            $range = '30d';
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return $this->dashboardService->exportSummaryCsv($range, $startDate, $endDate);
    }
}
