<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Jobs\Cargo\TrackingSyncJob;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentWebhookLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminShippingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Seller $seller;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create(['name' => 'Admin Logistics', 'email' => 'logistics@allzone.test']);
        $this->adminUser->assignRole($adminRole);

        $this->customerUser = User::factory()->create(['name' => 'Regular Customer', 'email' => 'customer@allzone.test']);

        $sellerUser = User::factory()->create();
        $this->seller = Seller::create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Prime Electronics Ltd',
            'legal_name' => 'Prime Electronics Pvt Ltd',
            'slug' => 'prime-electronics-' . uniqid(),
            'status' => 'approved',
        ]);

        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'Prime Hub',
            'slug' => 'prime-hub-' . uniqid(),
            'pincode' => '560038',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.shipping.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_customer_is_forbidden(): void
    {
        $response = $this->actingAs($this->customerUser)->get(route('admin.shipping.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_shipping_dashboard_with_all_10_kpis(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-SHP-001']);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-SHP-001',
            'status' => 'processing',
            'subtotal' => 1200,
        ]);

        $shipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-SHP-99101',
            'provider' => 'shiprocket',
            'awb' => 'SR99101001',
            'courier' => 'Delhivery Express',
            'service' => 'Surface',
            'status' => ShipmentStatus::IN_TRANSIT,
            'weight' => 1.25,
            'charges' => 85.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.shipping.index'));

        $response->assertStatus(200);
        $response->assertSee('Shiprocket Shipping Administration');
        $response->assertSee('Total Shipments');
        $response->assertSee('Creation Pending');
        $response->assertSee('Pickup Pending');
        $response->assertSee('Picked Up');
        $response->assertSee('In Transit');
        $response->assertSee('Delivered');
        $response->assertSee('Delayed');
        $response->assertSee('Failed / NDR');
        $response->assertSee('RTO (Return Origin)');
        $response->assertSee('Cancelled');
        $response->assertSee('SR-SHP-99101');
        $response->assertSee('SR99101001');
        $response->assertSee('Delhivery Express');
    }

    public function test_admin_can_filter_shipments_by_status_and_search(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-SEARCH-01']);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-SEARCH-01',
            'status' => 'processing',
            'subtotal' => 500,
        ]);

        $shp1 = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-SHP-FILTER-1',
            'provider' => 'shiprocket',
            'awb' => 'AWB-UNIQUE-111',
            'courier' => 'BlueDart Express',
            'status' => ShipmentStatus::IN_TRANSIT,
            'weight' => 0.5,
        ]);

        $shp2 = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-SHP-FILTER-2',
            'provider' => 'shiprocket',
            'awb' => 'AWB-OTHER-222',
            'courier' => 'Ekart Logistics',
            'status' => ShipmentStatus::DELIVERED,
            'weight' => 1.0,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.shipping.index', [
            'search' => 'UNIQUE-111',
        ]));

        $response->assertStatus(200);
        $response->assertSee('AWB-UNIQUE-111');
        $response->assertDontSee('AWB-OTHER-222');
    }

    public function test_admin_can_view_creation_pending_queue(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-PENDING-01']);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-PENDING-99',
            'status' => 'ready_to_ship',
            'subtotal' => 850,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.shipping.index', [
            'view' => 'pending_creation',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Orders &amp; Consignments Awaiting Shiprocket Booking', false);
        $response->assertSee('FO-PENDING-99');
        $response->assertSee('Book Shiprocket');
    }

    public function test_admin_can_create_shipment_consignment(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-BOOK-01',
            'address_snapshot' => [
                'name' => 'Jane Buyer',
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
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-BOOK-01',
            'status' => 'ready_to_ship',
            'subtotal' => 1500,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.create_shipment', $fo), [
            'weight' => 0.8,
            'length' => 12,
            'breadth' => 10,
            'height' => 8,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shipment = Shipment::where('fulfilment_order_id', $fo->id)->first();
        $this->assertNotNull($shipment);
        $this->assertEquals('shiprocket', $shipment->provider);
        $this->assertNotNull($shipment->awb);
    }

    public function test_duplicate_shipment_creation_is_prevented(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-DUP-01']);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-DUP-01',
            'status' => 'ready_to_ship',
            'subtotal' => 1000,
        ]);

        Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-EXISTING-01',
            'provider' => 'shiprocket',
            'awb' => 'AWB-EXISTING-01',
            'courier' => 'Delhivery',
            'status' => ShipmentStatus::MANIFEST_CREATED,
            'weight' => 0.5,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.create_shipment', $fo), [
            'weight' => 0.5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Duplicate creation prevented', session('error'));
    }

    public function test_admin_can_cancel_active_shipment(): void
    {
        $order = Order::factory()->create();
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-CNL-01',
            'status' => 'processing',
            'subtotal' => 500,
        ]);

        $shipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-CNL-01',
            'provider' => 'shiprocket',
            'awb' => 'AWB-TO-CANCEL',
            'courier' => 'Delhivery',
            'status' => ShipmentStatus::MANIFEST_CREATED,
            'weight' => 0.5,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.cancel_shipment', $shipment), [
            'reason' => 'Customer requested change of address before dispatch.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $shipment->fresh();
        $this->assertEquals(ShipmentStatus::CANCELLED, $fresh->status);
    }

    public function test_admin_can_test_shiprocket_connection(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.test_shiprocket'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Shiprocket connection verified', session('success'));
    }

    public function test_admin_can_check_serviceability_and_courier_rates(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.check_serviceability'), [
            'pickup_pincode' => '560038',
            'delivery_pincode' => '110001',
            'weight_kg' => 0.75,
            'is_cod' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull(session('serviceability_results'));
        $this->assertNotEmpty(session('serviceability_results'));
    }

    public function test_admin_can_update_shipping_configuration_with_password_security(): void
    {
        Setting::set('shiprocket_password', 'secret_pass_123', 'shiprocket');

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.update'), [
            'default_weight_kg' => 0.75,
            'default_length_cm' => 18,
            'default_width_cm' => 12,
            'default_height_cm' => 6,
            'volumetric_divisor' => 5000,
            'standard_base_fee' => 50.00,
            'free_shipping_threshold' => 799.00,
            'extra_kg_slab_fee' => 35.00,
            'cod_max_limit' => 6000.00,
            'rto_max_attempts' => 3,
            'shiprocket_email' => 'admin.logistics@allzone.in',
            'shiprocket_password' => '', // Empty password should not overwrite existing secret
            'shiprocket_enabled' => 1,
            'shiprocket_fake' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0.75, (float) Setting::get('shipping_default_weight_kg'));
        $this->assertEquals(50.00, (float) Setting::get('shipping_standard_base_fee'));
        $this->assertEquals(799.00, (float) Setting::get('shipping_free_threshold'));
        $this->assertEquals('secret_pass_123', Setting::get('shiprocket_password')); // Secret preserved
    }

    public function test_shiprocket_webhook_logs_payload_and_advances_status(): void
    {
        $order = Order::factory()->create();
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-WH-01',
            'status' => 'processing',
            'subtotal' => 600,
        ]);

        $shipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SR-WH-01',
            'provider' => 'shiprocket',
            'awb' => 'AWB-WEBHOOK-99',
            'status' => ShipmentStatus::MANIFEST_CREATED,
            'weight' => 0.5,
        ]);

        $response = $this->postJson('/api/v1/webhooks/shiprocket', [
            'awb' => 'AWB-WEBHOOK-99',
            'order_id' => 'FO-WH-01',
            'current_status' => 'DELIVERED',
            'current_status_id' => 7,
            'current_city' => 'Bengaluru Central Hub',
            'current_timestamp' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify webhook log created
        $log = ShipmentWebhookLog::where('awb', 'AWB-WEBHOOK-99')->first();
        $this->assertNotNull($log);
        $this->assertEquals(7, $log->status_code);
        $this->assertTrue($log->is_verified);

        // Verify shipment updated to delivered
        $fresh = $shipment->fresh();
        $this->assertEquals(ShipmentStatus::DELIVERED, $fresh->status);
    }

    public function test_admin_can_view_failed_shipment_queue(): void
    {
        $order = Order::factory()->create(['order_number' => 'ORD-FAIL-01']);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-FAIL-01',
            'status' => 'processing',
            'subtotal' => 500,
        ]);

        $failedShipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SHP-FAIL-001',
            'provider' => 'shiprocket',
            'awb' => null,
            'status' => ShipmentStatus::FAILED,
            'weight' => 0.5,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.shipping.failed_queue'));
        $response->assertStatus(200);
        $response->assertSee('Failed Shipments Queue');
        $response->assertSee('SHP-FAIL-001');
        $response->assertSee('ORD-FAIL-01');
    }

    public function test_admin_can_retry_failed_shipment(): void
    {
        $order = Order::factory()->create();
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-RETRY-01',
            'status' => 'processing',
            'subtotal' => 500,
        ]);

        $shipment = Shipment::create([
            'fulfilment_order_id' => $fo->id,
            'shipment_number' => 'SHP-RETRY-01',
            'provider' => 'shiprocket',
            'courier' => 'Delhivery Express',
            'awb' => null,
            'status' => ShipmentStatus::FAILED,
            'weight' => 0.5,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.shipping.retry_shipment', $shipment));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $shipment->fresh();
        $this->assertEquals(ShipmentStatus::MANIFEST_CREATED, $fresh->status);
        $this->assertNotNull($fresh->awb);
    }
}
