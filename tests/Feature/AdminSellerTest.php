<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SellerDocumentStatus;
use App\Enums\SellerDocumentType;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerBankAccount;
use App\Models\SellerDocument;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\User;
use App\Notifications\SellerApprovedNotification;
use App\Notifications\SellerRejectedNotification;
use App\Notifications\SellerSuspendedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminSellerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $supportUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Super Admin / Operations Lead (has all permissions including sellers.approve, sellers.suspend)
        $this->adminUser = User::factory()->create([
            'email' => 'admin.ops@onestall.in',
            'name' => 'Operations Director',
        ]);
        $this->adminUser->assignRole('admin');

        // Support Admin (does NOT have sellers.approve or sellers.suspend)
        $this->supportUser = User::factory()->create([
            'email' => 'support.agent@onestall.in',
            'name' => 'Support Desk Lead',
        ]);
        $this->supportUser->assignRole('support_admin');
    }

    public function test_admin_can_view_sellers_list_with_filters_and_search(): void
    {
        $sellerUser1 = User::factory()->create(['name' => 'Bangalore Organic Store', 'email' => 'bangalore@organic.in']);
        $seller1 = Seller::factory()->create([
            'user_id' => $sellerUser1->id,
            'business_name' => 'Bangalore Organic Emporium',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::PENDING,
        ]);
        $store1 = Store::factory()->create(['seller_id' => $seller1->id]);
        StoreLocation::create([
            'store_id' => $store1->id,
            'address' => 'Indiranagar 100ft road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'lat' => 12.9716,
            'lng' => 77.5946,
        ]);

        $sellerUser2 = User::factory()->create(['name' => 'Delhi Electronics', 'email' => 'delhi@electro.in']);
        $seller2 = Seller::factory()->create([
            'user_id' => $sellerUser2->id,
            'business_name' => 'Delhi Gadget Hub',
            'seller_type' => SellerType::MARKETPLACE,
            'status' => SellerStatus::APPROVED,
        ]);

        // 1. Unfiltered list
        $response = $this->actingAs($this->adminUser)->get('/admin/sellers');
        $response->assertStatus(200);
        $response->assertSee('Bangalore Organic Emporium');
        $response->assertSee('Delhi Gadget Hub');

        // 2. Filter by status: pending
        $pendingResponse = $this->actingAs($this->adminUser)->get('/admin/sellers?status=pending');
        $pendingResponse->assertStatus(200);
        $pendingResponse->assertSee('Bangalore Organic Emporium');
        $pendingResponse->assertDontSee('Delhi Gadget Hub');

        // 3. Filter by search query
        $searchResponse = $this->actingAs($this->adminUser)->get('/admin/sellers?search=Bangalore');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Bangalore Organic Emporium');
        $searchResponse->assertDontSee('Delhi Gadget Hub');

        // 4. Filter by city
        $cityResponse = $this->actingAs($this->adminUser)->get('/admin/sellers?city=Bengaluru');
        $cityResponse->assertStatus(200);
        $cityResponse->assertSee('Bangalore Organic Emporium');
        $cityResponse->assertDontSee('Delhi Gadget Hub');
    }

    public function test_sellers_list_can_be_exported_to_csv(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Exportable Merchant LLP',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/sellers/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="allzone_sellers_export_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Business Name', $content);
        $this->assertStringContainsString('Exportable Merchant LLP', $content);
    }

    public function test_seller_detail_page_renders_with_tabs_and_statistics(): void
    {
        $sellerUser = User::factory()->create(['name' => 'Ramesh Merchant', 'email' => 'ramesh@retail.in']);
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Ramesh Kirana Mart',
            'legal_name' => 'Ramesh Retail Private Limited',
            'status' => SellerStatus::PENDING,
            'pan' => 'ABCDE1234F',
            'gstin' => '29ABCDE1234F1Z5',
        ]);

        $store = Store::factory()->create(['seller_id' => $seller->id, 'name' => 'Ramesh Kirana Store']);
        StoreLocation::create([
            'store_id' => $store->id,
            'address' => 'Brigade Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'lat' => 12.9716,
            'lng' => 77.5946,
        ]);

        SellerDocument::create([
            'seller_id' => $seller->id,
            'type' => SellerDocumentType::PAN_CARD,
            'file_path' => 'documents/sellers/pan_sample.pdf',
            'status' => SellerDocumentStatus::PENDING,
        ]);

        SellerBankAccount::create([
            'seller_id' => $seller->id,
            'account_holder' => 'Ramesh Kirana Mart',
            'account_no' => '987654321000',
            'ifsc' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'branch_name' => 'MG Road',
            'is_primary' => true,
        ]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'title' => 'Ramesh Basmati Rice 5kg',
            'product_type' => \App\Enums\ProductType::SIMPLE,
        ]);

        $custUser = User::factory()->create();
        $customer = \App\Models\Customer::create(['user_id' => $custUser->id]);

        $parentOrder = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-SELLER-DETAIL-1',
            'subtotal' => 500,
            'tax' => 25,
            'delivery_fee' => 40,
            'total' => 565,
            'payment_status' => \App\Enums\PaymentStatus::PAID,
            'payment_method' => \App\Enums\PaymentMethod::UPI,
            'status' => \App\Enums\OrderStatus::DELIVERED,
            'address_snapshot' => ['city' => 'Bengaluru'],
        ]);

        FulfilmentOrder::create([
            'order_id' => $parentOrder->id,
            'fulfilment_order_number' => 'FO-SELLER-DETAIL-1',
            'seller_id' => $seller->id,
            'fulfilment_type' => \App\Enums\FulfilmentType::QUICK,
            'status' => 'delivered',
            'subtotal' => 500,
            'commission_amount' => 25,
            'seller_payable' => 475,
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}");

        $response->assertStatus(200);
        $response->assertSee('Ramesh Kirana Mart');
        $response->assertSee('Ramesh Retail Private Limited');
        $response->assertSee('ABCDE1234F');
        $response->assertSee('29ABCDE1234F1Z5');
        $response->assertSee('PAN Card');
        $response->assertSee('HDFC Bank');
        $response->assertSee('Overview');
        $response->assertSee('Business &amp; KYC', false);
        $response->assertSee('Commission Override');
        $response->assertSee('Activity Log');
    }

    public function test_unauthorized_admin_cannot_approve_seller(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'status' => SellerStatus::PENDING,
        ]);

        // Support desk user lacks 'sellers.approve' permission
        $response = $this->actingAs($this->supportUser)->post("/admin/sellers/{$seller->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals(SellerStatus::PENDING, $seller->fresh()->status);
    }

    public function test_authorized_admin_can_approve_seller_with_workflow(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create(['name' => 'Fresh Dairy Owner']);
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Fresh Milk Co',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::PENDING,
        ]);

        // Prior to approval, seller user does not have seller_owner role
        $this->assertFalse($sellerUser->hasRole('seller_owner'));

        $response = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/approve");

        $response->assertRedirect();
        $seller->refresh();

        // 1. Status updated to approved with timestamps
        $this->assertEquals(SellerStatus::APPROVED, $seller->status);
        $this->assertNotNull($seller->approved_at);
        $this->assertEquals($this->adminUser->id, $seller->approved_by);

        // 2. Default store auto-created with unique slug
        $this->assertCount(1, $seller->stores);
        $store = $seller->stores->first();
        $this->assertEquals('Fresh Milk Co', $store->name);
        $this->assertEquals(StoreStatus::ACTIVE, $store->status);
        $this->assertTrue($store->accepts_quick_commerce);

        // 3. seller_owner role assigned to user
        $this->assertTrue($sellerUser->fresh()->hasRole('seller_owner'));

        // 4. Queued notification sent
        Notification::assertSentTo($sellerUser, SellerApprovedNotification::class);

        // 5. Activity log generated
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Seller::class,
            'subject_id' => $seller->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_rejecting_seller_requires_mandatory_remarks(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'status' => SellerStatus::KYC_SUBMITTED,
        ]);

        // Attempting to reject without reason fails validation
        $failResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/reject", [
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('reason');

        // Providing valid reason
        $response = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/reject", [
            'reason' => 'Invalid GSTIN number provided. Please upload valid certificate.',
        ]);

        $response->assertRedirect();
        $seller->refresh();

        $this->assertEquals(SellerStatus::REJECTED, $seller->status);
        $this->assertEquals('Invalid GSTIN number provided. Please upload valid certificate.', $seller->rejection_reason);

        // Notification sent
        Notification::assertSentTo($sellerUser, SellerRejectedNotification::class);

        // Audit log verified
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Seller::class,
            'subject_id' => $seller->id,
            'description' => "Rejected seller application #{$seller->id} ({$seller->business_name}). Reason: Invalid GSTIN number provided. Please upload valid certificate.",
        ]);
    }

    public function test_suspend_and_reactivate_workflow(): void
    {
        Notification::fake();

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'status' => SellerStatus::APPROVED,
        ]);

        $store = Store::factory()->create([
            'seller_id' => $seller->id,
            'status' => StoreStatus::ACTIVE,
        ]);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'status' => ProductStatus::ACTIVE,
        ]);

        // 1. Suspend seller without reason fails
        $failResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/suspend", [
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('reason');

        // 2. Suspend with reason succeeds and deactivates storefront + products
        $suspendResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/suspend", [
            'reason' => 'Multiple counterfeit product flags received from consumers.',
        ]);

        $suspendResponse->assertRedirect();
        $seller->refresh();

        $this->assertEquals(SellerStatus::SUSPENDED, $seller->status);
        $this->assertEquals('Multiple counterfeit product flags received from consumers.', $seller->suspension_reason);
        $this->assertEquals(StoreStatus::SUSPENDED, $store->fresh()->status);
        $this->assertEquals(ProductStatus::INACTIVE, $product->fresh()->status);

        Notification::assertSentTo($sellerUser, SellerSuspendedNotification::class);

        // 3. Reactivate seller restores storefront and products
        $reactivateResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/reactivate", [
            'reason' => 'Vendor submitted authentic authorized distributor invoices.',
        ]);

        $reactivateResponse->assertRedirect();
        $seller->refresh();

        $this->assertEquals(SellerStatus::APPROVED, $seller->status);
        $this->assertNull($seller->suspension_reason);
        $this->assertEquals(StoreStatus::ACTIVE, $store->fresh()->status);
        $this->assertEquals(ProductStatus::ACTIVE, $product->fresh()->status);
    }

    public function test_admin_can_approve_and_reject_individual_kyc_document(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);

        $document = SellerDocument::create([
            'seller_id' => $seller->id,
            'type' => SellerDocumentType::FSSAI_LICENSE,
            'file_path' => 'documents/fssai.pdf',
            'status' => SellerDocumentStatus::PENDING,
        ]);

        // Approve document
        $approveResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/documents/{$document->id}/review", [
            'status' => 'approved',
            'remarks' => 'FSSAI License verified with national food safety registry.',
        ]);

        $approveResponse->assertRedirect();
        $this->assertEquals(SellerDocumentStatus::APPROVED, $document->fresh()->status);

        // Reject document without remarks fails
        $failResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/documents/{$document->id}/review", [
            'status' => 'rejected',
            'remarks' => '',
        ]);
        $failResponse->assertSessionHasErrors('remarks');

        // Reject document with remarks
        $rejectResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/documents/{$document->id}/review", [
            'status' => 'rejected',
            'remarks' => 'Expired validity period on certificate.',
        ]);
        $rejectResponse->assertRedirect();
        $this->assertEquals(SellerDocumentStatus::REJECTED, $document->fresh()->status);
        $this->assertEquals('Expired validity period on certificate.', $document->fresh()->remarks);
    }

    public function test_admin_can_verify_and_unverify_bank_account(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);

        $bank = SellerBankAccount::create([
            'seller_id' => $seller->id,
            'account_holder' => 'Test Merchant Account',
            'account_no' => '123456789012',
            'ifsc' => 'SBIN0001234',
            'bank_name' => 'State Bank of India',
            'is_primary' => true,
        ]);

        $this->assertNull($bank->verified_at);

        // Verify bank account
        $verifyResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/bank-accounts/{$bank->id}/verify", [
            'verified' => '1',
        ]);
        $verifyResponse->assertRedirect();
        $this->assertNotNull($bank->fresh()->verified_at);

        // Unverify bank account
        $unverifyResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/bank-accounts/{$bank->id}/verify", [
            'verified' => '0',
        ]);
        $unverifyResponse->assertRedirect();
        $this->assertNull($bank->fresh()->verified_at);
    }

    public function test_admin_can_update_commission_override(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'commission_override' => null,
        ]);

        // Set commission override to 7.50%
        $response = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/commission", [
            'commission_override' => 7.50,
            'remarks' => 'Special promotion partnership agreement.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(7.50, $seller->fresh()->commission_override);

        // Reset to platform default (null)
        $resetResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/commission", [
            'commission_override' => null,
            'remarks' => 'Reverted to platform default.',
        ]);
        $resetResponse->assertRedirect();
        $this->assertNull($seller->fresh()->commission_override);
    }

    public function test_admin_can_execute_bulk_actions_on_merchants(): void
    {
        Notification::fake();

        $seller1 = Seller::factory()->create(['status' => SellerStatus::PENDING]);
        $seller2 = Seller::factory()->create(['status' => SellerStatus::PENDING]);

        $response = $this->actingAs($this->adminUser)->post('/admin/sellers/bulk-action', [
            'action' => 'approve',
            'seller_ids' => [$seller1->id, $seller2->id],
        ]);

        $response->assertRedirect();
        $this->assertEquals(SellerStatus::APPROVED, $seller1->fresh()->status);
        $this->assertEquals(SellerStatus::APPROVED, $seller2->fresh()->status);
    }

    public function test_admin_can_download_seller_kyc_document(): void
    {
        \Illuminate\Support\Facades\Storage::fake('private');

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);

        $filePath = 'documents/sellers/pan_test.pdf';
        \Illuminate\Support\Facades\Storage::disk('private')->put($filePath, 'fake-document-content');

        $doc = SellerDocument::create([
            'seller_id' => $seller->id,
            'type' => SellerDocumentType::PAN_CARD,
            'file_path' => $filePath,
            'status' => SellerDocumentStatus::PENDING,
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}/documents/{$doc->id}/download");
        $response->assertStatus(200);
    }

    public function test_sellers_index_tabs_filter_correctly_and_display_accurate_counts(): void
    {
        $pendingUser = User::factory()->create();
        $pendingSeller = Seller::factory()->create([
            'user_id' => $pendingUser->id,
            'business_name' => 'Pending Merchant Alpha',
            'status' => SellerStatus::PENDING,
        ]);

        $activeUser = User::factory()->create();
        $activeSeller = Seller::factory()->create([
            'user_id' => $activeUser->id,
            'business_name' => 'Active Merchant Beta',
            'status' => SellerStatus::APPROVED,
        ]);

        $rejectedUser = User::factory()->create();
        $rejectedSeller = Seller::factory()->create([
            'user_id' => $rejectedUser->id,
            'business_name' => 'Rejected Merchant Gamma',
            'status' => SellerStatus::REJECTED,
        ]);

        $suspendedUser = User::factory()->create();
        $suspendedSeller = Seller::factory()->create([
            'user_id' => $suspendedUser->id,
            'business_name' => 'Suspended Merchant Delta',
            'status' => SellerStatus::SUSPENDED,
        ]);

        // 1. All tab
        $allRes = $this->actingAs($this->adminUser)->get('/admin/sellers?tab=all');
        $allRes->assertStatus(200);
        $allRes->assertSee('Pending Merchant Alpha');
        $allRes->assertSee('Active Merchant Beta');
        $allRes->assertSee('Rejected Merchant Gamma');
        $allRes->assertSee('Suspended Merchant Delta');

        // 2. Pending tab
        $pendingRes = $this->actingAs($this->adminUser)->get('/admin/sellers?tab=pending');
        $pendingRes->assertStatus(200);
        $pendingRes->assertSee('Pending Merchant Alpha');
        $pendingRes->assertDontSee('Active Merchant Beta');
        $pendingRes->assertDontSee('Rejected Merchant Gamma');
        $pendingRes->assertDontSee('Suspended Merchant Delta');

        // 3. Active tab
        $activeRes = $this->actingAs($this->adminUser)->get('/admin/sellers?tab=active');
        $activeRes->assertStatus(200);
        $activeRes->assertSee('Active Merchant Beta');
        $activeRes->assertDontSee('Pending Merchant Alpha');
        $activeRes->assertDontSee('Rejected Merchant Gamma');
        $activeRes->assertDontSee('Suspended Merchant Delta');

        // 4. Rejected tab
        $rejectedRes = $this->actingAs($this->adminUser)->get('/admin/sellers?tab=rejected');
        $rejectedRes->assertStatus(200);
        $rejectedRes->assertSee('Rejected Merchant Gamma');
        $rejectedRes->assertDontSee('Pending Merchant Alpha');
        $rejectedRes->assertDontSee('Active Merchant Beta');

        // 5. Suspended tab
        $suspendedRes = $this->actingAs($this->adminUser)->get('/admin/sellers?tab=suspended');
        $suspendedRes->assertStatus(200);
        $suspendedRes->assertSee('Suspended Merchant Delta');
        $suspendedRes->assertDontSee('Active Merchant Beta');
        $suspendedRes->assertDontSee('Rejected Merchant Gamma');
    }

    public function test_sellers_index_renders_all_required_columns(): void
    {
        $sellerUser = User::factory()->create([
            'name' => 'Kavita Sharma',
            'email' => 'kavita@artisancraft.in',
            'phone' => '9876501234',
        ]);
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Artisan Craft Bazaar',
            'seller_type' => SellerType::MARKETPLACE,
            'status' => SellerStatus::APPROVED,
            'gstin' => '29AABCA1234A1Z5',
            'commission_override' => 8.50,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/sellers');

        $response->assertStatus(200);
        $response->assertSee('Artisan Craft Bazaar');
        $response->assertSee('Kavita Sharma');
        $response->assertSee('kavita@artisancraft.in');
        $response->assertSee('9876501234');
        $response->assertSee('29AABCA1234A1Z5');
        $response->assertSee('8.5%');
        $response->assertSee('Custom Override');
        $response->assertSee('Active');
        $response->assertSee('items / orders');
    }

    public function test_admin_can_request_additional_verification_documents_from_seller(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Spices of Kerala',
            'status' => SellerStatus::PENDING,
        ]);

        // Attempting without instructions fails validation
        $failResponse = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/request-documents", [
            'document_type' => 'fssai_license',
            'instructions' => '',
        ]);
        $failResponse->assertSessionHasErrors('instructions');

        // Successful document request
        $response = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/request-documents", [
            'document_type' => 'fssai_license',
            'instructions' => 'Please provide valid 14-digit state FSSAI manufacturing license.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify document entry exists with pending status
        $this->assertDatabaseHas('seller_documents', [
            'seller_id' => $seller->id,
            'type' => 'fssai_license',
            'status' => 'pending',
            'remarks' => 'Requested by Administration: Please provide valid 14-digit state FSSAI manufacturing license.',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Seller::class,
            'subject_id' => $seller->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_update_seller_operational_settings(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'seller_type' => SellerType::LOCAL_STORE,
            'commission_override' => null,
        ]);
        $store = Store::factory()->create([
            'seller_id' => $seller->id,
            'accepts_quick_commerce' => false,
            'accepts_pickup' => false,
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/sellers/{$seller->id}/settings", [
            'seller_type' => 'marketplace',
            'commission_override' => 12.00,
            'accepts_quick_commerce' => '1',
            'accepts_pickup' => '1',
            'remarks' => 'Upgraded merchant to marketplace tier with custom rake.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertEquals(SellerType::MARKETPLACE, $seller->seller_type);
        $this->assertEquals(12.00, $seller->commission_override);

        $store->refresh();
        $this->assertTrue($store->accepts_quick_commerce);
        $this->assertTrue($store->accepts_pickup);

        // Audit log verified
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Seller::class,
            'subject_id' => $seller->id,
            'causer_id' => $this->adminUser->id,
        ]);
    }

    public function test_sensitive_bank_details_are_masked_for_unauthorized_staff_and_unmasked_for_authorized_staff(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);

        SellerBankAccount::create([
            'seller_id' => $seller->id,
            'account_holder' => 'Sensitive Merchant Account',
            'account_no' => '987654321000',
            'ifsc' => 'HDFC0004321',
            'bank_name' => 'HDFC Bank Payouts',
            'is_primary' => true,
        ]);

        // 1. Support admin user (no viewSensitive authorization) sees masked account number
        $supportResponse = $this->actingAs($this->supportUser)->get("/admin/sellers/{$seller->id}?tab=bank");
        $supportResponse->assertStatus(200);
        $supportResponse->assertSee('Restricted Financial Access');
        $supportResponse->assertSee('•••••••• 1000');
        $supportResponse->assertDontSee('987654321000');

        // 2. Admin user (authorized) sees unmasked account number
        $adminResponse = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}?tab=bank");
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Staff Clearance');
        $adminResponse->assertSee('987654321000');
    }

    public function test_seller_detail_new_tabs_render_proper_content(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $sellerUser->id,
            'business_name' => 'All Tabs Showcase Seller',
        ]);

        // 1. Pickup Location
        \App\Models\SellerPickupLocation::create([
            'seller_id' => $seller->id,
            'nickname' => 'Central Hub Warehouse',
            'contact_person' => 'Rajesh Logistics',
            'contact_phone' => '9876598765',
            'address_line1' => 'Plot 42, Electronic City Phase 1',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560100',
            'shiprocket_pickup_name' => 'SR_BLR_01',
            'is_default' => true,
            'is_verified' => true,
            'is_serviceable' => true,
        ]);

        // 2. Settlement
        \App\Models\SellerSettlement::create([
            'seller_id' => $seller->id,
            'settlement_number' => 'SET-TEST-999',
            'period_start' => now()->subDays(7),
            'period_end' => now(),
            'gross' => 15000.00,
            'commission' => 1500.00,
            'tax_on_commission' => 270.00,
            'cod_collected' => 0.00,
            'adjustments' => 0.00,
            'net' => 13230.00,
            'status' => \App\Enums\SettlementStatus::SETTLED,
            'paid_at' => now(),
            'reference' => 'UTR123456789',
        ]);

        // 3. Fulfilment order and Commission entry
        $custUser = User::factory()->create();
        $customer = \App\Models\Customer::create(['user_id' => $custUser->id]);
        $parentOrder = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-COMM-ENTRY-1',
            'subtotal' => 2000,
            'tax' => 100,
            'delivery_fee' => 50,
            'total' => 2150,
            'payment_status' => \App\Enums\PaymentStatus::PAID,
            'payment_method' => \App\Enums\PaymentMethod::UPI,
            'status' => \App\Enums\OrderStatus::DELIVERED,
            'address_snapshot' => ['city' => 'Bengaluru'],
        ]);
        $fo = FulfilmentOrder::create([
            'order_id' => $parentOrder->id,
            'fulfilment_order_number' => 'FO-COMM-ENTRY-1',
            'seller_id' => $seller->id,
            'fulfilment_type' => \App\Enums\FulfilmentType::QUICK,
            'status' => 'delivered',
            'subtotal' => 2000,
            'commission_amount' => 200,
            'seller_payable' => 1800,
        ]);
        \App\Models\CommissionEntry::create([
            'fulfilment_order_id' => $fo->id,
            'base_amount' => 2000.00,
            'commission' => 200.00,
            'tax_on_commission' => 36.00,
            'gateway_fee' => 0.00,
            'shipping_adjustment' => 0.00,
            'net_payable' => 1764.00,
            'entry_type' => 'Standard Commission',
            'notes' => '10% platform rake applied.',
        ]);

        // 4. Support Ticket
        \App\Models\SupportTicket::create([
            'ticket_number' => 'TCK-SHOWCASE-1',
            'seller_id' => $seller->id,
            'user_id' => $sellerUser->id,
            'subject' => 'Inquiry regarding Shiprocket API integration',
            'category' => 'Shipping & Fulfilment',
            'priority' => \App\Enums\TicketPriority::HIGH,
            'status' => \App\Enums\TicketStatus::OPEN,
        ]);

        // Test Pickup Tab
        $pickupRes = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}?tab=pickup");
        $pickupRes->assertStatus(200);
        $pickupRes->assertSee('Central Hub Warehouse');
        $pickupRes->assertSee('Plot 42, Electronic City Phase 1');
        $pickupRes->assertSee('560100');
        $pickupRes->assertSee('SR_BLR_01');

        // Test Payments Tab
        $paymentRes = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}?tab=payments");
        $paymentRes->assertStatus(200);
        $paymentRes->assertSee('SET-TEST-999');
        $paymentRes->assertSee('15,000.00');
        $paymentRes->assertSee('13,230.00');
        $paymentRes->assertSee('UTR123456789');

        // Test Commission Tab
        $commRes = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}?tab=commission");
        $commRes->assertStatus(200);
        $commRes->assertSee('Merchant Commission Agreement');
        $commRes->assertSee('Commission Deduction Ledger');
        $commRes->assertSee('2,000.00');
        $commRes->assertSee('10% platform rake applied.');

        // Test Tickets Tab
        $ticketRes = $this->actingAs($this->adminUser)->get("/admin/sellers/{$seller->id}?tab=tickets");
        $ticketRes->assertStatus(200);
        $ticketRes->assertSee('TCK-SHOWCASE-1');
        $ticketRes->assertSee('Inquiry regarding Shiprocket API integration');
    }
}
