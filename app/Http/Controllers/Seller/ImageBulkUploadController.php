<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Services\Seller\ImageBulkUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageBulkUploadController extends Controller
{
    public function __construct(
        protected ImageBulkUploadService $imageService
    ) {}

    /**
     * Display Image Bulk Upload studio with gallery management.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'No seller profile attached.');

        $products = Product::where('seller_id', $seller->id)
            ->with(['variants:id,product_id,sku,name', 'media:id,product_id,path,sort_order'])
            ->latest('id')
            ->get(['id', 'seller_id', 'title', 'slug', 'status']);

        $totalImages = ProductMedia::whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))->count();
        $productsWithImages = Product::where('seller_id', $seller->id)->has('media')->count();
        $productsWithoutImages = Product::where('seller_id', $seller->id)->doesntHave('media')->count();

        // Recent media gallery
        $selectedProductId = $request->query('product_id') ? (int) $request->query('product_id') : null;
        $galleryQuery = ProductMedia::whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
            ->with(['product:id,title,slug', 'variant:id,sku,name'])
            ->orderBy('sort_order');

        if ($selectedProductId) {
            $galleryQuery->where('product_id', $selectedProductId);
        } else {
            $galleryQuery->latest('id');
        }

        $mediaGallery = $galleryQuery->paginate(24)->withQueryString();

        return view('seller.products.bulk-upload', [
            'seller' => $seller,
            'products' => $products,
            'totalImages' => $totalImages,
            'productsWithImages' => $productsWithImages,
            'productsWithoutImages' => $productsWithoutImages,
            'mediaGallery' => $mediaGallery,
            'selectedProductId' => $selectedProductId,
        ]);
    }

    /**
     * Handle multi-image upload with auto-matching or manual product assignment.
     */
    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', 'max:10240'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'set_primary' => ['nullable', 'boolean'],
        ]);

        $uploadedFiles = $request->file('images', []);
        $explicitProduct = null;
        if ($request->filled('product_id')) {
            $explicitProduct = Product::where('seller_id', $seller->id)->find($request->input('product_id'));
            abort_if(!$explicitProduct, 403, 'Unauthorized target product.');
        }

        $explicitVariant = null;
        if ($request->filled('variant_id')) {
            $explicitVariant = ProductVariant::find($request->input('variant_id'));
        }

        $results = [];
        $savedCount = 0;
        $errors = [];

        foreach ($uploadedFiles as $file) {
            $origName = $file->getClientOriginalName();

            // Validate image
            $validation = $this->imageService->validateFile($file, $seller);
            if (!$validation['valid']) {
                $errors[] = "{$origName}: " . $validation['error'];
                $results[] = [
                    'filename' => $origName,
                    'success' => false,
                    'error' => $validation['error'],
                ];
                continue;
            }

            // Determine target product
            $targetProduct = $explicitProduct;
            $targetVariant = $explicitVariant;
            $matchedBy = 'Manual Selection';

            if (!$targetProduct) {
                $match = $this->imageService->matchImageToProduct($origName, $seller);
                $targetProduct = $match['product'];
                $targetVariant = $match['variant'];
                $matchedBy = $match['matched_by'] ?? 'None';
            }

            if (!$targetProduct) {
                $errors[] = "{$origName}: Could not auto-detect target product from filename (e.g. name photos as SKU_1.jpg or select a product).";
                $results[] = [
                    'filename' => $origName,
                    'success' => false,
                    'error' => 'No matching product found.',
                ];
                continue;
            }

            // Store image
            try {
                $media = $this->imageService->storeImageForProduct(
                    $targetProduct,
                    $file,
                    $targetVariant
                );

                if ($request->boolean('set_primary')) {
                    $this->imageService->setPrimaryImage($media);
                }

                $savedCount++;
                $results[] = [
                    'id' => $media->id,
                    'filename' => $origName,
                    'success' => true,
                    'url' => $media->url,
                    'product_id' => $targetProduct->id,
                    'product_title' => $targetProduct->title,
                    'matched_by' => $matchedBy,
                ];
            } catch (\Throwable $e) {
                $errors[] = "{$origName}: Storage error - " . $e->getMessage();
                $results[] = [
                    'filename' => $origName,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => $savedCount > 0,
                'saved_count' => $savedCount,
                'total_files' => count($uploadedFiles),
                'errors' => $errors,
                'items' => $results,
            ]);
        }

        $msg = "Successfully uploaded and assigned {$savedCount} image(s).";
        if (count($errors) > 0) {
            return back()->with('info', $msg . ' (' . count($errors) . ' file(s) had errors).')->with('upload_errors', $errors);
        }

        return back()->with('success', $msg);
    }

    /**
     * Process CSV mapping file alongside bulk image files.
     */
    public function mapCsv(Request $request): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', 'max:10240'],
        ]);

        $uploadedFiles = [];
        foreach ($request->file('images', []) as $f) {
            $uploadedFiles[$f->getClientOriginalName()] = $f;
        }

        $result = $this->imageService->processCsvMapping($seller, $request->file('csv_file'), $uploadedFiles);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json($result);
        }

        if ($result['mapped_count'] > 0) {
            $msg = "Successfully mapped and uploaded {$result['mapped_count']} product image(s).";
            if ($result['failed_count'] > 0) {
                return back()->with('info', $msg . " ({$result['failed_count']} rows had issues).")
                    ->with('upload_errors', $result['errors']);
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', "No images were mapped. Check the CSV file format and uploaded filenames.")
            ->with('upload_errors', $result['errors']);
    }

    /**
     * Download CSV mapping template.
     */
    public function downloadMappingTemplate(): StreamedResponse
    {
        $csv = $this->imageService->generateMappingTemplateCsv();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'allzone_image_mapping_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Fetch gallery images for a specific product as JSON.
     */
    public function productGallery(Product $product): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $product->seller_id !== $seller->id, 403);

        $media = $product->media()->with('variant:id,sku,name')->orderBy('sort_order')->get();

        return response()->json([
            'product_id' => $product->id,
            'title' => $product->title,
            'images' => $media->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'path' => $m->path,
                'sort_order' => $m->sort_order,
                'type' => $m->type->value,
                'variant_sku' => $m->variant?->sku,
                'is_primary' => $m->sort_order === 1,
            ]),
        ]);
    }

    /**
     * Reorder product gallery images.
     */
    public function reorderGallery(Request $request, Product $product): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $product->seller_id !== $seller->id, 403);

        $request->validate([
            'media_ids' => ['required', 'array'],
            'media_ids.*' => ['integer', 'exists:product_media,id'],
        ]);

        $this->imageService->reorderGallery($product, $request->input('media_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Gallery image order updated successfully.',
        ]);
    }

    /**
     * Set a product image as the primary cover photo.
     */
    public function setPrimary(ProductMedia $media): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        $product = $media->product;
        abort_if(!$seller || !$product || $product->seller_id !== $seller->id, 403);

        $this->imageService->setPrimaryImage($media);

        if (request()->expectsJson() || request()->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Primary cover photo updated successfully.',
            ]);
        }

        return back()->with('success', 'Cover photo updated.');
    }

    /**
     * Replace an existing image with a new file upload.
     */
    public function replace(Request $request, ProductMedia $media): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        $product = $media->product;
        abort_if(!$seller || !$product || $product->seller_id !== $seller->id, 403);

        $request->validate([
            'new_image' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('new_image');
        $val = $this->imageService->validateFile($file, $seller);
        if (!$val['valid']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $val['error']], 422);
            }
            return back()->with('error', $val['error']);
        }

        $updatedMedia = $this->imageService->replaceImage($media, $file);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'id' => $updatedMedia->id,
                'url' => $updatedMedia->url,
                'message' => 'Image replaced successfully.',
            ]);
        }

        return back()->with('success', 'Image replaced successfully.');
    }

    /**
     * Delete an image from product gallery.
     */
    public function destroy(ProductMedia $media): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        $product = $media->product;
        abort_if(!$seller || !$product || $product->seller_id !== $seller->id, 403);

        $this->imageService->deleteImage($media);

        if (request()->expectsJson() || request()->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Image removed successfully.',
            ]);
        }

        return back()->with('success', 'Image removed from gallery.');
    }
}
