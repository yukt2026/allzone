<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerProfileController extends Controller
{
    /**
     * Display Customer Profile, Addresses, and Account Overview.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            $customer = Customer::firstOrCreate(['user_id' => $user->id]);
        }

        $addresses = CustomerAddress::where('user_id', $user->id)
            ->orWhere('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        $recentOrders = Order::where('customer_id', $customer->id)
            ->with(['fulfilmentOrders.seller', 'payments'])
            ->latest('id')
            ->take(5)
            ->get();

        $stats = [
            'total_orders' => Order::where('customer_id', $customer->id)->count(),
            'active_orders' => Order::where('customer_id', $customer->id)
                ->whereIn('status', [
                    \App\Enums\OrderStatus::PENDING,
                    \App\Enums\OrderStatus::PLACED,
                    \App\Enums\OrderStatus::CONFIRMED,
                    \App\Enums\OrderStatus::PROCESSING,
                    \App\Enums\OrderStatus::READY_FOR_PICKUP,
                    \App\Enums\OrderStatus::OUT_FOR_DELIVERY,
                ])
                ->count(),
            'saved_addresses' => $addresses->count(),
            'wishlist_count' => Wishlist::where('customer_id', $customer->id)->count(),
        ];

        return view('storefront.customer.profile', [
            'user' => $user,
            'customer' => $customer,
            'addresses' => $addresses,
            'recentOrders' => $recentOrders,
            'stats' => $stats,
            'activeTab' => $request->query('tab', 'overview'),
        ]);
    }

    /**
     * Update customer personal details (Name, Phone, DOB, Gender).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $customer = $user->customer ?? Customer::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'dob' => ['nullable', 'date', 'before:today'],
        ]);

        $user->name = $validated['name'];
        if (isset($validated['phone'])) {
            $user->phone = $validated['phone'];
        }
        $user->save();

        $customer->update([
            'gender' => $validated['gender'] ?? $customer->gender,
            'dob' => $validated['dob'] ?? $customer->dob,
        ]);

        return redirect()->route('customer.profile', ['tab' => 'profile'])
            ->with('success', 'Profile information updated successfully!');
    }

    /**
     * Update user account security password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('customer.profile', ['tab' => 'security'])
            ->with('success', 'Account password changed successfully!');
    }

    /**
     * Save a new delivery address.
     */
    public function storeAddress(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $customer = $user->customer ?? Customer::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'type' => ['required', 'string', 'in:home,work,other'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default');

        // If this is the user's first address, force it to be default
        $hasExisting = CustomerAddress::where('user_id', $user->id)->exists();
        if (!$hasExisting) {
            $isDefault = true;
        }

        if ($isDefault) {
            CustomerAddress::where('user_id', $user->id)->update(['is_default' => false]);
            CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
        }

        CustomerAddress::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'],
            'type' => AddressType::tryFrom($validated['type']) ?? AddressType::HOME,
            'is_default' => $isDefault,
        ]);

        return redirect()->route('customer.profile', ['tab' => 'addresses'])
            ->with('success', 'New delivery address added successfully!');
    }

    /**
     * Update an existing delivery address.
     */
    public function updateAddress(Request $request, CustomerAddress $address): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($address->user_id === $user->id || $address->customer_id === $user->customer?->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'type' => ['required', 'string', 'in:home,work,other'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            CustomerAddress::where('user_id', $user->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
            if ($user->customer) {
                CustomerAddress::where('customer_id', $user->customer->id)->where('id', '!=', $address->id)->update(['is_default' => false]);
            }
        }

        $address->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'line1' => $validated['line1'],
            'line2' => $validated['line2'] ?? null,
            'landmark' => $validated['landmark'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'pincode' => $validated['pincode'],
            'type' => AddressType::tryFrom($validated['type']) ?? AddressType::HOME,
            'is_default' => $isDefault ? true : $address->is_default,
        ]);

        return redirect()->route('customer.profile', ['tab' => 'addresses'])
            ->with('success', 'Delivery address updated successfully!');
    }

    /**
     * Set address as primary default.
     */
    public function setDefaultAddress(CustomerAddress $address): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($address->user_id === $user->id || $address->customer_id === $user->customer?->id, 403);

        CustomerAddress::where('user_id', $user->id)->update(['is_default' => false]);
        if ($user->customer) {
            CustomerAddress::where('customer_id', $user->customer->id)->update(['is_default' => false]);
        }

        $address->is_default = true;
        $address->save();

        return redirect()->route('customer.profile', ['tab' => 'addresses'])
            ->with('success', 'Default address updated successfully!');
    }

    /**
     * Delete an address.
     */
    public function destroyAddress(CustomerAddress $address): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($address->user_id === $user->id || $address->customer_id === $user->customer?->id, 403);

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, promote the first remaining address to default
        if ($wasDefault) {
            $next = CustomerAddress::where('user_id', $user->id)->first();
            if ($next) {
                $next->is_default = true;
                $next->save();
            }
        }

        return redirect()->route('customer.profile', ['tab' => 'addresses'])
            ->with('success', 'Address removed from your account.');
    }
}
