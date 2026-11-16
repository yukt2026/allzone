<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectronicsOnlyUnifiedMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected User $sellerUser;
    protected Seller $seller;
    protected Brand $brand;
    protected Category $electronicsCat;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->customerUser = User::factory()->create([
            'name' => 'Aditi Sharma',
            'email' => 'aditi@allzone.in',
            'phone' => '9876543210',
            'status' => 'active',
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'loyalty_points' => 100,
        ]);

        $this->sellerUser = User::factory()->create([
            'name' => 'Vikas Electronics',
            'email' => 'vikas@seller.in',
            'phone' => '9876543211',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Internal Vendor Alpha Electronics Pvt Ltd',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->brand = Brand::create([
            'name' => 'Sony Electronics',
            'slug' => 'sony-electronics',
            'is_active' => true,
        ]);

        $this->electronicsCat = Category::create([
            'name' => 'Audio & Headphones',
            'slug' => 'audio-and-headphones',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->electronicsCat->id,
            'brand_id' => $this->brand->id,
            'title' => 'Sony WH-1000XM5 Wireless Headphones',
            'slug' => 'sony-wh-1000xm5-wireless-headphones',
            'product_type' => ProductType::SIMPLE,
            'status' => ProductStatus::ACTIVE,
            'is_active' => true,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Midnight Black',
            'sku' => 'SONY-WHXM5-BLK',
            'selling_price' => 29990.00,
            'mrp' => 34990.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        $location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'name' => 'Main Electronics Hub',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_only_electronics_categories_exist_and_zero_grocery_categories_exist(): void
    {
        $groceryNames = [
            'Groceries & Staples',
            'Fresh Fruits & Vegetables',
            'Dairy, Bread & Eggs',
            'Snacks & Beverages',
            'Personal Care & Hygiene',
            'Beauty & Cosmetics',
            'Baby Care & Kids',
            'Stationery & Office Supplies',
            'Fashion & Apparel',
        ];

        foreach ($groceryNames as $grocery) {
            $this->assertDatabaseMissing('categories', ['name' => $grocery]);
        }
    }

    public function test_api_categories_returns_only_active_electronics_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Audio & Headphones']);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $cat) {
            $this->assertStringNotContainsStringIgnoringCase('grocery', $cat['name']);
            $this->assertStringNotContainsStringIgnoringCase('vegetable', $cat['name']);
            $this->assertStringNotContainsStringIgnoringCase('dairy', $cat['name']);
            $this->assertStringNotContainsStringIgnoringCase('cosmetics', $cat['name']);
        }
    }

    public function test_api_home_returns_only_active_electronics_categories(): void
    {
        $response = $this->getJson('/api/v1/home');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $categories = $response->json('data.categories');
        $this->assertNotEmpty($categories);
        foreach ($categories as $cat) {
            $this->assertStringNotContainsStringIgnoringCase('grocery', $cat['name']);
            $this->assertStringNotContainsStringIgnoringCase('fruit', $cat['name']);
        }
    }

    public function test_api_product_detail_returns_unified_allzone_branding_without_store_names(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.seller.name', 'ALLZONE Verified Partner')
            ->assertJsonPath('data.seller.fulfilled_by', 'ALLZONE')
            ->assertJsonPath('data.seller.badge', 'ALLZONE Assured');

        // Internal business name must never be exposed
        $response->assertJsonMissing(['name' => 'Internal Vendor Alpha Electronics Pvt Ltd']);
        $this->assertStringNotContainsString(
            'Internal Vendor Alpha Electronics Pvt Ltd',
            (string) $response->getContent()
        );
    }

    public function test_api_customer_order_detail_returns_unified_allzone_branding(): void
    {
        $order = Order::create([
            'order_number' => 'AZ-' . date('Ymd') . '-UNIFIED1',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::PLACED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'subtotal' => 29990.00,
            'delivery_fee' => 0.00,
            'total' => 29990.00,
            'currency' => 'INR',
            'idempotency_key' => 'idemp-unified-test-1',
            'address_snapshot' => [
                'name' => 'Aditi Sharma',
                'phone' => '9876543210',
                'line1' => 'MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'fulfilment_order_number' => 'FO-UNIFIED-01',
            'status' => 'placed',
            'fulfilment_type' => 'standard',
            'subtotal' => 29990.00,
            'shipping_fee' => 0.00,
            'total' => 29990.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'title' => $this->product->title,
            'variant_name' => $this->variant->name,
            'sku' => $this->variant->sku,
            'price' => 29990.00,
            'qty' => 1,
            'total' => 29990.00,
        ]);

        $response = $this->actingAs($this->customerUser)
            ->getJson("/api/v1/customer/orders/{$order->order_number}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $packages = $response->json('data.packages');
        $this->assertNotEmpty($packages);
        $this->assertEquals('ALLZONE Verified Partner', $packages[0]['seller_name']);
        $this->assertStringNotContainsString(
            'Internal Vendor Alpha Electronics Pvt Ltd',
            (string) $response->getContent()
        );
    }

    public function test_public_stores_directory_redirects_to_search(): void
    {
        $response = $this->get(route('stores.index'));
        $response->assertRedirect(route('search'));
    }

    public function test_public_quick_portal_redirects_to_search(): void
    {
        $response = $this->get('/quick');
        $response->assertRedirect(route('search'));

        $legacyResponse = $this->get('/quick-commerce');
        $legacyResponse->assertRedirect('/quick');
    }

    public function test_product_detail_page_hides_vendor_identity_and_shows_allzone_assured(): void
    {
        $response = $this->get(route('product.show', $this->product->slug));

        $response->assertStatus(200)
            ->assertSee('Fulfilled by ALLZONE Assured')
            ->assertSee('Shiprocket Express')
            ->assertSee('100% Genuine')
            ->assertDontSee('Internal Vendor Alpha Electronics Pvt Ltd')
            ->assertDontSee('Visit Store');
    }

    public function test_cart_and_checkout_pages_show_allzone_assured_and_hide_individual_seller_names(): void
    {
        $this->actingAs($this->customerUser);

        $this->post(route('cart.add'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        // Cart Page
        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200)
            ->assertSee('ALLZONE Assured')
            ->assertSee('Shiprocket')
            ->assertDontSee('Internal Vendor Alpha Electronics Pvt Ltd');
    }

    public function test_order_success_page_shows_allzone_assured_and_hides_store_names(): void
    {
        $order = Order::create([
            'order_number' => 'AZ-' . date('Ymd') . '-SUCC1',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::PLACED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'subtotal' => 29990.00,
            'delivery_fee' => 0.00,
            'total' => 29990.00,
            'currency' => 'INR',
            'idempotency_key' => 'idemp-succ-test-1',
            'address_snapshot' => [
                'name' => 'Aditi Sharma',
                'phone' => '9876543210',
                'line1' => 'MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'fulfilment_order_number' => 'FO-SUCC-01',
            'status' => 'placed',
            'fulfilment_type' => 'standard',
            'subtotal' => 29990.00,
            'shipping_fee' => 0.00,
            'total' => 29990.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'title' => $this->product->title,
            'variant_name' => $this->variant->name,
            'sku' => $this->variant->sku,
            'price' => 29990.00,
            'qty' => 1,
            'total' => 29990.00,
        ]);

        $this->actingAs($this->customerUser);
        $response = $this->get(route('checkout.success', $order->order_number));

        $response->assertStatus(200)
            ->assertSee('Thank You For Your Order!')
            ->assertSee('ALLZONE Assured')
            ->assertSee('Shiprocket Express')
            ->assertDontSee('Internal Vendor Alpha Electronics Pvt Ltd');
    }

    public function test_seller_cannot_publish_non_electronics_products(): void
    {
        $nonElectronicsItems = [
            'Fresh Cow Milk 1L Pouch',
            'Organic Basmati Rice 10kg',
            'Men Cotton Round Neck T-Shirt',
            'Matte Finish Red Lipstick',
            'Premium Baby Diapers Jumbo Pack',
            'Spicy Potato Chips 150g',
        ];

        foreach ($nonElectronicsItems as $title) {
            $payload = [
                'title' => $title,
                'category_id' => $this->electronicsCat->id,
                'brand_id' => $this->brand->id,
                'product_type' => 'simple',
                'simple_sku' => 'TEST-SKU-' . rand(1000, 9999),
                'simple_mrp' => 500.00,
                'simple_selling_price' => 450.00,
                'tax_percent' => 18.0,
                'return_window_days' => 7,
                'stock' => 10,
                'submit_action' => 'save_draft',
            ];

            $response = $this->actingAs($this->sellerUser)
                ->from(route('seller.products.create'))
                ->post(route('seller.products.store'), $payload);

            $response->assertSessionHasErrors('title');
        }
    }

    public function test_multivendor_backend_allows_approved_sellers_to_manage_electronics_products(): void
    {
        // Seller 2
        $sellerUser2 = User::factory()->create([
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $sellerUser2->assignRole('seller_owner');
        $seller2 = Seller::factory()->create([
            'user_id' => $sellerUser2->id,
            'business_name' => 'Internal Beta Appliances LLP',
            'status' => SellerStatus::APPROVED,
        ]);

        // Seller 2 creates valid electronics product
        $payload = [
            'title' => 'Smart Inverter Microwave Oven 28L',
            'category_id' => $this->electronicsCat->id,
            'brand_id' => $this->brand->id,
            'product_type' => 'simple',
            'simple_sku' => 'OVEN-INV-28L',
            'simple_mrp' => 15990.00,
            'simple_selling_price' => 13490.00,
            'tax_percent' => 18.0,
            'return_window_days' => 7,
            'stock' => 12,
            'submit_action' => 'save_draft',
        ];

        $response = $this->actingAs($sellerUser2)
            ->post(route('seller.products.store'), $payload);

        $response->assertRedirect(route('seller.products.index'));

        $newProduct = Product::where('title', 'Smart Inverter Microwave Oven 28L')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals($seller2->id, $newProduct->seller_id);
    }
}
