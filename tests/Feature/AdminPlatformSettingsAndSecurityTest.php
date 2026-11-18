<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPlatformSettingsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $operationsAdmin;
    protected User $catalogManager;
    protected User $sellerSupport;
    protected User $financeManager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Super Admin
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@allzone.in']);
        $this->superAdmin->assignRole('super_admin');

        // Operations Admin
        $this->operationsAdmin = User::factory()->create(['name' => 'Ops Admin', 'email' => 'ops@allzone.in']);
        $this->operationsAdmin->assignRole('operations_admin');

        // Catalog Manager
        $this->catalogManager = User::factory()->create(['name' => 'Catalog Manager', 'email' => 'catalog@allzone.in']);
        $this->catalogManager->assignRole('catalog_manager');

        // Seller Support
        $this->sellerSupport = User::factory()->create(['name' => 'Support Agent', 'email' => 'support@allzone.in']);
        $this->sellerSupport->assignRole('seller_support');

        // Finance Manager
        $this->financeManager = User::factory()->create(['name' => 'Finance Chief', 'email' => 'finance@allzone.in']);
        $this->financeManager->assignRole('finance_manager');

        // Customer
        $this->customer = User::factory()->create(['name' => 'Customer User', 'email' => 'customer@gmail.com']);
        $this->customer->assignRole('customer');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.security.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customer_is_forbidden_from_admin_settings_and_security(): void
    {
        $this->actingAs($this->customer)->get(route('admin.settings.index'))->assertStatus(403);
        $this->actingAs($this->customer)->get(route('admin.security.index'))->assertStatus(403);
    }

    public function test_super_admin_can_view_all_platform_settings_tabs(): void
    {
        // 1. Branding & Identity Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'branding']));
        $res->assertStatus(200);
        $res->assertSee('Marketplace Tagline');
        $res->assertSee('Primary Logo URL');
        $res->assertSee('Favicon URL');

        // 2. Business Details Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'business']));
        $res->assertStatus(200);
        $res->assertSee('Company Legal Entity Name');
        $res->assertSee('Registered Office Address');
        $res->assertSee('Statutory Grievance Redressal Officer');

        // 3. Tax & GST Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'tax']));
        $res->assertStatus(200);
        $res->assertSee('Marketplace GSTIN');
        $res->assertSee('Default Platform GST Rate (%)');
        $res->assertSee('Apply Section 52 GST TCS on Sellers');

        // 4. Product Configuration Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'product']));
        $res->assertStatus(200);
        $res->assertSee('Auto-Approve Seller Product Listings');
        $res->assertSee('Maximum Product Gallery Images');

        // 5. Order Settings Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'order']));
        $res->assertStatus(200);
        $res->assertSee('Minimum Order Checkout Amount (INR)');
        $res->assertSee('Auto Cancel Unpaid Online Orders (Hours)');

        // 6. Return Policies Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'returns']));
        $res->assertStatus(200);
        $res->assertSee('Standard Return Window (Days from Delivery)');
        $res->assertSee('Require Warehouse QC Inspection Before Refund');

        // 7. Seller Registration Rules Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'seller_rules']));
        $res->assertStatus(200);
        $res->assertSee('Accept New Seller Registrations');
        $res->assertSee('Mandatory KYC Documents (PAN / GST)');
        $res->assertSee('Mandatory Bank Account Penny Drop Verification');

        // 8. Integrations Tab
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'integrations']));
        $res->assertStatus(200);
        $res->assertSee('Shiprocket Account Email');
        $res->assertSee('Integration Diagnostics');
    }

    public function test_secret_credentials_are_masked_and_never_overwritten_by_bullet_placeholders(): void
    {
        // First ensure essential settings exist
        $this->actingAs($this->superAdmin)->get(route('admin.settings.index'));

        // Set a known real secret
        Setting::set('razorpay_key_secret', 'rzp_live_secret_real_value_12345');

        // Render payment tab and verify secret is not in plain text in HTML
        $res = $this->actingAs($this->superAdmin)->get(route('admin.settings.index', ['tab' => 'payment']));
        $res->assertStatus(200);
        $res->assertDontSee('rzp_live_secret_real_value_12345');
        $res->assertSee('••••••••••••••••');

        // Update settings submitting bullet placeholder mask for the secret
        $postRes = $this->actingAs($this->superAdmin)->post(route('admin.settings.update'), [
            'tab' => 'payment',
            'settings' => [
                'razorpay_key_secret' => '••••••••••••••••',
                'cod_allowed' => '1',
            ],
        ]);
        $postRes->assertRedirect(route('admin.settings.index', ['tab' => 'payment']));

        // Verify the original secret is still intact in DB!
        $this->assertEquals('rzp_live_secret_real_value_12345', Setting::get('razorpay_key_secret'));

        // Activity log contains modified keys, not secret values
        $activity = Activity::where('log_name', 'settings')->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertStringNotContainsString('rzp_live_secret_real_value_12345', (string) $activity->description);
    }

    public function test_integration_connection_diagnostics(): void
    {
        // Test Razorpay connectivity test
        Setting::set('razorpay_key_id', 'rzp_test_SampleKey123');
        Setting::set('razorpay_key_secret', 'secret123');

        $res = $this->actingAs($this->superAdmin)->postJson(route('admin.settings.test_integration'), [
            'service' => 'razorpay',
        ]);
        $res->assertStatus(200);
        $res->assertJsonFragment(['status' => 'success', 'connected' => true, 'service' => 'Razorpay']);

        // Test Shiprocket connectivity test
        Setting::set('shiprocket_email', 'shipping@allzone.in');
        Setting::set('shiprocket_api_key', 'sr_token_123');

        $res = $this->actingAs($this->superAdmin)->postJson(route('admin.settings.test_integration'), [
            'service' => 'shiprocket',
        ]);
        $res->assertStatus(200);
        $res->assertJsonFragment(['status' => 'success', 'connected' => true, 'service' => 'Shiprocket']);
    }

    public function test_operations_admin_permissions_boundary(): void
    {
        // Operations admin CANNOT update platform settings (403)
        $this->actingAs($this->operationsAdmin)->post(route('admin.settings.update'), [
            'tab' => 'general',
            'settings' => ['name' => 'Hacked Name'],
        ])->assertStatus(403);

        // Operations admin CANNOT process/approve seller settlements (403)
        $seller = Seller::factory()->create(['status' => SellerStatus::APPROVED]);
        $settlement = SellerSettlement::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'net' => 1000.00,
        ]);
        $this->actingAs($this->operationsAdmin)->post(route('admin.settlements.approve', $settlement))
            ->assertStatus(403);

        // Operations admin CAN suspend a seller
        $this->actingAs($this->operationsAdmin)->post(route('admin.sellers.suspend', $seller), [
            'reason' => 'Violation of platform dispatch SLA terms.',
        ])->assertRedirect();
        $this->assertEquals(SellerStatus::SUSPENDED, $seller->fresh()->status);
    }

    public function test_finance_manager_permissions_boundary(): void
    {
        // Finance manager CANNOT update platform settings (403)
        $this->actingAs($this->financeManager)->post(route('admin.settings.update'), [
            'tab' => 'general',
            'settings' => ['name' => 'Finance Edited'],
        ])->assertStatus(403);

        // Finance manager CANNOT suspend sellers (403)
        $seller = Seller::factory()->create(['status' => SellerStatus::APPROVED]);
        $this->actingAs($this->financeManager)->post(route('admin.sellers.suspend', $seller), [
            'reason' => 'Illegal suspension attempt',
        ])->assertStatus(403);

        // Finance manager CAN approve settlements
        $settlement = SellerSettlement::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
            'net' => 2500.00,
        ]);
        $this->actingAs($this->financeManager)->post(route('admin.settlements.approve', $settlement))
            ->assertRedirect();
        $this->assertEquals(\App\Enums\SettlementStatus::PROCESSING, $settlement->fresh()->status);
    }

    public function test_catalog_manager_permissions_boundary(): void
    {
        // Catalog manager CANNOT modify platform settings (403)
        $this->actingAs($this->catalogManager)->post(route('admin.settings.update'), [
            'tab' => 'general',
            'settings' => ['name' => 'Catalog Edited'],
        ])->assertStatus(403);

        // Catalog manager CANNOT process settlements (403)
        $seller = Seller::factory()->create(['status' => SellerStatus::APPROVED]);
        $settlement = SellerSettlement::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
        ]);
        $this->actingAs($this->catalogManager)->post(route('admin.settlements.approve', $settlement))
            ->assertStatus(403);

        // Catalog manager CANNOT suspend sellers (403)
        $this->actingAs($this->catalogManager)->post(route('admin.sellers.suspend', $seller), [
            'reason' => 'Catalog violation',
        ])->assertStatus(403);
    }

    public function test_seller_support_permissions_boundary(): void
    {
        // Seller support CANNOT modify platform settings (403)
        $this->actingAs($this->sellerSupport)->post(route('admin.settings.update'), [
            'tab' => 'general',
            'settings' => ['name' => 'Support Edited'],
        ])->assertStatus(403);

        // Seller support CANNOT process settlements (403)
        $seller = Seller::factory()->create(['status' => SellerStatus::APPROVED]);
        $settlement = SellerSettlement::factory()->create([
            'seller_id' => $seller->id,
            'status' => 'pending',
        ]);
        $this->actingAs($this->sellerSupport)->post(route('admin.settlements.approve', $settlement))
            ->assertStatus(403);

        // Seller support CANNOT suspend sellers (403)
        $this->actingAs($this->sellerSupport)->post(route('admin.sellers.suspend', $seller), [
            'reason' => 'Support violation',
        ])->assertStatus(403);
    }

    public function test_failed_login_creates_security_audit_log(): void
    {
        // Attempt failed login with wrong password
        $res = $this->post(route('admin.login.submit'), [
            'email' => 'admin@nonexistent.test',
            'password' => 'WrongPassword123!',
        ]);
        $res->assertSessionHasErrors('email');

        // Verify security activity log was written
        $activity = Activity::where('log_name', 'security')
            ->where('event', 'failed_login')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('admin@nonexistent.test', $activity->properties['attempted_email'] ?? null);
    }

    public function test_unauthorized_role_login_creates_security_audit_log(): void
    {
        // Attempt login with valid customer credentials on admin portal
        $customerUser = User::factory()->create([
            'email' => 'regular@customer.test',
            'password' => bcrypt('Secret123!'),
        ]);
        $customerUser->assignRole('customer');

        $res = $this->post(route('admin.login.submit'), [
            'email' => 'regular@customer.test',
            'password' => 'Secret123!',
        ]);
        $res->assertSessionHasErrors('email');

        // Verify security activity log recorded unauthorized_admin_access
        $activity = Activity::where('log_name', 'security')
            ->where('event', 'unauthorized_admin_access')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('regular@customer.test', $activity->properties['attempted_email'] ?? null);
    }

    public function test_refund_sensitive_operation_authorization(): void
    {
        $seller = Seller::factory()->create(['status' => SellerStatus::APPROVED]);
        $orderReturn = OrderReturn::factory()->create([
            'status' => 'approved',
            'refund_amount' => 500.00,
        ]);

        // Operations Admin cannot initiate refund (403)
        $this->actingAs($this->operationsAdmin)->post(route('admin.returns.initiate_refund', $orderReturn), [
            'amount' => 500.00,
            'mode' => 'original',
        ])->assertStatus(403);

        // Catalog Manager cannot initiate refund (403)
        $this->actingAs($this->catalogManager)->post(route('admin.returns.initiate_refund', $orderReturn), [
            'amount' => 500.00,
            'mode' => 'original',
        ])->assertStatus(403);

        // Seller Support cannot initiate refund (403)
        $this->actingAs($this->sellerSupport)->post(route('admin.returns.initiate_refund', $orderReturn), [
            'amount' => 500.00,
            'mode' => 'original',
        ])->assertStatus(403);
    }

    public function test_admin_can_view_security_dashboard_and_terminate_session(): void
    {
        // Populate a fake session in database sessions table
        $fakeSessionId = 'test_sess_' . uniqid();
        DB::table('sessions')->insert([
            'id' => $fakeSessionId,
            'user_id' => $this->operationsAdmin->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'payload' => serialize(['test' => true]),
            'last_activity' => time() - 300,
        ]);

        // Super Admin views security center
        $res = $this->actingAs($this->superAdmin)->get(route('admin.security.index'));
        $res->assertStatus(200);
        $res->assertSee('Platform Security &amp; Session Management', false);
        $res->assertSee('192.168.1.100');
        $res->assertSee($this->operationsAdmin->name);

        // Terminate the session
        $termRes = $this->actingAs($this->superAdmin)
            ->post(route('admin.security.terminate_session', ['sessionId' => $fakeSessionId]));

        $termRes->assertRedirect();
        $termRes->assertSessionHas('success');

        // Verify session was deleted from database
        $this->assertDatabaseMissing('sessions', ['id' => $fakeSessionId]);

        // Verify security activity log was written for termination
        $activity = Activity::where('log_name', 'security')
            ->where('event', 'session_terminated')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($fakeSessionId, $activity->properties['terminated_session_id'] ?? null);
    }
}
