<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\SellerFinanceApplication;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /**
     * Display the seller finance overview, eligibility status, or transparent unavailable/waitlist state.
     */
    public function index(): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        // 1. Admin-controlled finance program status
        $status = Setting::get('seller_finance_status', 'not_available'); // not_available, coming_soon, active_partner
        $providerName = Setting::get('seller_finance_provider_name', '');
        $disclaimer = Setting::get('seller_finance_disclaimer', 'ALLZONE is an e-commerce technology marketplace and does not operate as a bank, NBFC, or credit institution.');
        $minMonths = (int) Setting::get('seller_finance_min_months_active', 3);
        $minOrders = (int) Setting::get('seller_finance_min_orders', 25);

        // 2. Real Seller Track Record & Eligibility Evaluation
        $sellerTenureMonths = $seller->approved_at
            ? max(0, (int) $seller->approved_at->diffInMonths(now()))
            : 0;

        $deliveredOrdersCount = FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->count();

        $deliveredRevenue = (float) FulfilmentOrder::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->sum('subtotal');

        $returnsCount = OrderReturn::forSeller($seller->id)
            ->whereIn('status', ['approved', 'completed', 'refunded', 'qc_passed'])
            ->count();

        $returnRate = $deliveredOrdersCount > 0
            ? round(($returnsCount / $deliveredOrdersCount) * 100, 2)
            : 0.00;

        $isEligible = ($sellerTenureMonths >= $minMonths) && ($deliveredOrdersCount >= $minOrders);

        // 3. Seller's existing applications / inquiries
        $application = SellerFinanceApplication::where('seller_id', $seller->id)
            ->with('transactions')
            ->latest('id')
            ->first();

        $transactions = $application
            ? $application->transactions()->latest('transacted_at')->paginate(10)
            : collect();

        // Strict real data - zero fabricated balances
        $approvedLimit = $application ? (float) $application->approved_limit : 0.00;
        $outstandingBalance = $application ? (float) $application->outstanding_balance : 0.00;

        return view('seller.finance.index', compact(
            'seller',
            'status',
            'providerName',
            'disclaimer',
            'minMonths',
            'minOrders',
            'sellerTenureMonths',
            'deliveredOrdersCount',
            'deliveredRevenue',
            'returnRate',
            'isEligible',
            'application',
            'transactions',
            'approvedLimit',
            'outstandingBalance'
        ));
    }

    /**
     * Submit an interest registration (waitlist) or an authorized partner financing referral inquiry.
     */
    public function apply(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'Unauthorized seller account.');

        $status = Setting::get('seller_finance_status', 'not_available');

        // Case 1: Active Partner - requires formal inquiry fields & regulatory acknowledgement
        if ($status === 'active_partner') {
            $validated = $request->validate([
                'requested_amount' => ['required', 'numeric', 'min:5000', 'max:5000000'],
                'pan_number' => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i'],
                'gstin' => ['nullable', 'string', 'max:20'],
                'annual_turnover' => ['required', 'numeric', 'min:10000'],
                'seller_notes' => ['nullable', 'string', 'max:500'],
                'terms_accepted' => ['required', 'accepted'],
            ], [
                'pan_number.regex' => 'Please provide a valid 10-character Indian PAN format (e.g. ABCDE1234F).',
                'terms_accepted.accepted' => 'You must review and accept the financial partner terms and regulatory disclaimers.',
            ]);

            $providerName = Setting::get('seller_finance_provider_name', 'Authorized NBFC/Banking Partner');

            $application = SellerFinanceApplication::create([
                'seller_id' => $seller->id,
                'application_type' => 'credit_line_inquiry',
                'requested_amount' => $validated['requested_amount'],
                'approved_limit' => 0.00,
                'outstanding_balance' => 0.00,
                'pan_number' => strtoupper($validated['pan_number']),
                'gstin' => $validated['gstin'] ? strtoupper($validated['gstin']) : null,
                'annual_turnover' => $validated['annual_turnover'],
                'status' => 'under_review',
                'provider_name' => $providerName,
                'terms_accepted_at' => now(),
                'seller_notes' => $validated['seller_notes'] ?? null,
            ]);

            return back()->with('success', "Your financing inquiry has been securely submitted to {$providerName}. Underwriting review typically requires 2-3 business days.");
        }

        // Case 2: Not Available or Coming Soon - Express Interest / Waitlist
        $validated = $request->validate([
            'seller_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $existingWaitlist = SellerFinanceApplication::where('seller_id', $seller->id)
            ->where('application_type', 'waitlist')
            ->first();

        if ($existingWaitlist) {
            return back()->with('info', 'Your interest is already registered. We will notify you immediately once an authorized financial program is launched.');
        }

        SellerFinanceApplication::create([
            'seller_id' => $seller->id,
            'application_type' => 'waitlist',
            'status' => 'registered',
            'seller_notes' => $validated['seller_notes'] ?? null,
            'terms_accepted_at' => now(),
        ]);

        return back()->with('success', 'Thank you! Your interest has been registered. You will receive priority notification when licensed financing options become available.');
    }
}
