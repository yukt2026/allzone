@extends('layouts.storefront')

@section('title', 'My Account - ' . setting('name', 'ALLZONE'))

@section('content')
<div class="min-h-screen bg-slate-50/70 py-5 sm:py-8" x-data="{
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center space-x-2 text-xs text-slate-500 overflow-x-auto no-scrollbar whitespace-nowrap">
            <a href="{{ route('home') }}" class="hover:text-blue-600 transition font-medium">Home</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-slate-800">My Account</span>
        </nav>

        <!-- Session Feedback Alerts -->
        @if(session('success'))
            <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <span class="text-emerald-600 text-base font-black">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-base font-bold cursor-pointer">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm space-y-1 shadow-xs">
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

        <!-- Customer Profile Header Card (Clean, Compact, Zero Truncation) -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs p-4 sm:p-6 transition">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 sm:gap-6">
                <!-- User Identity Section -->
                <div class="flex items-start sm:items-center space-x-3.5 sm:space-x-5 min-w-0">
                    <div class="relative shrink-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xl sm:text-2xl flex items-center justify-center shadow-md shadow-blue-500/20 border-2 border-white">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="absolute -bottom-1 -right-1 w-4 h-4 sm:w-4.5 sm:h-4.5 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-[9px] text-white font-bold" title="Account Active">✓</span>
                    </div>

                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                                ALLZONE Member
                            </span>
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span>✓ Verified Customer</span>
                                </span>
                            @endif
                        </div>

                        <!-- Customer Full Name without truncation -->
                        <h1 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight leading-snug break-words">
                            {{ $user->name }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 pt-0.5">
                            <span class="flex items-center gap-1.5 break-all">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span>{{ $user->email }}</span>
                            </span>
                            @if($user->phone)
                                <span class="text-slate-300 hidden sm:inline">&bull;</span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span>+91 {{ $user->phone }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 4 Real Customer Statistics Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 shrink-0 pt-3 lg:pt-0 border-t lg:border-0 border-slate-100">
                    <a href="{{ route('orders.index') }}" class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-blue-50/50 border border-slate-200/80 hover:border-blue-300 transition text-center group">
                        <span class="text-lg sm:text-xl font-black block text-slate-900 group-hover:text-blue-600 transition">{{ $stats['total_orders'] }}</span>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Total Orders</span>
                    </a>

                    <a href="{{ route('orders.index', ['status' => 'processing']) }}" class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-amber-50/50 border border-slate-200/80 hover:border-amber-300 transition text-center group">
                        <span class="text-lg sm:text-xl font-black block text-amber-600 transition">{{ $stats['active_orders'] }}</span>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Active</span>
                    </a>

                    <button type="button" @click="activeTab = 'addresses'" class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-teal-50/50 border border-slate-200/80 hover:border-teal-300 transition text-center group cursor-pointer">
                        <span class="text-lg sm:text-xl font-black block text-teal-600 transition">{{ $stats['saved_addresses'] }}</span>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Addresses</span>
                    </button>

                    <a href="{{ route('wishlist.index') }}" class="p-3 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-50 hover:bg-rose-50/50 border border-slate-200/80 hover:border-rose-300 transition text-center group">
                        <span class="text-lg sm:text-xl font-black block text-rose-500 transition">{{ $stats['wishlist_count'] }}</span>
                        <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">Wishlist</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Account Navigation Tabs Bar (Scrollable on Mobile, Zero Cutoff) -->
        <div class="border-b border-slate-200 flex items-center space-x-1 sm:space-x-6 overflow-x-auto no-scrollbar text-xs sm:text-sm font-bold">
            <button type="button" @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'text-blue-600 border-blue-600 bg-blue-50/40' : 'text-slate-500 hover:text-slate-900 border-transparent hover:bg-slate-100/50'"
                    class="py-2.5 sm:py-3 px-3 sm:px-4 border-b-2 rounded-t-xl sm:rounded-none whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
                <span>📊 Overview</span>
            </button>
            <button type="button" @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'text-blue-600 border-blue-600 bg-blue-50/40' : 'text-slate-500 hover:text-slate-900 border-transparent hover:bg-slate-100/50'"
                    class="py-2.5 sm:py-3 px-3 sm:px-4 border-b-2 rounded-t-xl sm:rounded-none whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
                <span>👤 Personal Details</span>
            </button>
            <button type="button" @click="activeTab = 'addresses'"
                    :class="activeTab === 'addresses' ? 'text-blue-600 border-blue-600 bg-blue-50/40' : 'text-slate-500 hover:text-slate-900 border-transparent hover:bg-slate-100/50'"
                    class="py-2.5 sm:py-3 px-3 sm:px-4 border-b-2 rounded-t-xl sm:rounded-none whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
                <span>📍 Delivery Addresses ({{ $addresses->count() }})</span>
            </button>
            <button type="button" @click="activeTab = 'security'"
                    :class="activeTab === 'security' ? 'text-blue-600 border-blue-600 bg-blue-50/40' : 'text-slate-500 hover:text-slate-900 border-transparent hover:bg-slate-100/50'"
                    class="py-2.5 sm:py-3 px-3 sm:px-4 border-b-2 rounded-t-xl sm:rounded-none whitespace-nowrap transition cursor-pointer flex items-center space-x-1.5">
                <span>🔒 Security &amp; Password</span>
            </button>
        </div>

        <!-- TAB 1: OVERVIEW -->
        <div x-show="activeTab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6 items-start">
                
                <!-- Left 2 Cols: Recent Orders -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center space-x-2">
                            <span>📦 Recent Orders</span>
                        </h3>
                        <a href="{{ route('orders.index') }}" class="text-xs font-bold text-blue-600 hover:underline">
                            View All Orders &rarr;
                        </a>
                    </div>

                    @if($recentOrders->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($recentOrders as $order)
                                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-blue-400 hover:shadow-sm transition">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono font-bold text-xs text-slate-900">#{{ $order->order_number }}</span>
                                            <x-order-status-badge :status="$order->status" size="sm" />
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Placed on {{ $order->created_at->format('M d, Y h:i A') }} &bull; {{ $order->fulfilmentOrders->count() }} package(s)
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 pt-2.5 sm:pt-0 border-t sm:border-0 border-slate-100">
                                        <div class="text-left sm:text-right">
                                            <span class="text-xs sm:text-sm font-black text-slate-900 block">₹{{ number_format((float) $order->total) }}</span>
                                            <span class="text-[10px] text-slate-400 uppercase font-mono">{{ strtoupper($order->payment_method->value ?? 'Online') }} ({{ strtoupper($order->payment_status->value ?? 'Paid') }})</span>
                                        </div>
                                        <a href="{{ route('orders.show', $order->order_number) }}" class="px-3.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white font-bold text-xs transition shadow-2xs whitespace-nowrap">
                                            Details &amp; Track &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center bg-white rounded-2xl sm:rounded-3xl border border-slate-200 space-y-2">
                            <span class="text-3xl block">🛍️</span>
                            <h4 class="text-sm font-bold text-slate-800">No orders placed yet</h4>
                            <p class="text-xs text-slate-500">Explore electronics, appliances, and gadgets on ALLZONE.</p>
                            <a href="{{ route('home') }}" class="inline-block mt-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                                Start Shopping
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Right Col: Primary Delivery Address & Quick Shortcuts -->
                <div class="space-y-4">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                        📍 Primary Delivery Address
                    </h3>

                    @php
                        $defaultAddr = $addresses->firstWhere('is_default', true) ?? $addresses->first();
                    @endphp

                    @if($defaultAddr)
                        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ strtoupper($defaultAddr->type?->value ?? 'HOME') }}
                                </span>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                    ✓ PRIMARY
                                </span>
                            </div>
                            <div class="space-y-0.5 text-xs text-slate-600 leading-relaxed">
                                <p class="font-bold text-slate-900 text-sm">{{ $defaultAddr->name }}</p>
                                <p class="font-medium text-slate-700">+91 {{ $defaultAddr->phone }}</p>
                                <p class="text-slate-600">{{ $defaultAddr->line1 }}</p>
                                @if($defaultAddr->line2)<p class="text-slate-600">{{ $defaultAddr->line2 }}</p>@endif
                                @if($defaultAddr->landmark)<p class="text-[11px] text-slate-400">Landmark: {{ $defaultAddr->landmark }}</p>@endif
                                <p class="font-bold text-slate-900 pt-0.5">{{ $defaultAddr->city }}, {{ $defaultAddr->state }} - {{ $defaultAddr->pincode }}</p>
                            </div>
                            <div class="pt-2 border-t border-slate-100">
                                <button type="button" @click="activeTab = 'addresses'" class="text-xs font-bold text-blue-600 hover:underline cursor-pointer">
                                    Manage All Addresses ({{ $addresses->count() }}) &rarr;
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="p-6 text-center bg-white rounded-2xl border border-slate-200 space-y-2 text-xs">
                            <p class="text-slate-500">No saved addresses found.</p>
                            <button type="button" @click="addAddressModal = true" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-sm cursor-pointer">
                                + Add Address
                            </button>
                        </div>
                    @endif

                    <!-- Account Quick Shortcuts -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs space-y-2 text-xs">
                        <h4 class="font-bold text-slate-900">Quick Shortcuts</h4>
                        <ul class="space-y-2.5 text-slate-600 pt-1">
                            <li>
                                <a href="{{ route('wishlist.index') }}" class="flex items-center justify-between hover:text-blue-600 transition">
                                    <span class="flex items-center gap-2">❤️ <span>Saved Wishlist</span></span>
                                    <span class="font-bold bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full text-[10px]">{{ $stats['wishlist_count'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('notifications.index') }}" class="flex items-center justify-between hover:text-blue-600 transition">
                                    <span class="flex items-center gap-2">🔔 <span>Notifications Center</span></span>
                                    <span class="text-slate-400">&rarr;</span>
                                </a>
                            </li>
                            <li>
                                <button type="button" @click="activeTab = 'security'" class="text-left w-full hover:text-blue-600 transition flex items-center justify-between cursor-pointer">
                                    <span class="flex items-center gap-2">🔑 <span>Change Password</span></span>
                                    <span class="text-slate-400">&rarr;</span>
                                </button>
                            </li>
                            <li>
                                <a href="{{ route('orders.index') }}" class="flex items-center justify-between hover:text-blue-600 transition">
                                    <span class="flex items-center gap-2">📄 <span>Order History &amp; Invoices</span></span>
                                    <span class="text-slate-400">&rarr;</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: PERSONAL DETAILS -->
        <div x-show="activeTab === 'profile'" class="max-w-2xl bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs">
            <form method="POST" action="{{ route('customer.profile.update') }}" class="space-y-4 sm:space-y-5 text-xs sm:text-sm">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                        Personal Information
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Manage your personal details for orders and notifications.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-blue-500 focus:border-blue-500 font-medium">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Address</label>
                        <input type="email" value="{{ $user->email }}" disabled
                               class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 cursor-not-allowed">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Email cannot be changed directly.</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Mobile Number (10 digits)</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2.5 rounded-l-xl bg-slate-100 border border-r-0 border-slate-200 text-slate-500 font-bold text-xs">+91</span>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="10" placeholder="9876543210"
                                   class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-r-xl border border-slate-200 focus:ring-blue-500 focus:border-blue-500 font-medium">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Gender</label>
                        <select name="gender" class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-xl border border-slate-200 focus:ring-blue-500 focus:border-blue-500 font-medium bg-white">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date of Birth</label>
                        <input type="date" name="dob" value="{{ old('dob', $customer->dob ? $customer->dob->format('Y-m-d') : '') }}"
                               class="w-full text-xs sm:text-sm py-2.5 px-3 rounded-xl border border-slate-200 focus:ring-blue-500 focus:border-blue-500 font-medium bg-white">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- TAB 3: SAVED ADDRESSES -->
        <div x-show="activeTab === 'addresses'" class="space-y-5 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                        Saved Delivery Addresses
                    </h3>
                    <p class="text-xs text-slate-500">Manage multiple doorstep locations for faster one-click checkout.</p>
                </div>
                <button type="button" @click="addAddressModal = true" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer self-start sm:self-auto">
                    <span>+ Add New Address</span>
                </button>
            </div>

            @if($addresses->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($addresses as $addr)
                        <div class="p-4 sm:p-5 rounded-2xl bg-white border {{ $addr->is_default ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-slate-200/90' }} shadow-xs flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider {{ $addr->type?->value === 'home' ? 'bg-blue-50 text-blue-700' : ($addr->type?->value === 'work' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ strtoupper($addr->type?->value ?? 'HOME') }}
                                    </span>
                                    @if($addr->is_default)
                                        <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                            ✓ DEFAULT
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-slate-600 space-y-0.5 leading-relaxed">
                                    <h4 class="font-extrabold text-slate-900 text-sm">{{ $addr->name }}</h4>
                                    <p class="font-medium text-slate-700">+91 {{ $addr->phone }}</p>
                                    <p class="line-clamp-2">{{ $addr->line1 }}</p>
                                    @if($addr->line2)<p>{{ $addr->line2 }}</p>@endif
                                    @if($addr->landmark)<p class="text-slate-400 text-[11px]">Near: {{ $addr->landmark }}</p>@endif
                                    <p class="font-bold text-slate-900 pt-0.5">{{ $addr->city }}, {{ $addr->state }} - {{ $addr->pincode }}</p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div class="flex items-center space-x-2">
                                    <button type="button" @click="openEdit({{ json_encode($addr) }})" class="font-bold text-blue-600 hover:underline cursor-pointer">
                                        Edit
                                    </button>
                                    <span class="text-slate-300">&bull;</span>
                                    <form method="POST" action="{{ route('customer.address.delete', $addr->id) }}" onsubmit="return confirm('Remove this delivery address?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-bold text-rose-600 hover:underline cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                @if(!$addr->is_default)
                                    <form method="POST" action="{{ route('customer.address.set-default', $addr->id) }}">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-slate-500 hover:text-blue-600 cursor-pointer">
                                            Set as Default
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-10 sm:p-12 text-center bg-white rounded-2xl sm:rounded-3xl border border-slate-200 space-y-3">
                    <span class="text-4xl block">📍</span>
                    <h4 class="text-sm font-bold text-slate-800">You don't have any saved addresses yet</h4>
                    <p class="text-xs text-slate-500">Save your home, office, and other addresses to speed up checkout.</p>
                    <button type="button" @click="addAddressModal = true" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md cursor-pointer">
                        + Add First Address
                    </button>
                </div>
            @endif
        </div>

        <!-- TAB 4: SECURITY -->
        <div x-show="activeTab === 'security'" class="max-w-xl bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 p-5 sm:p-7 shadow-xs">
            <form method="POST" action="{{ route('customer.password.update') }}" class="space-y-4 sm:space-y-5 text-xs sm:text-sm">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">
                        Change Password
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ensure your account is protected with a strong, secure password.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Current Password *</label>
                    <input type="password" name="current_password" required
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-blue-500 font-medium">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">New Password *</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-blue-500 font-medium">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Minimum 8 characters with numbers and letters.</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full text-xs sm:text-sm py-2.5 px-3.5 rounded-xl border border-slate-200 focus:ring-blue-500 font-medium">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition cursor-pointer">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- MODAL: ADD NEW ADDRESS -->
    <div x-show="addAddressModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="addAddressModal = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 my-8">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900">Add New Delivery Address</h3>
                <button type="button" @click="addAddressModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" action="{{ route('customer.address.store') }}" class="space-y-3.5 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Name *</label>
                        <input type="text" name="name" required placeholder="Full Name"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Phone *</label>
                        <input type="text" name="phone" required maxlength="10" placeholder="10-digit mobile"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">House / Flat / Street *</label>
                    <input type="text" name="line1" required placeholder="e.g. Flat 402, Sunshine Apartments"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Area / Locality</label>
                    <input type="text" name="line2" placeholder="e.g. 100 Feet Road, Indiranagar"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" required placeholder="Bengaluru"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State *</label>
                        <input type="text" name="state" required placeholder="Karnataka"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">PIN Code *</label>
                        <input type="text" name="pincode" required maxlength="6" placeholder="560001"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nearby Landmark</label>
                    <input type="text" name="landmark" placeholder="e.g. Opposite Metro Pillar 140"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Address Type</label>
                        <div class="flex items-center space-x-3">
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="home" checked class="text-blue-600">
                                <span>Home</span>
                            </label>
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="work" class="text-blue-600">
                                <span>Work</span>
                            </label>
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="other" class="text-blue-600">
                                <span>Other</span>
                            </label>
                        </div>
                    </div>

                    <label class="inline-flex items-center space-x-1.5 cursor-pointer mt-3">
                        <input type="checkbox" name="is_default" value="1" class="rounded text-blue-600">
                        <span class="font-bold text-slate-700">Set as Primary</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="addAddressModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md cursor-pointer">
                        Save Address
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT ADDRESS -->
    <div x-show="editAddressModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.outside="editAddressModal = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 my-8">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900">Edit Delivery Address</h3>
                <button type="button" @click="editAddressModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form method="POST" :action="'/customer/address/' + editingAddress.id" class="space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Name *</label>
                        <input type="text" name="name" x-model="editingAddress.name" required
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact Phone *</label>
                        <input type="text" name="phone" x-model="editingAddress.phone" required maxlength="10"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">House / Flat / Street *</label>
                    <input type="text" name="line1" x-model="editingAddress.line1" required
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Area / Locality</label>
                    <input type="text" name="line2" x-model="editingAddress.line2"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">City *</label>
                        <input type="text" name="city" x-model="editingAddress.city" required
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State *</label>
                        <input type="text" name="state" x-model="editingAddress.state" required
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">PIN Code *</label>
                        <input type="text" name="pincode" x-model="editingAddress.pincode" required maxlength="6"
                               class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nearby Landmark</label>
                    <input type="text" name="landmark" x-model="editingAddress.landmark"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Address Type</label>
                        <div class="flex items-center space-x-3">
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="home" :checked="editingAddress.type === 'home'" class="text-blue-600">
                                <span>Home</span>
                            </label>
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="work" :checked="editingAddress.type === 'work'" class="text-blue-600">
                                <span>Work</span>
                            </label>
                            <label class="inline-flex items-center space-x-1 cursor-pointer">
                                <input type="radio" name="type" value="other" :checked="editingAddress.type === 'other'" class="text-blue-600">
                                <span>Other</span>
                            </label>
                        </div>
                    </div>

                    <label class="inline-flex items-center space-x-1.5 cursor-pointer mt-3">
                        <input type="checkbox" name="is_default" value="1" :checked="editingAddress.is_default" class="rounded text-blue-600">
                        <span class="font-bold text-slate-700">Set as Primary</span>
                    </label>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button" @click="editAddressModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-md cursor-pointer">
                        Update Address
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
