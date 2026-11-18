<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\SellerCampaignParticipation;
use App\Models\SellerCampaignProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PromotionController extends Controller
{
    /**
     * Display Promotional Campaigns & Sale Opt-in Dashboard.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $tab = (string) $request->query('tab', 'available');
        $search = trim((string) $request->query('search', ''));

        $query = Campaign::query()
            ->with(['category'])
            ->withCount(['participatingProducts' => fn ($q) => $q->where('seller_id', $seller->id)])
            ->where(function ($q) use ($seller) {
                $q->whereNull('eligible_seller_ids')
                  ->orWhereJsonContains('eligible_seller_ids', $seller->id)
                  ->orWhereJsonContains('eligible_seller_ids', (string) $seller->id);
            })
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $now = now();

        match ($tab) {
            'upcoming' => $query->where('starts_at', '>', $now),
            'live' => $query->where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                }),
            'expired' => $query->whereNotNull('ends_at')->where('ends_at', '<', $now),
            'participating' => $query->whereHas('participations', function ($pq) use ($seller) {
                $pq->where('seller_id', $seller->id)->where('status', 'enrolled');
            }),
            'history' => $query->whereHas('participations', function ($pq) use ($seller) {
                $pq->where('seller_id', $seller->id);
            }),
            default => $query->where('is_active', true) // 'available'
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('opt_in_deadline')->orWhere('opt_in_deadline', '>=', $now);
                }),
        };

        $campaigns = $query->paginate(12)->withQueryString();

        // Seller Participations map for status badges
        $participations = SellerCampaignParticipation::where('seller_id', $seller->id)
            ->get()
            ->keyBy('campaign_id');

        // Metrics Summary for Seller
        $allActiveCampaigns = Campaign::active()->get();
        $enrolledCampaignIds = $participations->where('status', 'enrolled')->pluck('campaign_id')->all();

        $enrolledProducts = SellerCampaignProduct::where('seller_id', $seller->id)
            ->where('status', 'enrolled')
            ->get();

        $metrics = [
            'available_campaigns' => Campaign::where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })->count(),
            'live_campaigns' => Campaign::where('is_active', true)
                ->where(function ($q) use ($now) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                })->count(),
            'upcoming_campaigns' => Campaign::where('starts_at', '>', $now)->count(),
            'participating_campaigns' => count($enrolledCampaignIds),
            'enrolled_products_count' => $enrolledProducts->count(),
            'avg_discount' => round((float) ($enrolledProducts->avg('discount_percentage') ?? 0), 1),
        ];

        return view('seller.promotions.index', compact(
            'seller',
            'campaigns',
            'participations',
            'metrics',
            'tab',
            'search'
        ));
    }

    /**
     * Display Campaign Details, Eligibility, Enrolled Products and Transparent Benefits.
     */
    public function show(Campaign $campaign): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');
        abort_if(!$campaign->isEligibleForSeller($seller), 403, 'Your store is not eligible for this targeted campaign.');

        $campaign->load(['category']);

        $participation = $campaign->participationForSeller($seller->id);
        $participationStatus = $participation ? $participation->status : 'not_enrolled';

        // Products enrolled by this seller in this campaign
        $enrolledProducts = SellerCampaignProduct::where('seller_id', $seller->id)
            ->where('campaign_id', $campaign->id)
            ->with(['product.variants', 'product.media'])
            ->latest('id')
            ->get();

        // Transparent Benefits Calculation
        $activeEnrolled = $enrolledProducts->where('status', 'enrolled');
        $enrolledCount = $activeEnrolled->count();
        $avgDiscount = round((float) ($activeEnrolled->avg('discount_percentage') ?? 0), 1);
        $totalSavingsPerUnit = (float) $activeEnrolled->sum(fn ($p) => max(0, (float) $p->original_price - (float) $p->campaign_price));

        $transparentBenefits = [
            'enrolled_count' => $enrolledCount,
            'avg_discount' => $avgDiscount,
            'total_savings_per_unit' => $totalSavingsPerUnit,
            'estimated_commission_rate' => 8.0, // Standard 8% benchmark category fee
        ];

        // Eligible products available to enroll (excluding already enrolled)
        $alreadyEnrolledProductIds = $enrolledProducts->pluck('product_id')->all();

        $eligibleQuery = Product::where('seller_id', $seller->id)
            ->whereNotIn('id', $alreadyEnrolledProductIds)
            ->with(['variants', 'media', 'category']);

        if ($campaign->applicable_category_id) {
            $eligibleQuery->where('category_id', $campaign->applicable_category_id);
        }

        $eligibleProducts = $eligibleQuery->latest('id')->limit(50)->get();

        return view('seller.promotions.show', compact(
            'seller',
            'campaign',
            'participation',
            'participationStatus',
            'enrolledProducts',
            'eligibleProducts',
            'transparentBenefits'
        ));
    }

    /**
     * Opt-in selected products to the promotional campaign.
     */
    public function optIn(Request $request, Campaign $campaign): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');
        abort_if(!$campaign->isEligibleForSeller($seller), 403, 'Your store is not eligible for this targeted campaign.');

        if (!$campaign->isOptInOpen()) {
            return back()->with('error', "Opt-in is closed for campaign '{$campaign->name}'.");
        }

        $validated = $request->validate([
            'terms_accepted' => ['required', 'accepted'],
            'selected_products' => ['required', 'array', 'min:1'],
            'selected_products.*' => ['integer', 'exists:products,id'],
            'discounts' => ['nullable', 'array'],
            'discounts.*' => ['nullable', 'numeric'],
        ], [
            'terms_accepted.accepted' => 'You must review and agree to the campaign discount lock terms.',
            'selected_products.required' => 'Please select at least one product to enroll.',
        ]);

        $selectedProductIds = array_map('intval', $validated['selected_products']);

        // Tenant isolation: verify all selected products belong to this seller
        $products = Product::where('seller_id', $seller->id)
            ->whereIn('id', $selectedProductIds)
            ->get();

        if ($products->isEmpty()) {
            return back()->with('error', 'No valid seller products found for enrollment.');
        }

        $minDiscount = (float) $campaign->min_discount_percentage;
        $maxDiscount = $campaign->max_discount_percentage ? (float) $campaign->max_discount_percentage : 90.0;
        $participationStatus = $campaign->participation_requires_approval ? 'pending_approval' : 'enrolled';

        DB::transaction(function () use ($seller, $campaign, $products, $request, $minDiscount, $maxDiscount, $participationStatus) {
            // 1. Record / update participation
            SellerCampaignParticipation::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'seller_id' => $seller->id,
                ],
                [
                    'status' => $participationStatus,
                    'enrolled_at' => now(),
                    'opted_out_at' => null,
                    'agreed_to_terms' => true,
                ]
            );

            // 2. Enroll products
            foreach ($products as $product) {
                $customDiscount = $request->input("discounts.{$product->id}");
                $discount = $customDiscount !== null ? (float) $customDiscount : $minDiscount;

                // Validate bounds
                $discount = max($minDiscount, min($maxDiscount, $discount));

                $basePrice = (float) $product->effective_price;
                if ($basePrice <= 0) {
                    $basePrice = 100.0; // Fallback safe price
                }

                $campaignPrice = round($basePrice * (1 - ($discount / 100)), 2);

                SellerCampaignProduct::updateOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'seller_id' => $seller->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'original_price' => $basePrice,
                        'discount_percentage' => $discount,
                        'campaign_price' => $campaignPrice,
                        'status' => $participationStatus,
                    ]
                );
            }
        });

        $successMsg = $campaign->participation_requires_approval
            ? "Participation submitted for {$products->count()} products. Awaiting Admin review and approval."
            : "Enrolled {$products->count()} products in '{$campaign->name}' successfully!";

        return back()->with('success', $successMsg);
    }

    /**
     * Opt-out from a campaign where permitted.
     */
    public function optOut(Request $request, Campaign $campaign): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        if (!$campaign->allow_opt_out && $campaign->isLive()) {
            return back()->with('error', "Opt-out is locked for '{$campaign->name}' as the promotional sale is currently live.");
        }

        DB::transaction(function () use ($seller, $campaign) {
            SellerCampaignParticipation::updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'seller_id' => $seller->id,
                ],
                [
                    'status' => 'opted_out',
                    'opted_out_at' => now(),
                ]
            );

            SellerCampaignProduct::where('seller_id', $seller->id)
                ->where('campaign_id', $campaign->id)
                ->update(['status' => 'opted_out']);
        });

        return back()->with('success', "You have opted out of campaign '{$campaign->name}'. Products have returned to regular pricing.");
    }

    /**
     * Remove an individual product from campaign enrollment.
     */
    public function removeProduct(Campaign $campaign, Product $product): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $product->seller_id !== $seller->id, 403, 'Unauthorized product action.');

        SellerCampaignProduct::where('seller_id', $seller->id)
            ->where('campaign_id', $campaign->id)
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('success', "Removed '{$product->title}' from campaign '{$campaign->name}'.");
    }

    /**
     * Return eligible products for a campaign as JSON.
     */
    public function eligibleProducts(Campaign $campaign): JsonResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $enrolledIds = SellerCampaignProduct::where('seller_id', $seller->id)
            ->where('campaign_id', $campaign->id)
            ->pluck('product_id')
            ->all();

        $query = Product::where('seller_id', $seller->id)
            ->whereNotIn('id', $enrolledIds)
            ->with(['variants']);

        if ($campaign->applicable_category_id) {
            $query->where('category_id', $campaign->applicable_category_id);
        }

        $products = $query->limit(50)->get()->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'sku' => $p->sku,
            'effective_price' => $p->effective_price,
            'min_discount' => $campaign->min_discount_percentage,
            'recommended_price' => round($p->effective_price * (1 - ($campaign->min_discount_percentage / 100)), 2),
        ]);

        return response()->json($products);
    }

    /**
     * Standalone Daily Offers & Flexi Deals console.
     */
    public function offers(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $tab = $request->query('tab', 'flexi');
        $search = $request->query('search');

        $query = Product::where('seller_id', $seller->id)
            ->with(['variants', 'media'])
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        $products = $query->paginate(15)->withQueryString();

        return view('seller.promotions.offers', compact('seller', 'products', 'tab', 'search'));
    }

    /**
     * Sales alias to index.
     */
    public function sales(Request $request): View
    {
        return $this->index($request);
    }
}
