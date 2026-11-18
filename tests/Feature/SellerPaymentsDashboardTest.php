<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SettlementStatus;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\FulfilmentOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\SellerBankAccount;
use App\Models\SellerSettlement;
use App\Models\SettlementItem;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerPaymentsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected SellerBankAccount $bankAccount;

    protected User $otherSellerUser;
    protected Seller $otherSeller;
    protected Store $otherStore;

    protected Customer $customer;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('return_window_days', '7');
        Setting::set('default_commission_pct', '10.00');

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'seller.payments@allzone.test',
        ]);

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Zenith Tech Innovations',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Zenith Hi-Tech Store',
        ]);

        $this->bankAccount = SellerBankAccount::create([
            'seller_id' => $this->seller->id,
            'account_holder' => 'Zenith Tech Innovations',
            'account_no' => '50100234567890',
            'ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'branch_name' => 'Connaught Place Branch',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        // Seller 2 (Tenant Isolation)
        $this->otherSellerUser = User::factory()->create([
            'email' => 'other.payments@allzone.test',
        ]);

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Apex Appliances',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->otherStore = Store::factory()->create([
            'seller_id' => $this->otherSeller->id,
            'name' => 'Apex Electronics Store',
        ]);

        // Customer & Catalog
        $customerUser = User::factory()->create();
        $this->customer = Customer::create(['user_id' => $customerUser->id]);

        $this->category = Category::factory()->create([
            'name' => 'Consumer Audio',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Zenith Studio Soundbar Pro',
            'status' => 'active',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'ZEN-SBAR-PRO',
            'selling_price' => 5000.00,
            'mrp' => 5500.00,
        ]);
    }

    public function test_seller_can_view_payments_dashboard_with_all_actual_kpi_metrics(): void
    {
        // 1. Create Delivered Order past return window (Matured)
        $order1 = Order::factory()->create([
            'order_number' => 'ORD-PAY-1001',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 5000.00,
        ]);

        $fo1 = FulfilmentOrder::create([
            'order_id' => $order1->id,
            'fulfilment_order_number' => 'FO-PAY-1001',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 5000.00,
            'commission_amount' => 500.00,
            'seller_payable' => 4410.00,
            'delivered_at' => Carbon::now()->subDays(10), // Matured
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fo1->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Zenith Studio Soundbar Pro',
            'sku' => 'ZEN-SBAR-PRO',
            'qty' => 1,
            'price' => 5000.00,
            'total' => 5000.00,
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fo1->id,
            'base_amount' => 5000.00,
            'commission' => 500.00,
            'tax_on_commission' => 90.00, // 18% of 500
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 4410.00,
            'entry_type' => 'standard',
        ]);

        // 2. Create Delivered Order within return window (Maturing)
        $order2 = Order::factory()->create([
            'order_number' => 'ORD-PAY-1002',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 3000.00,
        ]);

        $fo2 = FulfilmentOrder::create([
            'order_id' => $order2->id,
            'fulfilment_order_number' => 'FO-PAY-1002',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 3000.00,
            'commission_amount' => 300.00,
            'seller_payable' => 2646.00,
            'delivered_at' => Carbon::now()->subDays(2), // Within 7 days
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fo2->id,
            'base_amount' => 3000.00,
            'commission' => 300.00,
            'tax_on_commission' => 54.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 2646.00,
            'entry_type' => 'standard',
        ]);

        // 3. Create Completed (Paid) Settlement Batch
        $settlementPaid = SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-20261118-PAID01',
            'period_start' => Carbon::now()->subDays(20)->toDateString(),
            'period_end' => Carbon::now()->subDays(14)->toDateString(),
            'gross' => 10000.00,
            'commission' => 1000.00,
            'tax_on_commission' => 180.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 8820.00,
            'status' => SettlementStatus::PAID,
            'reference' => 'UTR998877665544',
            'paid_at' => Carbon::now()->subDays(13),
        ]);

        // 4. Create Pending Settlement Batch
        SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-20261118-PEND01',
            'period_start' => Carbon::now()->subDays(13)->toDateString(),
            'period_end' => Carbon::now()->subDays(7)->toDateString(),
            'gross' => 4000.00,
            'commission' => 400.00,
            'tax_on_commission' => 72.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 3528.00,
            'status' => SettlementStatus::PENDING,
        ]);

        // 5. Create On-Hold Settlement Batch
        SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-20261118-HOLD01',
            'period_start' => Carbon::now()->subDays(7)->toDateString(),
            'period_end' => Carbon::now()->toDateString(),
            'gross' => 2000.00,
            'commission' => 200.00,
            'tax_on_commission' => 36.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 1764.00,
            'status' => SettlementStatus::ON_HOLD,
            'hold_reason' => 'KYC address re-verification required',
        ]);

        // 6. Create Refund Reversal Entry
        $reversalOrder = Order::factory()->create([
            'order_number' => 'ORD-PAY-REF01',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::REFUNDED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::REFUNDED,
            'subtotal' => 1000.00,
        ]);

        $reversalFo = FulfilmentOrder::create([
            'order_id' => $reversalOrder->id,
            'fulfilment_order_number' => 'FO-PAY-REF01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'cancelled',
            'subtotal' => 1000.00,
            'commission_amount' => 0.00,
            'seller_payable' => -882.00,
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $reversalFo->id,
            'base_amount' => 1000.00,
            'commission' => 100.00,
            'tax_on_commission' => 18.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => -882.00,
            'entry_type' => 'reversal',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index'));

        $response->assertStatus(200);

        // Verify Upcoming Settlements: 4410 (matured) + 2646 (maturing) = 7056.00
        $this->assertEquals(7056.00, (float) $response->viewData('upcomingSettlements'));
        $this->assertEquals(4410.00, (float) $response->viewData('upcomingMaturedAmount'));
        $this->assertEquals(2646.00, (float) $response->viewData('upcomingMaturingAmount'));

        // Verify Completed Settlements: 8820.00
        $this->assertEquals(8820.00, (float) $response->viewData('completedSettlements'));
        $this->assertEquals(1, (int) $response->viewData('completedBatchesCount'));

        // Verify Pending Settlements: 3528.00
        $this->assertEquals(3528.00, (float) $response->viewData('pendingSettlements'));
        $this->assertEquals(1, (int) $response->viewData('pendingBatchesCount'));

        // Verify On-Hold: 1764.00
        $this->assertEquals(1764.00, (float) $response->viewData('onHoldAmounts'));
        $this->assertEquals(1, (int) $response->viewData('onHoldBatchesCount'));

        // Verify Refund Adjustments: 1000.00
        $this->assertEquals(1000.00, (float) $response->viewData('refundAdjustments'));

        // Verify Commission Deductions: 500 + 300 = 800.00
        $this->assertEquals(800.00, (float) $response->viewData('commissionDeductions'));

        // Verify Other Authorized Fees (Taxes on commission): 90 + 54 + 18 = 162.00
        $this->assertEquals(162.00, (float) $response->viewData('otherAuthorizedFees'));

        // Verify Net Seller Payable across all 3 entries: 4410 + 2646 - 882 = 6174.00
        $this->assertEquals(6174.00, (float) $response->viewData('netSellerPayable'));

        // Verify Payout Bank Account Details
        $response->assertSee('HDFC Bank');
        $response->assertSee('HDFC0001234');
        $response->assertSee('7890'); // Last 4 digits of masked account
        $response->assertSee('Verified Bank Account');

        // Verify Table Contains Orders
        $response->assertSee('ORD-PAY-1001');
        $response->assertSee('ORD-PAY-1002');
        $response->assertSee('ORD-PAY-REF01');
        $response->assertSee('Refund Adjustment');
    }

    public function test_multivendor_tenant_isolation_on_payments_and_earnings(): void
    {
        // Seller 1 entry
        $order1 = Order::factory()->create([
            'order_number' => 'ORD-SELLER-1',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 2000.00,
        ]);

        $fo1 = FulfilmentOrder::create([
            'order_id' => $order1->id,
            'fulfilment_order_number' => 'FO-SELLER-1',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 2000.00,
        ]);

        $entry1 = CommissionEntry::create([
            'fulfilment_order_id' => $fo1->id,
            'base_amount' => 2000.00,
            'commission' => 200.00,
            'tax_on_commission' => 36.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 1764.00,
            'entry_type' => 'standard',
        ]);

        // Seller 2 entry
        $order2 = Order::factory()->create([
            'order_number' => 'ORD-SELLER-2-SECRET',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 8000.00,
        ]);

        $fo2 = FulfilmentOrder::create([
            'order_id' => $order2->id,
            'fulfilment_order_number' => 'FO-SELLER-2-SECRET',
            'seller_id' => $this->otherSeller->id,
            'store_id' => $this->otherStore->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 8000.00,
        ]);

        $entry2 = CommissionEntry::create([
            'fulfilment_order_id' => $fo2->id,
            'base_amount' => 8000.00,
            'commission' => 800.00,
            'tax_on_commission' => 144.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 7056.00,
            'entry_type' => 'standard',
        ]);

        // Seller 1 visits payments dashboard
        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index'));

        $response->assertStatus(200);
        $response->assertSee('ORD-SELLER-1');
        $response->assertDontSee('ORD-SELLER-2-SECRET');
        $this->assertEquals(1764.00, (float) $response->viewData('netSellerPayable'));

        // Seller 1 cannot access Seller 2's transaction details JSON
        $detailsRes = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.transaction_details', $entry2));

        $detailsRes->assertStatus(403);
    }

    public function test_payment_history_filtering_by_status_tabs(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-TAB-01',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 4000.00,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-TAB-01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 4000.00,
        ]);

        $entry = CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 4000.00,
            'commission' => 400.00,
            'tax_on_commission' => 72.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 3528.00,
            'entry_type' => 'standard',
        ]);

        // Settlement in PAID status
        $settlement = SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-TAB-PAID',
            'period_start' => now()->subDays(5)->toDateString(),
            'period_end' => now()->toDateString(),
            'gross' => 4000.00,
            'commission' => 400.00,
            'tax_on_commission' => 72.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 3528.00,
            'status' => SettlementStatus::PAID,
            'reference' => 'UTR1234567890',
            'paid_at' => now(),
        ]);

        SettlementItem::create([
            'seller_settlement_id' => $settlement->id,
            'fulfilment_order_id' => $fo->id,
            'commission_entry_id' => $entry->id,
            'amount' => 3528.00,
            'type' => 'order_payout',
            'description' => 'Order payout',
        ]);

        // Test status=settled
        $responseSettled = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index', ['status' => 'settled']));
        $responseSettled->assertStatus(200);
        $responseSettled->assertSee('ORD-TAB-01');

        // Test status=refunds (should be empty)
        $responseRefunds = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index', ['status' => 'refunds']));
        $responseRefunds->assertStatus(200);
        $responseRefunds->assertDontSee('ORD-TAB-01');
    }

    public function test_payment_history_search_by_order_number_and_transaction_id(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-SEARCH-TARGET-99',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 2500.00,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-SEARCH-99',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 2500.00,
        ]);

        $entry = CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 2500.00,
            'commission' => 250.00,
            'tax_on_commission' => 45.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 2205.00,
            'entry_type' => 'standard',
        ]);

        // Search by Order Number
        $resOrder = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index', ['search' => 'TARGET-99']));
        $resOrder->assertStatus(200);
        $resOrder->assertSee('ORD-SEARCH-TARGET-99');

        // Search by Transaction ID (TXN-00000X)
        $resTxn = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.index', ['search' => 'TXN-' . $entry->id]));
        $resTxn->assertStatus(200);
        $resTxn->assertSee('ORD-SEARCH-TARGET-99');
    }

    public function test_seller_can_fetch_transaction_details_json_with_arithmetic_breakdown(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-BREAKDOWN-01',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 5000.00,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-BREAKDOWN-01',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 5000.00,
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'title' => 'Zenith Studio Soundbar Pro',
            'sku' => 'ZEN-SBAR-PRO',
            'qty' => 1,
            'price' => 5000.00,
            'total' => 5000.00,
        ]);

        $entry = CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 5000.00,
            'commission' => 500.00,
            'tax_on_commission' => 90.00,
            'gateway_fee' => 25.00,
            'shipping_adjustment' => 50.00,
            'net_payable' => 4335.00,
            'entry_type' => 'standard',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.transaction_details', $entry));

        $response->assertStatus(200);
        $response->assertJson([
            'order_number' => 'ORD-BREAKDOWN-01',
            'sub_order_number' => 'FO-BREAKDOWN-01',
            'gross_amount' => 5000.0,
            'commission' => 500.0,
            'tax_on_commission' => 90.0,
            'gateway_fee' => 25.0,
            'shipping_adjustment' => 50.0,
            'net_payable' => 4335.0,
        ]);
    }

    public function test_seller_can_export_payments_ledger_csv(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'ORD-CSV-999',
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'subtotal' => 3500.00,
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-CSV-999',
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::SELLER_DIRECT,
            'status' => 'delivered',
            'subtotal' => 3500.00,
        ]);

        CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 3500.00,
            'commission' => 350.00,
            'tax_on_commission' => 63.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 3087.00,
            'entry_type' => 'standard',
        ]);

        $response = $this->actingAs($this->sellerUser)
            ->get(route('seller.payments.export'));

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ALLZONE-Payments-Ledger', $response->headers->get('Content-Disposition') ?? '');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Transaction ID', $content);
        $this->assertStringContainsString('Order #', $content);
        $this->assertStringContainsString('Net Amount (INR)', $content);
        $this->assertStringContainsString('ORD-CSV-999', $content);
        $this->assertStringContainsString('3087.00', $content);
    }

    public function test_seller_can_view_and_export_settlement_batches(): void
    {
        $settlement = SellerSettlement::create([
            'seller_id' => $this->seller->id,
            'settlement_number' => 'SET-TEST-BATCH-77',
            'period_start' => now()->subDays(10)->toDateString(),
            'period_end' => now()->subDays(3)->toDateString(),
            'gross' => 6000.00,
            'commission' => 600.00,
            'tax_on_commission' => 108.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 5292.00,
            'status' => SettlementStatus::PAID,
            'reference' => 'UTR-BATCH-77-OK',
            'paid_at' => now()->subDays(2),
        ]);

        // 1. View settlements index
        $indexRes = $this->actingAs($this->sellerUser)
            ->get(route('seller.settlements.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('SET-TEST-BATCH-77');
        $indexRes->assertSee('UTR-BATCH-77-OK');

        // 2. Export settlements CSV
        $exportRes = $this->actingAs($this->sellerUser)
            ->get(route('seller.settlements.export'));
        $exportRes->assertStatus(200);
        $this->assertStringContainsString('SET-TEST-BATCH-77', $exportRes->streamedContent());

        // 3. Download individual settlement CSV
        $csvRes = $this->actingAs($this->sellerUser)
            ->get(route('seller.settlements.csv', $settlement));
        $csvRes->assertStatus(200);
    }
}
