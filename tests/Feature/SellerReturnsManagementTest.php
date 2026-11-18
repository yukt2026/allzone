<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QCStatus;
use App\Enums\ReturnStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RtoCase;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerReturnsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sellerUser = User::factory()->create([
            'email' => 'returns.merchant@allzone.in',
            'phone' => '+919876543211',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Lifestyle Apparels',
            'legal_name' => 'Zenith Lifestyle Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Indiranagar Outlet',
            'slug' => 'zenith-indiranagar-outlet',
            'status' => StoreStatus::ACTIVE,
            'accepts_quick_commerce' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'title' => 'Slim Fit Cotton Casual Shirt',
            'slug' => 'slim-fit-cotton-casual-shirt',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-BLK-M',
            'name' => 'Black / Medium',
            'mrp' => 1499.00,
            'selling_price' => 999.00,
            'weight_grams' => 300,
        ]);
    }

    protected function createDeliveredOrder(Seller $seller): array
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'address_snapshot' => [
                'name' => 'Vikram Seth',
                'phone' => '9876543210',
                'line1' => 'Suite 101, Prestige Heights',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
            ],
        ]);

        $fo = FulfilmentOrder::factory()->create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'seller_id' => $seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => 'delivered',
            'subtotal' => 999.00,
            'commission_amount' => 50.00,
            'seller_payable' => 949.00,
            'delivered_at' => now()->subDays(2),
        ]);

        $item = OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Slim Fit Cotton Casual Shirt',
            'variant_name' => 'Black / Medium',
            'sku' => 'ZNT-SHIRT-BLK-M',
            'qty' => 1,
            'price' => 999.00,
            'total' => 999.00,
        ]);

        \App\Models\Payment::create([
            'order_id' => $order->id,
            'gateway' => 'cod',
            'gateway_order_id' => 'cod_' . strtoupper(uniqid()),
            'gateway_payment_id' => 'pay_cod_' . strtoupper(uniqid()),
            'method' => PaymentMethod::COD,
            'amount' => 999.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'verified_at' => now(),
        ]);

        return [$order, $fo, $item];
    }

    protected function createReturn(OrderItem $item, string $status = 'requested', string $type = 'return'): OrderReturn
    {
        return OrderReturn::create([
            'return_number' => 'RET-' . strtoupper(uniqid()),
            'type' => $type,
            'order_item_id' => $item->id,
            'reason' => 'Size too small',
            'customer_notes' => 'Fabric quality is good but chest size runs smaller than usual.',
            'photos' => ['https://allzone.in/storage/evidence-1.jpg'],
            'status' => $status,
            'qc_status' => QCStatus::PENDING,
            'refund_amount' => 999.00,
            'refund_mode' => 'gateway',
            'pickup_waybill' => $status !== 'requested' ? 'AWB-RET-889900' : null,
        ]);
    }

    /**
     * Test 1: Seller can view overview dashboard with actual return rates and metrics.
     */
    public function test_seller_can_view_returns_overview_dashboard_with_actual_metrics_and_trends(): void
    {
        // Create 2 delivered orders
        [$order1, $fo1, $item1] = $this->createDeliveredOrder($this->seller);
        [$order2, $fo2, $item2] = $this->createDeliveredOrder($this->seller);

        // 1 customer return
        $return = $this->createReturn($item1, 'requested');

        // 1 courier RTO shipment
        $rtoShipment = Shipment::create([
            'fulfilment_order_id' => $fo2->id,
            'shipment_number' => 'SHP-RTO-1234',
            'provider' => 'shiprocket',
            'awb' => 'AWB-RTO-998877',
            'courier' => 'Delhivery Surface via Shiprocket',
            'status' => ShipmentStatus::RTO_INITIATED,
            'pickup_status' => 'completed',
            'charges' => 90.00,
        ]);

        RtoCase::create([
            'shipment_id' => $rtoShipment->id,
            'reason' => 'Customer door closed after 3 delivery attempts',
            'status' => 'in_transit',
            'initiated_at' => now(),
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.returns.index', ['tab' => 'overview']));

        $response->assertStatus(200);
        $response->assertViewIs('seller.returns.index');
        $response->assertSee('Returns & RTO Management');
        $response->assertSee('Customer Return Rate');
        $response->assertSee('Courier RTO Rate');
        $response->assertSee('Delivered Orders');
        $response->assertSee('Return Orders');
        $response->assertSee('Reverse Freight Cost');
        $response->assertSee('Product-Wise Return Performance');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['delivered_orders']);
        $this->assertEquals(1, $metrics['customer_returns']);
        $this->assertEquals(50.0, $metrics['customer_return_rate']);
        $this->assertEquals(1, $metrics['courier_rto']);
        $this->assertEquals(90.00, $metrics['reverse_shipping_costs']);

        $trends = $response->viewData('trends');
        $this->assertArrayHasKey('labels', $trends);
        $this->assertArrayHasKey('returns', $trends);
        $this->assertArrayHasKey('rto', $trends);

        $productPerformance = $response->viewData('productPerformance');
        $this->assertNotEmpty($productPerformance);
        $this->assertEquals('Slim Fit Cotton Casual Shirt', $productPerformance[0]['title']);
    }

    /**
     * Test 2: Multi-vendor tenant isolation (Seller A cannot view or manage Seller B's returns).
     */
    public function test_multivendor_tenant_isolation_on_returns(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other.merchant@allzone.in',
            'phone' => '+919876543212',
        ]);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Royal Silks & Sarees',
            'status' => SellerStatus::APPROVED,
        ]);

        [$myOrder, $myFo, $myItem] = $this->createDeliveredOrder($this->seller);
        $myReturn = $this->createReturn($myItem, 'requested');

        [$otherOrder, $otherFo, $otherItem] = $this->createDeliveredOrder($otherSeller);
        $otherReturn = $this->createReturn($otherItem, 'requested');

        // Seller A should see only their return in returns tab
        $response = $this->actingAs($this->sellerUser)->get(route('seller.returns.index', ['tab' => 'returns']));
        $response->assertStatus(200);
        $response->assertSee($myReturn->return_number);
        $response->assertDontSee($otherReturn->return_number);

        // Seller A attempting to view Seller B's return gets 403 Forbidden
        $detailResponse = $this->actingAs($this->sellerUser)->get(route('seller.returns.show', $otherReturn));
        $detailResponse->assertStatus(403);

        // Seller A attempting to approve Seller B's return gets 403 Forbidden
        $approveResponse = $this->actingAs($this->sellerUser)->post(route('seller.returns.approve', $otherReturn));
        $approveResponse->assertStatus(403);
    }

    /**
     * Test 3: Seller can filter returns by tabs and search.
     */
    public function test_seller_can_filter_returns_by_tabs_and_search(): void
    {
        [$order1, $fo1, $item1] = $this->createDeliveredOrder($this->seller);
        [$order2, $fo2, $item2] = $this->createDeliveredOrder($this->seller);

        $return1 = $this->createReturn($item1, 'requested');
        $return2 = $this->createReturn($item2, 'approved');

        // Filter tab: returns with status = requested
        $resFilter = $this->actingAs($this->sellerUser)->get(route('seller.returns.index', [
            'tab' => 'returns',
            'status' => 'requested',
        ]));
        $resFilter->assertStatus(200);
        $resFilter->assertSee($return1->return_number);
        $resFilter->assertDontSee($return2->return_number);

        // Search by return number
        $resSearch = $this->actingAs($this->sellerUser)->get(route('seller.returns.index', [
            'tab' => 'returns',
            'search' => $return1->return_number,
        ]));
        $resSearch->assertStatus(200);
        $resSearch->assertSee($return1->return_number);
        $resSearch->assertDontSee($return2->return_number);
    }

    /**
     * Test 4: Seller can view RTO tracking tab.
     */
    public function test_seller_can_view_rto_tracking_tab(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        $rtoShipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SHP-RTO-9999',
            'provider' => 'shiprocket',
            'awb' => 'AWB-RTO-777666',
            'courier' => 'Shadowfax Surface via Shiprocket',
            'status' => ShipmentStatus::RTO_DELIVERED,
            'charges' => 85.00,
        ]);

        RtoCase::create([
            'shipment_id' => $rtoShipment->id,
            'reason' => 'Customer refused parcel at doorstep',
            'status' => 'delivered',
            'initiated_at' => now()->subDays(3),
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.returns.index', ['tab' => 'rto']));

        $response->assertStatus(200);
        $response->assertSee('Courier Return to Origin (RTO) Consignments');
        $response->assertSee('AWB-RTO-777666');
        $response->assertSee('Customer refused parcel at doorstep');
        $response->assertSee('Restocked at Depot');
    }

    /**
     * Test 5: Seller can approve a return request.
     */
    public function test_seller_can_approve_return_request(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);
        $return = $this->createReturn($item, 'requested');

        $response = $this->actingAs($this->sellerUser)->post(route('seller.returns.approve', $return), [
            'seller_notes' => 'Return approved. Customer requested pickup scheduled.',
        ]);

        $response->assertSessionHas('success');

        $return->refresh();
        $this->assertTrue(in_array($return->status, [ReturnStatus::APPROVED, ReturnStatus::REVERSE_PICKUP_SCHEDULED]));
    }

    /**
     * Test 6: Seller can reject a return request with mandatory reason.
     */
    public function test_seller_can_reject_return_request(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);
        $return = $this->createReturn($item, 'requested');

        $response = $this->actingAs($this->sellerUser)->post(route('seller.returns.reject', $return), [
            'reason' => 'Item shows physical damage not covered by standard return policy.',
        ]);

        $response->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals(ReturnStatus::REJECTED, $return->status);
        $this->assertEquals('Item shows physical damage not covered by standard return policy.', $return->rejection_reason);
    }

    /**
     * Test 7: Seller can record QC outcome and issue refund.
     */
    public function test_seller_can_record_qc_outcome_and_issue_refund(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);
        $return = $this->createReturn($item, 'received');

        // 1. Record QC outcome (Passed)
        $qcResponse = $this->actingAs($this->sellerUser)->post(route('seller.returns.record_qc', $return), [
            'outcome' => 'passed',
            'qc_notes' => 'Item verified in unused original box with tags intact.',
            'is_resellable' => true,
        ]);
        $qcResponse->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals(ReturnStatus::QC_PASSED, $return->status);
        $this->assertEquals(QCStatus::PASSED, $return->qc_status);

        // 2. Issue Refund
        $refundResponse = $this->actingAs($this->sellerUser)->post(route('seller.returns.refund', $return));
        $refundResponse->assertSessionHas('success');

        $return->refresh();
        $this->assertEquals(ReturnStatus::REFUNDED, $return->status);
        $this->assertNotNull($return->refund_id);
    }

    /**
     * Test 8: Duplicate refunds are prevented.
     */
    public function test_duplicate_refunds_are_prevented(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);
        $return = $this->createReturn($item, 'refunded');

        // Attempting to refund an already refunded return fails gracefully
        $response = $this->actingAs($this->sellerUser)->post(route('seller.returns.refund', $return));
        $response->assertSessionHas('error');
    }

    /**
     * Test 9: Seller can export returns and RTO as CSV.
     */
    public function test_seller_can_export_returns_and_rto_as_csv(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);
        $return = $this->createReturn($item, 'requested');

        // Export Customer Returns CSV
        $resReturns = $this->actingAs($this->sellerUser)->get(route('seller.returns.export', ['tab' => 'returns']));
        $resReturns->assertStatus(200);
        $resReturns->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contentReturns = $resReturns->streamedContent();
        $this->assertStringContainsString('Return Number', $contentReturns);
        $this->assertStringContainsString('Sub-order Package #', $contentReturns);
        $this->assertStringContainsString($return->return_number, $contentReturns);

        // Export RTO Consignments CSV
        $resRto = $this->actingAs($this->sellerUser)->get(route('seller.returns.export', ['tab' => 'rto']));
        $resRto->assertStatus(200);
        $resRto->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $contentRto = $resRto->streamedContent();
        $this->assertStringContainsString('Sub-order Package #', $contentRto);
        $this->assertStringContainsString('RTO Reason', $contentRto);
    }
}
