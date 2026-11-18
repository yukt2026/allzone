<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\QCStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ReviewStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Seller;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $supportUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin.customers@allzone.in',
            'name' => 'Customer Operations Lead',
        ]);
        $this->adminUser->assignRole('admin');

        $this->supportUser = User::factory()->create([
            'email' => 'support.desk@allzone.in',
            'name' => 'Support Desk Agent',
        ]);
        $this->supportUser->assignRole('support_admin');
    }

    public function test_admin_can_view_customers_list_with_all_required_columns(): void
    {
        $user = User::factory()->create([
            'name' => 'Ananya Sharma',
            'email' => 'ananya.sharma@example.in',
            'phone' => '9876543210',
            'status' => UserStatus::ACTIVE,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'gender' => 'female',
            'dob' => '1995-06-15',
        ]);

        CustomerAddress::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'name' => 'Ananya Home',
            'phone' => '9876543210',
            'line1' => '123 Palm Grove Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560034',
            'type' => 'home',
            'is_default' => true,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-CUST-COL-01',
            'subtotal' => 2500,
            'tax' => 125,
            'delivery_fee' => 50,
            'total' => 2675,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'status' => OrderStatus::DELIVERED,
            'address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/customers');

        $response->assertStatus(200);
        // Required Columns Assertions
        $response->assertSee("#{$customer->id}");
        $response->assertSee('Ananya Sharma');
        $response->assertSee('ananya.sharma@example.in');
        $response->assertSee('9876543210');
        $response->assertSee($customer->created_at->format('M d, Y'));
        $response->assertSee('2,675.00');
        $response->assertSee('Active');
        $response->assertSee('Dossier &rarr;', false);
    }

    public function test_admin_can_search_customers_by_name_email_phone_and_id(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Rohan Varma',
            'email' => 'rohan.varma@example.in',
            'phone' => '9111222333',
        ]);
        $customer1 = Customer::create(['user_id' => $user1->id]);

        $user2 = User::factory()->create([
            'name' => 'Sunita Sen',
            'email' => 'sunita.sen@example.in',
            'phone' => '9444555666',
        ]);
        $customer2 = Customer::create(['user_id' => $user2->id]);

        // Search by name
        $nameRes = $this->actingAs($this->adminUser)->get('/admin/customers?search=Rohan');
        $nameRes->assertStatus(200);
        $nameRes->assertSee('Rohan Varma');
        $nameRes->assertDontSee('Sunita Sen');

        // Search by email
        $emailRes = $this->actingAs($this->adminUser)->get('/admin/customers?search=sunita.sen');
        $emailRes->assertStatus(200);
        $emailRes->assertSee('Sunita Sen');
        $emailRes->assertDontSee('Rohan Varma');

        // Search by phone
        $phoneRes = $this->actingAs($this->adminUser)->get('/admin/customers?search=9111222333');
        $phoneRes->assertStatus(200);
        $phoneRes->assertSee('Rohan Varma');
        $phoneRes->assertDontSee('Sunita Sen');

        // Search by ID with hash prefix
        $idRes = $this->actingAs($this->adminUser)->get("/admin/customers?search=%23{$customer2->id}");
        $idRes->assertStatus(200);
        $idRes->assertSee('Sunita Sen');
        $idRes->assertDontSee('Rohan Varma');
    }

    public function test_admin_can_filter_customers_by_tabs_and_view_tab_counts(): void
    {
        $activeUser = User::factory()->create(['name' => 'Active Shopper', 'status' => UserStatus::ACTIVE]);
        $activeCust = Customer::create(['user_id' => $activeUser->id]);

        $restrictedUser = User::factory()->create(['name' => 'Restricted Shopper', 'status' => UserStatus::SUSPENDED]);
        $restrictedCust = Customer::create(['user_id' => $restrictedUser->id]);

        // 1. All tab
        $allRes = $this->actingAs($this->adminUser)->get('/admin/customers?tab=all');
        $allRes->assertStatus(200);
        $allRes->assertSee('Active Shopper');
        $allRes->assertSee('Restricted Shopper');
        $allRes->assertSee('All Customers');

        // 2. Active tab
        $activeRes = $this->actingAs($this->adminUser)->get('/admin/customers?tab=active');
        $activeRes->assertStatus(200);
        $activeRes->assertSee('Active Shopper');
        $activeRes->assertDontSee('Restricted Shopper');

        // 3. Restricted tab
        $restrRes = $this->actingAs($this->adminUser)->get('/admin/customers?tab=restricted');
        $restrRes->assertStatus(200);
        $restrRes->assertSee('Restricted Shopper');
        $restrRes->assertDontSee('Active Shopper');
    }

    public function test_authorized_admin_can_export_customers_csv(): void
    {
        $user = User::factory()->create([
            'name' => 'Exportable Customer',
            'email' => 'export.cust@allzone.in',
            'phone' => '9988776655',
            'status' => UserStatus::ACTIVE,
        ]);
        $customer = Customer::create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)->get('/admin/customers/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('allzone_customers_export_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Customer ID', $content);
        $this->assertStringContainsString('Lifetime Spend (INR)', $content);
        $this->assertStringContainsString('Exportable Customer', $content);
        $this->assertStringContainsString('export.cust@allzone.in', $content);
    }

    public function test_admin_can_view_customer_details_with_all_9_tabs(): void
    {
        $user = User::factory()->create([
            'name' => 'Karthik Raja',
            'email' => 'karthik.raja@allzone.in',
            'phone' => '9880011223',
            'status' => UserStatus::ACTIVE,
            'wallet_balance' => 450.00,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'gender' => 'male',
            'dob' => '1990-04-12',
        ]);

        // 1. Address
        CustomerAddress::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'name' => 'Karthik Office',
            'phone' => '9880011223',
            'line1' => 'MG Road Technopark',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'type' => 'work',
            'is_default' => true,
        ]);

        // 2. Order
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-KARTHIK-01',
            'subtotal' => 1800,
            'tax' => 90,
            'delivery_fee' => 40,
            'total' => 1930,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'status' => OrderStatus::DELIVERED,
            'address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        // 3. Payment
        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_KarthikTest123',
            'method' => PaymentMethod::UPI,
            'amount' => 1930.00,
            'currency' => 'INR',
            'status' => PaymentStatus::PAID,
            'verified_at' => now(),
        ]);

        // 4. Refund
        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 300.00,
            'reason' => 'Defective accessory partial refund',
            'status' => RefundStatus::PROCESSED,
            'gateway_refund_id' => 'rfnd_KarthikTest300',
            'processed_at' => now(),
        ]);

        // 5. Fulfilment order, order item, and return
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'fulfilment_order_number' => 'FO-KARTHIK-01',
            'seller_id' => $seller->id,
            'fulfilment_type' => \App\Enums\FulfilmentType::STANDARD,
            'status' => 'delivered',
            'subtotal' => 1800,
            'commission_amount' => 180,
            'seller_payable' => 1620,
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'title' => 'Wireless Noise Cancelling Earbuds',
        ]);

        $variant = \App\Models\ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Default Variant',
            'sku' => 'EARBUDS-BLK-01',
            'mrp' => 2499,
            'selling_price' => 1800,
            'cost_price' => 1200,
        ]);

        $orderItem = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title' => 'Wireless Noise Cancelling Earbuds',
            'sku' => 'EARBUDS-BLK-01',
            'qty' => 1,
            'price' => 1800,
            'tax' => 90,
            'discount' => 0,
            'total' => 1800,
        ]);

        OrderReturn::create([
            'return_number' => 'RET-KARTHIK-01',
            'type' => 'return',
            'order_item_id' => $orderItem->id,
            'reason' => 'Item defective / audio crackle',
            'customer_notes' => 'Left earpiece distorted on high volume.',
            'status' => ReturnStatus::REQUESTED,
            'qc_status' => QCStatus::PENDING,
            'refund_amount' => 1800.00,
        ]);

        // 6. Support Ticket
        SupportTicket::create([
            'ticket_number' => 'TCK-KARTHIK-01',
            'user_id' => $user->id,
            'subject' => 'Delivery delay inquiry for expedited order',
            'category' => 'Delivery',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
        ]);

        // 7. Review
        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
            'title' => 'Superb Sound Quality',
            'body' => 'Deep bass and clear vocals, highly recommend this seller!',
            'is_verified_purchase' => true,
            'status' => ReviewStatus::APPROVED,
        ]);

        // Assert Profile Tab
        $profRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=profile");
        $profRes->assertStatus(200);
        $profRes->assertSee('Karthik Raja');
        $profRes->assertSee('karthik.raja@allzone.in');
        $profRes->assertSee('9880011223');
        $profRes->assertSee('₹450.00');

        // Assert Addresses Tab
        $addrRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=addresses");
        $addrRes->assertStatus(200);
        $addrRes->assertSee('Karthik Office');
        $addrRes->assertSee('MG Road Technopark');
        $addrRes->assertSee('560001');

        // Assert Orders Tab
        $orderRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=orders");
        $orderRes->assertStatus(200);
        $orderRes->assertSee('ORD-KARTHIK-01');
        $orderRes->assertSee('1,930.00');

        // Assert Payments Tab
        $payRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=payments");
        $payRes->assertStatus(200);
        $payRes->assertSee('pay_KarthikTest123');
        $payRes->assertSee('Razorpay');

        // Assert Refunds Tab
        $rfndRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=refunds");
        $rfndRes->assertStatus(200);
        $rfndRes->assertSee('300.00');
        $rfndRes->assertSee('rfnd_KarthikTest300');

        // Assert Returns Tab
        $retRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=returns");
        $retRes->assertStatus(200);
        $retRes->assertSee('RET-KARTHIK-01');
        $retRes->assertSee('Item defective / audio crackle');

        // Assert Tickets Tab
        $tckRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=tickets");
        $tckRes->assertStatus(200);
        $tckRes->assertSee('TCK-KARTHIK-01');
        $tckRes->assertSee('Delivery delay inquiry for expedited order');

        // Assert Reviews Tab
        $revRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=reviews");
        $revRes->assertStatus(200);
        $revRes->assertSee('Superb Sound Quality');
        $revRes->assertSee('5 ★');

        // Assert Activity Tab
        $actRes = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}?tab=activity");
        $actRes->assertStatus(200);
        $actRes->assertSee('Account Activity');
    }

    public function test_admin_can_restrict_customer_with_mandatory_reason(): void
    {
        $user = User::factory()->create([
            'name' => 'Suspect Shopper',
            'status' => UserStatus::ACTIVE,
        ]);
        $customer = Customer::create(['user_id' => $user->id]);

        // Attempt without reason fails validation
        $failRes = $this->actingAs($this->adminUser)->post("/admin/customers/{$customer->id}/restrict", [
            'reason' => '',
        ]);
        $failRes->assertSessionHasErrors('reason');

        // Successful restriction
        $response = $this->actingAs($this->adminUser)->post("/admin/customers/{$customer->id}/restrict", [
            'reason' => 'Multiple chargeback claims filed without prior communication.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(UserStatus::SUSPENDED, $user->fresh()->status);

        // Audit log verified
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_reactivate_customer_with_mandatory_reason(): void
    {
        $user = User::factory()->create([
            'name' => 'Reactivable Shopper',
            'status' => UserStatus::SUSPENDED,
        ]);
        $customer = Customer::create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)->post("/admin/customers/{$customer->id}/reactivate", [
            'reason' => 'Customer completed identity verification with support team.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(UserStatus::ACTIVE, $user->fresh()->status);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_record_suspicious_activity_review(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)->post("/admin/customers/{$customer->id}/suspicious-review", [
            'decision' => 'restrict_cod',
            'notes' => 'Customer refused multiple COD shipments. Disallow COD orders.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $customer->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_customer_privacy_protection_and_sensitive_data_masking(): void
    {
        $user = User::factory()->create([
            'name' => 'Privacy Sensitive User',
            'email' => 'privacy.shopper@allzone.in',
            'phone' => '9876501234',
            'password' => bcrypt('SuperSecretPassword123!'),
        ]);
        $customer = Customer::create(['user_id' => $user->id]);

        $response = $this->actingAs($this->adminUser)->get("/admin/customers/{$customer->id}");

        $response->assertStatus(200);
        // Password hash must NEVER appear in HTML
        $response->assertDontSee($user->password);
        $response->assertDontSee('SuperSecretPassword123!');
    }
}
