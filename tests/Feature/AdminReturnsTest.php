<?php

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\InventoryLocationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QCStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\SellerStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReturnsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customerUser;
    protected Customer $customer;
    protected CustomerAddress $address;
    protected Seller $seller;
    protected Store $store;
    protected InventoryLocation $location;
    protected Product $product;
    protected ProductVariant $variant;
    protected Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_returns_' . uniqid() . '@example.com',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->customerUser = User::factory()->create([
            'name' => 'Neha Gupta',
            'email' => 'neha.returns@example.com',
            'wallet_balance' => 0.00,
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
        ]);
        $this->address = CustomerAddress::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'name' => 'Neha Gupta',
            'phone' => '9876543210',
            'line1' => 'Flat 101, Lake View',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'pincode' => '110001',
            'is_default' => true,
        ]);

        $sellerUser = User::factory()->create();
        $this->seller = Seller::create([
            'user_id' => $sellerUser->id,
            'company_name' => 'Tech Retailers Hub',
            'business_name' => 'Tech Hub Online',
            'legal_name' => 'Tech Hub Retailers Pvt Ltd',
            'slug' => 'tech-hub-online',
            'status' => SellerStatus::APPROVED,
        ]);
        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'Tech Store Delhi',
            'slug' => 'tech-store-delhi',
            'status' => StoreStatus::ACTIVE,
            'delivery_radius_km' => 15.0,
        ]);
        $this->location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'name' => 'Delhi Main Hub',
            'code' => 'DEL-01',
            'type' => InventoryLocationType::WAREHOUSE,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Smart Gadgets',
            'slug' => 'smart-gadgets',
            'is_active' => true,
        ]);
        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'Smart Fitness Band 5',
            'slug' => 'smart-fitness-band-5',
            'is_active' => true,
            'is_returnable' => true,
            'return_window_days' => 7,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'SFB-05-BLK',
            'name' => 'Black Strap',
            'selling_price' => 1499.00,
            'mrp' => 1999.00,
            'cost_price' => 900.00,
            'is_active' => true,
        ]);
        $this->inventory = Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 15,
            'quantity_reserved' => 0,
            'reorder_threshold' => 2,
        ]);
    }

    protected function createReturn(ReturnStatus $status = ReturnStatus::REQUESTED, QCStatus $qc = QCStatus::PENDING): OrderReturn
    {
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'customer_id' => $this->customer->id,
            'customer_address_id' => $this->address->id,
            'address_snapshot' => ['name' => 'Neha Gupta', 'phone' => '9876543210'],
            'subtotal' => 1499.00,
            'tax' => 0.00,
            'delivery_fee' => 0.00,
            'discount' => 0.00,
            'total' => 1499.00,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->customerUser->id,
            'amount' => 1499.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::UPI,
            'gateway' => 'razorpay',
            'gateway_transaction_id' => 'pay_test_' . uniqid(),
            'status' => PaymentStatus::PAID,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'inventory_location_id' => $this->location->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => OrderStatus::DELIVERED,
            'subtotal' => 1499.00,
            'commission_amount' => 149.90,
            'delivered_at' => Carbon::now()->subDays(2),
        ]);

        $item = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => $this->product->title,
            'variant_name' => $this->variant->name,
            'sku' => $this->variant->sku,
            'price' => 1499.00,
            'qty' => 1,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 1499.00,
        ]);

        return OrderReturn::create([
            'return_number' => 'RET-' . strtoupper(uniqid()),
            'order_item_id' => $item->id,
            'user_id' => $this->customerUser->id,
            'qty' => 1,
            'reason' => 'Defective battery backup',
            'customer_notes' => 'Battery drains within 2 hours',
            'refund_amount' => 1499.00,
            'refund_mode' => 'wallet',
            'status' => $status,
            'qc_status' => $qc,
        ]);
    }

    protected function createCodReturn(ReturnStatus $status = ReturnStatus::RECEIVED, QCStatus $qc = QCStatus::PASSED): OrderReturn
    {
        $order = Order::create([
            'order_number' => 'ORD-COD-' . strtoupper(uniqid()),
            'customer_id' => $this->customer->id,
            'customer_address_id' => $this->address->id,
            'address_snapshot' => ['name' => 'Neha Gupta', 'phone' => '9876543210'],
            'subtotal' => 1499.00,
            'tax' => 0.00,
            'delivery_fee' => 0.00,
            'discount' => 0.00,
            'total' => 1499.00,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::COD,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $this->customerUser->id,
            'amount' => 1499.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::COD,
            'gateway' => 'cod',
            'gateway_transaction_id' => 'cash_' . uniqid(),
            'status' => PaymentStatus::PAID,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'inventory_location_id' => $this->location->id,
            'fulfilment_order_number' => 'FO-COD-' . strtoupper(uniqid()),
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => OrderStatus::DELIVERED,
            'subtotal' => 1499.00,
            'commission_amount' => 149.90,
            'delivered_at' => Carbon::now()->subDays(2),
        ]);

        $item = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => $this->product->title,
            'variant_name' => $this->variant->name,
            'sku' => $this->variant->sku,
            'price' => 1499.00,
            'qty' => 1,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 1499.00,
        ]);

        return OrderReturn::create([
            'return_number' => 'RET-COD-' . strtoupper(uniqid()),
            'order_item_id' => $item->id,
            'user_id' => $this->customerUser->id,
            'qty' => 1,
            'reason' => 'Defective battery backup',
            'customer_notes' => 'Battery drains within 2 hours',
            'refund_amount' => 1499.00,
            'refund_mode' => 'original',
            'status' => $status,
            'qc_status' => $qc,
        ]);
    }

    public function test_admin_can_view_returns_list_with_metrics(): void
    {
        $return = $this->createReturn();

        $response = $this->actingAs($this->admin)->get(route('admin.returns.index'));

        $response->assertOk();
        $response->assertSee('Returns & RMA Oversight');
        $response->assertSee($return->return_number);
        $response->assertSee('Neha Gupta');
        $response->assertSee('Smart Fitness Band 5');
    }

    public function test_admin_can_filter_returns_by_status_and_search(): void
    {
        $ret1 = $this->createReturn(ReturnStatus::REQUESTED);
        $ret2 = $this->createReturn(ReturnStatus::REJECTED);

        // Filter by status
        $response = $this->actingAs($this->admin)->get(route('admin.returns.index', ['status' => 'requested']));
        $response->assertOk();
        $response->assertSee($ret1->return_number);
        $response->assertDontSee($ret2->return_number);

        // Search by return number
        $response = $this->actingAs($this->admin)->get(route('admin.returns.index', ['search' => $ret2->return_number]));
        $response->assertOk();
        $response->assertSee($ret2->return_number);
        $response->assertDontSee($ret1->return_number);
    }

    public function test_admin_can_view_return_details_page(): void
    {
        $return = $this->createReturn();

        $response = $this->actingAs($this->admin)->get(route('admin.returns.show', $return));

        $response->assertOk();
        $response->assertSee($return->return_number);
        $response->assertSee('Dispute resolution console');
        $response->assertSee('Defective battery backup');
        $response->assertSee('Tech Hub Online');
    }

    public function test_admin_can_override_and_approve_return(): void
    {
        $return = $this->createReturn(ReturnStatus::REJECTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.override', $return), [
            'action' => 'approve',
            'notes' => 'Seller unfairly rejected valid battery defect claim',
        ]);

        $response->assertRedirect();
        $this->assertContains($return->fresh()->status, [ReturnStatus::APPROVED, ReturnStatus::REVERSE_PICKUP_SCHEDULED]);
        $this->assertStringContainsString('Seller unfairly rejected', $return->fresh()->admin_notes);
    }

    public function test_admin_can_override_and_reject_return(): void
    {
        $return = $this->createReturn(ReturnStatus::REQUESTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.override', $return), [
            'action' => 'reject',
            'notes' => 'Customer reported physical damage not covered',
        ]);

        $response->assertRedirect();
        $this->assertEquals(ReturnStatus::REJECTED, $return->fresh()->status);
        $this->assertStringContainsString('physical damage not covered', $return->fresh()->admin_notes);
    }

    public function test_admin_can_force_refund_return(): void
    {
        $return = $this->createReturn(ReturnStatus::REQUESTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.force_refund', $return), [
            'amount' => 1499.00,
            'is_resellable' => 1,
            'notes' => 'Executive override for VIP customer satisfaction',
        ]);

        $response->assertRedirect();
        $this->assertEquals(ReturnStatus::REFUNDED, $return->fresh()->status);
        $this->assertEquals(1499.00, (float) $this->customerUser->fresh()->wallet_balance);
    }

    public function test_admin_can_export_returns_csv(): void
    {
        $return = $this->createReturn();

        $response = $this->actingAs($this->admin)->get(route('admin.returns.export'));

        $response->assertOk();
        $this->assertTrue(str_contains((string) $response->headers->get('content-type'), 'text/csv'));
        $this->assertStringContainsString($return->return_number, $response->streamedContent());
        $this->assertStringContainsString('Smart Fitness Band 5', $response->streamedContent());
    }

    public function test_admin_can_approve_return_via_dedicated_endpoint(): void
    {
        $return = $this->createReturn(ReturnStatus::REQUESTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.approve', $return), [
            'notes' => 'Claim approved after reviewing customer pictures.',
            'schedule_pickup' => 1,
        ]);

        $response->assertRedirect();
        $this->assertContains($return->fresh()->status, [ReturnStatus::APPROVED, ReturnStatus::REVERSE_PICKUP_SCHEDULED]);
    }

    public function test_admin_can_reject_return_with_mandatory_remarks_via_dedicated_endpoint(): void
    {
        $return = $this->createReturn(ReturnStatus::REQUESTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.reject', $return), [
            'reason' => 'Item shows physical accidental damage not covered by warranty.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(ReturnStatus::REJECTED, $return->fresh()->status);
    }

    public function test_admin_can_request_additional_information(): void
    {
        $return = $this->createReturn(ReturnStatus::REQUESTED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.request_info', $return), [
            'instructions' => 'Please provide a clear photo of the device serial number on the strap underside.',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('device serial number', $return->fresh()->admin_notes);
    }

    public function test_admin_can_record_qc_outcome_with_resellable_flag(): void
    {
        $return = $this->createReturn(ReturnStatus::RECEIVED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.record_qc', $return), [
            'outcome' => 'passed',
            'is_resellable' => 1,
            'qc_notes' => 'Product factory reset confirmed. All accessories present and intact.',
        ]);

        $response->assertRedirect();
        $fresh = $return->fresh();
        $this->assertEquals(QCStatus::PASSED, $fresh->qc_status);
        $this->assertTrue($fresh->is_resellable);
        $this->assertNotNull($fresh->qc_completed_at);
        $this->assertStringContainsString('Product factory reset confirmed', $fresh->qc_notes);
    }

    public function test_admin_can_manually_update_return_status(): void
    {
        $return = $this->createReturn(ReturnStatus::APPROVED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.update_status', $return), [
            'status' => 'received',
            'note' => 'Arrived at Delhi regional returns processing warehouse.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(ReturnStatus::RECEIVED, $return->fresh()->status);
        $this->assertNotNull($return->fresh()->received_at);
        $this->assertStringContainsString('Arrived at Delhi regional returns', $return->fresh()->admin_notes);
    }

    public function test_cod_refund_safeguard_blocks_direct_razorpay_routing(): void
    {
        $return = $this->createCodReturn(ReturnStatus::QC_PASSED);

        // Attempting to disburse COD refund via 'original' without verified payout reference
        $response = $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'original',
            'notes' => 'Attempting refund without COD payout reference',
        ]);

        $response->assertRedirect();
        $this->assertNotEquals(ReturnStatus::REFUNDED, $return->fresh()->status);
    }

    public function test_admin_can_refund_cod_order_with_manual_bank_payout_reference(): void
    {
        $return = $this->createCodReturn(ReturnStatus::QC_PASSED);

        $response = $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'cod_payout',
            'payout_reference' => 'UTR-20261118-HDFC-991244',
            'confirm_cod_payout' => 1,
            'notes' => 'Direct IMPS payout dispatched to customer bank account.',
        ]);

        $response->assertRedirect();
        $fresh = $return->fresh();
        $this->assertEquals(ReturnStatus::REFUNDED, $fresh->status);
        $this->assertNotNull($fresh->refund_id);

        $refund = Refund::find($fresh->refund_id);
        $this->assertNotNull($refund);
        $this->assertEquals('UTR-20261118-HDFC-991244', $refund->gateway_refund_id);
    }

    public function test_admin_can_refund_cod_order_with_wallet_credit(): void
    {
        $return = $this->createCodReturn(ReturnStatus::QC_PASSED);
        $initialBalance = (float) $this->customerUser->wallet_balance;

        $response = $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'wallet',
            'notes' => 'Customer opted for immediate store credit instead of bank transfer.',
        ]);

        $response->assertRedirect();
        $fresh = $return->fresh();
        $this->assertEquals(ReturnStatus::REFUNDED, $fresh->status);
        $this->assertEquals($initialBalance + 1499.00, (float) $this->customerUser->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->customerUser->id,
            'amount' => 1499.00,
            'type' => 'credit',
        ]);
    }

    public function test_duplicate_refund_is_strictly_prevented(): void
    {
        $return = $this->createCodReturn(ReturnStatus::QC_PASSED);

        // 1st refund
        $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'wallet',
            'notes' => 'First refund disbursement.',
        ]);

        $this->assertEquals(ReturnStatus::REFUNDED, $return->fresh()->status);

        // 2nd refund attempt
        $response = $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'wallet',
            'notes' => 'Second attempt should fail.',
        ]);

        $response->assertRedirect();
        // Wallet balance must not increase again
        $this->assertEquals(1499.00, (float) $this->customerUser->fresh()->wallet_balance);
    }

    public function test_refund_restocks_inventory_when_resellable_is_enabled(): void
    {
        $return = $this->createCodReturn(ReturnStatus::QC_PASSED);
        $initialQty = $this->inventory->fresh()->quantity_on_hand;

        $this->actingAs($this->admin)->post(route('admin.returns.initiate_refund', $return), [
            'amount' => 1499.00,
            'mode' => 'wallet',
            'is_resellable' => 1,
            'notes' => 'Approved and restocked.',
        ]);

        $this->assertEquals($initialQty + 1, $this->inventory->fresh()->quantity_on_hand);
    }

    public function test_admin_can_switch_to_rto_cargo_view(): void
    {
        $return = $this->createReturn();
        $fo = $return->orderItem->fulfilmentOrder;

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-' . strtoupper(uniqid()),
            'fulfilment_order_id' => $fo->id,
            'courier_name' => 'Shiprocket Express',
            'awb' => 'AWB-RTO-' . rand(100000, 999999),
            'status' => ShipmentStatus::RTO_INITIATED,
            'shipping_cost' => 85.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.returns.index', ['view' => 'rto']));

        $response->assertOk();
        $response->assertSee('Return to Origin (RTO) Consignments');
        $response->assertSee($shipment->awb);
    }
}
