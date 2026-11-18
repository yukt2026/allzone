<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ReturnStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RtoCase;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use App\Models\Shipment;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Seller $activeSeller;
    protected Product $sampleProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@allzone.test',
            'name' => 'Super Administrator',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $sellerUser = User::factory()->create(['name' => 'Top Merchant User']);
        $this->activeSeller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Apex Retailers',
            'status' => SellerStatus::APPROVED,
            'approved_at' => Carbon::now()->subDays(5),
        ]);

        $category = Category::factory()->create(['name' => 'Electronics']);
        $brand = Brand::factory()->create(['name' => 'ApexBrand']);

        $this->sampleProduct = Product::factory()->create([
            'seller_id' => $this->activeSeller->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'title' => 'Wireless Noise Cancelling Headphones',
            'status' => ProductStatus::ACTIVE,
        ]);
    }

    public function test_super_admin_can_access_dashboard_and_see_platform_overview_metrics(): void
    {
        // 1. Create customer
        $custUser = User::factory()->create(['name' => 'Jane Buyer']);
        Customer::factory()->create(['user_id' => $custUser->id]);

        // 2. Create pending seller
        $pendingUser = User::factory()->create();
        Seller::factory()->create([
            'user_id' => $pendingUser->id,
            'business_name' => 'Pending Onboarding Store',
            'status' => SellerStatus::PENDING,
        ]);

        // 3. Create pending product
        Product::factory()->create([
            'seller_id' => $this->activeSeller->id,
            'title' => 'Unreviewed Smart Watch',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        // 4. Create an order with items & fulfilment
        $order = Order::factory()->create([
            'total' => 4500.00,
            'status' => OrderStatus::DELIVERED,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $this->sampleProduct->id,
            'selling_price' => 4500.00,
        ]);

        $fo = FulfilmentOrder::create([
            'fulfilment_order_number' => 'FO-TEST-001',
            'order_id' => $order->id,
            'seller_id' => $this->activeSeller->id,
            'subtotal' => 4500.00,
            'shipping_fee' => 100.00,
            'tax' => 200.00,
            'discount' => 0.00,
            'total' => 4800.00,
            'commission_amount' => 450.00,
            'seller_payable' => 4050.00,
            'status' => OrderStatus::DELIVERED,
            'fulfilment_type' => FulfilmentType::STANDARD,
        ]);
        $fo->created_at = Carbon::now()->subDays(2);
        $fo->save();

        $orderItem = OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->sampleProduct->id,
            'variant_id' => $variant->id,
            'qty' => 1,
            'price' => 4500.00,
            'total' => 4500.00,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        CommissionEntry::create([
            'seller_id' => $this->activeSeller->id,
            'order_id' => $order->id,
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 4500.00,
            'commission_rate' => 10.0,
            'commission' => 450.00,
            'tax_on_commission' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 4050.00,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // 5. Create pending settlement
        SellerSettlement::create([
            'seller_id' => $this->activeSeller->id,
            'settlement_number' => 'SET-TEST-001',
            'period_start' => Carbon::now()->subDays(7),
            'period_end' => Carbon::now(),
            'gross' => 4500.00,
            'commission' => 450.00,
            'net' => 4050.00,
            'status' => SettlementStatus::PENDING,
        ]);

        // 6. Create Return
        OrderReturn::create([
            'return_number' => 'RET-TEST-001',
            'type' => 'return',
            'order_item_id' => $orderItem->id,
            'reason' => 'defective',
            'status' => ReturnStatus::REQUESTED,
        ]);

        // 7. Create Support Ticket
        $ticketCategory = SupportCategory::create([
            'name' => 'Order Logistics',
            'slug' => 'order-logistics',
        ]);
        SupportTicket::create([
            'ticket_number' => 'TCK-TEST-001',
            'user_id' => $custUser->id,
            'category_id' => $ticketCategory->id,
            'subject' => 'Delayed package delivery',
            'message' => 'Please provide updated ETA',
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::HIGH,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');

        // Check key elements on page
        $response->assertSee('Central Operations & Command Hub', false);
        $response->assertSee('Admin Quick Actions');
        $response->assertSee('Total GMV');
        $response->assertSee('Total Orders');
        $response->assertSee('Commission Earned');
        $response->assertSee('Active Sellers');
        $response->assertSee('Top-Selling Products');
        $response->assertSee('Top 10 Merchants by Sales Volume');
        $response->assertSee('Apex Retailers');
        $response->assertSee('Wireless Noise Cancelling Headphones');

        // Check view data
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_customers'] >= 1
                && $stats['total_sellers'] >= 2
                && $stats['active_sellers']['value'] >= 1
                && $stats['pending_seller_approvals']['value'] >= 1
                && $stats['total_products'] >= 2
                && $stats['pending_product_reviews']['value'] >= 1
                && $stats['returns_count'] >= 1
                && $stats['open_tickets'] >= 1;
        });
    }

    public function test_quick_action_cards_and_badges_render_correctly(): void
    {
        // 1 pending seller
        $pendingUser = User::factory()->create();
        Seller::factory()->create([
            'user_id' => $pendingUser->id,
            'business_name' => 'New Seller Queue',
            'status' => SellerStatus::PENDING,
        ]);

        // 1 pending product
        Product::factory()->create([
            'seller_id' => $this->activeSeller->id,
            'title' => 'Pending Verification Watch',
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        // 1 open dispute (claim)
        $order = Order::factory()->create();
        SellerClaim::create([
            'claim_number' => 'CLM-TEST-001',
            'seller_id' => $this->activeSeller->id,
            'order_id' => $order->id,
            'claim_type' => ClaimType::RETURN_WRONG_ITEM,
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 500.00,
            'reason' => 'Customer sent back an empty box',
            'description' => 'Customer sent back an empty package with missing items',
        ]);

        // 1 pending settlement
        SellerSettlement::create([
            'seller_id' => $this->activeSeller->id,
            'settlement_number' => 'SET-TEST-002',
            'period_start' => Carbon::now()->subDays(7),
            'period_end' => Carbon::now(),
            'gross' => 1000.00,
            'commission' => 100.00,
            'net' => 900.00,
            'status' => SettlementStatus::PENDING,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();

        // Verify quick actions links
        $response->assertSee(route('admin.sellers.index', ['status' => 'pending']));
        $response->assertSee(route('admin.catalogue.moderation.index'));
        $response->assertSee(route('admin.orders.index'));
        $response->assertSee(route('admin.claims.index'));
        $response->assertSee(route('admin.campaigns.index'));
        $response->assertSee(route('admin.announcements.create'));
        $response->assertSee(route('admin.settlements.index'));

        // Verify counters
        $response->assertViewHas('quickActions', function ($quick) {
            return $quick['pending_sellers'] >= 1
                && $quick['pending_products'] >= 1
                && $quick['open_disputes'] >= 1
                && $quick['pending_settlements'] >= 1;
        });
    }

    public function test_range_filter_yesterday_calculates_correct_db_aggregates(): void
    {
        // Order placed yesterday
        $yesterdayOrder = Order::factory()->create([
            'total' => 2500.00,
            'status' => OrderStatus::DELIVERED,
        ]);
        $yesterdayOrder->created_at = Carbon::yesterday()->startOfDay()->addHours(10);
        $yesterdayOrder->save();

        // Order placed 5 days ago (should not be in yesterday's count)
        $oldOrder = Order::factory()->create([
            'total' => 8000.00,
            'status' => OrderStatus::DELIVERED,
        ]);
        $oldOrder->created_at = Carbon::now()->subDays(5);
        $oldOrder->save();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['range' => 'yesterday']));

        $response->assertOk();
        $response->assertViewHas('activeRange', 'yesterday');

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_gmv']['value'] === 2500.00
                && $stats['total_orders']['value'] === 1;
        });
    }

    public function test_custom_date_range_calculates_correct_db_aggregates(): void
    {
        $startDate = Carbon::now()->subDays(12)->format('Y-m-d');
        $endDate = Carbon::now()->subDays(10)->format('Y-m-d');

        // Target order inside custom range
        $targetOrder = Order::factory()->create([
            'total' => 3200.00,
            'status' => OrderStatus::DELIVERED,
        ]);
        $targetOrder->created_at = Carbon::now()->subDays(11);
        $targetOrder->save();

        // Order outside custom range
        $outsideOrder = Order::factory()->create([
            'total' => 5000.00,
            'status' => OrderStatus::DELIVERED,
        ]);
        $outsideOrder->created_at = Carbon::now()->subDays(2);
        $outsideOrder->save();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', [
            'range' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertViewHas('activeRange', 'custom');
        $response->assertViewHas('startDate', $startDate);
        $response->assertViewHas('endDate', $endDate);

        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_gmv']['value'] === 3200.00
                && $stats['total_orders']['value'] === 1;
        });
    }

    public function test_top_selling_products_are_aggregated_from_order_items(): void
    {
        $order = Order::factory()->create([
            'total' => 6000.00,
            'status' => OrderStatus::DELIVERED,
        ]);
        $order->created_at = Carbon::now()->subDays(1);
        $order->save();

        $variant = ProductVariant::factory()->create([
            'product_id' => $this->sampleProduct->id,
            'selling_price' => 3000.00,
        ]);

        $fo = FulfilmentOrder::create([
            'fulfilment_order_number' => 'FO-TEST-002',
            'order_id' => $order->id,
            'seller_id' => $this->activeSeller->id,
            'subtotal' => 6000.00,
            'shipping_fee' => 0.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 6000.00,
            'commission_amount' => 600.00,
            'seller_payable' => 5400.00,
            'status' => OrderStatus::DELIVERED,
            'fulfilment_type' => FulfilmentType::STANDARD,
        ]);
        $fo->created_at = Carbon::now()->subDays(1);
        $fo->save();

        $item = OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->sampleProduct->id,
            'variant_id' => $variant->id,
            'qty' => 2,
            'price' => 3000.00,
            'total' => 6000.00,
        ]);
        $item->created_at = Carbon::now()->subDays(1);
        $item->save();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard', ['range' => '7d']));

        $response->assertOk();
        $response->assertViewHas('topProducts', function ($products) {
            return count($products) >= 1
                && $products[0]['title'] === 'Wireless Noise Cancelling Headphones'
                && $products[0]['units_sold'] === 2
                && $products[0]['revenue'] === 6000.00;
        });
    }

    public function test_chart_data_endpoint_supports_all_required_charts(): void
    {
        $response = $this->actingAs($this->superAdmin)->getJson(route('admin.dashboard.chart_data', [
            'range' => '7d',
            'chart' => 'all',
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'range',
            'chart',
            'data' => [
                'stats',
                'sales_orders',
                'order_types',
                'category_revenue',
                'top_sellers',
                'top_products',
                'status_funnel',
            ],
        ]);
    }

    public function test_dashboard_summary_csv_exports_platform_kpis_and_top_products(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard.export', ['range' => '7d']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Executive Dashboard Summary', $content);
        $this->assertStringContainsString('Total Platform GMV', $content);
        $this->assertStringContainsString('Total Order Count', $content);
        $this->assertStringContainsString('Customer Returns Count', $content);
        $this->assertStringContainsString('Courier RTO Count', $content);
        $this->assertStringContainsString('Seller Payable (INR)', $content);
        $this->assertStringContainsString('TOP-SELLING PRODUCTS', $content);
    }

    public function test_unauthorized_users_are_forbidden_from_super_admin_dashboard(): void
    {
        // Unauthenticated
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        // Customer
        $customerUser = User::factory()->create();
        $customerUser->assignRole('customer');
        $this->actingAs($customerUser)->get(route('admin.dashboard'))->assertStatus(403);

        // Seller
        $sellerUser = User::factory()->create();
        Seller::factory()->create(['user_id' => $sellerUser->id]);
        $this->actingAs($sellerUser)->get(route('admin.dashboard'))->assertStatus(403);
    }
}
