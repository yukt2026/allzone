<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Order\PlaceOrderAction;
use App\Enums\AddressType;
use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\QCStatus;
use App\Enums\ReturnStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use App\Models\SettlementItem;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Admin\AdminCatalogueService;
use App\Services\Admin\AdminClaimService;
use App\Services\Admin\AdminSellerService;
use App\Services\Cargo\ShiprocketLogisticsProvider;
use App\Services\Commission\CommissionService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderStateMachine;
use App\Services\Order\ReturnService;
use App\Services\Payment\PaymentService;
use App\Services\Settlement\SettlementService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MarketplaceWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $sellerUser;
    protected User $otherSellerUser;
    protected User $customerUser;
    protected Customer $customer;
    protected CustomerAddress $customerAddress;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@allzone.test',
            'name' => 'Platform SuperAdmin',
        ]);
        $this->superAdmin->assignRole('super_admin');

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'seller1@allzone.test',
            'name' => 'Seller One',
            'wallet_balance' => 0.00,
        ]);

        // Seller 2
        $this->otherSellerUser = User::factory()->create([
            'email' => 'seller2@allzone.test',
            'name' => 'Seller Two',
            'wallet_balance' => 0.00,
        ]);

        // Customer
        $this->customerUser = User::factory()->create([
            'email' => 'shopper@allzone.test',
            'name' => 'Shopper Persona',
            'wallet_balance' => 0.00,
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Shopper Persona',
            'email' => 'shopper@allzone.test',
            'phone' => '9876543210',
        ]);

        $this->customerAddress = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->customerUser->id,
            'type' => AddressType::HOME,
            'name' => 'Shopper Persona',
            'phone' => '9876543210',
            'line1' => 'Flat 402, Palm Heights',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_default' => true,
        ]);

        // Category
        $this->category = Category::create([
            'name' => 'Smart Electronics',
            'slug' => 'smart-electronics',
            'commission_percent' => 10.00,
            'is_active' => true,
        ]);
    }

    /**
     * Workflow 1: Seller Lifecycle
     * Registration → Admin Verification → Approval → Auto Store Activation → Product Creation → QC Moderation → Live.
     */
    public function test_complete_seller_lifecycle(): void
    {
        $sellerService = app(AdminSellerService::class);
        $catalogueService = app(AdminCatalogueService::class);

        // 1. Seller registers
        $seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zephyr Mobiles',
            'legal_name' => 'Zephyr Mobiles Private Limited',
            'status' => SellerStatus::PENDING,
        ]);

        $this->assertEquals(SellerStatus::PENDING, $seller->status);
        $this->assertFalse($this->sellerUser->fresh()->hasRole('seller_owner'));

        // 2. Admin verifies and approves seller
        $store = $sellerService->approveSeller($seller, $this->superAdmin);

        $seller->refresh();
        $this->assertEquals(SellerStatus::APPROVED, $seller->status);
        $this->assertNotNull($seller->approved_at);
        $this->assertEquals($this->superAdmin->id, $seller->approved_by);
        $this->assertTrue($this->sellerUser->fresh()->hasRole('seller_owner'));

        // Store is automatically provisioned and active
        $this->assertNotNull($store);
        $this->assertEquals(StoreStatus::ACTIVE, $store->status);
        $this->assertEquals('Zephyr Mobiles', $store->name);

        // 3. Seller creates a product
        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'Zephyr Flagship Pro 5G',
            'slug' => 'zephyr-flagship-pro-5g',
            'status' => ProductStatus::PENDING_REVIEW,
            'price' => 39999.00,
        ]);

        $this->assertEquals(ProductStatus::PENDING_REVIEW, $product->status);

        // 4. Admin reviews and approves product into live marketplace
        $catalogueService->approveProduct($product, $this->superAdmin);

        $product->refresh();
        $this->assertEquals(ProductStatus::ACTIVE, $product->status);

        // Discoverable in active catalog
        $liveProducts = Product::where('status', ProductStatus::ACTIVE)->pluck('id')->all();
        $this->assertContains($product->id, $liveProducts);
    }

    /**
     * Workflow 2: Customer Order & Logistics Lifecycle
     * Place Order (Idempotent) → Payment Confirmation → Multi-Seller Splitting → Inventory Reserved →
     * Seller Packaging → Shiprocket Shipment & AWB → Transit → Delivery → Commission Record.
     */
    public function test_complete_customer_order_and_shipment_lifecycle(): void
    {
        // 1. Setup Approved Seller, Store, Location, Product, Variant, Inventory
        $seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Tech',
            'legal_name' => 'Zenith Tech India LLP',
            'status' => SellerStatus::APPROVED,
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Zenith Hub',
            'slug' => 'zenith-hub',
            'status' => StoreStatus::ACTIVE,
        ]);

        $location = InventoryLocation::create([
            'seller_id' => $seller->id,
            'store_id' => $store->id,
            'name' => 'Bengaluru Central Hub',
            'code' => 'ZEN-BLR-01',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'Noise-Cancelling Studio Earbuds',
            'slug' => 'nc-studio-earbuds',
            'status' => ProductStatus::ACTIVE,
            'price' => 4500.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'EAR-STUDIO-BLK',
            'name' => 'Matte Black',
            'mrp' => 6000.00,
            'selling_price' => 4500.00,
        ]);

        $inventory = Inventory::create([
            'variant_id' => $variant->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 2,
        ]);

        // 2. Customer Cart
        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'session_id' => 'sess_test_123',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 4500.00,
        ]);

        // 3. Atomic Order Placement with Idempotency Key
        $placeOrderAction = app(PlaceOrderAction::class);
        $idempotencyKey = 'IDEM-ORDER-XYZ-999';

        $order = $placeOrderAction->execute(
            customer: $this->customer,
            address: $this->customerAddress,
            cart: $cart,
            paymentMethod: 'online',
            idempotencyKey: $idempotencyKey
        );

        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::PLACED, $order->status);
        $this->assertEquals(PaymentStatus::PENDING, $order->payment_status);
        $this->assertEquals(9000.00, (float) $order->total);

        // Package was correctly split and assigned to seller
        $this->assertCount(1, $order->fulfilmentOrders);
        $fo = $order->fulfilmentOrders->first();
        $this->assertEquals($seller->id, $fo->seller_id);
        $this->assertEquals(9000.00, (float) $fo->total);

        // Inventory reserved (2 units reserved, 8 available)
        $inventory->refresh();
        $this->assertEquals(8, $inventory->available_quantity);
        $this->assertEquals(2, $inventory->quantity_reserved);

        // 4. Online Payment Confirmation via Razorpay
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'amount' => 9000.00,
            'status' => PaymentStatus::PENDING,
        ]);

        $paymentService = app(PaymentService::class);
        $paymentService->handlePaymentSuccess($payment, 'pay_razorpay_99887766');

        $payment->refresh();
        $order->refresh();
        $fo->refresh();

        $this->assertEquals(PaymentStatus::PAID, $payment->status);
        $this->assertEquals('pay_razorpay_99887766', $payment->gateway_payment_id);
        $this->assertEquals(PaymentStatus::PAID, $order->payment_status);
        $this->assertEquals(OrderStatus::PROCESSING, $order->status);

        // 5. Seller Processes Order: packed → ready_to_ship
        $stateMachine = app(OrderStateMachine::class);
        $stateMachine->transitionFulfilmentOrder($fo, 'packed', 'Items picked and securely boxed');
        $stateMachine->transitionFulfilmentOrder($fo, 'ready_to_ship', 'Box sealed and awaiting carrier courier');

        $fo->refresh();
        $this->assertEquals('ready_to_ship', strtolower($fo->status));

        // 6. Shiprocket Logistics Integration: Shipment & AWB Creation
        $shiprocketProvider = app(ShiprocketLogisticsProvider::class);
        $shipmentResult = $shiprocketProvider->createShipment($fo);

        $this->assertTrue($shipmentResult['success']);
        $this->assertNotEmpty($shipmentResult['awb']);
        $shipment = $shipmentResult['shipment'];
        $this->assertNotNull($shipment);
        $this->assertEquals($fo->id, $shipment->fulfilment_order_id);

        // 7. Carrier In-Transit & Delivery
        $stateMachine->transitionFulfilmentOrder($fo, 'picked_up', 'Picked up by courier');
        $stateMachine->transitionFulfilmentOrder($fo, 'in_transit', 'In transit via regional hub');
        $stateMachine->transitionFulfilmentOrder($fo, 'out_for_delivery', 'Out with local delivery executive');
        $stateMachine->transitionFulfilmentOrder($fo, 'delivered', 'Handed over to customer with OTP verification');

        $fo->refresh();
        $this->assertEquals('delivered', strtolower($fo->status));
        $this->assertNotNull($fo->delivered_at);

        // 8. Commission Ledger recorded upon successful delivery
        $commissionEntry = CommissionEntry::where('fulfilment_order_id', $fo->id)->first();
        $this->assertNotNull($commissionEntry);
        $this->assertEquals(900.00, (float) $commissionEntry->commission); // 10% on 9000.00
        $this->assertEquals(162.00, (float) $commissionEntry->tax_on_commission); // 18% GST
        $this->assertEquals(180.00, (float) $commissionEntry->gateway_fee); // 2% payment gateway fee
        $this->assertEquals(7758.00, (float) $fo->fresh()->seller_payable);

        // Inventory permanently deducted
        $inventory->refresh();
        $this->assertEquals(8, $inventory->quantity_on_hand);
        $this->assertEquals(0, $inventory->quantity_reserved);
    }

    /**
     * Workflow 3: Return, Inspection & Financial Refund Lifecycle
     * Return Request → Eligibility Check → Approval → Received → QC Passed → Restock → Refund Issued → No Duplicate Refund.
     */
    public function test_complete_return_inspection_and_refund_lifecycle(): void
    {
        $seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Audio Masters',
            'legal_name' => 'Audio Masters Retail LLP',
            'status' => SellerStatus::APPROVED,
        ]);

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Audio Masters Store',
            'slug' => 'audio-masters',
            'status' => StoreStatus::ACTIVE,
        ]);

        $location = InventoryLocation::create([
            'seller_id' => $seller->id,
            'store_id' => $store->id,
            'name' => 'Return Inspection Center',
            'code' => 'RET-BLR-01',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'Pro Studio Monitor Speakers',
            'slug' => 'pro-studio-speakers',
            'status' => ProductStatus::ACTIVE,
            'is_returnable' => true,
            'return_window_days' => 7,
            'price' => 12000.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SPK-STUDIO-PRO',
            'name' => 'Pair Edition',
            'mrp' => 15000.00,
            'selling_price' => 12000.00,
        ]);

        $inventory = Inventory::create([
            'variant_id' => $variant->id,
            'location_id' => $location->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 1,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-RET-TEST-001',
            'customer_id' => $this->customer->id,
            'total' => 12000.00,
            'subtotal' => 12000.00,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Bengaluru', 'pincode' => '560001'],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'store_id' => $store->id,
            'fulfilment_order_number' => 'FO-RET-TEST-001',
            'fulfilment_type' => FulfilmentType::STANDARD,
            'subtotal' => 12000.00,
            'total' => 12000.00,
            'seller_payable' => 10800.00,
            'status' => 'delivered',
            'delivered_at' => now()->subDays(2),
        ]);

        $orderItem = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title' => 'Pro Studio Monitor Speakers',
            'sku' => 'SPK-STUDIO-PRO',
            'qty' => 1,
            'price' => 12000.00,
            'total' => 12000.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'amount' => 12000.00,
            'status' => PaymentStatus::PAID,
        ]);

        $returnService = app(ReturnService::class);

        // 1. Eligibility Check
        $eligibility = $returnService->canRequestReturn($orderItem);
        $this->assertTrue($eligibility['allowed']);

        // 2. Customer submits return
        $return = OrderReturn::create([
            'order_item_id' => $orderItem->id,
            'return_number' => 'RMA-SPK-001',
            'refund_amount' => 12000.00,
            'reason' => 'Too large for desk space',
            'status' => ReturnStatus::REQUESTED,
            'refund_mode' => 'wallet',
        ]);

        // 3. Admin / Seller approves return
        $returnService->approve($return, 'Approved by admin', $this->superAdmin, false);
        $this->assertEquals(ReturnStatus::APPROVED, $return->fresh()->status);

        // 4. Return item received at inspection center
        $returnService->markReceived($return, $this->superAdmin);
        $this->assertEquals(ReturnStatus::RECEIVED, $return->fresh()->status);

        // 5. Quality Control: Inspection Passed & Resellable
        $initialStock = $inventory->fresh()->quantity_on_hand;
        $returnService->recordQcOutcome(
            return: $return,
            outcome: 'passed',
            qcNotes: 'Factory seal opened, but all items pristine with zero blemishes.',
            isResellable: true,
            actor: $this->superAdmin
        );

        $return->refresh();
        $this->assertEquals(QCStatus::PASSED, $return->qc_status);
        $this->assertTrue((bool) $return->is_resellable);

        // 6. Process Financial Refund & Inventory Restock
        $returnService->processRefund($return, null, $this->superAdmin);

        $return->refresh();
        $this->assertEquals(ReturnStatus::REFUNDED, $return->status);
        $this->assertNotNull($return->refunded_at);

        // Inventory restocked because product was resellable
        $inventory->refresh();
        $this->assertEquals($initialStock + 1, $inventory->quantity_on_hand);

        // Customer wallet credited
        $this->assertEquals(12000.00, (float) $this->customerUser->fresh()->wallet_balance);

        // 7. Mandatory Check: Duplicate refund attempt MUST be blocked
        $this->expectException(RuntimeException::class);
        $returnService->processRefund($return, null, $this->superAdmin);
    }

    /**
     * Workflow 4: Claims & Dispute Management Lifecycle
     * Seller raises dispute claim → Admin reviews → More Info requested → Approval → Wallet compensation credited → No duplicate compensation.
     */
    public function test_complete_claims_and_compensation_lifecycle(): void
    {
        $seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Prism Optics',
            'legal_name' => 'Prism Optics India Private Limited',
            'status' => SellerStatus::APPROVED,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CLM-TEST-001',
            'customer_id' => $this->customer->id,
            'total' => 3500.00,
            'subtotal' => 3500.00,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Bengaluru', 'pincode' => '560001'],
        ]);

        $claimService = app(AdminClaimService::class);

        // 1. Seller files claim
        $claim = SellerClaim::create([
            'claim_number' => 'CLM-RTO-DAMAGE-77',
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'claim_type' => ClaimType::RTO_DAMAGE,
            'claimed_amount' => 2000.00,
            'reason' => 'Item crushed during reverse courier transit',
            'description' => 'Outer box crushed, glass cracked. Photos uploaded.',
            'status' => ClaimStatus::OPEN,
        ]);

        // 2. Admin requests more info
        $claimService->requestMoreInfo($claim, 'Please upload barcode sticker photo from packaging', $this->superAdmin);

        $claim->refresh();
        $this->assertEquals(ClaimStatus::MORE_INFO_NEEDED, $claim->status);

        // 3. Admin verifies and approves claim for ₹2,000 compensation
        $initialSellerWallet = (float) $this->sellerUser->fresh()->wallet_balance;
        $claimService->approveClaim($claim, 2000.00, 'Damage verified against logistics incident report', $this->superAdmin);

        $claim->refresh();
        $this->assertEquals(ClaimStatus::APPROVED, $claim->status);
        $this->assertEquals(2000.00, (float) $claim->approved_amount);
        $this->assertNotNull($claim->compensated_at);
        $this->assertNotNull($claim->compensation_reference);

        // Seller wallet balance credited
        $this->assertEquals($initialSellerWallet + 2000.00, (float) $this->sellerUser->fresh()->wallet_balance);

        // Ledger transaction recorded
        $txn = WalletTransaction::where('user_id', $this->sellerUser->id)
            ->where('reference_type', SellerClaim::class)
            ->where('reference_id', $claim->id)
            ->first();

        $this->assertNotNull($txn);
        $this->assertEquals(2000.00, (float) $txn->amount);
        $this->assertEquals('credit', $txn->type);

        // 4. Mandatory Check: Duplicate compensation MUST be blocked
        $this->expectException(RuntimeException::class);
        $claimService->approveClaim($claim, 2000.00, 'Duplicate attempt', $this->superAdmin);
    }

    /**
     * Workflow 5: Financial Settlements Lifecycle
     * Order GMV → Delivered past return window → Weekly settlement batch → Accurate Net Calculation → Zero Duplicate Settlement.
     */
    public function test_complete_financial_settlement_lifecycle(): void
    {
        $seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Elite Furnishings',
            'legal_name' => 'Elite Furnishings LLP',
            'status' => SellerStatus::APPROVED,
        ]);

        $store = Store::create([
            'seller_id' => $seller->id,
            'name' => 'Elite Hub',
            'slug' => 'elite-hub',
            'status' => StoreStatus::ACTIVE,
        ]);

        // Order 1: Delivered 10 days ago (past 7-day return window -> eligible for settlement)
        $order = Order::create([
            'order_number' => 'ORD-SET-ELIG-001',
            'customer_id' => $this->customer->id,
            'total' => 20000.00,
            'subtotal' => 20000.00,
            'status' => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Bengaluru', 'pincode' => '560001'],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'store_id' => $store->id,
            'fulfilment_order_number' => 'FO-SET-ELIG-001',
            'fulfilment_type' => FulfilmentType::STANDARD,
            'subtotal' => 20000.00,
            'total' => 20000.00,
            'seller_payable' => 18000.00,
            'status' => 'delivered',
            'delivered_at' => now()->subDays(10),
        ]);

        $settlementService = app(SettlementService::class);

        // 1. Generate weekly settlement batch
        $batch = $settlementService->generateWeeklyBatch(now(), $seller->id);

        $this->assertCount(1, $batch);
        $settlement = $batch->first();
        $this->assertEquals($seller->id, $settlement->seller_id);
        $this->assertEquals(20000.00, (float) $settlement->gross);
        $this->assertEquals(2000.00, (float) $settlement->commission);
        $this->assertEquals(360.00, (float) $settlement->tax_on_commission);
        $this->assertEquals(17640.00, (float) $settlement->net);

        // Settlement items recorded
        $settlementItem = SettlementItem::where('seller_settlement_id', $settlement->id)
            ->where('fulfilment_order_id', $fo->id)
            ->first();
        $this->assertNotNull($settlementItem);

        // 2. Admin records bank payout reference
        $settlement->update([
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => 'HDFC-NEFT-9988112233',
        ]);

        $settlement->refresh();
        $this->assertEquals(SettlementStatus::PAID, $settlement->status);
        $this->assertEquals('HDFC-NEFT-9988112233', $settlement->reference);

        // 3. Mandatory Check: Generating batch again produces ZERO duplicate settlements for the same FO
        $secondBatch = $settlementService->generateWeeklyBatch(now(), $seller->id);
        $this->assertCount(0, $secondBatch, 'Delivered package must not be settled twice.');
    }

    /**
     * Workflow 6: Mandatory Idempotency & Safety Protections
     * Duplicate Order Placement, Duplicate Payment Webhook, Duplicate Shipment Creation, and Seller Data Ownership Boundaries.
     */
    public function test_mandatory_idempotency_and_security_checks(): void
    {
        $sellerA = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Seller Alpha',
            'legal_name' => 'Alpha Commercials Pvt Ltd',
            'status' => SellerStatus::APPROVED,
        ]);

        $sellerB = Seller::create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Seller Beta',
            'legal_name' => 'Beta Electronics LLP',
            'status' => SellerStatus::APPROVED,
        ]);

        $storeA = Store::create([
            'seller_id' => $sellerA->id,
            'name' => 'Store Alpha',
            'slug' => 'store-alpha',
            'status' => StoreStatus::ACTIVE,
        ]);

        $locationA = InventoryLocation::create([
            'seller_id' => $sellerA->id,
            'store_id' => $storeA->id,
            'name' => 'Alpha Warehouse',
            'code' => 'ALPHA-WH-01',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'seller_id' => $sellerA->id,
            'category_id' => $this->category->id,
            'title' => 'Alpha Precision Watch',
            'slug' => 'alpha-precision-watch',
            'status' => ProductStatus::ACTIVE,
            'price' => 5000.00,
        ]);

        $variantA = ProductVariant::create([
            'product_id' => $productA->id,
            'sku' => 'WATCH-ALPHA-01',
            'name' => 'Silver Edition',
            'mrp' => 7000.00,
            'selling_price' => 5000.00,
        ]);

        $inventoryA = Inventory::create([
            'variant_id' => $variantA->id,
            'location_id' => $locationA->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 2,
        ]);

        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'session_id' => 'sess_idem_test',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'variant_id' => $variantA->id,
            'quantity' => 1,
            'price' => 5000.00,
        ]);

        $placeOrderAction = app(PlaceOrderAction::class);
        $idemKey = 'IDEM-REPEAT-CHECK-111';

        // 1. First order placement
        $orderFirst = $placeOrderAction->execute(
            customer: $this->customer,
            address: $this->customerAddress,
            cart: $cart,
            paymentMethod: 'online',
            idempotencyKey: $idemKey
        );

        $initialOrderCount = Order::count();
        $this->assertEquals(1, $initialOrderCount);

        // 2. Duplicate order placement with the EXACT same idempotency key
        $orderSecond = $placeOrderAction->execute(
            customer: $this->customer,
            address: $this->customerAddress,
            cart: $cart,
            paymentMethod: 'online',
            idempotencyKey: $idemKey
        );

        $this->assertEquals($orderFirst->id, $orderSecond->id);
        $this->assertEquals(1, Order::count(), 'Idempotent checkout must return existing order without creating duplicate orders.');

        // 3. Duplicate Payment Webhook Handling
        $payment = Payment::create([
            'order_id' => $orderFirst->id,
            'gateway' => 'razorpay',
            'amount' => 5000.00,
            'status' => PaymentStatus::PENDING,
        ]);

        $paymentService = app(PaymentService::class);
        $res1 = $paymentService->handlePaymentSuccess($payment, 'pay_unique_111');
        $this->assertEquals(PaymentStatus::PAID, $res1->status);

        // Immediate duplicate webhook callback with same payment
        $res2 = $paymentService->handlePaymentSuccess($payment, 'pay_unique_111');
        $this->assertEquals(PaymentStatus::PAID, $res2->status);

        // 4. Duplicate Shipment Prevention
        $fo = $orderFirst->fulfilmentOrders->first();
        $shiprocket = app(ShiprocketLogisticsProvider::class);

        $shipResult1 = $shiprocket->createShipment($fo);
        $this->assertTrue($shipResult1['success']);
        $firstShipmentId = $shipResult1['shipment']->id;

        // Second call on same FO
        $shipResult2 = $shiprocket->createShipment($fo);
        $this->assertTrue($shipResult2['success']);
        $this->assertEquals($firstShipmentId, $shipResult2['shipment']->id);
        $this->assertEquals(1, Shipment::where('fulfilment_order_id', $fo->id)->count(), 'Duplicate shipment must not be created for the same package.');

        // 5. Seller Data Ownership Boundary
        // Seller B attempting to access Seller A's order package
        $this->actingAs($this->otherSellerUser);
        $response = $this->get(route('seller.orders.show', $fo->id));
        $response->assertForbidden();
    }
}
