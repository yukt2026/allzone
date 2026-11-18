<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerAdCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdManagementController extends Controller
{
    /**
     * Audit and manage all seller ad campaigns across the marketplace.
     */
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', 'all');
        $sellerId = $request->query('seller_id');

        $query = SellerAdCampaign::query()
            ->with(['seller.user', 'approvedBy'])
            ->withCount('products')
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('headline', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if ($sellerId) {
            $query->where('seller_id', (int) $sellerId);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        $totalImpressions = (int) SellerAdCampaign::sum('impressions_count');
        $totalClicks = (int) SellerAdCampaign::sum('clicks_count');
        $totalSpend = (float) SellerAdCampaign::sum('spent_amount');
        $totalRevenue = (float) SellerAdCampaign::sum('attributed_revenue');

        $metrics = [
            'total' => SellerAdCampaign::count(),
            'pending' => SellerAdCampaign::where('status', 'pending_approval')->count(),
            'active' => SellerAdCampaign::where('status', 'active')->count(),
            'paused' => SellerAdCampaign::where('status', 'paused')->count(),
            'rejected' => SellerAdCampaign::where('status', 'rejected')->count(),
            'completed' => SellerAdCampaign::where('status', 'completed')->count(),
            'total_spend' => $totalSpend,
            'total_revenue' => $totalRevenue,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_orders' => (int) SellerAdCampaign::sum('attributed_orders_count'),
            'avg_ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0,
            'overall_roi' => $totalSpend > 0 ? round($totalRevenue / $totalSpend, 2) : 0.0,
        ];

        $sellers = Seller::query()->where('status', 'approved')->orderBy('business_name')->get(['id', 'business_name']);

        return view('admin.ads.index', compact('campaigns', 'metrics', 'status', 'search', 'type', 'sellers'));
    }

    /**
     * Creative review and detailed campaign performance breakdown.
     */
    public function show(SellerAdCampaign $campaign): View
    {
        $campaign->load([
            'seller.user',
            'approvedBy',
            'products.product.variants',
            'products.product.media',
        ]);

        return view('admin.ads.show', compact('campaign'));
    }

    /**
     * Approve a seller ad campaign for live delivery.
     */
    public function approve(SellerAdCampaign $campaign): RedirectResponse
    {
        $campaign->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ]);

        if (function_exists('activity')) {
            activity('ad_campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Ad campaign '{$campaign->name}' approved by Admin");
        }

        return back()->with('success', "Ad campaign '{$campaign->name}' approved. Delivery is now active.");
    }

    /**
     * Reject a seller ad campaign with mandatory reason.
     */
    public function reject(Request $request, SellerAdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $campaign->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        if (function_exists('activity')) {
            activity('ad_campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Ad campaign '{$campaign->name}' rejected: {$validated['rejection_reason']}");
        }

        return back()->with('success', "Ad campaign '{$campaign->name}' rejected: {$validated['rejection_reason']}");
    }

    /**
     * Update campaign delivery status (pause, resume, complete).
     */
    public function updateStatus(Request $request, SellerAdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,paused,completed,draft'],
        ]);

        $campaign->update(['status' => $validated['status']]);

        if (function_exists('activity')) {
            activity('ad_campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Ad campaign '{$campaign->name}' status changed to " . ucfirst($validated['status']));
        }

        return back()->with('success', "Ad campaign status updated to " . ucfirst($validated['status']) . ".");
    }

    /**
     * Update budget and billing configuration.
     */
    public function updateBudget(Request $request, SellerAdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'daily_budget' => ['required', 'numeric', 'min:10'],
            'total_budget' => ['required', 'numeric', 'min:50', 'gte:daily_budget'],
            'billing_status' => ['required', 'string', 'in:active_wallet,pending_deduction,deducted_from_payout,settled'],
            'billing_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $campaign->update($validated);

        if (function_exists('activity')) {
            activity('ad_campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Ad campaign '{$campaign->name}' budget and billing updated");
        }

        return back()->with('success', "Budget and billing configuration for '{$campaign->name}' updated.");
    }

    /**
     * Update verified ad delivery metrics.
     */
    public function updateMetrics(Request $request, SellerAdCampaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'impressions_count' => ['required', 'integer', 'min:0'],
            'clicks_count' => ['required', 'integer', 'min:0'],
            'attributed_orders_count' => ['required', 'integer', 'min:0'],
            'attributed_revenue' => ['required', 'numeric', 'min:0'],
            'spent_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $campaign->update($validated);

        if (function_exists('activity')) {
            activity('ad_campaign')
                ->performedOn($campaign)
                ->causedBy(Auth::user())
                ->log("Ad campaign '{$campaign->name}' delivery metrics updated");
        }

        return back()->with('success', "Attributed performance metrics updated for '{$campaign->name}'.");
    }

    /**
     * Export advertising performance report to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'ad-campaigns-performance-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $query = SellerAdCampaign::query()
            ->with(['seller'])
            ->latest('id');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Campaign ID',
                'Merchant Seller',
                'Campaign Name',
                'Type',
                'Status',
                'Daily Budget (₹)',
                'Total Budget (₹)',
                'Ad Spend (₹)',
                'Billing Status',
                'Impressions',
                'Clicks',
                'CTR (%)',
                'Attributed Orders',
                'Attributed Revenue (₹)',
                'ROI (x)',
                'Starts At',
                'Ends At',
            ]);

            $query->chunk(200, function ($campaigns) use ($handle) {
                foreach ($campaigns as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->seller?->business_name ?? 'N/A',
                        $c->name,
                        $c->type,
                        $c->status,
                        number_format((float) $c->daily_budget, 2, '.', ''),
                        number_format((float) $c->total_budget, 2, '.', ''),
                        number_format((float) $c->spent_amount, 2, '.', ''),
                        $c->billing_status ?? 'active_wallet',
                        $c->impressions_count,
                        $c->clicks_count,
                        $c->ctr,
                        $c->attributed_orders_count,
                        number_format((float) $c->attributed_revenue, 2, '.', ''),
                        $c->roi ?? '0.00',
                        $c->starts_at?->toDateTimeString() ?? '',
                        $c->ends_at?->toDateTimeString() ?? 'Ongoing',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
