<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerPricingManagementTest extends TestCase
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
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sellerUser = User::factory()->create([
            'email' => 'pricing.merchant@allzone.in',
            'phone' => '+919876543210',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Apparel Hub',
            'legal_name' => 'Zenith Apparel Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Indiranagar Store',
            'slug' => 'zenith-indiranagar-store',
            'status' => StoreStatus::ACTIVE,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Men Fashion',
            'slug' => 'men-fashion',
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Slim Fit Cotton Casual Shirt',
            'slug' => 'slim-fit-cotton-casual-shirt',
            'tax_percent' => 18.00,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-BLK-M',
            'name' => 'Black / Medium',
            'mrp' => 1499.00,
            'selling_price' => 999.00,
            'cost_price' => 600.00,
            'weight_grams' => 300,
        ]);
    }

    /**
     * Test 1: Seller can view pricing overview dashboard with actual metrics and tabs.
     */
    public function test_seller_can_view_pricing_overview_with_actual_metrics_and_tabs(): void
    {
        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.index'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.pricing.index');
        $response->assertSee('Pricing Management');
        $response->assertSee('Active Listings');
        $response->assertSee('Pricing Issues');
        $response->assertSee('Low Sales (30 Days)');
        $response->assertSee('Avg. Selling Price');
        $response->assertSee('ZNT-SHIRT-BLK-M');
        $response->assertSee('₹999.00');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total_active_products']);
        $this->assertEquals(999.00, $metrics['avg_selling_price']);
    }

    /**
     * Test 2: Multivendor tenant isolation on pricing management.
     */
    public function test_multivendor_tenant_isolation_on_pricing(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other.seller@allzone.in',
            'phone' => '+919876543299',
        ]);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Heritage Silks',
            'status' => SellerStatus::APPROVED,
        ]);

        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'title' => 'Pure Kanchipuram Silk Saree',
        ]);

        $otherVariant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'sku' => 'SLK-KANCHI-RED',
            'selling_price' => 4999.00,
            'mrp' => 6999.00,
        ]);

        // Seller A should see their variant, but NOT Seller B's variant
        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.index'));
        $response->assertStatus(200);
        $response->assertSee($this->variant->sku);
        $response->assertDontSee($otherVariant->sku);

        // Seller A cannot update Seller B's price (403 Forbidden)
        $unauthorizedUpdate = $this->actingAs($this->sellerUser)->post(
            route('seller.pricing.update_price', $otherVariant),
            ['selling_price' => 4500.00]
        );
        $unauthorizedUpdate->assertStatus(403);
    }

    /**
     * Test 3: Detection of pricing issues (price > MRP, zero/negative price, below cost).
     */
    public function test_pricing_issues_detection_and_filtering(): void
    {
        // 1. Variant with price exceeding MRP
        $invalidMrpVariant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-OVERPRICED',
            'selling_price' => 1999.00,
            'mrp' => 1499.00, // MRP is lower than selling price
        ]);

        // 2. Variant with ₹0 price
        $zeroPriceVariant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-ZERO',
            'selling_price' => 0.00,
            'mrp' => 1499.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.index', ['tab' => 'issues']));

        $response->assertStatus(200);
        $response->assertSee($invalidMrpVariant->sku);
        $response->assertSee($zeroPriceVariant->sku);
        $response->assertDontSee($this->variant->sku); // Compliant variant is not in issues tab
    }

    /**
     * Test 4: Detection of low sales products in past 30 days.
     */
    public function test_low_sales_detection_and_filtering(): void
    {
        // Create an order in the last 30 days for $this->variant
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_status' => PaymentStatus::PAID,
        ]);
        $fo = FulfilmentOrder::factory()->create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'status' => 'delivered',
        ]);
        OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'qty' => 3,
            'created_at' => now()->subDays(5),
        ]);

        // Create a 2nd variant with 0 sales
        $noSalesVariant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-NOSALES',
            'selling_price' => 899.00,
            'mrp' => 1299.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.index', ['tab' => 'low_sales']));

        $response->assertStatus(200);
        $response->assertSee($noSalesVariant->sku);
        $response->assertDontSee($this->variant->sku); // Variant with 3 orders should not be in low_sales
    }

    /**
     * Test 5: Seller can update individual product price and price history is recorded.
     */
    public function test_seller_can_update_individual_product_price_and_records_history(): void
    {
        $response = $this->actingAs($this->sellerUser)->post(
            route('seller.pricing.update_price', $this->variant),
            [
                'selling_price' => 849.00,
                'mrp' => 1399.00,
                'reason' => 'Mid-season discount promotion',
            ]
        );

        $response->assertSessionHas('success');

        $this->variant->refresh();
        $this->assertEquals(849.00, (float) $this->variant->selling_price);
        $this->assertEquals(1399.00, (float) $this->variant->mrp);

        // Verify audit log
        $this->assertDatabaseHas('product_price_histories', [
            'product_variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'user_id' => $this->sellerUser->id,
            'old_price' => 999.00,
            'new_price' => 849.00,
            'change_type' => 'manual',
            'reason' => 'Mid-season discount promotion',
        ]);
    }

    /**
     * Test 6: Validation rules reject selling price exceeding MRP or negative prices.
     */
    public function test_price_validation_rejects_price_exceeding_mrp_or_negative(): void
    {
        // 1. Selling price exceeding MRP
        $resMrp = $this->actingAs($this->sellerUser)->post(
            route('seller.pricing.update_price', $this->variant),
            [
                'selling_price' => 1999.00,
                'mrp' => 1200.00, // MRP is lower
            ]
        );
        $resMrp->assertSessionHas('error');

        // 2. Negative/zero price
        $resZero = $this->actingAs($this->sellerUser)->post(
            route('seller.pricing.update_price', $this->variant),
            [
                'selling_price' => 0.00,
                'mrp' => 1200.00,
            ]
        );
        $resZero->assertSessionHasErrors(['selling_price']);
    }

    /**
     * Test 7: Seller can preview bulk price adjustments before applying.
     */
    public function test_seller_can_preview_bulk_price_updates(): void
    {
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-BLK-L',
            'selling_price' => 1000.00,
            'mrp' => 1500.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->postJson(
            route('seller.pricing.bulk_preview'),
            [
                'variant_ids' => [$this->variant->id, $variant2->id],
                'adjustment_type' => 'percent_discount',
                'adjustment_value' => 10.0,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 2,
        ]);

        $items = $response->json('items');
        $this->assertEquals(899.10, $items[0]['new_price']);
        $this->assertEquals(900.00, $items[1]['new_price']);
        $this->assertNotNull($items[0]['new_earnings']);
    }

    /**
     * Test 8: Seller can execute bulk price update and audit trails are logged.
     */
    public function test_seller_can_execute_bulk_price_update_and_logs_audit_trail(): void
    {
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-BLK-XL',
            'selling_price' => 1200.00,
            'mrp' => 1600.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->post(
            route('seller.pricing.bulk_update'),
            [
                'variant_ids' => [$this->variant->id, $variant2->id],
                'adjustment_type' => 'fixed_discount',
                'adjustment_value' => 100.00,
                'reason' => 'Clearance sale flat 100 off',
            ]
        );

        $response->assertSessionHas('success');

        $this->variant->refresh();
        $variant2->refresh();

        $this->assertEquals(899.00, (float) $this->variant->selling_price);
        $this->assertEquals(1100.00, (float) $variant2->selling_price);

        // Verify bulk history logged for both
        $this->assertEquals(
            2,
            ProductPriceHistory::where('seller_id', $this->seller->id)
                ->where('change_type', 'bulk')
                ->where('reason', 'Clearance sale flat 100 off')
                ->count()
        );
    }

    /**
     * Test 9: Seller can calculate rule-based earnings live via AJAX.
     */
    public function test_seller_can_calculate_realtime_earnings_via_ajax(): void
    {
        $response = $this->actingAs($this->sellerUser)->getJson(
            route('seller.pricing.calculate_earnings', ['price' => 1000.00, 'category_id' => $this->category->id])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'selling_price',
            'commission_rate',
            'commission_amount',
            'total_commission',
            'gateway_fee',
            'net_payable',
        ]);
        $this->assertEquals(1000.00, $response->json('selling_price'));
        $this->assertGreaterThan(0, $response->json('net_payable'));
    }

    /**
     * Test 10: Seller can view price change history tab.
     */
    public function test_seller_can_view_price_change_history_tab(): void
    {
        ProductPriceHistory::create([
            'product_variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'user_id' => $this->sellerUser->id,
            'old_price' => 1099.00,
            'new_price' => 999.00,
            'change_type' => 'manual',
            'reason' => 'Price matched with competitors',
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.index', ['tab' => 'history']));

        $response->assertStatus(200);
        $response->assertSee('Price Change History');
        $response->assertSee('Price matched with competitors');
        $response->assertSee('₹1,099.00');
        $response->assertSee('₹999.00');
    }

    /**
     * Test 11: Seller can view return reduction tools with actual COD vs Prepaid data.
     */
    public function test_seller_can_view_reduce_rto_tools_with_actual_cod_prepaid_data(): void
    {
        // Create 1 COD order and 1 Prepaid order with shipments
        $customer = Customer::factory()->create();

        // 1. COD Order
        $orderCod = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method' => PaymentMethod::COD,
        ]);
        $foCod = FulfilmentOrder::factory()->create([
            'order_id' => $orderCod->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
        ]);
        Shipment::create([
            'fulfilment_order_id' => $foCod->id,
            'shipment_number' => 'SHP-COD-01',
            'provider' => 'shiprocket',
            'awb' => 'AWB-COD-01',
            'status' => ShipmentStatus::RTO_INITIATED,
        ]);

        // 2. Prepaid Order
        $orderPrepaid = Order::factory()->create([
            'customer_id' => $customer->id,
            'payment_method' => PaymentMethod::UPI,
        ]);
        $foPrepaid = FulfilmentOrder::factory()->create([
            'order_id' => $orderPrepaid->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
        ]);
        Shipment::create([
            'fulfilment_order_id' => $foPrepaid->id,
            'shipment_number' => 'SHP-PREPAID-01',
            'provider' => 'shiprocket',
            'awb' => 'AWB-PREPAID-01',
            'status' => ShipmentStatus::DELIVERED,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.pricing.reduce_rto'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.pricing.reduce-rto');
        $response->assertSee('Return & RTO Reduction Tools');
        $response->assertSee('Cash on Delivery (COD) Performance');
        $response->assertSee('Prepaid (Razorpay) Performance');
        $response->assertSee('Return Prevention Operational Checklist');

        $this->assertEquals(1, $response->viewData('totalCod'));
        $this->assertEquals(1, $response->viewData('codRto'));
        $this->assertEquals(100.0, $response->viewData('codRtoRate'));
        $this->assertEquals(1, $response->viewData('totalPrepaid'));
        $this->assertEquals(0, $response->viewData('prepaidRto'));
        $this->assertEquals(0.0, $response->viewData('prepaidRtoRate'));
    }

    /**
     * Test 12: Seller can export pricing list and history as CSV.
     */
    public function test_seller_can_export_pricing_and_history_as_csv(): void
    {
        // 1. Export catalog pricing
        $resPricing = $this->actingAs($this->sellerUser)->get(route('seller.pricing.export', ['tab' => 'all']));
        $resPricing->assertStatus(200);
        $resPricing->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contentPricing = $resPricing->streamedContent();
        $this->assertStringContainsString('Product Title', $contentPricing);
        $this->assertStringContainsString('Selling Price (INR)', $contentPricing);
        $this->assertStringContainsString($this->variant->sku, $contentPricing);

        // 2. Export price history
        ProductPriceHistory::create([
            'product_variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'user_id' => $this->sellerUser->id,
            'old_price' => 1100.00,
            'new_price' => 999.00,
            'change_type' => 'manual',
            'reason' => 'Export test log',
        ]);

        $resHistory = $this->actingAs($this->sellerUser)->get(route('seller.pricing.export', ['tab' => 'history']));
        $resHistory->assertStatus(200);
        $resHistory->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contentHistory = $resHistory->streamedContent();
        $this->assertStringContainsString('Old Selling Price (INR)', $contentHistory);
        $this->assertStringContainsString('Export test log', $contentHistory);
    }
}
