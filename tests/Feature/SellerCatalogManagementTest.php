<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryLocationType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\CatalogImport;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use App\Services\Integration\CatalogImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Category $category;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->sellerUser = User::factory()->create([
            'email' => 'seller.catalog@allzone.test',
        ]);

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Electronics Store',
            'status' => 'approved',
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Electronics Store',
            'slug' => 'apex-electronics-store',
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Smart TVs & Home Entertainment',
            'slug' => 'smart-tvs-home-entertainment',
            'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create([
            'name' => 'Sony Electronics',
            'slug' => 'sony-electronics',
            'seller_id' => $this->seller->id,
            'is_verified' => true,
        ]);
    }

    public function test_seller_can_view_catalog_overview_with_actual_kpis_and_tabs(): void
    {
        // Create actual product records with different statuses
        $activeProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Sony 55 Inch 4K Smart TV',
            'status' => ProductStatus::ACTIVE,
        ]);
        ProductVariant::factory()->create(['product_id' => $activeProd->id]);

        $pendingProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Sony Soundbar 300W',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);
        ProductVariant::factory()->create(['product_id' => $pendingProd->id]);

        $rejectedProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Defective Bluetooth Speaker',
            'status' => ProductStatus::REJECTED,
            'rejection_reason' => 'Missing Bureau of Indian Standards (BIS) certificate number.',
        ]);
        ProductVariant::factory()->create(['product_id' => $rejectedProd->id]);

        $draftProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Draft Wireless Earbuds',
            'status' => ProductStatus::DRAFT,
        ]);
        ProductVariant::factory()->create(['product_id' => $draftProd->id]);

        // Bulk batch record
        CatalogImport::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'file_name' => 'tvs_batch_nov.csv',
            'file_path' => 'catalog_imports/tvs_batch_nov.csv',
            'type' => 'products',
            'status' => 'completed',
            'total_rows' => 10,
            'imported_rows' => 10,
            'failed_rows' => 0,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.create', ['tab' => 'overview']));

        $response->assertStatus(200);
        $response->assertViewHas('totalProducts', 4);
        $response->assertViewHas('activeCount', 1);
        $response->assertViewHas('pendingCount', 1);
        $response->assertViewHas('rejectedCount', 1);
        $response->assertViewHas('draftCount', 1);
        $response->assertViewHas('bulkUploadsCount', 1);

        $response->assertSee('Sony 55 Inch 4K Smart TV');
        $response->assertSee('QC Pass');
        $response->assertSee('In Review');
        $response->assertSee('QC Error');
    }

    public function test_multivendor_tenant_isolation_on_catalog_uploads(): void
    {
        $otherUser = User::factory()->create();
        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Competitor Electronics',
            'status' => 'approved',
        ]);
        $otherStore = Store::factory()->create(['seller_id' => $otherSeller->id]);

        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'category_id' => $this->category->id,
            'title' => 'Competitor Secret TV Model X',
            'status' => ProductStatus::ACTIVE,
        ]);

        $otherImport = CatalogImport::create([
            'seller_id' => $otherSeller->id,
            'store_id' => $otherStore->id,
            'file_name' => 'competitor_catalog.csv',
            'file_path' => 'catalog_imports/competitor_catalog.csv',
            'type' => 'products',
            'status' => 'completed',
            'total_rows' => 50,
            'imported_rows' => 50,
            'failed_rows' => 0,
        ]);

        // Seller views catalog overview
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.create', ['tab' => 'overview']));

        $response->assertStatus(200);
        $response->assertDontSee('Competitor Secret TV Model X');
        $response->assertDontSee('competitor_catalog.csv');

        // Cannot download other seller's error report
        $errorReportResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.bulk_errors', $otherImport->id));
        $errorReportResponse->assertStatus(403);

        // Cannot confirm other seller's import
        $confirmResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.bulk_confirm', $otherImport->id));
        $confirmResponse->assertStatus(403);
    }

    public function test_catalog_history_filtering_by_status(): void
    {
        $activeProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Active Soundbar System',
            'status' => ProductStatus::ACTIVE,
        ]);
        $rejectedProd = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Rejected Projector Device',
            'status' => ProductStatus::REJECTED,
        ]);

        // Query active filter
        $resActive = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.create', ['tab' => 'overview', 'filter' => 'active']));
        $resActive->assertStatus(200);
        $resActive->assertSee('Active Soundbar System');
        $resActive->assertDontSee('Rejected Projector Device');

        // Query rejected filter
        $resRejected = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.create', ['tab' => 'overview', 'filter' => 'rejected']));
        $resRejected->assertStatus(200);
        $resRejected->assertSee('Rejected Projector Device');
        $resRejected->assertDontSee('Active Soundbar System');
    }

    public function test_seller_can_create_single_product_and_submit_for_review(): void
    {
        $payload = [
            'title' => 'Samsung 65 Inch Crystal 4K UHD Smart TV',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'product_type' => 'simple',
            'short_desc' => 'Crystal processor 4K, HDR10+, OTS Lite sound',
            'description' => '<p>Ultra-vivid colors and sleek AirSlim design.</p>',
            'hsn_code' => '8528',
            'tax_percent' => 18.00,
            'return_window_days' => 7,
            'is_returnable' => 1,
            'is_cod_allowed' => 1,
            'simple_sku' => 'SAM-65-CRYSTAL-4K',
            'simple_barcode' => '8901234567800',
            'simple_mrp' => 69999.00,
            'simple_selling_price' => 54999.00,
            'simple_cost_price' => 48000.00,
            'simple_stock' => 12,
            'simple_low_stock_threshold' => 3,
            'simple_weight_grams' => 16500,
            'simple_length' => 145.0,
            'simple_width' => 18.0,
            'simple_height' => 89.0,
            'submit_action' => 'submit_review',
        ];

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.store'), $payload);

        $response->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'seller_id' => $this->seller->id,
            'title' => 'Samsung 65 Inch Crystal 4K UHD Smart TV',
            'status' => ProductStatus::PENDING_REVIEW->value,
            'hsn_code' => '8528',
            'tax_percent' => 18.00,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'SAM-65-CRYSTAL-4K',
            'mrp' => 69999.00,
            'selling_price' => 54999.00,
            'weight_grams' => 16500,
        ]);

        $variant = ProductVariant::where('sku', 'SAM-65-CRYSTAL-4K')->first();
        $this->assertNotNull($variant);
        $this->assertDatabaseHas('inventory', [
            'variant_id' => $variant->id,
            'quantity_on_hand' => 12,
        ]);
    }

    public function test_single_product_validation_prevents_selling_price_exceeding_mrp(): void
    {
        $payload = [
            'title' => 'LG OLED 55 Inch TV 4K',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'product_type' => 'simple',
            'simple_sku' => 'LG-55-OLED-INV',
            'simple_mrp' => 50000.00,
            'simple_selling_price' => 60000.00, // Invalid: exceeds MRP
            'submit_action' => 'save_draft',
            'tax_percent' => 18.00,
            'return_window_days' => 7,
        ];

        $response = $this->actingAs($this->sellerUser)
            ->from(route('seller.products.create'))
            ->post(route('seller.products.store'), $payload);

        $response->assertSessionHasErrors('simple_selling_price');
        $this->assertDatabaseMissing('products', ['title' => 'LG OLED 55 Inch TV 4K']);
    }

    public function test_seller_can_download_bulk_csv_template(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.bulk_template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('sku,title,category,brand,selling_price,mrp', $content);
        $this->assertStringContainsString('weight_grams,length,width,height', $content);
        $this->assertStringContainsString('Ultra HD 4K Smart LED TV', $content);
    }

    public function test_bulk_csv_upload_performs_dry_run_validation_and_catches_row_errors(): void
    {
        // Prepare CSV with 1 valid row and 2 invalid rows
        $csvContent = "\xEF\xBB\xBF" .
            "sku,title,category,brand,selling_price,mrp,quantity,weight_grams,length,width,height,description\n" .
            "VALID-TV-01,Valid Smart 4K LED TV 43 Inch,Smart TVs,Sony,29999.00,39999.00,10,8500,98,12,60,Superb display\n" .
            "INVALID-PRICE-02,Faulty Price Smart TV,Smart TVs,Sony,50000.00,40000.00,5,8000,90,10,50,MRP less than price\n" . // MRP < selling_price
            "INVALID-NON-ELEC-03,Cotton Casual Shirt for Men,Fashion,Generic,999.00,1999.00,20,300,30,25,2,Non electronics item\n"; // Prohibited non-electronics

        $file = UploadedFile::fake()->createWithContent('catalog_test.csv', $csvContent);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.bulk_preview'), [
                'file' => $file,
            ]);

        $response->assertRedirect();

        $import = CatalogImport::where('seller_id', $this->seller->id)->latest()->first();
        $this->assertNotNull($import);
        $this->assertEquals(3, $import->total_rows);
        $this->assertEquals(2, $import->failed_rows);
        $this->assertCount(2, $import->errors);

        // Check error messages
        $errorFields = collect($import->errors)->pluck('field')->toArray();
        $this->assertContains('mrp', $errorFields);
        $this->assertContains('title', $errorFields);
    }

    public function test_seller_can_download_bulk_import_error_report(): void
    {
        $import = CatalogImport::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'file_name' => 'test_failed_catalog.csv',
            'file_path' => 'catalog_imports/test_failed_catalog.csv',
            'type' => 'products',
            'status' => 'pending',
            'total_rows' => 2,
            'imported_rows' => 0,
            'failed_rows' => 1,
            'errors' => [
                [
                    'row' => 2,
                    'sku' => 'ERR-SKU-99',
                    'title' => 'Broken Price TV',
                    'field' => 'selling_price',
                    'message' => 'Selling price must be greater than zero.',
                ]
            ],
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.products.bulk_errors', $import->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Row Number', $content);
        $this->assertStringContainsString('Error Reason', $content);
        $this->assertStringContainsString('ERR-SKU-99', $content);
        $this->assertStringContainsString('Selling price must be greater than zero.', $content);
    }

    public function test_seller_can_confirm_import_and_commits_records_to_database(): void
    {
        $csvContent = "\xEF\xBB\xBF" .
            "sku,title,category,brand,selling_price,mrp,quantity,weight_grams,length,width,height,description\n" .
            "IMPORT-TV-43,Allzone 43 Inch Full HD Smart TV,Smart TVs,Sony,21999.00,28999.00,15,7500,95,12,58,Crisp display with stereo sound\n";

        $storedPath = 'catalog_imports/valid_import.csv';
        Storage::disk('public')->put($storedPath, $csvContent);

        $import = CatalogImport::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'file_name' => 'valid_import.csv',
            'file_path' => $storedPath,
            'type' => 'products',
            'status' => 'pending',
            'total_rows' => 1,
            'imported_rows' => 0,
            'failed_rows' => 0,
            'column_mapping' => [
                'sku' => 'sku',
                'title' => 'title',
                'category' => 'category',
                'brand' => 'brand',
                'selling_price' => 'selling_price',
                'mrp' => 'mrp',
                'quantity' => 'quantity',
                'weight_grams' => 'weight_grams',
                'length' => 'length',
                'width' => 'width',
                'height' => 'height',
                'description' => 'description',
            ],
            'errors' => [],
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.bulk_confirm', $import->id));

        $response->assertRedirect(route('seller.products.create', ['tab' => 'overview']));

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->imported_rows);

        $this->assertDatabaseHas('products', [
            'seller_id' => $this->seller->id,
            'title' => 'Allzone 43 Inch Full HD Smart TV',
            'status' => ProductStatus::PENDING_REVIEW->value,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'IMPORT-TV-43',
            'selling_price' => 21999.00,
            'mrp' => 28999.00,
            'weight_grams' => 7500,
        ]);

        $variant = ProductVariant::where('sku', 'IMPORT-TV-43')->first();
        $this->assertDatabaseHas('inventory', [
            'variant_id' => $variant->id,
            'quantity_on_hand' => 15,
        ]);

        // Duplicate confirm is prevented
        $duplicateResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.bulk_confirm', $import->id));
        $duplicateResponse->assertSessionHas('error');
    }

    public function test_seller_can_save_product_as_draft(): void
    {
        $payload = [
            'title' => 'OnePlus Nord Buds 2 Wireless Earphones',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'product_type' => 'simple',
            'simple_sku' => 'OP-NORD-BUDS-2',
            'simple_mrp' => 3299.00,
            'simple_selling_price' => 2799.00,
            'simple_stock' => 25,
            'simple_weight_grams' => 200,
            'simple_length' => 10.0,
            'simple_width' => 10.0,
            'simple_height' => 5.0,
            'tax_percent' => 18.00,
            'return_window_days' => 7,
            'submit_action' => 'save_draft',
        ];

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.products.store'), $payload);

        $response->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'seller_id' => $this->seller->id,
            'title' => 'OnePlus Nord Buds 2 Wireless Earphones',
            'status' => ProductStatus::DRAFT->value,
        ]);
    }

    public function test_single_product_creation_rejects_non_electronics_items(): void
    {
        $payload = [
            'title' => 'Organic Fresh Cow Milk 1L Bottle',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'product_type' => 'simple',
            'simple_sku' => 'MILK-COW-1L',
            'simple_mrp' => 80.00,
            'simple_selling_price' => 75.00,
            'tax_percent' => 0.00,
            'return_window_days' => 0,
            'submit_action' => 'save_draft',
        ];

        $response = $this->actingAs($this->sellerUser)
            ->from(route('seller.products.create'))
            ->post(route('seller.products.store'), $payload);

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseMissing('products', ['title' => 'Organic Fresh Cow Milk 1L Bottle']);
    }

    public function test_admin_can_approve_or_reject_submitted_catalog_product_with_reason(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Realme TechLife Smart Air Fryer 3.8L',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id]);

        // Admin rejects product
        $rejectResponse = $this->actingAs($admin)
            ->post(route('admin.catalogue.moderation.reject', $product->id), [
                'reason' => 'Energy Star rating label photo is missing from gallery.',
            ]);

        $rejectResponse->assertRedirect();
        $product->refresh();
        $this->assertEquals(ProductStatus::REJECTED, $product->status);
        $this->assertEquals('Energy Star rating label photo is missing from gallery.', $product->rejection_reason);

        // Admin approves after re-review
        $approveResponse = $this->actingAs($admin)
            ->post(route('admin.catalogue.moderation.approve', $product->id));

        $approveResponse->assertRedirect();
        $product->refresh();
        $this->assertEquals(ProductStatus::ACTIVE, $product->status);
        $this->assertNull($product->rejection_reason);
    }
}
