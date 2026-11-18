<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerCampaignParticipation;
use App\Models\SellerCampaignProduct;
use App\Models\Store;
use App\Models\User;
use App\Services\Storefront\CartService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionsAndCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected User $sellerUser;
    protected Seller $seller;
    protected User $otherSellerUser;
    protected Seller $otherSeller;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;
    protected InventoryLocation $location;
    protected Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin.promo@allzone.in',
            'name' => 'Promotions Director',
        ]);
        $this->adminUser->assignRole('admin');

        $this->customerUser = User::factory()->create([
            'email' => 'customer.shopper@allzone.in',
            'name' => 'Active Shopper',
        ]);
        $this->customerUser->assignRole('customer');

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'seller.merchant@allzone.in',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Supreme Audio Innovations',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
        ]);

        $store = Store::factory()->create([
            'seller_id' => $this->seller->id,
        ]);

        $this->location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'store_id' => $store->id,
            'name' => 'Main Hub Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        // Seller 2
        $this->otherSellerUser = User::factory()->create([
            'email' => 'other.merchant@allzone.in',
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Rival Audio Store',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Audio & Accessories',
            'slug' => 'audio-accessories',
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Studio Over-Ear Headphones X9',
            'slug' => 'studio-over-ear-headphones-x9',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'HDPHN-X9-BLK',
            'selling_price' => 3000.00,
            'mrp' => 4000.00,
            'is_default' => true,
        ]);

        $this->inventory = Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_admin_can_view_campaigns_list_with_metrics_and_filters(): void
    {
        $campaign1 = Campaign::create([
            'name' => 'Winter Electronics Carnival',
            'slug' => 'winter-electronics-carnival',
            'type' => 'sale',
            'min_discount_percentage' => 10.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'is_active' => true,
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'name' => 'Diwali Flash Blitz',
            'slug' => 'diwali-flash-blitz',
            'type' => 'flash_sale',
            'min_discount_percentage' => 20.0,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(6),
            'is_active' => true,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/campaigns');

        $response->assertStatus(200);
        $response->assertSee('Marketing Campaigns &amp; Featured Events', false);
        $response->assertSee('Winter Electronics Carnival');
        $response->assertSee('Diwali Flash Blitz');
        $response->assertSee('Total Campaigns');
        $response->assertSee('Active Live');
        $response->assertSee('Scheduled');

        // Filter by scheduled
        $scheduledResponse = $this->actingAs($this->adminUser)->get('/admin/campaigns?status=scheduled');
        $scheduledResponse->assertStatus(200);
        $scheduledResponse->assertSee('Diwali Flash Blitz');
    }

    public function test_admin_can_create_campaign_with_all_configuration_options(): void
    {
        $payload = [
            'name' => 'Big Summer Category Markdown',
            'type' => 'category',
            'description' => 'Targeted category-wide promotions for audio and home entertainment.',
            'banner' => 'https://cdn.allzone.in/banners/summer-audio.jpg',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(10)->toDateTimeString(),
            'opt_in_deadline' => now()->addHours(12)->toDateTimeString(),
            'discount_type' => 'percentage',
            'min_discount_percentage' => 15.0,
            'max_discount_percentage' => 40.0,
            'max_discount' => 1000.0,
            'min_product_rating' => 4.0,
            'applicable_category_id' => $this->category->id,
            'eligible_seller_ids' => [$this->seller->id],
            'max_seller_products' => 5,
            'budget_limit' => 50000.0,
            'usage_limit' => 2000,
            'coupon_code' => 'AUDIO15',
            'allow_opt_out' => '1',
            'participation_requires_approval' => '1',
            'terms_and_conditions' => 'Discounts must be honored for all fulfilled orders.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)->post('/admin/campaigns', $payload);

        $response->assertRedirect('/admin/campaigns');
        $response->assertSessionHas('success');

        $campaign = Campaign::where('name', 'Big Summer Category Markdown')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals('category', $campaign->type);
        $this->assertEquals('scheduled', $campaign->status);
        $this->assertEquals(15.0, (float) $campaign->min_discount_percentage);
        $this->assertEquals(40.0, (float) $campaign->max_discount_percentage);
        $this->assertEquals(1000.0, (float) $campaign->max_discount);
        $this->assertEquals(50000.0, (float) $campaign->budget_limit);
        $this->assertEquals(2000, $campaign->usage_limit);
        $this->assertEquals('AUDIO15', $campaign->coupon_code);
        $this->assertTrue($campaign->participation_requires_approval);
        $this->assertEquals([$this->seller->id], $campaign->eligible_seller_ids);
    }

    public function test_admin_can_update_campaign_configuration(): void
    {
        $campaign = Campaign::create([
            'name' => 'Initial Spring Deals',
            'slug' => 'initial-spring-deals',
            'type' => 'sale',
            'min_discount_percentage' => 5.0,
            'is_active' => true,
        ]);

        $updatePayload = [
            'name' => 'Updated Spring Mega Bonanza',
            'type' => 'sale',
            'description' => 'Extended spring sale discounts across all products.',
            'min_discount_percentage' => 12.0,
            'budget_limit' => 75000.0,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)->put("/admin/campaigns/{$campaign->id}", $updatePayload);

        $response->assertRedirect('/admin/campaigns');
        $response->assertSessionHas('success');

        $campaign->refresh();
        $this->assertEquals('Updated Spring Mega Bonanza', $campaign->name);
        $this->assertEquals(12.0, (float) $campaign->min_discount_percentage);
        $this->assertEquals(75000.0, (float) $campaign->budget_limit);
    }

    public function test_admin_can_publish_pause_and_end_campaign(): void
    {
        $campaign = Campaign::create([
            'name' => 'Lifecycle Control Test Campaign',
            'slug' => 'lifecycle-control-test',
            'type' => 'sale',
            'min_discount_percentage' => 10.0,
            'is_active' => false,
            'status' => 'draft',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(5),
        ]);

        // 1. Publish
        $publishRes = $this->actingAs($this->adminUser)->post("/admin/campaigns/{$campaign->id}/publish");
        $publishRes->assertRedirect();
        $campaign->refresh();
        $this->assertTrue($campaign->is_active);
        $this->assertEquals('active', $campaign->status);

        // 2. Pause
        $pauseRes = $this->actingAs($this->adminUser)->post("/admin/campaigns/{$campaign->id}/pause");
        $pauseRes->assertRedirect();
        $campaign->refresh();
        $this->assertFalse($campaign->is_active);
        $this->assertEquals('paused', $campaign->status);

        // 3. End
        $endRes = $this->actingAs($this->adminUser)->post("/admin/campaigns/{$campaign->id}/end");
        $endRes->assertRedirect();
        $campaign->refresh();
        $this->assertFalse($campaign->is_active);
        $this->assertEquals('ended', $campaign->status);
        $this->assertTrue($campaign->ends_at->isPast());
    }

    public function test_admin_can_approve_and_reject_seller_participation(): void
    {
        $campaign = Campaign::create([
            'name' => 'Curated Seller Boutique Showcase',
            'slug' => 'curated-seller-boutique-showcase',
            'type' => 'seller',
            'min_discount_percentage' => 10.0,
            'participation_requires_approval' => true,
            'is_active' => true,
            'status' => 'active',
        ]);

        // Seller opts in (starts as pending_approval)
        $participation = SellerCampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'pending_approval',
            'enrolled_at' => now(),
        ]);

        $enrolledProd = SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 3000.00,
            'discount_percentage' => 10.00,
            'campaign_price' => 2700.00,
            'status' => 'pending_approval',
        ]);

        // Admin views show page and sees pending seller
        $showRes = $this->actingAs($this->adminUser)->get("/admin/campaigns/{$campaign->id}");
        $showRes->assertStatus(200);
        $showRes->assertSee('Supreme Audio Innovations');
        $showRes->assertSee('Pending Review');

        // Admin approves participation
        $approveRes = $this->actingAs($this->adminUser)->post("/admin/campaigns/{$campaign->id}/participations/{$participation->id}/approve");
        $approveRes->assertRedirect();
        $approveRes->assertSessionHas('success');

        $participation->refresh();
        $enrolledProd->refresh();
        $this->assertEquals('enrolled', $participation->status);
        $this->assertEquals('enrolled', $enrolledProd->status);
        $this->assertNotNull($participation->approved_at);
        $this->assertEquals($this->adminUser->id, $participation->approved_by);

        // Admin rejects participation
        $rejectRes = $this->actingAs($this->adminUser)->post("/admin/campaigns/{$campaign->id}/participations/{$participation->id}/reject", [
            'rejection_reason' => 'Product stock depth is below minimum 100 units requirement.',
        ]);
        $rejectRes->assertRedirect();

        $participation->refresh();
        $enrolledProd->refresh();
        $this->assertEquals('rejected', $participation->status);
        $this->assertEquals('rejected', $enrolledProd->status);
        $this->assertEquals('Product stock depth is below minimum 100 units requirement.', $participation->rejection_reason);
    }

    public function test_only_eligible_campaigns_appear_in_seller_panel(): void
    {
        // Campaign 1: Targeted exclusively to Seller 1
        $targetedCampaign = Campaign::create([
            'name' => 'VIP Merchant Exclusive Fest',
            'slug' => 'vip-merchant-exclusive-fest',
            'type' => 'seller',
            'min_discount_percentage' => 15.0,
            'eligible_seller_ids' => [$this->seller->id], // Only Seller 1
            'is_active' => true,
            'status' => 'active',
        ]);

        // Seller 1 checks promotions dashboard
        $seller1Response = $this->actingAs($this->sellerUser)->get('/seller/promotions');
        $seller1Response->assertStatus(200);
        $seller1Response->assertSee('VIP Merchant Exclusive Fest');

        // Seller 2 checks promotions dashboard
        $seller2Response = $this->actingAs($this->otherSellerUser)->get('/seller/promotions');
        $seller2Response->assertStatus(200);
        $seller2Response->assertDontSee('VIP Merchant Exclusive Fest');

        // Seller 2 attempting to view or opt in receives 403 Forbidden
        $forbiddenShow = $this->actingAs($this->otherSellerUser)->get(route('seller.promotions.show', $targetedCampaign));
        $forbiddenShow->assertStatus(403);

        $forbiddenOptIn = $this->actingAs($this->otherSellerUser)->post(route('seller.promotions.opt_in', $targetedCampaign), [
            'terms_accepted' => '1',
            'selected_products' => [9999],
        ]);
        $forbiddenOptIn->assertStatus(403);
    }

    public function test_active_promotions_apply_correctly_at_checkout_and_expired_promotions_do_not(): void
    {
        $campaign = Campaign::create([
            'name' => 'Live Storefront Megasale',
            'slug' => 'live-storefront-megasale',
            'type' => 'sale',
            'min_discount_percentage' => 20.0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'status' => 'active',
        ]);

        SellerCampaignParticipation::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        $campaignProd = SellerCampaignProduct::create([
            'campaign_id' => $campaign->id,
            'seller_id' => $this->seller->id,
            'product_id' => $this->product->id,
            'original_price' => 3000.00,
            'discount_percentage' => 20.00,
            'campaign_price' => 2400.00, // ₹600 promotional discount!
            'status' => 'enrolled',
        ]);

        // Create a customer cart with this variant
        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'is_active' => true,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1,
            'price_snapshot' => 3000.00,
        ]);

        $cartService = app(CartService::class);

        // 1. When campaign is live and active, promotional price ₹2400 applies
        $summaryActive = $cartService->getCartSummary($cart);
        $this->assertEquals(2400.00, $summaryActive['subtotal']);
        $this->assertEquals(2400.00, $summaryActive['active_items'][0]['price']);

        // 2. If campaign is expired or ended, promotional price is rejected and regular price ₹3000 applies
        $campaign->update([
            'status' => 'ended',
            'ends_at' => now()->subHour(),
            'is_active' => false,
        ]);

        $summaryExpired = $cartService->getCartSummary($cart);
        $this->assertEquals(3000.00, $summaryExpired['subtotal']);
        $this->assertEquals(3000.00, $summaryExpired['active_items'][0]['price']);
    }

    public function test_unauthorized_user_cannot_manage_admin_campaigns(): void
    {
        $response = $this->actingAs($this->customerUser)->get('/admin/campaigns');
        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->customerUser)->post('/admin/campaigns', [
            'name' => 'Hacker Campaign',
            'type' => 'sale',
        ]);
        $postResponse->assertStatus(403);
    }
}
