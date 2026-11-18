<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMarketplaceReportsAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $financeManager;
    protected User $operationsAdmin;
    protected User $customerUser;

    protected Seller $sellerA;
    protected Seller $sellerB;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Admins
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@allzone.in']);
        $this->superAdmin->assignRole('super_admin');

        $this->financeManager = User::factory()->create(['name' => 'Finance Chief', 'email' => 'finance@allzone.in']);
        $this->financeManager->assignRole('finance_manager');

        $this->operationsAdmin = User::factory()->create(['name' => 'Ops Chief', 'email' => 'ops@allzone.in']);
        $this->operationsAdmin->assignRole('operations_admin');

        // Customer
        $customerUser = User::factory()->create(['name' => 'John Customer', 'email' => 'john@customer.test']);
        $customerUser->assignRole('customer');
        $this->customerUser = $customerUser;
        $this->customer = Customer::create(['user_id' => $customerUser->id, 'phone' => '9876543210']);

        // Sellers
        $sellerUserA = User::factory()->create(['name' => 'Seller One']);
        $this->sellerA = Seller::factory()->create([
            'user_id' => $sellerUserA->id,
            'business_name' => 'Apex Electronics Hub',
            'status' => SellerStatus::APPROVED,
        ]);

        $sellerUserB = User::factory()->create(['name' => 'Seller Two']);
        $this->sellerB = Seller::factory()->create([
            'user_id' => $sellerUserB->id,
            'business_name' => 'Fresh Dairy & Bakes',
            'status' => SellerStatus::APPROVED,
        ]);

        // Category
        $this->category = Category::create([
            'name' => 'Electronics & Cooling',
            'slug' => 'electronics-cooling',
            'is_active' => true,
        ]);

        // Product & Variant
        $this->product = Product::factory()->create([
            'seller_id' => $this->sellerA->id,
            'category_id' => $this->category->id,
            'title' => 'Inverter Split AC 1.5 Ton',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'AC-15T-INV',
            'selling_price' => 32000.00,
        ]);

        // Store Location & Inventory
        $location = \App\Models\InventoryLocation::create([
            'type' => 'warehouse',
            'seller_id' => $this->sellerA->id,
            'name' => 'Main Electronics Hub',
            'is_active' => true,
        ]);

        Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 15,
            'quantity_reserved' => 2,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_customer_is_forbidden(): void
    {
        $this->actingAs($this->customerUser)->get(route('admin.reports.index'))->assertStatus(403);
    }

    public function test_all_14_marketplace_reports_render_cleanly_with_kpi_strip(): void
    {
        // Setup realistic multi-vendor order
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-REP-1001',
            'subtotal' => 32000.00,
            'tax' => 5760.00,
            'delivery_fee' => 500.00,
            'discount' => 1000.00,
            'total' => 37260.00,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'status' => OrderStatus::DELIVERED,
            'address_snapshot' => ['name' => 'John Customer', 'city' => 'Mumbai'],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'PKG-REP-1001',
            'seller_id' => $this->sellerA->id,
            'fulfilment_type' => 'standard',
            'status' => 'delivered',
            'subtotal' => 32000.00,
            'commission_amount' => 3200.00,
            'seller_payable' => 28800.00,
        ]);

        $item = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Inverter Split AC 1.5 Ton',
            'sku' => 'AC-15T-INV',
            'qty' => 1,
            'price' => 32000.00,
            'tax' => 5760.00,
            'total' => 32000.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'method' => 'card',
            'amount' => 37260.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => 'reconciled',
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'order_id' => $order->id,
            'base_amount' => 32000.00,
            'rate' => 10.00,
            'commission' => 3200.00,
            'tax_on_commission' => 576.00,
            'net_payable' => 28224.00,
        ]);

        SellerSettlement::create([
            'seller_id' => $this->sellerA->id,
            'settlement_number' => 'SETTLE-REP-1001',
            'period_start' => now()->subDays(14),
            'period_end' => now(),
            'gross' => 32000.00,
            'commission' => 3200.00,
            'adjustments' => 0.00,
            'net' => 28800.00,
            'status' => SettlementStatus::PAID,
            'reference' => 'UTR9988776655',
            'paid_at' => now(),
        ]);

        $orderReturn = OrderReturn::create([
            'order_item_id' => $item->id,
            'return_number' => 'RET-REP-1001',
            'type' => 'customer_return',
            'reason' => 'Damaged in cargo transit',
            'status' => 'refunded',
            'refund_amount' => 500.00,
            'refund_mode' => 'original',
        ]);

        Refund::create([
            'payment_id' => $payment->id,
            'fulfilment_order_id' => $fo->id,
            'amount' => 500.00,
            'reason' => 'Damaged transit refund',
            'status' => 'processed',
            'gateway_refund_id' => 'rfnd_sample_123',
            'processed_at' => now(),
        ]);

        SellerClaim::create([
            'seller_id' => $this->sellerA->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'order_return_id' => $orderReturn->id,
            'claim_number' => 'CLM-REP-1001',
            'claim_type' => 'rto_damage',
            'reason' => 'AC compressor dented by courier',
            'description' => 'Evidence attached',
            'claimed_amount' => 1200.00,
            'approved_amount' => 1200.00,
            'status' => 'approved',
        ]);

        Campaign::create([
            'name' => 'Summer Chill Fest 2026',
            'slug' => 'summer-chill-fest-2026',
            'type' => 'sale',
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'usage_count' => 18,
        ]);

        $allReports = [
            'sales',
            'orders',
            'sellers',
            'customers',
            'products',
            'categories',
            'inventory',
            'returns',
            'claims',
            'payments',
            'refunds',
            'commission',
            'settlements',
            'promotions',
            'tax',
        ];

        foreach ($allReports as $reportTab) {
            $response = $this->actingAs($this->superAdmin)->get(route('admin.reports.index', ['tab' => $reportTab]));
            $response->assertStatus(200);
            $response->assertViewIs('admin.reports.index');
            $response->assertSee('Business Intelligence &amp; Reports', false);
            $response->assertSee('Gross GMV');
            $response->assertSee('Net Sales');
            $response->assertSee('Collected Payments');
        }
    }

    public function test_financial_reconciliation_avoids_double_counting_split_orders(): void
    {
        // Order with 2 split packages (Seller A and Seller B)
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-SPLIT-999',
            'subtotal' => 10000.00,
            'tax' => 1800.00,
            'delivery_fee' => 100.00,
            'total' => 11900.00,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'status' => OrderStatus::DELIVERED,
            'address_snapshot' => ['name' => 'John Customer'],
        ]);

        // Package 1: Seller A (6,000)
        FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'PKG-SPLIT-A',
            'seller_id' => $this->sellerA->id,
            'fulfilment_type' => 'standard',
            'status' => 'delivered',
            'subtotal' => 6000.00,
            'commission_amount' => 600.00,
            'seller_payable' => 5400.00,
        ]);

        // Package 2: Seller B (4,000)
        FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'PKG-SPLIT-B',
            'seller_id' => $this->sellerB->id,
            'fulfilment_type' => 'standard',
            'status' => 'delivered',
            'subtotal' => 4000.00,
            'commission_amount' => 400.00,
            'seller_payable' => 3600.00,
        ]);

        // Verified captured payment
        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'method' => 'upi',
            'amount' => 11900.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => 'reconciled',
        ]);

        $service = app(\App\Services\Admin\AdminReportService::class);
        $summary = $service->getSummaryMetrics([]);

        // Gross GMV must be 11,900.00 (the single order total, NOT multiplied by 2 packages)
        $this->assertEquals(11900.00, $summary['grossGmv']);

        // Seller payable must equal package 1 + package 2 = 5,400 + 3,600 = 9,000.00
        $this->assertEquals(9000.00, $summary['sellerPayable']);

        // Collected payment must be 11,900.00
        $this->assertEquals(11900.00, $summary['collectedPayments']);
    }

    public function test_multi_dimensional_filtering(): void
    {
        // Create an order item for the product
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'order_number' => 'ORD-FILTER-TEST',
            'subtotal' => 32000.00,
            'tax' => 5760.00,
            'delivery_fee' => 500.00,
            'total' => 38260.00,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'status' => OrderStatus::DELIVERED,
            'address_snapshot' => ['name' => 'John Customer'],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'PKG-FILTER-TEST',
            'seller_id' => $this->sellerA->id,
            'fulfilment_type' => 'standard',
            'status' => 'delivered',
            'subtotal' => 32000.00,
            'commission_amount' => 3200.00,
            'seller_payable' => 28800.00,
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Inverter Split AC 1.5 Ton',
            'sku' => 'AC-15T-INV',
            'qty' => 1,
            'price' => 32000.00,
            'tax' => 5760.00,
            'total' => 32000.00,
        ]);

        // Filter by Seller A
        $resA = $this->actingAs($this->superAdmin)->get(route('admin.reports.index', [
            'tab' => 'sellers',
            'seller_id' => $this->sellerA->id,
        ]));
        $resA->assertStatus(200);
        $resA->assertSee('Apex Electronics Hub');

        // Filter by Category
        $resCat = $this->actingAs($this->superAdmin)->get(route('admin.reports.index', [
            'tab' => 'products',
            'category_id' => $this->category->id,
        ]));
        $resCat->assertStatus(200);
        $resCat->assertSee('Inverter Split AC 1.5 Ton');

        // Filter by Stock Status (low_stock)
        $resStock = $this->actingAs($this->superAdmin)->get(route('admin.reports.index', [
            'tab' => 'inventory',
            'stock_status' => 'in_stock',
        ]));
        $resStock->assertStatus(200);
        $resStock->assertSee('AC-15T-INV');
    }

    public function test_csv_export_returns_streamed_file_with_bom(): void
    {
        $types = ['sales', 'orders', 'sellers', 'customers', 'products', 'inventory', 'returns', 'claims', 'payments', 'refunds', 'settlements'];

        foreach ($types as $type) {
            $export = $this->actingAs($this->superAdmin)->get(route('admin.reports.export', [
                'type' => $type,
                'format' => 'csv',
            ]));

            $export->assertStatus(200);
            $this->assertStringContainsString('text/csv', strtolower((string) $export->headers->get('content-type')));
            $content = $export->streamedContent();
            $this->assertNotEmpty($content);
        }
    }

    public function test_pdf_export_returns_downloadable_pdf_document(): void
    {
        $res = $this->actingAs($this->superAdmin)->get(route('admin.reports.export.pdf', [
            'type' => 'sales',
        ]));

        $res->assertStatus(200);
        $this->assertStringContainsString('application/pdf', strtolower((string) $res->headers->get('content-type')));
    }

    public function test_xlsx_export_returns_valid_excel_download(): void
    {
        $res = $this->actingAs($this->superAdmin)->get(route('admin.reports.export.xlsx', [
            'type' => 'sellers',
        ]));

        $res->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', strtolower((string) $res->headers->get('content-type')));
    }
}
