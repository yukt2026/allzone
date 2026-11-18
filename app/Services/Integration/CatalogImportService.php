<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Enums\InventoryLocationType;
use App\Enums\ProductStatus;
use App\Models\CatalogImport;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogImportService
{
    /**
     * Generate standard CSV template string with valid Electronics sample products.
     */
    public function generateTemplateCsv(): string
    {
        $headers = [
            'sku', 'title', 'category', 'brand', 'selling_price', 'mrp', 
            'quantity', 'weight_grams', 'length', 'width', 'height', 
            'hsn_code', 'tax_percent', 'barcode', 'description'
        ];
        
        $sampleRows = [
            [
                'ALLZ-TV-55-4K', 
                'Ultra HD 4K Smart LED TV 55 Inch', 
                'Smart TVs & Displays', 
                'Sony', 
                '44999.00', 
                '59999.00', 
                '15', 
                '14500', 
                '124', 
                '15', 
                '78', 
                '8528', 
                '18.00', 
                '8901234567890', 
                '4K HDR smart television with Dolby Atmos audio and multiple HDMI ports'
            ],
            [
                'ALLZ-HEADPHONE-BT', 
                'Wireless Noise Cancelling Over-Ear Headphones', 
                'Audio & Headphones', 
                'boAt', 
                '2499.00', 
                '3999.00', 
                '40', 
                '320', 
                '20', 
                '18', 
                '8', 
                '8518', 
                '18.00', 
                '8901234567891', 
                'Bluetooth wireless headphones with active noise cancellation and 30-hour battery life'
            ],
        ];

        $handle = fopen('php://temp', 'r+');
        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($sampleRows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    /**
     * Prohibited non-electronics keywords as per ALLZONE exclusive catalog rules.
     */
    protected const PROHIBITED_KEYWORDS = [
        'grocery', 'groceries', 'atta', 'flour', 'rice', 'dal', 'dals', 'pulses',
        'edible oil', 'ghee', 'mustard oil', 'sunflower oil', 'spices', 'masala',
        'vegetable', 'vegetables', 'fruit', 'fruits', 'greens',
        'milk', 'curd', 'butter', 'cheese', 'paneer', 'bread', 'bakery', 'farm eggs',
        'chips', 'namkeen', 'biscuit', 'biscuits', 'cookie', 'cookies', 'tea', 'coffee',
        'honey', 'sugar', 'salt', 'chocolate', 'chocolates', 'sweets', 'snack', 'snacks', 'food',
        'shampoo', 'body wash', 'soap', 'soaps', 'toothpaste', 'oral care',
        'lipstick', 'lipsticks', 'makeup', 'cosmetics', 'nail polish',
        'shirt', 't-shirt', 'saree', 'trousers', 'jeans', 'clothing', 'apparel', 'dress',
        'diaper', 'diapers', 'baby food', 'baby wipes', 'baby care',
        'chyawanprash', 'ayurveda', 'herbal supplement', 'immunity booster',
        'notebook', 'notebooks', 'stationery', 'ball pen', 'pencils',
    ];

    /**
     * Handle uploaded CSV file, extract headers, run dry-run row validation, and generate preview.
     *
     * @return array{import: CatalogImport, headers: array<string>, preview_rows: array<array>, errors: array<array>, auto_mapping: array<string, string>, total_rows: int, valid_count: int, failed_count: int}
     */
    public function uploadAndPreview(Store $store, UploadedFile $file): array
    {
        $path = $file->store('catalog_imports', 'public');

        $headers = [];
        $validRows = [];
        $errors = [];
        $totalRows = 0;
        $seenSkus = [];

        $handle = $this->openStream($path);
        if ($handle !== false) {
            $headers = fgetcsv($handle) ?: [];
            // Clean BOM from first header if present
            if (!empty($headers[0])) {
                $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
                $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
            }

            // Auto column mapping detection
            $autoMapping = $this->detectColumnMapping($headers);
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (count(array_filter($data)) === 0) {
                    continue;
                }

                $totalRows++;
                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = $data[$idx] ?? '';
                }

                // Extract fields
                $sku = strtoupper(trim((string) ($this->getMappedValue($row, $autoMapping, 'sku') ?: '')));
                $title = trim((string) ($this->getMappedValue($row, $autoMapping, 'title') ?: ''));
                $categoryName = trim((string) ($this->getMappedValue($row, $autoMapping, 'category') ?: ''));
                $brandName = trim((string) ($this->getMappedValue($row, $autoMapping, 'brand') ?: ''));
                $sellingPriceStr = $this->getMappedValue($row, $autoMapping, 'selling_price');
                $mrpStr = $this->getMappedValue($row, $autoMapping, 'mrp');
                $quantityStr = $this->getMappedValue($row, $autoMapping, 'quantity');
                $weightStr = $this->getMappedValue($row, $autoMapping, 'weight_grams');
                $desc = trim((string) ($this->getMappedValue($row, $autoMapping, 'description') ?: ''));

                $rowHasError = false;

                // Validate Title
                if (empty($title)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku ?: 'N/A',
                        'title' => 'Missing Title',
                        'field' => 'title',
                        'message' => 'Product title is required and cannot be empty.'
                    ];
                    $rowHasError = true;
                } else {
                    // Check prohibited non-electronics products
                    $textToCheck = strtolower($title . ' ' . $desc . ' ' . $categoryName);
                    foreach (self::PROHIBITED_KEYWORDS as $prohibited) {
                        if (preg_match('/\b' . preg_quote($prohibited, '/') . '\b/i', $textToCheck)) {
                            $errors[] = [
                                'row' => $rowNumber,
                                'sku' => $sku ?: 'N/A',
                                'title' => $title,
                                'field' => 'title',
                                'message' => "ALLZONE is an exclusive Electronics & Appliances marketplace. Item '{$prohibited}' is prohibited."
                            ];
                            $rowHasError = true;
                            break;
                        }
                    }
                }

                // Validate SKU
                if (empty($sku)) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => 'N/A',
                        'title' => $title,
                        'field' => 'sku',
                        'message' => 'SKU is required for each catalog item.'
                    ];
                    $rowHasError = true;
                } elseif (isset($seenSkus[$sku])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku,
                        'title' => $title,
                        'field' => 'sku',
                        'message' => "Duplicate SKU '{$sku}' found in line {$rowNumber} (previously seen in line {$seenSkus[$sku]})."
                    ];
                    $rowHasError = true;
                } else {
                    $seenSkus[$sku] = $rowNumber;
                    // Check existing SKU in DB
                    $existingVariant = ProductVariant::where('sku', $sku)->with('product')->first();
                    if ($existingVariant && $existingVariant->product && $existingVariant->product->seller_id !== $store->seller_id) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $sku,
                            'title' => $title,
                            'field' => 'sku',
                            'message' => "SKU '{$sku}' is already registered by another seller."
                        ];
                        $rowHasError = true;
                    }
                }

                // Validate Selling Price
                $sellingPrice = (float) $sellingPriceStr;
                if (!is_numeric($sellingPriceStr) || $sellingPrice <= 0) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku ?: 'N/A',
                        'title' => $title,
                        'field' => 'selling_price',
                        'message' => 'Selling price must be a valid positive number greater than 0.'
                    ];
                    $rowHasError = true;
                }

                // Validate MRP
                $mrp = (float) $mrpStr;
                if (!is_numeric($mrpStr) || $mrp <= 0) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku ?: 'N/A',
                        'title' => $title,
                        'field' => 'mrp',
                        'message' => 'MRP must be a valid positive number.'
                    ];
                    $rowHasError = true;
                } elseif ($mrp < $sellingPrice) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'sku' => $sku ?: 'N/A',
                        'title' => $title,
                        'field' => 'mrp',
                        'message' => "MRP ({$mrp}) cannot be less than selling price ({$sellingPrice})."
                    ];
                    $rowHasError = true;
                }

                // Validate Stock / Quantity
                if ($quantityStr !== null && $quantityStr !== '') {
                    $qty = (int) $quantityStr;
                    if (!is_numeric($quantityStr) || $qty < 0) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $sku ?: 'N/A',
                            'title' => $title,
                            'field' => 'quantity',
                            'message' => 'Stock quantity must be zero or a positive integer.'
                        ];
                        $rowHasError = true;
                    }
                }

                // Validate Weight
                if ($weightStr !== null && $weightStr !== '') {
                    $weight = (int) $weightStr;
                    if (!is_numeric($weightStr) || $weight < 0) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'sku' => $sku ?: 'N/A',
                            'title' => $title,
                            'field' => 'weight_grams',
                            'message' => 'Package weight must be a positive integer in grams.'
                        ];
                        $rowHasError = true;
                    }
                }

                if (!$rowHasError) {
                    $validRows[] = array_merge($row, [
                        '_sku' => $sku,
                        '_title' => $title,
                        '_category' => $categoryName ?: 'Electronics & Appliances',
                        '_brand' => $brandName ?: null,
                        '_selling_price' => $sellingPrice,
                        '_mrp' => $mrp,
                        '_quantity' => (int) ($quantityStr ?: 0),
                        '_weight_grams' => (int) ($weightStr ?: 500),
                        '_length' => (float) ($this->getMappedValue($row, $autoMapping, 'length') ?: 10),
                        '_width' => (float) ($this->getMappedValue($row, $autoMapping, 'width') ?: 10),
                        '_height' => (float) ($this->getMappedValue($row, $autoMapping, 'height') ?: 5),
                        '_hsn_code' => (string) ($this->getMappedValue($row, $autoMapping, 'hsn_code') ?: '8517'),
                        '_tax_percent' => (float) ($this->getMappedValue($row, $autoMapping, 'tax_percent') ?: 18.00),
                        '_description' => $desc,
                    ]);
                }
            }
            fclose($handle);
        } else {
            $autoMapping = [];
        }

        $catalogImport = CatalogImport::create([
            'seller_id' => $store->seller_id,
            'store_id' => $store->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'type' => 'products',
            'status' => 'pending',
            'total_rows' => $totalRows,
            'imported_rows' => 0,
            'failed_rows' => count($errors),
            'column_mapping' => $autoMapping,
            'errors' => $errors,
        ]);

        return [
            'import' => $catalogImport,
            'headers' => $headers,
            'preview_rows' => array_slice($validRows, 0, 10),
            'errors' => $errors,
            'auto_mapping' => $autoMapping,
            'total_rows' => $totalRows,
            'valid_count' => count($validRows),
            'failed_count' => count($errors),
        ];
    }

    /**
     * Confirm and execute catalog import from file inside transaction.
     *
     * @return array{success: bool, imported: int, failed: int, message: string}
     */
    public function confirmImport(CatalogImport $import): array
    {
        if ($import->isCompleted()) {
            return [
                'success' => false,
                'imported' => $import->imported_rows,
                'failed' => $import->failed_rows,
                'message' => 'This catalog batch has already been processed and imported.'
            ];
        }

        $handle = $this->openStream($import->file_path);
        if (!$handle) {
            $import->update(['status' => 'failed', 'errors' => [['message' => 'Import file could not be read from storage.']]]);
            return ['success' => false, 'imported' => 0, 'failed' => 0, 'message' => 'Import file not found.'];
        }

        $import->update(['status' => 'processing']);

        $mapping = $import->column_mapping ?: [];
        $seller = $import->seller;
        $store = $import->store;

        $inventoryLocation = InventoryLocation::firstOrCreate(
            ['store_id' => $store->id, 'seller_id' => $seller->id],
            [
                'name' => "{$store->name} Stock",
                'code' => 'LOC-' . strtoupper(Str::random(6)),
                'type' => InventoryLocationType::STORE,
                'is_active' => true,
            ]
        );

        // Check if admin configured auto-approval
        $autoApprove = (bool) \App\Models\Setting::get('product_auto_approval', false);
        $initialStatus = $autoApprove ? ProductStatus::ACTIVE : ProductStatus::PENDING_REVIEW;

        $headers = fgetcsv($handle) ?: [];
        if (!empty($headers[0])) {
            $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
            $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF");
        }

        // Identify row numbers that had errors to skip them
        $errorRows = collect($import->errors ?? [])->pluck('row')->flip()->toArray();

        $importedCount = 0;
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (count(array_filter($data)) === 0 || isset($errorRows[$rowNumber])) {
                    continue;
                }

                $row = [];
                foreach ($headers as $idx => $header) {
                    $row[$header] = $data[$idx] ?? '';
                }

                $sku = strtoupper(trim((string) ($this->getMappedValue($row, $mapping, 'sku') ?: '')));
                $title = trim((string) ($this->getMappedValue($row, $mapping, 'title') ?: ''));
                $categoryName = trim((string) ($this->getMappedValue($row, $mapping, 'category') ?: 'Electronics & Appliances'));
                $brandName = trim((string) ($this->getMappedValue($row, $mapping, 'brand') ?: ''));
                $sellingPrice = (float) ($this->getMappedValue($row, $mapping, 'selling_price') ?: 0.0);
                $mrp = (float) ($this->getMappedValue($row, $mapping, 'mrp') ?: $sellingPrice);
                $qty = (int) ($this->getMappedValue($row, $mapping, 'quantity') ?: 0);
                $weight = (int) ($this->getMappedValue($row, $mapping, 'weight_grams') ?: 500);
                $length = (float) ($this->getMappedValue($row, $mapping, 'length') ?: 10);
                $width = (float) ($this->getMappedValue($row, $mapping, 'width') ?: 10);
                $height = (float) ($this->getMappedValue($row, $mapping, 'height') ?: 5);
                $hsn = (string) ($this->getMappedValue($row, $mapping, 'hsn_code') ?: '8517');
                $taxPercent = (float) ($this->getMappedValue($row, $mapping, 'tax_percent') ?: 18.00);
                $desc = (string) ($this->getMappedValue($row, $mapping, 'description') ?: '');

                if (empty($title) || $sellingPrice <= 0 || empty($sku)) {
                    continue;
                }

                // Resolve Category
                $category = Category::where('name', $categoryName)->first();
                if (!$category) {
                    $category = Category::where('slug', Str::slug($categoryName))->first()
                        ?: Category::where('is_active', true)->first();
                }

                // Resolve Brand if given
                $brandId = null;
                if (!empty($brandName)) {
                    $brand = \App\Models\Brand::firstOrCreate(
                        ['name' => $brandName],
                        ['slug' => Str::slug($brandName), 'seller_id' => $seller->id, 'is_verified' => false]
                    );
                    $brandId = $brand->id;
                }

                // Product slug
                $productSlug = Str::slug($title);
                if (Product::where('slug', $productSlug)->exists()) {
                    $productSlug .= '-' . Str::random(4);
                }

                $product = Product::updateOrCreate(
                    ['seller_id' => $seller->id, 'title' => $title],
                    [
                        'category_id' => $category?->id ?: 1,
                        'brand_id' => $brandId,
                        'slug' => $productSlug,
                        'description' => $desc,
                        'status' => $initialStatus,
                        'product_type' => \App\Enums\ProductType::SIMPLE,
                        'hsn_code' => $hsn,
                        'tax_percent' => $taxPercent,
                        'is_returnable' => true,
                        'is_cod_allowed' => true,
                        'return_window_days' => 7,
                    ]
                );

                // Variant
                $variant = ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'sku' => $sku],
                    [
                        'name' => $product->title,
                        'selling_price' => $sellingPrice,
                        'mrp' => $mrp,
                        'weight_grams' => $weight,
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                        'is_default' => true,
                        'is_active' => true,
                    ]
                );

                // Inventory & Ledger
                Inventory::updateOrCreate(
                    [
                        'variant_id' => $variant->id,
                        'location_id' => $inventoryLocation->id,
                    ],
                    [
                        'quantity_on_hand' => $qty,
                        'low_stock_threshold' => 5,
                    ]
                );

                if ($qty > 0) {
                    \App\Models\StockMovement::create([
                        'inventory_id' => Inventory::where('variant_id', $variant->id)->where('location_id', $inventoryLocation->id)->value('id'),
                        'type' => \App\Enums\StockMovementType::RESTOCK,
                        'quantity' => $qty,
                        'balance_after' => $qty,
                        'reason' => 'bulk_catalog_import',
                        'note' => "Initial stock import from batch #{$import->id} ({$import->file_name})",
                        'user_id' => $seller->user_id ?? null,
                    ]);
                }

                $importedCount++;
            }

            fclose($handle);

            $import->update([
                'status' => 'completed',
                'imported_rows' => $importedCount,
            ]);

            DB::commit();

            return [
                'success' => true,
                'imported' => $importedCount,
                'failed' => $import->failed_rows,
                'message' => "Successfully imported {$importedCount} catalog product(s) into ALLZONE."
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            Log::error("Bulk import failed for import #{$import->id}: " . $e->getMessage());
            $import->update(['status' => 'failed']);

            return [
                'success' => false,
                'imported' => 0,
                'failed' => $import->failed_rows,
                'message' => 'Import failed due to database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate CSV content for row-level import errors report.
     */
    public function generateErrorReportCsv(CatalogImport $import): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($handle, ['Row Number', 'SKU', 'Product Title', 'Field', 'Error Reason']);

        foreach ($import->errors ?? [] as $err) {
            fputcsv($handle, [
                $err['row'] ?? 'N/A',
                $err['sku'] ?? 'N/A',
                $err['title'] ?? 'N/A',
                $err['field'] ?? 'N/A',
                $err['message'] ?? ($err['error'] ?? 'Unknown error'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    /**
     * Helper to open stream from disk with fallback.
     */
    protected function openStream(string $path)
    {
        if (Storage::exists($path)) {
            $stream = Storage::readStream($path);
            if ($stream) {
                return $stream;
            }
        }

        $fullPath = Storage::path($path);
        if (file_exists($fullPath)) {
            return fopen($fullPath, 'r');
        }

        return false;
    }

    /**
     * Helper to auto-map CSV column names to standard fields.
     */
    protected function detectColumnMapping(array $headers): array
    {
        $mapping = [];
        $candidates = [
            'sku' => ['sku', 'item_code', 'code', 'product_sku', 'barcode'],
            'title' => ['title', 'name', 'product_name', 'item_name', 'product'],
            'category' => ['category', 'category_name', 'cat', 'department'],
            'selling_price' => ['selling_price', 'price', 'sale_price', 'unit_price', 'rate'],
            'mrp' => ['mrp', 'maximum_retail_price', 'retail_price', 'list_price'],
            'quantity' => ['quantity', 'qty', 'stock', 'stock_quantity', 'inventory', 'balance'],
            'weight_grams' => ['weight_grams', 'weight', 'weight_g', 'grams'],
            'brand' => ['brand', 'brand_name', 'make', 'manufacturer'],
            'length' => ['length', 'len', 'l'],
            'width' => ['width', 'w'],
            'height' => ['height', 'h'],
            'hsn_code' => ['hsn_code', 'hsn', 'hsncode'],
            'tax_percent' => ['tax_percent', 'tax', 'gst', 'gst_rate', 'tax_rate'],
            'barcode' => ['barcode', 'ean', 'upc', 'isbn'],
            'description' => ['description', 'desc', 'short_description', 'details'],
        ];

        foreach ($candidates as $field => $matches) {
            foreach ($headers as $header) {
                $clean = strtolower(trim(str_replace([' ', '-'], '_', (string) $header)));
                if ($clean === $field || in_array($clean, $matches, true)) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    protected function getMappedValue(array $row, array $mapping, string $field): mixed
    {
        $mappedHeader = $mapping[$field] ?? $field;
        return $row[$mappedHeader] ?? $row[$field] ?? null;
    }
}
