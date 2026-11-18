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
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerAdvertisingAndInfluencerTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Product $product;
    protected ProductVariant $variant;

    protected User $otherSellerUser;
    protected Seller $otherSeller;
    protected Product $otherProduct;

    protected User $adminUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->category = Category::factory()->create([
            'name' => 'Fashion & Apparel',
            'slug' => 'fashion-and-apparel',
        ]);

        // Primary Seller
        $this->sellerUser = User::factory()->create([
            'email' => 'ad.seller@allzone.in',
            'phone' => '+919876543201',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Apparel India',
            'legal_name' => 'Zenith Apparel Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Slim Fit Cotton Shirt',
            'slug' => 'slim-fit-cotton-shirt',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'selling_price' => 899.00,
            'mrp' => 1499.00,
            'is_default' => true,
        ]);

        // Rival Seller (for multi-tenant isolation verification)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.ads@allzone.in',
            'phone' => '+919876543202',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Vogue Couture Hub',
            'legal_name' => 'Vogue Couture Hub Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->otherProduct = Product::factory()->create([
            'seller_id' => $this->otherSeller->id,
            'category_id' => $this->category->id,
            'title' => 'Linen Casual Shirt',
            'slug' => 'linen-casual-shirt',
            'status' => ProductStatus::ACTIVE,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $this->otherProduct->id,
            'selling_price' => 1299.00,
            'mrp' => 1999.00,
            'is_default' => true,
        ]);

        // Admin User
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'email' => 'ads_governance@allzone.in',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_seller_can_view_ad_dashboard_with_real_metrics_and_tabs(): void
    {
        // Create an active campaign with real spend and revenue
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Summer Festive Boost',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 500.00,
            'total_budget' => 5000.00,
            'spent_amount' => 1200.00,
            'impressions_count' => 8500,
            'clicks_count' => 340,
            'attributed_orders_count' => 15,
            'attributed_revenue' => 13485.00,
            'starts_at' => now()->subDays(3),
        ]);

        SellerAdCampaignProduct::create([
            'ad_campaign_id' => $campaign->id,
            'product_id' => $this->product->id,
            'bid_amount' => 3.50,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.ads.index'));

        $response->assertStatus(200);
        $response->assertSee('Seller Advertising &amp; Sponsored Listings', false);
        $response->assertSee('Summer Festive Boost');
        $response->assertSee('₹1,200.00'); // Total ad spend
        $response->assertSee('8,500'); // Impressions
        $response->assertSee('340'); // Clicks
        $response->assertSee('15'); // Orders
        $response->assertSee('₹13,485.00'); // Revenue
        $response->assertSee('11.24x'); // ROI (13485 / 1200)
    }

    public function test_multivendor_tenant_isolation_on_ad_campaigns(): void
    {
        // Campaign belonging to rival seller
        $rivalCampaign = SellerAdCampaign::create([
            'seller_id' => $this->otherSeller->id,
            'name' => 'Rival Exclusive Mega Sale',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 800.00,
            'total_budget' => 8000.00,
            'spent_amount' => 2400.00,
            'impressions_count' => 12000,
            'clicks_count' => 600,
            'attributed_orders_count' => 30,
            'attributed_revenue' => 38970.00,
            'starts_at' => now()->subDays(5),
        ]);

        // Primary seller visits dashboard
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.ads.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Rival Exclusive Mega Sale');
        $response->assertDontSee('₹2,400.00');
        $response->assertDontSee('38,970');

        // Primary seller cannot view rival campaign details (isolated with 403 Forbidden)
        $viewResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.ads.show', $rivalCampaign));

        $viewResponse->assertStatus(403);
    }

    public function test_seller_can_create_ad_campaign_with_selected_products_and_bids(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.ads.store'), [
                'name' => 'Festival Ethnic Collection Push',
                'type' => 'sponsored_listing',
                'starts_at' => now()->addDay()->toDateString(),
                'ends_at' => now()->addDays(14)->toDateString(),
                'daily_budget' => 600.00,
                'total_budget' => 6000.00,
                'targeting_type' => 'category',
                'selected_products' => [$this->product->id],
                'bids' => [
                    $this->product->id => 4.50,
                ],
                'action' => 'submit',
            ]);

        $response->assertRedirect(route('seller.ads.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_ad_campaigns', [
            'seller_id' => $this->seller->id,
            'name' => 'Festival Ethnic Collection Push',
            'type' => 'sponsored_listing',
            'status' => 'pending_approval',
            'daily_budget' => 600.00,
            'total_budget' => 6000.00,
        ]);

        $campaign = SellerAdCampaign::where('name', 'Festival Ethnic Collection Push')->firstOrFail();

        $this->assertDatabaseHas('seller_ad_campaign_products', [
            'ad_campaign_id' => $campaign->id,
            'product_id' => $this->product->id,
            'bid_amount' => 4.50,
        ]);
    }

    public function test_seller_can_save_ad_campaign_as_draft(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.ads.store'), [
                'name' => 'Draft Winter Collection Campaign',
                'type' => 'sponsored_listing',
                'starts_at' => now()->addDays(5)->toDateString(),
                'daily_budget' => 400.00,
                'total_budget' => 4000.00,
                'targeting_type' => 'search_keywords',
                'targeting_keywords' => 'cotton, formal, casual, shirts',
                'selected_products' => [$this->product->id],
                'bids' => [
                    $this->product->id => 3.00,
                ],
                'action' => 'save_draft',
            ]);

        $response->assertRedirect(route('seller.ads.index'));

        $this->assertDatabaseHas('seller_ad_campaigns', [
            'seller_id' => $this->seller->id,
            'name' => 'Draft Winter Collection Campaign',
            'status' => 'draft',
        ]);
    }

    public function test_seller_can_toggle_active_ad_campaign_status_pause_and_resume(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Ongoing Evergreen Ad',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 300.00,
            'total_budget' => 3000.00,
            'starts_at' => now()->subDay(),
        ]);

        // Pause campaign
        $pauseResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.ads.toggle', $campaign));

        $pauseResponse->assertRedirect();
        $this->assertEquals('paused', $campaign->fresh()->status);

        // Resume campaign
        $resumeResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.ads.toggle', $campaign));

        $resumeResponse->assertRedirect();
        $this->assertEquals('active', $campaign->fresh()->status);
    }

    public function test_seller_cannot_toggle_another_sellers_campaign(): void
    {
        $rivalCampaign = SellerAdCampaign::create([
            'seller_id' => $this->otherSeller->id,
            'name' => 'Rival Live Campaign',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 500.00,
            'total_budget' => 5000.00,
            'starts_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.ads.toggle', $rivalCampaign));

        $response->assertStatus(403);
        $this->assertEquals('active', $rivalCampaign->fresh()->status);
    }

    public function test_admin_can_approve_and_reject_seller_ad_campaign_with_reason(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Diwali Dhamaka Campaign',
            'type' => 'sponsored_listing',
            'status' => 'pending_approval',
            'daily_budget' => 1000.00,
            'total_budget' => 10000.00,
            'starts_at' => now()->addDays(2),
        ]);

        // Admin views ad dashboard
        $viewResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.ads.index'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Diwali Dhamaka Campaign');
        $viewResponse->assertSee('Zenith Apparel India');

        // Admin approves campaign
        $approveResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.ads.approve', $campaign));

        $approveResponse->assertRedirect();
        $this->assertEquals('active', $campaign->fresh()->status);
        $this->assertNotNull($campaign->fresh()->approved_at);
        $this->assertEquals($this->adminUser->id, $campaign->fresh()->approved_by);

        // Another campaign rejected with reason
        $badCampaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Non-Compliant Ad',
            'type' => 'sponsored_listing',
            'status' => 'pending_approval',
            'daily_budget' => 500.00,
            'total_budget' => 2000.00,
            'starts_at' => now()->addDays(2),
        ]);

        $rejectResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.ads.reject', $badCampaign), [
                'rejection_reason' => 'Creative banner contains misleading pricing claims.',
            ]);

        $rejectResponse->assertRedirect();
        $this->assertEquals('rejected', $badCampaign->fresh()->status);
        $this->assertEquals('Creative banner contains misleading pricing claims.', $badCampaign->fresh()->rejection_reason);
    }

    public function test_admin_can_update_verified_ad_metrics(): void
    {
        $campaign = SellerAdCampaign::create([
            'seller_id' => $this->seller->id,
            'name' => 'Verified Delivery Ad',
            'type' => 'sponsored_listing',
            'status' => 'active',
            'daily_budget' => 500.00,
            'total_budget' => 5000.00,
            'starts_at' => now()->subDays(7),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.ads.metrics.update', $campaign), [
                'impressions_count' => 14500,
                'clicks_count' => 580,
                'attributed_orders_count' => 22,
                'attributed_revenue' => 19778.00,
                'spent_amount' => 1850.00,
            ]);

        $response->assertRedirect();

        $fresh = $campaign->fresh();
        $this->assertEquals(14500, $fresh->impressions_count);
        $this->assertEquals(580, $fresh->clicks_count);
        $this->assertEquals(22, $fresh->attributed_orders_count);
        $this->assertEquals(19778.00, (float) $fresh->attributed_revenue);
        $this->assertEquals(1850.00, (float) $fresh->spent_amount);
        $this->assertEquals(10.69, $fresh->roi); // 19778 / 1850 = 10.69
        $this->assertEquals(4.00, $fresh->ctr); // (580 / 14500) * 100 = 4.00%
    }

    public function test_seller_can_browse_influencer_profiles_and_submit_collaboration_proposal(): void
    {
        $influencer = InfluencerProfile::create([
            'name' => 'Ananya Lifestyle & Trends',
            'handle' => '@ananya_trends',
            'platform' => 'instagram',
            'category_id' => $this->category->id,
            'verified_followers' => 85000,
            'collaboration_fee' => 7500.00,
            'bio' => 'Top fashion and sustainable clothing reviewer based in Mumbai.',
            'status' => 'active',
            'is_approved' => true,
        ]);

        // Seller views influencer directory
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.influencer.index'));

        $response->assertStatus(200);
        $response->assertSee('Ananya Lifestyle &amp; Trends', false);
        $response->assertSee('@ananya_trends');
        $response->assertSee('85,000');
        $response->assertSee('₹7,500.00');

        // Seller submits collaboration proposal
        $proposalResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.influencer.collaborate'), [
                'influencer_profile_id' => $influencer->id,
                'campaign_title' => 'Festive Collection Reel Push',
                'product_id' => $this->product->id,
                'proposed_budget' => 8000.00,
                'proposal_notes' => 'Highlight the breathable cotton fabric and showcase modern office styling.',
            ]);

        $proposalResponse->assertRedirect();
        $proposalResponse->assertSessionHas('success');

        $this->assertDatabaseHas('influencer_collaborations', [
            'seller_id' => $this->seller->id,
            'influencer_profile_id' => $influencer->id,
            'campaign_title' => 'Festive Collection Reel Push',
            'product_id' => $this->product->id,
            'proposed_budget' => 8000.00,
            'status' => 'pending',
        ]);
    }

    public function test_multivendor_tenant_isolation_on_influencer_collaborations(): void
    {
        $influencer = InfluencerProfile::create([
            'name' => 'Kavya Style Guide',
            'handle' => '@kavya_style',
            'platform' => 'instagram',
            'verified_followers' => 120000,
            'collaboration_fee' => 12000.00,
            'status' => 'active',
            'is_approved' => true,
        ]);

        $rivalCollab = InfluencerCollaboration::create([
            'seller_id' => $this->otherSeller->id,
            'influencer_profile_id' => $influencer->id,
            'product_id' => $this->otherProduct->id,
            'campaign_title' => 'Rival Brand Endorsement',
            'proposed_budget' => 15000.00,
            'proposal_notes' => 'Private rival creative brief.',
            'status' => 'accepted',
        ]);

        // Primary seller cannot access rival collaboration detail (403 Forbidden)
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.influencer.show', $rivalCollab));

        $response->assertStatus(403);
    }

    public function test_admin_can_add_influencer_profile_and_update_collaboration_status_with_deliverable(): void
    {
        // Admin adds vetted influencer profile
        $createResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.influencers.profiles.store'), [
                'name' => 'Rahul Tech & Style',
                'handle' => '@rahul_techstyle',
                'platform' => 'youtube',
                'category_id' => $this->category->id,
                'verified_followers' => 250000,
                'collaboration_fee' => 25000.00,
                'bio' => 'Tech gadget reviews and daily styling tips for young professionals.',
            ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('influencer_profiles', [
            'handle' => '@rahul_techstyle',
            'platform' => 'youtube',
            'verified_followers' => 250000,
            'is_approved' => true,
            'status' => 'active',
        ]);

        $influencer = InfluencerProfile::where('handle', '@rahul_techstyle')->firstOrFail();

        // Seller has a proposal
        $collab = InfluencerCollaboration::create([
            'seller_id' => $this->seller->id,
            'influencer_profile_id' => $influencer->id,
            'product_id' => $this->product->id,
            'campaign_title' => 'Diwali Tech Style Showcase',
            'proposed_budget' => 25000.00,
            'status' => 'pending',
        ]);

        // Admin updates status to completed with deliverable URL and verified performance
        $updateResponse = $this->actingAs($this->adminUser)
            ->put(route('admin.influencers.collaborations.status', $collab), [
                'status' => 'completed',
                'deliverable_url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ',
                'admin_notes' => 'Video live on YouTube channel. Description link trackable.',
                'impressions' => 45000,
                'clicks' => 3200,
                'attributed_orders' => 110,
                'attributed_revenue' => 98890.00,
            ]);

        $updateResponse->assertRedirect();

        $fresh = $collab->fresh();
        $this->assertEquals('completed', $fresh->status);
        $this->assertEquals('https://youtube.com/watch?v=dQw4w9WgXcQ', $fresh->deliverable_url);
        $this->assertEquals(45000, $fresh->impressions);
        $this->assertEquals(3200, $fresh->clicks);
        $this->assertEquals(110, $fresh->attributed_orders);
        $this->assertEquals(98890.00, (float) $fresh->attributed_revenue);
        $this->assertEquals(3.96, $fresh->roi); // 98890 / 25000 = 3.96
    }
}
