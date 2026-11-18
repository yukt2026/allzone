<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerCampaignParticipation;
use App\Models\SellerCampaignProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /**
     * Display promotional campaigns list with metrics and filters.
     */
    public function index(Request $request): View
    {
        $query = Campaign::query()
            ->with(['category'])
            ->withCount(['participations', 'participatingProducts'])
            ->latest('id');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('coupon_code', 'like', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            if ($type !== 'all') {
                if (in_array($type, ['festive', 'festive_season', 'festive_sale'], true)) {
                    $query->whereIn('type', ['festive_season', 'festive_sale', 'festive']);
                } else {
                    $query->where('type', $type);
                }
            }
        }

        if ($status = $request->query('status')) {
            if ($status === 'active') {
                $query->where('is_active', true)->whereNotIn('status', ['draft', 'paused', 'ended']);
            } elseif ($status === 'inactive') {
                $query->where(function ($q) {
                    $q->where('is_active', false)->orWhereIn('status', ['paused', 'ended']);
                });
            } elseif ($status === 'scheduled') {
                $query->where('starts_at', '>', now());
            } elseif ($status === 'draft') {
                $query->where('status', 'draft');
            } elseif ($status === 'paused') {
                $query->where('status', 'paused');
            } elseif ($status === 'ended') {
                $query->where('status', 'ended');
            }
        }

        $campaigns = $query->paginate(15)->withQueryString();

        $metrics = [
            'total' => Campaign::count(),
            'active' => Campaign::active()->count(),
            'scheduled' => Campaign::where('starts_at', '>', now())->count(),
            'flash_sales' => Campaign::where('type', 'flash_sale')->count(),
            'festive' => Campaign::whereIn('type', ['festive_season', 'festive_sale', 'festive'])->count(),
            'total_participating_sellers' => SellerCampaignParticipation::where('status', 'enrolled')->distinct('seller_id')->count('seller_id'),
            'total_enrolled_products' => SellerCampaignProduct::where('status', 'enrolled')->count(),
        ];

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $sellers = Seller::query()->where('status', 'approved')->orderBy('business_name')->get(['id', 'business_name']);

        return view('admin.campaigns.index', compact('campaigns', 'metrics', 'categories', 'sellers'));
    }

    /**
     * Show single campaign details with participating sellers, products, and performance metrics.
     */
    public function show(Campaign $campaign): View
    {
        $campaign->load(['category']);

        $participations = $campaign->participations()
            ->with(['seller.user', 'approver', 'products.product'])
            ->latest('id')
            ->get();

        $participatingProducts = $campaign->participatingProducts()
            ->with(['seller', 'product'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $enrolledProducts = $campaign->participatingProducts()->where('status', 'enrolled')->get();
        $enrolledProductIds = $enrolledProducts->pluck('product_id')->all();

        // Real order performance metrics
        $campaignOrdersCount = OrderItem::whereIn('product_id', $enrolledProductIds)->count();
        $campaignGmv = (float) OrderItem::whereIn('product_id', $enrolledProductIds)->sum('subtotal');
        $totalSavingsPerUnit = (float) $enrolledProducts->sum(fn ($p) => max(0, (float) $p->original_price - (float) $p->campaign_price));

        $metrics = [
            'enrolled_sellers' => $participations->whereIn('status', ['enrolled', 'approved'])->count(),
            'pending_approval_sellers' => $participations->where('status', 'pending_approval')->count(),
            'opted_out_sellers' => $participations->where('status', 'opted_out')->count(),
            'total_products' => $campaign->participatingProducts()->count(),
            'active_products' => $enrolledProducts->count(),
            'avg_discount' => round((float) ($enrolledProducts->avg('discount_percentage') ?? 0), 1),
            'orders_count' => $campaignOrdersCount,
            'gmv' => $campaignGmv,
            'total_savings_per_unit' => $totalSavingsPerUnit,
        ];

        return view('admin.campaigns.show', compact('campaign', 'participations', 'participatingProducts', 'metrics'));
    }

    /**
     * Store new promotional campaign with complete configuration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,active,paused,ended'],
            'description' => ['nullable', 'string'],
            'banner' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'opt_in_deadline' => ['nullable', 'date'],
            'discount_type' => ['nullable', 'string', 'in:percentage,flat'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'min_discount_percentage' => ['nullable', 'numeric', 'min:1', 'max:99'],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:1', 'max:99'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_product_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'applicable_category_id' => ['nullable', 'exists:categories,id'],
            'eligible_seller_ids' => ['nullable', 'array'],
            'eligible_seller_ids.*' => ['integer', 'exists:sellers,id'],
            'eligible_product_ids' => ['nullable', 'array'],
            'eligible_product_ids.*' => ['integer', 'exists:products,id'],
            'max_seller_products' => ['nullable', 'integer', 'min:1'],
            'budget_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'allow_opt_out' => ['nullable', 'boolean'],
            'participation_requires_approval' => ['nullable', 'boolean'],
            'terms_and_conditions' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug . '-' . Str::random(4);
        while (Campaign::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . Str::random(6);
        }

        $validated['slug'] = $slug;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['allow_opt_out'] = $request->boolean('allow_opt_out', true);
        $validated['participation_requires_approval'] = $request->boolean('participation_requires_approval', false);
        $validated['discount_type'] = $validated['discount_type'] ?? 'percentage';
        $validated['min_discount_percentage'] = $validated['min_discount_percentage'] ?? 5.00;

        // Auto-determine status if not explicitly draft/paused
        if (empty($validated['status'])) {
            if (!empty($validated['starts_at']) && strtotime((string)$validated['starts_at']) > time()) {
                $validated['status'] = 'scheduled';
            } else {
                $validated['status'] = $validated['is_active'] ? 'active' : 'paused';
            }
        }

        $campaign = Campaign::create($validated);

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->withProperties(['name' => $campaign->name, 'type' => $campaign->type])
                ->log("Campaign #{$campaign->id} ({$campaign->name}) created");
        }

        return redirect()->route('admin.campaigns.index')->with('success', "Campaign '{$campaign->name}' created successfully.");
    }

    /**
     * Update an existing campaign configuration.
     */
    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,active,paused,ended'],
            'description' => ['nullable', 'string'],
            'banner' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'opt_in_deadline' => ['nullable', 'date'],
            'discount_type' => ['nullable', 'string', 'in:percentage,flat'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'min_discount_percentage' => ['nullable', 'numeric', 'min:1', 'max:99'],
            'max_discount_percentage' => ['nullable', 'numeric', 'min:1', 'max:99'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_product_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'applicable_category_id' => ['nullable', 'exists:categories,id'],
            'eligible_seller_ids' => ['nullable', 'array'],
            'eligible_seller_ids.*' => ['integer', 'exists:sellers,id'],
            'eligible_product_ids' => ['nullable', 'array'],
            'eligible_product_ids.*' => ['integer', 'exists:products,id'],
            'max_seller_products' => ['nullable', 'integer', 'min:1'],
            'budget_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'allow_opt_out' => ['nullable', 'boolean'],
            'participation_requires_approval' => ['nullable', 'boolean'],
            'terms_and_conditions' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['allow_opt_out'] = $request->boolean('allow_opt_out', true);
        $validated['participation_requires_approval'] = $request->boolean('participation_requires_approval', false);

        if (!empty($validated['status'])) {
            if ($validated['status'] === 'paused' || $validated['status'] === 'ended') {
                $validated['is_active'] = false;
            } elseif ($validated['status'] === 'active') {
                $validated['is_active'] = true;
            }
        }

        $campaign->update($validated);

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Campaign #{$campaign->id} ({$campaign->name}) updated");
        }

        return redirect()->route('admin.campaigns.index')->with('success', "Campaign '{$campaign->name}' updated successfully.");
    }

    /**
     * Delete a campaign.
     */
    public function destroy(Campaign $campaign): RedirectResponse
    {
        $name = $campaign->name;
        $campaign->delete();

        if (function_exists('activity')) {
            activity('campaign')
                ->causedBy(Auth::user())
                ->log("Campaign {$name} deleted");
        }

        return redirect()->route('admin.campaigns.index')->with('success', "Campaign '{$name}' deleted.");
    }

    /**
     * Toggle campaign active status.
     */
    public function toggle(Request $request, Campaign $campaign): JsonResponse|RedirectResponse
    {
        $campaign->is_active = !$campaign->is_active;
        $campaign->status = $campaign->is_active ? 'active' : 'paused';
        $campaign->save();

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Campaign #{$campaign->id} ({$campaign->name}) status changed to " . ($campaign->is_active ? 'Active' : 'Inactive'));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $campaign->is_active,
                'message' => "Campaign status updated to " . ($campaign->is_active ? 'Active' : 'Inactive'),
            ]);
        }

        return back()->with('success', "Campaign '{$campaign->name}' status toggled to " . ($campaign->is_active ? 'Active' : 'Inactive') . '.');
    }

    /**
     * Publish or schedule campaign.
     */
    public function publish(Campaign $campaign): RedirectResponse
    {
        $now = now();
        $isScheduled = $campaign->starts_at && $campaign->starts_at->isFuture();

        $campaign->is_active = true;
        $campaign->status = $isScheduled ? 'scheduled' : 'active';
        $campaign->save();

        $statusLabel = $isScheduled ? 'Scheduled' : 'Published & Live';

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Campaign #{$campaign->id} ({$campaign->name}) set to {$statusLabel}");
        }

        return back()->with('success', "Campaign '{$campaign->name}' is now {$statusLabel}.");
    }

    /**
     * Pause an active campaign.
     */
    public function pause(Campaign $campaign): RedirectResponse
    {
        $campaign->is_active = false;
        $campaign->status = 'paused';
        $campaign->save();

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Campaign #{$campaign->id} ({$campaign->name}) paused");
        }

        return back()->with('success', "Campaign '{$campaign->name}' has been paused.");
    }

    /**
     * Immediately end a campaign.
     */
    public function end(Campaign $campaign): RedirectResponse
    {
        $campaign->is_active = false;
        $campaign->status = 'ended';
        $campaign->ends_at = now();
        $campaign->save();

        if (function_exists('activity')) {
            activity('campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Campaign #{$campaign->id} ({$campaign->name}) ended");
        }

        return back()->with('success', "Campaign '{$campaign->name}' has been ended.");
    }

    /**
     * Approve seller campaign participation.
     */
    public function approveParticipation(Campaign $campaign, SellerCampaignParticipation $participation): RedirectResponse
    {
        abort_if($participation->campaign_id !== $campaign->id, 404);

        $participation->update([
            'status' => 'enrolled',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ]);

        // Also activate enrolled products for this seller in this campaign
        SellerCampaignProduct::where('campaign_id', $campaign->id)
            ->where('seller_id', $participation->seller_id)
            ->update(['status' => 'enrolled']);

        if (function_exists('activity')) {
            activity('campaign_participation')
                ->performedOn($participation)
                ->causedBy(Auth::user())
                ->log("Approved participation for seller {$participation->seller?->business_name} in campaign {$campaign->name}");
        }

        return back()->with('success', "Seller '{$participation->seller?->business_name}' participation approved successfully.");
    }

    /**
     * Reject seller campaign participation with reason.
     */
    public function rejectParticipation(Request $request, Campaign $campaign, SellerCampaignParticipation $participation): RedirectResponse
    {
        abort_if($participation->campaign_id !== $campaign->id, 404);

        $reason = trim((string) $request->input('rejection_reason', 'Product catalog criteria or quality score requirements were not met.'));

        $participation->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Mark enrolled products as rejected
        SellerCampaignProduct::where('campaign_id', $campaign->id)
            ->where('seller_id', $participation->seller_id)
            ->update(['status' => 'rejected']);

        if (function_exists('activity')) {
            activity('campaign_participation')
                ->performedOn($participation)
                ->causedBy(Auth::user())
                ->log("Rejected participation for seller {$participation->seller?->business_name} in campaign {$campaign->name}: {$reason}");
        }

        return back()->with('success', "Seller '{$participation->seller?->business_name}' participation rejected.");
    }
}
