<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
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

class SellerBusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Product $product1;
    protected Product $product2;
    protected ProductVariant $variant1;
    protected ProductVariant $variant2;

    protected User $otherSellerUser;
    protected Seller $otherSeller;
    protected Product $otherProduct;

    protected Customer $customer;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->category = Category::factory()->create([
            'name' => 'Home & Kitchen',
            'slug' => 'home-and-kitchen',
        ]);

        $this->customer = Customer::factory()->create();

        // Primary Seller
        $this->sellerUser = User::factory()->create([
            'email' => 'analytics.seller@allzone.in',
            'phone' => '+919876543401',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Kitchenware',
            'legal_name' => 'Zenith Kitchenware Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now()->subMonths(4),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Kitchen Hub',
        ]);

        $this->product1 = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Stainless Steel Cookware Set',
            'slug' => 'stainless-steel-cookware-set',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant1 = ProductVariant::factory()->create([
            'product_id' => $this->product1->id,
            'selling_price' => 2499.00,
            'mrp' => 3999.00,
            'is_default' => true,
        ]);

        $this->product2 = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Cast Iron Skillet Pan',
            'slug' => 'cast-iron-skillet-pan',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant2 = ProductVariant::factory()->create([
            'product_id' => $this->product2->id,
            'selling_price' => 1299.00,
            'mrp' => 1899.00,
            'is_default' => true,
        ]);

        // Rival Seller (for multi-tenant scoping verification)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.analytics@allzone.in',
            'phone' => '+919876543402',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Prime Cookware Direct',
            'legal_name' => 'Prime Cookware Direct Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now()->subMonths(5),
        ]);

        $this->otherProduct = Product::factory()->create([
            'seller_id' => $this->otherSeller->id,
            'category_id' => $this->category->id,
            'title' => 'Rival Copper Bottom Kadai',
            'slug' => 'rival-copper-bottom-kadai',
            'status' => ProductStatus::ACTIVE,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $this->otherProduct->id,
            'selling_price' => 3499.00,
            'mrp' => 4999.00,
            'is_default' => true,
        ]);
    }

    public function test_seller_can_view_business_dashboard_with_all_actual_metrics(): void
    {
        // Create order and fulfilment order for primary seller
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'subtotal' => 4998.00,
            'total' => 4998.00,
            'created_at' => now()->subDays(2),
        ]);

        $fulfilmentOrder = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-ZEN-001',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
            'subtotal' => 4998.00,
            'commission_amount' => 499.80,
            'seller_payable' => 4498.20,
            'delivered_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fulfilmentOrder->id,
            'product_id' => $this->product1->id,
            'variant_id' => $this->variant1->id,
            'title' => $this->product1->title,
            'variant_name' => 'Default',
            'sku' => 'ZEN-SKU-01',
            'qty' => 2,
            'price' => 2499.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 4998.00,
            'created_at' => now()->subDays(2),
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fulfilmentOrder->id,
            'base_amount' => 4998.00,
            'commission' => 499.80,
            'shipping_adjustment' => 0.00,
            'net_payable' => 4498.20,
            'entry_type' => 'credit',
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Business Analytics &amp; Intelligence', false);
        $response->assertSee('₹4,998.00'); // Gross sales
        $response->assertSee('₹4,498.20'); // Net revenue
        $response->assertSee('100% delivery rate');
        $response->assertSee('Stainless Steel Cookware Set'); // Top selling product
    }

    public function test_multivendor_tenant_isolation_on_business_dashboard(): void
    {
        // Rival order
        $rivalOrder = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'subtotal' => 6998.00,
            'total' => 6998.00,
            'created_at' => now()->subDays(2),
        ]);

        $rivalFO = FulfilmentOrder::create([
            'order_id' => $rivalOrder->id,
            'fulfilment_order_number' => 'FO-RIVAL-001',
            'seller_id' => $this->otherSeller->id,
            'status' => 'delivered',
            'subtotal' => 6998.00,
            'commission_amount' => 699.80,
            'seller_payable' => 6298.20,
            'created_at' => now()->subDays(2),
        ]);

        // Primary seller visits dashboard
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('FO-RIVAL-001');
        $response->assertDontSee('6,998.00');
        $response->assertDontSee('6,298.20');
        $response->assertDontSee('Rival Copper Bottom Kadai');
    }

    public function test_date_filters_yesterday_last_7_days_and_custom_range(): void
    {
        $order1 = Order::factory()->create(['customer_id' => $this->customer->id]);
        $order2 = Order::factory()->create(['customer_id' => $this->customer->id]);

        // 1. Order placed yesterday midday
        $fo1 = FulfilmentOrder::create([
            'order_id' => $order1->id,
            'fulfilment_order_number' => 'FO-YEST-01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
            'subtotal' => 1200.00,
            'commission_amount' => 120.00,
            'seller_payable' => 1080.00,
        ]);
        $fo1->created_at = now()->subDay()->startOfDay()->addHours(12);
        $fo1->save();

        // 2. Order placed 15 days ago
        $fo2 = FulfilmentOrder::create([
            'order_id' => $order2->id,
            'fulfilment_order_number' => 'FO-PAST-01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
            'subtotal' => 3500.00,
            'commission_amount' => 350.00,
            'seller_payable' => 3150.00,
        ]);
        $fo2->created_at = now()->subDays(15)->startOfDay()->addHours(12);
        $fo2->save();

        // Filter: yesterday -> sees 1200, not 3500
        $yestResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard', ['range' => 'yesterday']));

        $yestResponse->assertStatus(200);
        $yestResponse->assertSee('₹1,200.00');
        $yestResponse->assertDontSee('₹3,500.00');

        // Filter: last_30 -> sees both (1200 + 3500 = 4700)
        $monthResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard', ['range' => 'last_30']));

        $monthResponse->assertStatus(200);
        $monthResponse->assertSee('₹4,700.00');

        // Filter: custom -> range covering 15 days ago
        $customResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard', [
                'range' => 'custom',
                'start_date' => now()->subDays(20)->toDateString(),
                'end_date' => now()->subDays(10)->toDateString(),
            ]));

        $customResponse->assertStatus(200);
        $customResponse->assertSee('₹3,500.00');
        $customResponse->assertDontSee('₹1,200.00');
    }

    public function test_distinct_orders_counts_prevent_duplicate_split_order_counting(): void
    {
        // 1 Parent Order split into 2 Fulfilment Orders for primary seller (e.g. from 2 store outlets)
        $parentOrder = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'subtotal' => 5000.00,
            'total' => 5000.00,
            'created_at' => now()->subDays(1),
        ]);

        FulfilmentOrder::create([
            'order_id' => $parentOrder->id,
            'fulfilment_order_number' => 'FO-SPLIT-01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
            'subtotal' => 2500.00,
            'commission_amount' => 250.00,
            'seller_payable' => 2250.00,
            'created_at' => now()->subDays(1),
        ]);

        FulfilmentOrder::create([
            'order_id' => $parentOrder->id,
            'fulfilment_order_number' => 'FO-SPLIT-02',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
            'subtotal' => 2500.00,
            'commission_amount' => 250.00,
            'seller_payable' => 2250.00,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard'));

        $response->assertStatus(200);
        $response->assertSee('2'); // Total fulfilment order slices
        $response->assertSee('1 distinct baskets'); // Distinct parent orders count
        $response->assertSee('₹5,000.00'); // Combined subtotal
    }

    public function test_order_status_pipeline_distribution_breakdown(): void
    {
        $order1 = Order::factory()->create(['customer_id' => $this->customer->id]);
        $order2 = Order::factory()->create(['customer_id' => $this->customer->id]);
        $order3 = Order::factory()->create(['customer_id' => $this->customer->id]);

        FulfilmentOrder::create([
            'order_id' => $order1->id,
            'fulfilment_order_number' => 'FO-STAT-01',
            'seller_id' => $this->seller->id,
            'status' => 'pending',
            'subtotal' => 500.00,
            'seller_payable' => 450.00,
            'created_at' => now()->subDays(2),
        ]);

        FulfilmentOrder::create([
            'order_id' => $order2->id,
            'fulfilment_order_number' => 'FO-STAT-02',
            'seller_id' => $this->seller->id,
            'status' => 'delivered',
            'subtotal' => 800.00,
            'seller_payable' => 720.00,
            'created_at' => now()->subDays(2),
        ]);

        FulfilmentOrder::create([
            'order_id' => $order3->id,
            'fulfilment_order_number' => 'FO-STAT-03',
            'seller_id' => $this->seller->id,
            'status' => 'cancelled',
            'subtotal' => 600.00,
            'seller_payable' => 0.00,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Order Status Pipeline');
        $response->assertSee('Delivered Orders');
        $response->assertSee('Cancelled Orders');
    }

    public function test_top_selling_and_low_performing_product_identification(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-TOP-01',
            'seller_id' => $this->seller->id,
            'status' => 'delivered',
            'subtotal' => 4998.00,
            'seller_payable' => 4500.00,
            'created_at' => now()->subDays(3),
        ]);

        // product1 has 2 units sold
        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product1->id,
            'variant_id' => $this->variant1->id,
            'title' => $this->product1->title,
            'variant_name' => 'Default',
            'sku' => 'ZEN-SKU-01',
            'qty' => 2,
            'price' => 2499.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 4998.00,
            'created_at' => now()->subDays(3),
        ]);

        // product2 has 0 units sold in this period (low performing)

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Stainless Steel Cookware Set');
        $response->assertSee('2 units');
        $response->assertSee('Cast Iron Skillet Pan'); // in low performing table
    }

    public function test_chart_data_api_endpoint_returns_json_trends(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-JSON-01',
            'seller_id' => $this->seller->id,
            'status' => 'delivered',
            'subtotal' => 1500.00,
            'seller_payable' => 1350.00,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->getJson(route('seller.business_dashboard.chart_data', ['range' => 'last_7']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'range',
            'label',
            'start_date',
            'end_date',
            'trends' => [
                '*' => [
                    'date',
                    'label',
                    'orders',
                    'sales',
                    'revenue',
                    'delivered',
                ],
            ],
        ]);

        $this->assertEquals('last_7', $response->json('range'));
        $this->assertCount(7, $response->json('trends'));
    }

    public function test_seller_can_export_analytics_csv_report(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);

        FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-CSV-999',
            'seller_id' => $this->seller->id,
            'status' => 'delivered',
            'subtotal' => 2000.00,
            'commission_amount' => 200.00,
            'seller_payable' => 1800.00,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.business_dashboard.export', ['range' => 'last_7']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('FO-CSV-999', $response->streamedContent());
        $this->assertStringContainsString('2000.00', $response->streamedContent());
        $this->assertStringContainsString('1800.00', $response->streamedContent());
    }
}
