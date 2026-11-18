<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimType;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Services\Admin\AdminClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ClaimManagementController extends Controller
{
    public function __construct(
        protected AdminClaimService $claimService
    ) {}

    /**
     * Display all platform seller claims with status filtering and audit metrics.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $claimType = $request->query('claim_type');
        $sellerId = $request->query('seller_id');
        $search = $request->query('search');

        $filters = [
            'status' => $status,
            'claim_type' => $claimType,
            'seller_id' => $sellerId,
            'search' => $search,
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
        ];

        $claims = $this->claimService->buildClaimsQuery($filters)->paginate(20)->withQueryString();

        $metrics = $this->claimService->getClaimMetrics();
        $reasons = $this->claimService->getClaimReasonsDistribution();
        $trends = $this->claimService->getClaimTrends();
        $sellers = Seller::query()->orderBy('business_name')->get(['id', 'business_name']);

        return view('admin.claims.index', [
            'claims' => $claims,
            'metrics' => $metrics,
            'reasons' => $reasons,
            'trends' => $trends,
            'status' => $status,
            'claimType' => $claimType,
            'sellerId' => $sellerId,
            'search' => $search,
            'sellers' => $sellers,
            'types' => ClaimType::cases(),
        ]);
    }

    /**
     * Show claim detail screen with full conversation, internal notes, and evidence.
     */
    public function show(SellerClaim $claim): View
    {
        $claim->load([
            'seller.user',
            'order.user',
            'order.customer.user',
            'order.customerAddress',
            'orderItem.variant.product',
            'fulfilmentOrder.shipment.tracking',
            'orderReturn',
            'messages.user',
        ]);

        return view('admin.claims.show', [
            'claim' => $claim,
        ]);
    }

    /**
     * Add staff-only internal note to claim thread.
     */
    public function addInternalNote(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->claimService->addInternalNote($claim, $validated['note'], Auth::user());

            return back()->with('success', 'Internal note added to claim record.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to add note: ' . $e->getMessage());
        }
    }

    /**
     * Request additional information/proof from merchant.
     */
    public function requestInfo(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'instructions' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->claimService->requestMoreInfo($claim, $validated['instructions'], Auth::user());

            return back()->with('success', 'Requested additional proof from merchant. Claim status updated to More Info Needed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Request failed: ' . $e->getMessage());
        }
    }

    /**
     * Approve claim and disburse compensation ledger credit.
     */
    public function approve(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $claim->claimed_amount],
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->claimService->approveClaim(
                $claim,
                (float) $validated['approved_amount'],
                $validated['approval_notes'] ?? null,
                Auth::user()
            );

            return back()->with('success', "Claim #{$claim->claim_number} approved. ₹{$validated['approved_amount']} credited to seller ledger.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject claim with mandatory reason explanation.
     */
    public function reject(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->claimService->rejectClaim($claim, $validated['rejection_reason'], Auth::user());

            return back()->with('success', "Claim #{$claim->claim_number} has been rejected.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Formally close a resolved claim / dispute.
     */
    public function close(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'close_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->claimService->closeClaim($claim, $validated['close_reason'] ?? null, Auth::user());

            return back()->with('success', "Claim #{$claim->claim_number} has been closed.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Action failed: ' . $e->getMessage());
        }
    }

    /**
     * Escalate dispute for senior carrier investigation.
     */
    public function escalate(Request $request, SellerClaim $claim): RedirectResponse
    {
        $validated = $request->validate([
            'escalation_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->claimService->escalateDispute($claim, $validated['escalation_reason'], Auth::user());

            return back()->with('success', "Claim #{$claim->claim_number} has been escalated for senior review.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Escalation failed: ' . $e->getMessage());
        }
    }
}
