<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\User;
use App\Notifications\ProductApprovedNotification;
use App\Notifications\ProductRejectedNotification;
use App\Notifications\ProductBlockedNotification;
use App\Notifications\ProductCorrectionsRequestedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Administrator with categories.manage, brands.manage, products.approve, products.edit
        $this->adminUser = User::factory()->create([
            'email' => 'catalogue.lead@onestall.in',
            'name' => 'Catalogue Quality Director',
        ]);
        $this->adminUser->assignRole('admin');

        // Support admin (lacks categories.manage, brands.manage, products.approve)
        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->assignRole('support_admin');
    }

    // =========================================================================
    // CATEGORIES TESTS
    // =========================================================================

    public function test_admin_can_view_category_tree(): void
    {
        $root = Category::create([
            'name' => 'Groceries & Gourmet',
            'slug' => 'groceries-gourmet',
            'is_quick_eligible' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $child = Category::create([
            'parent_id' => $root->id,
            'name' => 'Dairy & Eggs',
            'slug' => 'dairy-eggs',
            'is_quick_eligible' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/catalogue/categories');

        $response->assertStatus(200);
        $response->assertSee('Groceries &amp; Gourmet', false);
        $response->assertSee('Dairy &amp; Eggs', false);
        $response->assertSee('10-30m Quick Eligible');
    }

    public function test_admin_can_create_category_with_seo_and_commission(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/catalogue/categories', [
            'name' => 'Personal Care & Beauty',
            'commission_percent' => 12.50,
            'is_quick_eligible' => 1,
            'is_active' => 1,
            'sort_order' => 5,
            'meta_title' => 'Shop Personal Care Online',
            'meta_description' => 'Fast delivery of cosmetics and skin care.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'name' => 'Personal Care & Beauty',
            'slug' => 'personal-care-beauty',
            'commission_percent' => 12.50,
            'is_quick_eligible' => 1,
            'meta_title' => 'Shop Personal Care Online',
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create([
            'name' => 'Old Category Name',
            'slug' => 'old-category',
            'commission_percent' => 5.00,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->adminUser)->put("/admin/catalogue/categories/{$category->id}", [
            'name' => 'Updated Category Name',
            'commission_percent' => 9.00,
            'is_quick_eligible' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $category->refresh();

        $this->assertEquals('Updated Category Name', $category->name);
        $this->assertEquals(9.00, (float) $category->commission_percent);
        $this->assertTrue($category->is_active);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::create([
            'name' => 'Category To Delete',
            'slug' => 'to-delete',
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/admin/catalogue/categories/{$category->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_can_reorder_categories(): void
    {
        $cat1 = Category::create(['name' => 'Cat 1', 'slug' => 'cat-1', 'sort_order' => 10]);
        $cat2 = Category::create(['name' => 'Cat 2', 'slug' => 'cat-2', 'sort_order' => 20]);

        $response = $this->actingAs($this->adminUser)->postJson('/admin/catalogue/categories/reorder', [
            'categories' => [
                ['id' => $cat1->id, 'sort_order' => 1, 'parent_id' => null],
                ['id' => $cat2->id, 'sort_order' => 2, 'parent_id' => null],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(1, $cat1->fresh()->sort_order);
        $this->assertEquals(2, $cat2->fresh()->sort_order);
    }

    // =========================================================================
    // BRANDS TESTS
    // =========================================================================

    public function test_admin_can_view_brands_list_with_filters(): void
    {
        $brand1 = Brand::create(['name' => 'Apple Electronics', 'slug' => 'apple', 'is_verified' => true]);
        $brand2 = Brand::create(['name' => 'Local Artisan Bakery', 'slug' => 'artisan', 'is_verified' => false]);

        $response = $this->actingAs($this->adminUser)->get('/admin/catalogue/brands');
        $response->assertStatus(200);
        $response->assertSee('Apple Electronics');
        $response->assertSee('Local Artisan Bakery');

        // Filter verified only
        $filterResponse = $this->actingAs($this->adminUser)->get('/admin/catalogue/brands?verified=1');
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('Apple Electronics');
        $filterResponse->assertDontSee('Local Artisan Bakery');
    }

    public function test_admin_can_create_and_delete_brand(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->adminUser)->post('/admin/catalogue/brands', [
            'name' => 'Amul Dairy India',
            'is_verified' => 1,
            'seller_id' => '', // verify empty string does not trigger foreign key constraint failure
            'logo_file' => $file,
            'meta_title' => 'Amul Dairy Online',
            'meta_description' => 'Buy Amul fresh milk and dairy products online',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('brands', [
            'name' => 'Amul Dairy India',
            'slug' => 'amul-dairy-india',
            'is_verified' => 1,
            'seller_id' => null,
            'meta_title' => 'Amul Dairy Online',
        ]);

        $brand = Brand::where('slug', 'amul-dairy-india')->first();
        $this->assertNotNull($brand->logo);

        $deleteResponse = $this->actingAs($this->adminUser)->delete("/admin/catalogue/brands/{$brand->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_admin_can_toggle_brand_verification(): void
    {
        $brand = Brand::create(['name' => 'Organic India', 'slug' => 'organic-india', 'is_verified' => false]);

        $response = $this->actingAs($this->adminUser)->post("/admin/catalogue/brands/{$brand->id}/toggle-verify");

        $response->assertRedirect();
        $this->assertTrue($brand->fresh()->is_verified);
    }

    // =========================================================================
    // ATTRIBUTES TESTS
    // =========================================================================

    public function test_admin_can_manage_attributes_and_values(): void
    {
        // 1. Create Attribute
        $createResponse = $this->actingAs($this->adminUser)->post('/admin/catalogue/attributes', [
            'name' => 'Apparel Size',
            'slug' => 'apparel-size',
        ]);
        $createResponse->assertRedirect();
        $attribute = ProductAttribute::where('slug', 'apparel-size')->first();
        $this->assertNotNull($attribute);

        // 2. Add Attribute Value
        $valResponse = $this->actingAs($this->adminUser)->post("/admin/catalogue/attributes/{$attribute->id}/values", [
            'value' => 'Extra Large (XL)',
            'code' => 'XL',
        ]);
        $valResponse->assertRedirect();
        $this->assertDatabaseHas('product_attribute_values', [
            'product_attribute_id' => $attribute->id,
            'value' => 'Extra Large (XL)',
            'code' => 'XL',
        ]);

        // 3. Delete Attribute Value
        $value = ProductAttributeValue::where('value', 'Extra Large (XL)')->first();
        $delValResponse = $this->actingAs($this->adminUser)->delete("/admin/catalogue/attributes/{$attribute->id}/values/{$value->id}");
        $delValResponse->assertRedirect();
        $this->assertDatabaseMissing('product_attribute_values', ['id' => $value->id]);
    }

    // =========================================================================
    // PRODUCT MODERATION TESTS
    // =========================================================================

    public function test_admin_can_view_moderation_queue_and_side_by_side_preview(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Beverages', 'slug' => 'beverages']);
        $brand = Brand::create(['name' => 'Nescafe', 'slug' => 'nescafe']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'title' => 'Nescafe Gold Blend Coffee 200g',
            'slug' => 'nescafe-gold-blend-coffee-200g',
            'status' => ProductStatus::PENDING_REVIEW,
            'product_type' => ProductType::SIMPLE,
            'hsn_code' => '09012190',
            'tax_percent' => 18.00,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'NES-GOLD-200G',
            'name' => '200g Jar',
            'mrp' => 850.00,
            'selling_price' => 749.00,
            'cost_price' => 600.00,
            'is_default' => true,
        ]);

        // Queue list
        $queueResponse = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation');
        $queueResponse->assertStatus(200);
        $queueResponse->assertSee('Nescafe Gold Blend Coffee 200g');
        $queueResponse->assertSee('Pending Review');

        // Side-by-side inspection page
        $showResponse = $this->actingAs($this->adminUser)->get("/admin/catalogue/moderation/{$product->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Nescafe Gold Blend Coffee 200g');
        $showResponse->assertSee('NES-GOLD-200G');
        $showResponse->assertSee('09012190');
        $showResponse->assertSee('749.00');
    }

    public function test_admin_can_approve_product_and_notify_seller(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Snacks', 'slug' => 'snacks']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Roasted Almonds 500g',
            'slug' => 'roasted-almonds-500g',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/catalogue/moderation/{$product->id}/approve");

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals(ProductStatus::ACTIVE, $product->status);
        Notification::assertSentTo($sellerUser, ProductApprovedNotification::class);
    }

    public function test_rejecting_product_requires_mandatory_reason_and_notifies_seller(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Wireless Gaming Headset',
            'slug' => 'wireless-gaming-headset',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        // Blank reason fails
        $failResponse = $this->actingAs($this->adminUser)->post("/admin/catalogue/moderation/{$product->id}/reject", [
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('reason');

        // Valid reason succeeds
        $rejectResponse = $this->actingAs($this->adminUser)->post("/admin/catalogue/moderation/{$product->id}/reject", [
            'reason' => 'Image quality does not meet marketplace 1000x1000px white background guidelines.',
        ]);

        $rejectResponse->assertRedirect();
        $product->refresh();

        $this->assertEquals(ProductStatus::REJECTED, $product->status);
        $this->assertEquals('Image quality does not meet marketplace 1000x1000px white background guidelines.', $product->rejection_reason);
        Notification::assertSentTo($sellerUser, ProductRejectedNotification::class);
    }

    public function test_admin_can_bulk_approve_products(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Fashion', 'slug' => 'fashion']);

        $product1 = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Cotton Shirt Blue', 'slug' => 'shirt-blue', 'status' => ProductStatus::PENDING_REVIEW]);
        $product2 = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Cotton Shirt Red', 'slug' => 'shirt-red', 'status' => ProductStatus::PENDING_REVIEW]);

        $response = $this->actingAs($this->adminUser)->post('/admin/catalogue/moderation/bulk-approve', [
            'product_ids' => [$product1->id, $product2->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals(ProductStatus::ACTIVE, $product1->fresh()->status);
        $this->assertEquals(ProductStatus::ACTIVE, $product2->fresh()->status);
    }

    public function test_admin_can_edit_product_on_behalf(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $cat1 = Category::create(['name' => 'Category One', 'slug' => 'cat-one']);
        $cat2 = Category::create(['name' => 'Category Two', 'slug' => 'cat-two']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $cat1->id,
            'title' => 'Original Incorrect Title',
            'slug' => 'orig-title',
            'hsn_code' => '1111',
            'tax_percent' => 5.00,
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-EDIT-01',
            'name' => 'Default',
            'mrp' => 100.00,
            'selling_price' => 90.00,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->put("/admin/catalogue/moderation/{$product->id}", [
            'title' => 'Corrected Formatted Title',
            'category_id' => $cat2->id,
            'hsn_code' => '85044090',
            'tax_percent' => 18.00,
            'mrp' => 120.00,
            'selling_price' => 99.00,
            'is_quick_capable' => 1,
        ]);

        $response->assertRedirect();
        $product->refresh();
        $variant->refresh();

        $this->assertEquals('Corrected Formatted Title', $product->title);
        $this->assertEquals($cat2->id, $product->category_id);
        $this->assertEquals('85044090', $product->hsn_code);
        $this->assertEquals(18.00, (float) $product->tax_percent);
        $this->assertTrue($product->is_quick_capable);
        $this->assertEquals(120.00, (float) $variant->mrp);
        $this->assertEquals(99.00, (float) $variant->selling_price);
    }

    public function test_unauthorized_user_cannot_approve_product(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Cordless Drill 18V',
            'slug' => 'cordless-drill',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        $response = $this->actingAs($this->unauthorizedUser)->post("/admin/catalogue/moderation/{$product->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals(ProductStatus::PENDING_REVIEW, $product->fresh()->status);
    }

    public function test_admin_can_view_and_manage_catalog_standards(): void
    {
        // 1. View standards page
        $res = $this->actingAs($this->adminUser)->get('/admin/catalogue/standards');
        $res->assertStatus(200);
        $res->assertSee('Units of Measure');
        $res->assertSee('Tax Configuration & GST Slabs', false);
        $res->assertSee('Specification Standards');
        $res->assertSee('Image Requirements & QC', false);

        // 2. Add custom unit of measure
        $unitRes = $this->actingAs($this->adminUser)->post('/admin/catalogue/standards/units', [
            'code' => 'sqm',
            'name' => 'Square Meter',
            'symbol' => 'sq m',
            'category' => 'Area',
            'is_active' => 1,
        ]);
        $unitRes->assertRedirect();

        // Check unit is reflected
        $getRes = $this->actingAs($this->adminUser)->get('/admin/catalogue/standards?tab=units');
        $getRes->assertSee('Square Meter');
        $getRes->assertSee('sqm');

        // 3. Update tax configuration rules
        $taxRes = $this->actingAs($this->adminUser)->post('/admin/catalogue/standards/tax-rules', [
            'default_tax_slab' => 18,
            'hsn_min_digits' => 6,
            'require_hsn_code' => 1,
            'tax_inclusive_pricing' => 1,
        ]);
        $taxRes->assertRedirect();

        // 4. Update image requirements
        $imgRes = $this->actingAs($this->adminUser)->post('/admin/catalogue/standards/image-requirements', [
            'min_width' => 1000,
            'min_height' => 1000,
            'recommended_ratio' => '1:1 (Square)',
            'max_file_size_mb' => 10,
            'min_images' => 1,
            'max_images' => 10,
            'background' => 'Pure white background (#FFFFFF)',
            'prohibited' => 'No watermarks or contact numbers allowed',
        ]);
        $imgRes->assertRedirect();

        // 5. Delete unit
        $delRes = $this->actingAs($this->adminUser)->delete('/admin/catalogue/standards/units/sqm');
        $delRes->assertRedirect();
    }

    public function test_admin_can_filter_moderation_queue_by_all_six_status_tabs_with_accurate_counts(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Apparel & Fashion', 'slug' => 'apparel-fashion']);

        $pPending = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Pending T-Shirt', 'slug' => 'p-pending', 'status' => ProductStatus::PENDING_REVIEW]);
        $pActive = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Active Jeans', 'slug' => 'p-active', 'status' => ProductStatus::ACTIVE]);
        $pRejected = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Rejected Jacket', 'slug' => 'p-rejected', 'status' => ProductStatus::REJECTED]);
        $pBlocked = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Blocked Shoes', 'slug' => 'p-blocked', 'status' => ProductStatus::BLOCKED]);
        $pInactive = Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => 'Inactive Belt', 'slug' => 'p-inactive', 'status' => ProductStatus::INACTIVE]);

        // 1. All Products Tab
        $resAll = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=all');
        $resAll->assertStatus(200);
        $resAll->assertSee('Pending T-Shirt');
        $resAll->assertSee('Active Jeans');
        $resAll->assertSee('Rejected Jacket');
        $resAll->assertSee('Blocked Shoes');
        $resAll->assertSee('Inactive Belt');

        // 2. Pending Review Tab
        $resPending = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=pending_review');
        $resPending->assertStatus(200);
        $resPending->assertSee('Pending T-Shirt');
        $resPending->assertDontSee('Active Jeans');

        // 3. Approved / Active Tab
        $resActive = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=active');
        $resActive->assertStatus(200);
        $resActive->assertSee('Active Jeans');
        $resActive->assertDontSee('Pending T-Shirt');

        // 4. Rejected Tab
        $resRejected = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=rejected');
        $resRejected->assertStatus(200);
        $resRejected->assertSee('Rejected Jacket');
        $resRejected->assertDontSee('Active Jeans');

        // 5. Blocked Tab
        $resBlocked = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=blocked');
        $resBlocked->assertStatus(200);
        $resBlocked->assertSee('Blocked Shoes');
        $resBlocked->assertDontSee('Pending T-Shirt');

        // 6. Inactive Tab
        $resInactive = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation?status=inactive');
        $resInactive->assertStatus(200);
        $resInactive->assertSee('Inactive Belt');
        $resInactive->assertDontSee('Active Jeans');
    }

    public function test_admin_can_block_product_according_to_policy_and_notify_seller(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Footwear', 'slug' => 'footwear']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Counterfeit Designer Sneakers',
            'slug' => 'counterfeit-sneakers',
            'status' => ProductStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/catalogue/moderation/{$product->id}/block", [
            'policy_reason' => 'Unauthorized trademark counterfeit violation of protected brand assets.',
        ]);

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals(ProductStatus::BLOCKED, $product->status);
        $this->assertStringContainsString('Unauthorized trademark counterfeit', $product->rejection_reason);
        Notification::assertSentTo($sellerUser, ProductBlockedNotification::class);
    }

    public function test_admin_can_request_corrections_from_seller(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Home & Kitchen', 'slug' => 'home-kitchen']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Stainless Steel Cookware Set',
            'slug' => 'steel-cookware-set',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/catalogue/moderation/{$product->id}/request-corrections", [
            'correction_notes' => 'Please provide 2 additional images showing the base induction plate and rectify HSN to 7323.',
        ]);

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals(ProductStatus::PENDING_REVIEW, $product->status);
        $this->assertStringContainsString('Corrections Requested', $product->rejection_reason);
        Notification::assertSentTo($sellerUser, ProductCorrectionsRequestedNotification::class);
    }

    public function test_admin_can_perform_bulk_status_update_and_bulk_category_reassignment(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $catA = Category::create(['name' => 'Old Category', 'slug' => 'old-category']);
        $catB = Category::create(['name' => 'New Category', 'slug' => 'new-category']);

        $p1 = Product::create(['seller_id' => $seller->id, 'category_id' => $catA->id, 'title' => 'Bulk Product 1', 'slug' => 'bp-1', 'status' => ProductStatus::ACTIVE]);
        $p2 = Product::create(['seller_id' => $seller->id, 'category_id' => $catA->id, 'title' => 'Bulk Product 2', 'slug' => 'bp-2', 'status' => ProductStatus::ACTIVE]);

        // Bulk status update to inactive
        $statusRes = $this->actingAs($this->adminUser)->post('/admin/catalogue/moderation/bulk-status', [
            'product_ids' => [$p1->id, $p2->id],
            'status' => 'inactive',
            'reason' => 'Seasonal archival',
        ]);
        $statusRes->assertRedirect();
        $this->assertEquals(ProductStatus::INACTIVE, $p1->fresh()->status);
        $this->assertEquals(ProductStatus::INACTIVE, $p2->fresh()->status);

        // Bulk category reassignment
        $catRes = $this->actingAs($this->adminUser)->post('/admin/catalogue/moderation/bulk-category', [
            'product_ids' => [$p1->id, $p2->id],
            'category_id' => $catB->id,
        ]);
        $catRes->assertRedirect();
        $this->assertEquals($catB->id, $p1->fresh()->category_id);
        $this->assertEquals($catB->id, $p2->fresh()->category_id);
    }

    public function test_admin_can_export_products_csv_and_download_catalog_template(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::create(['name' => 'Exportables', 'slug' => 'exportables']);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Exportable Smart Speaker',
            'slug' => 'exportable-smart-speaker',
            'status' => ProductStatus::ACTIVE,
            'hsn_code' => '85182200',
            'tax_percent' => 18.00,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SPK-EXP-001',
            'name' => 'Black',
            'mrp' => 2999.00,
            'selling_price' => 1999.00,
            'cost_price' => 1400.00,
            'is_default' => true,
        ]);

        // Export CSV
        $exportRes = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation/export');
        $exportRes->assertStatus(200);
        $exportRes->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Download Template
        $templateRes = $this->actingAs($this->adminUser)->get('/admin/catalogue/moderation/import-template');
        $templateRes->assertStatus(200);
        $templateRes->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
