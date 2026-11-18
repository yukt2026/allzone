<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CODRemittanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReconciliationStatus;
use App\Enums\RefundStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Models\Category;
use App\Models\CodRemittance;
use App\Models\CommissionEntry;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerSettlement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFinancialManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sellerUser;
    protected Seller $seller;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_fin_' . uniqid() . '@allzone.local',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->sellerUser = User::factory()->create([
            'email' => 'seller_fin_' . uniqid() . '@allzone.local',
        ]);
        $this->seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Electronics India',
            'legal_name' => 'Apex Electronics Retail Pvt Ltd',
            'slug' => 'apex-electronics-india',
            'status' => SellerStatus::APPROVED,
        ]);

        $customerUser = User::factory()->create(['status' => 'active']);
        $this->customer = Customer::create(['user_id' => $customerUser->id]);

        $category = Category::create([
            'name' => 'Audio Systems',
            'slug' => 'audio-systems',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'Apex Ultra Soundbar 120W',
            'slug' => 'apex-ultra-soundbar-120w',
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku' => 'APEX-SB-01',
            'name' => 'Standard Black',
            'selling_price' => 5000.00,
            'mrp' => 9999.00,
            'is_active' => true,
        ]);
    }

    protected function createOrder(float $total = 5000.00, string $method = 'upi'): Order
    {
        return Order::create([
            'order_number' => 'ORD-FIN-' . strtoupper(uniqid()),
            'customer_id' => $this->customer->id,
            'address_snapshot' => [
                'name' => 'Test Buyer',
                'phone' => '9876543210',
                'line1' => 'MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560001',
            ],
            'subtotal' => $total,
            'discount' => 0.00,
            'delivery_fee' => 0.00,
            'tax' => 0.00,
            'total' => $total,
            'payment_method' => $method,
            'payment_status' => PaymentStatus::PAID,
            'status' => 'processing',
        ]);
    }

    /**
     * Test 1: Admin can view centralized financial dashboard with 8 KPI cards and navigation tabs.
     */
    public function test_admin_can_view_centralized_financial_dashboard_with_all_metrics(): void
    {
        $order = $this->createOrder(3000.00);
        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_rzp_test_101',
            'gateway_payment_id' => 'pay_rzp_test_101',
            'gateway_signature' => 'sig_test_101',
            'method' => PaymentMethod::UPI,
            'amount' => 3000.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => ReconciliationStatus::RECONCILED,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee('Payments &amp; Transactions Ledger', false);
        $response->assertSee('Centralized Financial Dashboard');
        $response->assertSee('Customer Payments');
        $response->assertSee('Doorstep COD Remittances');
        $response->assertSee('Refunds Audit Ledger');
        $response->assertSee('Razorpay Webhook Events');
        $response->assertSee('Seller Financials');
        $response->assertSee('Settlement Management');
        $response->assertSee('Financial Analytics &amp; Reports', false);
        $response->assertSee('pay_rzp_test_101');
    }

    /**
     * Test 2: Customer payment records show Razorpay transaction references and reconciliation status.
     */
    public function test_customer_payment_records_display_razorpay_references(): void
    {
        $order = $this->createOrder(4500.00);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_rzp_ref_999',
            'gateway_payment_id' => 'pay_rzp_ref_999',
            'gateway_signature' => 'sig_hash_sha256_valid',
            'method' => PaymentMethod::CARD,
            'amount' => 4500.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => ReconciliationStatus::RECONCILED,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.payments.show', $payment));

        $response->assertOk();
        $response->assertSee('pay_rzp_ref_999');
        $response->assertSee('order_rzp_ref_999');
        $response->assertSee('Reconciliation');
        $response->assertSee('Reconciled');
    }

    /**
     * Test 3: Doorstep COD orders and remittances tab displays collection records.
     */
    public function test_doorstep_cod_orders_and_remittances_tab(): void
    {
        CodRemittance::create([
            'seller_id' => $this->seller->id,
            'amount' => 2499.00,
            'status' => CODRemittanceStatus::COLLECTED,
            'reference' => 'COD-REC-7788',
            'collected_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.payments.index', ['tab' => 'cod']));

        $response->assertOk();
        $response->assertSee('Doorstep COD Remittances');
        $response->assertSee('COD-REC-7788');
        $response->assertSee('Apex Electronics India');
        $response->assertSee('Cash Collected');
    }

    /**
     * Test 4: Refund records show Razorpay refund IDs and audit details.
     */
    public function test_refund_records_and_razorpay_refund_ids(): void
    {
        $order = $this->createOrder(2000.00);
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_rzp_refund_test',
            'method' => PaymentMethod::UPI,
            'amount' => 2000.00,
            'status' => PaymentStatus::PARTIALLY_REFUNDED,
        ]);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 800.00,
            'reason' => 'Customer requested return of soundbar accessory.',
            'status' => RefundStatus::PROCESSED,
            'gateway_refund_id' => 'rfnd_rzp_888999',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.payments.index', ['tab' => 'refunds']));

        $response->assertOk();
        $response->assertSee('rfnd_rzp_888999');
        $response->assertSee('Customer requested return of soundbar accessory.');
        $response->assertSee('800.00');
    }

    /**
     * Test 5: Razorpay webhook event logging and JSON payload inspection.
     */
    public function test_razorpay_webhook_event_logging_and_payload_inspection(): void
    {
        $log = PaymentWebhookLog::create([
            'gateway' => 'razorpay',
            'event' => 'payment.captured',
            'gateway_order_id' => 'order_rzp_wh_01',
            'gateway_payment_id' => 'pay_rzp_wh_01',
            'payload' => [
                'event' => 'payment.captured',
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_rzp_wh_01',
                            'amount' => 500000,
                            'status' => 'captured',
                        ],
                    ],
                ],
            ],
            'signature' => 'sig_valid_sha256',
            'is_valid_signature' => true,
            'status' => 'processed',
            'ip_address' => '127.0.0.1',
        ]);

        $responseWebhooks = $this->actingAs($this->admin)->get(route('admin.payments.index', ['tab' => 'webhooks']));
        $responseWebhooks->assertOk();
        $responseWebhooks->assertSee('payment.captured');
        $responseWebhooks->assertSee('pay_rzp_wh_01');

        $responsePayload = $this->actingAs($this->admin)->getJson(route('admin.payments.webhooks.payload', $log));
        $responsePayload->assertOk();
        $responsePayload->assertJsonPath('success', true);
        $responsePayload->assertJsonPath('log.event', 'payment.captured');
        $responsePayload->assertJsonPath('log.gateway_payment_id', 'pay_rzp_wh_01');
    }

    /**
     * Test 6: Single and batch reconciliation workflows.
     */
    public function test_reconciliation_workflow_single_and_batch(): void
    {
        $order1 = $this->createOrder(1000.00);
        $p1 = Payment::create([
            'order_id' => $order1->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_unrec_01',
            'method' => PaymentMethod::UPI,
            'amount' => 1000.00,
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => ReconciliationStatus::UNRECONCILED,
        ]);

        $order2 = $this->createOrder(2000.00);
        $p2 = Payment::create([
            'order_id' => $order2->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_unrec_02',
            'method' => PaymentMethod::UPI,
            'amount' => 2000.00,
            'status' => PaymentStatus::PAID,
            'reconciliation_status' => ReconciliationStatus::PENDING,
        ]);

        // 1. Single Reconcile
        $this->actingAs($this->admin)
            ->post(route('admin.payments.reconcile', $p1), [
                'notes' => 'Matched with Razorpay settlement statement batch 44.',
            ])
            ->assertRedirect();

        $p1->refresh();
        $this->assertEquals(ReconciliationStatus::RECONCILED, $p1->reconciliation_status);
        $this->assertEquals($this->admin->id, $p1->reconciled_by);

        // 2. Batch Reconcile
        $this->actingAs($this->admin)
            ->post(route('admin.payments.reconcile_batch'), [
                'payment_ids' => [$p2->id],
            ])
            ->assertRedirect();

        $p2->refresh();
        $this->assertEquals(ReconciliationStatus::RECONCILED, $p2->reconciliation_status);
    }

    /**
     * Test 7: Seller financials aggregation and net payable calculation.
     */
    public function test_seller_financials_aggregation(): void
    {
        $order = $this->createOrder(10000.00);
        $store = Store::firstOrCreate(
            ['seller_id' => $this->seller->id],
            ['name' => 'Store', 'slug' => 'store-' . uniqid(), 'status' => 'active']
        );
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $store->id,
            'fulfilment_order_number' => 'FO-FIN-01',
            'fulfilment_type' => 'standard',
            'subtotal' => 10000.00,
            'status' => 'delivered',
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'entry_type' => 'standard',
            'base_amount' => 10000.00,
            'commission' => 1000.00,
            'tax_on_commission' => 180.00,
            'net_payable' => 8820.00,
            'notes' => 'Fulfilment Order Commission',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.payments.index', ['tab' => 'sellers']));

        $response->assertOk();
        $response->assertSee('Apex Electronics India');
        $response->assertSee('10,000.00');
        $response->assertSee('8,820.00');
    }

    /**
     * Test 8: Settlement approval, bank UTR completion, and failure recording.
     */
    public function test_settlement_payout_approval_and_bank_utr_completion(): void
    {
        $settlement = SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-PAYOUT-001',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'gross' => 5000.00,
            'commission' => 500.00,
            'tax_on_commission' => 90.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 4410.00,
            'status' => SettlementStatus::PENDING,
        ]);

        // 1. Approve
        $this->actingAs($this->admin)
            ->post(route('admin.settlements.approve', $settlement))
            ->assertRedirect();

        $settlement->refresh();
        $this->assertEquals(SettlementStatus::PROCESSING, $settlement->status);
        $this->assertEquals($this->admin->id, $settlement->approved_by);
        $this->assertNotNull($settlement->approved_at);

        // 2. Mark Paid with Bank UTR
        $this->actingAs($this->admin)
            ->post(route('admin.settlements.pay', $settlement), [
                'reference' => 'UTR202611180099',
            ])
            ->assertRedirect();

        $settlement->refresh();
        $this->assertEquals(SettlementStatus::PAID, $settlement->status);
        $this->assertEquals('UTR202611180099', $settlement->reference);
        $this->assertNotNull($settlement->paid_at);

        // 3. Payout Failure Handling on a second settlement
        $settlement2 = SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-PAYOUT-002',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'gross' => 2000.00,
            'commission' => 200.00,
            'tax_on_commission' => 36.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 1764.00,
            'status' => SettlementStatus::PROCESSING,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.settlements.fail', $settlement2), [
                'reason' => 'Beneficiary bank IFSC invalid / account blocked.',
            ])
            ->assertRedirect();

        $settlement2->refresh();
        $this->assertEquals(SettlementStatus::FAILED, $settlement2->status);
        $this->assertEquals('Beneficiary bank IFSC invalid / account blocked.', $settlement2->failed_reason);
    }

    /**
     * Test 9: Financial summary and transaction CSV exports.
     */
    public function test_financial_reports_and_csv_exports(): void
    {
        $responsePaymentsCsv = $this->actingAs($this->admin)->get(route('admin.payments.export'));
        $responsePaymentsCsv->assertOk();
        $this->assertTrue(str_contains((string) $responsePaymentsCsv->headers->get('content-type'), 'text/csv'));

        $responseFinCsv = $this->actingAs($this->admin)->get(route('admin.payments.export_financials'));
        $responseFinCsv->assertOk();
        $this->assertTrue(str_contains((string) $responseFinCsv->headers->get('content-type'), 'text/csv'));
    }

    /**
     * Test 10: Unauthorized users and guests cannot access payments console.
     */
    public function test_security_unauthorized_access_is_blocked(): void
    {
        $guestResponse = $this->get(route('admin.payments.index'));
        $guestResponse->assertRedirect(route('admin.login'));

        $unauthorized = User::factory()->create(['status' => 'active']);
        $forbiddenResponse = $this->actingAs($unauthorized)->get(route('admin.payments.index'));
        $forbiddenResponse->assertForbidden();
    }
}
