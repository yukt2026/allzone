<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommissionRuleScope;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\Admin\AdminPricingCommissionService;
use App\Services\Commission\CommissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPricingAndCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Seller $seller;
    protected User $sellerUser;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;
    protected CommissionService $commissionService;
    protected AdminPricingCommissionService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);
        $this->pricingService = app(AdminPricingCommissionService::class);

        Setting::set('default_commission_pct', '10.00');
        Setting::set('commission_gst_pct', '18.00');
        Setting::set('payment_gateway_fee_pct', '2.00');
        Setting::set('gateway_fee_borne_by_seller', '1');
        Setting::set('min_product_price', '10.00');
        Setting::set('max_product_price', '500000.00');
        Setting::set('max_discount_percentage', '80.00');

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create([
            'email' => 'admin_pricing_' . uniqid() . '@allzone.local',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->sellerUser = User::factory()->create([
            'email' => 'seller_pricing_' . uniqid() . '@allzone.local',
        ]);
        $this->seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Nexus Retail Hub',
            'legal_name' => 'Nexus Retail Pvt Ltd',
            'slug' => 'nexus-retail-hub',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->category = Category::create([
            'name' => 'Wearables & Audio',
            'slug' => 'wearables-audio',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Nexus Pro Wireless Earbuds',
            'slug' => 'nexus-pro-wireless-earbuds',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'NEX-EAR-001',
            'name' => 'Default Variant',
            'selling_price' => 2000.00,
            'mrp' => 3999.00,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Admin can view pricing and commission console with all tabs and KPIs.
     */
    public function test_admin_can_view_pricing_and_commission_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.commissions.index'));

        $response->assertOk();
        $response->assertSee('Pricing, Commission & Platform Fees');
        $response->assertSee('Commission Rules &amp; Simulator', false);
        $response->assertSee('Platform Charges &amp; Pricing Controls', false);
        $response->assertSee('Commission History Ledger');
    }

    /**
     * Test 2: Product-specific exception rule takes highest priority over category, seller, and global.
     */
    public function test_product_specific_exception_rule_takes_highest_priority(): void
    {
        // 1. Global Rule (10%)
        CommissionRule::create([
            'name' => 'Global Default',
            'scope' => CommissionRuleScope::GLOBAL,
            'percent' => 10.00,
            'priority' => 1,
            'is_active' => true,
        ]);

        // 2. Category Rule (8%)
        CommissionRule::create([
            'name' => 'Audio Category Rule',
            'scope' => CommissionRuleScope::CATEGORY,
            'category_id' => $this->category->id,
            'percent' => 8.00,
            'priority' => 10,
            'is_active' => true,
        ]);

        // 3. Seller Rule (6%)
        CommissionRule::create([
            'name' => 'Nexus Seller Override',
            'scope' => CommissionRuleScope::SELLER,
            'seller_id' => $this->seller->id,
            'percent' => 6.00,
            'priority' => 20,
            'is_active' => true,
        ]);

        // 4. Product Exception Rule (3.5%)
        $productRule = CommissionRule::create([
            'name' => 'Nexus Earbuds Special Exception',
            'scope' => CommissionRuleScope::PRODUCT,
            'product_id' => $this->product->id,
            'percent' => 3.50,
            'priority' => 50,
            'is_active' => true,
        ]);

        // Resolve without product ID -> should resolve to seller rule (6%)
        $resolvedWithoutProduct = $this->commissionService->resolveRule(
            sellerId: $this->seller->id,
            categoryId: $this->category->id
        );
        $this->assertEquals('seller', $resolvedWithoutProduct['tier']);
        $this->assertEquals(6.00, $resolvedWithoutProduct['rate']);

        // Resolve with product ID -> should resolve to product exception (3.5%)
        $resolvedWithProduct = $this->commissionService->resolveRule(
            sellerId: $this->seller->id,
            categoryId: $this->category->id,
            productId: $this->product->id
        );
        $this->assertEquals('product', $resolvedWithProduct['tier']);
        $this->assertEquals(3.50, $resolvedWithProduct['rate']);
        $this->assertEquals($productRule->id, $resolvedWithProduct['rule']?->id);
    }

    /**
     * Test 3: Effective dates window is strictly respected.
     */
    public function test_commission_rule_effective_dates_window_is_respected(): void
    {
        $futurePromo = CommissionRule::create([
            'name' => 'Black Friday Promo',
            'scope' => CommissionRuleScope::PROMO,
            'percent' => 2.00,
            'starts_at' => Carbon::now()->addDays(5),
            'ends_at' => Carbon::now()->addDays(10),
            'priority' => 99,
            'is_active' => true,
        ]);

        // Today: should NOT apply
        $todayResolution = $this->commissionService->resolveRule();
        $this->assertNotEquals(2.00, $todayResolution['rate']);

        // During promo window (7 days in future): SHOULD apply
        $futureResolution = $this->commissionService->resolveRule(date: Carbon::now()->addDays(7));
        $this->assertEquals('promo', $futureResolution['tier']);
        $this->assertEquals(2.00, $futureResolution['rate']);
    }

    /**
     * Test 4: Historical transactions remain unchanged when a new commission rule is created.
     */
    public function test_new_commission_rule_does_not_mutate_historical_transactions(): void
    {
        $customerUser = User::factory()->create(['status' => 'active']);
        $customer = Customer::create(['user_id' => $customerUser->id]);
        $address = CustomerAddress::create([
            'user_id' => $customerUser->id,
            'customer_id' => $customer->id,
            'name' => 'John Buyer',
            'phone' => '9876543210',
            'line1' => 'MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_default' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-HIST-01',
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'address_snapshot' => [
                'name' => $address->name,
                'phone' => $address->phone,
                'line1' => $address->line1,
                'city' => $address->city,
                'state' => $address->state,
                'pincode' => $address->pincode,
            ],
            'subtotal' => 2000.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'delivery_fee' => 0.00,
            'total' => 2000.00,
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);

        $store = Store::firstOrCreate(
            ['seller_id' => $this->seller->id],
            [
                'name' => $this->seller->business_name . ' Store',
                'slug' => $this->seller->slug . '-store',
                'status' => 'active',
            ]
        );

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $store->id,
            'fulfilment_order_number' => 'FO-HIST-01',
            'fulfilment_type' => 'standard',
            'subtotal' => 2000.00,
            'status' => 'delivered',
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'title' => $this->product->title,
            'price' => 2000.00,
            'qty' => 1,
            'total' => 2000.00,
        ]);

        // Initial record created at default 10%
        $historicalEntry = $this->commissionService->recordForDeliveredFulfilmentOrder($fo);
        $this->assertEquals(200.00, (float) $historicalEntry->commission);

        // Later, admin introduces a 25% aggressive commission rule
        CommissionRule::create([
            'name' => 'New 25 Percent Aggressive Rule',
            'scope' => CommissionRuleScope::GLOBAL,
            'percent' => 25.00,
            'priority' => 100,
            'is_active' => true,
        ]);

        // Verify historical entry in database is completely untouched
        $freshEntry = CommissionEntry::find($historicalEntry->id);
        $this->assertEquals(200.00, (float) $freshEntry->commission);
        $this->assertEquals($historicalEntry->net_payable, $freshEntry->net_payable);
    }

    /**
     * Test 5: Admin can update platform charges and pricing control policies.
     */
    public function test_admin_can_update_platform_charges_and_pricing_controls(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.commissions.controls.update'), [
            'default_commission_pct' => 12.50,
            'commission_gst_pct' => 18.00,
            'payment_gateway_fee_pct' => 2.50,
            'gateway_fee_borne_by_seller' => 1,
            'pass_delivery_fee_to_seller' => 1,
            'fixed_platform_fee_per_order' => 15.00,
            'min_product_price' => 50.00,
            'max_product_price' => 250000.00,
            'max_discount_percentage' => 75.00,
            'max_coupon_discount_pct' => 40.00,
            'max_coupon_discount_flat' => 800.00,
            'require_admin_approval_for_large_price_drop' => 1,
        ]);

        $response->assertRedirect(route('admin.commissions.index', ['tab' => 'charges']));
        $response->assertSessionHas('success');

        $this->assertEquals('12.5', Setting::get('default_commission_pct'));
        $this->assertEquals('2.5', Setting::get('payment_gateway_fee_pct'));
        $this->assertEquals('50', Setting::get('min_product_price'));
        $this->assertEquals('75', Setting::get('max_discount_percentage'));
    }

    /**
     * Test 6: Decimal-safe financial breakdown calculation.
     */
    public function test_decimal_safe_financial_calculation_breakdown(): void
    {
        // ₹1,499.00 item, 10% commission + 18% GST on commission + 2% gateway fee
        $breakdown = $this->commissionService->calculate(1499.00);

        // Pre-tax commission = 10% of 1499 = 149.90
        $this->assertEquals(149.90, $breakdown['commission_pre_tax']);

        // Tax on commission = 18% of 149.90 = 26.98
        $this->assertEquals(26.98, $breakdown['tax_on_commission']);

        // Total platform deduction = 149.90 + 26.98 = 176.88
        $this->assertEquals(176.88, $breakdown['total_commission']);

        // Gateway fee = 2% of 1499 = 29.98
        $this->assertEquals(29.98, $breakdown['gateway_fee']);

        // Net payable = 1499 - 176.88 - 29.98 = 1292.14
        $this->assertEquals(1292.14, $breakdown['net_payable']);
    }

    /**
     * Test 7: Admin can view price change audit history and export financial ledger report.
     */
    public function test_admin_can_view_price_history_and_export_financial_report(): void
    {
        // 1. Create a price change history entry
        ProductPriceHistory::create([
            'product_variant_id' => $this->variant->id,
            'seller_id' => $this->seller->id,
            'user_id' => $this->admin->id,
            'old_price' => 2000.00,
            'new_price' => 1799.00,
            'old_mrp' => 3999.00,
            'new_mrp' => 3999.00,
            'change_type' => 'manual',
            'reason' => 'Seasonal holiday flash promotion markdown.',
        ]);

        $responsePriceHistory = $this->actingAs($this->admin)->get(route('admin.commissions.index', ['tab' => 'price_history']));
        $responsePriceHistory->assertOk();
        $responsePriceHistory->assertSee('Nexus Pro Wireless Earbuds');
        $responsePriceHistory->assertSee('NEX-EAR-001');
        $responsePriceHistory->assertSee('1,799.00');

        // 2. Export financial report CSV
        $responseCsv = $this->actingAs($this->admin)->get(route('admin.commissions.export_report'));
        $responseCsv->assertOk();
        $this->assertTrue(str_contains((string) $responseCsv->headers->get('content-type'), 'text/csv'));
    }
}
