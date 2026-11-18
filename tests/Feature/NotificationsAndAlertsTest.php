<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\ShipmentStatus;
use App\Models\Announcement;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Customer;
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
use App\Models\Shipment;
use App\Models\Store;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\NewOrderSellerNotification;
use App\Notifications\OrderCancelledSellerNotification;
use App\Notifications\PaymentFailedAdminNotification;
use App\Notifications\ShiprocketFailureAdminNotification;
use App\Services\Notification\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected User $customerUser;
    protected Customer $customer;
    protected InventoryLocation $location;
    protected Category $category;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->notificationService = app(NotificationService::class);

        // Super Admin
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@allzone.test',
            'name' => 'ALLZONE SuperAdmin',
        ]);
        $this->superAdmin->assignRole('super_admin');

        // Seller & Store
        $this->sellerUser = User::factory()->create([
            'email' => 'merchant@allzone.test',
            'name' => 'Merchant Owner',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Retailers',
            'legal_name' => 'Apex Retailers Private Limited',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Electronics',
            'slug' => 'apex-electronics',
            'status' => 'active',
            'email' => 'store@apex.test',
            'phone' => '9876543210',
        ]);

        // Customer
        $this->customerUser = User::factory()->create([
            'email' => 'buyer@allzone.test',
            'name' => 'Loyal Buyer',
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Loyal Buyer',
            'email' => 'buyer@allzone.test',
            'phone' => '9123456789',
        ]);

        // Inventory Location
        $this->location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Main Hub',
            'code' => 'APEX-HUB-01',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        // Category
        $this->category = Category::create([
            'name' => 'Consumer Electronics',
            'slug' => 'consumer-electronics',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_notifications(): void
    {
        $this->get(route('admin.notifications.index'))->assertRedirect(route('admin.login'));
        $this->get(route('seller.notifications.index'))->assertRedirect(route('seller.login'));
    }

    public function test_admin_notifications_center_renders_with_metrics_and_tabs(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Admin Alerts &amp; Notifications', false);
        $response->assertSee('Total Alerts');
        $response->assertSee('Unread Alerts');
        $response->assertSee('Admin Channel Preferences');
    }

    public function test_seller_notifications_center_renders_with_dashboard_layout(): void
    {
        $this->actingAs($this->sellerUser);

        $response = $this->get(route('seller.notifications.index'));

        $response->assertOk();
        $response->assertSee('Notification Center');
        $response->assertSee('Total Alerts');
        $response->assertSee('Channel Preferences');
    }

    public function test_seller_receives_all_required_notifications(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-ALERT-101',
            'customer_id' => $this->customer->id,
            'total' => 2500.00,
            'subtotal' => 2500.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $fo = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-ALERT-101',
            'fulfilment_type' => FulfilmentType::STANDARD,
            'subtotal' => 2500.00,
            'total' => 2500.00,
            'subtotal' => 2500.00,
            'status' => 'pending',
            'seller_payable' => 2300.00,
        ]);

        $product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Wireless ANC Headphones',
            'slug' => 'wireless-anc-headphones',
            'status' => ProductStatus::ACTIVE,
            'price' => 2500.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'ANC-HP-BLK',
            'name' => 'Black Variant',
            'mrp' => 3000.00,
            'selling_price' => 2500.00,
        ]);

        $inventory = Inventory::create([
            'variant_id' => $variant->id,
            'location_id' => $this->location->id,
            'quantity' => 4,
            'available_quantity' => 4,
            'low_stock_threshold' => 10,
        ]);

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-SELLER-001',
            'fulfilment_order_id' => $fo->id,
            'awb' => 'AWB-SR-998877',
            'status' => ShipmentStatus::PICKED_UP,
        ]);

        $orderItem = OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title' => 'Wireless ANC Headphones',
            'sku' => 'ANC-HP-BLK',
            'qty' => 1,
            'price' => 2500.00,
            'total' => 2500.00,
        ]);

        $return = OrderReturn::create([
            'order_item_id' => $orderItem->id,
            'return_number' => 'RET-ALERT-001',
            'refund_amount' => 2500.00,
            'reason' => 'Item defective',
            'status' => 'requested',
        ]);

        $claim = SellerClaim::create([
            'claim_number' => 'CLM-ALERT-001',
            'seller_id' => $this->seller->id,
            'order_id' => $order->id,
            'claim_type' => 'rto_damage',
            'claimed_amount' => 1500.00,
            'reason' => 'Item damaged during RTO return transit',
            'description' => 'The shipment box arrived torn open with contents damaged.',
            'status' => 'under_review',
        ]);

        $settlement = SellerSettlement::create([
            'settlement_number' => 'SET-ALERT-001',
            'seller_id' => $this->seller->id,
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'gross' => 18000.00,
            'commission' => 3000.00,
            'adjustments' => 0.00,
            'net' => 15000.00,
            'status' => 'paid',
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-ALERT-001',
            'user_id' => $this->sellerUser->id,
            'subject' => 'Catalog sync inquiry',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $announcement = Announcement::create([
            'title' => 'Diwali Peak Logistics Protocol',
            'slug' => 'diwali-peak-logistics-protocol',
            'content' => 'Mandatory dispatch within 24 hours during Diwali peak sale.',
            'category' => 'logistics',
            'priority' => 'high',
            'is_published' => true,
        ]);

        $campaign = Campaign::create([
            'name' => 'Mega Electronics Bonanza',
            'slug' => 'mega-electronics-bonanza',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'status' => 'published',
        ]);

        // Dispatch all 11 Seller Notifications
        $this->notificationService->notifySellerNewOrder($fo);
        $this->notificationService->notifySellerOrderCancelled($fo, 'Customer changed delivery address');
        $this->notificationService->notifySellerShipmentStatus($shipment, 'In Transit', 'Departed hub Delhi');
        $this->notificationService->notifySellerReturnRequest($return);
        $this->notificationService->notifySellerClaimUpdate($claim, 'approved', 'Compensation sanctioned');
        $this->notificationService->notifySellerProductModeration($product, 'approved');
        $this->notificationService->notifySellerLowStock($inventory);
        $this->notificationService->notifySellerSettlementUpdate($settlement, 'paid', 'HDFC000123456');
        $this->notificationService->notifySellerTicketReply($ticket, 'Support Agent', 'We resolved your sync issue.');
        $this->notificationService->notifySellerAnnouncement($announcement, $this->seller);
        $this->notificationService->notifySellerPromotionInvitation($campaign, $this->seller);

        // Verify all 11 notifications are recorded in seller's database notification history
        $sellerNotifications = $this->sellerUser->notifications()->get();
        $this->assertCount(11, $sellerNotifications);

        $eventKeys = $sellerNotifications->pluck('data.event_key')->all();
        $this->assertContains('seller_new_order', $eventKeys);
        $this->assertContains('seller_order_cancelled', $eventKeys);
        $this->assertContains('seller_shipment_status', $eventKeys);
        $this->assertContains('seller_return_request', $eventKeys);
        $this->assertContains('seller_claim_update', $eventKeys);
        $this->assertContains('seller_product_moderation', $eventKeys);
        $this->assertContains('seller_low_stock', $eventKeys);
        $this->assertContains('seller_settlement_update', $eventKeys);
        $this->assertContains('seller_ticket_reply', $eventKeys);
        $this->assertContains('seller_announcement', $eventKeys);
        $this->assertContains('seller_promotion_invitation', $eventKeys);
    }

    public function test_admin_receives_all_required_notifications(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-ADM-101',
            'customer_id' => $this->customer->id,
            'total' => 8500.00,
            'subtotal' => 8500.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'amount' => 8500.00,
            'status' => PaymentStatus::FAILED,
        ]);

        $product = Product::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Smart OLED TV 55 inch',
            'slug' => 'smart-oled-tv-55',
            'status' => ProductStatus::PENDING_REVIEW,
            'price' => 54999.00,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 8500.00,
            'status' => 'pending',
            'reason' => 'Defective return request',
        ]);

        $foAdmin = FulfilmentOrder::create([
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_order_number' => 'FO-ADM-DELAY',
            'fulfilment_type' => FulfilmentType::STANDARD,
            'subtotal' => 8500.00,
            'total' => 8500.00,
            'status' => 'in_transit',
            'seller_payable' => 8000.00,
        ]);

        $shipment = Shipment::create([
            'shipment_number' => 'SHP-ADM-001',
            'fulfilment_order_id' => $foAdmin->id,
            'awb' => 'AWB-DELAY-001',
            'status' => ShipmentStatus::IN_TRANSIT,
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-ESC-001',
            'user_id' => $this->customerUser->id,
            'subject' => 'Fraudulent merchant dispute',
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        // Dispatch all 11 Admin Notifications
        $this->notificationService->notifyAdminsNewSeller($this->seller);
        $this->notificationService->notifyAdminsSellerApprovalRequest($this->seller);
        $this->notificationService->notifyAdminsProductSubmitted($product);
        $this->notificationService->notifyAdminsNewOrder($order);
        $this->notificationService->notifyAdminsPaymentFailed($payment, 'Payment signature verification mismatch');
        $this->notificationService->notifyAdminsRefundRequest($refund, 'Refund approved pending payout');
        $this->notificationService->notifyAdminsReturnOrClaim('claim', 'CLM-999', 'Apex Retailers', 'Damaged goods in transit', 999);
        $this->notificationService->notifyAdminsShiprocketFailure('awb_generation', 'Rate limit exceeded on carrier API', 'ORD-ADM-101');
        $this->notificationService->notifyAdminsDelayedShipment($shipment, 48);
        $this->notificationService->notifyAdminsSupportEscalation($ticket, 'SLA breached for urgent buyer dispute');
        $this->notificationService->notifyAdminsSecurityOrInventoryAlert('security', 'Failed Login Flood', '25 consecutive failed logins detected from IP 192.168.1.50');

        $adminNotifications = $this->superAdmin->notifications()->get();
        $this->assertCount(11, $adminNotifications);

        $eventKeys = $adminNotifications->pluck('data.event_key')->all();
        $this->assertContains('admin_new_seller', $eventKeys);
        $this->assertContains('admin_seller_approval_request', $eventKeys);
        $this->assertContains('admin_product_submitted', $eventKeys);
        $this->assertContains('admin_new_order', $eventKeys);
        $this->assertContains('admin_payment_failed', $eventKeys);
        $this->assertContains('admin_refund_request', $eventKeys);
        $this->assertContains('admin_return_or_claim', $eventKeys);
        $this->assertContains('admin_shiprocket_failure', $eventKeys);
        $this->assertContains('admin_delayed_shipment', $eventKeys);
        $this->assertContains('admin_support_escalated', $eventKeys);
        $this->assertContains('admin_security_alert', $eventKeys);
    }

    public function test_deduplication_prevents_duplicate_notifications_on_repeated_webhook_calls(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-DEDUP-001',
            'customer_id' => $this->customer->id,
            'total' => 1200.00,
            'subtotal' => 1200.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::FAILED,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'razorpay',
            'amount' => 1200.00,
            'status' => PaymentStatus::FAILED,
        ]);

        // First webhook notification
        $dispatchedFirst = $this->notificationService->notifyAdminsPaymentFailed($payment, 'Gateway timeout');
        $this->assertGreaterThan(0, $dispatchedFirst);
        $initialCount = $this->superAdmin->notifications()->count();
        $this->assertEquals(1, $initialCount);

        // Immediate repeated webhook call for the exact same payment failure
        $dispatchedSecond = $this->notificationService->notifyAdminsPaymentFailed($payment, 'Gateway timeout');
        $this->assertEquals(0, $dispatchedSecond, 'Repeated webhook call should be suppressed');
        $afterRepeatedCount = $this->superAdmin->notifications()->count();
        $this->assertEquals(1, $afterRepeatedCount, 'Duplicate alert should not be stored in database');
    }

    public function test_mark_as_read_and_mark_all_read(): void
    {
        $this->actingAs($this->superAdmin);

        // Create 3 admin notifications
        $order = Order::create([
            'order_number' => 'ORD-READ-001',
            'customer_id' => $this->customer->id,
            'total' => 1000.00,
            'subtotal' => 1000.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $this->notificationService->notifyAdminsNewOrder($order);
        $this->notificationService->notifyAdminsNewSeller($this->seller);

        $this->assertEquals(2, $this->superAdmin->unreadNotifications()->count());

        $firstNotification = $this->superAdmin->unreadNotifications()->first();

        // Mark individual notification as read
        $this->post(route('admin.notifications.read', $firstNotification->id))->assertRedirect();
        $this->assertEquals(1, $this->superAdmin->unreadNotifications()->count());

        // Mark all as read
        $this->post(route('admin.notifications.mark_all_read'))->assertRedirect();
        $this->assertEquals(0, $this->superAdmin->unreadNotifications()->count());
    }

    public function test_direct_go_url_marks_notification_as_read_and_redirects(): void
    {
        $this->actingAs($this->superAdmin);

        $order = Order::create([
            'order_number' => 'ORD-GO-001',
            'customer_id' => $this->customer->id,
            'total' => 5000.00,
            'subtotal' => 5000.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $this->notificationService->notifyAdminsNewOrder($order);

        $notification = $this->superAdmin->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        // Click the 'go' endpoint
        $response = $this->get(route('admin.notifications.go', $notification->id));

        $response->assertRedirect(route('admin.orders.show', $order->id));

        // Notification should now be marked as read
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_filtering_notifications_by_read_status_and_category(): void
    {
        $this->actingAs($this->superAdmin);

        $order = Order::create([
            'order_number' => 'ORD-FLT-001',
            'customer_id' => $this->customer->id,
            'total' => 1500.00,
            'subtotal' => 1500.00,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'payment_method' => PaymentMethod::UPI,
            'address_snapshot' => ['city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001'],
        ]);

        $this->notificationService->notifyAdminsNewOrder($order);
        $this->notificationService->notifyAdminsNewSeller($this->seller);

        // Mark one as read
        $n = $this->superAdmin->unreadNotifications()->first();
        $n->markAsRead();

        // Filter unread
        $responseUnread = $this->get(route('admin.notifications.index', ['filter' => 'unread']));
        $responseUnread->assertOk();
        $this->assertCount(1, $responseUnread->viewData('notifications'));

        // Filter read
        $responseRead = $this->get(route('admin.notifications.index', ['filter' => 'read']));
        $responseRead->assertOk();
        $this->assertCount(1, $responseRead->viewData('notifications'));

        // Filter by category
        $responseCat = $this->get(route('admin.notifications.index', ['category' => 'sellers']));
        $responseCat->assertOk();
        $this->assertCount(1, $responseCat->viewData('notifications'));
    }

    public function test_notification_channel_preferences_can_be_saved(): void
    {
        $this->actingAs($this->superAdmin);

        $postData = [
            'preferences' => [
                'orders' => [
                    'database' => '1',
                    'mail' => '1',
                    'push' => '1',
                ],
                'security' => [
                    'database' => '1',
                    'mail' => '1',
                    'sms' => '1',
                    'push' => '1',
                ],
            ],
        ];

        $response = $this->post(route('admin.notifications.preferences.update'), $postData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $orderPref = UserNotificationPreference::where('user_id', $this->superAdmin->id)
            ->where('category', 'orders')
            ->first();

        $this->assertNotNull($orderPref);
        $this->assertTrue((bool) $orderPref->channel_database);
        $this->assertTrue((bool) $orderPref->channel_mail);
        $this->assertFalse((bool) $orderPref->channel_sms);

        $secPref = UserNotificationPreference::where('user_id', $this->superAdmin->id)
            ->where('category', 'security')
            ->first();

        $this->assertNotNull($secPref);
        $this->assertTrue((bool) $secPref->channel_sms);
    }
}
