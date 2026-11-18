<?php

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ReturnStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customerUser;
    protected Customer $customer;
    protected Seller $seller;
    protected Store $store;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_orders_' . uniqid() . '@example.com',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->customerUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.orders@example.com',
            'phone' => '+919876543210',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
        ]);

        $sellerUser = User::factory()->create([
            'email' => 'seller_orders_' . uniqid() . '@example.com',
            'status' => 'active',
        ]);

        $this->seller = Seller::create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Metro Superstore',
            'legal_name' => 'Metro Superstore Retail Pvt Ltd',
            'slug' => 'metro-superstore-' . uniqid(),
            'status' => 'approved',
        ]);

        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'Metro Store Indiranagar',
            'slug' => 'metro-indiranagar-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Groceries',
            'slug' => 'groceries-' . uniqid(),
            'commission_percent' => 5.0,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'Organic Apples',
            'slug' => 'organic-apples-' . uniqid(),
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'SKU-APPLES-001',
            'name' => '1kg Pack',
            'mrp' => 700.00,
            'selling_price' => 600.00,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    protected function createSampleOrder(array $attributes = [], array $foAttributes = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $this->customer->id,
            'address_snapshot' => [
                'name' => 'John Doe',
                'phone' => '+919876543210',
                'line1' => '100 Feet Road, Indiranagar',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
            ],
            'subtotal' => 1200.00,
            'discount' => 100.00,
            'delivery_fee' => 40.00,
            'tax' => 60.00,
            'total' => 1200.00,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::PROCESSING,
        ], $attributes));

        $fo = FulfilmentOrder::create(array_merge([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'fulfilment_type' => FulfilmentType::QUICK,
            'subtotal' => 1200.00,
            'commission_amount' => 120.00,
            'seller_payable' => 1080.00,
            'delivery_fee' => 40.00,
            'status' => 'processing',
        ], $foAttributes));

        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Sample Organic Apples',
            'sku' => 'SKU-APPLES-001',
            'qty' => 2,
            'price' => 600.00,
            'total' => 1200.00,
        ]);

        return $order;
    }

    public function test_admin_can_view_orders_index_with_metrics(): void
    {
        $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);
        $this->createSampleOrder(['status' => OrderStatus::DELIVERED]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('Orders &amp; Fulfilment Central', false);
        $response->assertSee('Total Orders');
        $response->assertSee('Gross Merchandise Value');
        $response->assertSee('Quick Commerce');
    }

    public function test_admin_can_view_all_10_order_tabs_with_accurate_counts(): void
    {
        // Create orders across various statuses
        $this->createSampleOrder(['status' => OrderStatus::PENDING]);
        $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);
        $this->createSampleOrder(['status' => OrderStatus::READY_FOR_PICKUP]);
        $this->createSampleOrder(['status' => OrderStatus::OUT_FOR_DELIVERY]);
        $this->createSampleOrder(['status' => OrderStatus::DELIVERED]);
        $this->createSampleOrder(['status' => OrderStatus::CANCELLED]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('All Orders');
        $response->assertSee('Pending');
        $response->assertSee('Processing');
        $response->assertSee('Ready to Ship');
        $response->assertSee('Shipped');
        $response->assertSee('Delivered');
        $response->assertSee('Cancelled');
        $response->assertSee('Return Requested');
        $response->assertSee('Returned');
        $response->assertSee('RTO');
    }

    public function test_admin_can_filter_orders_by_status_and_payment(): void
    {
        $order1 = $this->createSampleOrder([
            'order_number' => 'ORD-FILTER-1',
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $order2 = $this->createSampleOrder([
            'order_number' => 'ORD-FILTER-2',
            'status' => OrderStatus::CANCELLED,
            'payment_status' => PaymentStatus::REFUNDED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ORD-FILTER-1');
        $response->assertDontSee('ORD-FILTER-2');
    }

    public function test_admin_can_filter_orders_by_fulfilment_type(): void
    {
        $quickOrder = $this->createSampleOrder(
            ['order_number' => 'ORD-QUICK-99'],
            ['fulfilment_type' => FulfilmentType::QUICK]
        );

        $standardOrder = $this->createSampleOrder(
            ['order_number' => 'ORD-STD-99'],
            ['fulfilment_type' => FulfilmentType::STANDARD]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'fulfilment_type' => 'quick',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ORD-QUICK-99');
        $response->assertDontSee('ORD-STD-99');
    }

    public function test_admin_can_filter_by_seller_and_date_range(): void
    {
        $seller2User = User::factory()->create(['status' => 'active']);
        $seller2 = Seller::create([
            'user_id' => $seller2User->id,
            'business_name' => 'Apex Electronics',
            'legal_name' => 'Apex Electronics Pvt Ltd',
            'slug' => 'apex-electronics-' . uniqid(),
            'status' => 'approved',
        ]);
        $store2 = Store::create([
            'seller_id' => $seller2->id,
            'name' => 'Apex Store',
            'slug' => 'apex-store-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $order1 = $this->createSampleOrder(['order_number' => 'ORD-SELLER-1']);

        $order2 = Order::create([
            'order_number' => 'ORD-SELLER-2',
            'customer_id' => $this->customer->id,
            'address_snapshot' => ['name' => 'Buyer', 'phone' => '1234567890'],
            'subtotal' => 500,
            'total' => 500,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::PROCESSING,
        ]);
        $fo2 = FulfilmentOrder::create([
            'order_id' => $order2->id,
            'seller_id' => $seller2->id,
            'store_id' => $store2->id,
            'fulfilment_order_number' => 'FO-APEX-01',
            'fulfilment_type' => FulfilmentType::STANDARD,
            'subtotal' => 500,
            'status' => 'processing',
        ]);
        OrderItem::create([
            'fulfilment_order_id' => $fo2->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Other Item',
            'sku' => 'SKU-OTHER',
            'qty' => 1,
            'price' => 500,
            'total' => 500,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'seller_id' => $this->seller->id,
            'date_range' => 'today',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ORD-SELLER-1');
        $response->assertDontSee('ORD-SELLER-2');
    }

    public function test_admin_can_search_orders_by_number_or_customer(): void
    {
        $order1 = $this->createSampleOrder(['order_number' => 'ORD-UNIQUE-SEARCH-999']);
        $order2 = $this->createSampleOrder(['order_number' => 'ORD-OTHER-000']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index', [
            'search' => 'UNIQUE-SEARCH',
        ]));

        $response->assertStatus(200);
        $response->assertSee('ORD-UNIQUE-SEARCH-999');
        $response->assertDontSee('ORD-OTHER-000');
    }

    public function test_admin_can_export_orders_to_csv(): void
    {
        $order = $this->createSampleOrder(['order_number' => 'ORD-CSV-EXPORT-123']);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.export'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'attachment; filename='));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Order Number', $content);
        $this->assertStringContainsString('Date Placed', $content);
        $this->assertStringContainsString('ORD-CSV-EXPORT-123', $content);
    }

    public function test_admin_can_view_order_show_console(): void
    {
        $order = $this->createSampleOrder();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee($order->order_number);
        $response->assertSee('Customer &amp; Shipping Profile', false);
        $response->assertSee('Payment &amp; Gateway Audit', false);
        $response->assertSee('Multi-Vendor Packages');
        $response->assertSee('Metro Superstore');
        $response->assertSee('Sample Organic Apples');
    }

    public function test_admin_can_override_order_status_with_reason(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.override_status', $order), [
            'status' => 'delivered',
            'reason' => 'Customer confirmed delivery in person at store counter.',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals(OrderStatus::DELIVERED, $order->status);

        // Verify history logged
        $history = $order->statusHistory()->latest()->first();
        $this->assertNotNull($history);
        $this->assertStringContainsString('[ADMIN OVERRIDE]', $history->note);
    }

    public function test_admin_cannot_override_status_without_reason(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.override_status', $order), [
            'status' => 'delivered',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
        $order->refresh();
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);
    }

    public function test_admin_can_cancel_order_with_mandatory_reason(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), [
            'reason' => 'Suspected fraudulent transaction identified during fraud audit review.',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertStringContainsString('Suspected fraudulent transaction', $order->notes);

        // Sub-orders should be cancelled
        foreach ($order->fulfilmentOrders as $fo) {
            $this->assertEquals('cancelled', $fo->status);
        }
    }

    public function test_admin_cannot_cancel_order_without_reason(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.cancel', $order), [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
        $order->refresh();
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);
    }

    public function test_admin_can_cancel_specific_sub_order_package(): void
    {
        $order = $this->createSampleOrder(['status' => OrderStatus::PROCESSING]);
        $fo = $order->fulfilmentOrders()->first();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.cancel_sub_order', $fo), [
            'reason' => 'Vendor inventory damaged in warehouse prior to dispatch.',
        ]);

        $response->assertRedirect();
        $fo->refresh();
        $this->assertEquals('cancelled', $fo->status);
        $this->assertNotNull($fo->cancelled_at);
    }

    public function test_admin_can_resolve_order_dispute(): void
    {
        $order = $this->createSampleOrder();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.resolve_dispute', $order), [
            'resolution' => 'Merchant and buyer agreed on partial goodwill voucher replacement.',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertStringContainsString('DISPUTE RESOLVED', $order->notes);
        $this->assertStringContainsString('partial goodwill voucher', $order->notes);
    }

    public function test_admin_can_approve_customer_return(): void
    {
        $order = $this->createSampleOrder();
        $fo = $order->fulfilmentOrders()->first();
        $item = $fo->items()->first();

        $return = OrderReturn::create([
            'return_number' => 'RMA-TEST-001',
            'type' => 'return',
            'order_item_id' => $item->id,
            'reason' => 'Defective or expired item',
            'status' => ReturnStatus::REQUESTED,
            'refund_amount' => 600.00,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.orders.approve_return', [$order, $return]));

        $response->assertRedirect();
        $return->refresh();
        $this->assertEquals(ReturnStatus::APPROVED, $return->status);
        $this->assertNotNull($return->approved_at);
    }

    public function test_admin_can_add_internal_note(): void
    {
        $order = $this->createSampleOrder();

        $response = $this->actingAs($this->admin)->post(route('admin.orders.add_note', $order), [
            'note' => 'Spoke with delivery supervisor regarding road construction delay.',
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertStringContainsString('road construction delay', $order->notes);
    }

    public function test_admin_can_download_tax_invoice(): void
    {
        $order = $this->createSampleOrder();

        $response = $this->actingAs($this->admin)->get(route('admin.orders.invoice', $order));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'application/pdf'));
    }
}
