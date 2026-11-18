<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QualityController extends Controller
{
    /**
     * Display Product Quality & Recommendations Dashboard (Step 12).
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller, 403, 'No seller profile attached.');

        // 1. Overall Real Review Metrics
        $approvedReviewsQuery = Review::forSeller($seller->id)->where('status', ReviewStatus::APPROVED);
        $totalRatings = (int) (clone $approvedReviewsQuery)->count();
        $avgRatingRaw = (clone $approvedReviewsQuery)->avg('rating');
        $averageRating = $totalRatings > 0 ? round((float) $avgRatingRaw, 2) : 0.0;

        $lowRatingCount = (int) (clone $approvedReviewsQuery)->whereIn('rating', [1, 2])->count();
        $lowRatingPercent = $totalRatings > 0 ? round(($lowRatingCount / $totalRatings) * 100, 1) : 0.0;

        // Rating Star Distribution
        $distributionCounts = (clone $approvedReviewsQuery)
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingTrends = [
            5 => ['count' => (int) ($distributionCounts[5] ?? 0), 'percent' => $totalRatings > 0 ? round((($distributionCounts[5] ?? 0) / $totalRatings) * 100) : 0],
            4 => ['count' => (int) ($distributionCounts[4] ?? 0), 'percent' => $totalRatings > 0 ? round((($distributionCounts[4] ?? 0) / $totalRatings) * 100) : 0],
            3 => ['count' => (int) ($distributionCounts[3] ?? 0), 'percent' => $totalRatings > 0 ? round((($distributionCounts[3] ?? 0) / $totalRatings) * 100) : 0],
            2 => ['count' => (int) ($distributionCounts[2] ?? 0), 'percent' => $totalRatings > 0 ? round((($distributionCounts[2] ?? 0) / $totalRatings) * 100) : 0],
            1 => ['count' => (int) ($distributionCounts[1] ?? 0), 'percent' => $totalRatings > 0 ? round((($distributionCounts[1] ?? 0) / $totalRatings) * 100) : 0],
        ];

        // Quality Score Tier
        if ($totalRatings === 0) {
            $qualityTier = [
                'tier' => 'none',
                'label' => 'No score available',
                'subtext' => "Since you don't have enough customer ratings yet.",
                'score' => 'N/A',
                'color' => 'slate',
            ];
        } elseif ($lowRatingPercent <= 15.0) {
            $qualityTier = [
                'tier' => 'green',
                'label' => '1.6X VIEWS (Excellent)',
                'subtext' => 'High quality catalog! Only ' . $lowRatingPercent . '% low ratings.',
                'score' => number_format($averageRating, 1) . ' / 5.0',
                'color' => 'emerald',
            ];
        } elseif ($lowRatingPercent <= 22.0) {
            $qualityTier = [
                'tier' => 'yellow',
                'label' => '1.0X VIEWS (Fair)',
                'subtext' => 'Moderate quality. Address customer complaints to boost views.',
                'score' => number_format($averageRating, 1) . ' / 5.0',
                'color' => 'amber',
            ];
        } elseif ($lowRatingPercent <= 25.0) {
            $qualityTier = [
                'tier' => 'red',
                'label' => '0.6X VIEWS (At Risk)',
                'subtext' => 'High customer dissatisfaction (' . $lowRatingPercent . '% 1-2★ ratings).',
                'score' => number_format($averageRating, 1) . ' / 5.0',
                'color' => 'rose',
            ];
        } else {
            $qualityTier = [
                'tier' => 'blocked',
                'label' => 'BLOCKING RISK (Critical)',
                'subtext' => 'Severe low rating ratio exceeding 25%. Product listings subject to deactivation.',
                'score' => number_format($averageRating, 1) . ' / 5.0',
                'color' => 'rose',
            ];
        }

        // Top Customer Feedback
        $recentFeedback = (clone $approvedReviewsQuery)
            ->with(['product:id,title,slug', 'user:id,name'])
            ->latest('id')
            ->take(5)
            ->get();

        // 2. Recommendations Tabs & Product Diagnostics
        $activeTab = $request->query('tab', 'action_required');
        $search = trim((string) $request->query('search', ''));

        // Load all seller products with relations for diagnostic analysis
        $allProducts = Product::where('seller_id', $seller->id)
            ->with([
                'category:id,name',
                'defaultVariant',
                'variants',
                'media',
                'reviews' => fn ($q) => $q->where('status', ReviewStatus::APPROVED),
            ])
            ->withCount(['media', 'reviews' => fn ($q) => $q->where('status', ReviewStatus::APPROVED)])
            ->withAvg(['reviews' => fn ($q) => $q->where('status', ReviewStatus::APPROVED)], 'rating')
            ->get();

        // Diagnose each product
        $diagnosedProducts = $allProducts->map(function ($p) {
            $avgRating = $p->reviews_avg_rating ? round((float) $p->reviews_avg_rating, 1) : null;
            $reviewCount = (int) $p->reviews_count;
            $mediaCount = (int) $p->media_count;

            $lowRatingCount = $p->reviews->whereIn('rating', [1, 2])->count();
            $lowRatingPct = $reviewCount > 0 ? round(($lowRatingCount / $reviewCount) * 100, 1) : 0.0;

            $issues = [];
            $recommendations = [];

            // Status checks
            if ($p->status === ProductStatus::REJECTED) {
                $issues[] = 'QC Moderation Error: ' . ($p->rejection_reason ?: 'Product rejected by quality team');
                $recommendations[] = 'Revise product specifications and submit for QC re-review';
            }

            // Listing defect checks
            if ($mediaCount === 0) {
                $issues[] = 'Listing Defect: Missing product gallery photos';
                $recommendations[] = 'Upload at least 2 high-resolution photos on white background';
            }

            if (empty($p->description) || strlen(strip_tags((string) $p->description)) < 20) {
                $issues[] = 'Listing Defect: Incomplete or missing product description';
                $recommendations[] = 'Provide detailed technical specs, package contents, and warranty';
            }

            // Customer feedback checks
            if ($reviewCount > 0 && $avgRating !== null && $avgRating < 3.0) {
                $issues[] = "Low Customer Rating: {$avgRating}★ avg from {$reviewCount} review(s)";
                $recommendations[] = 'Investigate reported quality defects and respond to customer reviews';
            }

            if ($reviewCount >= 2 && $lowRatingPct >= 25.0) {
                $issues[] = "High Return / Complaint Risk: {$lowRatingPct}% of reviews are 1-2★";
                $recommendations[] = 'Inspect packaging to prevent transit damage and verify item description';
            }

            // Classification into tabs
            $isActionRequired = false;
            $isBlockingSoon = false;
            $isFixed = false;
            $isBlocked = false;

            if ($p->status === ProductStatus::INACTIVE || ($p->status === ProductStatus::REJECTED && str_contains(strtolower((string) $p->rejection_reason), 'quality'))) {
                $isBlocked = true;
            } elseif ($p->status === ProductStatus::PENDING_REVIEW || ($reviewCount > 0 && $avgRating >= 3.5 && count($issues) === 0)) {
                $isFixed = true;
            } elseif ($p->status === ProductStatus::ACTIVE && ($lowRatingPct >= 20.0 || ($avgRating !== null && $avgRating < 3.0 && $reviewCount >= 2))) {
                $isBlockingSoon = true;
            } elseif (count($issues) > 0 || $p->status === ProductStatus::REJECTED || $p->status === ProductStatus::DRAFT) {
                $isActionRequired = true;
            }

            return [
                'product' => $p,
                'avg_rating' => $avgRating,
                'review_count' => $reviewCount,
                'low_rating_count' => $lowRatingCount,
                'low_rating_pct' => $lowRatingPct,
                'issues' => $issues,
                'recommendations' => $recommendations,
                'latest_feedback' => $p->reviews->sortByDesc('id')->first()?->body,
                'is_action_required' => $isActionRequired,
                'is_blocking_soon' => $isBlockingSoon,
                'is_fixed' => $isFixed,
                'is_blocked' => $isBlocked,
            ];
        });

        // Tab counts
        $actionRequiredCount = $diagnosedProducts->where('is_action_required', true)->count();
        $blockingSoonCount = $diagnosedProducts->where('is_blocking_soon', true)->count();
        $fixedCount = $diagnosedProducts->where('is_fixed', true)->count();
        $blockedCount = $diagnosedProducts->where('is_blocked', true)->count();

        // Filter products for active tab
        $filteredItems = $diagnosedProducts->filter(function ($item) use ($activeTab, $search) {
            $matchesTab = match ($activeTab) {
                'blocking_soon' => $item['is_blocking_soon'],
                'fixed' => $item['is_fixed'],
                'blocked' => $item['is_blocked'],
                default => $item['is_action_required'],
            };

            if (!$matchesTab) {
                return false;
            }

            if (!empty($search)) {
                $term = strtolower($search);
                $p = $item['product'];
                $sku = strtolower((string) ($p->defaultVariant?->sku ?? ''));
                $title = strtolower((string) $p->title);
                $cat = strtolower((string) ($p->category?->name ?? ''));

                return str_contains($title, $term) || str_contains($sku, $term) || str_contains($cat, $term);
            }

            return true;
        });

        // Pagination for filtered items
        $perPage = 15;
        $page = (int) $request->query('page', 1);
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredItems->forPage($page, $perPage)->values(),
            $filteredItems->count(),
            $perPage,
            $page,
            ['path' => route('seller.quality.index'), 'query' => $request->query()]
        );

        return view('seller.quality.index', [
            'seller' => $seller,
            'totalRatings' => $totalRatings,
            'averageRating' => $averageRating,
            'lowRatingCount' => $lowRatingCount,
            'lowRatingPercent' => $lowRatingPercent,
            'ratingTrends' => $ratingTrends,
            'qualityTier' => $qualityTier,
            'recentFeedback' => $recentFeedback,
            'activeTab' => $activeTab,
            'search' => $search,
            'paginatedItems' => $paginatedItems,
            'actionRequiredCount' => $actionRequiredCount,
            'blockingSoonCount' => $blockingSoonCount,
            'fixedCount' => $fixedCount,
            'blockedCount' => $blockedCount,
        ]);
    }

    /**
     * Get product reviews list with customer comments and seller replies as JSON.
     */
    public function productReviews(Product $product): JsonResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $product->seller_id !== $seller->id, 403);

        $reviews = Review::where('product_id', $product->id)
            ->where('status', ReviewStatus::APPROVED)
            ->with('user:id,name')
            ->latest('id')
            ->get();

        return response()->json([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'total_reviews' => $reviews->count(),
            'avg_rating' => $reviews->avg('rating') ? round((float) $reviews->avg('rating'), 1) : null,
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'reviewer_name' => $r->user?->name ?? 'Verified Buyer',
                'is_verified_purchase' => (bool) $r->is_verified_purchase,
                'created_at' => $r->created_at?->format('d M Y'),
                'seller_response' => $r->seller_response,
                'seller_responded_at' => $r->seller_responded_at?->format('d M Y, h:i A'),
            ]),
        ]);
    }

    /**
     * Post a merchant reply to a customer review.
     */
    public function replyReview(Request $request, Review $review): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        $product = $review->product;
        abort_if(!$seller || !$product || $product->seller_id !== $seller->id, 403);

        $validated = $request->validate([
            'response' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $review->update([
            'seller_response' => trim($validated['response']),
            'seller_responded_at' => now(),
        ]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your reply to this customer review has been posted.',
                'review_id' => $review->id,
                'seller_response' => $review->seller_response,
                'seller_responded_at' => $review->seller_responded_at->format('d M Y, h:i A'),
            ]);
        }

        return back()->with('success', 'Your merchant reply has been posted.');
    }

    /**
     * Request re-review for a rejected or flagged product.
     */
    public function requestReReview(Product $product): JsonResponse|RedirectResponse
    {
        $seller = Auth::user()?->seller;
        abort_if(!$seller || $product->seller_id !== $seller->id, 403);

        $product->update([
            'status' => ProductStatus::PENDING_REVIEW,
        ]);

        activity()
            ->performedOn($product)
            ->causedBy(Auth::user())
            ->withProperties(['action' => 'quality_re_review_requested'])
            ->log("Seller #{$seller->id} requested quality re-review for product #{$product->id} ('{$product->title}')");

        if (request()->expectsJson() || request()->isJson()) {
            return response()->json([
                'success' => true,
                'message' => "Product #{$product->id} submitted for quality re-review.",
                'new_status' => ProductStatus::PENDING_REVIEW->value,
            ]);
        }

        return back()->with('success', "Product '{$product->title}' has been submitted for quality re-review.");
    }
}
