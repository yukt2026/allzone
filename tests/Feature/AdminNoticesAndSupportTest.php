<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminNoticesAndSupportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected User $customerUser;
    protected User $sellerUser;
    protected Seller $seller;
    protected User $otherSellerUser;
    protected Seller $otherSeller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Admin User
        $this->adminUser = User::factory()->create([
            'email' => 'admin.support@allzone.in',
            'name' => 'Communications Director',
        ]);
        $this->adminUser->assignRole('admin');

        // Staff User
        $this->staffUser = User::factory()->create([
            'email' => 'staff.agent@allzone.in',
            'name' => 'Support Desk Agent',
        ]);
        $this->staffUser->assignRole('support_admin');

        // Customer User
        $this->customerUser = User::factory()->create([
            'email' => 'customer.buyer@allzone.in',
            'name' => 'Regular Buyer',
        ]);
        $this->customerUser->assignRole('customer');

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'notice.seller@allzone.in',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Prime Electronics Hub',
            'legal_name' => 'Prime Electronics Hub Pvt Ltd',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        // Seller 2
        $this->otherSellerUser = User::factory()->create([
            'email' => 'rival.seller@allzone.in',
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Apex Footwear India',
            'legal_name' => 'Apex Footwear Pvt Ltd',
            'seller_type' => SellerType::BRAND,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);
    }

    /* -------------------------------------------------------------------------
     * NOTICES ADMINISTRATION TESTS
     * ------------------------------------------------------------------------- */

    public function test_admin_can_view_notices_listing_with_status_metrics(): void
    {
        Announcement::create([
            'title' => 'Diwali Logistics & Dispatch Guidelines',
            'slug' => 'diwali-logistics-guidelines',
            'category' => 'logistics',
            'priority' => 'urgent',
            'summary' => 'Important cutoff guidelines for festive delivery.',
            'content' => 'Full logistics handbook content here.',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
        ]);

        Announcement::create([
            'title' => 'Upcoming Scheduled Maintenance',
            'slug' => 'upcoming-scheduled-maintenance',
            'category' => 'feature',
            'priority' => 'low',
            'summary' => 'Scheduled downtime notice.',
            'content' => 'Server maintenance details.',
            'target_type' => 'all',
            'is_published' => false,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.announcements.index'));

        $response->assertOk();
        $response->assertSee('Diwali Logistics &amp; Dispatch Guidelines', false);
        $response->assertSee('Upcoming Scheduled Maintenance');
        $response->assertSee('All Sellers');
    }

    public function test_admin_can_create_notice_with_target_audience_and_attachments(): void
    {
        Storage::fake('public');

        $banner = UploadedFile::fake()->image('notice-banner.jpg', 800, 400);
        $attachment = UploadedFile::fake()->create('sop_handbook.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.announcements.store'), [
            'title' => 'Q4 Packaging & Safety Compliance Protocol',
            'category' => 'policy',
            'priority' => 'high',
            'summary' => 'Mandatory packaging specifications for high-value shipments.',
            'content' => 'Detailed compliance rules and return mitigation steps.',
            'target_type' => 'specific_sellers',
            'target_seller_ids' => [$this->seller->id],
            'is_published' => '1',
            'banner_image' => $banner,
            'attachment' => $attachment,
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $notice = Announcement::where('title', 'Q4 Packaging & Safety Compliance Protocol')->first();
        $this->assertNotNull($notice);
        $this->assertEquals('policy', $notice->category);
        $this->assertEquals('high', $notice->priority);
        $this->assertEquals('specific_sellers', $notice->target_type);
        $this->assertContains($this->seller->id, $notice->target_seller_ids);
        $this->assertNotNull($notice->banner_image);
        $this->assertNotNull($notice->attachment_path);
        Storage::disk('public')->assertExists($notice->banner_image);
        Storage::disk('public')->assertExists($notice->attachment_path);
    }

    public function test_admin_can_edit_notice_and_update_priority(): void
    {
        $notice = Announcement::create([
            'title' => 'GST Invoicing Mandate Notice',
            'slug' => 'gst-invoicing-mandate-notice',
            'category' => 'policy',
            'priority' => 'normal',
            'summary' => 'Initial GST summary.',
            'content' => 'Initial GST guidelines.',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.announcements.update', $notice), [
            'title' => 'GST Invoicing Mandate Notice - Revised',
            'category' => 'important',
            'priority' => 'urgent',
            'summary' => 'Updated urgent GST invoice requirement.',
            'content' => 'Strict verification required for all B2B sellers.',
            'target_type' => 'seller_group',
            'target_seller_type' => 'brand',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $notice->refresh();

        $this->assertEquals('GST Invoicing Mandate Notice - Revised', $notice->title);
        $this->assertEquals('urgent', $notice->priority);
        $this->assertEquals('seller_group', $notice->target_type);
        $this->assertEquals('brand', $notice->target_seller_type);
    }

    public function test_admin_can_schedule_and_toggle_notice_publication(): void
    {
        $futureDate = now()->addDays(5)->format('Y-m-d H:i:s');

        $notice = Announcement::create([
            'title' => 'Scheduled New Year Policy',
            'slug' => 'scheduled-new-year-policy',
            'category' => 'general',
            'priority' => 'normal',
            'content' => 'Will go live next week.',
            'target_type' => 'all',
            'published_at' => $futureDate,
            'is_published' => true,
            'created_by' => $this->adminUser->id,
        ]);

        // Toggle to draft
        $response = $this->actingAs($this->adminUser)->post(route('admin.announcements.toggle', $notice));
        $response->assertRedirect();
        $notice->refresh();
        $this->assertFalse($notice->is_published);

        // Toggle back to published
        $response = $this->actingAs($this->adminUser)->post(route('admin.announcements.toggle', $notice));
        $response->assertRedirect();
        $notice->refresh();
        $this->assertTrue($notice->is_published);
    }

    public function test_admin_can_archive_notice_immediately(): void
    {
        $notice = Announcement::create([
            'title' => 'Flash Sale Guidelines 2026',
            'slug' => 'flash-sale-guidelines-2026',
            'category' => 'general',
            'priority' => 'normal',
            'content' => 'Guidelines for weekend flash sale.',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'expires_at' => now()->addDays(5),
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.announcements.archive', $notice));

        $response->assertRedirect();
        $notice->refresh();
        $this->assertFalse($notice->is_published);
        $this->assertNotNull($notice->expires_at);
        $this->assertTrue($notice->expires_at->isPast() || $notice->expires_at->equalTo(now()));
    }

    public function test_admin_can_audit_read_confirmations_for_notice(): void
    {
        $notice = Announcement::create([
            'title' => 'Mandatory Bank Account Re-verification',
            'slug' => 'mandatory-bank-account-reverification',
            'category' => 'important',
            'priority' => 'urgent',
            'content' => 'All sellers must re-verify IFSC and canceled cheques.',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'created_by' => $this->adminUser->id,
        ]);

        // Record a confirmed read by Seller 1
        AnnouncementRead::create([
            'announcement_id' => $notice->id,
            'seller_id' => $this->seller->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.announcements.reads', $notice));

        $response->assertOk();
        $response->assertSee('Prime Electronics Hub');
        $response->assertSee('1 Reads');

        // Test JSON API response
        $jsonResponse = $this->actingAs($this->adminUser)->getJson(route('admin.announcements.reads', $notice));
        $jsonResponse->assertOk();
        $jsonResponse->assertJson([
            'success' => true,
            'reads_count' => 1,
            'reads' => [
                [
                    'seller_id' => $this->seller->id,
                    'seller_name' => 'Prime Electronics Hub',
                ],
            ],
        ]);
    }

    /* -------------------------------------------------------------------------
     * SUPPORT TICKETS & GOVERNANCE TESTS
     * ------------------------------------------------------------------------- */

    public function test_admin_can_view_support_desk_with_response_time_reporting_and_tabs(): void
    {
        $ticket1 = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0001',
            'user_id' => $this->customerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Delayed Courier Delivery Inquiry',
            'category' => 'orders',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
            'created_at' => now()->subHours(5),
        ]);

        $ticket2 = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0002',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Weight Discrepancy Dispute',
            'category' => 'claims',
            'priority' => TicketPriority::URGENT,
            'status' => TicketStatus::IN_PROGRESS,
            'is_escalated' => true,
            'escalated_at' => now()->subHours(2),
            'escalation_reason' => 'Logistics 2kg discrepancy charge disputed by seller.',
            'first_response_at' => now()->subHours(3),
            'created_at' => now()->subHours(4),
        ]);

        $ticket3 = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0003',
            'user_id' => $this->customerUser->id,
            'subject' => 'Damaged Item Return Help',
            'category' => 'returns',
            'priority' => TicketPriority::MEDIUM,
            'status' => TicketStatus::RESOLVED,
            'first_response_at' => now()->subHours(10),
            'resolved_at' => now()->subHours(1),
            'created_at' => now()->subHours(12),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.support.index'));

        $response->assertOk();
        $response->assertSee('Delayed Courier Delivery Inquiry');
        $response->assertSee('Weight Discrepancy Dispute');
        $response->assertSee('Damaged Item Return Help');
        $response->assertSee('Prime Electronics Hub');
        $response->assertSee('Escalated');

        // Test status tab filtering
        $openResponse = $this->actingAs($this->adminUser)->get(route('admin.support.index', ['status' => 'open']));
        $openResponse->assertOk();
        $openResponse->assertSee('Delayed Courier Delivery Inquiry');
        $openResponse->assertDontSee('Damaged Item Return Help');

        $escalatedResponse = $this->actingAs($this->adminUser)->get(route('admin.support.index', ['status' => 'escalated']));
        $escalatedResponse->assertOk();
        $escalatedResponse->assertSee('Weight Discrepancy Dispute');
        $escalatedResponse->assertDontSee('Delayed Courier Delivery Inquiry');
    }

    public function test_admin_can_assign_support_staff_and_update_priority(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0004',
            'user_id' => $this->customerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Incorrect Item Received in Parcel',
            'category' => 'returns',
            'priority' => TicketPriority::LOW,
            'status' => TicketStatus::OPEN,
        ]);

        // Assign staff agent
        $assignResponse = $this->actingAs($this->adminUser)->post(route('admin.support.assign', $ticket), [
            'assigned_to' => $this->staffUser->id,
        ]);

        $assignResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals($this->staffUser->id, $ticket->assigned_to);
        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->status);

        // Update priority
        $priorityResponse = $this->actingAs($this->adminUser)->post(route('admin.support.priority', $ticket), [
            'priority' => 'urgent',
        ]);

        $priorityResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketPriority::URGENT, $ticket->priority);
    }

    public function test_admin_can_post_public_reply_and_updates_first_response_time(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0005',
            'user_id' => $this->customerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Where is my order refund?',
            'category' => 'refund',
            'priority' => TicketPriority::MEDIUM,
            'status' => TicketStatus::OPEN,
            'first_response_at' => null,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.support.reply', $ticket), [
            'message' => 'Hello, we have confirmed your refund has been successfully initiated via Razorpay.',
            'is_internal' => '0',
        ]);

        $response->assertRedirect();
        $ticket->refresh();

        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->status);
        $this->assertNotNull($ticket->first_response_at);

        $message = TicketMessage::where('support_ticket_id', $ticket->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('Hello, we have confirmed your refund has been successfully initiated via Razorpay.', $message->message);
        $this->assertFalse($message->is_internal);
        $this->assertEquals($this->adminUser->id, $message->user_id);
    }

    public function test_admin_can_post_private_internal_note_with_attachments(): void
    {
        Storage::fake('public');
        $attachment = UploadedFile::fake()->create('internal_investigation.pdf', 300, 'application/pdf');

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0006',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Fraudulent Return Claim Investigation',
            'category' => 'claims',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.support.reply', $ticket), [
            'message' => 'Internal Note: Courier RTO weight logs confirmed barcoded parcel was swapped in hub.',
            'is_internal' => '1',
            'attachments' => [$attachment],
        ]);

        $response->assertRedirect();

        $message = TicketMessage::where('support_ticket_id', $ticket->id)->where('is_internal', true)->first();
        $this->assertNotNull($message);
        $this->assertTrue($message->is_internal);
        $this->assertTrue($message->hasAttachments());

        // Seller portal test: seller cannot see this internal note
        $sellerViewResponse = $this->actingAs($this->sellerUser)->get(route('seller.support.show', $ticket));
        $sellerViewResponse->assertOk();
        $sellerViewResponse->assertDontSee('Courier RTO weight logs confirmed barcoded parcel was swapped in hub.');
    }

    public function test_admin_can_request_information_switching_status_to_waiting_customer(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0007',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Damaged in Transit Claim',
            'category' => 'claims',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.support.request_info', $ticket), [
            'message' => 'Please upload clear 360-degree video footage of outer box packaging and shipping label.',
        ]);

        $response->assertRedirect();
        $ticket->refresh();

        $this->assertEquals(TicketStatus::WAITING_CUSTOMER, $ticket->status);

        $message = TicketMessage::where('support_ticket_id', $ticket->id)->latest('id')->first();
        $this->assertNotNull($message);
        $this->assertStringContainsString('INFORMATION REQUESTED', $message->message);
        $this->assertFalse($message->is_internal);
    }

    public function test_admin_can_escalate_ticket_to_urgent_priority(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0008',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'High Value Payout Discrepancy Dispute',
            'category' => 'payments',
            'priority' => TicketPriority::MEDIUM,
            'status' => TicketStatus::OPEN,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.support.escalate', $ticket), [
            'escalation_reason' => 'Disputed payment of ₹85,000 pending audit by Finance team.',
            'assigned_to' => $this->staffUser->id,
        ]);

        $response->assertRedirect();
        $ticket->refresh();

        $this->assertTrue($ticket->is_escalated);
        $this->assertEquals(TicketPriority::URGENT, $ticket->priority);
        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->status);
        $this->assertEquals($this->staffUser->id, $ticket->assigned_to);
        $this->assertEquals('Disputed payment of ₹85,000 pending audit by Finance team.', $ticket->escalation_reason);
        $this->assertNotNull($ticket->escalated_at);

        // Escalation documented in internal message thread
        $note = TicketMessage::where('support_ticket_id', $ticket->id)->where('is_internal', true)->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('ESCALATION TO URGENT', $note->message);
    }

    public function test_admin_can_change_status_to_resolved_and_closed(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0009',
            'user_id' => $this->customerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Product cataloging clarification',
            'category' => 'cataloging',
            'priority' => TicketPriority::LOW,
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        // Resolve
        $resolveResponse = $this->actingAs($this->adminUser)->post(route('admin.support.status', $ticket), [
            'status' => 'resolved',
        ]);
        $resolveResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketStatus::RESOLVED, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);

        // Close
        $closeResponse = $this->actingAs($this->adminUser)->post(route('admin.support.status', $ticket), [
            'status' => 'closed',
        ]);
        $closeResponse->assertRedirect();
        $ticket->refresh();
        $this->assertEquals(TicketStatus::CLOSED, $ticket->status);
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_admin_can_export_tickets_to_csv(): void
    {
        SupportTicket::create([
            'ticket_number' => 'TCK-202611-0010',
            'user_id' => $this->customerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Exportable Support Inflow',
            'category' => 'orders',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.support.export'));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Exportable Support Inflow', $response->streamedContent());
        $this->assertStringContainsString('TCK-202611-0010', $response->streamedContent());
    }

    public function test_seller_multi_tenant_isolation_on_support_tickets(): void
    {
        // Ticket owned by Seller 1
        $ticket1 = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0011',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Confidential Seller 1 Inquiry',
            'category' => 'account',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
        ]);

        // Ticket owned by Seller 2
        $ticket2 = SupportTicket::create([
            'ticket_number' => 'TCK-202611-0012',
            'user_id' => $this->otherSellerUser->id,
            'seller_id' => $this->otherSeller->id,
            'subject' => 'Confidential Seller 2 Inquiry',
            'category' => 'account',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
        ]);

        // Seller 1 viewing their tickets index sees only ticket1
        $sellerIndex = $this->actingAs($this->sellerUser)->get(route('seller.support.index'));
        $sellerIndex->assertOk();
        $sellerIndex->assertSee('Confidential Seller 1 Inquiry');
        $sellerIndex->assertDontSee('Confidential Seller 2 Inquiry');

        // Seller 1 trying to access Seller 2's ticket directly gets 403 Forbidden
        $directResponse = $this->actingAs($this->sellerUser)->get(route('seller.support.show', $ticket2));
        $directResponse->assertForbidden();

        // Admin can see both tickets
        $adminIndex = $this->actingAs($this->adminUser)->get(route('admin.support.index'));
        $adminIndex->assertOk();
        $adminIndex->assertSee('Confidential Seller 1 Inquiry');
        $adminIndex->assertSee('Confidential Seller 2 Inquiry');
    }

    public function test_unauthorized_user_cannot_access_admin_notices_and_support(): void
    {
        // Unauthenticated access redirects to admin login
        $this->get(route('admin.announcements.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.support.index'))->assertRedirect(route('admin.login'));

        // Customer access is forbidden
        $this->actingAs($this->customerUser)->get(route('admin.announcements.index'))->assertForbidden();
        $this->actingAs($this->customerUser)->get(route('admin.support.index'))->assertForbidden();
    }
}
