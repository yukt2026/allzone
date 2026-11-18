<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\MarketplaceReportExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Seller;
use App\Services\Admin\AdminReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected AdminReportService $reportService
    ) {}

    /**
     * Authorize reporting dashboard access.
     */
    protected function authorizeAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $allowedRoles = [
            'super_admin',
            'admin',
            'operations_admin',
            'finance_admin',
            'finance_manager',
            'catalog_manager',
        ];

        if (!$user->hasAnyRole($allowedRoles) && !$user->can('orders.view') && !$user->can('audit_logs.view')) {
            abort(403, 'Unauthorized. Administrative reporting clearance required.');
        }
    }

    /**
     * Display reports dashboard with tab selector and multi-dimensional filters.
     */
    public function index(Request $request): View
    {
        $this->authorizeAccess();

        $validTabs = [
            'sales' => 'Sales & GMV',
            'orders' => 'Orders & Channels',
            'sellers' => 'Seller Leaderboard',
            'customers' => 'Customer Growth & LTV',
            'products' => 'Product Performance',
            'categories' => 'Category Turnover',
            'inventory' => 'Inventory & Valuation',
            'returns' => 'Returns & RTO',
            'claims' => 'Claims & Disputes',
            'payments' => 'Payments & Reconciliation',
            'refunds' => 'Customer Refunds',
            'commission' => 'Platform Commission',
            'settlements' => 'Seller Settlements',
            'promotions' => 'Promotions & Discounts',
            'tax' => 'Tax & GST Reconciliation',
        ];

        $tab = (string) $request->query('tab', 'sales');
        if (!array_key_exists($tab, $validTabs)) {
            $tab = 'sales';
        }

        $filters = $request->all();
        [$startDate, $endDate] = $this->reportService->resolveDates($filters);

        $metrics = $this->reportService->getSummaryMetrics($filters);

        $data = match ($tab) {
            'orders' => $this->reportService->getOrdersReport($filters),
            'sellers' => $this->reportService->getSellersReport($filters),
            'customers' => $this->reportService->getCustomersReport($filters),
            'products' => $this->reportService->getProductsReport($filters),
            'categories' => $this->reportService->getCategoriesReport($filters),
            'inventory' => $this->reportService->getInventoryReport($filters),
            'returns' => $this->reportService->getReturnsReport($filters),
            'claims' => $this->reportService->getClaimsReport($filters),
            'payments' => $this->reportService->getPaymentsReport($filters),
            'refunds' => $this->reportService->getRefundsReport($filters),
            'commission' => $this->reportService->getCommissionReport($filters, true),
            'settlements' => $this->reportService->getSettlementsReport($filters),
            'promotions' => $this->reportService->getPromotionsReport($filters),
            'tax' => $this->reportService->getTaxReport($filters),
            default => $this->reportService->getSalesReport($filters),
        };

        $sellers = Seller::select('id', 'business_name')->orderBy('business_name')->get();
        $categories = Category::select('id', 'name')->orderBy('name')->get();

        return view('admin.reports.index', compact(
            'tab',
            'validTabs',
            'startDate',
            'endDate',
            'filters',
            'metrics',
            'data',
            'sellers',
            'categories'
        ));
    }

    /**
     * Export selected report type as CSV (default), XLSX, or PDF.
     */
    public function export(Request $request, string $type): StreamedResponse|Response|BinaryFileResponse
    {
        $this->authorizeAccess();

        $format = strtolower((string) $request->query('format', 'csv'));

        if ($format === 'pdf') {
            return $this->exportPdf($request, $type);
        }

        if ($format === 'xlsx') {
            return $this->exportXlsx($request, $type);
        }

        return $this->exportCsv($request, $type);
    }

    /**
     * Export selected report as CSV.
     */
    public function exportCsv(Request $request, string $type): StreamedResponse
    {
        $this->authorizeAccess();

        $filters = $request->all();
        $exportData = $this->reportService->getExportData($type, $filters);

        $filename = "allzone-report-{$type}-" . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($exportData) {
            $handle = fopen('php://output', 'w');

            // Insert UTF-8 BOM for native Microsoft Excel UTF-8 support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [setting('name', 'ALLZONE') . ' Marketplace Analytics: ' . $exportData['title']]);
            fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s T')]);
            fputcsv($handle, []);

            // Column Headings
            fputcsv($handle, $exportData['headings']);

            // Data Rows
            foreach ($exportData['rows'] as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export selected report as PDF.
     */
    public function exportPdf(Request $request, string $type): Response
    {
        $this->authorizeAccess();

        $filters = $request->all();
        $exportData = $this->reportService->getExportData($type, $filters);

        $pdf = Pdf::loadView('pdf.marketplace-report', $exportData)
            ->setPaper('a4', 'portrait');

        $filename = "allzone-report-{$type}-" . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export selected report as XLSX.
     */
    public function exportXlsx(Request $request, string $type): BinaryFileResponse
    {
        $this->authorizeAccess();

        $filters = $request->all();
        $exportData = $this->reportService->getExportData($type, $filters);

        $filename = "allzone-report-{$type}-" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new MarketplaceReportExport($exportData['headings'], $exportData['rows']),
            $filename
        );
    }
}
