<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\Storefront\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontApiController extends ApiController
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    /**
     * Get public platform configuration and localized delivery parameters.
     */
    public function config(): JsonResponse
    {
        return $this->success([
            'platform_name' => setting('name', 'ALLZONE'),
            'currency' => setting('currency', 'INR'),
            'currency_symbol' => setting('currency_symbol', '₹'),
            'quick_commerce_radius_km' => (float) setting('default_delivery_radius_km', 8.00),
            'free_delivery_threshold' => (float) setting('free_delivery_threshold', 499.00),
            'base_delivery_fee' => (float) setting('base_delivery_fee', 40.00),
            'support_email' => setting('support_email', 'support@allzone.in'),
            'support_phone' => setting('support_phone', '+91 98765 43210'),
        ]);
    }

    /**
     * Mobile Home Screen aggregate payload (Banners, Categories, Deals, Bestsellers).
     */
    public function home(): JsonResponse
    {
        $heroBanners = $this->storefrontService->getHeroBanners()->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'subtitle' => $b->position_label ?? null,
            'image_url' => $b->image_url,
            'link_url' => $b->link,
        ]);

        $promoBanners = $this->storefrontService->getPromoBanners()->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'image_url' => $b->image_url,
            'link_url' => $b->link,
        ]);

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(12)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image_url' => $c->image_url,
            ]);

        $deals = $this->storefrontService->getDealsProducts(8)->map(fn ($p) => $this->transformProductCard($p));
        $bestSellers = $this->storefrontService->getBestSellers(8)->map(fn ($p) => $this->transformProductCard($p));
        $recommended = $this->storefrontService->getRecommendedProducts(8)->map(fn ($p) => $this->transformProductCard($p));

        return $this->success([
            'hero_banners' => $heroBanners,
            'promo_banners' => $promoBanners,
            'categories' => $categories,
            'deals' => $deals,
            'best_sellers' => $bestSellers,
            'recommended' => $recommended,
        ]);
    }

    /**
     * Categories tree listing.
     */
    public function categories(): JsonResponse
    {
        $tree = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->withCount(['products' => fn ($q) => $q->where('status', ProductStatus::ACTIVE)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'image_url' => $cat->image_url,
                'products_count' => $cat->products_count,
                'subcategories' => $cat->children->map(fn ($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'image_url' => $child->image_url,
                ]),
            ]);

        return $this->success($tree);
    }

    /**
     * Active brands listing.
     */
    public function brands(): JsonResponse
    {
        $brands = Brand::withCount(['products' => fn ($q) => $q->where('status', ProductStatus::ACTIVE)])
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'is_verified' => (bool) $b->is_verified,
                'logo_url' => $b->logo ? asset('storage/' . $b->logo) : null,
                'products_count' => $b->products_count,
            ]);

        return $this->success($brands);
    }

    /**
     * Paginated products catalogue with search, filtering, and sorting.
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::active()
            ->with([
                'defaultVariant.inventories',
                'variants.inventories',
                'media',
                'brand',
                'category',
                'seller',
            ]);

        // Search
        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                    ->orWhereHas('specifications', fn ($sq) => $sq->where('value', 'like', "%{$search}%"));
            });
        }

        // Category filter (slug or id)
        if ($catId = $request->input('category_id')) {
            $query->where('category_id', (int) $catId);
        } elseif ($catSlug = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $catSlug));
        }

        // Brand filter (slug or id)
        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', (int) $brandId);
        } elseif ($brandSlug = $request->input('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        // Quick Commerce capable
        if ($request->boolean('quick_capable') || $request->input('delivery_type') === 'quick') {
            $query->where('is_quick_capable', true);
        }

        // Price range (supporting both min_price/price_min and max_price/price_max)
        $minPrice = $request->input('min_price') ?? $request->input('price_min');
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->whereHas('variants', fn ($q) => $q->where('selling_price', '>=', (float) $minPrice));
        }
        $maxPrice = $request->input('max_price') ?? $request->input('price_max');
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->whereHas('variants', fn ($q) => $q->where('selling_price', '<=', (float) $maxPrice));
        }

        // In Stock only
        if ($request->boolean('in_stock')) {
            $query->whereHas('variants.inventories', function ($iq) {
                $iq->whereRaw('(quantity_on_hand - quantity_reserved) > 0');
            });
        }

        // Sorting
        $sort = (string) $request->input('sort', 'latest');
        match ($sort) {
            'price_low', 'price_asc' => $query->join('product_variants', function ($j) {
                $j->on('products.id', '=', 'product_variants.product_id')->where('product_variants.is_default', true);
            })->orderBy('product_variants.selling_price', 'asc')->select('products.*'),
            'price_high', 'price_desc' => $query->join('product_variants', function ($j) {
                $j->on('products.id', '=', 'product_variants.product_id')->where('product_variants.is_default', true);
            })->orderBy('product_variants.selling_price', 'desc')->select('products.*'),
            'discount', 'discount_desc' => $query->orderBy(
                \App\Models\ProductVariant::selectRaw('((mrp - selling_price) / CASE WHEN mrp > 0 THEN mrp ELSE 1 END)')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderByRaw('((mrp - selling_price) / CASE WHEN mrp > 0 THEN mrp ELSE 1 END) desc')
                    ->limit(1),
                'desc'
            ),
            'rating', 'rating_desc' => $query->orderBy(
                Review::selectRaw('coalesce(avg(rating), 0)')
                    ->whereColumn('reviews.product_id', 'products.id')
                    ->where('status', 'approved'),
                'desc'
            )->orderByDesc('products.id'),
            default => $query->latest('products.id'),
        };

        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $paginator = $query->paginate($perPage);

        return $this->success([
            'items' => collect($paginator->items())->map(fn ($p) => $this->transformProductCard($p)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Product Detail with variants, stock, specifications, media, seller, and reviews.
     */
    public function productDetail(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with([
                'defaultVariant.inventories',
                'defaultVariant.media',
                'media',
                'variants.inventories.location',
                'variants.media',
                'brand',
                'category.parent',
                'seller.stores',
                'specifications',
                'reviews' => fn ($q) => $q->where('status', ReviewStatus::APPROVED)->with('user')->latest()->take(10),
            ])
            ->first();

        if (!$product) {
            return $this->error('Product not found or inactive.', 404);
        }

        $reviews = $product->reviews;
        $totalReviews = $reviews->count();
        $avgRating = $totalReviews > 0 ? (float) round($reviews->avg('rating'), 1) : 4.5;

        // Transform variants
        $variants = $product->variants->map(function ($v) {
            $stock = $v->relationLoaded('inventories')
                ? (int) $v->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved))
                : 0;

            $img = $v->relationLoaded('media') && $v->media->first()?->path
                ? asset('storage/' . $v->media->first()->path)
                : null;

            return [
                'id' => $v->id,
                'sku' => $v->sku,
                'name' => $v->name,
                'attributes' => $v->attributes ?? [],
                'selling_price' => (float) $v->selling_price,
                'formatted_selling_price' => format_currency((float) $v->selling_price),
                'mrp' => (float) $v->mrp,
                'formatted_mrp' => format_currency((float) $v->mrp),
                'discount_percent' => ($v->mrp > $v->selling_price && $v->mrp > 0)
                    ? (int) round((($v->mrp - $v->selling_price) / $v->mrp) * 100)
                    : 0,
                'stock' => $stock,
                'in_stock' => $stock > 0,
                'is_default' => (bool) $v->is_default,
                'image_url' => $img,
            ];
        });

        // Media gallery
        $gallery = $product->media->map(fn ($m) => [
            'id' => $m->id,
            'type' => $m->type?->value ?? 'image',
            'url' => (str_starts_with($m->path, 'http://') || str_starts_with($m->path, 'https://'))
                ? $m->path
                : asset('storage/' . $m->path),
        ]);

        // Specifications
        $specs = $product->specifications->groupBy(fn ($s) => $s->group ?? 'General')->map(function ($groupItems) {
            return $groupItems->map(fn ($s) => [
                'key' => $s->key,
                'value' => $s->value,
            ]);
        });

        return $this->success([
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'short_desc' => $product->short_desc,
            'description' => $product->description,
            'hsn_code' => $product->hsn_code,
            'tax_percent' => (float) $product->tax_percent,
            'return_window_days' => (int) $product->return_window_days,
            'is_returnable' => (bool) $product->is_returnable,
            'is_cod_allowed' => (bool) $product->is_cod_allowed,
            'is_quick_capable' => (bool) $product->is_quick_capable,
            'effective_price' => $product->effective_price,
            'effective_mrp' => $product->effective_mrp,
            'discount_percent' => $product->discount_percent,
            'primary_image_url' => $product->primaryImageUrl(),
            'rating' => $avgRating,
            'reviews_count' => $totalReviews,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
                'is_verified' => (bool) $product->brand->is_verified,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
                'parent' => $product->category->parent ? [
                    'id' => $product->category->parent->id,
                    'name' => $product->category->parent->name,
                    'slug' => $product->category->parent->slug,
                ] : null,
            ] : null,
            'seller' => [
                'name' => 'ALLZONE Verified Partner',
                'fulfilled_by' => setting('name', 'ALLZONE'),
                'is_verified' => true,
                'badge' => 'ALLZONE Assured',
            ],
            'gallery' => $gallery,
            'variants' => $variants,
            'specifications' => $specs,
            'recent_reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'user_name' => $r->user?->name ?? 'Verified Buyer',
                'created_at' => $r->created_at?->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Transform a Product model into an API product card representation.
     */
    protected function transformProductCard(Product $product): array
    {
        $variant = $product->relationLoaded('defaultVariant') && $product->defaultVariant
            ? $product->defaultVariant
            : ($product->relationLoaded('variants') ? $product->variants->first() : $product->defaultVariant);

        $inStock = true;
        if ($variant) {
            if ($variant->relationLoaded('inventories')) {
                $inStock = $variant->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved)) > 0;
            } elseif ($product->relationLoaded('variants')) {
                $matchingV = $product->variants->firstWhere('id', $variant->id);
                if ($matchingV && $matchingV->relationLoaded('inventories')) {
                    $inStock = $matchingV->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved)) > 0;
                }
            }
        }

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'image_url' => $product->primaryImageUrl(),
            'price' => $product->effective_price,
            'formatted_price' => format_currency($product->effective_price),
            'mrp' => $product->effective_mrp,
            'formatted_mrp' => format_currency($product->effective_mrp),
            'discount_percent' => $product->discount_percent,
            'is_quick_capable' => (bool) $product->is_quick_capable,
            'in_stock' => $inStock,
            'default_variant_id' => $variant?->id,
            'brand_name' => $product->brand?->name ?? setting('name', 'ALLZONE'),
            'category_name' => $product->category?->name,
        ];
    }
}
