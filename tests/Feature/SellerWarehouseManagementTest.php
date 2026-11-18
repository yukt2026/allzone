<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Seller;
use App\Models\SellerPickupLocation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerWarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected User $otherSellerUser;
    protected Seller $otherSeller;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'warehouse.merchant@allzone.in',
            'phone' => '+919876543201',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Nexus Electronics Hub',
            'legal_name' => 'Nexus Electronics Hub Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        // Seller 2 (for tenant isolation tests)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.merchant@allzone.in',
            'phone' => '+919876543202',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Apex Appliances India',
            'legal_name' => 'Apex Appliances India Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        // Admin User
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'email' => 'shipping_admin@allzone.in',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_seller_can_view_warehouse_dashboard_with_locations_and_package_presets(): void
    {
        $location = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Central Bengaluru Hub',
            'contact_person' => 'Ramesh Kumar',
            'contact_phone' => '9876543210',
            'contact_email' => 'ramesh@allzone.in',
            'address_line1' => 'Plot 42, Electronic City Phase 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
            'is_verified' => true,
            'is_serviceable' => true,
            'shiprocket_pickup_name' => 'NEXUS_CENTRAL_HUB',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.warehouse.index'));

        $response->assertOk();
        $response->assertSee('Warehouse');
        $response->assertSee('Pickup Locations');
        $response->assertSee('Central Bengaluru Hub');
        $response->assertSee('Electronic City Phase 1');
        $response->assertSee('560100');
        $response->assertSee('Primary Dispatch Hub');
        $response->assertSee('Shiprocket Serviceability');
        $response->assertSee('Standard Shipment Packaging Presets');
    }

    public function test_multivendor_tenant_isolation_on_warehouse_pickup_locations(): void
    {
        $locationA = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Seller A Private Hub',
            'contact_person' => 'Ramesh Kumar',
            'contact_phone' => '9876543210',
            'address_line1' => 'Secret Lane 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
        ]);

        // Other seller viewing dashboard should NOT see Seller A's location
        $response = $this->actingAs($this->otherSellerUser)
            ->get(route('seller.warehouse.index'));

        $response->assertOk();
        $response->assertDontSee('Seller A Private Hub');
        $response->assertDontSee('Secret Lane 1');

        // Other seller cannot inspect Seller A's location JSON
        $this->actingAs($this->otherSellerUser)
            ->get(route('seller.warehouse.show', $locationA))
            ->assertForbidden();

        // Other seller cannot update Seller A's location
        $this->actingAs($this->otherSellerUser)
            ->put(route('seller.warehouse.update', $locationA), [
                'nickname' => 'Hacked Hub',
                'contact_person' => 'Malicious Agent',
                'contact_phone' => '9999999999',
                'address_line1' => 'Hacked Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
            ])
            ->assertForbidden();

        // Other seller cannot set Seller A's location as default
        $this->actingAs($this->otherSellerUser)
            ->post(route('seller.warehouse.set_default', $locationA))
            ->assertForbidden();

        // Other seller cannot delete Seller A's location
        $this->actingAs($this->otherSellerUser)
            ->delete(route('seller.warehouse.destroy', $locationA))
            ->assertForbidden();
    }

    public function test_seller_can_add_pickup_location_with_shiprocket_serviceability_validation(): void
    {
        $payload = [
            'nickname' => 'Whitefield Fulfillment Center',
            'contact_person' => 'Suresh Reddy',
            'contact_phone' => '9876543211',
            'contact_email' => 'suresh@nexus-allzone.in',
            'address_line1' => 'Building 3B, ITPL Road, Whitefield',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560066',
            'is_default' => '1',
            'operating_hours' => '09:00 AM - 07:00 PM',
        ];

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.warehouse.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_pickup_locations', [
            'seller_id' => $this->seller->id,
            'nickname' => 'Whitefield Fulfillment Center',
            'contact_person' => 'Suresh Reddy',
            'pincode' => '560066',
            'is_default' => 1,
            'is_serviceable' => 1,
            'is_verified' => 0,
        ]);
    }

    public function test_seller_can_update_pickup_location(): void
    {
        $location = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Original Depot',
            'contact_person' => 'Ramesh Kumar',
            'contact_phone' => '9876543210',
            'address_line1' => 'Old Street 12',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
            'is_verified' => true,
        ]);

        $updatePayload = [
            'nickname' => 'Upgraded Mega Depot',
            'contact_person' => 'Ramesh K Senior',
            'contact_phone' => '9876543299',
            'address_line1' => 'Modern Logistics Park, Dock 4',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'is_default' => '1',
        ];

        $response = $this->actingAs($this->sellerUser)
            ->put(route('seller.warehouse.update', $location), $updatePayload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_pickup_locations', [
            'id' => $location->id,
            'nickname' => 'Upgraded Mega Depot',
            'contact_person' => 'Ramesh K Senior',
            'contact_phone' => '9876543299',
            'address_line1' => 'Modern Logistics Park, Dock 4',
        ]);
    }

    public function test_seller_can_promote_pickup_location_to_primary_default(): void
    {
        $location1 = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Old Default Hub',
            'contact_person' => 'Contact 1',
            'contact_phone' => '9876543210',
            'address_line1' => 'Address 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
        ]);

        $location2 = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'New Primary Hub',
            'contact_person' => 'Contact 2',
            'contact_phone' => '9876543211',
            'address_line1' => 'Address 2',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560066',
            'country' => 'India',
            'is_default' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.warehouse.set_default', $location2));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($location2->fresh()->is_default);
        $this->assertFalse($location1->fresh()->is_default);
    }

    public function test_seller_can_save_packaging_presets(): void
    {
        $location = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Default Packaging Hub',
            'contact_person' => 'Ramesh',
            'contact_phone' => '9876543210',
            'address_line1' => 'Hub Address',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.warehouse.package_config'), [
                'box_name' => 'Custom Large Appliance Box',
                'length' => 50,
                'width' => 40,
                'height' => 30,
                'weight_grams' => 4500,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $location->refresh();
        $this->assertIsArray($location->package_types);
        $this->assertArrayHasKey('custom_large_appliance_box', $location->package_types);
        $this->assertEquals(4500, $location->package_types['custom_large_appliance_box']['weight_grams']);
    }

    public function test_seller_can_delete_non_default_pickup_location(): void
    {
        $primary = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Primary Hub',
            'contact_person' => 'Ramesh',
            'contact_phone' => '9876543210',
            'address_line1' => 'Hub 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
        ]);

        $secondary = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Temporary Overflow Hub',
            'contact_person' => 'Suresh',
            'contact_phone' => '9876543211',
            'address_line1' => 'Hub 2',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560066',
            'country' => 'India',
            'is_default' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->delete(route('seller.warehouse.destroy', $secondary));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('seller_pickup_locations', [
            'id' => $secondary->id,
        ]);
    }

    public function test_admin_can_view_all_seller_pickup_locations_and_verify_address(): void
    {
        $location = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Unverified Warehouse',
            'contact_person' => 'Anil Sharma',
            'contact_phone' => '9876543212',
            'address_line1' => 'Survey No 88, Outer Ring Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560037',
            'country' => 'India',
            'is_default' => true,
            'status' => 'active',
            'is_verified' => false,
        ]);

        // Admin can list locations
        $indexResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.shipping.pickup_locations.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Seller Pickup Locations');
        $indexResponse->assertSee('Unverified Warehouse');
        $indexResponse->assertSee('Nexus Electronics Hub');

        // Admin can verify location
        $verifyResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.shipping.pickup_locations.verify', $location));

        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHas('success');

        $location->refresh();
        $this->assertTrue($location->is_verified);
        $this->assertEquals($this->adminUser->id, $location->verified_by);
        $this->assertNotNull($location->verified_at);
        $this->assertEquals('active', $location->status);
    }

    public function test_admin_can_reject_seller_pickup_location_with_reason(): void
    {
        $location = SellerPickupLocation::create([
            'seller_id' => $this->seller->id,
            'nickname' => 'Suspicious Hub Address',
            'contact_person' => 'Ghost Contact',
            'contact_phone' => '9876543213',
            'address_line1' => 'Unknown Alleyway 9',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'country' => 'India',
            'is_default' => false,
            'status' => 'active',
            'is_verified' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.shipping.pickup_locations.reject', $location), [
                'rejection_reason' => 'Address verification failed: Pincode does not match trade license registration.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $location->refresh();
        $this->assertFalse($location->is_verified);
        $this->assertEquals('rejected', $location->status);
        $this->assertStringContainsString('Pincode does not match', $location->rejection_reason);
    }
}
