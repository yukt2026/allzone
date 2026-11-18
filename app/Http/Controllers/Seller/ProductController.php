<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ProductRequest;
use App\Models\Brand;
use App\Models\CatalogImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\Integration\CatalogImportService;
use App\Services\Seller\SellerProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        protected SellerProductService $productService,
        protected CatalogImportService $catalogImportService
    ) {}

    /**
     * Display Image Bulk Upload screen (Screenshot 5).
     */
    public function bulkUploadView(Request $request)
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        return redirect()->route('seller.products.create', ['tab' => 'bulk']);
    }

    /**
     * Display seller product list with search, filters, pagination, and bulk actions.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'No seller profile attached.');

        $filters = $request->only(['search', 'category_id', 'brand_id', 'status', 'stock', 'quick_capable']);
        $products = $this->productService->listProducts($seller, $filters, 15);

        // Load categories and brands for filter dropdowns (cached)
        $categories = Cache::remember('seller_products_filter_categories', 600, fn () => Category::orderBy('name')->get(['id', 'name']));
        $brands = Cache::remember("seller_products_filter_brands_{$seller->id}", 600, fn () => Brand::where(function ($q) use ($seller) {
            $q->whereNull('seller_id')->orWhere('seller_id', $seller->id);
        })->orderBy('name')->get(['id', 'name']));

        // Efficient single-query status counts
        $statusCounts = Product::where('seller_id', $seller->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $counts = [
            'all' => (int) $statusCounts->sum(),
            'active' => (int) ($statusCounts[ProductStatus::ACTIVE->value] ?? 0),
            'pending' => (int) ($statusCounts[ProductStatus::PENDING_REVIEW->value] ?? 0),
            'draft' => (int) ($statusCounts[ProductStatus::DRAFT->value] ?? 0),
            'rejected' => (int) ($statusCounts[ProductStatus::REJECTED->value] ?? 0),
        ];

        return view('seller.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'brands' => $brands,
            'counts' => $counts,
        ]);
    }

    /**
     * Show catalog overview, single product creation wizard, or bulk catalog upload.
     */
    public function create(Request $request): View
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $categories = $this->getNestedCategories();
        $brands = Cache::remember("seller_brands_{$seller->id}", 600, function () use ($seller) {
            return Brand::where(function ($q) use ($seller) {
                $q->whereNull('seller_id')->orWhere('seller_id', $seller->id);
            })->orderBy('name')->get(['id', 'name', 'is_verified']);
        });

        $attributes = ProductAttribute::with('values')->orderBy('name')->get();

        $totalProducts = Product::where('seller_id', $seller->id)->count();
        $draftCount = Product::where('seller_id', $seller->id)->where('status', ProductStatus::DRAFT->value)->count();
        $pendingCount = Product::where('seller_id', $seller->id)->where('status', ProductStatus::PENDING_REVIEW->value)->count();
        $activeCount = Product::where('seller_id', $seller->id)->where('status', ProductStatus::ACTIVE->value)->count();
        $rejectedCount = Product::where('seller_id', $seller->id)->where('status', ProductStatus::REJECTED->value)->count();
        $bulkUploadsCount = CatalogImport::where('seller_id', $seller->id)->count();
        $singleUploadsCount = $totalProducts;

        $tab = $request->query('tab', 'overview');
        $filter = $request->query('filter', 'all');

        $bulkImports = CatalogImport::where('seller_id', $seller->id)->latest()->paginate(10, ['*'], 'bulk_page')->withQueryString();

        $recentUploadsQuery = Product::where('seller_id', $seller->id)
            ->with(['category', 'brand', 'variants.inventories'])
            ->latest();

        if ($filter === 'active') {
            $recentUploadsQuery->where('status', ProductStatus::ACTIVE);
        } elseif ($filter === 'pending') {
            $recentUploadsQuery->where('status', ProductStatus::PENDING_REVIEW);
        } elseif ($filter === 'rejected') {
            $recentUploadsQuery->where('status', ProductStatus::REJECTED);
        } elseif ($filter === 'draft') {
            $recentUploadsQuery->where('status', ProductStatus::DRAFT);
        }

        $recentUploads = $recentUploadsQuery->paginate(10)->withQueryString();

        $previewImport = null;
        if ($request->query('preview_id')) {
            $previewImport = CatalogImport::where('seller_id', $seller->id)->find($request->query('preview_id'));
        }

        return view('seller.products.create', [
            'seller' => $seller,
            'categories' => $categories,
            'brands' => $brands,
            'attributes' => $attributes,
            'tab' => $tab,
            'filter' => $filter,
            'totalProducts' => $totalProducts,
            'draftCount' => $draftCount,
            'pendingCount' => $pendingCount,
            'activeCount' => $activeCount,
            'rejectedCount' => $rejectedCount,
            'bulkUploadsCount' => $bulkUploadsCount,
            'singleUploadsCount' => $singleUploadsCount,
            'recentUploads' => $recentUploads,
            'bulkImports' => $bulkImports,
            'previewImport' => $previewImport,
        ]);
    }

    /**
     * Download standard prefilled CSV template for bulk product upload.
     */
    public function downloadTemplateCsv(): StreamedResponse
    {
        $csv = $this->catalogImportService->generateTemplateCsv();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'allzone_catalog_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Handle bulk CSV upload, file validation, and dry-run preview.
     */
    public function bulkUploadPreview(Request $request)
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $store = $seller->stores->first() ?: Store::firstOrCreate(
            ['seller_id' => $seller->id],
            ['name' => $seller->business_name, 'slug' => \Illuminate\Support\Str::slug($seller->business_name)]
        );

        $result = $this->catalogImportService->uploadAndPreview($store, $request->file('file'));

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'import_id' => $result['import']->id,
                'total_rows' => $result['total_rows'],
                'valid_count' => $result['valid_count'],
                'failed_count' => $result['failed_count'],
                'errors' => $result['errors'],
                'preview_rows' => $result['preview_rows'],
            ]);
        }

        return redirect()->route('seller.products.create', [
            'tab' => 'bulk',
            'preview_id' => $result['import']->id,
        ])->with('info', "Catalog file analyzed: {$result['valid_count']} valid rows ready for import, {$result['failed_count']} errors detected.");
    }

    /**
     * Confirm and execute bulk import for a validated catalog import batch.
     */
    public function bulkImportConfirm(Request $request, CatalogImport $import)
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $import->seller_id !== $seller->id, 403);

        $result = $this->catalogImportService->confirmImport($import);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json($result);
        }

        return redirect()->route('seller.products.create', ['tab' => 'overview'])
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Download CSV report containing row-level errors for an import batch.
     */
    public function bulkImportErrors(CatalogImport $import): StreamedResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $import->seller_id !== $seller->id, 403);

        $csv = $this->catalogImportService->generateErrorReportCsv($import);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "allzone_import_errors_batch_{$import->id}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Store new product in database.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $uploadedMedia = $request->file('media', []);
        $product = $this->productService->createProduct($seller, $request->validated(), is_array($uploadedMedia) ? $uploadedMedia : []);

        $msg = $product->status === ProductStatus::PENDING_REVIEW
            ? 'Product created and submitted for admin review successfully!'
            : 'Product saved as draft successfully!';

        return redirect()->route('seller.products.index')->with('success', $msg);
    }

    /**
     * Show edit form with sticky save bar and unsaved-changes warning.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([
            'variants.inventories',
            'media' => fn ($q) => $q->orderBy('sort_order'),
            'specifications',
            'category',
            'brand',
        ]);

        $seller = Auth::user()->seller;
        $categories = $this->getNestedCategories();
        $brands = Cache::remember("seller_brands_{$seller->id}", 600, function () use ($seller) {
            return Brand::where(function ($q) use ($seller) {
                $q->whereNull('seller_id')->orWhere('seller_id', $seller->id);
            })->orderBy('name')->get(['id', 'name', 'is_verified']);
        });

        return view('seller.products.edit', [
            'product' => $product,
            'seller' => $seller,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    /**
     * Update product details, variants, media, and specifications.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $uploadedMedia = $request->file('media', []);
        $result = $this->productService->updateProduct(
            $product,
            $request->validated(),
            is_array($uploadedMedia) ? $uploadedMedia : []
        );

        $updatedProduct = $result['product'];
        $retriggered = $result['retriggered_review'];

        if ($retriggered) {
            $msg = 'Sensitive changes detected (pricing, taxes, or core details). Product has been resubmitted for admin review.';
        } elseif ($updatedProduct->status === ProductStatus::PENDING_REVIEW) {
            $msg = 'Product changes saved and submitted for admin review!';
        } else {
            $msg = 'Product details and inventory updated successfully!';
        }

        return redirect()->route('seller.products.edit', $updatedProduct)
            ->with($retriggered ? 'info' : 'success', $msg);
    }

    /**
     * Soft delete a product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', "Product '{$product->title}' has been deleted.");
    }

    /**
     * Inline price & stock quick-edit AJAX handler.
     */
    public function quickUpdateVariant(Request $request, ProductVariant $variant): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'mrp' => ['nullable', 'numeric', 'min:0.01'],
            'selling_price' => ['nullable', 'numeric', 'min:0.01'],
            'quantity_on_hand' => ['nullable', 'integer', 'min:0'],
        ]);

        $updated = $this->productService->quickUpdateVariant($seller, $variant, $validated);

        return response()->json([
            'success' => true,
            'message' => "Quick update applied for SKU: {$updated->sku}",
            'variant' => [
                'id' => $updated->id,
                'mrp' => number_format((float) $updated->mrp, 2),
                'selling_price' => number_format((float) $updated->selling_price, 2),
                'stock' => $updated->inventories->sum('quantity_on_hand'),
            ],
        ]);
    }

    /**
     * Bulk actions handler (activate, deactivate, delete).
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $count = $this->productService->bulkAction($seller, $request->action, $request->product_ids);

        return back()->with('success', "Successfully processed {$count} product(s).");
    }

    /**
     * Export filtered products catalogue as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $filters = $request->only(['category_id', 'brand_id', 'status']);
        return $this->productService->exportCsv($seller, $filters);
    }

    /**
     * AJAX live SKU unique check.
     */
    public function checkSku(Request $request): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $sku = (string) $request->input('sku', '');
        $excludeId = $request->input('exclude_variant_id') ? (int) $request->input('exclude_variant_id') : null;

        $result = $this->productService->checkSkuAvailability($sku, $excludeId);

        return response()->json($result);
    }

    /**
     * Helper to retrieve nested categories formatted with hierarchy path.
     *
     * @return array<int, array{id: int, name: string, path: string}>
     */
    protected function getNestedCategories(): array
    {
        return Cache::remember('seller_nested_categories_flattened', 3600, function () {
            $rootCategories = Category::with('children.children')
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get();

            $flattened = [];

            foreach ($rootCategories as $root) {
                $flattened[] = [
                    'id' => $root->id,
                    'name' => $root->name,
                    'path' => $root->name,
                ];

                foreach ($root->children as $child) {
                    $flattened[] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'path' => "{$root->name} &rarr; {$child->name}",
                    ];

                    foreach ($child->children as $subChild) {
                        $flattened[] = [
                            'id' => $subChild->id,
                            'name' => $subChild->name,
                            'path' => "{$root->name} &rarr; {$child->name} &rarr; {$subChild->name}",
                        ];
                    }
                }
            }

            return $flattened;
        });
    }
}
