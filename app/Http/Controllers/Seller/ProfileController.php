<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the seller merchant profile & KYC credential status.
     */
    public function show(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'No seller profile associated with this account.');

        $seller->load([
            'user',
            'bankAccounts',
            'documents',
            'stores',
        ]);

        $primaryBank = $seller->primaryBankAccount ?: $seller->bankAccounts()->first();
        $primaryStore = $seller->stores()->first();

        return view('seller.profile', compact('seller', 'primaryBank', 'primaryStore'));
    }

    /**
     * Update seller profile & contact details.
     */
    public function update(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'category_focus' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
        ]);

        $seller->business_name = $validated['business_name'];
        $seller->legal_name = $validated['legal_name'];
        $seller->category_focus = $validated['category_focus'] ?? $seller->category_focus;
        $seller->save();

        $user = Auth::user();
        $user->phone = $validated['phone'];
        $user->email = $validated['email'];
        $user->name = $validated['business_name'];
        $user->save();

        if (function_exists('activity')) {
            activity('seller_profile')
                ->causedBy($user)
                ->performedOn($seller)
                ->log("Seller merchant profile updated");
        }

        return back()->with('success', 'Merchant profile and registered contact credentials updated successfully.');
    }
}
