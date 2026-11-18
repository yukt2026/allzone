<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\User;
use App\Notifications\ProductApprovedNotification;
use App\Notifications\ProductRejectedNotification;
use App\Notifications\ProductBlockedNotification;
use App\Notifications\ProductCorrectionsRequestedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCatalogueService
{
    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /**
     * Get nested category tree for visual hierarchy & drag-and-drop sort.
     *
     * @return Collection<int, Category>
     */
    public function getCategoryTree(): Collection
    {
        return Category::whereNull('parent_id')
            ->with([
                'children' => function ($q) {
                    $q->withCount('products')->orderBy('sort_order')->orderBy('name')->with([
                        'children' => function ($subQ) {
                            $subQ->withCount('products')->orderBy('sort_order')->orderBy('name');
                        }
                    ]);
                }
            ])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all categories in a flat list for select dropdowns.
     *
     * @return Collection<int, Category>
     */
    public function getAllCategoriesList(): Collection
    {
        return Category::orderBy('name')->get();
    }

    /**
     * Create a category.
     *
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): Category
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Unique slug fallback
        $baseSlug = $data['slug'];
        $counter = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $data['parent_id'] = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $data['commission_percent'] = (isset($data['commission_percent']) && $data['commission_percent'] !== '') ? (float) $data['commission_percent'] : null;
        $data['is_active'] = !empty($data['is_active']);
        $data['is_quick_eligible'] = !empty($data['is_quick_eligible']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return Category::create($data);
    }

    /**
     * Update an existing category.
     *
     * @param array<string, mixed> $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Prevent self-parenting loop
        if (!empty($data['parent_id']) && (int) $data['parent_id'] === (int) $category->id) {
            $data['parent_id'] = null;
        } else {
            $data['parent_id'] = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        }

        $data['commission_percent'] = (isset($data['commission_percent']) && $data['commission_percent'] !== '') ? (float) $data['commission_percent'] : null;
        $data['is_active'] = !empty($data['is_active']);
        $data['is_quick_eligible'] = !empty($data['is_quick_eligible']);

        $category->update($data);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(Category $category): void
    {
        // Re-assign direct children to root so tree doesn't break
        $category->children()->update(['parent_id' => $category->parent_id]);
        $category->delete();
    }

    /**
     * Reorder categories via drag-and-drop sortable payload.
     *
     * @param array<int, array{id: int, sort_order: int, parent_id: ?int}> $reorderList
     */
    public function reorderCategories(array $reorderList): void
    {
        DB::transaction(function () use ($reorderList) {
            foreach ($reorderList as $item) {
                Category::where('id', $item['id'])->update([
                    'sort_order' => (int) $item['sort_order'],
                    'parent_id' => !empty($item['parent_id']) ? (int) $item['parent_id'] : null,
                ]);
            }
        });
    }

    // =========================================================================
    // BRANDS
    // =========================================================================

    /**
     * Get paginated filtered brands list.
     *
     * @param array<string, mixed> $filters
     */
    public function getFilteredBrands(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Brand::query()->with('seller')->withCount('products');

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        if (isset($filters['verified']) && $filters['verified'] !== '') {
            $query->where('is_verified', (bool) $filters['verified']);
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Create brand.
     *
     * @param array<string, mixed> $data
     */
    public function createBrand(array $data): Brand
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $baseSlug = $data['slug'];
        $counter = 1;
        while (Brand::where('slug', $data['slug'])->exists()) {
            $data['slug'] = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $data['seller_id'] = !empty($data['seller_id']) ? (int) $data['seller_id'] : null;
        $data['is_verified'] = !empty($data['is_verified']);

        return Brand::create($data);
    }

    /**
     * Update brand.
     *
     * @param array<string, mixed> $data
     */
    public function updateBrand(Brand $brand, array $data): Brand
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['seller_id'] = !empty($data['seller_id']) ? (int) $data['seller_id'] : null;
        $data['is_verified'] = !empty($data['is_verified']);
        $brand->update($data);

        return $brand;
    }

    /**
     * Delete brand.
     */
    public function deleteBrand(Brand $brand): void
    {
        $brand->delete();
    }

    /**
     * Toggle brand verification badge.
     */
    public function toggleBrandVerification(Brand $brand): bool
    {
        $brand->update(['is_verified' => !$brand->is_verified]);

        return $brand->is_verified;
    }

    // =========================================================================
    // ATTRIBUTES & VALUES
    // =========================================================================

    /**
     * Get all attributes with their values.
     *
     * @return Collection<int, ProductAttribute>
     */
    public function getAttributesWithValues(): Collection
    {
        return ProductAttribute::with('values')->withCount('values')->latest('id')->get();
    }

    /**
     * Create attribute.
     *
     * @param array<string, mixed> $data
     */
    public function createAttribute(array $data): ProductAttribute
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return ProductAttribute::create($data);
    }

    /**
     * Update attribute.
     *
     * @param array<string, mixed> $data
     */
    public function updateAttribute(ProductAttribute $attribute, array $data): ProductAttribute
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $attribute->update($data);

        return $attribute;
    }

    /**
     * Delete attribute.
     */
    public function deleteAttribute(ProductAttribute $attribute): void
    {
        $attribute->delete();
    }

    /**
     * Add an attribute value (e.g. "XL", "500g", "Crimson Red" with code "#DC2626").
     *
     * @param array<string, mixed> $data
     */
    public function addAttributeValue(ProductAttribute $attribute, array $data): ProductAttributeValue
    {
        return $attribute->values()->create([
            'value' => trim((string) $data['value']),
            'code' => !empty($data['code']) ? trim((string) $data['code']) : null,
        ]);
    }

    /**
     * Delete attribute value.
     */
    public function deleteAttributeValue(ProductAttributeValue $value): void
    {
        $value->delete();
    }

    // =========================================================================
    // PRODUCT MODERATION QUEUE
    // =========================================================================

    /**
     * Build filtered moderation queue query.
     *
     * @param array<string, mixed> $filters
     */
    public function buildModerationQuery(array $filters = []): Builder
    {
        $query = Product::query()
            ->with(['seller.user', 'category', 'brand', 'variants', 'media']);

        // Status filter: support tabs 'all', 'pending_review', 'approved'/'active', 'rejected', 'blocked', 'inactive'
        $status = $filters['status'] ?? 'pending_review';
        if ($status !== 'all' && $status !== '') {
            if ($status === 'approved' || $status === 'active') {
                $query->where('status', ProductStatus::ACTIVE);
            } elseif ($status === 'inactive') {
                $query->whereIn('status', [ProductStatus::INACTIVE, ProductStatus::DRAFT]);
            } else {
                $query->where('status', $status);
            }
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('hsn_code', 'like', "%{$term}%")
                    ->orWhere('id', $term)
                    ->orWhereHas('seller', function (Builder $sSub) use ($term) {
                        $sSub->where('business_name', 'like', "%{$term}%")
                             ->orWhere('legal_name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('variants', function (Builder $vSub) use ($term) {
                        $vSub->where('sku', 'like', "%{$term}%")
                             ->orWhere('barcode', 'like', "%{$term}%");
                    });
            });
        }

        if (!empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            // Include subcategory IDs under this category
            $childCatIds = Category::where('parent_id', $catId)->pluck('id')->all();
            $allCatIds = array_merge([$catId], $childCatIds);
            $query->whereIn('category_id', $allCatIds);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', (int) $filters['seller_id']);
        }

        if (isset($filters['is_quick_capable']) && $filters['is_quick_capable'] !== '') {
            $query->where('is_quick_capable', (bool) $filters['is_quick_capable']);
        }

        if (!empty($filters['min_price'])) {
            $minPrice = (float) $filters['min_price'];
            $query->whereHas('variants', function (Builder $v) use ($minPrice) {
                $v->where('selling_price', '>=', $minPrice);
            });
        }

        if (!empty($filters['max_price'])) {
            $maxPrice = (float) $filters['max_price'];
            $query->whereHas('variants', function (Builder $v) use ($maxPrice) {
                $v->where('selling_price', '<=', $maxPrice);
            });
        }

        // Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            default => $query->latest('id'),
        };

        return $query;
    }

    /**
     * Get paginated moderation queue products.
     *
     * @param array<string, mixed> $filters
     */
    public function getModerationQueue(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildModerationQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * Get moderation queue counters across all required status tabs.
     *
     * @return array<string, int>
     */
    public function getModerationStats(): array
    {
        return [
            'all_count' => Product::count(),
            'pending_count' => Product::where('status', ProductStatus::PENDING_REVIEW)->count(),
            'approved_count' => Product::where('status', ProductStatus::ACTIVE)->count(),
            'rejected_count' => Product::where('status', ProductStatus::REJECTED)->count(),
            'blocked_count' => Product::where('status', ProductStatus::BLOCKED)->count(),
            'inactive_count' => Product::whereIn('status', [ProductStatus::INACTIVE, ProductStatus::DRAFT])->count(),
            'approved_today' => Product::where('status', ProductStatus::ACTIVE)
                ->whereDate('updated_at', today())
                ->count(),
            'total_active' => Product::where('status', ProductStatus::ACTIVE)->count(),
        ];
    }

    /**
     * Approve product in moderation queue.
     */
    public function approveProduct(Product $product, User $admin): void
    {
        $product->loadMissing('seller.user');
        DB::transaction(function () use ($product, $admin) {
            $oldStatus = $product->status->value;

            $product->update([
                'status' => ProductStatus::ACTIVE,
                'rejection_reason' => null,
            ]);

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'approve_product',
                    'old' => ['status' => $oldStatus],
                    'new' => ['status' => ProductStatus::ACTIVE->value],
                ])
                ->log("Approved product #{$product->id} ('{$product->title}') into live catalogue.");

            // Notify seller
            if ($product->seller?->user) {
                $product->seller->user->notify(new ProductApprovedNotification($product));
            }
        });
    }

    /**
     * Reject product with mandatory reason.
     */
    public function rejectProduct(Product $product, string $reason, User $admin): void
    {
        $product->loadMissing('seller.user');
        DB::transaction(function () use ($product, $reason, $admin) {
            $oldStatus = $product->status->value;

            $product->update([
                'status' => ProductStatus::REJECTED,
                'rejection_reason' => $reason,
            ]);

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'reject_product',
                    'reason' => $reason,
                    'old' => ['status' => $oldStatus],
                    'new' => ['status' => ProductStatus::REJECTED->value, 'rejection_reason' => $reason],
                ])
                ->log("Rejected product #{$product->id} ('{$product->title}'). Reason: {$reason}");

            // Notify seller
            if ($product->seller?->user) {
                $product->seller->user->notify(new ProductRejectedNotification($product, $reason));
            }
        });
    }

    /**
     * Block product listing according to platform compliance policy.
     */
    public function blockProduct(Product $product, string $policyReason, User $admin): void
    {
        $product->loadMissing('seller.user');
        DB::transaction(function () use ($product, $policyReason, $admin) {
            $oldStatus = $product->status->value;

            $product->update([
                'status' => ProductStatus::BLOCKED,
                'rejection_reason' => "Policy Violation: {$policyReason}",
            ]);

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'block_product',
                    'reason' => $policyReason,
                    'old' => ['status' => $oldStatus],
                    'new' => ['status' => ProductStatus::BLOCKED->value, 'rejection_reason' => "Policy Violation: {$policyReason}"],
                ])
                ->log("Blocked product #{$product->id} ('{$product->title}') due to policy violation: {$policyReason}");

            // Notify seller
            if ($product->seller?->user) {
                $product->seller->user->notify(new ProductBlockedNotification($product, $policyReason));
            }
        });
    }

    /**
     * Request specific corrections from seller for product listing.
     */
    public function requestCorrections(Product $product, string $notes, User $admin): void
    {
        $product->loadMissing('seller.user');
        DB::transaction(function () use ($product, $notes, $admin) {
            $product->update([
                'status' => ProductStatus::PENDING_REVIEW,
                'rejection_reason' => "Corrections Requested: {$notes}",
            ]);

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'request_corrections',
                    'notes' => $notes,
                    'status' => ProductStatus::PENDING_REVIEW->value,
                ])
                ->log("Requested corrections on product #{$product->id} ('{$product->title}'): {$notes}");

            // Notify seller
            if ($product->seller?->user) {
                $product->seller->user->notify(new ProductCorrectionsRequestedNotification($product, $notes));
            }
        });
    }

    /**
     * Bulk update status for multiple products.
     *
     * @param array<int> $productIds
     */
    public function bulkUpdateStatus(array $productIds, ProductStatus $newStatus, ?string $reason, User $admin): int
    {
        $products = Product::whereIn('id', $productIds)->with('seller.user')->get();
        $count = 0;

        foreach ($products as $product) {
            if ($newStatus === ProductStatus::ACTIVE) {
                $this->approveProduct($product, $admin);
            } elseif ($newStatus === ProductStatus::REJECTED) {
                $this->rejectProduct($product, $reason ?: 'Rejected in bulk administrative review.', $admin);
            } elseif ($newStatus === ProductStatus::BLOCKED) {
                $this->blockProduct($product, $reason ?: 'Blocked in bulk compliance action.', $admin);
            } else {
                $oldStatus = $product->status->value;
                $product->update([
                    'status' => $newStatus,
                    'rejection_reason' => $reason,
                ]);

                activity()
                    ->performedOn($product)
                    ->causedBy($admin)
                    ->withProperties([
                        'action' => 'bulk_status_update',
                        'old' => ['status' => $oldStatus],
                        'new' => ['status' => $newStatus->value],
                    ])
                    ->log("Updated product #{$product->id} status to {$newStatus->value} in bulk operation.");
            }
            $count++;
        }

        return $count;
    }

    /**
     * Bulk reassign category for multiple products.
     *
     * @param array<int> $productIds
     */
    public function bulkAssignCategory(array $productIds, int $categoryId, User $admin): int
    {
        $category = Category::findOrFail($categoryId);
        $products = Product::whereIn('id', $productIds)->get();
        $count = 0;

        foreach ($products as $product) {
            $oldCatId = $product->category_id;
            $product->update(['category_id' => $categoryId]);

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'bulk_category_assign',
                    'old' => ['category_id' => $oldCatId],
                    'new' => ['category_id' => $categoryId, 'category_name' => $category->name],
                ])
                ->log("Reassigned product #{$product->id} to category '{$category->name}' in bulk operation.");

            $count++;
        }

        return $count;
    }

    /**
     * Stream filtered products as an authorized CSV report.
     *
     * @param array<string, mixed> $filters
     */
    public function exportProductsCsv(array $filters = []): StreamedResponse
    {
        $query = $this->buildModerationQuery($filters);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="allzone_products_' . now()->format('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Product ID',
                'Title',
                'SKU (Default)',
                'Seller Store',
                'Owner Name',
                'Category',
                'Brand',
                'Status',
                'Selling Price (INR)',
                'MRP (INR)',
                'Cost Price (INR)',
                'HSN Code',
                'GST Tax %',
                'Is Returnable',
                'Return Days',
                'Quick Commerce',
                'Rejection/Block Reason',
                'Created At',
            ]);

            $query->chunk(200, function ($products) use ($handle) {
                foreach ($products as $product) {
                    $defaultVar = $product->variants->first();
                    fputcsv($handle, [
                        $product->id,
                        $product->title,
                        $defaultVar?->sku ?? 'N/A',
                        $product->seller?->business_name ?? 'N/A',
                        $product->seller?->user?->name ?? 'N/A',
                        $product->category?->name ?? 'Uncategorized',
                        $product->brand?->name ?? 'No Brand',
                        $product->status->value,
                        number_format((float) ($defaultVar?->selling_price ?? 0), 2, '.', ''),
                        number_format((float) ($defaultVar?->mrp ?? 0), 2, '.', ''),
                        number_format((float) ($defaultVar?->cost_price ?? 0), 2, '.', ''),
                        $product->hsn_code ?? '',
                        number_format((float) $product->tax_percent, 2, '.', ''),
                        $product->is_returnable ? 'YES' : 'NO',
                        $product->return_window_days,
                        $product->is_quick_capable ? 'YES' : 'NO',
                        $product->rejection_reason ?? '',
                        $product->created_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk approve multiple products.
     *
     * @param array<int> $productIds
     */
    public function bulkApproveProducts(array $productIds, User $admin): int
    {
        $products = Product::whereIn('id', $productIds)
            ->where('status', '!=', ProductStatus::ACTIVE)
            ->with('seller.user')
            ->get();

        $count = 0;
        foreach ($products as $product) {
            $this->approveProduct($product, $admin);
            $count++;
        }

        return $count;
    }

    /**
     * Edit product details on behalf of the merchant.
     *
     * @param array<string, mixed> $data
     */
    public function updateProductOnBehalf(Product $product, array $data, User $admin): Product
    {
        return DB::transaction(function () use ($product, $data, $admin) {
            $oldAttributes = $product->only(['title', 'category_id', 'brand_id', 'hsn_code', 'tax_percent']);

            $product->update([
                'title' => $data['title'],
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'short_desc' => $data['short_desc'] ?? null,
                'description' => $data['description'] ?? null,
                'hsn_code' => $data['hsn_code'] ?? null,
                'tax_percent' => (float) ($data['tax_percent'] ?? 0.00),
                'is_quick_capable' => !empty($data['is_quick_capable']),
                'is_cod_allowed' => !empty($data['is_cod_allowed']),
                'is_returnable' => !empty($data['is_returnable']),
                'return_window_days' => (int) ($data['return_window_days'] ?? 7),
            ]);

            // Update default variant pricing if provided
            if (isset($data['mrp']) || isset($data['selling_price'])) {
                $defaultVariant = $product->defaultVariant ?: $product->variants()->first();
                if ($defaultVariant) {
                    $defaultVariant->update([
                        'mrp' => isset($data['mrp']) ? (float) $data['mrp'] : $defaultVariant->mrp,
                        'selling_price' => isset($data['selling_price']) ? (float) $data['selling_price'] : $defaultVariant->selling_price,
                    ]);
                }
            }

            activity()
                ->performedOn($product)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'edit_on_behalf',
                    'old' => $oldAttributes,
                    'new' => $product->only(['title', 'category_id', 'brand_id', 'hsn_code', 'tax_percent']),
                ])
                ->log("Admin edited product #{$product->id} on behalf of seller #{$product->seller_id}.");

            return $product;
        });
    }
}
