<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerCampaignParticipation;
use App\Models\SellerCampaignProduct;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerPromotionsManagementTest extends TestCase
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
            'name' => 'Consumer Electronics',
            'slug' => 'consumer-electronics',
        ]);

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'promo.merchant@allzone.in',
            'phone' => '+919876543201',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Aura Audio Systems',
            'legal_name' => 'Aura Audio Systems India Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Wireless ANC Earbuds Pro',
            'slug' => 'wireless-anc-earbuds-pro',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'selling_price' => 2000.00,
            'mrp' => 2500.00,
            'is_default' => true,
        ]);

        // Seller 2 (for tenant isolation tests)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.promo@allzone.in',
            'phone' => '+919876543202',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Prime Gadgets Direct',
            'legal_name' => 'Prime Gadgets Direct Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->otherProduct = Product::factory()->create([
            'seller_id' => $this->otherSeller->id,
            'title' => 'Rival Bluetooth Speaker',
            'slug' => 'rival-bluetooth-speaker',
            'status' => ProductStatus::ACTIVE,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $this->otherProduct->id,
            'selling_price' => 3000.00,
            'mrp' => 3500.00,
            'is_default' => true,
        ]);

        // Admin User
        $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'email' => 'campaign_admin@allzone.in',
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    public function test_seller_can_view_promotions_dashboard_with_all_tabs_and_metrics(): void
    {
        $campaign = Campaign::create([
            'name' => 'Monsoon Electronics Bonanza',
            'slug' => 'monsoon-electronics-bonanza',
            'type' => 'seasonal',
            'description' => 'Huge monsoon savings on high quality audio and kitchen appliances.',
            'min_discount_percentage' => 10.0,
            'max_discount_percentage' => 40.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'opt_in_deadline' => now()->addDays(2),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.index'));

        $response->assertOk();
        $response->assertSee('Promotions &amp; Sale Opt-in', false);
        $response->assertSee('Monsoon Electronics Bonanza');
        $response->assertSee('Min 10');
        $response->assertSee('Available Campaigns');
        $response->assertSee('Live On Storefront');
        $response->assertSee('Transparent Margin');

        // Test other tabs
        $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.index', ['tab' => 'live']))
            ->assertOk()
            ->assertSee('Monsoon Electronics Bonanza');

        $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.index', ['tab' => 'upcoming']))
            ->assertOk();

        $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.index', ['tab' => 'participating']))
            ->assertOk();

        $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.index', ['tab' => 'history']))
            ->assertOk();
    }

    public function test_multivendor_tenant_isolation_on_promotions_and_enrolled_products(): void
    {
        $campaign = Campaign::create([
            'name' => 'Diwali Mega Sale',
            'slug' => 'diwali-mega-sale',
            'type' => 'festive_season',
            'min_discount_percentage' => 15.0,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        // Seller 1 enrolls Product 1
        SellerCampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 2000.00,
            'discount_percentage' => 15.00,
            'campaign_price' => 1700.00,
            'status' => 'enrolled',
        ]);

        // Seller 2 enrolls Product 2
        SellerCampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->otherSeller->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        $otherCampaignProd = SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->otherSeller->id,
            'product_id' => $this->otherProduct->id,
            'original_price' => 3000.00,
            'discount_percentage' => 20.00,
            'campaign_price' => 2400.00,
            'status' => 'enrolled',
        ]);

        // Seller 1 viewing campaign show page must ONLY see their own product, not Seller 2's
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.show', $campaign));

        $response->assertOk();
        $response->assertSee('Wireless ANC Earbuds Pro');
        $response->assertDontSee('Rival Bluetooth Speaker');

        // Seller 1 cannot delete Seller 2's enrolled product
        $this->actingAs($this->sellerUser)
            ->delete(route('seller.promotions.remove_product', [$campaign, $this->otherProduct]))
            ->assertForbidden();

        // Seller 1 cannot opt in Seller 2's product
        $optInAttempt = $this->actingAs($this->sellerUser)
            ->post(route('seller.promotions.opt_in', $campaign), [
                'terms_accepted' => '1',
                'selected_products' => [$this->otherProduct->id],
            ]);

        // Should not enroll other seller's product under Seller 1
        $this->assertDatabaseMissing('seller_campaign_products', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->otherProduct->id,
        ]);
    }

    public function test_seller_can_view_campaign_details_with_eligibility_criteria_and_transparent_benefits(): void
    {
        $campaign = Campaign::create([
            'name' => 'Summer Appliance Carnival',
            'slug' => 'summer-appliance-carnival',
            'type' => 'seasonal',
            'description' => 'Featured seasonal discounts for home comfort and personal audio.',
            'min_discount_percentage' => 10.0,
            'max_discount_percentage' => 30.0,
            'min_product_rating' => 3.5,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(8),
            'allow_opt_out' => true,
            'terms_and_conditions' => 'Discounts are locked by the seller during the active sale.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.show', $campaign));

        $response->assertOk();
        $response->assertSee('Summer Appliance Carnival');
        $response->assertSee('10');
        $response->assertSee('30');
        $response->assertSee('Min rating');
        $response->assertSee('Transparent Math');
        $response->assertSee('How Your Payout is Calculated');
        $response->assertSee('Net Merchant Settlement');
    }

    public function test_seller_can_opt_in_to_campaign_with_eligible_products_and_custom_discounts(): void
    {
        $campaign = Campaign::create([
            'name' => 'Great Electronics Gala',
            'slug' => 'great-electronics-gala',
            'type' => 'flash_sale',
            'min_discount_percentage' => 10.0,
            'max_discount_percentage' => 50.0,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        $payload = [
            'terms_accepted' => '1',
            'selected_products' => [$this->product->id],
            'discounts' => [
                $this->product->id => 15.0,
            ],
        ];

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.promotions.opt_in', $campaign), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Participation recorded
        $this->assertDatabaseHas('seller_campaign_participations', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'enrolled',
            'agreed_to_terms' => 1,
        ]);

        // Product enrolled with correct campaign discount and price calculation
        // Base price = 2000, 15% discount -> 1700
        $this->assertDatabaseHas('seller_campaign_products', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 2000.00,
            'discount_percentage' => 15.00,
            'campaign_price' => 1700.00,
            'status' => 'enrolled',
        ]);
    }

    public function test_seller_opt_in_requires_terms_acceptance(): void
    {
        $campaign = Campaign::create([
            'name' => 'Flash Sale Friday',
            'slug' => 'flash-sale-friday',
            'type' => 'flash_sale',
            'min_discount_percentage' => 5.0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.promotions.opt_in', $campaign), [
                // 'terms_accepted' missing
                'selected_products' => [$this->product->id],
            ]);

        $response->assertSessionHasErrors('terms_accepted');
    }

    public function test_seller_can_opt_out_of_campaign_when_permitted(): void
    {
        $campaign = Campaign::create([
            'name' => 'Spring Clearance Festival',
            'slug' => 'spring-clearance-festival',
            'type' => 'clearance',
            'min_discount_percentage' => 10.0,
            'allow_opt_out' => true,
            'is_active' => true,
        ]);

        // Pre-enroll seller
        SellerCampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);
        SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 2000.00,
            'discount_percentage' => 10.00,
            'campaign_price' => 1800.00,
            'status' => 'enrolled',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.promotions.opt_out', $campaign));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_campaign_participations', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'opted_out',
        ]);

        $this->assertDatabaseHas('seller_campaign_products', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'status' => 'opted_out',
        ]);
    }

    public function test_seller_can_remove_individual_enrolled_product_from_campaign(): void
    {
        $campaign = Campaign::create([
            'name' => 'Audio Spotlight Weekend',
            'slug' => 'audio-spotlight-weekend',
            'type' => 'seasonal',
            'min_discount_percentage' => 8.0,
            'is_active' => true,
        ]);

        SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 2000.00,
            'discount_percentage' => 8.00,
            'campaign_price' => 1840.00,
            'status' => 'enrolled',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->delete(route('seller.promotions.remove_product', [$campaign, $this->product]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('seller_campaign_products', [
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_seller_can_view_daily_offers_and_flexi_deals_page(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.promotions.offers'));

        $response->assertOk();
        $response->assertSee('Daily Offers &amp; Flexi Deals', false);
        $response->assertSee('Wireless ANC Earbuds Pro');
        $response->assertSee('Set Deal Price');
    }

    public function test_admin_can_create_campaign_with_eligibility_rules_and_view_participating_sellers(): void
    {
        $payload = [
            'name' => 'Independence Day Mega Appliance Sale',
            'type' => 'festive_season',
            'description' => 'Celebrate independence with massive electronics deals.',
            'min_discount_percentage' => 12.5,
            'max_discount_percentage' => 45.0,
            'min_product_rating' => 3.0,
            'applicable_category_id' => $this->category->id,
            'starts_at' => now()->addDays(5)->toDateTimeString(),
            'ends_at' => now()->addDays(15)->toDateTimeString(),
            'allow_opt_out' => '1',
            'terms_and_conditions' => 'Sellers must maintain inventory levels for enrolled listings.',
            'is_active' => '1',
        ];

        $storeResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.campaigns.store'), $payload);

        $storeResponse->assertRedirect(route('admin.campaigns.index'));
        $storeResponse->assertSessionHas('success');

        $campaign = Campaign::where('name', 'Independence Day Mega Appliance Sale')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals(12.5, (float) $campaign->min_discount_percentage);
        $this->assertEquals($this->category->id, $campaign->applicable_category_id);

        // Admin can view campaign show page
        $showResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.campaigns.show', $campaign));

        $showResponse->assertOk();
        $showResponse->assertSee('Independence Day Mega Appliance Sale');
        $showResponse->assertSee('12.50% minimum');
        $showResponse->assertSee('Consumer Electronics');
        $showResponse->assertSee('Enrolled Sellers');
    }
}
