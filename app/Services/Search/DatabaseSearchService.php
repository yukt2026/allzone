<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Enums\ProductStatus;
use App\Enums\StoreStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Seller;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseSearchService implements SearchServiceInterface
{
    /**
     * Search products with comprehensive filters, location-aware ranking and pagination.
     */
    public function searchProducts(
        string $query = '',
        array $filters = [],
        string $sort = 'relevance',
        array $location = [],
        int $perPage = 16
    ): LengthAwarePaginator {
        $q = trim($query);

        $productQuery = Product::active()
            ->with([
                'media',
                'defaultVariant',
                'variants',
                'brand',
                'category',
                'seller',
            ]);

        // 1. Text Search with Sanitized Fulltext, Trigram Token, SKU, & Specification Fallback
        if ($q !== '') {
            $productQuery->where(function (Builder $builder) use ($q) {
                // Strip characters that have special boolean operator semantics in MySQL Boolean Mode
                $sanitized = preg_replace('/[+\-><()~*\"@\\\\]+/', ' ', $q);
                $words = array_filter(preg_split('/\s+/', trim((string) $sanitized)), fn ($w) => mb_strlen($w) >= 2);

                if (DB::connection()->getDriverName() === 'mysql' && !empty($words)) {
                    $booleanQuery = implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));
                    $builder->whereRaw('MATCH(products.title, products.short_desc) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
                } else {
                    $builder->where('products.title', 'like', "%{$q}%")
                        ->orWhere('products.short_desc', 'like', "%{$q}%")
                        ->orWhere('products.description', 'like', "%{$q}%");
                }

                // Match in Brand Name
                $builder->orWhereHas('brand', function ($b) use ($q) {
                    $b->where('name', 'like', "%{$q}%");
                });

                // Match in Category Name
                $builder->orWhereHas('category', function ($c) use ($q) {
                    $c->where('name', 'like', "%{$q}%");
                });

                // Match in Variant SKU & Title (e.g. AZ-SKU-0008, 128GB Black)
                $builder->orWhereHas('variants', function ($v) use ($q) {
                    $v->where('sku', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%");
                });

                // Match in Product Specifications (model numbers, specs)
                $builder->orWhereHas('specifications', function ($s) use ($q) {
                    $s->where('value', 'like', "%{$q}%");
                });

                // Match individual tokens (trigram-like token matching)
                foreach ($words as $token) {
                    $builder->orWhere('products.title', 'like', "%{$token}%");
                }
            });
        }

        // 2. Category Filter (including descendants)
        if (!empty($filters['category'])) {
            $cat = $filters['category'];
            $targetCategory = is_numeric($cat)
                ? Category::find((int) $cat)
                : Category::where('slug', $cat)->first();

            if ($targetCategory) {
                $catIds = $targetCategory->children()->pluck('id')->push($targetCategory->id)->all();
                $productQuery->whereIn('products.category_id', $catIds);
            }
        }

        // 3. Brand Filter (Array or CSV)
        if (!empty($filters['brands'])) {
            $brandIds = is_array($filters['brands']) ? $filters['brands'] : explode(',', (string) $filters['brands']);
            $productQuery->where(function ($bQuery) use ($brandIds) {
                $bQuery->whereIn('products.brand_id', array_filter($brandIds, 'is_numeric'))
                    ->orWhereHas('brand', fn ($b) => $b->whereIn('slug', $brandIds));
            });
        }

        // 4. Seller Filter
        if (!empty($filters['sellers'])) {
            $sellerIds = is_array($filters['sellers']) ? $filters['sellers'] : explode(',', (string) $filters['sellers']);
            $productQuery->whereIn('products.seller_id', $sellerIds);
        }

        // 5. Price Range Filter (Min & Max Selling Price)
        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $min = (float) $filters['price_min'];
            $productQuery->whereHas('variants', fn ($v) => $v->where('selling_price', '>=', $min));
        }
        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $max = (float) $filters['price_max'];
            $productQuery->whereHas('variants', fn ($v) => $v->where('selling_price', '<=', $max));
        }

        // 6. In Stock Availability Filter
        if (!empty($filters['in_stock'])) {
            $productQuery->whereHas('variants.inventories', function ($inv) {
                $inv->whereRaw('quantity_on_hand > quantity_reserved');
            });
        }

        // 7. Delivery Type Filter (quick / standard / pickup)
        if (!empty($filters['delivery_type'])) {
            $type = (string) $filters['delivery_type'];
            if ($type === 'quick') {
                $productQuery->where('products.is_quick_capable', true);
            } elseif ($type === 'pickup') {
                $productQuery->whereHas('seller.stores', fn ($s) => $s->where('accepts_pickup', true));
            }
        }

        // 8. Delivery Time Filter (under_30_mins / today)
        if (!empty($filters['delivery_time']) && $filters['delivery_time'] === 'under_30_mins') {
            $productQuery->where('products.is_quick_capable', true);
        }

        // 9. Minimum Rating Filter
        if (!empty($filters['min_rating']) && is_numeric($filters['min_rating'])) {
            $minRating = (float) $filters['min_rating'];
            $productQuery->where(function ($q) use ($minRating) {
                $q->whereHas('reviews', fn ($r) => $r->where('status', 'approved'))
                    ->whereRaw('(SELECT coalesce(avg(rating), 0) FROM reviews WHERE reviews.product_id = products.id AND reviews.status = "approved") >= ?', [$minRating]);
            });
        }

        // 10. Minimum Discount Filter (percentage)
        if (!empty($filters['discount_min']) && is_numeric($filters['discount_min'])) {
            $minDiscount = (float) $filters['discount_min'] / 100.0;
            $productQuery->whereHas('variants', fn ($v) => $v->whereRaw('mrp > 0 AND ((mrp - selling_price) / mrp) >= ?', [$minDiscount]));
        }

        // 11. Specifications / Attributes Filter
        if (!empty($filters['specifications']) && is_array($filters['specifications'])) {
            foreach ($filters['specifications'] as $specKey => $specVal) {
                if (!empty($specVal)) {
                    $productQuery->whereHas('specifications', fn ($s) => $s->where('key', $specKey)->where('value', $specVal));
                }
            }
        }

        // 12. Location-Aware Ranking (Nearby Available First)
        $hasLocation = !empty($location['lat']) && !empty($location['lng']);
        if ($hasLocation) {
            $lat = (float) $location['lat'];
            $lng = (float) $location['lng'];
            $radius = (float) ($location['radius_km'] ?? setting('default_delivery_radius_km', 8.0));

            // Boost products whose seller has an active store nearby using subquery (avoids ONLY_FULL_GROUP_BY issues)
            if (DB::connection()->getDriverName() === 'sqlite') {
                $latDelta = $radius / 111.0;
                $lngDelta = $radius / (111.0 * max(0.1, cos(deg2rad($lat))));
                $productQuery->select('products.*')
                    ->selectRaw("CASE WHEN EXISTS (
                        SELECT 1 FROM store_locations
                        INNER JOIN stores ON stores.id = store_locations.store_id
                        WHERE stores.seller_id = products.seller_id
                        AND store_locations.lat BETWEEN ? AND ?
                        AND store_locations.lng BETWEEN ? AND ?
                    ) THEN 1 ELSE 0 END as is_nearby", [
                        $lat - $latDelta, $lat + $latDelta, $lng - $lngDelta, $lng + $lngDelta
                    ]);
            } else {
                $haversine = "(6371 * acos(
                    cos(radians({$lat})) * cos(radians(store_locations.lat)) * 
                    cos(radians(store_locations.lng) - radians({$lng})) + 
                    sin(radians({$lat})) * sin(radians(store_locations.lat))
                ))";
                $productQuery->select('products.*')
                    ->selectRaw("CASE WHEN EXISTS (
                        SELECT 1 FROM store_locations
                        INNER JOIN stores ON stores.id = store_locations.store_id
                        WHERE stores.seller_id = products.seller_id
                        AND {$haversine} <= ?
                    ) THEN 1 ELSE 0 END as is_nearby", [$radius]);
            }
        } else {
            $productQuery->select('products.*');
        }

        // 13. Sorting
        if ($hasLocation && ($sort === 'relevance' || empty($sort))) {
            $productQuery->orderByDesc('is_nearby');
        }

        switch ($sort) {
            case 'price_asc':
                $productQuery->orderBy(
                    ProductVariant::select('selling_price')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->orderBy('selling_price', 'asc')
                        ->limit(1),
                    'asc'
                );
                break;

            case 'price_desc':
                $productQuery->orderBy(
                    ProductVariant::select('selling_price')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->orderBy('selling_price', 'desc')
                        ->limit(1),
                    'desc'
                );
                break;

            case 'newest':
                $productQuery->orderByDesc('products.created_at');
                break;

            case 'discount_desc':
                $productQuery->orderBy(
                    ProductVariant::selectRaw('((mrp - selling_price) / CASE WHEN mrp > 0 THEN mrp ELSE 1 END)')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->orderByRaw('((mrp - selling_price) / CASE WHEN mrp > 0 THEN mrp ELSE 1 END) desc')
                        ->limit(1),
                    'desc'
                );
                break;

            case 'rating_desc':
                $productQuery->orderBy(
                    Review::selectRaw('coalesce(avg(rating), 0)')
                        ->whereColumn('reviews.product_id', 'products.id')
                        ->where('status', 'approved'),
                    'desc'
                )->orderByDesc('products.id');
                break;

            case 'relevance':
            default:
                $productQuery->orderByDesc('products.id');
                break;
        }

        return $productQuery->paginate($perPage)->withQueryString();
    }

    /**
     * Search stores with location and filters.
     */
    public function searchStores(
        string $query = '',
        array $filters = [],
        array $location = [],
        int $perPage = 12
    ): LengthAwarePaginator {
        $q = trim($query);

        $storeQuery = Store::active()
            ->with(['location', 'timings', 'seller']);

        if ($q !== '') {
            $storeQuery->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('location', function ($l) use ($q) {
                        $l->where('city', 'like', "%{$q}%")
                            ->orWhere('pincode', 'like', "%{$q}%")
                            ->orWhere('address', 'like', "%{$q}%");
                    });
            });
        }

        if (!empty($filters['quick_only'])) {
            $storeQuery->where('accepts_quick_commerce', true);
        }

        if (!empty($filters['pickup_only'])) {
            $storeQuery->where('accepts_pickup', true);
        }

        if (!empty($filters['city'])) {
            $storeQuery->whereHas('location', fn ($l) => $l->where('city', $filters['city']));
        }

        if (!empty($filters['category_id'])) {
            $catId = (string) $filters['category_id'];
            $storeQuery->where(function ($b) use ($catId) {
                $b->whereJsonContains('category_ids', $catId)
                  ->orWhereHas('products', fn ($p) => $p->where('category_id', (int) $catId));
            });
        }

        $applyRadius = !empty($location['lat']) && !empty($location['lng'])
            && empty($filters['all_stores'])
            && ($filters['radius_km'] ?? null) !== 'all'
            && empty($filters['city']); // If specific city selected, don't restrict to user's GPS radius

        if ($applyRadius) {
            $radius = (float) ($filters['radius_km'] ?? $location['radius_km'] ?? setting('default_delivery_radius_km', 25.0));
            try {
                $storeQuery->nearby((float) $location['lat'], (float) $location['lng'], $radius);
            } catch (\Throwable $e) {
                $storeQuery->orderByDesc('rating_avg');
            }
        } else {
            $sortBy = $filters['sort_by'] ?? 'rating';
            if ($sortBy === 'newest') {
                $storeQuery->latest();
            } elseif ($sortBy === 'name') {
                $storeQuery->orderBy('name');
            } else {
                $storeQuery->orderByDesc('rating_avg');
            }
        }

        $paginator = $storeQuery->paginate($perPage)->withQueryString();

        if (!empty($filters['open_only'])) {
            // Filter only open stores
            $openItems = $paginator->getCollection()->filter(fn (Store $s) => $s->isOpenNow());
            $paginator->setCollection($openItems->values());
        }

        return $paginator;
    }

    /**
     * Search brands with query.
     */
    public function searchBrands(
        string $query = '',
        int $perPage = 18
    ): LengthAwarePaginator {
        $q = trim($query);
        $brandQuery = Brand::query()
            ->withCount(['products' => fn ($p) => $p->active()]);

        if ($q !== '') {
            $brandQuery->where('name', 'like', "%{$q}%");
        }

        return $brandQuery->orderByDesc('is_verified')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Suggest typo corrections / "Did you mean?".
     */
    public function suggestCorrections(string $query): array
    {
        $term = strtolower(trim($query));
        if (strlen($term) < 3) {
            return ['suggested_query' => null, 'suggestions' => []];
        }

        // Pull popular product titles & brand names (cached 30 min)
        $words = \Illuminate\Support\Facades\Cache::remember('search.dictionary_words', 1800, function () {
            return Brand::pluck('name')
                ->merge(Category::where('is_active', true)->pluck('name'))
                ->merge(
                    Product::active()->take(50)->pluck('title')->flatMap(function ($title) {
                        return explode(' ', $title);
                    })
                )
                ->filter(fn ($w) => strlen(trim($w)) > 3)
                ->unique()
                ->values()
                ->all();
        });

        $bestWord = null;
        $bestDistance = 999;
        $suggestions = [];

        foreach ($words as $word) {
            $cleanWord = strtolower(trim($word));
            $dist = levenshtein($term, $cleanWord);

            if ($dist > 0 && $dist <= 2 && $dist < $bestDistance) {
                $bestDistance = $dist;
                $bestWord = $word;
            }

            if ($dist <= 2 && !in_array($word, $suggestions, true)) {
                $suggestions[] = $word;
            }
        }

        return [
            'suggested_query' => $bestWord,
            'suggestions' => array_slice($suggestions, 0, 5),
        ];
    }

    /**
     * Get facet counts / aggregations for sidebar filters.
     */
    public function getFilterFacets(string $query = '', array $currentFilters = []): array
    {
        // Cache global facets when no keyword is searched (10 min)
        if (trim($query) === '') {
            return \Illuminate\Support\Facades\Cache::remember('search.global_facets', 600, function () {
                return $this->computeFacets('');
            });
        }

        return $this->computeFacets($query);
    }

    /**
     * Compute actual facet aggregations.
     */
    protected function computeFacets(string $query): array
    {
        $baseQuery = Product::active();

        if (trim($query) !== '') {
            $q = trim($query);
            $baseQuery->where(function ($builder) use ($q) {
                $sanitized = preg_replace('/[+\-><()~*\"@\\\\]+/', ' ', $q);
                $words = array_filter(preg_split('/\s+/', trim((string) $sanitized)), fn ($w) => mb_strlen($w) >= 2);

                if (DB::connection()->getDriverName() === 'mysql' && !empty($words)) {
                    $booleanQuery = implode(' ', array_map(fn ($w) => '+' . $w . '*', $words));
                    $builder->whereRaw('MATCH(products.title, products.short_desc) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery]);
                } else {
                    $builder->where('products.title', 'like', "%{$q}%")
                        ->orWhere('products.description', 'like', "%{$q}%");
                }

                $builder->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"))
                    ->orWhereHas('specifications', fn ($s) => $s->where('value', 'like', "%{$q}%"));
            });
        }

        $matchingProductIds = trim($query) !== '' ? (clone $baseQuery)->pluck('id') : null;

        // Categories with counts
        $categoriesQuery = Category::where('is_active', true)
            ->whereHas('products', fn ($p) => $matchingProductIds !== null ? $p->whereIn('products.id', $matchingProductIds) : $p->active());

        if ($matchingProductIds !== null) {
            $categoriesQuery->withCount(['products' => fn ($p) => $p->whereIn('products.id', $matchingProductIds)]);
        } else {
            $categoriesQuery->withCount(['products' => fn ($p) => $p->active()]);
        }
        $categories = $categoriesQuery->orderByDesc('products_count')->take(10)->get();

        // Brands with counts
        $brandsQuery = Brand::whereHas('products', fn ($p) => $matchingProductIds !== null ? $p->whereIn('products.id', $matchingProductIds) : $p->active());

        if ($matchingProductIds !== null) {
            $brandsQuery->withCount(['products' => fn ($p) => $p->whereIn('products.id', $matchingProductIds)]);
        } else {
            $brandsQuery->withCount(['products' => fn ($p) => $p->active()]);
        }
        $brands = $brandsQuery->orderByDesc('products_count')->take(12)->get();

        // Price range bounds
        $minPrice = (float) (ProductVariant::where('is_active', true)->min('selling_price') ?? 0);
        $maxPrice = (float) (ProductVariant::where('is_active', true)->max('selling_price') ?? 2000);

        return [
            'categories' => $categories,
            'brands' => $brands,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'total_products_count' => (clone $baseQuery)->count(),
        ];
    }
}
