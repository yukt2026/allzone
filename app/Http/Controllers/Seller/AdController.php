<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerAdCampaign;
use App\Models\SellerAdCampaignProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdController extends Controller
{
    /**
     * Display Advertisement Dashboard with real performance metrics.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $tab = (string) $request->query('tab', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = $seller->adCampaigns()
            ->withCount('products')
            ->latest('id');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        match ($tab) {
            'draft' => $query->where('status', 'draft'),
            'active' => $query->where('status', 'active'),
            'paused' => $query->where('status', 'paused'),
            'pending_approval' => $query->where('status', 'pending_approval'),
            'completed' => $query->where('status', 'completed'),
            'rejected' => $query->where('status', 'rejected'),
            default => null, // 'all'
        };

        $campaigns = $query->paginate(12)->withQueryString();

        // Real Metrics calculated strictly from database records
        $allCampaigns = $seller->adCampaigns()->get();

        $totalAdSpend = (float) $allCampaigns->sum('spent_amount');
        $totalAttributedRevenue = (float) $allCampaigns->sum('attributed_revenue');
        $totalImpressions = (int) $allCampaigns->sum('impressions_count');
        $totalClicks = (int) $allCampaigns->sum('clicks_count');
        $totalAttributedOrders = (int) $allCampaigns->sum('attributed_orders_count');

        // ROI is computed only when ad spend and attributed revenue exist
        $overallRoi = ($totalAdSpend > 0 && $totalAttributedRevenue > 0)
            ? round($totalAttributedRevenue / $totalAdSpend, 2)
            : null;

        $metrics = [
            'total_campaigns' => $allCampaigns->count(),
            'active_campaigns' => $allCampaigns->where('status', 'active')->count(),
            'paused_campaigns' => $allCampaigns->where('status', 'paused')->count(),
            'draft_campaigns' => $allCampaigns->where('status', 'draft')->count(),
            'pending_campaigns' => $allCampaigns->where('status', 'pending_approval')->count(),
            'completed_campaigns' => $allCampaigns->where('status', 'completed')->count(),
            'total_spend' => $totalAdSpend,
            'total_revenue' => $totalAttributedRevenue,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_orders' => $totalAttributedOrders,
            'overall_roi' => $overallRoi,
        ];

        return view('seller.ads.index', compact('seller', 'campaigns', 'metrics', 'tab', 'search'));
    }

    /**
     * Show form to create a new ad campaign.
     */
    public function create(): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $products = Product::where('seller_id', $seller->id)
            ->where('status', ProductStatus::ACTIVE)
            ->with(['variants', 'media'])
            ->latest('id')
            ->get();

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('seller.ads.create', compact('seller', 'products', 'categories'));
    }

    /**
     * Store a newly created ad campaign and submit for approval or save as draft.
     */
    public function store(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'in:sponsored_listing,banner_display,featured_category'],
            'daily_budget' => ['required', 'numeric', 'min:50', 'max:100000'],
            'total_budget' => ['required', 'numeric', 'min:50', 'max:1000000', 'gte:daily_budget'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'targeting_type' => ['required', 'string', 'in:category,search_keywords,storewide'],
            'targeting_keywords' => ['nullable', 'string', 'max:500'],
            'headline' => ['nullable', 'string', 'max:150'],
            'creative_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'selected_products' => ['required', 'array', 'min:1'],
            'selected_products.*' => ['integer', 'exists:products,id'],
            'bids' => ['nullable', 'array'],
            'bids.*' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'action' => ['required', 'string', 'in:submit,save_draft'],
        ], [
            'total_budget.gte' => 'Total budget must be greater than or equal to daily budget.',
            'selected_products.required' => 'Please select at least one product for this ad campaign.',
        ]);

        $selectedProductIds = array_map('intval', $validated['selected_products']);

        // Multi-vendor tenant isolation check
        $products = Product::where('seller_id', $seller->id)
            ->whereIn('id', $selectedProductIds)
            ->get();

        if ($products->isEmpty()) {
            return back()->with('error', 'No valid seller products selected for advertising.')->withInput();
        }

        // Handle creative image upload
        $creativeUrl = null;
        if ($request->hasFile('creative_image')) {
            $path = $request->file('creative_image')->store('ads/creatives', 'public');
            $creativeUrl = '/storage/' . ltrim($path, '/');
        }

        $status = $validated['action'] === 'save_draft' ? 'draft' : 'pending_approval';

        $targetingPayload = [];
        if ($validated['targeting_type'] === 'search_keywords' && !empty($validated['targeting_keywords'])) {
            $keywords = array_filter(array_map('trim', explode(',', $validated['targeting_keywords'])));
            $targetingPayload['keywords'] = array_values($keywords);
        }

        $campaign = DB::transaction(function () use ($seller, $validated, $products, $creativeUrl, $status, $targetingPayload, $request) {
            $camp = SellerAdCampaign::create([
                'seller_id' => $seller->id,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'status' => $status,
                'daily_budget' => $validated['daily_budget'],
                'total_budget' => $validated['total_budget'],
                'spent_amount' => 0.00,
                'starts_at' => $validated['starts_at'] ?? now(),
                'ends_at' => $validated['ends_at'] ?? null,
                'targeting_type' => $validated['targeting_type'],
                'targeting_payload' => $targetingPayload,
                'creative_image_url' => $creativeUrl,
                'headline' => $validated['headline'] ?? null,
            ]);

            foreach ($products as $product) {
                $customBid = $request->input("bids.{$product->id}");
                $bidAmount = $customBid !== null ? (float) $customBid : 5.00;

                SellerAdCampaignProduct::create([
                    'ad_campaign_id' => $camp->id,
                    'product_id' => $product->id,
                    'bid_amount' => $bidAmount,
                    'status' => 'active',
                ]);
            }

            return $camp;
        });

        $msg = $status === 'draft'
            ? "Ad campaign '{$campaign->name}' saved as draft."
            : "Ad campaign '{$campaign->name}' submitted for admin review. Real delivery begins once approved.";

        return redirect()->route('seller.ads.index')->with('success', $msg);
    }

    /**
     * Show details of an ad campaign with reliable performance breakdown.
     */
    public function show(SellerAdCampaign $campaign): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $campaign->seller_id !== $seller->id, 403, 'Unauthorized campaign access.');

        $campaign->load(['products.product.variants', 'approvedBy']);

        return view('seller.ads.show', compact('seller', 'campaign'));
    }

    /**
     * Pause or resume an active ad campaign.
     */
    public function toggleStatus(SellerAdCampaign $campaign): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $campaign->seller_id !== $seller->id, 403, 'Unauthorized campaign access.');

        if (!in_array($campaign->status, ['active', 'paused'], true)) {
            return back()->with('error', "Only active or paused campaigns can be toggled. Current status: {$campaign->status}.");
        }

        $newStatus = $campaign->status === 'active' ? 'paused' : 'active';
        $campaign->status = $newStatus;
        $campaign->save();

        $actionText = $newStatus === 'active' ? 'resumed' : 'paused';

        return back()->with('success', "Campaign '{$campaign->name}' has been {$actionText}.");
    }

    /**
     * Delete or cancel an ad campaign.
     */
    public function destroy(SellerAdCampaign $campaign): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $campaign->seller_id !== $seller->id, 403, 'Unauthorized campaign access.');

        if (in_array($campaign->status, ['active'], true)) {
            return back()->with('error', 'Active campaigns must be paused before removal.');
        }

        $campaign->delete();

        return redirect()->route('seller.ads.index')->with('success', "Ad campaign '{$campaign->name}' removed.");
    }
}
