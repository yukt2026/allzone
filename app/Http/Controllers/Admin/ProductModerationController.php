<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectProductRequest;
use App\Http\Requests\Admin\UpdateProductOnBehalfRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\Admin\AdminCatalogueService;
use App\Services\Integration\CatalogImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductModerationController extends Controller
{
    public function __construct(
        protected AdminCatalogueService $catalogueService,
        protected CatalogImportService $catalogImportService
    ) {}

    /**
     * Display the product moderation queue with filters and counters across 6 tabs.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = [
            'status' => $request->query('status', 'pending_review'),
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'brand_id' => $request->query('brand_id'),
            'seller_id' => $request->query('seller_id'),
            'is_quick_capable' => $request->query('is_quick_capable'),
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'sort' => $request->query('sort', 'latest'),
        ];

        $products = $this->catalogueService->getModerationQueue($filters, 15);
        $stats = $this->catalogueService->getModerationStats();
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $sellers = Seller::orderBy('business_name')->get();

        return view('admin.catalogue.moderation.index', [
            'products' => $products,
            'filters' => $filters,
            'stats' => $stats,
            'categories' => $categories,
            'brands' => $brands,
            'sellers' => $sellers,
        ]);
    }

    /**
     * Side-by-side inspection and preview of a submitted product.
     */
    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load([
            'seller.user',
            'category.parent',
            'brand',
            'variants.inventories.location',
            'media',
            'specifications',
        ]);

        $activities = \Spatie\Activitylog\Models\Activity::forSubject($product)
            ->with('causer')
            ->latest('id')
            ->take(20)
            ->get();

        // Calculate financial margins and commission estimations
        $defaultVariant = $product->variants->firstWhere('is_default', true) ?: $product->variants->first();
        $sellingPrice = (float) ($defaultVariant?->selling_price ?? 0);
        $mrp = (float) ($defaultVariant?->mrp ?? 0);
        $costPrice = (float) ($defaultVariant?->cost_price ?? 0);

        $commissionPercent = (float) ($product->category?->commission_percent ?? 5.00);
        $estimatedCommission = round(($sellingPrice * $commissionPercent) / 100, 2);
        $sellerReceivable = max(0, $sellingPrice - $estimatedCommission);

        $discountPercent = $mrp > 0 && $sellingPrice < $mrp ? (int) round((($mrp - $sellingPrice) / $mrp) * 100) : 0;
        $profitMarginPercent = $sellingPrice > 0 && $costPrice > 0 ? (int) round((($sellingPrice - $costPrice) / $sellingPrice) * 100) : null;

        return view('admin.catalogue.moderation.show', [
            'product' => $product,
            'activities' => $activities,
            'financials' => [
                'selling_price' => $sellingPrice,
                'mrp' => $mrp,
                'cost_price' => $costPrice,
                'discount_percent' => $discountPercent,
                'profit_margin_percent' => $profitMarginPercent,
                'commission_percent' => $commissionPercent,
                'estimated_commission' => $estimatedCommission,
                'seller_receivable' => $sellerReceivable,
            ],
        ]);
    }

    /**
     * Edit-on-behalf: Admin adjusts catalogue details before approval.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['category', 'brand', 'defaultVariant', 'variants']);
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        return view('admin.catalogue.moderation.edit', [
            'product' => $product,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    /**
     * Save admin changes to the product listing.
     */
    public function update(UpdateProductOnBehalfRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->catalogueService->updateProductOnBehalf($product, $request->validated(), $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.show', $product)
            ->with('success', "Product '{$product->title}' updated on behalf of merchant.");
    }

    /**
     * Approve listing into active live catalogue.
     */
    public function approve(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('approve', $product);

        $this->catalogueService->approveProduct($product, $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Product '{$product->title}' approved! Listing is now active on marketplace.");
    }

    /**
     * Reject listing with mandatory reason and feedback.
     */
    public function reject(RejectProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('reject', $product);

        $this->catalogueService->rejectProduct($product, (string) $request->validated('reason'), $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Product '{$product->title}' rejected. Feedback sent to merchant.");
    }

    /**
     * Block listing according to platform policy.
     */
    public function block(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('block', $product);

        $validated = $request->validate([
            'policy_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->catalogueService->blockProduct($product, $validated['policy_reason'], $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Product '{$product->title}' has been blocked according to platform compliance policy.");
    }

    /**
     * Request specific corrections from seller for product listing.
     */
    public function requestCorrections(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('requestCorrection', $product);

        $validated = $request->validate([
            'correction_notes' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->catalogueService->requestCorrections($product, $validated['correction_notes'], $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Corrections requested for '{$product->title}'. Merchant notified.");
    }

    /**
     * Bulk approve multiple pending products.
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('bulkApprove', Product::class);

        $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $approvedCount = $this->catalogueService->bulkApproveProducts((array) $request->input('product_ids'), $request->user());

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Bulk approved {$approvedCount} product listing(s).");
    }

    /**
     * Bulk update product statuses (e.g. active, inactive, blocked, rejected).
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $this->authorize('bulkUpdate', Product::class);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'status' => ['required', new Enum(ProductStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $status = ProductStatus::from($validated['status']);
        $count = $this->catalogueService->bulkUpdateStatus(
            (array) $validated['product_ids'],
            $status,
            $validated['reason'] ?? null,
            $request->user()
        );

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Updated status to '{$status->label()}' for {$count} product(s).");
    }

    /**
     * Bulk reassign category for selected products.
     */
    public function bulkAssignCategory(Request $request): RedirectResponse
    {
        $this->authorize('bulkUpdate', Product::class);

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $count = $this->catalogueService->bulkAssignCategory(
            (array) $validated['product_ids'],
            (int) $validated['category_id'],
            $request->user()
        );

        return redirect()
            ->route('admin.catalogue.moderation.index')
            ->with('success', "Reassigned {$count} product(s) to new category.");
    }

    /**
     * Export products to CSV according to active filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Product::class);

        return $this->catalogueService->exportProductsCsv($request->all());
    }

    /**
     * Download the standard CSV catalog template.
     */
    public function importTemplate(): StreamedResponse
    {
        $this->authorize('viewAny', Product::class);

        $csv = $this->catalogImportService->generateTemplateCsv();

        return response()->stream(function () use ($csv) {
            echo $csv;
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="allzone_catalog_template.csv"',
        ]);
    }
}
