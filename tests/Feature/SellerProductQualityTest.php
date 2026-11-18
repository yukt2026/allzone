<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerProductQualityTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sellerUser = User::factory()->create([
            'email' => 'seller.quality@allzone.test',
        ]);

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Tech Innovations',
            'status' => 'approved',
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Tech Store',
            'slug' => 'zenith-tech-store',
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Audio & Hi-Fi Appliances',
            'slug' => 'audio-hi-fi-appliances',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Zenith Wireless ANC Headphones',
            'status' => ProductStatus::ACTIVE,
            'description' => 'Active noise cancelling wireless headphones with 40-hour battery life.',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZEN-ANC-HP-01',
        ]);

        ProductMedia::create([
            'product_id' => $this->product->id,
            'type' => MediaType::IMAGE,
            'path' => 'products/images/zen_front.jpg',
            'sort_order' => 1,
        ]);
    }

    public function test_seller_can_view_quality_dashboard_with_actual_metrics_and_score_tier(): void
    {
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $buyer3 = User::factory()->create();

        // 3 reviews: 5★, 4★, 1★
        Review::create([
            'product_id' => $this->product->id,
            'user_id' => $buyer1->id,
            'rating' => 5,
            'title' => 'Terrific sound!',
            'body' => 'Crisp treble and deep bass with excellent ANC.',
            'status' => ReviewStatus::APPROVED,
            'is_verified_purchase' => true,
        ]);

        Review::create([
            'product_id' => $this->product->id,
            'user_id' => $buyer2->id,
            'rating' => 4,
            'title' => 'Good build',
            'body' => 'Comfortable ear cushions for long listening sessions.',
            'status' => ReviewStatus::APPROVED,
            'is_verified_purchase' => true,
        ]);

        Review::create([
            'product_id' => $this->product->id,
            'user_id' => $buyer3->id,
            'rating' => 1,
            'title' => 'Charging cord was loose',
            'body' => 'Box was slightly dented and USB-C cable didn’t fit well.',
            'status' => ReviewStatus::APPROVED,
            'is_verified_purchase' => false,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index'));

        $response->assertStatus(200);
        $response->assertViewHas('totalRatings', 3);
        $this->assertEquals(3.33, (float) $response->viewData('averageRating'));
        $this->assertEquals(1, (int) $response->viewData('lowRatingCount'));
        $this->assertEquals(33.3, (float) $response->viewData('lowRatingPercent'));

        $response->assertSee('Terrific sound!');
        $response->assertSee('Charging cord was loose');
        $response->assertSee('Zenith Wireless ANC Headphones');
    }

    public function test_quality_dashboard_displays_honest_no_score_state_when_no_reviews_exist(): void
    {
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index'));

        $response->assertStatus(200);
        $response->assertViewHas('totalRatings', 0);
        $response->assertViewHas('averageRating', 0.0);

        $tier = $response->viewData('qualityTier');
        $this->assertEquals('none', $tier['tier']);
        $this->assertEquals('No score available', $tier['label']);

        $response->assertSee('No score available');
        $response->assertSee('No customer feedback yet');
    }

    public function test_multivendor_tenant_isolation_on_quality_reviews(): void
    {
        $otherUser = User::factory()->create();
        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Rival Brand',
            'status' => 'approved',
        ]);
        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'category_id' => $this->category->id,
            'title' => 'Rival Smart Watch 4G',
        ]);
        $otherReview = Review::create([
            'product_id' => $otherProduct->id,
            'user_id' => $otherUser->id,
            'rating' => 2,
            'title' => 'Poor battery',
            'body' => 'Died after 4 hours of usage.',
            'status' => ReviewStatus::APPROVED,
        ]);

        // Seller views quality dashboard - rival review is not included
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Rival Smart Watch 4G');
        $response->assertDontSee('Poor battery');

        // Cannot view rival reviews JSON
        $jsonResponse = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.product_reviews', $otherProduct->id));
        $jsonResponse->assertStatus(403);

        // Cannot reply to rival review
        $replyResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.quality.reply_review', $otherReview->id), [
                'response' => 'We are sorry about your battery.',
            ]);
        $replyResponse->assertStatus(403);

        // Cannot request re-review for rival product
        $reReviewResponse = $this->actingAs($this->sellerUser)
            ->post(route('seller.quality.request_review', $otherProduct->id));
        $reReviewResponse->assertStatus(403);
    }

    public function test_quality_recommendations_tabs_categorize_products_correctly(): void
    {
        // 1. Rejected Product (Action Required)
        $rejectedProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Rejected Bluetooth Dongle',
            'status' => ProductStatus::REJECTED,
            'rejection_reason' => 'Package photo is unreadable and missing specifications.',
        ]);
        ProductVariant::factory()->create(['product_id' => $rejectedProduct->id, 'sku' => 'ZEN-DONGLE-01']);

        // 2. High Return / Low Rating Product (Blocking Soon)
        $blockingProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'At Risk Smart Audio Adapter',
            'status' => ProductStatus::ACTIVE,
            'description' => 'Adapter description with sufficient text details.',
        ]);
        ProductVariant::factory()->create(['product_id' => $blockingProduct->id, 'sku' => 'ZEN-ADAPT-02']);
        ProductMedia::create(['product_id' => $blockingProduct->id, 'path' => 'img.jpg', 'sort_order' => 1]);

        $bUser1 = User::factory()->create();
        $bUser2 = User::factory()->create();
        Review::create(['product_id' => $blockingProduct->id, 'user_id' => $bUser1->id, 'rating' => 1, 'body' => 'Distorted sound.', 'status' => ReviewStatus::APPROVED]);
        Review::create(['product_id' => $blockingProduct->id, 'user_id' => $bUser2->id, 'rating' => 2, 'body' => 'Connection drops.', 'status' => ReviewStatus::APPROVED]);

        // 3. Fixed / Under Review Product
        $fixedProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Fixed Studio Microphone',
            'status' => ProductStatus::PENDING_REVIEW,
            'description' => 'Revised full microphone description.',
        ]);
        ProductVariant::factory()->create(['product_id' => $fixedProduct->id, 'sku' => 'ZEN-MIC-03']);

        // 4. Blocked Product
        $blockedProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Deactivated Speaker System',
            'status' => ProductStatus::INACTIVE,
        ]);
        ProductVariant::factory()->create(['product_id' => $blockedProduct->id, 'sku' => 'ZEN-SPK-04']);

        // Query Action Required tab
        $resAction = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index', ['tab' => 'action_required']));
        $resAction->assertStatus(200);
        $resAction->assertSee('Rejected Bluetooth Dongle');

        // Query Blocking Soon tab
        $resBlocking = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index', ['tab' => 'blocking_soon']));
        $resBlocking->assertStatus(200);
        $resBlocking->assertSee('At Risk Smart Audio Adapter');

        // Query Fixed tab
        $resFixed = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index', ['tab' => 'fixed']));
        $resFixed->assertStatus(200);
        $resFixed->assertSee('Fixed Studio Microphone');

        // Query Blocked tab
        $resBlocked = $this->actingAs($this->sellerUser)
            ->get(route('seller.quality.index', ['tab' => 'blocked']));
        $resBlocked->assertStatus(200);
        $resBlocked->assertSee('Deactivated Speaker System');
    }

    public function test_seller_can_fetch_product_reviews_json(): void
    {
        $buyer = User::factory()->create(['name' => 'Rohan Sharma']);
        $review = Review::create([
            'product_id' => $this->product->id,
            'user_id' => $buyer->id,
            'rating' => 5,
            'title' => 'Outstanding quality',
            'body' => 'High quality build and very fast connectivity.',
            'status' => ReviewStatus::APPROVED,
            'is_verified_purchase' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->getJson(route('seller.quality.product_reviews', $this->product->id));

        $response->assertStatus(200);
        $response->assertJson([
            'product_id' => $this->product->id,
            'total_reviews' => 1,
            'avg_rating' => 5.0,
        ]);
        $response->assertJsonFragment([
            'reviewer_name' => 'Rohan Sharma',
            'is_verified_purchase' => true,
            'body' => 'High quality build and very fast connectivity.',
        ]);
    }

    public function test_seller_can_post_merchant_reply_to_customer_review(): void
    {
        $buyer = User::factory()->create();
        $review = Review::create([
            'product_id' => $this->product->id,
            'user_id' => $buyer->id,
            'rating' => 3,
            'title' => 'Average bass',
            'body' => 'The bass could be punchier for hip hop tracks.',
            'status' => ReviewStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.quality.reply_review', $review->id), [
                'response' => 'Thank you for the feedback Rohan. You can boost bass frequencies via the mobile companion app equalizer.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $review->refresh();
        $this->assertEquals(
            'Thank you for the feedback Rohan. You can boost bass frequencies via the mobile companion app equalizer.',
            $review->seller_response
        );
        $this->assertNotNull($review->seller_responded_at);
        $this->assertTrue($review->hasSellerResponse());
    }

    public function test_seller_can_request_qc_re_review_for_rejected_product(): void
    {
        $rejectedProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Fixed Smart Audio Receiver',
            'status' => ProductStatus::REJECTED,
            'rejection_reason' => 'Specifications lacked power output rating.',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->post(route('seller.quality.request_review', $rejectedProduct->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $rejectedProduct->refresh();
        $this->assertEquals(ProductStatus::PENDING_REVIEW, $rejectedProduct->status);
    }
}
