<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\StoreStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;

    protected User $otherSellerUser;
    protected Seller $otherSeller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        // Admin User
        $this->adminUser = User::factory()->create([
            'email' => 'admin@allzone.test',
            'name' => 'ALLZONE SuperAdmin',
        ]);
        $this->adminUser->assignRole('super_admin');

        // Seller 1: Marketplace Seller
        $this->sellerUser = User::factory()->create([
            'email' => 'seller1@allzone.test',
            'name' => 'Marketplace Merchant',
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Marketplace Mega Deals',
            'seller_type' => SellerType::MARKETPLACE,
            'status' => SellerStatus::APPROVED,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Mega Deals Central',
            'status' => StoreStatus::ACTIVE,
        ]);

        // Seller 2: Local Store Seller
        $this->otherSellerUser = User::factory()->create([
            'email' => 'seller2@allzone.test',
            'name' => 'Local Kirana Boss',
        ]);
        $this->otherSellerUser->assignRole('seller_owner');

        $this->otherSeller = Seller::factory()->create([
            'user_id' => $this->otherSellerUser->id,
            'business_name' => 'Local Quick Store',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function test_seller_can_view_published_announcements_targeted_to_all(): void
    {
        Announcement::create([
            'title' => 'Important GST Compliance Update 2026',
            'slug' => 'important-gst-compliance-update-2026',
            'category' => 'important',
            'priority' => 'urgent',
            'summary' => 'All sellers must verify their e-invoicing thresholds.',
            'content' => 'Full details regarding new tax compliances on the platform.',
            'target_type' => 'all',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.notices.index'));

        $response->assertStatus(200);
        $response->assertSee('Important GST Compliance Update 2026');
        $response->assertSee('Important Notice');
        $response->assertSee('Urgent');
        $response->assertSee('All sellers must verify their e-invoicing thresholds.');
    }

    public function test_seller_only_sees_announcements_targeted_to_their_group(): void
    {
        // Notice targeted strictly to local_store sellers
        Announcement::create([
            'title' => 'Local Store Quick Commerce Guidelines',
            'slug' => 'local-store-quick-commerce-guidelines',
            'category' => 'logistics',
            'priority' => 'normal',
            'summary' => 'Fast dispatch requirements for neighbourhood stores.',
            'content' => 'Pack orders within 15 minutes.',
            'target_type' => 'seller_group',
            'target_seller_type' => 'local_store',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        // Marketplace seller should NOT see it
        $responseMarketplace = $this->actingAs($this->sellerUser)->get(route('seller.notices.index'));
        $responseMarketplace->assertStatus(200);
        $responseMarketplace->assertDontSee('Local Store Quick Commerce Guidelines');

        // Local store seller MUST see it
        $responseLocal = $this->actingAs($this->otherSellerUser)->get(route('seller.notices.index'));
        $responseLocal->assertStatus(200);
        $responseLocal->assertSee('Local Store Quick Commerce Guidelines');
    }

    public function test_seller_does_not_see_draft_or_scheduled_or_expired_notices(): void
    {
        // Draft notice
        Announcement::create([
            'title' => 'Secret Draft Announcement',
            'slug' => 'secret-draft-announcement',
            'category' => 'general',
            'content' => 'Draft content not ready yet.',
            'target_type' => 'all',
            'is_published' => false,
        ]);

        // Scheduled notice
        Announcement::create([
            'title' => 'Future Festival Big Sale Announcement',
            'slug' => 'future-festival-big-sale-announcement',
            'category' => 'general',
            'content' => 'Coming in 5 days.',
            'target_type' => 'all',
            'published_at' => now()->addDays(5),
            'is_published' => true,
        ]);

        // Expired notice
        Announcement::create([
            'title' => 'Expired Flash Sale Rules',
            'slug' => 'expired-flash-sale-rules',
            'category' => 'general',
            'content' => 'Old rules that expired yesterday.',
            'target_type' => 'all',
            'published_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.notices.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Secret Draft Announcement');
        $response->assertDontSee('Future Festival Big Sale Announcement');
        $response->assertDontSee('Expired Flash Sale Rules');
    }

    public function test_seller_viewing_notice_details_automatically_marks_it_as_read(): void
    {
        $announcement = Announcement::create([
            'title' => 'Packaging Material SLA Policy',
            'slug' => 'packaging-material-sla-policy',
            'category' => 'policy',
            'priority' => 'high',
            'summary' => 'Mandatory eco-friendly boxes for all consignments.',
            'content' => 'Here are the technical specifications for packaging and labels.',
            'target_type' => 'all',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this->assertFalse($announcement->isReadBy($this->seller));

        $response = $this->actingAs($this->sellerUser)->get(route('seller.notices.show', $announcement));

        $response->assertStatus(200);
        $response->assertSee('Packaging Material SLA Policy');
        $response->assertSee('Here are the technical specifications for packaging and labels.');

        $this->assertTrue($announcement->fresh()->isReadBy($this->seller));
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_seller_can_mark_all_notices_as_read(): void
    {
        $announcement1 = Announcement::create([
            'title' => 'Notice 1 for Sellers',
            'slug' => 'notice-1-for-sellers',
            'category' => 'general',
            'content' => 'Content 1',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        $announcement2 = Announcement::create([
            'title' => 'Notice 2 for Sellers',
            'slug' => 'notice-2-for-sellers',
            'category' => 'general',
            'content' => 'Content 2',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->assertFalse($announcement1->isReadBy($this->seller));
        $this->assertFalse($announcement2->isReadBy($this->seller));

        $response = $this->actingAs($this->sellerUser)->post(route('seller.notices.mark_all_read'));

        $response->assertRedirect();
        $this->assertTrue($announcement1->fresh()->isReadBy($this->seller));
        $this->assertTrue($announcement2->fresh()->isReadBy($this->seller));
    }

    public function test_seller_can_download_notice_attachment(): void
    {
        $fakeFile = UploadedFile::fake()->create('standard_operating_procedure.pdf', 100, 'application/pdf');
        $storedPath = $fakeFile->store('announcements/attachments', 'public');

        $announcement = Announcement::create([
            'title' => 'Shiprocket Dispatch SOP 2026',
            'slug' => 'shiprocket-dispatch-sop-2026',
            'category' => 'logistics',
            'content' => 'Download the SOP guide.',
            'attachment_path' => $storedPath,
            'attachment_name' => 'standard_operating_procedure.pdf',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.notices.download', $announcement));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_seller_cannot_access_unauthorized_targeted_notice(): void
    {
        $targetedAnnouncement = Announcement::create([
            'title' => 'Confidential Brand Seller Meeting',
            'slug' => 'confidential-brand-seller-meeting',
            'category' => 'important',
            'content' => 'Exclusively for brand owners.',
            'target_type' => 'seller_group',
            'target_seller_type' => 'brand',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        // Seller 1 is a marketplace seller, not a brand
        $response = $this->actingAs($this->sellerUser)->get(route('seller.notices.show', $targetedAnnouncement));
        $response->assertStatus(404);
    }

    public function test_admin_can_list_announcements_with_metrics(): void
    {
        Announcement::create([
            'title' => 'Admin Test Notice',
            'slug' => 'admin-test-notice',
            'category' => 'general',
            'content' => 'Content',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.announcements.index'));

        $response->assertStatus(200);
        $response->assertSee('Seller Notices &amp; Announcements', false);
        $response->assertSee('Admin Test Notice');
        $response->assertSee('Live &amp; Active', false);
    }

    public function test_admin_can_create_announcement_with_file_uploads(): void
    {
        $banner = UploadedFile::fake()->image('festival_banner.jpg', 600, 300);
        $doc = UploadedFile::fake()->create('festival_guidelines.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.announcements.store'), [
            'title' => 'Diwali Mega Sale 2026 Terms',
            'category' => 'policy',
            'priority' => 'high',
            'summary' => 'Festive logistics schedules and inventory deadlines.',
            'content' => 'Detailed commission discount terms for the festival sale.',
            'target_type' => 'all',
            'is_published' => '1',
            'banner_image' => $banner,
            'attachment' => $doc,
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Diwali Mega Sale 2026 Terms',
            'slug' => 'diwali-mega-sale-2026-terms',
            'category' => 'policy',
            'priority' => 'high',
            'is_published' => true,
        ]);

        $announcement = Announcement::where('slug', 'diwali-mega-sale-2026-terms')->first();
        $this->assertNotNull($announcement->banner_image);
        $this->assertNotNull($announcement->attachment_path);
        Storage::disk('public')->assertExists($announcement->banner_image);
        Storage::disk('public')->assertExists($announcement->attachment_path);
    }

    public function test_admin_can_update_and_toggle_publish_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Original Notice Title',
            'slug' => 'original-notice-title',
            'category' => 'general',
            'priority' => 'normal',
            'content' => 'Initial content',
            'target_type' => 'all',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Update
        $response = $this->actingAs($this->adminUser)->put(route('admin.announcements.update', $announcement), [
            'title' => 'Updated Notice Title by Admin',
            'category' => 'important',
            'priority' => 'urgent',
            'content' => 'Updated content with revised guidelines.',
            'target_type' => 'all',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Notice Title by Admin',
            'priority' => 'urgent',
        ]);

        // Toggle to unpublish
        $toggleResponse = $this->actingAs($this->adminUser)->post(route('admin.announcements.toggle', $announcement));
        $toggleResponse->assertRedirect();
        $this->assertFalse($announcement->fresh()->is_published);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Notice to be Deleted',
            'slug' => 'notice-to-be-deleted',
            'category' => 'general',
            'content' => 'Will be purged',
            'target_type' => 'all',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('admin.announcements.destroy', $announcement));

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    public function test_published_announcements_appear_on_seller_dashboard(): void
    {
        Announcement::create([
            'title' => 'New Courier Payout Cycle Activated',
            'slug' => 'new-courier-payout-cycle-activated',
            'category' => 'logistics',
            'priority' => 'high',
            'summary' => 'Weekly payout cycle starting every Monday.',
            'content' => 'Full logistics terms.',
            'target_type' => 'all',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->sellerUser)->get(route('seller.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('New Courier Payout Cycle Activated');
        $response->assertSee('Logistics &amp; Cargo', false);
    }
}
