<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\FulfilmentOrder;
use App\Models\SellerSettlement;
use App\Models\Setting;
use App\Services\Settlement\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    /**
     * Display seller wallet, matured earnings balance, and withdrawal ledger.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'No seller profile associated with this account.');

        // Calculation of delivered orders past return window
        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $eligibleCutoff = Carbon::now()->subDays($returnWindowDays);

        $unsettledOrders = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->whereDoesntHave('settlementItem')
            ->with(['commissionEntry'])
            ->get();

        $maturedOrders = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->lte($eligibleCutoff));
        $escrowOrders = $unsettledOrders->filter(fn ($fo) => $fo->delivered_at && $fo->delivered_at->gt($eligibleCutoff));

        $availableBalance = (float) $maturedOrders->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));
        $escrowBalance = (float) $escrowOrders->sum(fn ($fo) => (float) ($fo->commissionEntry?->net_payable ?? $fo->seller_payable));

        $lifetimeSettled = (float) SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PAID, SettlementStatus::SETTLED])
            ->sum('net');

        $pendingPayouts = (float) SellerSettlement::where('seller_id', $seller->id)
            ->whereIn('status', [SettlementStatus::PENDING, SettlementStatus::PROCESSING])
            ->sum('net');

        $settlements = SellerSettlement::query()
            ->where('seller_id', $seller->id)
            ->withCount('items')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();

        // Recent earnings entries
        $recentTransactions = CommissionEntry::query()
            ->whereHas('fulfilmentOrder', fn ($q) => $q->where('seller_id', $seller->id))
            ->with(['fulfilmentOrder.order', 'settlementItem.settlement'])
            ->latest('id')
            ->take(10)
            ->get();

        return view('seller.wallet', compact(
            'seller',
            'availableBalance',
            'escrowBalance',
            'lifetimeSettled',
            'pendingPayouts',
            'settlements',
            'primaryBank',
            'recentTransactions',
            'returnWindowDays',
            'maturedOrders'
        ));
    }

    /**
     * Submit early payout or withdrawal request.
     */
    public function requestWithdrawal(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();
        if (!$primaryBank) {
            return back()->with('error', 'Please link and verify a primary bank account before requesting withdrawals.');
        }

        $returnWindowDays = (int) Setting::get('return_window_days', 7);
        $eligibleCutoff = Carbon::now()->subDays($returnWindowDays);

        $settlement = $this->settlementService->generateForSeller($seller, $eligibleCutoff, Carbon::now());

        if (!$settlement || (float) $settlement->net <= 0) {
            return back()->with('error', 'No matured orders available for immediate withdrawal. Orders must pass the ' . $returnWindowDays . '-day customer return window.');
        }

        if (function_exists('activity')) {
            activity('withdrawal')
                ->causedBy(Auth::user())
                ->performedOn($settlement)
                ->log("Seller requested withdrawal #{$settlement->settlement_number} for ₹" . number_format((float)$settlement->net, 2));
        }

        return back()->with('success', "Withdrawal request #{$settlement->settlement_number} for ₹" . number_format((float)$settlement->net, 2) . " submitted successfully and queued for admin disbursement.");
    }
}
