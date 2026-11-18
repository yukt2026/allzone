<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryLocationType;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\StoreStatus;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerCompleteInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $sellerUser;
    protected Seller $seller;
    protected Store $store;
    protected InventoryLocation $location;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;
    protected Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->sellerUser = User::factory()->create([
            'email' => 'inventory.merchant@allzone.in',
            'phone' => '+919876543212',
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->sellerUser->assignRole('seller_owner');

        $this->seller = Seller::factory()->create([
            'user_id' => $this->sellerUser->id,
            'business_name' => 'Apex Retail & Apparel',
            'legal_name' => 'Apex Retail Private Limited',
            'seller_type' => SellerType::LOCAL_STORE,
            'status' => SellerStatus::APPROVED,
            'onboarding_step' => 7,
            'approved_at' => now(),
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Central Hub',
            'slug' => 'apex-central-hub',
            'status' => StoreStatus::ACTIVE,
        ]);

        $this->location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'name' => 'Apex Central Warehouse',
            'type' => InventoryLocationType::STORE,
            'is_active' => true,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Men Apparel',
            'slug' => 'men-apparel',
        ]);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Classic Polo T-Shirt',
            'slug' => 'classic-polo-t-shirt',
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'APX-POLO-NAVY-M',
            'name' => 'Navy / Medium',
            'mrp' => 1299.00,
            'selling_price' => 799.00,
            'is_active' => true,
        ]);

        $this->inventory = Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 5,
            'low_stock_threshold' => 10,
            'is_quick_available' => true,
        ]);
    }

    /**
     * Test 1: Seller can view inventory dashboard with all 7 tabs and KPI metrics.
     */
    public function test_seller_can_view_inventory_dashboard_with_all_metrics_and_tabs(): void
    {
        $response = $this->actingAs($this->sellerUser)->get(route('seller.inventory.index'));

        $response->assertStatus(200);
        $response->assertViewIs('seller.inventory.index');
        $response->assertSee('Inventory &amp; Stock Balances', false);
        $response->assertSee('All Stock');
        $response->assertSee('Active');
        $response->assertSee('Out of Stock');
        $response->assertSee('Low Stock');
        $response->assertSee('Pending Approval');
        $response->assertSee('Blocked');
        $response->assertSee('Paused');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total_products']);
        $this->assertEquals(1, $metrics['total_skus']);
        $this->assertEquals(50, $metrics['total_on_hand']);
        $this->assertEquals(5, $metrics['total_reserved']);
        $this->assertEquals(45, $metrics['total_available']); // 50 - 5
        $this->assertEquals(1, $metrics['in_stock_skus']);

        $counts = $response->viewData('counts');
        $this->assertEquals(1, $counts['all']);
        $this->assertEquals(1, $counts['active']);
    }

    /**
     * Test 2: Multi-vendor tenant isolation on inventory.
     */
    public function test_multivendor_tenant_isolation_on_inventory(): void
    {
        $otherUser = User::factory()->create(['email' => 'other.merchant@allzone.in']);
        $otherUser->assignRole('seller_owner');

        $otherSeller = Seller::factory()->create([
            'user_id' => $otherUser->id,
            'business_name' => 'Royal Heritage Silks',
            'status' => SellerStatus::APPROVED,
        ]);

        $otherLocation = InventoryLocation::create([
            'seller_id' => $otherSeller->id,
            'name' => 'Royal Silks Depot',
            'type' => InventoryLocationType::STORE,
            'is_active' => true,
        ]);

        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'title' => 'Kanchipuram Pattu Saree',
        ]);

        $otherVariant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'sku' => 'ROYAL-SAREE-GOLD',
        ]);

        $otherInventory = Inventory::create([
            'variant_id' => $otherVariant->id,
            'location_id' => $otherLocation->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);

        // Seller A should see only their inventory, NOT Seller B's
        $response = $this->actingAs($this->sellerUser)->get(route('seller.inventory.index'));
        $response->assertStatus(200);
        $response->assertSee($this->variant->sku);
        $response->assertDontSee($otherVariant->sku);

        // Seller A cannot update Seller B's stock (403 Forbidden)
        $unauthorized = $this->actingAs($this->sellerUser)->post(
            route('seller.inventory.update', $otherInventory),
            ['quantity_on_hand' => 150, 'low_stock_threshold' => 5]
        );
        $unauthorized->assertStatus(403);
    }

    /**
     * Test 3: Tab filtering by status (Out of stock, Low stock, Pending, Blocked, Paused).
     */
    public function test_inventory_tab_filtering_by_status_and_stock_levels(): void
    {
        // 1. Out of stock item (available <= 0)
        $oosProduct = Product::factory()->create(['seller_id' => $this->seller->id, 'title' => 'OOS Shoes']);
        $oosVariant = ProductVariant::factory()->create(['product_id' => $oosProduct->id, 'sku' => 'SHOE-OOS-42']);
        $oosInv = Inventory::create([
            'variant_id' => $oosVariant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 2, // Available = 0
            'low_stock_threshold' => 5,
        ]);

        // 2. Low stock item (available > 0 and <= threshold)
        $lowProduct = Product::factory()->create(['seller_id' => $this->seller->id, 'title' => 'Low Stock Belt']);
        $lowVariant = ProductVariant::factory()->create(['product_id' => $lowProduct->id, 'sku' => 'BELT-LOW-34']);
        $lowInv = Inventory::create([
            'variant_id' => $lowVariant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 4,
            'quantity_reserved' => 0, // Available = 4 <= 5
            'low_stock_threshold' => 5,
        ]);

        // 3. Blocked item (product status = rejected)
        $blockedProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'title' => 'Blocked Replica Watch',
            'status' => ProductStatus::REJECTED,
        ]);
        $blockedVariant = ProductVariant::factory()->create(['product_id' => $blockedProduct->id, 'sku' => 'WATCH-REPLICA']);
        $blockedInv = Inventory::create([
            'variant_id' => $blockedVariant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);

        // Query Out of Stock Tab
        $resOos = $this->actingAs($this->sellerUser)->get(route('seller.inventory.index', ['tab' => 'out_of_stock']));
        $resOos->assertStatus(200);
        $resOos->assertSee($oosVariant->sku);
        $resOos->assertDontSee($this->variant->sku);

        // Query Low Stock Tab
        $resLow = $this->actingAs($this->sellerUser)->get(route('seller.inventory.index', ['tab' => 'low_stock']));
        $resLow->assertStatus(200);
        $resLow->assertSee($lowVariant->sku);
        $resLow->assertDontSee($this->variant->sku);

        // Query Blocked Tab
        $resBlocked = $this->actingAs($this->sellerUser)->get(route('seller.inventory.index', ['tab' => 'blocked']));
        $resBlocked->assertStatus(200);
        $resBlocked->assertSee($blockedVariant->sku);
        $resBlocked->assertDontSee($this->variant->sku);
    }

    /**
     * Test 4: Seller can update individual stock level and movement ledger is recorded.
     */
    public function test_seller_can_update_individual_stock_and_records_transaction_movement(): void
    {
        $response = $this->actingAs($this->sellerUser)->post(
            route('seller.inventory.update', $this->inventory),
            [
                'quantity_on_hand' => 85,
                'low_stock_threshold' => 12,
                'reason' => 'Warehouse cycle count physical verification',
            ]
        );

        $response->assertSessionHas('success');

        $this->inventory->refresh();
        $this->assertEquals(85, $this->inventory->quantity_on_hand);
        $this->assertEquals(12, $this->inventory->low_stock_threshold);
        $this->assertEquals(80, $this->inventory->available_quantity); // 85 - 5

        // Verify stock_movements ledger entry
        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $this->inventory->id,
            'quantity' => 35, // 85 - 50 = +35
            'note' => 'Warehouse cycle count physical verification',
            'created_by' => $this->sellerUser->id,
        ]);
    }

    /**
     * Test 5: Validation prevents negative stock levels.
     */
    public function test_stock_validation_prevents_negative_quantity(): void
    {
        $response = $this->actingAs($this->sellerUser)->post(
            route('seller.inventory.update', $this->inventory),
            [
                'quantity_on_hand' => -10,
                'low_stock_threshold' => 5,
            ]
        );

        $response->assertSessionHasErrors(['quantity_on_hand']);
    }

    /**
     * Test 6: Seller can execute bulk stock updates (add_stock and set_stock).
     */
    public function test_seller_can_execute_bulk_stock_updates(): void
    {
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'sku' => 'APX-POLO-NAVY-L',
        ]);
        $inv2 = Inventory::create([
            'variant_id' => $variant2->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);

        // 1. Bulk Add Stock (+25 units each)
        $resAdd = $this->actingAs($this->sellerUser)->post(
            route('seller.inventory.bulk_update'),
            [
                'inventory_ids' => [$this->inventory->id, $inv2->id],
                'action_type' => 'add_stock',
                'quantity' => 25,
                'reason' => 'Inward shipment batch #77',
            ]
        );

        $resAdd->assertSessionHas('success');

        $this->inventory->refresh();
        $inv2->refresh();
        $this->assertEquals(75, $this->inventory->quantity_on_hand); // 50 + 25
        $this->assertEquals(45, $inv2->quantity_on_hand); // 20 + 25

        // 2. Bulk Set Stock (Set both to exact 100 units)
        $resSet = $this->actingAs($this->sellerUser)->post(
            route('seller.inventory.bulk_update'),
            [
                'inventory_ids' => [$this->inventory->id, $inv2->id],
                'action_type' => 'set_stock',
                'quantity' => 100,
                'reason' => 'Reconciled to 100 each',
            ]
        );

        $resSet->assertSessionHas('success');

        $this->inventory->refresh();
        $inv2->refresh();
        $this->assertEquals(100, $this->inventory->quantity_on_hand);
        $this->assertEquals(100, $inv2->quantity_on_hand);
    }

    /**
     * Test 7: Seller can view movement ledger history JSON for a SKU.
     */
    public function test_seller_can_view_stock_movements_history_json(): void
    {
        // Create 2 stock movements
        StockMovement::create([
            'inventory_id' => $this->inventory->id,
            'type' => \App\Enums\StockMovementType::RESTOCK,
            'quantity' => 50,
            'note' => 'Initial stock intake',
            'created_by' => $this->sellerUser->id,
        ]);

        $response = $this->actingAs($this->sellerUser)->getJson(
            route('seller.inventory.movements', $this->inventory)
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'sku',
            'product_title',
            'location',
            'current_on_hand',
            'movements' => [
                '*' => ['id', 'type', 'type_label', 'quantity', 'note', 'user', 'created_at']
            ],
        ]);

        $this->assertEquals($this->variant->sku, $response->json('sku'));
        $this->assertNotEmpty($response->json('movements'));
    }

    /**
     * Test 8: Seller can export inventory to streaming CSV with UTF-8 BOM.
     */
    public function test_seller_can_export_inventory_to_csv(): void
    {
        $response = $this->actingAs($this->sellerUser)->get(route('seller.inventory.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Product Title', $content);
        $this->assertStringContainsString('Current Stock (On Hand)', $content);
        $this->assertStringContainsString($this->variant->sku, $content);
        $this->assertStringContainsString('Classic Polo T-Shirt', $content);
    }

    /**
     * Test 9: Seller can download sample CSV template.
     */
    public function test_seller_can_download_sample_csv(): void
    {
        $response = $this->actingAs($this->sellerUser)->get(route('seller.inventory.sample_csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('sku,location_id,quantity_on_hand,low_stock_threshold', $content);
    }
}
