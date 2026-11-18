@extends('layouts.dashboard', ['portal' => 'admin', 'title' => ($customer->user?->name ?? 'Customer') . ' - Customer Dossier'])

@section('content')
<div class="space-y-6" x-data="customerDetail('{{ $activeTab }}')">
    <!-- Top Header Breadcrumb & Profile Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600 transition">Command Central</a>
                <span>&bull;</span>
                <a href="{{ route('admin.customers.index') }}" class="hover:text-emerald-600 transition">Customers</a>
                <span>&bull;</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $customer->user?->name ?? 'Customer' }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ $customer->user?->name ?? 'Customer Account' }}
                </h1>
                @php
                    $uStatus = $customer->user?->status;
                    $statusStr = $uStatus instanceof \App\Enums\UserStatus ? $uStatus->value : (string) ($uStatus ?? 'active');
                    $statusLabel = $uStatus instanceof \App\Enums\UserStatus ? $uStatus->label() : ucfirst($statusStr);
                    $isActive = ($statusStr === 'active');
                    $badgeClass = $uStatus instanceof \App\Enums\UserStatus 
                        ? $uStatus->badgeClass() 
                        : ($isActive 
                            ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300' 
                            : 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300');
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
                    {{ $statusLabel }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Customer ID: <strong class="font-mono text-slate-800 dark:text-slate-200">#{{ $customer->id }}</strong> &bull; Member Since: {{ $customer->created_at->format('M d, Y h:i A') }}
            </p>
        </div>

        <!-- Top Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @can('reviewSuspicious', $customer)
                <button 
                    type="button" 
                    @click="$dispatch('open-modal', 'suspicious-review-modal')"
                    class="px-3.5 py-2 text-xs font-bold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 border border-slate-200 dark:border-slate-700 transition flex items-center"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Risk Review
                </button>
            @endcan

            @if($isActive)
                @can('restrict', $customer)
                    <button 
                        type="button" 
                        @click="$dispatch('open-modal', 'restrict-customer-modal')"
                        class="px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Restrict Account
                    </button>
                @endcan
            @else
                @can('reactivate', $customer)
                    <button 
                        type="button" 
                        @click="$dispatch('open-modal', 'reactivate-customer-modal')"
                        class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reactivate Account
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- Suspicious Risk Signal Alerts Banner -->
    @if(!empty($details['fraud_signals']))
        <div class="space-y-2">
            @foreach($details['fraud_signals'] as $signal)
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-start space-x-3">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <strong class="font-bold">Security &amp; Fraud Signal Detected:</strong> {{ $signal['message'] }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Account Restricted Alert Banner -->
    @if(!$isActive)
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-xs text-rose-900 dark:text-rose-200 flex items-start space-x-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <div>
                <strong class="font-bold">Account Restricted:</strong> This customer account is currently suspended from checkout and placing new orders. Use the Reactivate button above to restore standard purchasing permissions.
            </div>
        </div>
    @endif

    <!-- 6 KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <x-stat-card 
            label="Lifetime Spend" 
            :value="'₹' . number_format($details['lifetime_spend'], 2)" 
            change="Cumulative Purchases" 
            changeType="neutral" 
            color="primary"
        />
        <x-stat-card 
            label="Total Orders" 
            :value="number_format($details['orders_count'])" 
            change="Orders Placed" 
            changeType="up" 
            color="emerald"
        />
        <x-stat-card 
            label="Delivered Orders" 
            :value="number_format($details['delivered_orders_count'])" 
            change="Fulfilled" 
            changeType="neutral" 
            color="sky"
        />
        <x-stat-card 
            label="Cancelled Orders" 
            :value="number_format($details['cancelled_orders_count'])" 
            change="Aborted" 
            changeType="neutral" 
            color="amber"
        />
        <x-stat-card 
            label="Returns Filed" 
            :value="number_format($details['returns_count'])" 
            change="RMA Claims" 
            changeType="neutral" 
            color="rose"
        />
        <x-stat-card 
            label="Total Refunds" 
            :value="'₹' . number_format($details['refunds_total'], 2)" 
            change="Settled Back" 
            changeType="neutral" 
            color="indigo"
        />
    </div>

    <!-- 9 Navigation Tabs Bar -->
    <div class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 rounded-2xl p-1.5 shadow-xs">
        <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-slate-200 dark:scrollbar-thumb-slate-700">
            <nav class="flex space-x-1 sm:space-x-1.5 min-w-max p-1" aria-label="Customer detail navigation tabs">
                @php
                    $tabList = [
                        'profile' => [
                            'label' => 'Profile', 
                            'badge' => null,
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
                        ],
                        'addresses' => [
                            'label' => 'Addresses', 
                            'badge' => $customer->addresses->count(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
                        ],
                        'orders' => [
                            'label' => 'Orders', 
                            'badge' => $orders->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>'
                        ],
                        'payments' => [
                            'label' => 'Payment History', 
                            'badge' => $payments->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'
                        ],
                        'refunds' => [
                            'label' => 'Refunds', 
                            'badge' => $refunds->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        ],
                        'returns' => [
                            'label' => 'Returns & Claims', 
                            'badge' => $returns->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                        ],
                        'tickets' => [
                            'label' => 'Support Tickets', 
                            'badge' => $tickets->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'
                        ],
                        'reviews' => [
                            'label' => 'Reviews', 
                            'badge' => $reviews->total(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>'
                        ],
                        'activity' => [
                            'label' => 'Account Activity', 
                            'badge' => $activityLogs->count(),
                            'svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        ],
                    ];
                @endphp

                @foreach($tabList as $key => $tab)
                    <button 
                        type="button" 
                        id="tab-btn-{{ $key }}"
                        @click="setTab('{{ $key }}')"
                        :class="activeTab === '{{ $key }}' 
                            ? 'bg-emerald-600 text-white shadow-xs font-bold' 
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'"
                        class="px-3.5 py-2 rounded-xl text-xs transition-all flex items-center space-x-1.5 shrink-0 focus:outline-hidden"
                    >
                        <span class="shrink-0" :class="activeTab === '{{ $key }}' ? 'text-white' : 'text-slate-400 dark:text-slate-500'">
                            {!! $tab['svg'] !!}
                        </span>
                        <span>{{ $tab['label'] }}</span>
                        @if($tab['badge'] !== null)
                            <span 
                                :class="activeTab === '{{ $key }}' 
                                    ? 'bg-emerald-700/80 text-white border-transparent' 
                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors ml-1"
                            >
                                {{ $tab['badge'] }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: PROFILE & IDENTITY -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'profile'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Account Identity & Authentication -->
            <x-card title="Profile &amp; Authentication Credentials" subtitle="Primary account records, verification milestones, and security status">
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Full Name</span>
                        <strong class="text-slate-900 dark:text-white">{{ $customer->user?->name ?? 'Guest Customer' }}</strong>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Email Address</span>
                        <span class="font-mono text-slate-900 dark:text-white">{{ $customer->user?->email ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Mobile Phone</span>
                        <span class="font-mono text-slate-900 dark:text-white">{{ $customer->user?->phone ?? 'Not provided' }}</span>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Gender</span>
                        <strong class="text-slate-900 dark:text-white">{{ ucfirst($customer->gender ?? 'Unspecified') }}</strong>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Date of Birth</span>
                        <strong class="text-slate-900 dark:text-white">{{ $customer->dob ? $customer->dob->format('M d, Y') : 'Not provided' }}</strong>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Wallet Balance</span>
                        <strong class="font-mono text-emerald-600 dark:text-emerald-400">₹{{ number_format((float) ($customer->user?->wallet_balance ?? 0), 2) }}</strong>
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Phone Verification</span>
                        @if($customer->user?->phone_verified_at)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Verified ({{ $customer->user->phone_verified_at->format('M d, Y') }})
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Unverified
                            </span>
                        @endif
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Email Verification</span>
                        @if($customer->user?->email_verified_at)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Verified ({{ $customer->user->email_verified_at->format('M d, Y') }})
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                Unverified
                            </span>
                        @endif
                    </div>

                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-400 font-medium">Last Login</span>
                        <span class="text-slate-700 dark:text-slate-300">
                            {{ $customer->user?->last_login_at ? $customer->user->last_login_at->format('M d, Y h:i A') : 'No recorded login' }}
                        </span>
                    </div>
                </div>
            </x-card>

            <!-- Default Delivery Location & Shopping Profile -->
            <div class="space-y-6">
                <x-card title="Default Delivery Address" subtitle="Primary shipping destination on file">
                    @if($customer->defaultAddress)
                        <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $customer->defaultAddress->name }} ({{ $customer->defaultAddress->phone }})</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Primary Address
                                </span>
                            </div>
                            <div class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                {{ $customer->defaultAddress->line1 }}
                                @if($customer->defaultAddress->line2), {{ $customer->defaultAddress->line2 }}@endif
                                <br>
                                {{ $customer->defaultAddress->city }}, {{ $customer->defaultAddress->state }} - <strong class="font-mono text-slate-900 dark:text-white">{{ $customer->defaultAddress->pincode }}</strong>
                            </div>
                            <div class="text-[11px] text-slate-400 pt-1">
                                Address Type: {{ $customer->defaultAddress->type instanceof \App\Enums\AddressType ? $customer->defaultAddress->type->label() : ucfirst((string)($customer->defaultAddress->type ?? 'Home')) }}
                            </div>
                        </div>
                    @else
                        <div class="text-xs text-slate-400 py-6 text-center">
                            No default address configured.
                        </div>
                    @endif
                </x-card>

                <!-- Privacy & Confidentiality Notice -->
                <x-card title="Data Privacy & Policy Compliance" subtitle="Customer data security standards">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 space-y-1.5">
                        <p class="font-bold text-slate-900 dark:text-white">Security Protections Active:</p>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Under ALLZONE customer protection rules, plaintext passwords and raw financial payment credentials (card CVV, full card numbers) are never stored or exposed to platform staff. All administrative profile edits, restrictions, and reviews are permanently recorded in the immutable audit ledger.
                        </p>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: ADDRESSES -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'addresses'" class="space-y-6">
        <x-card title="Saved Customer Delivery Addresses" subtitle="Registered physical destinations for courier and hyper-local dispatches">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($customer->addresses as $addr)
                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $addr->name }} ({{ $addr->phone }})</span>
                            <div class="flex items-center space-x-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ $addr->type instanceof \App\Enums\AddressType ? $addr->type->label() : ucfirst((string)($addr->type ?? 'Home')) }}
                                </span>
                                @if($addr->is_default)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Default
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ $addr->line1 }}
                            @if($addr->line2), {{ $addr->line2 }}@endif
                            <br>
                            {{ $addr->city }}, {{ $addr->state }} - <strong class="font-mono text-slate-900 dark:text-white">{{ $addr->pincode }}</strong>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-xs text-slate-400 text-center py-8">
                        No delivery addresses saved for this customer.
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: ORDERS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'orders'" class="space-y-6">
        <x-card title="Order Purchase History" subtitle="Full chronological record of marketplace and quick commerce orders">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Order #</th>
                            <th class="py-2.5 px-4">Date</th>
                            <th class="py-2.5 px-4 text-center">Items</th>
                            <th class="py-2.5 px-4 text-center">Payment Status</th>
                            <th class="py-2.5 px-4 text-center">Fulfilment Status</th>
                            <th class="py-2.5 px-4 text-right">Total Amount</th>
                            <th class="py-2.5 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($orders as $order)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $order->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono">
                                    {{ $order->items->count() }} item(s)
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @php
                                        $payStatus = $order->payment_status instanceof \App\Enums\PaymentStatus ? $order->payment_status->value : (string) ($order->payment_status ?? 'pending');
                                        $payClass = match($payStatus) {
                                            'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'failed' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $payClass }}">
                                        {{ ucfirst($payStatus) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <x-order-status-badge :status="$order->status" />
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format((float)$order->total, 2) }}
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 transition">
                                        View Order
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No orders placed by this customer yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$orders" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: PAYMENT HISTORY -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'payments'" class="space-y-6">
        <x-card title="Payment Transactions" subtitle="Payment gateway transactions, online settlements, and COD collections">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Payment ID</th>
                            <th class="py-2.5 px-4">Order #</th>
                            <th class="py-2.5 px-4">Gateway</th>
                            <th class="py-2.5 px-4">Method</th>
                            <th class="py-2.5 px-4 text-right">Amount</th>
                            <th class="py-2.5 px-4 text-center">Status</th>
                            <th class="py-2.5 px-4 text-right">Verified At</th>
                            <th class="py-2.5 px-6 text-right">Gateway Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($payments as $payment)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $payment->id }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    <a href="{{ $payment->order ? route('admin.orders.show', $payment->order) : '#' }}" class="hover:underline">
                                        {{ $payment->order?->order_number ?? '#' . $payment->order_id }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">
                                    {{ ucfirst($payment->gateway ?? 'Standard') }}
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $methodStr = $payment->method instanceof \BackedEnum ? $payment->method->value : (string) ($payment->method ?? 'UPI');
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ strtoupper($methodStr) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format((float)$payment->amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @php
                                        $pStatus = $payment->status instanceof \BackedEnum ? $payment->status->value : (string) ($payment->status ?? 'pending');
                                        $pClass = match($pStatus) {
                                            'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'failed' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $pClass }}">
                                        {{ ucfirst($pStatus) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-500">
                                    {{ $payment->verified_at ? $payment->verified_at->format('M d, Y H:i') : 'Unverified' }}
                                </td>
                                <td class="py-3 px-6 text-right font-mono text-[11px] text-slate-500">
                                    {{ $payment->gateway_payment_id ?? ($payment->gateway_order_id ?? '—') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No payment transaction records found for this customer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$payments" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 5: REFUNDS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'refunds'" class="space-y-6">
        <x-card title="Processed & Pending Customer Refunds" subtitle="Reversal distributions against returned items or cancelled pre-paid orders">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Refund ID</th>
                            <th class="py-2.5 px-4">Order #</th>
                            <th class="py-2.5 px-4 text-right">Refund Amount</th>
                            <th class="py-2.5 px-4">Reason / Notes</th>
                            <th class="py-2.5 px-4 text-center">Status</th>
                            <th class="py-2.5 px-4 text-right">Processed Date</th>
                            <th class="py-2.5 px-6 text-right">Gateway Refund ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($refunds as $refund)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $refund->id }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $refund->payment?->order?->order_number ?? '#' . $refund->payment_id }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-emerald-600 dark:text-emerald-400">
                                    ₹{{ number_format((float)$refund->amount, 2) }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-xs truncate">
                                    {{ $refund->reason ?? 'Customer return refund' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @php
                                        $rfStatus = $refund->status instanceof \BackedEnum ? $refund->status->value : (string) ($refund->status ?? 'pending');
                                        $rfClass = match($rfStatus) {
                                            'completed', 'processed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'failed' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $rfClass }}">
                                        {{ ucfirst($rfStatus) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-500">
                                    {{ $refund->processed_at ? $refund->processed_at->format('M d, Y H:i') : ($refund->created_at->format('M d, Y')) }}
                                </td>
                                <td class="py-3 px-6 text-right font-mono text-[11px] text-slate-500">
                                    {{ $refund->gateway_refund_id ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No refund transactions recorded for this customer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$refunds" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 6: RETURNS & CLAIMS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'returns'" class="space-y-6">
        <x-card title="Product Returns &amp; RMA Claims" subtitle="Buyer-initiated returns, pickup dispatches, and QC assessments">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Return #</th>
                            <th class="py-2.5 px-4">Item Details</th>
                            <th class="py-2.5 px-4">Return Reason</th>
                            <th class="py-2.5 px-4 text-center">QC Status</th>
                            <th class="py-2.5 px-4 text-center">Return Status</th>
                            <th class="py-2.5 px-4 text-right">Refund Amount</th>
                            <th class="py-2.5 px-6 text-right">Date Filed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($returns as $ret)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ $ret->return_number ?? '#' . $ret->id }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200">
                                    {{ $ret->orderItem?->product_title ?? 'Marketplace Item' }}
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $ret->orderItem?->sku ?? 'N/A' }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-xs">
                                    {{ $ret->reason }}
                                    @if($ret->customer_notes)
                                        <div class="text-[10px] text-slate-400 italic">"{{ $ret->customer_notes }}"</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $ret->qc_status?->badgeClass() ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $ret->qc_status?->label() ?? 'Pending QC' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ method_exists($ret->status, 'badgeClass') ? $ret->status->badgeClass() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ method_exists($ret->status, 'label') ? $ret->status->label() : ucfirst((string)$ret->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-emerald-600 dark:text-emerald-400">
                                    ₹{{ number_format((float)$ret->refund_amount, 2) }}
                                </td>
                                <td class="py-3 px-6 text-right font-mono text-[11px] text-slate-500">
                                    {{ $ret->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No product return requests filed by this customer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$returns" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 7: SUPPORT TICKETS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'tickets'" class="space-y-6">
        <x-card title="Customer Support Tickets &amp; Escalations" subtitle="Buyer inquiries, delivery issues, payment escalations, and resolution status">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Ticket #</th>
                            <th class="py-2.5 px-4">Subject</th>
                            <th class="py-2.5 px-4">Category</th>
                            <th class="py-2.5 px-4 text-center">Priority</th>
                            <th class="py-2.5 px-4 text-center">Status</th>
                            <th class="py-2.5 px-4">Assigned Staff</th>
                            <th class="py-2.5 px-4 text-right">Created</th>
                            <th class="py-2.5 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    <a href="{{ route('admin.support.show', $ticket) }}" class="hover:underline">
                                        {{ $ticket->ticket_number ?? '#' . $ticket->id }}
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white max-w-xs truncate">
                                    {{ $ticket->subject }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    {{ $ticket->categoryRelation?->name ?? ($ticket->category ?? 'General Support') }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ method_exists($ticket->priority, 'badgeClass') ? $ticket->priority->badgeClass() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $ticket->priority instanceof \BackedEnum ? ucfirst($ticket->priority->value) : ucfirst((string)$ticket->priority) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ method_exists($ticket->status, 'badgeClass') ? $ticket->status->badgeClass() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $ticket->status instanceof \BackedEnum ? ucfirst(str_replace('_', ' ', $ticket->status->value)) : ucfirst(str_replace('_', ' ', (string)$ticket->status)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-[11px] text-slate-500">
                                    {{ $ticket->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <a href="{{ route('admin.support.show', $ticket) }}" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 transition">
                                        View Ticket
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No support tickets registered by this customer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$tickets" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 8: REVIEWS -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'reviews'" class="space-y-6">
        <x-card title="Product &amp; Merchant Reviews" subtitle="Feedback submitted by this customer across platform merchandise">
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase font-semibold text-[10px]">
                            <th class="py-2.5 px-6">Product</th>
                            <th class="py-2.5 px-4 text-center">Rating</th>
                            <th class="py-2.5 px-4">Review Content</th>
                            <th class="py-2.5 px-4 text-center">Verified</th>
                            <th class="py-2.5 px-4 text-center">Status</th>
                            <th class="py-2.5 px-6 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($reviews as $rev)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                                <td class="py-3 px-6 font-bold text-slate-900 dark:text-white">
                                    {{ $rev->product?->title ?? 'Product #' . $rev->product_id }}
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap font-bold text-amber-500">
                                    {{ $rev->rating }} ★
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-sm">
                                    @if($rev->title)
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $rev->title }}</div>
                                    @endif
                                    <div class="text-[11px] text-slate-500">{{ $rev->body }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($rev->is_verified_purchase)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Verified Buyer
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ method_exists($rev->status, 'badgeClass') ? $rev->status->badgeClass() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ method_exists($rev->status, 'label') ? $rev->status->label() : ucfirst((string)$rev->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-right font-mono text-[11px] text-slate-500">
                                    {{ $rev->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    No product reviews authored by this customer yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-3 mt-4">
                <x-pagination :paginator="$reviews" />
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- TAB 9: ACCOUNT ACTIVITY -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'activity'" class="space-y-6">
        <x-card title="Customer Account Activity Log &amp; Governance Trail" subtitle="Chronological ledger of security events, status modifications, and admin actions">
            <div class="space-y-4">
                @forelse($activityLogs as $activity)
                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $activity->description }}</span>
                            <span class="text-slate-400 text-[11px] font-mono">{{ $activity->created_at->diffForHumans() }} ({{ $activity->created_at->format('Y-m-d H:i') }})</span>
                        </div>
                        <div class="text-slate-500 text-[11px]">
                            Initiated by: <strong class="text-slate-700 dark:text-slate-300">{{ $activity->causer?->name ?? 'System Automated' }}</strong>
                        </div>

                        @if(!empty($activity->properties['reason']))
                            <div class="p-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-[11px] text-slate-700 dark:text-slate-300">
                                <strong>Administrative Justification:</strong> {{ $activity->properties['reason'] }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-xs text-slate-400 text-center py-8">
                        No activity records logged for this customer account yet.
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>

    <!-- ========================================== -->
    <!-- MODALS -->
    <!-- ========================================== -->

    <!-- Restrict Customer Modal -->
    <x-modal name="restrict-customer-modal" title="Restrict Customer Account" size="md">
        <form method="POST" action="{{ route('admin.customers.restrict', $customer) }}" class="space-y-4">
            @csrf
            <div>
                <p class="text-xs text-rose-600 dark:text-rose-400 mb-3 leading-relaxed font-semibold">
                    Warning: Restricting this customer will immediately suspend their account, preventing checkout, order placement, and access to active shopping sessions.
                </p>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Mandatory Policy Justification / Reason <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="reason" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="Provide specific justification (e.g. Repeated COD delivery refusal, payment dispute abuse, compromised credentials)..." 
                    class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" @click="$dispatch('close-modal', 'restrict-customer-modal')" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition">
                    Confirm Account Restriction
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Reactivate Customer Modal -->
    <x-modal name="reactivate-customer-modal" title="Reactivate Customer Account" size="md">
        <form method="POST" action="{{ route('admin.customers.reactivate', $customer) }}" class="space-y-4">
            @csrf
            <div>
                <p class="text-xs text-slate-500 mb-3 leading-relaxed">
                    Reactivating will restore normal purchasing privileges and allow <strong>{{ $customer->user?->name ?? 'this customer' }}</strong> to checkout and place orders.
                </p>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Mandatory Reactivation Rationale <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="reason" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="Provide compliance or resolution rationale (e.g. Identity re-verified, false positive resolved)..." 
                    class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" @click="$dispatch('close-modal', 'reactivate-customer-modal')" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                    Reactivate Account
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Suspicious Risk Review Modal -->
    <x-modal name="suspicious-review-modal" title="Security &amp; Suspicious Activity Review" size="md">
        <form method="POST" action="{{ route('admin.customers.suspicious_review', $customer) }}" class="space-y-4">
            @csrf
            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Risk Review Decision <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="decision" 
                        required 
                        class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="cleared">Cleared – Verified Legitimate Buyer</option>
                        <option value="watchlist">Watchlist – Monitor Future Orders</option>
                        <option value="restrict_cod">Restrict COD – Pre-paid Payment Only</option>
                        <option value="high_risk">High Risk – Escalate for Verification</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Review Notes / Findings <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="notes" 
                        rows="3" 
                        required 
                        minlength="5"
                        placeholder="Detail the review findings or reason for decision..." 
                        class="w-full p-2.5 text-xs rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                    ></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2">
                <button type="button" @click="$dispatch('close-modal', 'suspicious-review-modal')" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 transition">
                    Save Review
                </button>
            </div>
        </form>
    </x-modal>

</div>

<script>
    function customerDetail(initialTab) {
        return {
            activeTab: initialTab || 'profile',

            init() {
                this.$nextTick(() => {
                    const el = document.getElementById('tab-btn-' + this.activeTab);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                    }
                });

                window.addEventListener('popstate', () => {
                    const url = new URL(window.location);
                    const tab = url.searchParams.get('tab');
                    if (tab && tab !== this.activeTab) {
                        this.activeTab = tab;
                        this.$nextTick(() => {
                            document.getElementById('tab-btn-' + tab)?.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                        });
                    }
                });
            },

            setTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
                this.$nextTick(() => {
                    const el = document.getElementById('tab-btn-' + tab);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', inline: 'nearest', block: 'nearest' });
                    }
                });
            }
        };
    }
</script>
@endsection
