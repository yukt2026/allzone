<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Size / Weight',
                'slug' => 'size',
                'values' => [
                    ['value' => '250g', 'code' => null],
                    ['value' => '500g', 'code' => null],
                    ['value' => '1kg', 'code' => null],
                    ['value' => '2kg', 'code' => null],
                    ['value' => '5kg', 'code' => null],
                    ['value' => '10kg', 'code' => null],
                    ['value' => '250ml', 'code' => null],
                    ['value' => '500ml', 'code' => null],
                    ['value' => '1L', 'code' => null],
                    ['value' => '2L', 'code' => null],
                    ['value' => 'Small (S)', 'code' => null],
                    ['value' => 'Medium (M)', 'code' => null],
                    ['value' => 'Large (L)', 'code' => null],
                    ['value' => 'Extra Large (XL)', 'code' => null],
                    ['value' => 'Double XL (XXL)', 'code' => null],
                ],
            ],
            [
                'name' => 'Color',
                'slug' => 'color',
                'values' => [
                    ['value' => 'Midnight Black', 'code' => '#111827'],
                    ['value' => 'Pearl White', 'code' => '#F9FAFB'],
                    ['value' => 'Navy Blue', 'code' => '#1E3A8A'],
                    ['value' => 'Crimson Red', 'code' => '#DC2626'],
                    ['value' => 'Forest Green', 'code' => '#15803D'],
                    ['value' => 'Space Grey', 'code' => '#4B5563'],
                    ['value' => 'Royal Amber', 'code' => '#D97706'],
                ],
            ],
            [
                'name' => 'Storage Capacity',
                'slug' => 'storage',
                'values' => [
                    ['value' => '64GB', 'code' => null],
                    ['value' => '128GB', 'code' => null],
                    ['value' => '256GB', 'code' => null],
                    ['value' => '512GB', 'code' => null],
                    ['value' => '1TB', 'code' => null],
                ],
            ],
            [
                'name' => 'System RAM',
                'slug' => 'ram',
                'values' => [
                    ['value' => '4GB', 'code' => null],
                    ['value' => '6GB', 'code' => null],
                    ['value' => '8GB', 'code' => null],
                    ['value' => '12GB', 'code' => null],
                    ['value' => '16GB', 'code' => null],
                ],
            ],
            [
                'name' => 'Pack Size',
                'slug' => 'pack',
                'values' => [
                    ['value' => 'Single Unit (1 Pack)', 'code' => null],
                    ['value' => 'Pack of 2', 'code' => null],
                    ['value' => 'Pack of 3', 'code' => null],
                    ['value' => 'Pack of 4', 'code' => null],
                    ['value' => 'Family Pack (5+)', 'code' => null],
                ],
            ],
            [
                'name' => 'Flavour',
                'slug' => 'flavour',
                'values' => [
                    ['value' => 'Classic / Natural', 'code' => null],
                    ['value' => 'Rich Chocolate', 'code' => null],
                    ['value' => 'French Vanilla', 'code' => null],
                    ['value' => 'Wild Strawberry', 'code' => null],
                    ['value' => 'Royal Elaichi', 'code' => null],
                    ['value' => 'Alphonso Mango', 'code' => null],
                ],
            ],
        ];

        foreach ($attributes as $attrData) {
            $attribute = ProductAttribute::firstOrCreate(
                ['slug' => $attrData['slug']],
                ['name' => $attrData['name']]
            );

            foreach ($attrData['values'] as $valData) {
                ProductAttributeValue::firstOrCreate(
                    [
                        'product_attribute_id' => $attribute->id,
                        'value' => $valData['value'],
                    ],
                    [
                        'code' => $valData['code'],
                    ]
                );
            }
        }
    }
}
