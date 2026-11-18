<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\InfluencerCollaboration;
use App\Models\InfluencerProfile;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InfluencerController extends Controller
{
    /**
     * Display Influencer Marketing Dashboard & Discovery Hub.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $tab = (string) $request->query('tab', 'explore');
        $platform = (string) $request->query('platform', 'all');

        // 1. Approved Influencer Profiles
        $influencersQuery = InfluencerProfile::active()->with('category');
        if ($platform !== 'all') {
            $influencersQuery->where('platform', $platform);
        }
        $influencers = $influencersQuery->latest('id')->get();

        // 2. Seller's Collaboration Requests & History
        $collaborations = $seller->influencerCollaborations()
            ->with(['influencer', 'product'])
            ->latest('id')
            ->get();

        // 3. Seller's Active Products for Collaboration Proposals
        $sellerProducts = Product::where('seller_id', $seller->id)
            ->where('status', ProductStatus::ACTIVE)
            ->get(['id', 'title']);

        // 4. Performance Metrics (Strictly Real Data)
        $acceptedOrCompleted = $collaborations->whereIn('status', ['accepted', 'in_progress', 'completed']);
        $totalSpend = (float) $acceptedOrCompleted->sum('proposed_budget');
        $totalAttributedRev = (float) $acceptedOrCompleted->sum('attributed_revenue');
        $totalOrders = (int) $acceptedOrCompleted->sum('attributed_orders');
        $totalImpressions = (int) $acceptedOrCompleted->sum('impressions');
        $totalClicks = (int) $acceptedOrCompleted->sum('clicks');

        $overallRoi = ($totalSpend > 0 && $totalAttributedRev > 0)
            ? round($totalAttributedRev / $totalSpend, 2)
            : null;

        $metrics = [
            'approved_influencers' => $influencers->count(),
            'active_collaborations' => $collaborations->whereIn('status', ['accepted', 'in_progress'])->count(),
            'completed_collaborations' => $collaborations->where('status', 'completed')->count(),
            'pending_proposals' => $collaborations->where('status', 'pending')->count(),
            'total_spend' => $totalSpend,
            'total_revenue' => $totalAttributedRev,
            'total_orders' => $totalOrders,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'overall_roi' => $overallRoi,
        ];

        return view('seller.influencer.index', compact(
            'seller',
            'influencers',
            'collaborations',
            'sellerProducts',
            'metrics',
            'tab',
            'platform'
        ));
    }

    /**
     * Submit a collaboration proposal to an approved influencer.
     */
    public function collaborate(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $validated = $request->validate([
            'influencer_profile_id' => ['required', 'integer', 'exists:influencer_profiles,id'],
            'campaign_title' => ['required', 'string', 'max:150'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'proposed_budget' => ['required', 'numeric', 'min:100', 'max:500000'],
            'proposal_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Tenant isolation: verify product belongs to this seller if provided
        if (!empty($validated['product_id'])) {
            $product = Product::where('seller_id', $seller->id)
                ->where('id', $validated['product_id'])
                ->first();

            if (!$product) {
                return back()->with('error', 'Selected product does not belong to your catalog.');
            }
        }

        $influencer = InfluencerProfile::findOrFail($validated['influencer_profile_id']);

        $collaboration = InfluencerCollaboration::create([
            'seller_id' => $seller->id,
            'influencer_profile_id' => $influencer->id,
            'product_id' => $validated['product_id'] ?? null,
            'campaign_title' => $validated['campaign_title'],
            'proposed_budget' => $validated['proposed_budget'],
            'proposal_notes' => $validated['proposal_notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('seller.influencer.index', ['tab' => 'my_collaborations'])
            ->with('success', "Collaboration proposal for '{$influencer->name}' submitted successfully.");
    }

    /**
     * Show details of a specific collaboration.
     */
    public function show(InfluencerCollaboration $collaboration): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || $collaboration->seller_id !== $seller->id, 403, 'Unauthorized collaboration access.');

        $collaboration->load(['influencer.category', 'product']);

        return view('seller.influencer.show', compact('seller', 'collaboration'));
    }
}
