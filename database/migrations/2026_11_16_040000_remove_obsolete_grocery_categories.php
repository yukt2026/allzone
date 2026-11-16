<?php

declare(strict_types=1);

use App\Enums\CommissionRuleScope;
use App\Models\Category;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Obsolete root category IDs from the previous grocery/quick-commerce catalog.
     */
    protected array $obsoleteParentIds = [1, 6, 11, 16, 21, 26, 31, 36, 41, 46, 51, 56];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Decouple historical commission entries pointing to obsolete rules so no FK violation occurs
            $obsoleteRuleIds = CommissionRule::whereIn('category_id', $this->obsoleteParentIds)->pluck('id')->toArray();
            if (!empty($obsoleteRuleIds)) {
                CommissionEntry::whereIn('rule_id', $obsoleteRuleIds)->update(['rule_id' => null]);
                CommissionRule::whereIn('id', $obsoleteRuleIds)->delete();
            }

            // 2. Delete all subcategories of obsolete categories
            Category::whereIn('parent_id', $this->obsoleteParentIds)->delete();

            // 3. Delete obsolete parent categories
            Category::whereIn('id', $this->obsoleteParentIds)->delete();

            // 4. Ensure category commission rules exist for active electronics categories
            $activeElectronicsCategories = Category::whereNull('parent_id')->where('is_active', true)->get();
            foreach ($activeElectronicsCategories as $cat) {
                CommissionRule::updateOrCreate(
                    ['name' => $cat->name . ' Rule'],
                    [
                        'scope' => CommissionRuleScope::CATEGORY,
                        'category_id' => $cat->id,
                        'seller_id' => null,
                        'percent' => $cat->commission_percent ?? 5.00,
                        'fixed_fee' => 0.00,
                        'priority' => 10,
                        'is_active' => true,
                    ]
                );
            }
        });

        // 5. Clear application caches for category tree & storefront navigation
        Cache::forget('storefront.categories_tree');
        Cache::forget('search.global_facets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op as obsolete grocery categories were retired
    }
};
