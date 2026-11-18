<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\SellerStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\User;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminClaimsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $sellerUser;
    protected Seller $seller;
    protected SellerClaim $claim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create(['name' => 'Admin User', 'email' => 'admin.claims@allzone.in']);
        $this->adminUser->assignRole($adminRole);

        $this->sellerUser = User::factory()->create([
            'email' => 'merchant.claim@allzone.in',
            'wallet_balance' => 500.00,
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Pioneer Electronics',
            'status' => SellerStatus::APPROVED,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-CLM-9090',
        ]);

        $this->claim = SellerClaim::create([
            'claim_number' => 'CLM-ADM-1001',
            'seller_id' => $this->seller->id,
            'order_id' => $order->id,
            'claim_type' => ClaimType::RTO_DAMAGE,
            'reason' => 'Item arrived damaged from carrier',
            'description' => 'Smartphone screen shattered during reverse shipping.',
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 1500.00,
            'approved_amount' => 0.00,
        ]);
    }

    /**
     * Test 1: Admin can view claims index with platform-wide metrics.
     */
    public function test_admin_can_view_claims_index_with_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.claims.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.claims.index');
        $response->assertSee('Seller Claims &amp; Disputes', false);
        $response->assertSee('CLM-ADM-1001');
        $response->assertSee('Pioneer Electronics');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total']);
        $this->assertEquals(1, $metrics['open']);
    }

    /**
     * Test 2: Admin can add an internal staff note.
     */
    public function test_admin_can_add_internal_note(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.internal_note', $this->claim),
            ['note' => 'Spoke with Delhivery representative. AWB damage claim initiated on their portal.']
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $this->claim->id,
            'user_id' => $this->adminUser->id,
            'is_internal' => true,
            'action_type' => 'internal_note',
            'message' => 'Spoke with Delhivery representative. AWB damage claim initiated on their portal.',
        ]);
    }

    /**
     * Test 3: Admin can request additional proof from merchant.
     */
    public function test_admin_can_request_more_information(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.request_info', $this->claim),
            ['instructions' => 'Please provide the high-resolution photo of the shipping waybill label attached to the outer carton.']
        );

        $response->assertSessionHas('success');

        $this->claim->refresh();
        $this->assertEquals(ClaimStatus::MORE_INFO_NEEDED, $this->claim->status);

        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $this->claim->id,
            'is_internal' => false,
            'action_type' => 'more_info_requested',
        ]);
    }

    /**
     * Test 4: Admin can approve claim with compensation, crediting seller wallet.
     */
    public function test_admin_can_approve_claim_and_credits_seller_wallet(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.approve', $this->claim),
            [
                'approved_amount' => 1200.00,
                'approval_notes' => 'Approved 80% compensation as per carrier liability settlement.',
            ]
        );

        $response->assertSessionHas('success');

        $this->claim->refresh();
        $this->assertEquals(ClaimStatus::APPROVED, $this->claim->status);
        $this->assertEquals(1200.00, (float) $this->claim->approved_amount);
        $this->assertNotNull($this->claim->compensated_at);
        $this->assertNotNull($this->claim->compensation_reference);

        // Verify seller wallet credit
        $this->sellerUser->refresh();
        $this->assertEquals(1700.00, (float) $this->sellerUser->wallet_balance); // 500 initial + 1200 credit

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->sellerUser->id,
            'type' => 'credit',
            'amount' => 1200.00,
            'balance_after' => 1700.00,
            'reference_type' => SellerClaim::class,
            'reference_id' => $this->claim->id,
        ]);
    }

    /**
     * Test 5: Duplicate compensation is prevented.
     */
    public function test_duplicate_compensation_is_prevented(): void
    {
        // 1. First approval
        $this->actingAs($this->adminUser)->post(
            route('admin.claims.approve', $this->claim),
            ['approved_amount' => 1000.00]
        );

        // 2. Second approval attempt
        $secondResponse = $this->actingAs($this->adminUser)->post(
            route('admin.claims.approve', $this->claim),
            ['approved_amount' => 1000.00]
        );

        $secondResponse->assertSessionHas('error');
    }

    /**
     * Test 6: Admin can reject claim with mandatory reason.
     */
    public function test_admin_can_reject_claim_with_mandatory_reason(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.reject', $this->claim),
            ['rejection_reason' => 'Claim was submitted 45 days after delivery, exceeding the 7-day policy filing deadline.']
        );

        $response->assertSessionHas('success');

        $this->claim->refresh();
        $this->assertEquals(ClaimStatus::REJECTED, $this->claim->status);
        $this->assertEquals(
            'Claim was submitted 45 days after delivery, exceeding the 7-day policy filing deadline.',
            $this->claim->rejection_reason
        );
    }

    /**
     * Test 7: Admin can close a resolved claim.
     */
    public function test_admin_can_close_claim(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.close', $this->claim),
            ['close_reason' => 'Direct merchant settlement agreed via phone mediation.']
        );

        $response->assertSessionHas('success');

        $this->claim->refresh();
        $this->assertEquals(ClaimStatus::CLOSED, $this->claim->status);
        $this->assertNotNull($this->claim->closed_at);

        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $this->claim->id,
            'action_type' => 'closed',
        ]);
    }

    /**
     * Test 8: Admin can escalate a dispute for senior review.
     */
    public function test_admin_can_escalate_dispute(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.escalate', $this->claim),
            ['escalation_reason' => 'Courier proof of delivery weight differs by 1.2kg from warehouse dispatch weight.']
        );

        $response->assertSessionHas('success');

        $this->claim->refresh();
        $this->assertEquals(ClaimStatus::UNDER_REVIEW, $this->claim->status);
        $this->assertStringContainsString('DISPUTE ESCALATED', $this->claim->admin_notes);

        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $this->claim->id,
            'action_type' => 'escalated',
        ]);
    }

    /**
     * Test 9: Admin cannot approve claim with amount exceeding claimed amount.
     */
    public function test_admin_cannot_approve_amount_exceeding_claimed_amount(): void
    {
        // Claimed amount is 1500.00, attempting to approve 2500.00
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.claims.approve', $this->claim),
            ['approved_amount' => 2500.00]
        );

        $response->assertSessionHasErrors('approved_amount');
        $this->assertEquals(ClaimStatus::OPEN, $this->claim->fresh()->status);
    }

    /**
     * Test 10: Admin can view claim details page with evidence and related shipment.
     */
    public function test_admin_can_view_claim_details_page(): void
    {
        $this->claim->update([
            'evidence_urls' => ['https://example.com/damage-photo-1.jpg', 'https://example.com/outer-box.jpg'],
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.claims.show', $this->claim));

        $response->assertOk();
        $response->assertSee($this->claim->claim_number);
        $response->assertSee('Pioneer Electronics');
        $response->assertSee('Item arrived damaged from carrier');
        $response->assertSee('Smartphone screen shattered during reverse shipping');
        $response->assertSee('damage-photo-1.jpg');
    }

    /**
     * Test 11: Admin can filter claims by status, category, and search query.
     */
    public function test_admin_can_filter_claims_by_status_and_search(): void
    {
        $claim2 = SellerClaim::create([
            'claim_number' => 'CLM-ADM-2002',
            'seller_id' => $this->seller->id,
            'order_id' => $this->claim->order_id,
            'claim_type' => ClaimType::COURIER_LOSS,
            'reason' => 'Logistics carrier lost consignment',
            'description' => 'Delivery marked lost by Delhivery courier.',
            'status' => ClaimStatus::CLOSED,
            'claimed_amount' => 3200.00,
            'approved_amount' => 0.00,
        ]);

        // Filter by status=closed
        $responseClosed = $this->actingAs($this->adminUser)->get(route('admin.claims.index', ['status' => 'closed']));
        $responseClosed->assertOk();
        $responseClosed->assertSee($claim2->claim_number);
        $responseClosed->assertDontSee($this->claim->claim_number);

        // Search by claim_number
        $responseSearch = $this->actingAs($this->adminUser)->get(route('admin.claims.index', ['search' => 'CLM-ADM-2002']));
        $responseSearch->assertOk();
        $responseSearch->assertSee($claim2->claim_number);
        $responseSearch->assertDontSee($this->claim->claim_number);
    }
}
