<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerImageBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->sellerUser = User::factory()->create([
            'email' => 'seller.images@allzone.test',
        ]);

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Visionary Tech Mart',
            'status' => 'approved',
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Visionary Tech Mart',
            'slug' => 'visionary-tech-mart',
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Smart TVs & Home Entertainment',
            'slug' => 'smart-tvs-home-entertainment',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'LG C3 55 Inch 4K OLED TV',
            'slug' => 'lg-c3-55-inch-4k-oled-tv',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'LG-C3-55-OLED',
            'name' => '55 Inch OLED',
            'mrp' => 149999.00,
            'selling_price' => 119999.00,
        ]);
    }

    public function test_seller_can_view_image_bulk_upload_interface_with_kpis(): void
    {
        // Existing media
        ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/sample.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.images.bulk_upload'));

        $response->assertStatus(200);
        $response->assertViewHas('totalImages', 1);
        $response->assertViewHas('productsWithImages', 1);
        $response->assertViewHas('productsWithoutImages', 0);
        $response->assertSee('LG C3 55 Inch 4K OLED TV');
        $response->assertSee('Add Product Photography');
        $response->assertSee('Watermark image');
    }

    public function test_multivendor_tenant_isolation_on_image_bulk_upload(): void
    {
        $otherUser = User::factory()->create();
        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Competitor Corp',
            'status' => 'approved',
        ]);
        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'category_id' => $this->category->id,
            'title' => 'Competitor Smartphone Pro',
        ]);
        $otherMedia = ProductMedia::create([
            'product_id' => $otherProduct->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/comp.jpg',
            'sort_order' => 1,
        ]);

        // Seller views interface - other seller's product/images are not in list
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.images.bulk_upload'));
        $response->assertStatus(200);
        $response->assertDontSee('Competitor Smartphone Pro');

        // Cannot upload directly to other seller's product
        $fakeImg = UploadedFile::fake()->image('test.jpg', 600, 600);
        $uploadResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.upload'), [
                'images' => [$fakeImg],
                'product_id' => $otherProduct->id,
            ]);
        $uploadResponse->assertStatus(403);

        // Cannot delete other seller's media
        $deleteResponse = $this->actingAs($this->sellerUser)
            ->delete(route('seller.images.destroy', $otherMedia->id));
        $deleteResponse->assertStatus(403);

        // Cannot replace other seller's media
        $replaceResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.replace', $otherMedia->id), [
                'new_image' => $fakeImg,
            ]);
        $replaceResponse->assertStatus(403);
    }

    public function test_seller_can_upload_multi_image_with_manual_product_assignment(): void
    {
        $img1 = UploadedFile::fake()->image('lg_front.jpg', 800, 800);
        $img2 = UploadedFile::fake()->image('lg_side.png', 800, 800);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.upload'), [
                'product_id' => $this->product->id,
                'variant_id' => $this->variant->id,
                'images' => [$img1, $img2],
                'set_primary' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('product_media', 2);
        $media1 = ProductMedia::where('product_id', $this->product->id)->where('sort_order', 1)->first();
        $this->assertNotNull($media1);
        $this->assertEquals($this->variant->id, $media1->variant_id);
        Storage::disk('public')->assertExists($media1->path);
    }

    public function test_smart_filename_auto_matching_by_sku(): void
    {
        // Image filename contains exact SKU prefix
        $img = UploadedFile::fake()->image('LG-C3-55-OLED_front.jpg', 600, 600);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.upload'), [
                'images' => [$img],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $media = ProductMedia::where('product_id', $this->product->id)->first();
        $this->assertNotNull($media);
        $this->assertEquals($this->variant->id, $media->variant_id);
    }

    public function test_smart_filename_auto_matching_by_product_id(): void
    {
        $img = UploadedFile::fake()->image("PRD-{$this->product->id}_main.jpg", 600, 600);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.upload'), [
                'images' => [$img],
            ]);

        $response->assertRedirect();

        $media = ProductMedia::where('product_id', $this->product->id)->first();
        $this->assertNotNull($media);
    }

    public function test_image_validation_rejects_unsupported_extensions(): void
    {
        $invalidFile = UploadedFile::fake()->create('malicious.exe', 500, 'application/x-msdownload');

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.upload'), [
                'product_id' => $this->product->id,
                'images' => [$invalidFile],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('upload_errors');
        $this->assertDatabaseCount('product_media', 0);
    }

    public function test_seller_can_download_csv_mapping_template(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.images.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('sku,product_id,image_filename,sort_order,is_primary', $content);
        $this->assertStringContainsString('tv_front.jpg', $content);
    }

    public function test_seller_can_upload_images_with_csv_mapping_file(): void
    {
        $img1 = UploadedFile::fake()->image('oled_display_front.jpg', 800, 800);
        $img2 = UploadedFile::fake()->image('oled_ports_back.jpg', 800, 800);

        $csvContent = "\xEF\xBB\xBF" .
            "sku,product_id,image_filename,sort_order,is_primary\n" .
            "LG-C3-55-OLED,,oled_display_front.jpg,1,yes\n" .
            "LG-C3-55-OLED,,oled_ports_back.jpg,2,no\n";

        $csvFile = UploadedFile::fake()->createWithContent('mapping.csv', $csvContent);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.map_csv'), [
                'csv_file' => $csvFile,
                'images' => [$img1, $img2],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('product_media', 2);
        $primaryMedia = ProductMedia::where('product_id', $this->product->id)->where('sort_order', 1)->first();
        $this->assertNotNull($primaryMedia);
    }

    public function test_seller_can_set_primary_cover_image(): void
    {
        $media1 = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/first.jpg',
            'sort_order' => 1,
        ]);

        $media2 = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/second.jpg',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.set_primary', $media2->id));

        $response->assertRedirect();
        $media2->refresh();
        $media1->refresh();

        $this->assertEquals(1, $media2->sort_order);
        $this->assertGreaterThan(1, $media1->sort_order);
    }

    public function test_seller_can_reorder_product_gallery_images(): void
    {
        $media1 = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/m1.jpg',
            'sort_order' => 1,
        ]);

        $media2 = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/m2.jpg',
            'sort_order' => 2,
        ]);

        $media3 = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/m3.jpg',
            'sort_order' => 3,
        ]);

        // Reorder: 3, 1, 2
        $response = $this->actingAs($this->sellerUser)
            ->postJson(route('seller.images.reorder', $this->product->id), [
                'media_ids' => [$media3->id, $media1->id, $media2->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(1, $media3->fresh()->sort_order);
        $this->assertEquals(2, $media1->fresh()->sort_order);
        $this->assertEquals(3, $media2->fresh()->sort_order);
    }

    public function test_seller_can_replace_an_existing_gallery_image(): void
    {
        $oldPath = 'products/images/old_photo.jpg';
        Storage::disk('public')->put($oldPath, 'fake-old-image-bytes');

        $media = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => $oldPath,
            'sort_order' => 1,
        ]);

        $newImage = UploadedFile::fake()->image('new_crisp_photo.jpg', 1000, 1000);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.images.replace', $media->id), [
                'new_image' => $newImage,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $media->refresh();
        $this->assertNotEquals($oldPath, $media->path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_seller_can_delete_image_with_storage_cleanup(): void
    {
        $path = 'products/images/delete_me.jpg';
        Storage::disk('public')->put($path, 'fake-image-bytes');

        $media = ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => $path,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->delete(route('seller.images.destroy', $media->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('product_media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
