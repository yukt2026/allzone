<?php

declare(strict_types=1);

namespace App\Services\Seller;

use App\Enums\MediaType;
use App\Jobs\OptimizeProductMediaJob;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Seller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageBulkUploadService
{
    /**
     * Whitelist of allowed extensions.
     */
    protected const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'mp4'];

    /**
     * Whitelist of allowed MIME types.
     */
    protected const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'video/mp4',
    ];

    /**
     * Minimum image dimension in pixels.
     */
    public const MIN_DIMENSION = 200;

    /**
     * Maximum file size in kilobytes (10MB).
     */
    public const MAX_FILE_SIZE_KB = 10240;

    /**
     * Validate an uploaded media file.
     *
     * @return array{valid: bool, error: ?string, width: ?int, height: ?int, hash: ?string, mime: string, size_formatted: string}
     */
    public function validateFile(UploadedFile $file, ?Seller $seller = null): array
    {
        $sizeBytes = $file->getSize() ?: 0;
        $sizeKb = round($sizeBytes / 1024, 2);
        $sizeFormatted = $sizeKb > 1024 ? round($sizeKb / 1024, 2) . ' MB' : $sizeKb . ' KB';
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = $file->getMimeType() ?: '';

        // 1. Extension check
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'valid' => false,
                'error' => "Unsupported file extension '.{$ext}'. Allowed formats: JPG, PNG, WEBP, MP4.",
                'width' => null,
                'height' => null,
                'hash' => null,
                'mime' => $mime,
                'size_formatted' => $sizeFormatted,
            ];
        }

        // 2. MIME type check
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return [
                'valid' => false,
                'error' => "Invalid file MIME type '{$mime}'.",
                'width' => null,
                'height' => null,
                'hash' => null,
                'mime' => $mime,
                'size_formatted' => $sizeFormatted,
            ];
        }

        // 3. File size check
        if ($sizeKb > self::MAX_FILE_SIZE_KB) {
            return [
                'valid' => false,
                'error' => "File size ({$sizeFormatted}) exceeds the 10MB limit.",
                'width' => null,
                'height' => null,
                'hash' => null,
                'mime' => $mime,
                'size_formatted' => $sizeFormatted,
            ];
        }

        $width = null;
        $height = null;
        $hash = null;

        // 4. Image corruption and dimension checks
        if (str_starts_with($mime, 'image/')) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'error' => 'File appears corrupted or is not a readable image.',
                    'width' => null,
                    'height' => null,
                    'hash' => null,
                    'mime' => $mime,
                    'size_formatted' => $sizeFormatted,
                ];
            }

            $width = $imageInfo[0] ?? null;
            $height = $imageInfo[1] ?? null;

            if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
                return [
                    'valid' => false,
                    'error' => "Image resolution ({$width}x{$height}px) is too small. Minimum required is 200x200px (recommended 1000x1000px).",
                    'width' => $width,
                    'height' => $height,
                    'hash' => null,
                    'mime' => $mime,
                    'size_formatted' => $sizeFormatted,
                ];
            }
        }

        // 5. Hash computation
        $hash = @hash_file('sha256', $file->getRealPath()) ?: null;

        return [
            'valid' => true,
            'error' => null,
            'width' => $width,
            'height' => $height,
            'hash' => $hash,
            'mime' => $mime,
            'size_formatted' => $sizeFormatted,
        ];
    }

    /**
     * Auto-match an image filename to a seller product or variant.
     * Looks for SKU patterns (e.g. "SKU-TV-01_1.jpg", "SONY55-front.jpg") or Product ID ("PRD-12_2.png").
     *
     * @return array{product: ?Product, variant: ?ProductVariant, matched_by: ?string}
     */
    public function matchImageToProduct(string $filename, Seller $seller): array
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        // Strip common suffix patterns like _front, _1, _main, _back, _side, -1, -2
        $cleaned = preg_replace('/[_\-](front|back|side|top|bottom|main|thumb|angle|\d+)$/i', '', $baseName);
        $candidateTerms = array_unique(array_filter([$baseName, $cleaned]));

        // 1. Try to match by SKU on seller's variants
        foreach ($candidateTerms as $term) {
            $variant = ProductVariant::whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
                ->where(function ($q) use ($term) {
                    $q->where('sku', $term)
                        ->orWhere('sku', strtoupper($term))
                        ->orWhere('barcode', $term);
                })
                ->with('product')
                ->first();

            if ($variant && $variant->product) {
                return [
                    'product' => $variant->product,
                    'variant' => $variant,
                    'matched_by' => "SKU: {$variant->sku}",
                ];
            }
        }

        // 2. Try to match by Product ID (e.g. "123_front.jpg" or "PRD-123.jpg")
        if (preg_match('/^(?:PRD-)?(\d+)/i', $baseName, $m)) {
            $productId = (int) $m[1];
            $product = Product::where('seller_id', $seller->id)->find($productId);
            if ($product) {
                return [
                    'product' => $product,
                    'variant' => null,
                    'matched_by' => "Product ID: #{$product->id}",
                ];
            }
        }

        // 3. Try to match by Product slug or title prefix
        foreach ($candidateTerms as $term) {
            $product = Product::where('seller_id', $seller->id)
                ->where(function ($q) use ($term) {
                    $q->where('slug', Str::slug($term))
                        ->orWhere('title', 'like', "%{$term}%");
                })
                ->first();

            if ($product) {
                return [
                    'product' => $product,
                    'variant' => null,
                    'matched_by' => "Product: {$product->title}",
                ];
            }
        }

        return [
            'product' => null,
            'variant' => null,
            'matched_by' => null,
        ];
    }

    /**
     * Store and associate an image with a product and optional variant.
     */
    public function storeImageForProduct(
        Product $product,
        UploadedFile $file,
        ?ProductVariant $variant = null,
        ?int $sortOrder = null
    ): ProductMedia {
        $mime = $file->getMimeType() ?: '';
        $type = str_starts_with($mime, 'video/') ? MediaType::VIDEO : MediaType::IMAGE;
        $ext = $file->getClientOriginalExtension() ?: ($type === MediaType::VIDEO ? 'mp4' : 'jpg');
        $fileName = 'prod-' . $product->id . '-' . time() . '-' . Str::random(6) . '.' . $ext;
        $subDir = $type === MediaType::VIDEO ? 'videos' : 'images';
        $path = $file->storeAs("products/{$subDir}", $fileName, 'public');

        if ($sortOrder === null) {
            $currentMax = (int) $product->media()->max('sort_order') ?: 0;
            $sortOrder = $currentMax + 1;
        }

        $media = ProductMedia::create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'type' => $type,
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);

        // Dispatch background thumbnail generation job
        dispatch(new OptimizeProductMediaJob($media->id));

        return $media;
    }

    /**
     * Set a media item as primary for its product.
     * Shifts its sort_order to 1 and shifts other items down.
     */
    public function setPrimaryImage(ProductMedia $media): void
    {
        DB::transaction(function () use ($media) {
            $productId = $media->product_id;

            // Shift other media sort_order
            ProductMedia::where('product_id', $productId)
                ->where('id', '!=', $media->id)
                ->increment('sort_order', 1);

            $media->update(['sort_order' => 1]);
        });
    }

    /**
     * Reorder all gallery images for a product.
     *
     * @param array<int> $mediaIdsInOrder Array of ProductMedia IDs in desired display sequence.
     */
    public function reorderGallery(Product $product, array $mediaIdsInOrder): void
    {
        DB::transaction(function () use ($product, $mediaIdsInOrder) {
            foreach ($mediaIdsInOrder as $order => $mediaId) {
                ProductMedia::where('id', $mediaId)
                    ->where('product_id', $product->id)
                    ->update(['sort_order' => $order + 1]);
            }
        });
    }

    /**
     * Replace an existing media item with a new uploaded file while preserving sort order and relations.
     */
    public function replaceImage(ProductMedia $media, UploadedFile $newFile): ProductMedia
    {
        return DB::transaction(function () use ($media, $newFile) {
            $oldPath = $media->path;

            $mime = $newFile->getMimeType() ?: '';
            $type = str_starts_with($mime, 'video/') ? MediaType::VIDEO : MediaType::IMAGE;
            $ext = $newFile->getClientOriginalExtension() ?: 'jpg';
            $fileName = 'prod-' . $media->product_id . '-' . time() . '-' . Str::random(6) . '.' . $ext;
            $path = $newFile->storeAs('products/images', $fileName, 'public');

            $media->update([
                'type' => $type,
                'path' => $path,
            ]);

            // Safely cleanup old file
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            dispatch(new OptimizeProductMediaJob($media->id));

            return $media;
        });
    }

    /**
     * Delete a media item safely from database and storage.
     */
    public function deleteImage(ProductMedia $media): void
    {
        DB::transaction(function () use ($media) {
            $path = $media->path;
            $productId = $media->product_id;

            $media->delete();

            // Re-sequence remaining media sort_orders
            $remaining = ProductMedia::where('product_id', $productId)->orderBy('sort_order')->get();
            foreach ($remaining as $idx => $m) {
                $m->update(['sort_order' => $idx + 1]);
            }

            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    /**
     * Process a CSV image mapping file against uploaded image files.
     *
     * @param array<string, UploadedFile> $uploadedFiles Indexed by original filename
     * @return array{mapped_count: int, failed_count: int, errors: array<string>}
     */
    public function processCsvMapping(Seller $seller, UploadedFile $csvFile, array $uploadedFiles): array
    {
        $handle = fopen($csvFile->getRealPath(), 'r');
        if (!$handle) {
            return ['mapped_count' => 0, 'failed_count' => 0, 'errors' => ['Unable to read mapping CSV file.']];
        }

        $headers = fgetcsv($handle) ?: [];
        if (!empty($headers[0])) {
            $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        }

        $mappedCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowNum = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($data)) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $idx => $h) {
                $row[strtolower(trim((string) $h))] = $data[$idx] ?? '';
            }

            $filename = trim((string) ($row['image_filename'] ?? $row['filename'] ?? $row['image'] ?? ''));
            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            $productId = !empty($row['product_id']) ? (int) $row['product_id'] : null;
            $sortOrder = isset($row['sort_order']) && $row['sort_order'] !== '' ? (int) $row['sort_order'] : null;
            $isPrimary = !empty($row['is_primary']) && in_array(strtolower((string) $row['is_primary']), ['1', 'true', 'yes', 'y'], true);

            if (empty($filename)) {
                $errors[] = "Row {$rowNum}: 'image_filename' is empty.";
                $failedCount++;
                continue;
            }

            // Find matching uploaded file
            $matchedFile = null;
            foreach ($uploadedFiles as $origName => $uFile) {
                if (strtolower($origName) === strtolower($filename)) {
                    $matchedFile = $uFile;
                    break;
                }
            }

            if (!$matchedFile) {
                $errors[] = "Row {$rowNum}: Image file '{$filename}' was not found in the uploaded batch.";
                $failedCount++;
                continue;
            }

            // Resolve product
            $product = null;
            $variant = null;

            if (!empty($sku)) {
                $variant = ProductVariant::where('sku', $sku)
                    ->whereHas('product', fn ($q) => $q->where('seller_id', $seller->id))
                    ->with('product')
                    ->first();
                $product = $variant?->product;
            }

            if (!$product && $productId) {
                $product = Product::where('seller_id', $seller->id)->find($productId);
            }

            if (!$product) {
                $errors[] = "Row {$rowNum}: Could not find product belonging to you with SKU '{$sku}' or ID '{$productId}'.";
                $failedCount++;
                continue;
            }

            try {
                $createdMedia = $this->storeImageForProduct(
                    $product,
                    $matchedFile,
                    $variant,
                    $isPrimary ? 1 : $sortOrder
                );

                if ($isPrimary) {
                    $this->setPrimaryImage($createdMedia);
                }

                $mappedCount++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum} ({$filename}): Failed to store image: " . $e->getMessage();
                $failedCount++;
            }
        }

        fclose($handle);

        return [
            'mapped_count' => $mappedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Generate standard CSV mapping template.
     */
    public function generateMappingTemplateCsv(): string
    {
        $headers = ['sku', 'product_id', 'image_filename', 'sort_order', 'is_primary'];
        $samples = [
            ['ALLZ-TV-55-4K', '', 'tv_front.jpg', '1', 'yes'],
            ['ALLZ-TV-55-4K', '', 'tv_back_ports.jpg', '2', 'no'],
            ['ALLZ-HEADPHONE-BT', '', 'headphone_angled.png', '1', 'yes'],
        ];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($handle, $headers);
        foreach ($samples as $sample) {
            fputcsv($handle, $sample);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }
}
