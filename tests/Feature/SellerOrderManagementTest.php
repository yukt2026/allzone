<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\StoreStatus;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sellerUser = User::factory()->create([
            'email' => 'merchant@allzone.in',
            'phone' => '+919876543201',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Electronics Retail',
            'legal_name' => 'Apex Electronics Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'pan' => 'APEXR1234F',
            'gstin' => '29APEXR1234F1Z5',
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Electronics Flagship',
            'slug' => 'apex-electronics-flagship',
            'status' => StoreStatus::ACTIVE,
            'accepts_quick_commerce' => true,
            'accepts_pickup' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'title' => 'Wireless Bluetooth Earbuds Pro',
            'slug' => 'wireless-bluetooth-earbuds-pro',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'APX-EAR-001',
            'name' => 'Matte Black',
            'mrp' => 2499.00,
            'selling_price' => 1999.00,
        ]);
    }

    protected function createOrderForSeller(
        Seller $seller,
        string $status = 'placed',
        FulfilmentType $fulfilmentType = FulfilmentType::STANDARD,
        PaymentMethod $paymentMethod = PaymentMethod::UPI,
        PaymentStatus $paymentStatus = PaymentStatus::PAID,
        ?\DateTimeInterface $createdAt = null
    ): FulfilmentOrder {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'address_snapshot' => [
                'name' => 'Rahul Sharma',
                'phone' => '9876543210',
                'line1' => 'Flat 402, Green Acres Apt',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
            ],
            'created_at' => $createdAt ?? now(),
        ]);

        $fo = FulfilmentOrder::factory()->create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-' . strtoupper(uniqid()),
            'seller_id' => $seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => $fulfilmentType,
            'status' => $status,
            'subtotal' => 1999.00,
            'commission_amount' => 100.00,
            'seller_payable' => 1899.00,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        OrderItem::factory()->create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Wireless Bluetooth Earbuds Pro',
            'variant_name' => 'Matte Black',
            'sku' => 'APX-EAR-001',
            'qty' => 1,
            'price' => 1999.00,
            'total' => 1999.00,
        ]);

        if ($createdAt) {
            \Illuminate\Support\Facades\DB::table('fulfilment_orders')->where('id', $fo->id)->update(['created_at' => $createdAt]);
            $fo->refresh();
        }

        return $fo;
    }

    /**
     * Test 1: Seller can view orders index and sees all 9 tabs with real counts.
     */
    public function test_seller_can_view_orders_index_with_nine_tabs_and_counts(): void
    {
        $this->createOrderForSeller($this->seller, 'placed');
        $this->createOrderForSeller($this->seller, 'confirmed');
        $this->createOrderForSeller($this->seller, 'ready_to_ship');
        $this->createOrderForSeller($this->seller, 'in_transit');
        $this->createOrderForSeller($this->seller, 'delivered');
        $this->createOrderForSeller($this->seller, 'cancelled');
        $this->createOrderForSeller($this->seller, 'returned');

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.index'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.orders.index');
        $response->assertSee('Orders Management');
        $response->assertSee('All Orders');
        $response->assertSee('New / Pending');
        $response->assertSee('On Hold');
        $response->assertSee('Processing');
        $response->assertSee('Ready to Ship');
        $response->assertSee('Shipped');
        $response->assertSee('Delivered');
        $response->assertSee('Cancelled');
        $response->assertSee('Returned');

        $counts = $response->viewData('counts');
        $this->assertEquals(7, $counts['all']);
        $this->assertEquals(1, $counts['pending']);
        $this->assertEquals(0, $counts['on_hold']);
        $this->assertEquals(1, $counts['processing']);
        $this->assertEquals(1, $counts['ready_to_ship']);
        $this->assertEquals(1, $counts['shipped']);
        $this->assertEquals(1, $counts['delivered']);
        $this->assertEquals(1, $counts['cancelled']);
        $this->assertEquals(1, $counts['returned']);
    }

    /**
     * Test 2: Multi-vendor tenant isolation (Seller A cannot view or manage Seller B's sub-orders).
     */
    public function test_multivendor_tenant_isolation(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other.merchant@allzone.in',
            'phone' => '+919876543202',
        ]);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Zenith Garments LLP',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
        ]);

        $sellerAFo = $this->createOrderForSeller($this->seller, 'placed');
        $sellerBFo = $this->createOrderForSeller($otherSeller, 'placed');

        // Seller A should see only their sub-order in list
        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.index'));
        $response->assertStatus(200);
        $response->assertSee($sellerAFo->fulfilment_order_number);
        $response->assertDontSee($sellerBFo->fulfilment_order_number);

        // Seller A attempting to view Seller B's sub-order is forbidden (403)
        $detailResponse = $this->actingAs($this->sellerUser)->get(route('seller.orders.show', $sellerBFo));
        $detailResponse->assertStatus(403);

        // Seller A attempting to accept or mutate Seller B's sub-order is forbidden (403)
        $acceptResponse = $this->actingAs($this->sellerUser)->post(route('seller.orders.accept', $sellerBFo));
        $acceptResponse->assertStatus(403);

        $readyResponse = $this->actingAs($this->sellerUser)->post(route('seller.orders.ready_to_ship', $sellerBFo));
        $readyResponse->assertStatus(403);
    }

    /**
     * Test 3: Filter by payment method (Prepaid Razorpay vs COD).
     */
    public function test_filter_by_payment_method(): void
    {
        $prepaidFo = $this->createOrderForSeller(
            $this->seller,
            'placed',
            FulfilmentType::STANDARD,
            PaymentMethod::UPI,
            PaymentStatus::PAID
        );

        $codFo = $this->createOrderForSeller(
            $this->seller,
            'placed',
            FulfilmentType::STANDARD,
            PaymentMethod::COD,
            PaymentStatus::PENDING
        );

        // Query COD only
        $responseCod = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['payment_method' => 'cod']));
        $responseCod->assertStatus(200);
        $responseCod->assertSee($codFo->fulfilment_order_number);
        $responseCod->assertDontSee($prepaidFo->fulfilment_order_number);

        // Query Prepaid only
        $responsePrepaid = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['payment_method' => 'prepaid']));
        $responsePrepaid->assertStatus(200);
        $responsePrepaid->assertSee($prepaidFo->fulfilment_order_number);
        $responsePrepaid->assertDontSee($codFo->fulfilment_order_number);
    }

    /**
     * Test 4: Filter by date ranges (today, yesterday, 7d, 30d).
     */
    public function test_filter_by_date_ranges(): void
    {
        $todayFo = $this->createOrderForSeller($this->seller, 'placed', FulfilmentType::STANDARD, PaymentMethod::UPI, PaymentStatus::PAID, now());
        $oldFo = $this->createOrderForSeller($this->seller, 'placed', FulfilmentType::STANDARD, PaymentMethod::UPI, PaymentStatus::PAID, now()->subDays(15));

        // Today filter
        $resToday = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['date_filter' => 'today']));
        $resToday->assertStatus(200);
        $resToday->assertSee($todayFo->fulfilment_order_number);
        $resToday->assertDontSee($oldFo->fulfilment_order_number);

        // 7 days filter
        $res7d = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['date_filter' => '7d']));
        $res7d->assertStatus(200);
        $res7d->assertSee($todayFo->fulfilment_order_number);
        $res7d->assertDontSee($oldFo->fulfilment_order_number);

        // 30 days filter
        $res30d = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['date_filter' => '30d']));
        $res30d->assertStatus(200);
        $res30d->assertSee($todayFo->fulfilment_order_number);
        $res30d->assertSee($oldFo->fulfilment_order_number);
    }

    /**
     * Test 5: Search orders by order number, SKU, and address.
     */
    public function test_search_orders_by_number_and_sku(): void
    {
        $targetFo = $this->createOrderForSeller($this->seller, 'placed');
        $otherFo = $this->createOrderForSeller($this->seller, 'placed');

        // Search by Sub-order package number
        $resNumber = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['search' => $targetFo->fulfilment_order_number]));
        $resNumber->assertStatus(200);
        $resNumber->assertSee($targetFo->fulfilment_order_number);
        $resNumber->assertDontSee($otherFo->fulfilment_order_number);

        // Search by SKU
        $resSku = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['search' => 'APX-EAR-001']));
        $resSku->assertStatus(200);
        $resSku->assertSee($targetFo->fulfilment_order_number);

        // Search by City
        $resCity = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['search' => 'Bengaluru']));
        $resCity->assertStatus(200);
        $resCity->assertSee($targetFo->fulfilment_order_number);
    }

    /**
     * Test 6: Order accept action updates status and logs history trail.
     */
    public function test_seller_can_accept_order_and_record_history(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'placed', FulfilmentType::STANDARD);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.accept', $fo));
        $response->assertSessionHas('success');

        $fo->refresh();
        $this->assertEquals('confirmed', $fo->status);

        $this->assertDatabaseHas('order_status_history', [
            'orderable_type' => FulfilmentOrder::class,
            'orderable_id' => $fo->id,
            'to_status' => 'confirmed',
        ]);
    }

    /**
     * Test 7: Seller can mark standard order ready to ship.
     */
    public function test_seller_can_mark_order_ready_to_ship(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'confirmed', FulfilmentType::STANDARD);

        // Standard order transitions confirmed -> packed -> ready_to_ship
        // Transition to packed first
        $this->actingAs($this->sellerUser)->post(route('seller.orders.transition', $fo), [
            'target_status' => 'packed',
            'note' => 'Items securely boxed with tamper-evident tape',
        ]);

        $fo->refresh();
        $this->assertEquals('packed', $fo->status);

        // Then mark ready to ship
        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.ready_to_ship', $fo));
        $response->assertSessionHas('success');

        $fo->refresh();
        $this->assertEquals('ready_to_ship', $fo->status);
    }

    /**
     * Test 8: Seller can export permitted order data as CSV.
     */
    public function test_seller_can_export_permitted_order_data_as_csv(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'placed');

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['export' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Sub-order Package #', $content);
        $this->assertStringContainsString('Order Date', $content);
        $this->assertStringContainsString('SKUs', $content);
        $this->assertStringContainsString($fo->fulfilment_order_number, $content);
        $this->assertStringContainsString('APX-EAR-001', $content);
    }

    /**
     * Test 9: Customer phone privacy controls are maintained.
     */
    public function test_customer_phone_privacy_control_is_masked(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'placed');

        $response = $this->actingAs($this->sellerUser)->get(route('seller.orders.index'));
        $response->assertStatus(200);

        // Unmasked phone number should NOT appear in plain listing
        $response->assertDontSee('9876543210');
        // Masked phone format (987***210) should appear
        $response->assertSee('987***210');
    }

    /**
     * Test 10: Tab filtering returns only orders for that specific tab.
     */
    public function test_seller_can_filter_by_individual_tabs(): void
    {
        $pendingFo = $this->createOrderForSeller($this->seller, 'placed');
        $deliveredFo = $this->createOrderForSeller($this->seller, 'delivered');

        // Tab: pending
        $resPending = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['tab' => 'pending']));
        $resPending->assertStatus(200);
        $resPending->assertSee($pendingFo->fulfilment_order_number);
        $resPending->assertDontSee($deliveredFo->fulfilment_order_number);

        // Tab: delivered
        $resDelivered = $this->actingAs($this->sellerUser)->get(route('seller.orders.index', ['tab' => 'delivered']));
        $resDelivered->assertStatus(200);
        $resDelivered->assertSee($deliveredFo->fulfilment_order_number);
        $resDelivered->assertDontSee($pendingFo->fulfilment_order_number);
    }

    /**
     * Test 11: Document downloads (Packing Slip, Tax Invoice, Shipping Label).
     */
    public function test_seller_can_download_order_documents(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'confirmed');

        // Packing slip
        $resSlip = $this->actingAs($this->sellerUser)->get(route('seller.orders.packing_slip', $fo));
        $resSlip->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resSlip->headers->get('Content-Type'));

        // Tax invoice
        $resInvoice = $this->actingAs($this->sellerUser)->get(route('seller.orders.invoice', $fo));
        $resInvoice->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resInvoice->headers->get('Content-Type'));

        // Shipping label
        $resLabel = $this->actingAs($this->sellerUser)->get(route('seller.orders.shipping_label', $fo));
        $resLabel->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resLabel->headers->get('Content-Type'));
    }

    /**
     * Test 12: Duplicate processing is prevented.
     */
    public function test_duplicate_processing_is_prevented(): void
    {
        $fo = $this->createOrderForSeller($this->seller, 'delivered');

        // Attempting to accept an already delivered order should fail gracefully with an error redirect
        $response = $this->actingAs($this->sellerUser)->post(route('seller.orders.accept', $fo));
        $response->assertSessionHas('error');

        $fo->refresh();
        $this->assertEquals('delivered', $fo->status);
    }
}
