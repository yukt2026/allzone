@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Admin Central Command'])

@section('content')
<div class="space-y-6 sm:space-y-8" x-data="adminDashboard()" x-init="initDashboard()">
    <!-- Page Header & Period Range Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-1">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Central Operations & Command Hub
                </h1>
                <x-badge variant="success" dot pulse size="sm">
                    Live Telemetry
                </x-badge>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                Platform-wide performance, multi-vendor sales, quick commerce fulfilment, dispute management, and financial settlements.
            </p>
        </div>

        <!-- Range Filter Buttons, Date Inputs, Refresh, and CSV Export -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
            <!-- Period Selector Pills -->
            <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 shadow-2xs">
                @foreach(['yesterday' => 'Yesterday', '7d' => '7 Days', '30d' => '30 Days', 'custom' => 'Custom'] as $key => $title)
                    <button 
                        type="button"
                        @click="setRange('{{ $key }}')"
                        :class="activeRange === '{{ $key }}' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 font-bold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'"
                        class="px-2.5 sm:px-3 py-1.5 text-xs rounded-lg transition-all duration-150 focus:outline-hidden"
                    >
                        {{ $title }}
                    </button>
                @endforeach
            </div>

            <!-- Custom Date Range Form (Visible when custom is selected) -->
            <div x-show="activeRange === 'custom'" x-cloak class="flex items-center gap-1.5 bg-white dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                <input 
                    type="date" 
                    x-model="customStart" 
                    class="px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-emerald-500"
                    placeholder="Start date"
                />
                <span class="text-xs text-slate-400">to</span>
                <input 
                    type="date" 
                    x-model="customEnd" 
                    class="px-2 py-1 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:outline-emerald-500"
                    placeholder="End date"
                />
                <button 
                    type="button" 
                    @click="applyCustomDate()" 
                    class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition"
                >
                    Apply
                </button>
            </div>

            <!-- Refresh Button -->
            <button 
                type="button" 
                @click="refresh()" 
                title="Refresh metrics"
                class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs transition-colors"
            >
                <svg :class="isLoading ? 'animate-spin' : ''" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            <!-- Export Summary CSV -->
            <a 
                :href="exportUrl"
                class="inline-flex items-center px-3 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-750 shadow-2xs transition-all hover:border-slate-300 dark:hover:border-slate-600"
            >
                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="hidden sm:inline">Export Summary (CSV)</span>
                <span class="sm:hidden">Export</span>
            </a>
        </div>
    </div>

    <!-- Admin Quick Actions Bar -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Admin Quick Actions</span>
            <span class="text-[11px] text-slate-400">Immediate operations & moderation queues</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
            <!-- 1. Approve Seller -->
            <a href="{{ route('admin.sellers.index', ['status' => 'pending']) }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-emerald-500 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition group text-center">
                <div class="relative mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    @if(($quickActions['pending_sellers'] ?? 0) > 0)
                        <span class="absolute -top-1 -right-2 px-1.5 py-0.2 text-[9px] font-extrabold bg-rose-600 text-white rounded-full">
                            {{ $quickActions['pending_sellers'] }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Approve Sellers</span>
                <span class="text-[10px] text-slate-400 mt-0.5">{{ $quickActions['pending_sellers'] ?? 0 }} pending</span>
            </a>

            <!-- 2. Review Product -->
            <a href="{{ route('admin.catalogue.moderation.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-amber-500 hover:bg-amber-50/30 dark:hover:bg-amber-950/20 transition group text-center">
                <div class="relative mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    @if(($quickActions['pending_products'] ?? 0) > 0)
                        <span class="absolute -top-1 -right-2 px-1.5 py-0.2 text-[9px] font-extrabold bg-amber-600 text-white rounded-full">
                            {{ $quickActions['pending_products'] }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">Review Products</span>
                <span class="text-[10px] text-slate-400 mt-0.5">{{ $quickActions['pending_products'] ?? 0 }} pending</span>
            </a>

            <!-- 3. Manage Orders -->
            <a href="{{ route('admin.orders.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-blue-500 hover:bg-blue-50/30 dark:hover:bg-blue-950/20 transition group text-center">
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">Manage Orders</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Pipeline status</span>
            </a>

            <!-- 4. Review Disputes -->
            <a href="{{ route('admin.claims.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-rose-500 hover:bg-rose-50/30 dark:hover:bg-rose-950/20 transition group text-center">
                <div class="relative mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    @if(($quickActions['open_disputes'] ?? 0) > 0)
                        <span class="absolute -top-1 -right-2 px-1.5 py-0.2 text-[9px] font-extrabold bg-rose-600 text-white rounded-full">
                            {{ $quickActions['open_disputes'] }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-rose-600 dark:group-hover:text-rose-400">Review Disputes</span>
                <span class="text-[10px] text-slate-400 mt-0.5">{{ $quickActions['open_disputes'] ?? 0 }} claims</span>
            </a>

            <!-- 5. Manage Promotions -->
            <a href="{{ route('admin.campaigns.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-purple-500 hover:bg-purple-50/30 dark:hover:bg-purple-950/20 transition group text-center">
                <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">Manage Promos</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Campaigns</span>
            </a>

            <!-- 6. Publish Announcement -->
            <a href="{{ route('admin.announcements.create') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-sky-500 hover:bg-sky-50/30 dark:hover:bg-sky-950/20 transition group text-center">
                <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-sky-600 dark:group-hover:text-sky-400">Announcement</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Broadcast</span>
            </a>

            <!-- 7. View Pending Payments -->
            <a href="{{ route('admin.settlements.index') }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-indigo-500 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition group text-center">
                <div class="relative mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    @if(($quickActions['pending_settlements'] ?? 0) > 0)
                        <span class="absolute -top-1 -right-2 px-1.5 py-0.2 text-[9px] font-extrabold bg-indigo-600 text-white rounded-full">
                            {{ $quickActions['pending_settlements'] }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Pending Payouts</span>
                <span class="text-[10px] text-slate-400 mt-0.5">{{ $quickActions['pending_settlements'] ?? 0 }} due</span>
            </a>

            <!-- 8. Review Seller Ads -->
            <a href="{{ route('admin.ads.index', ['status' => 'pending_approval']) }}" class="flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 hover:border-amber-500 hover:bg-amber-50/30 dark:hover:bg-amber-950/20 transition group text-center">
                <div class="relative mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </div>
                    @if(($quickActions['pending_ads'] ?? 0) > 0)
                        <span class="absolute -top-1 -right-2 px-1.5 py-0.2 text-[9px] font-extrabold bg-amber-600 text-white rounded-full">
                            {{ $quickActions['pending_ads'] }}
                        </span>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">Review Ads</span>
                <span class="text-[10px] text-slate-400 mt-0.5">{{ $quickActions['pending_ads'] ?? 0 }} pending</span>
            </a>
        </div>
    </div>

    <!-- 4 Primary High-Impact Financial & Velocity KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- 1. Total GMV -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden flex flex-col justify-between hover:border-emerald-300 dark:hover:border-emerald-800 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total GMV</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60">
                        Gross Volume
                    </span>
                </div>
                <div class="flex items-baseline justify-between gap-2">
                    <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="stats.total_gmv?.formatted ?? '{{ $stats['total_gmv']['formatted'] }}'">
                        {{ $stats['total_gmv']['formatted'] }}
                    </div>
                    <div class="flex items-center text-xs font-bold" :class="getTrendColor(stats.total_gmv?.trend?.type)">
                        <svg x-show="stats.total_gmv?.trend?.type === 'up'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        <svg x-show="stats.total_gmv?.trend?.type === 'down'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        <span x-text="stats.total_gmv?.trend?.text ?? '{{ $stats['total_gmv']['trend']['text'] }}'">{{ $stats['total_gmv']['trend']['text'] }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">Gross order revenue before cancellations</div>
        </div>

        <!-- 2. Total Orders -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden flex flex-col justify-between hover:border-blue-300 dark:hover:border-blue-800 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Orders</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800/60">
                        Checkouts
                    </span>
                </div>
                <div class="flex items-baseline justify-between gap-2">
                    <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="stats.total_orders?.formatted ?? '{{ $stats['total_orders']['formatted'] }}'">
                        {{ $stats['total_orders']['formatted'] }}
                    </div>
                    <div class="flex items-center text-xs font-bold" :class="getTrendColor(stats.total_orders?.trend?.type)">
                        <svg x-show="stats.total_orders?.trend?.type === 'up'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        <svg x-show="stats.total_orders?.trend?.type === 'down'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        <span x-text="stats.total_orders?.trend?.text ?? '{{ $stats['total_orders']['trend']['text'] }}'">{{ $stats['total_orders']['trend']['text'] }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">Checkout conversions across all channels</div>
        </div>

        <!-- 3. Commission Earned -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden flex flex-col justify-between hover:border-purple-300 dark:hover:border-purple-800 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Commission Earned</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/60">
                        Net Revenue
                    </span>
                </div>
                <div class="flex items-baseline justify-between gap-2">
                    <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="stats.platform_commission?.formatted ?? '{{ $stats['platform_commission']['formatted'] }}'">
                        {{ $stats['platform_commission']['formatted'] }}
                    </div>
                    <div class="flex items-center text-xs font-bold" :class="getTrendColor(stats.platform_commission?.trend?.type)">
                        <svg x-show="stats.platform_commission?.trend?.type === 'up'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        <svg x-show="stats.platform_commission?.trend?.type === 'down'" class="w-3.5 h-3.5 mr-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        <span x-text="stats.platform_commission?.trend?.text ?? '{{ $stats['platform_commission']['trend']['text'] }}'">{{ $stats['platform_commission']['trend']['text'] }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">Net platform rake from vendor fulfilments</div>
        </div>

        <!-- 4. Active Sellers -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden flex flex-col justify-between hover:border-sky-300 dark:hover:border-sky-800 transition">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Sellers</span>
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border bg-sky-50 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/60">
                        Approved
                    </span>
                </div>
                <div class="flex items-baseline justify-between gap-2">
                    <div class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white" x-text="stats.active_sellers?.formatted ?? '{{ $stats['active_sellers']['formatted'] }}'">
                        {{ $stats['active_sellers']['formatted'] }}
                    </div>
                    <div class="flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        <span x-text="stats.active_sellers?.trend?.text ?? '{{ $stats['active_sellers']['trend']['text'] }}'">{{ $stats['active_sellers']['trend']['text'] }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2 text-[11px] text-slate-400 dark:text-slate-500 border-t border-slate-100 dark:border-slate-800">Verified merchants operating on {{ setting('name', 'ALLZONE') }}</div>
        </div>
    </div>

    <!-- Operational Queues & Live Action Strip (4 Actionable Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Pending KYC -->
        <a href="{{ route('admin.sellers.index', ['status' => 'pending']) }}" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-rose-400 dark:hover:border-rose-600 transition flex items-center justify-between group">
            <div class="min-w-0 pr-3">
                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-600 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Pending KYC</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    Merchants awaiting document verification
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg border" :class="(stats.pending_seller_approvals?.value ?? {{ $stats['pending_seller_approvals']['value'] }}) > 0 ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'" x-text="stats.pending_seller_approvals?.formatted ?? '{{ $stats['pending_seller_approvals']['formatted'] }}'">
                    {{ $stats['pending_seller_approvals']['formatted'] }}
                </span>
                <span class="text-slate-400 group-hover:translate-x-0.5 transition-transform text-sm font-bold">→</span>
            </div>
        </a>

        <!-- 2. Pending Products -->
        <a href="{{ route('admin.catalogue.moderation.index') }}" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-amber-400 dark:hover:border-amber-600 transition flex items-center justify-between group">
            <div class="min-w-0 pr-3">
                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-600 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Pending Products</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    Catalogue listings awaiting moderation
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg border bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800" x-text="stats.pending_product_reviews?.formatted ?? '{{ $stats['pending_product_reviews']['formatted'] }}'">
                    {{ $stats['pending_product_reviews']['formatted'] }}
                </span>
                <span class="text-slate-400 group-hover:translate-x-0.5 transition-transform text-sm font-bold">→</span>
            </div>
        </a>

        <!-- 3. Quick Orders -->
        <a href="{{ route('admin.orders.index', ['type' => 'quick']) }}" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-emerald-400 dark:hover:border-emerald-600 transition flex items-center justify-between group">
            <div class="min-w-0 pr-3">
                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-600 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Quick Orders</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    10-30m rapid fulfilment volume
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800" x-text="stats.quick_orders?.formatted ?? '{{ $stats['quick_orders']['formatted'] }}'">
                    {{ $stats['quick_orders']['formatted'] }}
                </span>
                <span class="text-slate-400 group-hover:translate-x-0.5 transition-transform text-sm font-bold">→</span>
            </div>
        </a>

        <!-- 4. Active Customers -->
        <a href="{{ route('admin.customers.index') }}" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 transition flex items-center justify-between group">
            <div class="min-w-0 pr-3">
                <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-600 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>Active Customers</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate">
                    Registered buyers across platform
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <span class="px-2.5 py-1 text-xs font-extrabold rounded-lg border bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800" x-text="stats.active_customers?.formatted ?? '{{ $stats['active_customers']['formatted'] }}'">
                    {{ $stats['active_customers']['formatted'] }}
                </span>
                <span class="text-slate-400 group-hover:translate-x-0.5 transition-transform text-sm font-bold">→</span>
            </div>
        </a>
    </div>

    <!-- Platform Operational Metrics Overview (Ecosystem, Orders, Returns, Financials) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Marketplace Ecosystem Block -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ecosystem</span>
                <span class="text-[10px] text-slate-400">Buyers & Sellers</span>
            </div>
            <div class="space-y-2.5 mt-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Total Customers:</span>
                    <span class="font-bold text-slate-900 dark:text-white" x-text="stats.total_customers ?? '{{ $stats['total_customers'] ?? 0 }}'">{{ $stats['total_customers'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Total Sellers:</span>
                    <span class="font-bold text-slate-900 dark:text-white" x-text="stats.total_sellers ?? '{{ $stats['total_sellers'] ?? 0 }}'">{{ $stats['total_sellers'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending Seller KYC:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="stats.pending_seller_approvals?.formatted ?? '{{ $stats['pending_seller_approvals']['formatted'] }}'">{{ $stats['pending_seller_approvals']['formatted'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Total Products:</span>
                    <span class="font-bold text-slate-900 dark:text-white" x-text="stats.total_products ?? '{{ $stats['total_products'] ?? 0 }}'">{{ $stats['total_products'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Active Listings:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="stats.active_products ?? '{{ $stats['active_products'] ?? 0 }}'">{{ $stats['active_products'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending Review:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="stats.pending_product_reviews?.formatted ?? '{{ $stats['pending_product_reviews']['formatted'] }}'">{{ $stats['pending_product_reviews']['formatted'] }}</span>
                </div>
            </div>
        </div>

        <!-- 2. Orders Pipeline Block -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Orders Pipeline</span>
                <span class="text-[10px] text-slate-400">Fulfillment States</span>
            </div>
            <div class="space-y-2.5 mt-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending / Placed:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="stats.pending_orders ?? '{{ $stats['pending_orders'] ?? 0 }}'">{{ $stats['pending_orders'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">In Transit / Shipped:</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400" x-text="stats.shipped_orders ?? '{{ $stats['shipped_orders'] ?? 0 }}'">{{ $stats['shipped_orders'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Delivered Orders:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="stats.delivered_orders ?? '{{ $stats['delivered_orders'] ?? 0 }}'">{{ $stats['delivered_orders'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Cancelled Orders:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="stats.cancelled_orders ?? '{{ $stats['cancelled_orders'] ?? 0 }}'">{{ $stats['cancelled_orders'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Quick 10-30m Orders:</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="stats.quick_orders?.formatted ?? '{{ $stats['quick_orders']['formatted'] }}'">{{ $stats['quick_orders']['formatted'] }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Returns & Logistics Block -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Quality & Logistics</span>
                <span class="text-[10px] text-slate-400">Reverse Supply</span>
            </div>
            <div class="space-y-2.5 mt-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Customer Returns:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400" x-text="stats.returns_count ?? '{{ $stats['returns_count'] ?? 0 }}'">{{ $stats['returns_count'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Courier RTOs:</span>
                    <span class="font-bold text-amber-600 dark:text-amber-400" x-text="stats.rto_count ?? '{{ $stats['rto_count'] ?? 0 }}'">{{ $stats['rto_count'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Open Support Tickets:</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="stats.open_tickets ?? '{{ $stats['open_tickets'] ?? 0 }}'">{{ $stats['open_tickets'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Seller Disputes:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $quickActions['open_disputes'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- 4. Settlement & Balances Block -->
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Financial Balances</span>
                <span class="text-[10px] text-slate-400">Vendor Liabilities</span>
            </div>
            <div class="space-y-2.5 mt-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Gross Sales:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + Number(stats.gross_sales ?? {{ $stats['gross_sales'] ?? 0 }}).toLocaleString('en-IN', {minimumFractionDigits: 2})">
                        ₹{{ number_format((float) ($stats['gross_sales'] ?? 0), 2) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Seller Payable:</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'₹' + Number(stats.seller_payable ?? {{ $stats['seller_payable'] ?? 0 }}).toLocaleString('en-IN', {minimumFractionDigits: 2})">
                        ₹{{ number_format((float) ($stats['seller_payable'] ?? 0), 2) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pending Settlements:</span>
                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400" x-text="'₹' + Number(stats.pending_settlements ?? {{ $stats['pending_settlements'] ?? 0 }}).toLocaleString('en-IN', {minimumFractionDigits: 2})">
                        ₹{{ number_format((float) ($stats['pending_settlements'] ?? 0), 2) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Platform Rake:</span>
                    <span class="font-mono font-bold text-purple-600 dark:text-purple-400" x-text="stats.platform_commission?.formatted ?? '{{ $stats['platform_commission']['formatted'] }}'">
                        {{ $stats['platform_commission']['formatted'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Dual-Axis Sales Trend + Orders by Type Donut -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales & Orders Dual-Axis Trend Line (2/3) -->
        <div class="lg:col-span-2 min-w-0">
            <x-card 
                title="Sales & Orders Trend" 
                subtitle="Gross Merchandise Value (₹) on primary axis, Order Volume on secondary axis"
            >
                <div class="relative w-full h-64 sm:h-72 lg:h-80">
                    <div x-show="isLoading" class="absolute inset-0 z-10 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center rounded-xl">
                        <div class="flex items-center space-x-2 text-emerald-600 dark:text-emerald-400 font-semibold text-xs">
                            <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Updating trend analysis...</span>
                        </div>
                    </div>
                    <canvas id="adminSalesOrdersChart" class="w-full h-full"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Orders by Fulfilment Type Donut (1/3) -->
        <div class="min-w-0">
            <x-card 
                title="Orders by Fulfilment" 
                subtitle="Standard Delivery vs Quick (10-30m) vs Store Pickup"
            >
                <div class="relative w-full h-64 sm:h-72 lg:h-80 flex items-center justify-center">
                    <div x-show="isLoading" class="absolute inset-0 z-10 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center rounded-xl">
                        <svg class="animate-spin w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <canvas id="adminOrderTypesChart" class="w-full h-full"></canvas>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Charts Row 2: Category Revenue Horizontal Bar + Order Status Funnel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Category Revenue Bar -->
        <div class="min-w-0">
            <x-card 
                title="Revenue by Category" 
                subtitle="Top performing product verticals by Gross Value (₹)"
            >
                <div class="relative w-full h-64 sm:h-72">
                    <div x-show="isLoading" class="absolute inset-0 z-10 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center rounded-xl">
                        <svg class="animate-spin w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <canvas id="adminCategoryRevenueChart" class="w-full h-full"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Order Status Funnel -->
        <div class="min-w-0">
            <x-card 
                title="Order Pipeline & Funnel" 
                subtitle="Progression across checkout lifecycle stages"
            >
                <div class="relative w-full h-64 sm:h-72">
                    <div x-show="isLoading" class="absolute inset-0 z-10 bg-white/70 dark:bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center rounded-xl">
                        <svg class="animate-spin w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <canvas id="adminStatusFunnelChart" class="w-full h-full"></canvas>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Ranked Performance: Top-Selling Products + Top Merchants -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top-Selling Products Table -->
        <x-card 
            title="Top-Selling Products" 
            subtitle="Products generating highest revenue across marketplace in selected period"
        >
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-6">Product</th>
                            <th class="py-2.5 px-3">Seller</th>
                            <th class="py-2.5 px-3 text-right">Units</th>
                            <th class="py-2.5 px-6 text-right">Revenue (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <template x-for="(product, idx) in topProducts" :key="product.product_id">
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]" x-text="product.title"></div>
                                    <div class="text-[10px] text-slate-400" x-text="product.category"></div>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-300 truncate max-w-[120px]" x-text="product.seller_name"></td>
                                <td class="py-3 px-3 text-right font-mono font-medium text-slate-700 dark:text-slate-300" x-text="product.units_sold"></td>
                                <td class="py-3 px-6 text-right font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + Number(product.revenue).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                            </tr>
                        </template>
                        <tr x-show="!topProducts || topProducts.length === 0">
                            <td colspan="4" class="py-6 text-center text-slate-400">
                                No product sales recorded for this period.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Top 10 Merchants Table with Sparklines -->
        <x-card 
            title="Top 10 Merchants by Sales Volume" 
            subtitle="Ranked performance with 7-day actual order sparkline"
        >
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-6">Rank & Merchant</th>
                            <th class="py-2.5 px-3 text-right">Orders</th>
                            <th class="py-2.5 px-3 text-right">GMV (₹)</th>
                            <th class="py-2.5 px-6 text-center">7-Day Trend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <template x-for="(seller, index) in topSellers" :key="seller.id">
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition-colors">
                                <td class="py-3 px-6">
                                    <div class="flex items-center space-x-2.5">
                                        <span 
                                            class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0"
                                            :class="index === 0 ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-extrabold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
                                            x-text="'#' + (index + 1)"
                                        ></span>
                                        <div class="min-w-0">
                                            <a :href="'/admin/sellers/' + seller.id" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition truncate block max-w-[140px]" x-text="seller.business_name"></a>
                                            <div class="text-[10px] text-slate-400 truncate" x-text="seller.seller_type"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-medium text-slate-700 dark:text-slate-300" x-text="seller.order_count"></td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white" x-text="seller.formatted_sales"></td>
                                <td class="py-3 px-6 text-center">
                                    <div class="inline-flex items-center justify-center">
                                        <svg class="w-20 h-5 text-emerald-500" viewBox="0 0 80 24" fill="none">
                                            <polyline 
                                                :points="renderSparklinePoints(seller.sparkline)" 
                                                fill="none" 
                                                stroke="currentColor" 
                                                stroke-width="2" 
                                                stroke-linecap="round" 
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!topSellers || topSellers.length === 0">
                            <td colspan="4" class="py-6 text-center text-slate-400">
                                No seller activity recorded for this period.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Operational Streams Grid (Recent Orders, Pending KYC, Open Tickets, Settlements Due & Health) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Widget 1: Recent Checkouts Table -->
        <x-card 
            title="Recent Orders" 
            subtitle="Latest incoming consumer orders across all channels"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.orders.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                    View All Orders →
                </a>
            </x-slot>

            @if(isset($widgets['recent_orders']) && count($widgets['recent_orders']) > 0)
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="py-2.5 px-6">Order #</th>
                                <th class="py-2.5 px-4">Customer</th>
                                <th class="py-2.5 px-4 text-right">Amount</th>
                                <th class="py-2.5 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach($widgets['recent_orders'] as $order)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition-colors">
                                    <td class="py-3 px-6 font-mono font-bold whitespace-nowrap">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">
                                            {{ $order->order_number }}
                                        </a>
                                        <div class="text-[10px] font-normal text-slate-400">{{ $order->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-900 dark:text-white truncate max-w-[150px]">{{ $order->customer?->user?->name ?? 'Guest User' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $order->fulfilmentOrders->count() }} package(s)</div>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                        ₹{{ number_format((float) $order->total, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $order->status->badgeClass() }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state 
                    title="No orders yet" 
                    description="When customers place orders, they will appear in this real-time stream." 
                />
            @endif
        </x-card>

        <!-- Widget 2: Pending KYC Queue -->
        <x-card 
            title="Pending Merchant KYC Queue" 
            subtitle="Sellers awaiting verification before store go-live"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.sellers.index', ['status' => 'pending']) }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                    View KYC Queue →
                </a>
            </x-slot>

            @if(isset($widgets['pending_kyc']) && count($widgets['pending_kyc']) > 0)
                <div class="space-y-3">
                    @foreach($widgets['pending_kyc'] as $seller)
                        <div class="p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-900 dark:text-white truncate">
                                    {{ $seller->business_name }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                    Owner: {{ $seller->user?->name ?? 'N/A' }} &bull; {{ $seller->user?->phone ?? $seller->user?->email }}
                                </div>
                                <div class="text-[10px] text-amber-600 dark:text-amber-400 font-medium mt-0.5">
                                    Status: {{ $seller->status->label() }} &bull; Applied {{ $seller->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center space-x-2">
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition inline-block">
                                    Audit KYC →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state 
                    title="KYC Queue Clear" 
                    description="All merchant applications have been reviewed and approved." 
                />
            @endif
        </x-card>

        <!-- Widget 3: Open Support Tickets -->
        <x-card 
            title="Open Support Inquiries" 
            subtitle="Customer and merchant escalations needing resolution"
        >
            <x-slot name="actions">
                <a href="{{ route('admin.support.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                    Support Helpdesk →
                </a>
            </x-slot>

            @if(isset($widgets['open_tickets']) && count($widgets['open_tickets']) > 0)
                <div class="space-y-2.5">
                    @foreach($widgets['open_tickets'] as $ticket)
                        <a href="{{ route('admin.support.show', $ticket) }}" class="p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between gap-3 hover:border-emerald-300 dark:hover:border-emerald-700 transition block group">
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                        #{{ $ticket->ticket_number }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded-sm text-[10px] font-bold uppercase {{ $ticket->priority->value === 'urgent' ? 'bg-rose-100 text-rose-800' : ($ticket->priority->value === 'high' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $ticket->priority->label() }}
                                    </span>
                                </div>
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate mt-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                    {{ $ticket->subject }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    From: {{ $ticket->user?->name ?? 'User' }} &bull; {{ $ticket->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <span class="shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ $ticket->status->label() }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state 
                    title="No open tickets" 
                    description="Zero active customer or merchant complaints at this time." 
                />
            @endif
        </x-card>

        <!-- Widget 4: Settlements Due & Platform Infrastructure -->
        <div class="space-y-6">
            <!-- Settlements Due Box -->
            <x-card 
                title="Settlements Pending" 
                subtitle="Upcoming payout disbursements to merchant bank accounts"
            >
                <div class="p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 flex items-center justify-between mb-4">
                    <div>
                        <div class="text-xs font-bold text-emerald-900 dark:text-emerald-200">Total Pending Payout</div>
                        <div class="text-xl sm:text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                            ₹{{ number_format((float) ($widgets['total_pending_payout'] ?? 0), 2) }}
                        </div>
                    </div>
                    <a href="{{ route('admin.settlements.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white shadow-xs hover:bg-emerald-700 transition inline-flex items-center space-x-1">
                        <span>Execute Payouts</span>
                        <span>→</span>
                    </a>
                </div>

                @if(isset($widgets['due_settlements']) && count($widgets['due_settlements']) > 0)
                    <div class="space-y-2">
                        @foreach($widgets['due_settlements'] as $settlement)
                            <a href="{{ route('admin.settlements.show', $settlement) }}" class="flex items-center justify-between text-xs py-2 px-2.5 rounded-lg border border-transparent hover:border-slate-200 dark:hover:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-850/50 transition">
                                <div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200">{{ $settlement->seller?->business_name ?? 'Merchant' }}</div>
                                    <div class="text-[10px] text-slate-400">#{{ $settlement->settlement_number }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format((float) $settlement->net, 2) }}</div>
                                    <span class="text-[10px] text-amber-600 font-semibold">Pending</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-slate-400 text-center py-2">All due merchant settlements have been settled.</div>
                @endif
            </x-card>

            <!-- System Health & Background Jobs -->
            <x-card 
                title="System Health & Infrastructure" 
                subtitle="Queue backlog, failed jobs, cache engine, and service telemetry"
            >
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800">
                        <div class="text-[11px] font-semibold text-slate-500">Queue Backlog</div>
                        <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                            {{ $widgets['queue_backlog'] ?? 0 }}
                        </div>
                        <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Database Workers Active</div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800">
                        <div class="text-[11px] font-semibold text-slate-500">Failed Jobs</div>
                        <div class="text-xl font-extrabold {{ ($widgets['failed_jobs'] ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }} mt-1">
                            {{ $widgets['failed_jobs'] ?? 0 }}
                        </div>
                        <div class="text-[10px] {{ ($widgets['failed_jobs'] ?? 0) > 0 ? 'text-rose-500 font-bold' : 'text-emerald-600 font-semibold' }} mt-0.5">
                            {{ ($widgets['failed_jobs'] ?? 0) > 0 ? 'Action Required' : 'Zero Exceptions' }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">All Systems Nominal</span>
                    </div>
                    <span class="font-mono text-[11px] text-slate-400">Laravel v{{ app()->version() }} &bull; PHP 8.3</span>
                </div>
            </x-card>
        </div>
    </div>
</div>

<script>
    function adminDashboard() {
        return {
            activeRange: '{{ $activeRange }}',
            customStart: '{{ $startDate ?? '' }}',
            customEnd: '{{ $endDate ?? '' }}',
            isLoading: false,
            stats: @js($stats),
            topSellers: @js($topSellers),
            topProducts: @js($topProducts),
            salesOrdersChart: null,
            orderTypesChart: null,
            categoryRevenueChart: null,
            statusFunnelChart: null,

            get exportUrl() {
                let url = '{{ route("admin.dashboard.export") }}?range=' + this.activeRange;
                if (this.activeRange === 'custom') {
                    if (this.customStart) url += '&start_date=' + encodeURIComponent(this.customStart);
                    if (this.customEnd) url += '&end_date=' + encodeURIComponent(this.customEnd);
                }
                return url;
            },

            initDashboard() {
                this.$nextTick(() => {
                    this.initCharts();
                    this.fetchData(this.activeRange, false);
                });
            },

            setRange(range) {
                if (this.activeRange === range && range !== 'custom' && !this.isLoading) return;
                this.activeRange = range;
                if (range !== 'custom') {
                    this.fetchData(range, true);
                }
            },

            applyCustomDate() {
                if (!this.customStart || !this.customEnd) return;
                this.activeRange = 'custom';
                this.fetchData('custom', true);
            },

            refresh() {
                this.fetchData(this.activeRange, true);
            },

            getTrendColor(type) {
                if (type === 'up') return 'text-emerald-600 dark:text-emerald-400';
                if (type === 'down') return 'text-rose-600 dark:text-rose-400';
                return 'text-slate-500 dark:text-slate-400';
            },

            renderSparklinePoints(data) {
                if (!data || !Array.isArray(data) || data.length === 0) return '';
                const min = Math.min(...data);
                const max = Math.max(...data) || 1;
                const range = (max - min) === 0 ? 1 : (max - min);
                const width = 80;
                const height = 24;
                const step = width / (data.length - 1 || 1);

                return data.map((val, idx) => {
                    const x = (idx * step).toFixed(1);
                    const y = (height - ((val - min) / range) * (height - 6) - 3).toFixed(1);
                    return `${x},${y}`;
                }).join(' ');
            },

            getChartTheme() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    textColor: isDark ? '#94a3b8' : '#64748b',
                    gridColor: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)',
                };
            },

            initCharts() {
                if (!window.Chart) return;
                const theme = this.getChartTheme();

                // 1. Dual Axis Sales & Orders Line Chart
                const ctxSales = document.getElementById('adminSalesOrdersChart');
                if (ctxSales) {
                    this.salesOrdersChart = new window.Chart(ctxSales, {
                        type: 'line',
                        data: { labels: [], datasets: [] },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    labels: { color: theme.textColor, font: { family: 'Inter', size: 11, weight: '600' } }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            if (context.dataset.yAxisID === 'y') {
                                                return ' GMV: ₹' + Number(context.parsed.y).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                            }
                                            return ' Orders: ' + context.parsed.y;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: theme.gridColor },
                                    ticks: { color: theme.textColor, font: { family: 'Inter', size: 10 } }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    grid: { color: theme.gridColor },
                                    ticks: {
                                        color: theme.textColor,
                                        font: { family: 'Inter', size: 10 },
                                        callback: (val) => '₹' + Number(val).toLocaleString('en-IN')
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    grid: { drawOnChartArea: false },
                                    ticks: {
                                        color: theme.textColor,
                                        font: { family: 'Inter', size: 10 },
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                }

                // 2. Orders by Fulfilment Type Donut Chart
                const ctxTypes = document.getElementById('adminOrderTypesChart');
                if (ctxTypes) {
                    this.orderTypesChart = new window.Chart(ctxTypes, {
                        type: 'doughnut',
                        data: { labels: [], datasets: [] },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: theme.textColor, boxWidth: 12, padding: 16, font: { family: 'Inter', size: 11 } }
                                }
                            }
                        }
                    });
                }

                // 3. Category Revenue Horizontal Bar Chart
                const ctxCat = document.getElementById('adminCategoryRevenueChart');
                if (ctxCat) {
                    this.categoryRevenueChart = new window.Chart(ctxCat, {
                        type: 'bar',
                        data: { labels: [], datasets: [] },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => ' Revenue: ₹' + Number(ctx.parsed.x).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: theme.gridColor },
                                    ticks: {
                                        color: theme.textColor,
                                        font: { family: 'Inter', size: 10 },
                                        callback: (val) => '₹' + Number(val).toLocaleString('en-IN')
                                    }
                                },
                                y: {
                                    grid: { display: false },
                                    ticks: { color: theme.textColor, font: { family: 'Inter', size: 10, weight: '500' } }
                                }
                            }
                        }
                    });
                }

                // 4. Order Status Funnel Chart
                const ctxFunnel = document.getElementById('adminStatusFunnelChart');
                if (ctxFunnel) {
                    this.statusFunnelChart = new window.Chart(ctxFunnel, {
                        type: 'bar',
                        data: { labels: [], datasets: [] },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { color: theme.textColor, font: { family: 'Inter', size: 10, weight: '600' } }
                                },
                                y: {
                                    grid: { color: theme.gridColor },
                                    ticks: { color: theme.textColor, font: { family: 'Inter', size: 10 }, precision: 0 }
                                }
                            }
                        }
                    });
                }
            },

            async fetchData(range, updateStats = true) {
                this.isLoading = true;
                try {
                    let queryUrl = `{{ route('admin.dashboard.chart_data') }}?range=${range}&chart=all`;
                    if (range === 'custom') {
                        if (this.customStart) queryUrl += '&start_date=' + encodeURIComponent(this.customStart);
                        if (this.customEnd) queryUrl += '&end_date=' + encodeURIComponent(this.customEnd);
                    }

                    const response = await fetch(queryUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to load telemetry');
                    const result = await response.json();
                    const data = result.data;

                    if (updateStats && data.stats) {
                        this.stats = data.stats;
                    }

                    if (data.top_sellers) {
                        this.topSellers = data.top_sellers;
                    }

                    if (data.top_products) {
                        this.topProducts = data.top_products;
                    }

                    // Update Sales & Orders Trend
                    if (this.salesOrdersChart && data.sales_orders) {
                        this.salesOrdersChart.data.labels = data.sales_orders.labels;
                        this.salesOrdersChart.data.datasets = data.sales_orders.datasets;
                        this.salesOrdersChart.update();
                    }

                    // Update Orders by Fulfilment Type
                    if (this.orderTypesChart && data.order_types) {
                        this.orderTypesChart.data.labels = data.order_types.labels;
                        this.orderTypesChart.data.datasets = data.order_types.datasets;
                        this.orderTypesChart.update();
                    }

                    // Update Revenue by Category
                    if (this.categoryRevenueChart && data.category_revenue) {
                        this.categoryRevenueChart.data.labels = data.category_revenue.labels;
                        this.categoryRevenueChart.data.datasets = data.category_revenue.datasets;
                        this.categoryRevenueChart.update();
                    }

                    // Update Status Funnel
                    if (this.statusFunnelChart && data.status_funnel) {
                        this.statusFunnelChart.data.labels = data.status_funnel.labels;
                        this.statusFunnelChart.data.datasets = data.status_funnel.datasets;
                        this.statusFunnelChart.update();
                    }
                } catch (error) {
                    console.error('Error fetching dashboard telemetry:', error);
                } finally {
                    this.isLoading = false;
                }
            }
        };
    }
</script>
@endsection
