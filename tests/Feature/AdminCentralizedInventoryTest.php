<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InventoryLocationType;
use App\Enums\SellerStatus;
use App\Enums\StockMovementType;
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

class AdminCentralizedInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
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

        $this->adminUser = User::factory()->create([
            'email' => 'admin.inventory@allzone.in',
            'name' => 'Inventory Administrator',
        ]);
        $this->adminUser->assignRole('admin');

        $this->customerUser = User::factory()->create([
            'email' => 'shopper@allzone.in',
            'name' => 'Regular Shopper',
        ]);
        $this->customerUser->assignRole('customer');

        $this->seller = Seller::factory()->create([
            'business_name' => 'Apex Electronics India',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->store = Store::factory()->create([
            'seller_id' => $this->seller->id,
            'name' => 'Apex Koramangala Hub',
        ]);

        $this->location = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'name' => 'Central Warehouse Bay 4',
            'type' => InventoryLocationType::WAREHOUSE,
            'is_active' => true,
        ]);

        $this->category = Category::factory()->create(['name' => 'Smartphones & Audio']);

        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Wireless ANC Headphones Pro',
        ]);

        $this->variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Midnight Black / 40mm',
            'sku' => 'ANC-HP-BLK-01',
            'mrp' => 4999.00,
            'selling_price' => 3499.00,
        ]);

        $this->inventory = Inventory::create([
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 15,
            'low_stock_threshold' => 20,
            'is_quick_available' => true,
        ]);
    }

    public function test_admin_can_view_centralized_inventory_dashboard_with_all_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/inventory');

        $response->assertStatus(200);
        $response->assertSee('Global Marketplace Inventory');
        $response->assertSee('Total SKUs');
        $response->assertSee('Total Physical Units');
        $response->assertSee('Available Stock');
        $response->assertSee('Cart Locked Units');
        $response->assertSee('Low Stock');
        $response->assertSee('Stocked-Out SKUs');
        $response->assertSee('Discrepancies');

        // Check values
        $response->assertSee('Apex Electronics India');
        $response->assertSee('Wireless ANC Headphones Pro');
        $response->assertSee('ANC-HP-BLK-01');
        $response->assertSee('Midnight Black / 40mm');
        $response->assertSee('Smartphones & Audio');
    }

    public function test_admin_can_filter_inventory_by_category(): void
    {
        $otherCategory = Category::factory()->create(['name' => 'Home Appliances']);
        $otherProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $otherCategory->id,
            'title' => 'Electric Kettle 1.5L',
        ]);
        $otherVariant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'sku' => 'KETTLE-15L',
        ]);
        Inventory::create([
            'variant_id' => $otherVariant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 40,
            'quantity_reserved' => 0,
            'low_stock_threshold' => 5,
        ]);

        // Filter by initial category
        $response = $this->actingAs($this->adminUser)->get("/admin/inventory?category_id={$this->category->id}");
        $response->assertStatus(200);
        $response->assertSee('Wireless ANC Headphones Pro');
        $response->assertDontSee('Electric Kettle 1.5L');

        // Filter by second category
        $responseOther = $this->actingAs($this->adminUser)->get("/admin/inventory?category_id={$otherCategory->id}");
        $responseOther->assertStatus(200);
        $responseOther->assertSee('Electric Kettle 1.5L');
        $responseOther->assertDontSee('Wireless ANC Headphones Pro');
    }

    public function test_admin_can_view_and_filter_stock_movements_ledger(): void
    {
        // Create movement entries
        StockMovement::create([
            'inventory_id' => $this->inventory->id,
            'type' => StockMovementType::RESTOCK,
            'quantity' => 50,
            'note' => 'Supplier factory inward container shipment',
            'created_by' => $this->adminUser->id,
        ]);

        StockMovement::create([
            'inventory_id' => $this->inventory->id,
            'type' => StockMovementType::SALE,
            'quantity' => -2,
            'note' => 'Dispatched order #ALLZONE-1001',
            'created_by' => null,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/inventory?tab=movements');
        $response->assertStatus(200);
        $response->assertSee('Stock Movement Ledger');
        $response->assertSee('Supplier factory inward container shipment');
        $response->assertSee('Dispatched order #ALLZONE-1001');
        $response->assertSee('Inward Stock Restock');
        $response->assertSee('+50');
        $response->assertSee('-2');

        // Filter by movement type 'sale'
        $saleFiltered = $this->actingAs($this->adminUser)->get('/admin/inventory?tab=movements&type=sale');
        $saleFiltered->assertStatus(200);
        $saleFiltered->assertSee('Dispatched order #ALLZONE-1001');
        $saleFiltered->assertDontSee('Supplier factory inward container shipment');
    }

    public function test_admin_can_export_movements_to_csv(): void
    {
        StockMovement::create([
            'inventory_id' => $this->inventory->id,
            'type' => StockMovementType::RESTOCK,
            'quantity' => 10,
            'note' => 'Export test restock entry',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/inventory/movements/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="stock-movements-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_admin_can_view_stock_discrepancies(): void
    {
        // Create an inventory with discrepancy (quantity_on_hand < quantity_reserved)
        $discProduct = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Discrepancy Demo SKU',
        ]);
        $discVariant = ProductVariant::factory()->create([
            'product_id' => $discProduct->id,
            'sku' => 'DISC-SKU-99',
        ]);
        $discInventory = Inventory::create([
            'variant_id' => $discVariant->id,
            'location_id' => $this->location->id,
            'quantity_on_hand' => 2,
            'quantity_reserved' => 10, // 2 < 10 discrepancy!
            'low_stock_threshold' => 5,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/inventory?tab=discrepancies');
        $response->assertStatus(200);
        $response->assertSee('Stock Discrepancy Audit');
        $response->assertSee('Discrepancy Demo SKU');
        $response->assertSee('DISC-SKU-99');
        $response->assertDontSee('Wireless ANC Headphones Pro'); // Healthy inventory not in discrepancy tab
    }

    public function test_admin_can_perform_authorized_manual_stock_adjustment_with_audit_trail(): void
    {
        $this->assertEquals(100, $this->inventory->quantity_on_hand);

        $response = $this->actingAs($this->adminUser)->post("/admin/inventory/{$this->inventory->id}/adjust", [
            'new_quantity_on_hand' => 125,
            'reason' => 'Annual physical count audit: surplus 25 units discovered on shelf 4B',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify inventory updated
        $this->inventory->refresh();
        $this->assertEquals(125, $this->inventory->quantity_on_hand);

        // Verify StockMovement audit log
        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $this->inventory->id,
            'type' => StockMovementType::ADJUSTMENT->value,
            'quantity' => 25, // 125 - 100 = +25 delta
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_manual_stock_adjustment_requires_mandatory_reason(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/admin/inventory/{$this->inventory->id}/adjust", [
            'new_quantity_on_hand' => 50,
            'reason' => '', // Empty reason prohibited
        ]);

        $response->assertSessionHasErrors(['reason']);

        $this->inventory->refresh();
        $this->assertEquals(100, $this->inventory->quantity_on_hand);
    }

    public function test_manual_stock_adjustment_prevents_negative_stock(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/admin/inventory/{$this->inventory->id}/adjust", [
            'new_quantity_on_hand' => -5,
            'reason' => 'Negative inventory attempt',
        ]);

        $response->assertSessionHasErrors(['new_quantity_on_hand']);

        $this->inventory->refresh();
        $this->assertEquals(100, $this->inventory->quantity_on_hand);
    }

    public function test_unauthorized_user_cannot_perform_stock_adjustment(): void
    {
        $response = $this->actingAs($this->customerUser)->post("/admin/inventory/{$this->inventory->id}/adjust", [
            'new_quantity_on_hand' => 50,
            'reason' => 'Unauthorized attempt by customer',
        ]);

        $response->assertStatus(403);

        $this->inventory->refresh();
        $this->assertEquals(100, $this->inventory->quantity_on_hand);
    }
}
