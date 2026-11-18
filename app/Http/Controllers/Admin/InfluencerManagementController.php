<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InfluencerCollaboration;
use App\Models\InfluencerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InfluencerManagementController extends Controller
{
    /**
     * Manage approved influencers and audit seller collaboration requests.
     */
    public function index(Request $request): View
    {
        $tab = (string) $request->query('tab', 'profiles');
        $status = (string) $request->query('status', 'all');
        $platform = (string) $request->query('platform', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $search = trim((string) $request->query('search', ''));

        // Profiles Query
        $profilesQuery = InfluencerProfile::query()
            ->with(['category'])
            ->withCount('collaborations')
            ->latest('id');

        if ($platform !== 'all') {
            $profilesQuery->where('platform', $platform);
        }

        if ($search !== '' && $tab === 'profiles') {
            $profilesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('handle', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        $profiles = $profilesQuery->paginate(15, ['*'], 'profiles_page');

        // Collaborations Query
        $collaborationsQuery = InfluencerCollaboration::query()
            ->with(['seller.user', 'influencer', 'product'])
            ->latest('id');

        if ($status !== 'all') {
            $collaborationsQuery->where('status', $status);
        }

        if ($paymentStatus !== 'all') {
            $collaborationsQuery->where('payment_status', $paymentStatus);
        }

        if ($search !== '' && $tab === 'collaborations') {
            $collaborationsQuery->where(function ($q) use ($search) {
                $q->where('campaign_title', 'like', "%{$search}%")
                  ->orWhere('proposal_notes', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"))
                  ->orWhereHas('influencer', fn ($iq) => $iq->where('handle', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $collaborations = $collaborationsQuery->paginate(20, ['*'], 'collabs_page');

        $totalSpend = (float) InfluencerCollaboration::whereIn('status', ['accepted', 'in_progress', 'completed'])->sum('proposed_budget');
        $totalPaid = (float) InfluencerCollaboration::where('payment_status', 'paid')->sum('proposed_budget');

        $metrics = [
            'total_profiles' => InfluencerProfile::count(),
            'active_profiles' => InfluencerProfile::where('status', 'active')->count(),
            'verified_profiles' => InfluencerProfile::where('is_approved', true)->count(),
            'pending_proposals' => InfluencerCollaboration::where('status', 'pending')->count(),
            'in_progress_collaborations' => InfluencerCollaboration::where('status', 'in_progress')->count(),
            'completed_collaborations' => InfluencerCollaboration::where('status', 'completed')->count(),
            'total_spend' => $totalSpend,
            'total_paid' => $totalPaid,
            'total_attributed_revenue' => (float) InfluencerCollaboration::sum('attributed_revenue'),
            'total_attributed_orders' => (int) InfluencerCollaboration::sum('attributed_orders'),
        ];

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.influencers.index', compact(
            'profiles',
            'collaborations',
            'metrics',
            'categories',
            'tab',
            'status',
            'platform',
            'paymentStatus',
            'search'
        ));
    }

    /**
     * Create/onboard a new vetted influencer profile.
     */
    public function storeProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'handle' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'platform' => ['required', 'string', 'in:instagram,youtube,x,blog,tiktok'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'verified_followers' => ['required', 'integer', 'min:0'],
            'collaboration_fee' => ['nullable', 'numeric', 'min:0'],
            'bio' => ['nullable', 'string', 'max:500'],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_approved'] = true;
        $validated['status'] = 'active';

        $profile = InfluencerProfile::create($validated);

        if (function_exists('activity')) {
            activity('influencer_profile')
                ->performedOn($profile)
                ->causedBy(Auth::user())
                ->log("Influencer profile '{$profile->name}' ({$profile->handle}) added by Admin");
        }

        return back()->with('success', "Influencer profile '{$profile->name}' approved and listed on marketplace.");
    }

    /**
     * Update an existing influencer profile.
     */
    public function updateProfile(Request $request, InfluencerProfile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'handle' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'platform' => ['required', 'string', 'in:instagram,youtube,x,blog,tiktok'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'verified_followers' => ['required', 'integer', 'min:0'],
            'collaboration_fee' => ['nullable', 'numeric', 'min:0'],
            'bio' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $profile->update($validated);

        if (function_exists('activity')) {
            activity('influencer_profile')
                ->performedOn($profile)
                ->causedBy(Auth::user())
                ->log("Influencer profile '{$profile->name}' updated");
        }

        return back()->with('success', "Influencer profile '{$profile->name}' updated successfully.");
    }

    /**
     * Toggle or update verification status of an influencer profile.
     */
    public function toggleProfileVerification(Request $request, InfluencerProfile $profile): RedirectResponse
    {
        $profile->is_approved = !$profile->is_approved;
        if ($request->filled('verification_notes')) {
            $profile->verification_notes = $request->input('verification_notes');
        }
        $profile->save();

        $statusText = $profile->is_approved ? 'Verified' : 'Unverified';

        if (function_exists('activity')) {
            activity('influencer_profile')
                ->performedOn($profile)
                ->causedBy(Auth::user())
                ->log("Influencer '{$profile->name}' marked as {$statusText}");
        }

        return back()->with('success', "Influencer '{$profile->name}' verification set to {$statusText}.");
    }

    /**
     * Update collaboration workflow status, deliverable URL, and performance attribution.
     */
    public function updateCollaborationStatus(Request $request, InfluencerCollaboration $collaboration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,accepted,declined,in_progress,completed,cancelled'],
            'deliverable_url' => ['nullable', 'url', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:500'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'attributed_orders' => ['nullable', 'integer', 'min:0'],
            'attributed_revenue' => ['nullable', 'numeric', 'min:0'],
        ]);

        $collaboration->update($validated);

        if (function_exists('activity')) {
            activity('influencer_collaboration')
                ->performedOn($collaboration)
                ->causedBy(Auth::user())
                ->log("Collaboration #{$collaboration->id} status updated to " . ucfirst($validated['status']));
        }

        return back()->with('success', "Collaboration #{$collaboration->id} status updated to " . ucfirst($validated['status']) . '.');
    }

    /**
     * Update payment record for a collaboration.
     */
    public function updateCollaborationPayment(Request $request, InfluencerCollaboration $collaboration): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'string', 'in:unpaid,in_escrow,paid,cancelled'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validated['payment_status'] === 'paid' && !$collaboration->paid_at) {
            $validated['paid_at'] = now();
        } elseif ($validated['payment_status'] !== 'paid') {
            $validated['paid_at'] = null;
        }

        $collaboration->update($validated);

        if (function_exists('activity')) {
            activity('influencer_collaboration')
                ->performedOn($collaboration)
                ->causedBy(Auth::user())
                ->log("Collaboration #{$collaboration->id} payment status updated to {$validated['payment_status']}");
        }

        return back()->with('success', "Payment record for collaboration #{$collaboration->id} updated.");
    }

    /**
     * Export influencer collaborations & performance report to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'influencer-collaborations-report-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $query = InfluencerCollaboration::query()
            ->with(['seller', 'influencer', 'product'])
            ->latest('id');

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, [
                'Collaboration ID',
                'Merchant Seller',
                'Influencer Name',
                'Handle',
                'Platform',
                'Product Promoted',
                'Campaign Title',
                'Proposed Budget (₹)',
                'Payment Status',
                'Collaboration Status',
                'Deliverable URL',
                'Impressions',
                'Clicks',
                'Attributed Orders',
                'Attributed Revenue (₹)',
                'ROI (x)',
                'Created At',
            ]);

            $query->chunk(200, function ($collabs) use ($handle) {
                foreach ($collabs as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->seller?->business_name ?? 'N/A',
                        $c->influencer?->name ?? 'N/A',
                        $c->influencer?->handle ?? 'N/A',
                        $c->influencer?->platform ?? 'N/A',
                        $c->product?->title ?? 'N/A',
                        $c->campaign_title,
                        number_format((float) $c->proposed_budget, 2, '.', ''),
                        ucfirst((string) $c->payment_status),
                        ucfirst((string) $c->status),
                        $c->deliverable_url ?? '',
                        $c->impressions,
                        $c->clicks,
                        $c->attributed_orders,
                        number_format((float) $c->attributed_revenue, 2, '.', ''),
                        $c->roi ?? '0.00',
                        $c->created_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
