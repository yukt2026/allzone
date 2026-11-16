<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CommissionRuleScope;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Roles and Permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Super Admin User
        $superAdminEmail = env('ADMIN_EMAIL', 'admin@allzone.in');
        $superAdminPassword = env('ADMIN_PASSWORD', 'Password@123');

        $superAdmin = User::firstOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => 'ALLZONE Super Admin',
                'phone' => '+919999900000',
                'password' => Hash::make($superAdminPassword),
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super_admin', 'admin']);

        // 3. Platform Settings
        $settings = [
            [
                'key' => 'default_commission_pct',
                'value' => '10.00',
                'type' => 'float',
                'group' => 'commission',
                'label' => 'Default Platform Commission Rate (%)',
                'description' => 'Platform fee percentage charged on completed marketplace orders',
                'is_public' => false,
            ],
            [
                'key' => 'default_delivery_radius_km',
                'value' => '8.00',
                'type' => 'float',
                'group' => 'logistics',
                'label' => 'Quick Commerce Delivery Radius (km)',
                'description' => 'Maximum distance radius for physical store connect and 10-30 min hyper-local deliveries',
                'is_public' => true,
            ],
            [
                'key' => 'base_delivery_fee',
                'value' => '40.00',
                'type' => 'float',
                'group' => 'logistics',
                'label' => 'Base Delivery Fee',
                'description' => 'Standard starting shipping charge for local deliveries',
                'is_public' => true,
            ],
            [
                'key' => 'free_delivery_threshold',
                'value' => '499.00',
                'type' => 'float',
                'group' => 'logistics',
                'label' => 'Free Delivery Cart Minimum',
                'description' => 'Cart subtotal required to qualify for free shipping',
                'is_public' => true,
            ],
            [
                'key' => 'order_number_prefix',
                'value' => 'OS-',
                'type' => 'string',
                'group' => 'orders',
                'label' => 'Order Code Prefix',
                'description' => 'Prefix assigned to customer order identifiers',
                'is_public' => false,
            ],
            [
                'key' => 'cargo_tracking_prefix',
                'value' => 'OSC-',
                'type' => 'string',
                'group' => 'cargo',
                'label' => 'Cargo Waybill Prefix',
                'description' => 'Tracking number prefix for ALLZONE Cargo logistics consignments',
                'is_public' => true,
            ],
            [
                'key' => 'fulfilment_priority',
                'value' => json_encode([
                    'Nearby Store',
                    'Nearby Seller',
                    'ALLZONE Store',
                    'Regional Warehouse',
                    'National Seller',
                ]),
                'type' => 'json',
                'group' => 'fulfilment',
                'label' => 'Fulfilment Priority List',
                'description' => 'Order in which routing algorithm searches for stock allocation',
                'is_public' => false,
            ],
            [
                'key' => 'currency',
                'value' => 'INR',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Store Currency Code',
                'description' => 'ISO standard 3-character currency code',
                'is_public' => true,
            ],
            [
                'key' => 'currency_symbol',
                'value' => '₹',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Currency Symbol',
                'description' => 'Visual currency glyph',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        // 4. Default Global Commission Rule
        CommissionRule::updateOrCreate(
            ['name' => 'Default Global Commission Rule'],
            [
                'scope' => CommissionRuleScope::GLOBAL,
                'category_id' => null,
                'seller_id' => null,
                'percent' => 10.00,
                'fixed_fee' => 0.00,
                'priority' => 0,
                'is_active' => true,
            ]
        );

        // 5. 8 Curated Electronics & Appliances Categories
        $catalogueData = [
            [
                'name' => 'Refrigerators & Freezers',
                'slug' => 'refrigerators-freezers',
                'image' => 'categories/refrigerators-freezers.svg',
                'commission' => 5.00,
                'quick' => false,
                'meta_title' => 'Refrigerators & Freezers | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Refrigerators & Freezers with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Mobiles & Smartphones',
                'slug' => 'mobiles-smartphones',
                'image' => 'categories/mobiles-smartphones.svg',
                'commission' => 5.00,
                'quick' => true,
                'meta_title' => 'Mobiles & Smartphones | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Mobiles & Smartphones with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Televisions',
                'slug' => 'televisions',
                'image' => 'categories/televisions.svg',
                'commission' => 5.00,
                'quick' => false,
                'meta_title' => 'Televisions | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Televisions with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Air Conditioners',
                'slug' => 'air-conditioners',
                'image' => 'categories/air-conditioners.svg',
                'commission' => 5.00,
                'quick' => false,
                'meta_title' => 'Air Conditioners | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Air Conditioners with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Laptops & Computers',
                'slug' => 'laptops-computers',
                'image' => 'categories/laptops-computers.svg',
                'commission' => 5.00,
                'quick' => false,
                'meta_title' => 'Laptops & Computers | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Laptops & Computers with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Audio & Headphones',
                'slug' => 'audio-headphones',
                'image' => 'categories/audio-headphones.svg',
                'commission' => 8.00,
                'quick' => false,
                'meta_title' => 'Audio & Headphones | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Audio & Headphones with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Home Appliances',
                'slug' => 'home-appliances',
                'image' => 'categories/home-appliances.svg',
                'commission' => 5.00,
                'quick' => false,
                'meta_title' => 'Home Appliances | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Home Appliances with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
            [
                'name' => 'Computer Accessories',
                'slug' => 'computer-accessories',
                'image' => 'categories/computer-accessories.svg',
                'commission' => 8.00,
                'quick' => false,
                'meta_title' => 'Computer Accessories | ALLZONE Electronics & Appliances',
                'meta_description' => 'Shop verified Computer Accessories with brand warranty and fast delivery on ALLZONE.',
                'children' => [],
            ],
        ];

        $sortOrder = 1;
        foreach ($catalogueData as $catItem) {
            $parent = Category::updateOrCreate(
                ['slug' => $catItem['slug']],
                [
                    'name' => $catItem['name'],
                    'parent_id' => null,
                    'image' => $catItem['image'],
                    'commission_percent' => $catItem['commission'],
                    'is_quick_eligible' => $catItem['quick'],
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                    'meta_title' => $catItem['meta_title'] ?? null,
                    'meta_description' => $catItem['meta_description'] ?? null,
                ]
            );

            // Create Category Commission Rule
            CommissionRule::updateOrCreate(
                ['name' => $parent->name . ' Rule'],
                [
                    'scope' => CommissionRuleScope::CATEGORY,
                    'category_id' => $parent->id,
                    'seller_id' => null,
                    'percent' => $catItem['commission'],
                    'fixed_fee' => 0.00,
                    'priority' => 10,
                    'is_active' => true,
                ]
            );

            $childSort = 1;
            foreach ($catItem['children'] as $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'parent_id' => $parent->id,
                        'image' => 'categories/' . Str::slug($childName) . '.jpg',
                        'commission_percent' => $catItem['commission'],
                        'is_quick_eligible' => $catItem['quick'],
                        'is_active' => true,
                        'sort_order' => $childSort++,
                    ]
                );
            }
        }

        // 6. 10 Verified Platform Brands
        $brands = [
            ['name' => 'Amul', 'logo' => 'brands/amul.png'],
            ['name' => 'Tata Sampann', 'logo' => 'brands/tatasampann.png'],
            ['name' => 'Aashirvaad', 'logo' => 'brands/aashirvaad.png'],
            ['name' => 'Fortune', 'logo' => 'brands/fortune.png'],
            ['name' => 'Boat', 'logo' => 'brands/boat.png'],
            ['name' => 'Noise', 'logo' => 'brands/noise.png'],
            ['name' => 'OnePlus', 'logo' => 'brands/oneplus.png'],
            ['name' => 'Samsung', 'logo' => 'brands/samsung.png'],
            ['name' => 'Dettol', 'logo' => 'brands/dettol.png'],
            ['name' => 'Milton', 'logo' => 'brands/milton.png'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'logo' => $brand['logo'],
                    'seller_id' => null,
                    'is_verified' => true,
                ]
            );
        }
    }
}
