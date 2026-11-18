<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CatalogStandardsController extends Controller
{
    /**
     * Display the Catalog Standards & Governance Hub.
     */
    public function index(Request $request): View
    {
        $currentTab = $request->query('tab', 'units');

        $units = $this->getUnits();
        $taxSlabs = $this->getTaxSlabs();
        $specGroups = $this->getSpecGroups();
        $imageRequirements = $this->getImageRequirements();

        return view('admin.catalogue.standards.index', [
            'currentTab' => $currentTab,
            'units' => $units,
            'taxSlabs' => $taxSlabs,
            'specGroups' => $specGroups,
            'imageRequirements' => $imageRequirements,
        ]);
    }

    /**
     * Add or update a Unit of Measure.
     */
    public function storeUnit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:20'],
            'category' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $units = $this->getUnits();
        $code = strtolower(trim($validated['code']));

        $existingIndex = null;
        foreach ($units as $index => $unit) {
            if (strtolower($unit['code']) === $code) {
                $existingIndex = $index;
                break;
            }
        }

        $unitData = [
            'code' => $code,
            'name' => trim($validated['name']),
            'symbol' => trim($validated['symbol']),
            'category' => trim($validated['category']),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($existingIndex !== null) {
            $units[$existingIndex] = $unitData;
        } else {
            $units[] = $unitData;
        }

        Setting::updateOrCreate(
            ['key' => 'catalogue_units'],
            [
                'value' => json_encode($units),
                'type' => 'json',
                'group' => 'catalogue',
                'label' => 'Standard Units of Measure',
                'description' => 'Configured measurement units available for merchant catalogue variations.',
                'is_public' => true,
            ]
        );

        return redirect()
            ->route('admin.catalogue.standards.index', ['tab' => 'units'])
            ->with('success', "Unit '{$unitData['name']}' saved successfully.");
    }

    /**
     * Delete or toggle a Unit of Measure.
     */
    public function deleteUnit(string $code): RedirectResponse
    {
        $units = $this->getUnits();
        $filtered = array_values(array_filter($units, fn($u) => strtolower($u['code']) !== strtolower($code)));

        Setting::updateOrCreate(
            ['key' => 'catalogue_units'],
            [
                'value' => json_encode($filtered),
                'type' => 'json',
                'group' => 'catalogue',
                'label' => 'Standard Units of Measure',
                'is_public' => true,
            ]
        );

        return redirect()
            ->route('admin.catalogue.standards.index', ['tab' => 'units'])
            ->with('success', "Unit '{$code}' removed successfully.");
    }

    /**
     * Update Image Compliance Standards.
     */
    public function updateImageRequirements(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_width' => ['required', 'integer', 'min:400', 'max:4000'],
            'min_height' => ['required', 'integer', 'min:400', 'max:4000'],
            'recommended_ratio' => ['required', 'string', 'max:50'],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:25'],
            'min_images' => ['required', 'integer', 'min:1', 'max:5'],
            'max_images' => ['required', 'integer', 'min:1', 'max:20'],
            'background' => ['required', 'string', 'max:255'],
            'prohibited' => ['required', 'string', 'max:500'],
        ]);

        $validated['allowed_formats'] = ['image/jpeg', 'image/png', 'image/webp'];

        Setting::updateOrCreate(
            ['key' => 'catalogue_image_requirements'],
            [
                'value' => json_encode($validated),
                'type' => 'json',
                'group' => 'catalogue',
                'label' => 'Catalogue Image Requirements',
                'description' => 'Mandatory standards for product images submitted for QC moderation.',
                'is_public' => true,
            ]
        );

        return redirect()
            ->route('admin.catalogue.standards.index', ['tab' => 'images'])
            ->with('success', 'Catalogue image requirements updated successfully.');
    }

    /**
     * Update Tax Configuration Settings.
     */
    public function updateTaxConfiguration(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_tax_slab' => ['required', 'numeric', 'in:0,5,12,18,28'],
            'tax_inclusive_pricing' => ['nullable', 'boolean'],
            'require_hsn_code' => ['nullable', 'boolean'],
            'hsn_min_digits' => ['required', 'integer', 'in:4,6,8'],
        ]);

        $config = [
            'default_tax_slab' => (float) $validated['default_tax_slab'],
            'tax_inclusive_pricing' => $request->boolean('tax_inclusive_pricing', true),
            'require_hsn_code' => $request->boolean('require_hsn_code', true),
            'hsn_min_digits' => (int) $validated['hsn_min_digits'],
        ];

        Setting::updateOrCreate(
            ['key' => 'catalogue_tax_rules'],
            [
                'value' => json_encode($config),
                'type' => 'json',
                'group' => 'catalogue',
                'label' => 'Tax & HSN Compliance Rules',
                'description' => 'GST tax slabs and mandatory HSN validation rules.',
                'is_public' => true,
            ]
        );

        return redirect()
            ->route('admin.catalogue.standards.index', ['tab' => 'taxes'])
            ->with('success', 'Tax compliance rules updated successfully.');
    }

    // =========================================================================
    // PRIVATE DEFAULTS
    // =========================================================================

    /**
     * @return array<int, array{code: string, name: string, symbol: string, category: string, is_active: bool}>
     */
    private function getUnits(): array
    {
        $raw = Setting::where('key', 'catalogue_units')->value('value');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            ['code' => 'pc', 'name' => 'Piece', 'symbol' => 'pc', 'category' => 'Quantity', 'is_active' => true],
            ['code' => 'unit', 'name' => 'Unit', 'symbol' => 'unit', 'category' => 'Quantity', 'is_active' => true],
            ['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg', 'category' => 'Weight', 'is_active' => true],
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g', 'category' => 'Weight', 'is_active' => true],
            ['code' => 'l', 'name' => 'Litre', 'symbol' => 'L', 'category' => 'Volume', 'is_active' => true],
            ['code' => 'ml', 'name' => 'Millilitre', 'symbol' => 'ml', 'category' => 'Volume', 'is_active' => true],
            ['code' => 'm', 'name' => 'Meter', 'symbol' => 'm', 'category' => 'Length', 'is_active' => true],
            ['code' => 'cm', 'name' => 'Centimeter', 'symbol' => 'cm', 'category' => 'Length', 'is_active' => true],
            ['code' => 'box', 'name' => 'Box', 'symbol' => 'box', 'category' => 'Packaging', 'is_active' => true],
            ['code' => 'pack', 'name' => 'Pack', 'symbol' => 'pack', 'category' => 'Packaging', 'is_active' => true],
            ['code' => 'set', 'name' => 'Set', 'symbol' => 'set', 'category' => 'Combo', 'is_active' => true],
            ['code' => 'pair', 'name' => 'Pair', 'symbol' => 'pair', 'category' => 'Apparel & Footwear', 'is_active' => true],
            ['code' => 'dz', 'name' => 'Dozen', 'symbol' => 'dz', 'category' => 'Bulk', 'is_active' => true],
        ];
    }

    /**
     * @return array<int, array{rate: float, name: string, description: string, hsn_example: string}>
     */
    private function getTaxSlabs(): array
    {
        return [
            ['rate' => 0.0, 'name' => 'Nil / Exempt (0%)', 'description' => 'Unbranded grains, fresh vegetables, fresh milk, unbranded organic items', 'hsn_example' => '0101 - 1008'],
            ['rate' => 5.0, 'name' => 'Essential Commodities (5%)', 'description' => 'Packaged food items, apparel under ₹1000, footwear under ₹1000, generic medicines', 'hsn_example' => '0403, 1001, 3004'],
            ['rate' => 12.0, 'name' => 'Standard Low (12%)', 'description' => 'Processed foods, processed cheese, sewing machines, umbrellas, diagnostic kits', 'hsn_example' => '2106, 8452, 9018'],
            ['rate' => 18.0, 'name' => 'Standard High (18%)', 'description' => 'Apparel above ₹1000, footwear above ₹1000, smartphones, consumer electronics, cosmetics', 'hsn_example' => '8517, 6109, 3304'],
            ['rate' => 28.0, 'name' => 'Luxury / Higher Rate (28%)', 'description' => 'Air conditioners, high-end refrigerators, dishwashers, motor vehicles', 'hsn_example' => '8415, 8703'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function getSpecGroups(): array
    {
        return [
            'General Information' => [
                'Model Name', 'Brand', 'Country of Origin', 'Generic Name', 'Manufacturer Name & Address', 'Importer Details', 'Packer Details'
            ],
            'Dimensions & Weight' => [
                'Product Dimensions (LxWxH)', 'Item Weight', 'Package Dimensions', 'Net Quantity', 'Volume / Capacity'
            ],
            'Material & Build' => [
                'Primary Material', 'Finish Type', 'Pattern / Print', 'Color / Shade', 'Closure Type', 'Lining Material'
            ],
            'Technical & Performance' => [
                'Power Consumption', 'Battery Capacity', 'Connectivity Type', 'Compatible Devices', 'Operating Voltage', 'Special Features'
            ],
            'Care, Safety & Warranty' => [
                'Care Instructions', 'Safety Warnings', 'Shelf Life / Expiry', 'Warranty Summary', 'Covered in Warranty', 'Service Type'
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getImageRequirements(): array
    {
        $raw = Setting::where('key', 'catalogue_image_requirements')->value('value');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [
            'min_width' => 800,
            'min_height' => 800,
            'recommended_ratio' => '1:1 (Square)',
            'max_file_size_mb' => 5,
            'allowed_formats' => ['image/jpeg', 'image/png', 'image/webp'],
            'min_images' => 1,
            'max_images' => 8,
            'background' => 'Pure white (#FFFFFF) or clean neutral solid background',
            'prohibited' => 'Watermarks, merchant phone numbers, price tags, promotional stickers, heavy borders, and blurred shots are strictly prohibited.',
        ];
    }
}
