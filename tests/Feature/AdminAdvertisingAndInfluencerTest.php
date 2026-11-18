<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Category;
use App\Models\InfluencerCollaboration;
use App\Models\InfluencerProfile;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerAdCampaign;
use App\Models\SellerAdCampaignProduct;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdvertisingAndInfluencerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $sellerUser;
    protected Seller $seller;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Admin User
        $this->adminUser = User::factory()->create([
            'email' => 'ad.admin@allzone.in',
            'name' => 'Ad Governance Lead',
        ]);
        $this->adminUser->assignRole('admin');

        // Customer User (for access control checks)
        $this->customerUser = User::factory()->create([
            'email' => 'regular.buyer@allzone.in',
            'name' => 'Regular Buyer',
        ]);
        $this->customerUser->assignRole('customer');

        // Category
        $this->category = Category::factory()->create([
            'name' => 'Smart Electronics',
            'slug' => 'smart-electronics',
        ]);

        // Seller
        $this->sellerUser = User::factory()->create([
            'email' => 'ad.merchant@allzone.in',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Acrobat Electronics India',
            'legal_name' => 'Acrobat Electronics Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Wireless ANC Headphones Pro',
            'slug' => 'wireless-anc-headphones-pro',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'selling_price' => 2999.00,
            'mrp' => 4999.00,
            'is_default' => true,
        ]);
    }

    public function test_admin_can_view_ad_campaigns_listing_with_metrics_and_filters(): void
    {
        $campaign1 = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Diwali Audio Blitz',
            'type' => 'banner_display',
            'status' => 'pending_approval',
            'daily_budget' => 500.00,
            'total_budget' => 5000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $campaign2 = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Mega Sponsored Gadgets',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 300.00,
            'total_budget' => 3000.00,
            'spent_amount' => 1200.00,
            'impressions_count' => 15000,
            'clicks_count' => 600,
            'attributed_orders_count' => 24,
            'attributed_revenue' => 71976.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.ads.index'));

        $response->assertOk();
        $response->assertSee('Diwali Audio Blitz');
        $response->assertSee('Mega Sponsored Gadgets');
        $response->assertSee('Acrobat Electronics India');

        // Test status filtering
        $filterResponse = $this->actingAs($this->adminUser)->get(route('admin.ads.index', ['status' => 'pending_approval']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Diwali Audio Blitz');
        $filterResponse->assertDontSee('Mega Sponsored Gadgets');
    }

    public function test_admin_can_inspect_creative_review_details(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Premium Audio Banner Showcase',
            'type' => 'banner_display',
            'status' => 'pending_approval',
            'headline' => 'Crystal Clear Sound at 40% Off',
            'creative_image_url' => 'https://cdn.allzone.in/ads/audio-banner-promo.jpg',
            'targeting_payload' => [
                'categories' => [$this->category->id],
                'locations' => ['Mumbai', 'Delhi NCR', 'Bengaluru'],
            ],
            'daily_budget' => 1000.00,
            'total_budget' => 10000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        SellerAdCampaignProduct::create([
            'ad_campaign_id' => $campaign->id,
            'product_id' => $this->product->id,
            'bid_amount' => 45.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.ads.show', $campaign));

        $response->assertOk();
        $response->assertSee('Premium Audio Banner Showcase');
        $response->assertSee('Crystal Clear Sound at 40% Off');
        $response->assertSee('https://cdn.allzone.in/ads/audio-banner-promo.jpg');
        $response->assertSee('Wireless ANC Headphones Pro');
        $response->assertSee('45.00');
        $response->assertSee('Acrobat Electronics India');
    }

    public function test_admin_can_approve_pending_ad_campaign(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Soundbar Spectacular',
            'type' => 'sponsored_listing',
            'status' => 'pending_approval',
            'daily_budget' => 400.00,
            'total_budget' => 4000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.ads.approve', $campaign));

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('active', $campaign->status);
        $this->assertNotNull($campaign->approved_at);
        $this->assertEquals($this->adminUser->id, $campaign->approved_by);
        $this->assertNull($campaign->rejection_reason);
    }

    public function test_admin_can_reject_ad_campaign_with_mandatory_reason(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Questionable Creative Ad',
            'type' => 'banner_display',
            'status' => 'pending_approval',
            'daily_budget' => 500.00,
            'total_budget' => 5000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        // Validation error if rejection reason is missing
        $failResponse = $this->actingAs($this->adminUser)->post(route('admin.ads.reject', $campaign), [
            'rejection_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('rejection_reason');

        // Successful rejection
        $response = $this->actingAs($this->adminUser)->post(route('admin.ads.reject', $campaign), [
            'rejection_reason' => 'Misleading promotional banner claims violate marketplace advertising guidelines.',
        ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('rejected', $campaign->status);
        $this->assertEquals('Misleading promotional banner claims violate marketplace advertising guidelines.', $campaign->rejection_reason);
    }

    public function test_admin_can_update_ad_campaign_delivery_status(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Smart Watch Launch Ad',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 600.00,
            'total_budget' => 6000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        // Pause campaign
        $response = $this->actingAs($this->adminUser)->post(route('admin.ads.status', $campaign), [
            'status' => 'paused',
        ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        // Resume campaign
        $resumeResponse = $this->actingAs($this->adminUser)->post(route('admin.ads.status', $campaign), [
            'status' => 'active',
        ]);

        $resumeResponse->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('active', $campaign->status);
    }

    public function test_admin_can_update_budget_and_billing_configuration(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Earbuds Campaign',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 200.00,
            'total_budget' => 2000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.ads.budget', $campaign), [
            'daily_budget' => 500.00,
            'total_budget' => 8000.00,
            'billing_status' => 'deducted_from_payout',
            'billing_notes' => 'Adjusted daily budget to ₹500 and configured deduction from upcoming bi-weekly settlement.',
        ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(500.00, (float) $campaign->daily_budget);
        $this->assertEquals(8000.00, (float) $campaign->total_budget);
        $this->assertEquals('deducted_from_payout', $campaign->billing_status);
        $this->assertEquals('Adjusted daily budget to ₹500 and configured deduction from upcoming bi-weekly settlement.', $campaign->billing_notes);
    }

    public function test_admin_can_update_verified_delivery_metrics(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'High-Fidelity Audio Blitz',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 1000.00,
            'total_budget' => 10000.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.ads.metrics.update', $campaign), [
            'impressions_count' => 25400,
            'clicks_count' => 1270,
            'attributed_orders_count' => 45,
            'attributed_revenue' => 134955.00,
            'spent_amount' => 4500.00,
        ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals(25400, $campaign->impressions_count);
        $this->assertEquals(1270, $campaign->clicks_count);
        $this->assertEquals(45, $campaign->attributed_orders_count);
        $this->assertEquals(134955.00, (float) $campaign->attributed_revenue);
        $this->assertEquals(4500.00, (float) $campaign->spent_amount);
        $this->assertEquals(5.0, (float) $campaign->ctr); // (1270 / 25400) * 100
        $this->assertEquals(29.99, (float) $campaign->roi); // 134955 / 4500
    }

    public function test_admin_can_export_ad_campaigns_to_csv(): void
    {
        SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Exportable Audio Blast',
            'type' => 'banner_display',
            'status' => 'active',
            'daily_budget' => 750.00,
            'total_budget' => 7500.00,
            'spent_amount' => 1500.00,
            'impressions_count' => 20000,
            'clicks_count' => 800,
            'attributed_orders_count' => 30,
            'attributed_revenue' => 89970.00,
            'billing_status' => 'active_wallet',
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.ads.export'));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Exportable Audio Blast', $response->streamedContent());
        $this->assertStringContainsString('Acrobat Electronics India', $response->streamedContent());
    }

    public function test_admin_can_onboard_and_verify_influencer_profile(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.influencers.profiles.store'), [
            'name' => 'Vikram Tech Insights',
            'handle' => '@vikramtech_in',
            'contact_email' => 'partnerships@vikramtech.in',
            'platform' => 'youtube',
            'category_id' => $this->category->id,
            'verified_followers' => 450000,
            'collaboration_fee' => 15000.00,
            'bio' => 'Consumer tech and unboxing reviews with 450K authentic Indian subscribers.',
            'verification_notes' => 'Audited view counts and subscriber retention metrics.',
        ]);

        $response->assertRedirect();

        $profile = InfluencerProfile::where('handle', '@vikramtech_in')->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Vikram Tech Insights', $profile->name);
        $this->assertEquals('youtube', $profile->platform);
        $this->assertEquals(450000, $profile->verified_followers);
        $this->assertEquals(15000.00, (float) $profile->collaboration_fee);
        $this->assertTrue($profile->is_approved);
        $this->assertEquals('active', $profile->status);

        // Toggle verification status
        $toggleResponse = $this->actingAs($this->adminUser)->post(route('admin.influencers.profiles.toggle_verification', $profile), [
            'verification_notes' => 'Temporarily revoked verification pending re-audit of followers.',
        ]);

        $toggleResponse->assertRedirect();
        $profile->refresh();
        $this->assertFalse($profile->is_approved);
        $this->assertEquals('Temporarily revoked verification pending re-audit of followers.', $profile->verification_notes);
    }

    public function test_admin_can_update_influencer_profile(): void
    {
        $profile = InfluencerProfile::create([
            'name' => 'Rohan Sound Geek',
            'handle' => '@rohan_audio',
            'contact_email' => 'rohan@audiogeek.in',
            'platform' => 'instagram',
            'category_id' => $this->category->id,
            'verified_followers' => 120000,
            'collaboration_fee' => 8000.00,
            'bio' => 'Audiophile gear reviews.',
            'is_approved' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.influencers.profiles.update', $profile), [
            'name' => 'Rohan Sound Geek Official',
            'handle' => '@rohan_audio_official',
            'contact_email' => 'rohan.business@audiogeek.in',
            'platform' => 'instagram',
            'category_id' => $this->category->id,
            'verified_followers' => 150000,
            'collaboration_fee' => 10000.00,
            'bio' => 'Premium audiophile gear reviews and studio sound tests.',
            'status' => 'active',
            'verification_notes' => 'Follower milestone 150K reached.',
        ]);

        $response->assertRedirect();
        $profile->refresh();
        $this->assertEquals('Rohan Sound Geek Official', $profile->name);
        $this->assertEquals('@rohan_audio_official', $profile->handle);
        $this->assertEquals(150000, $profile->verified_followers);
        $this->assertEquals(10000.00, (float) $profile->collaboration_fee);
    }

    public function test_admin_can_govern_collaboration_deliverables_and_performance(): void
    {
        $profile = InfluencerProfile::create([
            'name' => 'Priya Audio Styles',
            'handle' => '@priya_styles',
            'platform' => 'instagram',
            'category_id' => $this->category->id,
            'verified_followers' => 85000,
            'collaboration_fee' => 7500.00,
            'is_approved' => true,
            'status' => 'active',
        ]);

        $collaboration = InfluencerCollaboration::create([
            'seller_id' => $this->seller->id,
            'influencer_profile_id' => $profile->id,
            'product_id' => $this->product->id,
            'campaign_title' => 'Festive Audio Showcase Reel',
            'proposed_budget' => 7500.00,
            'status' => 'in_progress',
            'payment_status' => 'in_escrow',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.influencers.collaborations.status', $collaboration), [
            'status' => 'completed',
            'deliverable_url' => 'https://instagram.com/reel/DC543210',
            'admin_notes' => 'Reel reviewed and approved. Product link tagged and verified.',
            'impressions' => 45000,
            'clicks' => 2200,
            'attributed_orders' => 65,
            'attributed_revenue' => 194935.00,
        ]);

        $response->assertRedirect();
        $collaboration->refresh();
        $this->assertEquals('completed', $collaboration->status);
        $this->assertEquals('https://instagram.com/reel/DC543210', $collaboration->deliverable_url);
        $this->assertEquals(45000, $collaboration->impressions);
        $this->assertEquals(2200, $collaboration->clicks);
        $this->assertEquals(65, $collaboration->attributed_orders);
        $this->assertEquals(194935.00, (float) $collaboration->attributed_revenue);
    }

    public function test_admin_can_record_collaboration_payment_reference(): void
    {
        $profile = InfluencerProfile::create([
            'name' => 'Suresh Tech Hub',
            'handle' => '@sureshtech',
            'platform' => 'youtube',
            'category_id' => $this->category->id,
            'verified_followers' => 200000,
            'collaboration_fee' => 12000.00,
            'is_approved' => true,
            'status' => 'active',
        ]);

        $collaboration = InfluencerCollaboration::create([
            'seller_id' => $this->seller->id,
            'influencer_profile_id' => $profile->id,
            'product_id' => $this->product->id,
            'campaign_title' => 'Noise Cancellation Test Video',
            'proposed_budget' => 12000.00,
            'status' => 'completed',
            'payment_status' => 'in_escrow',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.influencers.collaborations.payment', $collaboration), [
            'payment_status' => 'paid',
            'payment_reference' => 'NEFT-AXIS-99238472918',
        ]);

        $response->assertRedirect();
        $collaboration->refresh();
        $this->assertEquals('paid', $collaboration->payment_status);
        $this->assertEquals('NEFT-AXIS-99238472918', $collaboration->payment_reference);
        $this->assertNotNull($collaboration->paid_at);
    }

    public function test_admin_can_export_influencer_collaborations_to_csv(): void
    {
        $profile = InfluencerProfile::create([
            'name' => 'Ananya Lifestyle',
            'handle' => '@ananya_life',
            'platform' => 'instagram',
            'category_id' => $this->category->id,
            'verified_followers' => 300000,
            'collaboration_fee' => 18000.00,
            'is_approved' => true,
            'status' => 'active',
        ]);

        InfluencerCollaboration::create([
            'seller_id' => $this->seller->id,
            'influencer_profile_id' => $profile->id,
            'product_id' => $this->product->id,
            'campaign_title' => 'Exportable Sound Review',
            'proposed_budget' => 18000.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_reference' => 'IMPS-HDFC-883719401',
            'deliverable_url' => 'https://instagram.com/reel/AN123456',
            'impressions' => 60000,
            'clicks' => 3000,
            'attributed_orders' => 80,
            'attributed_revenue' => 239920.00,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.influencers.export'));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Exportable Sound Review', $response->streamedContent());
        $this->assertStringContainsString('Ananya Lifestyle', $response->streamedContent());
        $this->assertStringContainsString('Acrobat Electronics India', $response->streamedContent());
    }

    public function test_unauthorized_user_cannot_access_admin_ad_and_influencer_endpoints(): void
    {
        // Unauthenticated access
        $this->get(route('admin.ads.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.influencers.index'))->assertRedirect(route('admin.login'));

        // Customer access forbidden
        $this->actingAs($this->customerUser)->get(route('admin.ads.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('admin.influencers.index'))->assertForbidden();
    }
}
