@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Seller Advertising & Sponsored Listings - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-bold">Advertising Hub</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Seller Advertising &amp; Sponsored Listings</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Drive targeted discovery across ALLZONE search results and category collections with verified advertising delivery.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('seller.influencer.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs">
                Influencer Marketing &rarr;
            </a>
            <a href="{{ route('seller.ads.create') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs flex items-center space-x-1.5">
                <span>+</span>
                <span>Create Ad Campaign</span>
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Real Ad Metrics Overview Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- 1. Ad Spend -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Ad Spend</span>
            <div class="text-xl font-black text-slate-900 dark:text-white font-mono mt-1">₹{{ number_format($metrics['total_spend'], 2) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Actual billed budget</div>
        </div>

        <!-- 2. Attributed Revenue -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Attributed Revenue</span>
            <div class="text-xl font-black text-emerald-700 dark:text-emerald-400 font-mono mt-1">₹{{ number_format($metrics['total_revenue'], 2) }}</div>
            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1">From ad-clicked orders</div>
        </div>

        <!-- 3. ROI -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Return on Ad Spend (ROI)</span>
            <div class="text-xl font-black font-mono mt-1 {{ $metrics['overall_roi'] ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}">
                {{ $metrics['overall_roi'] ? "{$metrics['overall_roi']}x" : 'N/A' }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                {{ $metrics['overall_roi'] ? 'Reliable attribution' : 'Requires spend & revenue' }}
            </div>
        </div>

        <!-- 4. Impressions -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Impressions</span>
            <div class="text-xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($metrics['total_impressions']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Storefront search views</div>
        </div>

        <!-- 5. Clicks -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Clicks (Traffic)</span>
            <div class="text-xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ number_format($metrics['total_clicks']) }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                @if($metrics['total_impressions'] > 0)
                    CTR: {{ round(($metrics['total_clicks'] / $metrics['total_impressions']) * 100, 2) }}%
                @else
                    CTR: 0.0%
                @endif
            </div>
        </div>

        <!-- 6. Attributed Orders -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Attributed Orders</span>
            <div class="text-xl font-black text-indigo-700 dark:text-indigo-400 font-mono mt-1">{{ $metrics['total_orders'] }}</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Completed purchases</div>
        </div>
    </div>

    <!-- Transparent Advertising Policy Banner -->
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm border border-emerald-800/40">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 uppercase">
                    ALLZONE VERIFIED AD DELIVERY
                </span>
                <span class="text-xs text-emerald-200 font-medium">No Simulated Metrics</span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-white">
                Genuine Buyer Impressions &amp; Dedicated Review
            </h3>
            <p class="text-xs text-emerald-100/90 leading-relaxed max-w-2xl">
                Every ad campaign is audited by ALLZONE administrators before going live. Analytics reflect authentic customer interactions on search listings and featured catalogs with strict multi-vendor isolation.
            </p>
        </div>
        <div class="shrink-0 flex items-center space-x-3 bg-white/10 rounded-xl p-3 border border-white/10">
            <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-emerald-200">Active Campaigns</div>
                <div class="text-sm font-black text-white font-mono">{{ $metrics['active_campaigns'] }} Live</div>
            </div>
            <span class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-300 font-bold">✓</span>
        </div>
    </div>

    <!-- Campaigns Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <!-- Toolbar & Status Tabs -->
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Navigation Tabs -->
            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                @php
                    $tabs = [
                        'all' => 'All (' . $metrics['total_campaigns'] . ')',
                        'active' => 'Active (' . $metrics['active_campaigns'] . ')',
                        'pending_approval' => 'Pending Review (' . $metrics['pending_campaigns'] . ')',
                        'paused' => 'Paused (' . $metrics['paused_campaigns'] . ')',
                        'draft' => 'Drafts (' . $metrics['draft_campaigns'] . ')',
                        'completed' => 'Completed (' . $metrics['completed_campaigns'] . ')',
                    ];
                @endphp

                @foreach($tabs as $tabKey => $tabLabel)
                    <a 
                        href="{{ route('seller.ads.index', ['tab' => $tabKey, 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-lg transition {{ $tab === $tabKey ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                    >
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search Form -->
            <form action="{{ route('seller.ads.index') }}" method="GET" class="flex items-center space-x-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search campaigns..." 
                        class="w-56 px-3 py-1.5 pl-8 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-emerald-500"
                    >
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-bold transition">Search</button>
                @if($search !== '')
                    <a href="{{ route('seller.ads.index', ['tab' => $tab]) }}" class="px-2 py-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">Clear</a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Campaign</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Daily / Total Budget</th>
                        <th class="p-3">Ad Spend</th>
                        <th class="p-3">Views &bull; Clicks</th>
                        <th class="p-3">Attributed Orders</th>
                        <th class="p-3">Revenue &bull; ROI</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($campaigns as $camp)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <!-- Campaign Name & Products -->
                            <td class="p-3">
                                <a href="{{ route('seller.ads.show', $camp) }}" class="font-extrabold text-slate-900 dark:text-white hover:text-emerald-700 dark:hover:text-emerald-400 transition">
                                    {{ $camp->name }}
                                </a>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 flex items-center space-x-1.5">
                                    <span class="capitalize">{{ str_replace('_', ' ', $camp->type) }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $camp->products_count }} Products</span>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="p-3">
                                @if($camp->status === 'active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        ● Active
                                    </span>
                                @elseif($camp->status === 'pending_approval')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        Pending Review
                                    </span>
                                @elseif($camp->status === 'paused')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        Paused
                                    </span>
                                @elseif($camp->status === 'draft')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-950/60 text-sky-800 dark:text-sky-400 border border-sky-200 dark:border-sky-800">
                                        Draft
                                    </span>
                                @elseif($camp->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-800" title="{{ $camp->rejection_reason }}">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        {{ ucfirst($camp->status) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Budget -->
                            <td class="p-3 font-mono">
                                <div class="font-bold text-slate-800 dark:text-slate-200">₹{{ number_format((float)$camp->daily_budget, 2) }}/day</div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500">Total: ₹{{ number_format((float)$camp->total_budget, 2) }}</div>
                            </td>

                            <!-- Spend -->
                            <td class="p-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                ₹{{ number_format((float)$camp->spent_amount, 2) }}
                            </td>

                            <!-- Impressions & Clicks -->
                            <td class="p-3">
                                <div class="font-mono font-bold text-slate-900 dark:text-white">{{ number_format($camp->impressions_count) }} views</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ number_format($camp->clicks_count) }} clicks ({{ $camp->ctr }}%)</div>
                            </td>

                            <!-- Attributed Orders -->
                            <td class="p-3 font-mono font-bold text-indigo-700 dark:text-indigo-400">
                                {{ $camp->attributed_orders_count }}
                            </td>

                            <!-- Attributed Revenue & ROI -->
                            <td class="p-3 font-mono">
                                <div class="font-bold text-emerald-700 dark:text-emerald-400">₹{{ number_format((float)$camp->attributed_revenue, 2) }}</div>
                                <div class="text-[10px] {{ $camp->roi ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $camp->roi ? "{$camp->roi}x ROI" : 'No ROI data' }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                @if(in_array($camp->status, ['active', 'paused'], true))
                                    <form action="{{ route('seller.ads.toggle', $camp) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded text-xs font-bold border {{ $camp->status === 'active' ? 'border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40' : 'border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' }}">
                                            {{ $camp->status === 'active' ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('seller.ads.show', $camp) }}" class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs inline-block transition">
                                    Details
                                </a>

                                @if($camp->status !== 'active')
                                    <form action="{{ route('seller.ads.destroy', $camp) }}" method="POST" class="inline" data-confirm="Are you sure you want to delete this ad campaign?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition" title="Delete">
                                            &times;
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="text-2xl">📢</div>
                                    <div class="font-bold text-slate-700 dark:text-slate-300 text-sm">No ad campaigns found</div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Launch a sponsored listing campaign to boost visibility across relevant product categories.
                                    </p>
                                    <a href="{{ route('seller.ads.create') }}" class="inline-block mt-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-2xs">
                                        Create First Campaign &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
