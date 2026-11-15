@extends('layouts.storefront')

@section('title', 'My Profile & Account - ' . setting('name', 'ALLZONE'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" x-data="{
    activeTab: '{{ $activeTab }}',
    addAddressModal: false,
    editAddressModal: false,
    editingAddress: {
        id: null,
        name: '',
        phone: '',
        line1: '',
        line2: '',
        landmark: '',
        city: '',
        state: '',
        pincode: '',
        type: 'home',
        is_default: false
    },
    openEdit(addr) {
        this.editingAddress = { ...addr };
        this.editAddressModal = true;
    }
}">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 overflow-x-auto no-scrollbar">
        <a href="{{ route('home') }}" class="hover:text-indigo-600 font-medium">Home</a>
        <span class="text-slate-300">/</span>
        <span class="font-bold text-slate-800">My Account</span>
    </nav>

    <!-- Session Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span class="text-emerald-600 text-lg">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm space-y-1 shadow-xs">
            <div class="font-bold flex items-center space-x-1.5">
                <span>⚠️</span>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5 pl-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Header Banner -->
    <div class="p-5 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center space-x-4 sm:space-x-5">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-indigo-600 text-white font-black text-2xl sm:text-3xl flex items-center justify-center border-2 border-white/20 shadow-lg">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-white/20 text-white backdrop-blur-md">
                        ALLZONE CUSTOMER
                    </span>
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <span>✓ Verified</span>
                        </span>
                    @endif
                </div>
                <h1 class="text-xl sm:text-3xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                <p class="text-xs text-slate-300">
                    {{ $user->email }} @if($user->phone)&bull; +91 {{ $user->phone }}@endif
                </p>
            </div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 text-center">
            <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                <span class="text-lg sm:text-xl font-black block text-white">{{ $stats['total_orders'] }}</span>
                <span class="text-[10px] font-semibold text-slate-300">Total Orders</span>
            </div>
            <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                <span class="text-lg sm:text-xl font-black block text-amber-400">{{ $stats['active_orders'] }}</span>
                <span class="text-[10px] font-semibold text-slate-300">Active</span>
            </div>
            <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                <span class="text-lg sm:text-xl font-black block text-teal-300">{{ $stats['saved_addresses'] }}</span>
                <span class="text-[10px] font-semibold text-slate-300">Addresses</span>
            </div>
            <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10">
                <span class="text-lg sm:text-xl font-black block text-rose-300">{{ $stats['wishlist_count'] }}</span>
                <span class="text-[10px] font-semibold text-slate-300">Wishlist</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 flex items-center space-x-2 sm:space-x-8 overflow-x-auto no-scrollbar text-xs sm:text-sm font-bold">
        <button type="button" @click="activeTab = 'overview'"
                :class="activeTab === 'overview' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 hover:text-slate-900 border-transparent'"
                class="py-3 px-2 border-b-2 whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
            <span>📊 Overview</span>
        </button>
        <button type="button" @click="activeTab = 'profile'"
                :class="activeTab === 'profile' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 hover:text-slate-900 border-transparent'"
                class="py-3 px-2 border-b-2 whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
            <span>👤 Personal Details</span>
        </button>
        <button type="button" @click="activeTab = 'addresses'"
                :class="activeTab === 'addresses' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 hover:text-slate-900 border-transparent'"
                class="py-3 px-2 border-b-2 whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
            <span>📍 Delivery Addresses ({{ $addresses->count() }})</span>
        </button>
        <button type="button" @click="activeTab = 'security'"
                :class="activeTab === 'security' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 hover:text-slate-900 border-transparent'"
                class="py-3 px-2 border-b-2 whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
            <span>🔒 Security & Password</span>
        </button>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left 2 Cols: Recent Orders -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center space-x-2">
                        <span>📦 Recent Orders</span>
                    </h3>
                    <a href="{{ route('orders.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                        View All Orders &rarr;
                    </a>
                </div>

                @if($recentOrders->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($recentOrders as $order)
                            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-indigo-300 transition">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono font-bold text-xs text-slate-900">{{ $order->order_number }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $order->status->value === 'delivered' ? 'bg-emerald-50 text-emerald-700' : ($order->status->value === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ str_replace('_', ' ', $order->status->value) }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Placed on {{ $order->created_at->format('M d, Y h:i A') }} &bull; {{ $order->fulfilmentOrders->count() }} package(s)
                                    </p>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-0 border-slate-100">
                                    <div class="text-left sm:text-right">
                                        <span class="text-xs sm:text-sm font-black text-slate-900 block">{{ format_currency($order->total) }}</span>
                                        <span class="text-[10px] text-slate-400 uppercase font-mono">{{ $order->payment_method->value }}</span>
                                    </div>
                                    <a href="{{ route('orders.show', $order->order_number) }}" class="px-3.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white font-bold text-xs transition shadow-2xs">
                                        Details
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center bg-white rounded-3xl border border-slate-200 space-y-2">
                        <span class="text-3xl block">🛍️</span>
                        <h4 class="text-sm font-bold text-slate-800">No orders placed yet</h4>
                        <p class="text-xs text-slate-500">Explore products on ALLZONE and get fast doorstep delivery.</p>
                        <a href="{{ route('home') }}" class="inline-block mt-2 px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-sm">
                            Start Shopping
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Col: Default Address & Quick Links -->
            <div class="space-y-4">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                    📍 Default Delivery Address
                </h3>

                @php
                    $defaultAddr = $addresses->firstWhere('is_default', true) ?? $addresses->first();
                @endphp

                @if($defaultAddr)
                    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700">
                                {{ strtoupper($defaultAddr->type?->value ?? 'HOME') }}
                            </span>
                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                ✓ PRIMARY
                            </span>
                        </div>
                        <div class="space-y-0.5 text-xs text-slate-600">
                            <p class="font-bold text-slate-900 text-sm">{{ $defaultAddr->name }}</p>
                            <p>+91 {{ $defaultAddr->phone }}</p>
                            <p>{{ $defaultAddr->line1 }}</p>
                            @if($defaultAddr->line2)<p>{{ $defaultAddr->line2 }}</p>@endif
                            @if($defaultAddr->landmark)<p class="text-[11px] text-slate-400">Landmark: {{ $defaultAddr->landmark }}</p>@endif
                            <p class="font-medium text-slate-800">{{ $defaultAddr->city }}, {{ $defaultAddr->state }} - {{ $defaultAddr->pincode }}</p>
                        </div>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" @click="activeTab = 'addresses'" class="text-xs font-bold text-indigo-600 hover:underline">
                                Manage All Addresses &rarr;
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-6 text-center bg-white rounded-2xl border border-slate-200 space-y-2 text-xs">
                        <p class="text-slate-500">No saved addresses found.</p>
                        <button type="button" @click="addAddressModal = true" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-sm">
                            + Add Address
                        </button>
                    </div>
                @endif

                <!-- Account Security Shortcut -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-2 text-xs">
                    <h4 class="font-bold text-slate-900">Security & Quick Actions</h4>
                    <ul class="space-y-2 text-slate-600 pt-1">
                        <li>
                            <a href="{{ route('wishlist.index') }}" class="flex items-center justify-between hover:text-indigo-600">
                                <span>❤️ View Wishlist Items</span>
                                <span class="font-bold">{{ $stats['wishlist_count'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('notifications.index') }}" class="flex items-center justify-between hover:text-indigo-600">
                                <span>🔔 Notification Preferences</span>
                                <span>&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <button type="button" @click="activeTab = 'security'" class="text-left w-full hover:text-indigo-600">
                                <span>🔑 Change Password</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB 2: PERSONAL DETAILS -->
    <div x-show="activeTab === 'profile'" class="max-w-2xl bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-5 text-xs sm:text-sm">
            @csrf
            @method('PUT')

            <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                Personal Information
            </h3>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" value="{{ $user->email }}" disabled
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 cursor-not-allowed">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Email cannot be changed directly for security.</span>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mobile Number (10 digits)</label>
                    <div class="flex items-center">
                        <span class="px-3 py-2.5 rounded-l-xl bg-slate-100 border border-r-0 border-slate-200 text-slate-500 font-bold text-xs">+91</span>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="10" placeholder="9876543210"
                               class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-r-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Gender</label>
                    <select name="gender" class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium bg-white">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Date of Birth</label>
                    <input type="date" name="dob" value="{{ old('dob', $customer->dob ? $customer->dob->format('Y-m-d') : '') }}"
                           class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium bg-white">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md transition cursor-pointer">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 3: SAVED ADDRESSES -->
    <div x-show="activeTab === 'addresses'" class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                    Saved Delivery Addresses
                </h3>
                <p class="text-xs text-slate-500">Manage multiple doorstep locations for faster one-click checkout.</p>
            </div>
            <button type="button" @click="addAddressModal = true" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md transition flex items-center space-x-1.5 cursor-pointer">
                <span>+ Add New Address</span>
            </button>
        </div>

        @if($addresses->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($addresses as $addr)
                    <div class="p-5 rounded-2xl bg-white border {{ $addr->is_default ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-200/80' }} shadow-xs flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $addr->type?->value === 'home' ? 'bg-blue-50 text-blue-700' : ($addr->type?->value === 'work' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ strtoupper($addr->type?->value ?? 'HOME') }}
                                </span>
                                @if($addr->is_default)
                                    <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        ✓ DEFAULT
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs text-slate-600 space-y-0.5">
                                <h4 class="font-extrabold text-slate-900 text-sm">{{ $addr->name }}</h4>
                                <p class="font-medium text-slate-700">+91 {{ $addr->phone }}</p>
                                <p class="line-clamp-2">{{ $addr->line1 }}</p>
                                @if($addr->line2)<p>{{ $addr->line2 }}</p>@endif
                                @if($addr->landmark)<p class="text-slate-400 text-[11px]">Near: {{ $addr->landmark }}</p>@endif
                                <p class="font-bold text-slate-900 pt-1">{{ $addr->city }}, {{ $addr->state }} - {{ $addr->pincode }}</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openEdit({{ json_encode($addr) }})" class="font-bold text-indigo-600 hover:underline">
                                    Edit
                                </button>
                                <span class="text-slate-300">&bull;</span>
                                <form method="POST" action="{{ route('customer.address.delete', $addr->id) }}" onsubmit="return confirm('Remove this delivery address?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-bold text-rose-600 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </div>

                            @if(!$addr->is_default)
                                <form method="POST" action="{{ route('customer.address.set-default', $addr->id) }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-slate-500 hover:text-indigo-600">
                                        Set as Default
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-3">
                <span class="text-4xl block">📍</span>
                <h4 class="text-sm font-bold text-slate-800">You don't have any saved addresses yet</h4>
                <p class="text-xs text-slate-500">Save your home, office, and other addresses to speed up checkout.</p>
                <button type="button" @click="addAddressModal = true" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-xs shadow-md">
                    + Add First Address
                </button>
            </div>
        @endif
    </div>

    <!-- TAB 4: SECURITY -->
    <div x-show="activeTab === 'security'" class="max-w-xl bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <form method="POST" action="{{ route('customer.password.update') }}" class="space-y-5 text-xs sm:text-sm">
            @csrf
            @method('PUT')

            <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                Change Password
            </h3>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Current Password *</label>
                <input type="password" name="current_password" required
                       class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">New Password *</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
                <span class="text-[10px] text-slate-400 mt-0.5 block">Minimum 8 characters with numbers and letters.</span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Confirm New Password *</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-indigo-500 font-medium">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md transition cursor-pointer">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- MODAL: ADD ADDRESS -->
    <div x-show="addAddressModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200" @click.away="addAddressModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Add Delivery Address</h3>
                <button type="button" @click="addAddressModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('customer.address.store') }}" class="space-y-3.5 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Name *</label>
                        <input type="text" name="name" value="{{ $user->name }}" required class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phone Number (10 digits) *</label>
                        <input type="text" name="phone" value="{{ $user->phone }}" required maxlength="10" placeholder="9876543210" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Flat / House No. / Building Name *</label>
                    <input type="text" name="line1" required placeholder="e.g. Flat 402, Royal Residency" class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Street Address / Area</label>
                    <input type="text" name="line2" placeholder="e.g. 1st Cross, Indiranagar" class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pincode *</label>
                        <input type="text" name="pincode" required maxlength="6" placeholder="560001" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" required placeholder="Bengaluru" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State *</label>
                        <input type="text" name="state" required placeholder="Karnataka" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nearby Landmark (Optional)</label>
                    <input type="text" name="landmark" placeholder="e.g. Opposite City Hospital" class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="home" checked class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Home</span>
                        </label>
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="work" class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Work</span>
                        </label>
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="other" class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Other</span>
                        </label>
                    </div>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" class="rounded text-indigo-600 h-4 w-4">
                        <span class="font-bold text-slate-700">Set as Primary</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="addAddressModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold shadow-sm">Save Address</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT ADDRESS -->
    <div x-show="editAddressModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200" @click.away="editAddressModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Edit Delivery Address</h3>
                <button type="button" @click="editAddressModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" :action="'/customer/addresses/' + editingAddress.id" class="space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Name *</label>
                        <input type="text" name="name" x-model="editingAddress.name" required class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phone Number *</label>
                        <input type="text" name="phone" x-model="editingAddress.phone" required maxlength="10" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Flat / House No. / Building *</label>
                    <input type="text" name="line1" x-model="editingAddress.line1" required class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Street Address / Area</label>
                    <input type="text" name="line2" x-model="editingAddress.line2" class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pincode *</label>
                        <input type="text" name="pincode" x-model="editingAddress.pincode" required maxlength="6" class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" x-model="editingAddress.city" required class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State *</label>
                        <input type="text" name="state" x-model="editingAddress.state" required class="w-full p-2.5 rounded-xl border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nearby Landmark (Optional)</label>
                    <input type="text" name="landmark" x-model="editingAddress.landmark" class="w-full p-2.5 rounded-xl border border-slate-200">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="home" :checked="editingAddress.type === 'home'" class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Home</span>
                        </label>
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="work" :checked="editingAddress.type === 'work'" class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Work</span>
                        </label>
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="radio" name="type" value="other" :checked="editingAddress.type === 'other'" class="text-indigo-600 focus:ring-0">
                            <span class="font-bold text-slate-700">Other</span>
                        </label>
                    </div>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" :checked="editingAddress.is_default" class="rounded text-indigo-600 h-4 w-4">
                        <span class="font-bold text-slate-700">Primary Default</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="editAddressModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
