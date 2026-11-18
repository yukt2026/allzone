<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display seller bank details settings (Screenshot 8).
     */
    public function bank(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $bankAccount = SellerBankAccount::where('seller_id', $seller->id)
            ->where('is_primary', true)
            ->first();

        if (!$bankAccount) {
            $bankAccount = SellerBankAccount::create([
                'seller_id' => $seller->id,
                'account_holder' => $seller->legal_name ?? $seller->business_name ?? 'DREESTI ENTERPRISES',
                'account_no' => '501008824582',
                'ifsc' => 'HDFC0001234',
                'bank_name' => 'HDFC Bank Ltd',
                'branch_name' => 'Koramangala Branch',
                'is_primary' => true,
                'verified_at' => now(),
            ]);
        }

        return view('seller.settings.bank', compact('seller', 'bankAccount'));
    }

    /**
     * Update seller bank details.
     */
    public function updateBank(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'account_holder' => ['required', 'string', 'max:150'],
            'account_no' => ['required', 'string', 'min:9', 'max:20', 'confirmed'],
            'ifsc' => ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:100'],
        ]);

        $bankAccount = SellerBankAccount::where('seller_id', $seller->id)
            ->where('is_primary', true)
            ->first();

        if ($bankAccount) {
            $bankAccount->update([
                'account_holder' => $validated['account_holder'],
                'account_no' => $validated['account_no'],
                'ifsc' => strtoupper($validated['ifsc']),
                'bank_name' => $validated['bank_name'],
                'verified_at' => now(), // instant verification for demo
            ]);
        } else {
            SellerBankAccount::create([
                'seller_id' => $seller->id,
                'account_holder' => $validated['account_holder'],
                'account_no' => $validated['account_no'],
                'ifsc' => strtoupper($validated['ifsc']),
                'bank_name' => $validated['bank_name'],
                'is_primary' => true,
                'verified_at' => now(),
            ]);
        }

        return back()->with('success', 'Bank account details updated and verified successfully.');
    }

    /**
     * Display seller tax details (GSTIN, PAN).
     */
    public function tax(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        return view('seller.settings.tax', compact('seller'));
    }

    /**
     * Update seller tax details.
     */
    public function updateTax(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'pan' => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
        ]);

        $seller->update([
            'pan' => strtoupper($validated['pan']),
            'gstin' => strtoupper($validated['gstin'] ?? ''),
        ]);

        return back()->with('success', 'Tax details updated successfully.');
    }

    /**
     * Change password view.
     */
    public function password(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        return view('seller.settings.password', compact('seller'));
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Supplier signature view.
     */
    public function signature(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        return view('seller.settings.signature', compact('seller'));
    }

    /**
     * Notifications settings view.
     */
    public function notifications(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        return view('seller.settings.notifications', compact('seller'));
    }
}
