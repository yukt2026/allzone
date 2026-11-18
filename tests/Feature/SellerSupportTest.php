<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SupportCategory;
use App\Models\SupportQuestion;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerSupportTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;

    protected User $otherSellerUser;
    protected Seller $otherSeller;

    protected User $adminUser;
    protected SupportCategory $category;
    protected SupportQuestion $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        // Admin User
        $this->adminUser = User::factory()->create(['email' => 'admin@allzone.test']);
        $this->adminUser->assignRole('super_admin');

        // Seller 1
        $this->sellerUser = User::factory()->create([
            'email' => 'seller1@allzone.test',
            'phone' => '+919876543211',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Retailers Hub',
            'seller_type' => SellerType::MARKETPLACE,
            'status' => SellerStatus::APPROVED,
            'approved_at' => now(),
        ]);

        // Seller 2
        $this->otherSellerUser = User::factory()->create([
            'email' => 'seller2@allzone.test',
            'phone' => '+919876543222',
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Deccan Supermart',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'approved_at' => now(),
        ]);

        // Support Category and Question from seeded defaults
        $this->category = SupportCategory::where('slug', 'orders-and-shipping')->first();
        if (!$this->category) {
            $this->category = SupportCategory::create([
                'name' => 'Orders and Shipping',
                'slug' => 'orders-and-shipping',
                'icon' => 'truck',
                'description' => 'Help with courier dispatch and Shiprocket tracking',
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }

        $this->question = $this->category->questions()->first();
        if (!$this->question) {
            $this->question = SupportQuestion::create([
                'support_category_id' => $this->category->id,
                'question' => 'Courier missed scheduled pickup for consignment',
                'slug' => 'courier-missed-scheduled-pickup',
                'short_answer' => 'We will escalate with the carrier within 2 hours.',
                'requires_order' => true,
                'requires_shipment' => true,
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }
    }

    public function test_seller_can_view_support_help_topics_and_search_categories(): void
    {
        $response = $this->actingAs($this->sellerUser)->get(route('seller.support.index', ['tab' => 'help']));

        $response->assertStatus(200);
        $response->assertSee('Browse Help Topics');
        $response->assertSee('Orders and Shipping');
        $response->assertSee($this->question->question);

        // Search test
        $searchResponse = $this->actingAs($this->sellerUser)->get(route('seller.support.index', [
            'tab' => 'help',
            'search' => 'pickup',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Orders and Shipping');
    }

    public function test_seller_can_view_my_tickets_tab_with_status_filters(): void
    {
        SupportTicket::create([
            'ticket_number' => 'TCK-2026-10001',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Late payout settlement enquiry',
            'category' => 'payments',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::OPEN,
        ]);

        SupportTicket::create([
            'ticket_number' => 'TCK-2026-10002',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Resolved weight discrepancy fee',
            'category' => 'orders',
            'priority' => TicketPriority::LOW,
            'status' => TicketStatus::RESOLVED,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.support.index', ['tab' => 'tickets']));

        $response->assertStatus(200);
        $response->assertSee('TCK-2026-10001');
        $response->assertSee('Late payout settlement enquiry');
        $response->assertSee('TCK-2026-10002');
        $response->assertSee('Resolved weight discrepancy fee');

        // Filter by open only
        $openFilterResponse = $this->actingAs($this->sellerUser)->get(route('seller.support.index', [
            'tab' => 'tickets',
            'status' => 'open',
        ]));
        $openFilterResponse->assertStatus(200);
        $openFilterResponse->assertSee('TCK-2026-10001');
        $openFilterResponse->assertDontSee('TCK-2026-10002');
    }

    public function test_seller_can_create_ticket_with_attachments_and_receives_unique_ticket_number(): void
    {
        $file = UploadedFile::fake()->image('damage_proof.jpg', 640, 480);
        $doc = UploadedFile::fake()->create('manifest_copy.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->sellerUser)->post(route('seller.support.store'), [
            'subject' => 'Package crushed during reverse logistics',
            'support_category_id' => $this->category->id,
            'support_question_id' => $this->question->id,
            'priority' => 'high',
            'awb_number' => 'AWB-88391823',
            'callback_phone' => '+919876543211',
            'message' => 'Please investigate the crushed box returned by courier.',
            'attachments' => [$file, $doc],
        ]);

        $ticket = SupportTicket::where('subject', 'Package crushed during reverse logistics')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($this->seller->id, $ticket->seller_id);
        $this->assertStringStartsWith('TCK-2026-', $ticket->ticket_number);
        $this->assertEquals(TicketStatus::OPEN, $ticket->status);
        $this->assertEquals(TicketPriority::HIGH, $ticket->priority);

        $response->assertRedirect(route('seller.support.show', $ticket));

        // Verify message and attachments
        $firstMessage = $ticket->messages()->first();
        $this->assertNotNull($firstMessage);
        $this->assertEquals('Please investigate the crushed box returned by courier.', $firstMessage->message);
        $this->assertFalse($firstMessage->is_internal);
        $this->assertCount(2, $firstMessage->attachments);

        Storage::disk('public')->assertExists($firstMessage->attachments[0]['path']);
        Storage::disk('public')->assertExists($firstMessage->attachments[1]['path']);
    }

    public function test_seller_cannot_view_or_reply_to_another_sellers_ticket(): void
    {
        // Ticket belonging to Seller 2
        $otherTicket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-99999',
            'user_id' => $this->otherSellerUser->id,
            'seller_id' => $this->otherSeller->id,
            'subject' => 'Confidential banking audit',
            'category' => 'account',
            'priority' => TicketPriority::URGENT,
            'status' => TicketStatus::OPEN,
        ]);

        // Seller 1 attempts to view Seller 2's ticket -> 403 Forbidden
        $responseShow = $this->actingAs($this->sellerUser)->get(route('seller.support.show', $otherTicket));
        $responseShow->assertStatus(403);

        // Seller 1 attempts to reply to Seller 2's ticket -> 403 Forbidden
        $responseReply = $this->actingAs($this->sellerUser)->post(route('seller.support.reply', $otherTicket), [
            'message' => 'Malicious injection reply',
        ]);
        $responseReply->assertStatus(403);
    }

    public function test_seller_never_sees_internal_admin_notes(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-55555',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Commission rate query',
            'category' => 'pricing',
            'priority' => TicketPriority::MEDIUM,
            'status' => TicketStatus::IN_PROGRESS,
        ]);

        // Public message
        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->adminUser->id,
            'message' => 'Hello seller, we are reviewing your standard commission schedule.',
            'is_internal' => false,
        ]);

        // Internal staff note (should NEVER be visible to seller)
        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->adminUser->id,
            'message' => 'INTERNAL STAFF AUDIT: Seller is requesting discount. Do not approve without director signoff.',
            'is_internal' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.support.show', $ticket));

        $response->assertStatus(200);
        $response->assertSee('Hello seller, we are reviewing your standard commission schedule.');
        $response->assertDontSee('INTERNAL STAFF AUDIT');
        $response->assertDontSee('Do not approve without director signoff');
    }

    public function test_seller_can_reply_and_transitions_waiting_status_to_in_progress(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-77777',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Weight discrepancy dispute',
            'category' => 'logistics',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::WAITING_CUSTOMER, // Waiting for Seller
        ]);

        $response = $this->actingAs($this->sellerUser)->post(route('seller.support.reply', $ticket), [
            'message' => 'Here are the requested dimension slips and scale weight photos.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(TicketStatus::IN_PROGRESS, $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'message' => 'Here are the requested dimension slips and scale weight photos.',
            'is_internal' => false,
        ]);
    }

    public function test_seller_can_reopen_resolved_ticket(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-88888',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Missing payout for dispatch',
            'category' => 'payments',
            'priority' => TicketPriority::HIGH,
            'status' => TicketStatus::RESOLVED,
            'resolved_at' => now()->subDay(),
        ]);

        $this->assertTrue($ticket->canBeReopened());

        $response = $this->actingAs($this->sellerUser)->post(route('seller.support.reopen', $ticket), [
            'reason' => 'Bank account statement shows transaction was reversed by payment gateway.',
        ]);

        $response->assertRedirect();
        $fresh = $ticket->fresh();
        $this->assertEquals(TicketStatus::IN_PROGRESS, $fresh->status);
        $this->assertNotNull($fresh->reopened_at);
        $this->assertDatabaseHas('ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->sellerUser->id,
        ]);
    }

    public function test_admin_can_manage_support_categories_and_questions(): void
    {
        // Admin creates category
        $responseCreate = $this->actingAs($this->adminUser)->post(route('admin.support.categories.store'), [
            'name' => 'Brand Protection & IP',
            'description' => 'Trademark infringement and brand registry help',
            'sort_order' => 11,
        ]);

        $responseCreate->assertRedirect();
        $this->assertDatabaseHas('support_categories', [
            'name' => 'Brand Protection & IP',
            'slug' => 'brand-protection-ip',
        ]);

        $cat = SupportCategory::where('slug', 'brand-protection-ip')->first();

        // Admin adds question to category
        $responseQuestion = $this->actingAs($this->adminUser)->post(route('admin.support.categories.questions.store', $cat), [
            'question' => 'How to report counterfeit product listing?',
            'short_answer' => 'Submit trademark certificate and listing URL.',
            'requires_product' => '1',
            'sort_order' => 1,
        ]);

        $responseQuestion->assertRedirect();
        $this->assertDatabaseHas('support_questions', [
            'support_category_id' => $cat->id,
            'question' => 'How to report counterfeit product listing?',
            'requires_product' => true,
        ]);
    }

    public function test_admin_can_update_ticket_priority(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-66666',
            'user_id' => $this->sellerUser->id,
            'seller_id' => $this->seller->id,
            'subject' => 'Urgent critical inventory mismatch',
            'priority' => TicketPriority::LOW,
            'status' => TicketStatus::OPEN,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.support.priority', $ticket), [
            'priority' => 'urgent',
        ]);

        $response->assertRedirect();
        $this->assertEquals(TicketPriority::URGENT, $ticket->fresh()->priority);
    }
}
