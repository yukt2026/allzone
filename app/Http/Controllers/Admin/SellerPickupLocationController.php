<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerPickupLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SellerPickupLocationController extends Controller
{
    /**
     * View and audit all seller pickup addresses and shipping configurations.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $sellerId = $request->query('seller_id');

        $query = SellerPickupLocation::query()
            ->with(['seller.user', 'verifiedBy'])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nickname', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('pincode', 'like', "%{$search}%")
                  ->orWhere('shiprocket_pickup_name', 'like', "%{$search}%")
                  ->orWhereHas('seller', fn ($sq) => $sq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status === 'verified') {
            $query->where('is_verified', true);
        } elseif ($status === 'pending') {
            $query->where('is_verified', false)->where('status', '!=', 'rejected');
        } elseif ($status === 'rejected') {
            $query->where('status', 'rejected');
        } elseif ($status === 'non_serviceable') {
            $query->where('is_serviceable', false);
        }

        if ($sellerId) {
            $query->where('seller_id', (int) $sellerId);
        }

        $locations = $query->paginate(20)->withQueryString();

        $metrics = [
            'total_locations' => SellerPickupLocation::count(),
            'verified' => SellerPickupLocation::where('is_verified', true)->count(),
            'pending' => SellerPickupLocation::where('is_verified', false)->where('status', '!=', 'rejected')->count(),
            'rejected' => SellerPickupLocation::where('status', 'rejected')->count(),
        ];

        $sellers = Seller::query()->orderBy('business_name')->get(['id', 'business_name']);

        return view('admin.shipping.pickup-locations.index', compact('locations', 'metrics', 'sellers', 'search', 'status', 'sellerId'));
    }

    /**
     * Verify and approve a seller pickup address for automated Shiprocket logistics.
     */
    public function verify(Request $request, SellerPickupLocation $location): RedirectResponse
    {
        $location->is_verified = true;
        $location->status = 'active';
        $location->verified_at = now();
        $location->verified_by = Auth::id();
        $location->rejection_reason = null;

        if (empty($location->shiprocket_pickup_name)) {
            $location->shiprocket_pickup_name = strtoupper('WH_' . $location->seller_id . '_' . $location->pincode);
        }

        $location->save();

        return back()->with('success', "Pickup address '{$location->nickname}' for seller '{$location->seller?->business_name}' verified successfully.");
    }

    /**
     * Reject a seller pickup address with reason.
     */
    public function reject(Request $request, SellerPickupLocation $location): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $location->is_verified = false;
        $location->status = 'rejected';
        $location->rejection_reason = $validated['rejection_reason'];
        $location->save();

        return back()->with('success', "Pickup address '{$location->nickname}' rejected: {$validated['rejection_reason']}");
    }

    /**
     * Update location logistics configuration.
     */
    public function update(Request $request, SellerPickupLocation $location): RedirectResponse
    {
        $validated = $request->validate([
            'shiprocket_pickup_name' => ['nullable', 'string', 'max:100'],
            'is_serviceable' => ['nullable', 'boolean'],
        ]);

        $location->update([
            'shiprocket_pickup_name' => $validated['shiprocket_pickup_name'] ?? $location->shiprocket_pickup_name,
            'is_serviceable' => $request->boolean('is_serviceable'),
        ]);

        return back()->with('success', "Pickup location configuration updated for '{$location->nickname}'.");
    }
}
