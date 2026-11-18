<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\FulfilmentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerClaimsManagementTest extends TestCase
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
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sellerUser = User::factory()->create([
            'email' => 'claims.merchant@allzone.in',
            'phone' => '+919876543211',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Claims Apparel',
            'legal_name' => 'Zenith Claims Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Indiranagar Store',
            'slug' => 'zenith-indiranagar-store',
            'status' => StoreStatus::ACTIVE,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Fashion & Wear',
            'slug' => 'fashion-wear',
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Men Casual Denim Shirt',
            'slug' => 'men-casual-denim-shirt',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZNT-SHIRT-BLU-L',
            'name' => 'Blue / Large',
            'mrp' => 1499.00,
            'selling_price' => 999.00,
            'weight_grams' => 350,
        ]);
    }

    protected function createDeliveredOrder(Seller $seller): array
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $fo = FulfilmentOrder::factory()->create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'seller_id' => $seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => 'delivered',
            'delivered_at' => now()->subDays(2),
        ]);

        $item = OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => $this->product->title,
            'sku' => $this->variant->sku,
            'qty' => 1,
            'price' => 999.00,
            'total' => 999.00,
        ]);

        return [$order, $fo, $item];
    }

    /**
     * Test 1: Seller can view claims overview dashboard with actual metrics and trends.
     */
    public function test_seller_can_view_claims_overview_with_actual_metrics_and_trends(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        // Create an existing claim
        $claim = SellerClaim::create([
            'claim_number' => 'CLM-TEST-0001',
            'seller_id' => $this->seller->id,
            'order_id' => $order->id,
            'fulfilment_order_id' => $fo->id,
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::RTO_DAMAGE,
            'reason' => 'Package arrived torn with stained item inside',
            'description' => 'Unboxing revealed heavy stains on the fabric.',
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 999.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.claims.index'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.claims.index');
        $response->assertSee('Claims Management');
        $response->assertSee('CLM-TEST-0001');
        $response->assertSee('Claim Approval Rate');
        $response->assertSee('Claim Filing Rate');

        $claimStats = $response->viewData('claimStats');
        $this->assertEquals(1, $claimStats['total']);
        $this->assertEquals(1, $claimStats['open']);
        $this->assertArrayHasKey('trend', $claimStats);
    }

    /**
     * Test 2: Multivendor tenant isolation on claims.
     */
    public function test_multivendor_tenant_isolation_on_claims(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other.merchant@allzone.in',
            'phone' => '+919876543298',
        ]);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Royal Silks',
            'status' => SellerStatus::APPROVED,
        ]);

        [$myOrder, $myFo, $myItem] = $this->createDeliveredOrder($this->seller);
        $myClaim = SellerClaim::create([
            'claim_number' => 'CLM-MY-001',
            'seller_id' => $this->seller->id,
            'order_id' => $myOrder->id,
            'order_item_id' => $myItem->id,
            'claim_type' => ClaimType::RTO_DAMAGE,
            'reason' => 'My claim',
            'description' => 'My claim description',
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 999.00,
        ]);

        [$otherOrder, $otherFo, $otherItem] = $this->createDeliveredOrder($otherSeller);
        $otherClaim = SellerClaim::create([
            'claim_number' => 'CLM-OTHER-002',
            'seller_id' => $otherSeller->id,
            'order_id' => $otherOrder->id,
            'order_item_id' => $otherItem->id,
            'claim_type' => ClaimType::RTO_DAMAGE,
            'reason' => 'Other claim',
            'description' => 'Other claim description',
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 999.00,
        ]);

        // Seller A should see only their claim in table
        $response = $this->actingAs($this->sellerUser)->get(route('seller.claims.index'));
        $response->assertStatus(200);
        $response->assertSee($myClaim->claim_number);
        $response->assertDontSee($otherClaim->claim_number);

        // Seller A cannot view Seller B's claim (403)
        $detailResponse = $this->actingAs($this->sellerUser)->get(route('seller.claims.show', $otherClaim));
        $detailResponse->assertStatus(403);

        // Seller A cannot reply to Seller B's claim (403)
        $replyResponse = $this->actingAs($this->sellerUser)->post(
            route('seller.claims.reply', $otherClaim),
            ['message' => 'Unauthorized reply']
        );
        $replyResponse->assertStatus(403);
    }

    /**
     * Test 3: Seller can search orders for claim eligibility.
     */
    public function test_seller_can_search_order_for_claim_eligibility(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        $response = $this->actingAs($this->sellerUser)->getJson(
            route('seller.claims.search_order', ['q' => $item->sku])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'items' => [
                '*' => ['id', 'order_number', 'sku', 'title', 'total', 'is_eligible']
            ]
        ]);

        $this->assertEquals($item->id, $response->json('items.0.id'));
        $this->assertTrue($response->json('items.0.is_eligible'));
    }

    /**
     * Test 4: Seller can raise a new claim and claim number is generated.
     */
    public function test_seller_can_raise_claim_with_details_and_generates_claim_number(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.claims.store'), [
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::RTO_DAMAGE->value,
            'reason' => 'Outer box crushed and jeans ripped inside',
            'description' => 'The courier delivery arrived with torn packaging and the garment is completely ripped.',
            'claimed_amount' => 999.00,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_claims', [
            'seller_id' => $this->seller->id,
            'order_item_id' => $item->id,
            'status' => ClaimStatus::OPEN->value,
            'claimed_amount' => 999.00,
        ]);

        $claim = SellerClaim::where('order_item_id', $item->id)->first();
        $this->assertNotNull($claim);
        $this->assertStringStartsWith('CLM-', $claim->claim_number);

        // Message trail created
        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $claim->id,
            'action_type' => 'created',
        ]);
    }

    /**
     * Test 5: Duplicate claims on the same order item are prevented.
     */
    public function test_duplicate_claims_on_same_item_are_prevented(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        // 1. First claim succeeds
        $this->actingAs($this->sellerUser)->post(route('seller.claims.store'), [
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::RTO_DAMAGE->value,
            'reason' => 'Crushed parcel',
            'description' => 'Parcel crushed during reverse shipment.',
            'claimed_amount' => 999.00,
        ]);

        // 2. Second claim fails gracefully
        $responseDuplicate = $this->actingAs($this->sellerUser)->post(route('seller.claims.store'), [
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::RTO_DAMAGE->value,
            'reason' => 'Another claim attempt',
            'description' => 'Attempting duplicate claim.',
            'claimed_amount' => 999.00,
        ]);

        $responseDuplicate->assertSessionHas('error');
        $this->assertEquals(1, SellerClaim::where('order_item_id', $item->id)->count());
    }

    /**
     * Test 6: Seller can view claim detail and reply when more info is requested.
     */
    public function test_seller_can_reply_when_more_info_needed_and_transitions_status(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        $claim = SellerClaim::create([
            'claim_number' => 'CLM-TEST-0003',
            'seller_id' => $this->seller->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::RETURN_WRONG_ITEM,
            'reason' => 'Customer returned different brand shirt',
            'description' => 'Received counterfeit product.',
            'status' => ClaimStatus::MORE_INFO_NEEDED,
            'claimed_amount' => 999.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.claims.reply', $claim), [
            'message' => 'Here is the manufacturer barcode photo showing the difference.',
        ]);

        $response->assertSessionHas('success');

        $claim->refresh();
        $this->assertEquals(ClaimStatus::UNDER_REVIEW, $claim->status);

        $this->assertDatabaseHas('claim_messages', [
            'seller_claim_id' => $claim->id,
            'action_type' => 'info_submitted',
            'message' => 'Here is the manufacturer barcode photo showing the difference.',
        ]);
    }

    /**
     * Test 7: Seller can export claims as CSV.
     */
    public function test_seller_can_export_claims_as_csv(): void
    {
        [$order, $fo, $item] = $this->createDeliveredOrder($this->seller);

        $claim = SellerClaim::create([
            'claim_number' => 'CLM-CSV-0004',
            'seller_id' => $this->seller->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'claim_type' => ClaimType::COURIER_LOSS,
            'reason' => 'Consignment lost in transit by carrier',
            'description' => 'No tracking update for 25 days.',
            'status' => ClaimStatus::OPEN,
            'claimed_amount' => 999.00,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.claims.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Claim Number', $content);
        $this->assertStringContainsString('CLM-CSV-0004', $content);
        $this->assertStringContainsString('Consignment lost in transit by carrier', $content);
    }
}
