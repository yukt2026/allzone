<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Enums\SellerType;
use App\Enums\StoreStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFilterSortingPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected Seller $seller;
    protected Store $store;
    protected InventoryLocation $invLocation;
    protected Brand $sony;
    protected Brand $samsung;
    protected Brand $apple;
    protected Category $electronics;
    protected Category $smartphones;
    protected Category $audio;
    protected Category $televisions;

    protected function setUp(): void
    {
        parent::setUp();

        $sellerUser = User::factory()->create(['email' => 'techseller@allzone.in']);
        $this->seller = Seller::create([
            'user_id' => $sellerUser->id,
            'business_name' => 'Allzone Electronics Hub',
            'legal_name' => 'Allzone Electronics India Pvt Ltd',
            'seller_type' => SellerType::MARKETPLACE,
            'status' => 'approved',
        ]);

        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'Allzone Electronics Mega Store',
            'slug' => 'allzone-electronics-mega-store',
            'status' => StoreStatus::ACTIVE,
            'is_onestall_owned' => true,
            'accepts_quick_commerce' => true,
            'accepts_pickup' => true,
            'rating_avg' => 4.9,
            'delivery_radius_km' => 20.0,
        ]);

        StoreLocation::create([
            'store_id' => $this->store->id,
            'address' => 'MG Road, Indiranagar',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'lat' => 12.9716,
            'lng' => 77.5946,
        ]);

        $this->invLocation = InventoryLocation::create([
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'name' => 'Bengaluru Central Electronics Hub',
            'code' => 'BLR-CENTRAL-01',
            'type' => 'warehouse',
            'is_active' => true,
        ]);

        // Brands
        $this->sony = Brand::create(['name' => 'Sony', 'slug' => 'sony', 'is_verified' => true, 'is_active' => true]);
        $this->samsung = Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'is_verified' => true, 'is_active' => true]);
        $this->apple = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'is_verified' => true, 'is_active' => true]);

        // Categories
        $this->electronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $this->smartphones = Category::create(['name' => 'Smartphones', 'slug' => 'smartphones', 'parent_id' => $this->electronics->id, 'is_active' => true]);
        $this->audio = Category::create(['name' => 'Audio & Headphones', 'slug' => 'audio-headphones', 'parent_id' => $this->electronics->id, 'is_active' => true]);
        $this->televisions = Category::create(['name' => 'Televisions', 'slug' => 'televisions', 'parent_id' => $this->electronics->id, 'is_active' => true]);

        // Seed 25 Products to test pagination (> 16 items per page)
        $this->seedCatalogue();
    }

    protected function seedCatalogue(): void
    {
        $productsData = [
            // Sony Products
            [
                'title' => 'Sony WH-1000XM4 Wireless Noise Cancelling Headphones',
                'brand_id' => $this->sony->id,
                'category_id' => $this->audio->id,
                'sku' => 'AZ-SONY-XM4',
                'model' => 'WH-1000XM4',
                'price' => 19990.0,
                'mrp' => 29990.0,
                'stock' => 15,
                'quick' => true,
                'rating' => 5,
            ],
            [
                'title' => 'Sony Bravia 55 Inch 4K Ultra HD Smart Google TV',
                'brand_id' => $this->sony->id,
                'category_id' => $this->televisions->id,
                'sku' => 'AZ-SONY-TV55',
                'model' => 'KD-55X74L',
                'price' => 54990.0,
                'mrp' => 84990.0,
                'stock' => 8,
                'quick' => false,
                'rating' => 5,
            ],
            [
                'title' => 'Sony WF-C500 Truly Wireless Bluetooth Earbuds (Black)',
                'brand_id' => $this->sony->id,
                'category_id' => $this->audio->id,
                'sku' => 'AZ-SONY-WFC500',
                'model' => 'WF-C500',
                'price' => 4490.0,
                'mrp' => 8990.0,
                'stock' => 20,
                'quick' => true,
                'rating' => 4,
            ],
            // Samsung Products
            [
                'title' => 'Samsung Galaxy S24 Ultra 5G Smartphone',
                'brand_id' => $this->samsung->id,
                'category_id' => $this->smartphones->id,
                'sku' => 'AZ-SAM-S24U',
                'model' => 'SM-S928B',
                'price' => 119999.0,
                'mrp' => 134999.0,
                'stock' => 10,
                'quick' => true,
                'rating' => 5,
            ],
            [
                'title' => 'Samsung 43 Inch Crystal 4K Vivid Pro Smart LED TV',
                'brand_id' => $this->samsung->id,
                'category_id' => $this->televisions->id,
                'sku' => 'AZ-SAM-TV43',
                'model' => 'UA43DUE70B',
                'price' => 28990.0,
                'mrp' => 44900.0,
                'stock' => 12,
                'quick' => false,
                'rating' => 4,
            ],
            [
                'title' => 'Samsung Galaxy Buds2 Pro Active Noise Cancelling',
                'brand_id' => $this->samsung->id,
                'category_id' => $this->audio->id,
                'sku' => 'AZ-SAM-BUDS2',
                'model' => 'SM-R510',
                'price' => 8990.0,
                'mrp' => 17990.0,
                'stock' => 0, // OUT OF STOCK
                'quick' => true,
                'rating' => 3,
            ],
            // Apple Products
            [
                'title' => 'Apple iPhone 15 Pro Max (256 GB) - Natural Titanium',
                'brand_id' => $this->apple->id,
                'category_id' => $this->smartphones->id,
                'sku' => 'AZ-APL-IP15PM',
                'model' => 'MU773HN/A',
                'price' => 144990.0,
                'mrp' => 159900.0,
                'stock' => 5,
                'quick' => true,
                'rating' => 5,
            ],
            [
                'title' => 'Apple AirPods Pro (2nd Generation) with MagSafe USB-C',
                'brand_id' => $this->apple->id,
                'category_id' => $this->audio->id,
                'sku' => 'AZ-APL-APP2',
                'model' => 'MTJV3HN/A',
                'price' => 20990.0,
                'mrp' => 24900.0,
                'stock' => 15,
                'quick' => true,
                'rating' => 5,
            ],
        ];

        // Add 17 additional filler electronics products to total 25 products
        for ($i = 9; $i <= 25; $i++) {
            $brand = ($i % 2 === 0) ? $this->samsung : $this->sony;
            $cat = ($i % 3 === 0) ? $this->televisions : $this->audio;
            $productsData[] = [
                'title' => "Electronics Gadget Accessory Model {$i}",
                'brand_id' => $brand->id,
                'category_id' => $cat->id,
                'sku' => "AZ-SKU-" . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'model' => "MOD-{$i}",
                'price' => 1000.0 + ($i * 500.0),
                'mrp' => 2000.0 + ($i * 700.0),
                'stock' => $i % 4 === 0 ? 0 : 10,
                'quick' => ($i % 2 === 0),
                'rating' => ($i % 5) + 1,
            ];
        }

        $user = User::first();

        foreach ($productsData as $data) {
            $product = Product::create([
                'seller_id' => $this->seller->id,
                'brand_id' => $data['brand_id'],
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => \Illuminate\Support\Str::slug($data['title']),
                'type' => ProductType::SIMPLE,
                'status' => ProductStatus::ACTIVE,
                'is_featured' => true,
                'is_quick_capable' => $data['quick'],
                'short_desc' => 'High quality electronics gadget certified authentic.',
                'description' => 'Comprehensive technical features and specifications.',
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => 'Standard Edition',
                'title' => 'Standard Edition',
                'sku' => $data['sku'],
                'selling_price' => $data['price'],
                'mrp' => $data['mrp'],
                'cost_price' => $data['price'] * 0.8,
                'is_default' => true,
                'is_active' => true,
            ]);

            Inventory::create([
                'variant_id' => $variant->id,
                'location_id' => $this->invLocation->id,
                'quantity_on_hand' => $data['stock'],
                'quantity_reserved' => 0,
            ]);

            ProductSpecification::create([
                'product_id' => $product->id,
                'group' => 'Technical Specifications',
                'key' => 'Model Number',
                'value' => $data['model'],
            ]);

            if ($data['rating'] > 0) {
                Review::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'rating' => $data['rating'],
                    'title' => 'Great product quality',
                    'body' => 'Works exceptionally well, highly recommended.',
                    'status' => ReviewStatus::APPROVED,
                ]);
            }
        }
    }

    /**
     * Test 1: Search Matrix (Complete name, partial name, brand, model, SKU, case insensitivity, special characters).
     */
    public function test_search_functionality_across_scenarios(): void
    {
        // 1. Full product name
        $response = $this->get('/search?q=' . urlencode('Sony WH-1000XM4 Wireless Noise Cancelling Headphones'));
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM4 Wireless Noise Cancelling Headphones');

        // 2. Partial product name
        $response = $this->get('/search?q=' . urlencode('Bravia 55'));
        $response->assertStatus(200);
        $response->assertSee('Sony Bravia 55 Inch');

        // 3. Search by Brand
        $response = $this->get('/search?q=Apple');
        $response->assertStatus(200);
        $response->assertSee('Apple iPhone 15 Pro Max');
        $response->assertSee('Apple AirPods Pro');

        // 4. Search by Model Number (Specification)
        $response = $this->get('/search?q=SM-S928B');
        $response->assertStatus(200);
        $response->assertSee('Samsung Galaxy S24 Ultra');

        // 5. Search by Variant SKU
        $response = $this->get('/search?q=AZ-SONY-XM4');
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM4');

        // 6. Case Insensitivity (UPPERCASE and lowercase)
        $respUpper = $this->get('/search?q=SONY');
        $respLower = $this->get('/search?q=sony');
        $respUpper->assertStatus(200);
        $respLower->assertStatus(200);
        $respUpper->assertSee('Sony WH-1000XM4');
        $respLower->assertSee('Sony WH-1000XM4');

        // 7. Extra whitespace handling
        $response = $this->get('/search?q=' . urlencode('   Samsung     Galaxy    '));
        $response->assertStatus(200);
        $response->assertSee('Samsung Galaxy S24 Ultra');

        // 8. Special characters & MySQL Full-text Boolean operators (+, -, *, (), "", @)
        // Must execute gracefully without 500 error!
        $response = $this->get('/search?q=' . urlencode('Sony (Black) + Earbuds - 50% * "ANC" @ test'));
        $response->assertStatus(200);

        // 9. Non-matching query
        $response = $this->get('/search?q=NonExistentGadgetXYZ999');
        $response->assertStatus(200);
        $response->assertSee('No products match your search');

        // 10. Empty search query
        $response = $this->get('/search?q=');
        $response->assertStatus(200);
        $response->assertSee('Explore');
    }

    /**
     * Test 2: Live Search Suggestions Autocomplete Endpoint.
     */
    public function test_search_suggestions_autocomplete(): void
    {
        $response = $this->getJson('/api/storefront/search/suggestions?q=Sony');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'type',
            'results' => [
                'products',
                'brands',
            ],
        ]);
        $data = $response->json('results.products');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('Sony', $data[0]['title']);
    }

    /**
     * Test 3: Filters (Category, Brand, Price Range, In-Stock, Delivery Speed, Combined).
     */
    public function test_filter_functionality(): void
    {
        // 1. Category filter (descendants included: Electronics -> Audio)
        $response = $this->get('/search?category=audio-headphones');
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM4');

        // 2. Brand filter
        $response = $this->get('/search?brands[]=' . $this->apple->slug);
        $response->assertStatus(200);
        $response->assertSee('Apple iPhone 15 Pro Max');
        $response->assertDontSee('Samsung Galaxy S24 Ultra');

        // 3. Price range filter (Min & Max)
        $response = $this->get('/search?price_min=100000&price_max=150000');
        $response->assertStatus(200);
        $response->assertSee('Samsung Galaxy S24 Ultra');
        $response->assertSee('Apple iPhone 15 Pro Max');
        $response->assertDontSee('Sony WF-C500');

        // 4. In-Stock filter (excludes 0 quantity stock)
        $response = $this->get('/search?in_stock=1&brands[]=' . $this->samsung->slug);
        $response->assertStatus(200);
        $response->assertSee('Samsung Galaxy S24 Ultra');
        $response->assertDontSee('Samsung Galaxy Buds2 Pro'); // stock = 0

        // 5. Combined filters: Brand + Category + In Stock
        $response = $this->get('/search?brands[]=' . $this->sony->slug . '&category=audio-headphones&in_stock=1');
        $response->assertStatus(200);
        $response->assertSee('Sony WH-1000XM4');
        $response->assertDontSee('Sony Bravia 55 Inch'); // Television, not Audio

        // 6. Filter chips & Reset All link rendered
        $response = $this->get('/search?brands[]=' . $this->apple->slug . '&in_stock=1');
        $response->assertStatus(200);
        $response->assertSee('Reset All');
    }

    /**
     * Test 4: Sorting Functionality (Relevance, Price ASC, Price DESC, Discount, Rating, Newest).
     */
    public function test_sorting_functionality(): void
    {
        // 1. Price Low to High
        $response = $this->get('/search?sort=price_asc');
        $response->assertStatus(200);

        // 2. Price High to Low
        $response = $this->get('/search?sort=price_desc');
        $response->assertStatus(200);
        // Top product should be iPhone 15 Pro Max (144,990) or S24 Ultra (119,999)
        $response->assertSee('Apple iPhone 15 Pro Max');

        // 3. Customer Rating Descending
        $response = $this->get('/search?sort=rating_desc');
        $response->assertStatus(200);

        // 4. Top Discount
        $response = $this->get('/search?sort=discount_desc');
        $response->assertStatus(200);

        // 5. Newest
        $response = $this->get('/search?sort=newest');
        $response->assertStatus(200);
    }

    /**
     * Test 5: Pagination Functionality (Count, Per-Page Limit, Parameter Preservation across Pages).
     */
    public function test_pagination_and_query_preservation(): void
    {
        // 25 total products seeded. Search per-page is 16.
        // Page 1 should have 16 products, Page 2 should have 9 products.
        $responsePage1 = $this->get('/search?page=1');
        $responsePage1->assertStatus(200);
        $responsePage1->assertSee('Showing 25 results');

        $responsePage2 = $this->get('/search?page=2');
        $responsePage2->assertStatus(200);

        // Query parameters (q, sort, brands) MUST be preserved in pagination links
        $responseWithParams = $this->get('/search?q=Electronics&sort=price_asc&brands[]=' . $this->sony->slug . '&page=1');
        $responseWithParams->assertStatus(200);
        $responseWithParams->assertSee('brands%5B0%5D=' . $this->sony->slug);
        $responseWithParams->assertSee('sort=price_asc');
    }

    /**
     * Test 6: API Products Endpoint (/api/v1/products).
     */
    public function test_api_v1_products_search_filter_sort_and_pagination(): void
    {
        // Search & Pagination in API
        $response = $this->getJson('/api/v1/products?q=Sony&per_page=5');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ],
        ]);

        // Price Filter in API
        $priceResp = $this->getJson('/api/v1/products?min_price=100000&max_price=160000');
        $priceResp->assertStatus(200);
        $items = $priceResp->json('data.items');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(100000, $item['price']);
            $this->assertLessThanOrEqual(160000, $item['price']);
        }

        // Sorting in API
        $sortResp = $this->getJson('/api/v1/products?sort=price_low');
        $sortResp->assertStatus(200);
        $sortItems = $sortResp->json('data.items');
        $this->assertGreaterThan(0, count($sortItems));
    }
}
