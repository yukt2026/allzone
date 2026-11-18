<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\SellerFinanceApplication;
use App\Models\SellerFinanceTransaction;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerFinanceManagementTest extends TestCase
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

        // Ensure default settings
        Setting::set('seller_finance_status', 'not_available');
        Setting::set('seller_finance_provider_name', '');
        Setting::set('seller_finance_disclaimer', 'ALLZONE is an e-commerce technology marketplace and does not operate as a bank, NBFC, or credit institution.');
        Setting::set('seller_finance_min_months_active', '3');
        Setting::set('seller_finance_min_orders', '25');
        Setting::clearRuntimeCache();

        // Primary Seller
        $this->sellerUser = User::factory()->create([
            'email' => 'finance.seller@allzone.in',
            'phone' => '+919876543301',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Retail Solutions',
            'legal_name' => 'Apex Retail Solutions Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now()->subMonths(6),
        ]);

        // Rival Seller (for multi-tenant isolation verification)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.finance@allzone.in',
            'phone' => '+919876543302',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Vortex Traders',
            'legal_name' => 'Vortex Traders LLP',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now()->subMonths(8),
        ]);

        // Admin User
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'email' => 'finance_governance@allzone.in',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_seller_views_finance_page_in_not_available_state_with_clear_badge_and_disclaimer(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.finance.index'));

        $response->assertStatus(200);
        $response->assertSee('Seller Growth Capital &amp; Financing', false);
        $response->assertSee('Not Available');
        $response->assertSee('Regulatory Disclosure &amp; Platform Lending Disclaimer', false);
        $response->assertSee('does not operate as a bank, NBFC, or credit institution');
        $response->assertDontSee('Instant Cash'); // Meesho brand violation check
        $response->assertDontSee('Pay Later Lite');
    }

    public function test_admin_can_toggle_finance_status_to_coming_soon(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.settings.update'), [
                'seller_finance_status' => 'coming_soon',
                'seller_finance_provider_name' => '',
                'seller_finance_disclaimer' => 'ALLZONE is preparing an authorized financial referral program with licensed partners.',
                'seller_finance_min_months_active' => 3,
                'seller_finance_min_orders' => 20,
            ]);

        $response->assertRedirect();
        $this->assertEquals('coming_soon', Setting::get('seller_finance_status'));

        // Seller now sees "Coming Soon" state with registration form
        $sellerResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.finance.index'));

        $sellerResponse->assertStatus(200);
        $sellerResponse->assertSee('Coming Soon');
        $sellerResponse->assertSee('Register Interest for Launch Notification');
    }

    public function test_seller_can_register_interest_in_coming_soon_state(): void
    {
        Setting::set('seller_finance_status', 'coming_soon');
        Setting::clearRuntimeCache();

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.finance.apply'), [
                'seller_notes' => 'Looking for seasonal working capital for Q4 inventory.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_finance_applications', [
            'seller_id' => $this->seller->id,
            'application_type' => 'waitlist',
            'status' => 'registered',
            'seller_notes' => 'Looking for seasonal working capital for Q4 inventory.',
            'approved_limit' => 0.00,
            'outstanding_balance' => 0.00,
        ]);

        // Duplicate registration attempts return informative notice
        $dupResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.finance.apply'), [
                'seller_notes' => 'Duplicate attempt.',
            ]);

        $dupResponse->assertSessionHas('info');
    }

    public function test_multivendor_tenant_isolation_on_finance_applications(): void
    {
        // Application belonging to rival seller
        $rivalApp = SellerFinanceApplication::create([
            'seller_id' => $this->otherSeller->id,
            'application_type' => 'credit_line_inquiry',
            'requested_amount' => 500000.00,
            'approved_limit' => 350000.00,
            'outstanding_balance' => 120000.00,
            'pan_number' => 'ABCDE1234F',
            'status' => 'approved',
            'provider_name' => 'Licensed NBFC Partner',
        ]);

        // Primary seller visits page and never sees rival data or limits
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.finance.index'));

        $response->assertStatus(200);
        $response->assertDontSee('350,000');
        $response->assertDontSee('120,000');
        $response->assertDontSee('ABCDE1234F');
    }

    public function test_admin_can_enable_active_partner_and_seller_submits_credit_inquiry(): void
    {
        // Admin configures an authorized NBFC partner
        $adminResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.settings.update'), [
                'seller_finance_status' => 'active_partner',
                'seller_finance_provider_name' => 'HDFC Credila &amp; Partner NBFC',
                'seller_finance_disclaimer' => 'Credit underwriting is performed solely by HDFC Credila &amp; Partner NBFC.',
                'seller_finance_min_months_active' => 3,
                'seller_finance_min_orders' => 25,
            ]);

        $adminResponse->assertRedirect();
        $this->assertEquals('active_partner', Setting::get('seller_finance_status'));

        // Seller submits application
        $applyResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.finance.apply'), [
                'requested_amount' => 250000.00,
                'pan_number' => 'ABCDE1234F',
                'gstin' => '29ABCDE1234F1Z5',
                'annual_turnover' => 1800000.00,
                'seller_notes' => 'Expanding warehouse inventory for apparel section.',
                'terms_accepted' => 1,
            ]);

        $applyResponse->assertRedirect();
        $applyResponse->assertSessionHas('success');

        $this->assertDatabaseHas('seller_finance_applications', [
            'seller_id' => $this->seller->id,
            'application_type' => 'credit_line_inquiry',
            'requested_amount' => 250000.00,
            'pan_number' => 'ABCDE1234F',
            'gstin' => '29ABCDE1234F1Z5',
            'annual_turnover' => 1800000.00,
            'status' => 'under_review',
            'approved_limit' => 0.00,
            'outstanding_balance' => 0.00,
        ]);
    }

    public function test_seller_application_validates_pan_and_turnover_under_active_partner(): void
    {
        Setting::set('seller_finance_status', 'active_partner');
        Setting::set('seller_finance_provider_name', 'Authorized Bank');
        Setting::clearRuntimeCache();

        // Invalid PAN format and missing terms acceptance
        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.finance.apply'), [
                'requested_amount' => 100000.00,
                'pan_number' => 'INVALID_PAN',
                'annual_turnover' => 500000.00,
                'terms_accepted' => 0,
            ]);

        $response->assertSessionHasErrors(['pan_number', 'terms_accepted']);
    }

    public function test_admin_can_review_and_approve_application_with_credit_limit(): void
    {
        $application = SellerFinanceApplication::create([
            'seller_id' => $this->seller->id,
            'application_type' => 'credit_line_inquiry',
            'requested_amount' => 400000.00,
            'pan_number' => 'XYZPA9876Q',
            'annual_turnover' => 3000000.00,
            'status' => 'under_review',
            'provider_name' => 'Licensed Bank Partner',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.finance.applications.update', $application), [
                'status' => 'approved',
                'approved_limit' => 300000.00,
                'provider_reference_id' => 'BANK-FACILITY-2026-88',
                'admin_notes' => 'Approved after clean GST verification and 6-month transaction track record.',
            ]);

        $response->assertRedirect();

        $fresh = $application->fresh();
        $this->assertEquals('approved', $fresh->status);
        $this->assertEquals(300000.00, (float) $fresh->approved_limit);
        $this->assertEquals('BANK-FACILITY-2026-88', $fresh->provider_reference_id);
    }

    public function test_admin_can_record_verified_ledger_transactions_and_update_outstanding_balance(): void
    {
        $application = SellerFinanceApplication::create([
            'seller_id' => $this->seller->id,
            'application_type' => 'credit_line_inquiry',
            'requested_amount' => 200000.00,
            'approved_limit' => 200000.00,
            'outstanding_balance' => 0.00,
            'status' => 'approved',
            'provider_name' => 'Licensed NBFC',
        ]);

        // 1. Disbursement transaction (+50,000)
        $disburseResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.transactions.record', $application), [
                'transaction_type' => 'disbursement',
                'amount' => 50000.00,
                'reference_id' => 'DISB-2026-001',
                'notes' => 'Tranche 1 disbursed to verified seller bank account.',
            ]);

        $disburseResponse->assertRedirect();
        $this->assertEquals(50000.00, (float) $application->fresh()->outstanding_balance);

        // 2. Repayment transaction (-20,000)
        $repayResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.transactions.record', $application), [
                'transaction_type' => 'repayment',
                'amount' => 20000.00,
                'reference_id' => 'REPAY-2026-001',
                'notes' => 'Settlement offset deduction.',
            ]);

        $repayResponse->assertRedirect();
        $this->assertEquals(30000.00, (float) $application->fresh()->outstanding_balance);

        $this->assertDatabaseHas('seller_finance_transactions', [
            'finance_application_id' => $application->id,
            'seller_id' => $this->seller->id,
            'transaction_type' => 'repayment',
            'amount' => 20000.00,
            'reference_id' => 'REPAY-2026-001',
        ]);
    }

    public function test_zero_fake_credit_limits_and_proper_default_zero_balances(): void
    {
        Setting::set('seller_finance_status', 'active_partner');
        Setting::clearRuntimeCache();

        // Seller without any approved loan has strictly 0.00 balance
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.finance.index'));

        $response->assertStatus(200);
        $response->assertSee('₹0.00');
        $response->assertSee('No active credit facility limit');
        $response->assertSee('No current outstanding dues');
    }
}
