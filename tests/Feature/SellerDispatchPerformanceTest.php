<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDispatchPerformanceTest extends TestCase
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
            'email' => 'dispatch.merchant@allzone.in',
            'phone' => '+919876543209',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Vortex Logistics & Retail',
            'legal_name' => 'Vortex Retail India Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Vortex Central Depot',
            'slug' => 'vortex-central-depot',
            'status' => StoreStatus::ACTIVE,
            'accepts_quick_commerce' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'title' => 'Smart Fitness Tracker Band 7',
            'slug' => 'smart-fitness-tracker-band-7',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'VTX-BAND-07',
            'name' => 'Midnight Black',
            'mrp' => 2999.00,
            'selling_price' => 2499.00,
            'weight_grams' => 250,
        ]);
    }

    protected function createOrder(string $status = 'placed', ?\DateTimeInterface $createdAt = null): FulfilmentOrder
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'address_snapshot' => [
                'name' => 'Priya Nair',
                'phone' => '9876543210',
                'line1' => 'Plot 88, 5th Cross, Indiranagar',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
            ],
            'created_at' => $createdAt ?? now(),
        ]);

        $fo = FulfilmentOrder::factory()->create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => $status,
            'subtotal' => 2499.00,
            'commission_amount' => 125.00,
            'seller_payable' => 2374.00,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Smart Fitness Tracker Band 7',
            'variant_name' => 'Midnight Black',
            'sku' => 'VTX-BAND-07',
            'qty' => 1,
            'price' => 2499.00,
            'total' => 2499.00,
        ]);

        if ($createdAt) {
            \Illuminate\Support\Facades\DB::table('fulfilment_orders')->where('id', $fo->id)->update(['created_at' => $createdAt]);
            $fo->refresh();
        }

        return $fo;
    }

    protected function createShipment(FulfilmentOrder $fo, ShipmentStatus $status = ShipmentStatus::MANIFEST_CREATED, ?\DateTimeInterface $createdAt = null): Shipment
    {
        return Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SHP-' . strtoupper(uniqid()),
            'provider' => 'shiprocket',
            'awb' => 'SR' . rand(100000000, 999999999),
            'courier' => 'Delhivery Surface via Shiprocket',
            'service' => 'surface',
            'status' => $status,
            'pickup_status' => $status === ShipmentStatus::PICKED_UP ? 'completed' : 'pending',
            'label_url' => 'https://shiprocket.co/label/demo.pdf',
            'cod_amount' => 0.00,
            'weight' => 0.50,
            'charges' => 85.00,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }

    /**
     * Test 1: Seller can view dispatch performance dashboard and see all 10 metrics.
     */
    public function test_seller_can_view_dispatch_performance_dashboard_with_ten_metrics(): void
    {
        // 1. Pending dispatch
        $this->createOrder('placed');

        // 2. Ready to ship
        $this->createOrder('ready_to_ship');

        // 3. Shipment created (manifested)
        $fo3 = $this->createOrder('confirmed');
        $this->createShipment($fo3, ShipmentStatus::MANIFEST_CREATED);

        // 4. Picked up
        $fo4 = $this->createOrder('confirmed');
        $this->createShipment($fo4, ShipmentStatus::PICKED_UP);

        // 5. In transit
        $fo5 = $this->createOrder('confirmed');
        $this->createShipment($fo5, ShipmentStatus::IN_TRANSIT);

        // 6. Delivered
        $this->createOrder('delivered');

        // 7. Delayed shipment (> 24 hours in placed status)
        $this->createOrder('placed', now()->subHours(36));

        // 8. Failed delivery / NDR
        $fo8 = $this->createOrder('confirmed');
        $this->createShipment($fo8, ShipmentStatus::NDR_RAISED);

        // 9. RTO
        $fo9 = $this->createOrder('confirmed');
        $this->createShipment($fo9, ShipmentStatus::RTO_INITIATED);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.dispatch_performance'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.dispatch.index');
        $response->assertSee('Dispatch Performance & Shiprocket Logistics');
        $response->assertSee('1. Pending Dispatch');
        $response->assertSee('2. Ready to Ship');
        $response->assertSee('3. Shipment Created');
        $response->assertSee('4. Pickup Pending');
        $response->assertSee('5. Picked Up');
        $response->assertSee('6. In Transit');
        $response->assertSee('7. Delivered');
        $response->assertSee('8. Delayed Shipments');
        $response->assertSee('9. Failed Delivery');
        $response->assertSee('10. RTO Shipments');

        $metrics = $response->viewData('metrics');
        $this->assertGreaterThanOrEqual(1, $metrics['pending_dispatch']);
        $this->assertGreaterThanOrEqual(1, $metrics['ready_to_ship']);
        $this->assertGreaterThanOrEqual(1, $metrics['shipment_created']);
        $this->assertGreaterThanOrEqual(1, $metrics['pickup_pending']);
        $this->assertGreaterThanOrEqual(1, $metrics['picked_up']);
        $this->assertGreaterThanOrEqual(1, $metrics['in_transit']);
        $this->assertGreaterThanOrEqual(1, $metrics['delivered']);
        $this->assertGreaterThanOrEqual(1, $metrics['delayed_shipments']);
        $this->assertGreaterThanOrEqual(1, $metrics['failed_delivery']);
        $this->assertGreaterThanOrEqual(1, $metrics['rto_shipments']);
    }

    /**
     * Test 2: Multi-vendor tenant isolation (Seller A cannot view Seller B's dispatch records).
     */
    public function test_multivendor_tenant_isolation_on_dispatch_dashboard(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other.logistics@allzone.in',
            'phone' => '+919876543208',
        ]);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Rival Merchant Hub',
            'status' => SellerStatus::APPROVED,
        ]);

        $myFo = $this->createOrder('placed');

        // Other seller order
        $otherCustomer = Customer::factory()->create();
        $otherOrder = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        $otherFo = FulfilmentOrder::factory()->create([
            'order_id' => $otherOrder->id,
            'fulfilment_order_number' => 'FO-OTHER-SELLER-999',
            'seller_id' => $otherSeller->id,
            'store_id' => $this->store->id,
            'status' => 'placed',
            'subtotal' => 1500.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.dispatch_performance'));
        $response->assertStatus(200);
        $response->assertSee($myFo->fulfilment_order_number);
        $response->assertDontSee($otherFo->fulfilment_order_number);
    }

    /**
     * Test 3: Check live Shiprocket courier rates for an order.
     */
    public function test_check_shiprocket_rates_for_order(): void
    {
        $fo = $this->createOrder('confirmed');

        $response = $this->actingAs($this->sellerUser)->getJson(route('seller.orders.check_rates', [
            'fulfilmentOrder' => $fo,
            'weight' => 1.25,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'weight' => 1.25,
        ]);
        $this->assertArrayHasKey('couriers', $response->json());
    }

    /**
     * Test 4: Package dimensions and weight validation before shipment creation.
     */
    public function test_dimensions_and_weight_validation_on_shipment_creation(): void
    {
        $fo = $this->createOrder('confirmed');

        // Missing dimensions / invalid weight should fail validation
        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.create_shipment', $fo), [
            'length' => 0, // invalid (min 0.5)
            'breadth' => -1, // invalid
            'height' => 10,
            'weight' => 0, // invalid (min 0.01)
        ]);

        $response->assertSessionHasErrors(['length', 'breadth', 'weight']);
    }

    /**
     * Test 5: Successful Shiprocket shipment creation creates actual AWB and updates order.
     */
    public function test_successful_shipment_creation_creates_awb(): void
    {
        $fo = $this->createOrder('confirmed');

        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.create_shipment', $fo), [
            'length' => 15.0,
            'breadth' => 12.0,
            'height' => 8.0,
            'weight' => 0.75,
            'courier_name' => 'Blue Dart Air via Shiprocket',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shipments', [
            'fulfilment_order_id' => $fo->id,
            'provider' => 'shiprocket',
            'weight' => 0.75,
        ]);

        $shipment = Shipment::where('fulfilment_order_id', $fo->id)->first();
        $this->assertNotNull($shipment);
        $this->assertNotNull($shipment->awb);

        $fo->refresh();
        $this->assertEquals('ready_to_ship', $fo->status);
    }

    /**
     * Test 6: Duplicate shipment creation is safely prevented.
     */
    public function test_duplicate_shipment_creation_is_prevented(): void
    {
        $fo = $this->createOrder('confirmed');
        $existingShipment = $this->createShipment($fo, ShipmentStatus::MANIFEST_CREATED);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.create_shipment', $fo), [
            'length' => 15.0,
            'breadth' => 12.0,
            'height' => 8.0,
            'weight' => 0.75,
        ]);

        $response->assertSessionHas('info');

        // Verify count of shipments remains 1
        $this->assertEquals(1, Shipment::where('fulfilment_order_id', $fo->id)->count());
    }

    /**
     * Test 7: Live tracking sync from Shiprocket API records milestones.
     */
    public function test_sync_live_tracking_from_shiprocket(): void
    {
        $fo = $this->createOrder('confirmed');
        $shipment = $this->createShipment($fo, ShipmentStatus::MANIFEST_CREATED);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.shipments.sync_tracking', $shipment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('shipment_tracking', [
            'shipment_id' => $shipment->id,
        ]);
    }

    /**
     * Test 8: Schedule carrier pickup via Shiprocket.
     */
    public function test_schedule_carrier_pickup(): void
    {
        $fo = $this->createOrder('confirmed');
        $shipment = $this->createShipment($fo, ShipmentStatus::MANIFEST_CREATED);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.shipments.request_pickup', $shipment), [
            'pickup_date' => now()->format('Y-m-d'),
            'time_slot' => '10:00 - 13:00',
        ]);

        $response->assertSessionHas('success');
    }

    /**
     * Test 9: Export dispatch performance report as CSV.
     */
    public function test_seller_can_export_dispatch_performance_as_csv(): void
    {
        $fo = $this->createOrder('confirmed');
        $this->createShipment($fo, ShipmentStatus::MANIFEST_CREATED);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.dispatch_performance', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Sub-order Package #', $content);
        $this->assertStringContainsString('Dispatch SLA Deadline', $content);
        $this->assertStringContainsString('Shiprocket Courier', $content);
        $this->assertStringContainsString($fo->fulfilment_order_number, $content);
    }
}
