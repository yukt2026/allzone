@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Featured Marketing Campaigns'])

@section('content')
<div class="space-y-6" x-data="campaignManager()">

    <!-- Page Header -->
    <x-page-header 
        title="Marketing Campaigns & Featured Events" 
        subtitle="Manage seasonal mega sales, flash deal collections, category promotions, seller-specific campaigns, and store promotions."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Campaigns' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                {{ $metrics['active'] }} Active Campaigns
            </span>
        </x-slot>

        <x-slot name="actions">
            <button 
                type="button"
                @click="openModal()" 
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl shadow-xs flex items-center space-x-1.5 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Campaign</span>
            </button>
        </x-slot>
    </x-page-header>

    <!-- Metrics Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Campaigns</div>
            <div class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ $metrics['total'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">All created events</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-emerald-500">
            <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Active Live</div>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['active'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Running on storefront</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-indigo-500">
            <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Scheduled</div>
            <div class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $metrics['scheduled'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Upcoming promotions</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-amber-500">
            <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Flash & Festive</div>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $metrics['flash_sales'] + $metrics['festive'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">High velocity events</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-teal-500">
            <div class="text-[11px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">Active Merchants</div>
            <div class="text-2xl font-black font-mono text-teal-600 dark:text-teal-400 mt-1">{{ $metrics['total_participating_sellers'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Opted-in sellers</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-purple-500">
            <div class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Enrolled SKUs</div>
            <div class="text-2xl font-black font-mono text-purple-600 dark:text-purple-400 mt-1">{{ $metrics['total_enrolled_products'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Discounted listings</div>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row gap-4 justify-between items-center">
        <div class="flex flex-wrap items-center gap-1.5">
            @php 
                $currentType = request('type', 'all');
                $currentStatus = request('status', 'all');
            @endphp
            
            <a href="{{ request()->fullUrlWithQuery(['type' => 'all', 'status' => 'all', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentType === 'all' && $currentStatus === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                All Campaigns
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'sale', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ in_array($currentType, ['sale', 'flash_sale', 'clearance', 'seasonal', 'festive_season']) ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Sale Campaigns
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'discount', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentType === 'discount' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Discount Deals
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'category', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentType === 'category' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Category Promos
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'seller', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentType === 'seller' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Seller-Specific
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'product', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentType === 'product' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Product Promotions
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Active Live
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status' => 'scheduled', 'page' => 1]) }}" 
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $currentStatus === 'scheduled' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                Scheduled
            </a>
        </div>

        <form method="GET" action="{{ route('admin.campaigns.index') }}" class="flex items-center space-x-2 w-full md:w-auto">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative w-full md:w-64">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search campaign, code, or description..." 
                    class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 dark:text-white pl-8 pr-3 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                Filter
            </button>
            @if(request('search') || (request('type') && request('type') !== 'all') || (request('status') && request('status') !== 'all'))
                <a href="{{ route('admin.campaigns.index') }}" class="px-2.5 py-2 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Campaigns Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                    <tr>
                        <th class="px-6 py-3.5">Campaign Name &amp; URL</th>
                        <th class="px-6 py-3.5">Type &amp; Rules</th>
                        <th class="px-6 py-3.5">Active Window</th>
                        <th class="px-6 py-3.5 text-center">Sellers</th>
                        <th class="px-6 py-3.5 text-center">Products</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($campaigns as $camp)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ $camp->name }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5 flex items-center space-x-1">
                                <a href="{{ route('campaign.show', $camp->slug) }}" target="_blank" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 hover:underline flex items-center space-x-0.5" title="View public storefront campaign page">
                                    <span>/campaign/{{ $camp->slug }}</span>
                                    <svg class="w-2.5 h-2.5 inline ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                @if($camp->coupon_code)
                                    <span class="ml-1 px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold uppercase text-[9px]">
                                        Code: {{ $camp->coupon_code }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                {{ $camp->type_label }}
                            </span>
                            <div class="text-[11px] text-slate-500 mt-1">
                                @if($camp->discount_type === 'flat' && $camp->discount_value)
                                    Flat ₹{{ number_format((float) $camp->discount_value, 2) }} OFF
                                @else
                                    {{ $camp->min_discount_percentage }}% min
                                    @if($camp->max_discount_percentage) - {{ $camp->max_discount_percentage }}% @endif
                                @endif
                                @if($camp->max_discount)
                                    (Cap: ₹{{ number_format((float) $camp->max_discount, 0) }})
                                @endif
                            </div>
                            @if($camp->category)
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                    Category: {{ $camp->category->name }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500 font-mono text-[11px]">
                            @if($camp->starts_at || $camp->ends_at)
                                <div class="text-slate-700 dark:text-slate-300 font-medium">
                                    {{ $camp->starts_at ? $camp->starts_at->format('d M Y') : 'Immediate' }} &rarr; 
                                    {{ $camp->ends_at ? $camp->ends_at->format('d M Y') : 'Indefinite' }}
                                </div>
                            @else
                                <span class="inline-flex items-center text-slate-400">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Always Active
                                </span>
                            @endif
                            @if($camp->opt_in_deadline)
                                <div class="text-[10px] text-amber-600 mt-0.5">
                                    Opt-in by {{ $camp->opt_in_deadline->format('d M Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
                            {{ $camp->participations_count }}
                        </td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-800 dark:text-slate-200">
                            {{ $camp->participating_products_count }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $status = $camp->status ?? ($camp->is_active ? 'active' : 'paused');
                                $isScheduled = $camp->starts_at && $camp->starts_at->isFuture();
                                if ($isScheduled && $camp->is_active) $status = 'scheduled';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400' : '' }}
                                {{ $status === 'scheduled' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-400' : '' }}
                                {{ $status === 'paused' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-400' : '' }}
                                {{ $status === 'ended' || $status === 'expired' ? 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-400' : '' }}
                                {{ $status === 'draft' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}
                            ">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $status === 'active' ? 'bg-emerald-500' : ($status === 'scheduled' ? 'bg-indigo-500' : 'bg-slate-400') }}"></span>
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <a 
                                    href="{{ route('admin.campaigns.show', $camp) }}" 
                                    class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition" 
                                    title="View Enrolled Sellers & Performance"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>

                                @if($camp->status !== 'active')
                                    <form method="POST" action="{{ route('admin.campaigns.publish', $camp) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Publish / Activate Campaign">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.campaigns.pause', $camp) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Pause Campaign">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </form>
                                @endif

                                @if($camp->status !== 'ended')
                                    <form method="POST" action="{{ route('admin.campaigns.end', $camp) }}" class="inline" data-confirm="Immediately end this campaign?">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="End Campaign">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                                        </button>
                                    </form>
                                @endif

                                <button 
                                    type="button"
                                    @click="editCampaign(@js($camp))" 
                                    class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition" 
                                    title="Edit Campaign"
                                >
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <form method="POST" action="{{ route('admin.campaigns.destroy', $camp) }}" class="inline" data-confirm="Delete this marketing campaign?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition" title="Delete Campaign">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                            <div class="max-w-sm mx-auto text-center space-y-3">
                                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                                <div class="text-sm font-semibold text-slate-900 dark:text-white">No marketing campaigns match query</div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Create campaigns to group promotional discounts, highlight festival deals, and boost sales across multiple merchants.</p>
                                <button 
                                    type="button" 
                                    @click="openModal()" 
                                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-xs transition"
                                >
                                    Create First Campaign
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Campaign Modal -->
    <x-modal name="campaign-modal" size="lg">
        <form :action="formUrl" method="POST" class="flex flex-col min-h-0">
            @csrf
            <template x-if="editingCampaign">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xs">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="editingCampaign ? 'Edit Marketing Campaign' : 'Create New Promotional Campaign'"></h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configure campaign type, discount rules, merchant eligibility, and budget limits.</p>
                </div>
                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'campaign-modal')" 
                    class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 space-y-4 overflow-y-auto max-h-[75vh] text-left">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Campaign Title *</label>
                        <input 
                            type="text" 
                            name="name" 
                            required 
                            x-model="form.name" 
                            placeholder="e.g. Great Indian Festive Sale" 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Campaign Type *</label>
                        <select 
                            name="type" 
                            x-model="form.type" 
                            required
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                            <optgroup label="Sale Campaigns">
                                <option value="sale">Sale Campaign</option>
                                <option value="flash_sale">Flash Sale</option>
                                <option value="festive_season">Festive Season</option>
                                <option value="seasonal">Seasonal Mega Sale</option>
                                <option value="clearance">Clearance Event</option>
                            </optgroup>
                            <optgroup label="Targeted Promotions">
                                <option value="discount">Discount Campaign (Markdown)</option>
                                <option value="coupon">Coupon Campaign</option>
                                <option value="category">Category Promotion</option>
                                <option value="seller">Seller-Specific Promotion</option>
                                <option value="product">Product-Specific Promotion</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Banner & Coupon Code -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Banner Image URL / Asset Path</label>
                        <input 
                            type="text" 
                            name="banner" 
                            x-model="form.banner" 
                            placeholder="https://... or /storage/banners/..." 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Associated Coupon Code (Optional)</label>
                        <input 
                            type="text" 
                            name="coupon_code" 
                            x-model="form.coupon_code" 
                            placeholder="e.g. ALLZONEFEST20" 
                            class="w-full text-xs font-mono uppercase rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Promotional Description</label>
                    <textarea 
                        name="description" 
                        rows="2" 
                        x-model="form.description" 
                        placeholder="Short summary of promotional highlights, target audiences, and discounts..."
                        class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                    ></textarea>
                </div>

                <!-- Validity Schedule -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Starts At (Schedule)</label>
                        <input 
                            type="datetime-local" 
                            name="starts_at" 
                            x-model="form.starts_at" 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Ends At (Schedule)</label>
                        <input 
                            type="datetime-local" 
                            name="ends_at" 
                            x-model="form.ends_at" 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Seller Opt-in Deadline</label>
                        <input 
                            type="datetime-local" 
                            name="opt_in_deadline" 
                            x-model="form.opt_in_deadline" 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                        >
                    </div>
                </div>

                <!-- Discount Configuration -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Discount Configuration</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Discount Type</label>
                            <select 
                                name="discount_type" 
                                x-model="form.discount_type" 
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2"
                            >
                                <option value="percentage">Percentage (%)</option>
                                <option value="flat">Flat Amount (₹)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Min Discount (%) *</label>
                            <input 
                                type="number" 
                                step="0.5" 
                                min="1" 
                                max="99" 
                                name="min_discount_percentage" 
                                x-model="form.min_discount_percentage" 
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Max Discount (%)</label>
                            <input 
                                type="number" 
                                step="0.5" 
                                min="1" 
                                max="99" 
                                name="max_discount_percentage" 
                                x-model="form.max_discount_percentage" 
                                placeholder="e.g. 50"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2"
                            >
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Max Discount Cap (₹)</label>
                            <input 
                                type="number" 
                                step="1" 
                                min="0" 
                                name="max_discount" 
                                x-model="form.max_discount" 
                                placeholder="e.g. 500"
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2"
                            >
                        </div>
                    </div>
                </div>

                <!-- Eligibility Rules & Limits -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Restricted Category</label>
                        <select 
                            name="applicable_category_id" 
                            x-model="form.applicable_category_id" 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">All Categories Eligible</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Min Product Rating (Stars)</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            min="1" 
                            max="5" 
                            name="min_product_rating" 
                            x-model="form.min_product_rating" 
                            placeholder="e.g. 3.5"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Max Products Per Seller</label>
                        <input 
                            type="number" 
                            min="1" 
                            name="max_seller_products" 
                            x-model="form.max_seller_products" 
                            placeholder="e.g. 10 (empty for unlimited)"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                </div>

                <!-- Budget & Usage Limits -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Promotional Budget Limit (₹)</label>
                        <input 
                            type="number" 
                            step="100" 
                            min="0" 
                            name="budget_limit" 
                            x-model="form.budget_limit" 
                            placeholder="Optional total budget (e.g. 100000)"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Maximum Order Usage Limit</label>
                        <input 
                            type="number" 
                            min="1" 
                            name="usage_limit" 
                            x-model="form.usage_limit" 
                            placeholder="Optional total claims (e.g. 5000)"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Terms &amp; Conditions</label>
                    <textarea 
                        name="terms_and_conditions" 
                        rows="2" 
                        x-model="form.terms_and_conditions" 
                        placeholder="Seller participation terms, discount lock guarantee, cancellation policies..."
                        class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                    ></textarea>
                </div>

                <!-- Toggles & Governance Policies -->
                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <input 
                            type="checkbox" 
                            id="camp_active" 
                            name="is_active" 
                            value="1" 
                            x-model="form.is_active" 
                            class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                        >
                        <label for="camp_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Campaign is active immediately
                        </label>
                    </div>

                    <div class="flex items-center space-x-2.5">
                        <input 
                            type="checkbox" 
                            id="camp_requires_approval" 
                            name="participation_requires_approval" 
                            value="1" 
                            x-model="form.participation_requires_approval" 
                            class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                        >
                        <label for="camp_requires_approval" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Require Admin approval for seller participation (manual review)
                        </label>
                    </div>

                    <div class="flex items-center space-x-2.5">
                        <input 
                            type="checkbox" 
                            id="camp_opt_out" 
                            name="allow_opt_out" 
                            value="1" 
                            x-model="form.allow_opt_out" 
                            class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                        >
                        <label for="camp_opt_out" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Allow seller opt-out after enrollment
                        </label>
                    </div>
                </div>
            </div>

            <!-- Modal Action Footer -->
            <div class="px-6 py-3.5 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xs border-t border-slate-200 dark:border-slate-800 flex justify-end space-x-2 shrink-0">
                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'campaign-modal')" 
                    class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center space-x-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="editingCampaign ? 'Update Campaign' : 'Save Campaign'"></span>
                </button>
            </div>
        </form>
    </x-modal>

</div>

<script>
function campaignManager() {
    return {
        editingCampaign: null,
        formUrl: '{{ route("admin.campaigns.store") }}',
        form: {
            name: '',
            type: 'sale',
            description: '',
            banner: '',
            coupon_code: '',
            starts_at: '',
            ends_at: '',
            opt_in_deadline: '',
            discount_type: 'percentage',
            min_discount_percentage: 5.0,
            max_discount_percentage: '',
            max_discount: '',
            min_product_rating: '',
            applicable_category_id: '',
            max_seller_products: '',
            budget_limit: '',
            usage_limit: '',
            terms_and_conditions: '',
            allow_opt_out: true,
            participation_requires_approval: false,
            is_active: true
        },
        openModal() {
            this.editingCampaign = null;
            this.formUrl = '{{ route("admin.campaigns.store") }}';
            this.form = {
                name: '',
                type: 'sale',
                description: '',
                banner: '',
                coupon_code: '',
                starts_at: '',
                ends_at: '',
                opt_in_deadline: '',
                discount_type: 'percentage',
                min_discount_percentage: 5.0,
                max_discount_percentage: '',
                max_discount: '',
                min_product_rating: '',
                applicable_category_id: '',
                max_seller_products: '',
                budget_limit: '',
                usage_limit: '',
                terms_and_conditions: '',
                allow_opt_out: true,
                participation_requires_approval: false,
                is_active: true
            };
            this.$dispatch('open-modal', 'campaign-modal');
        },
        editCampaign(campaign) {
            this.editingCampaign = campaign;
            this.formUrl = `/admin/campaigns/${campaign.id}`;

            this.form = {
                name: campaign.name || '',
                type: campaign.type || 'sale',
                description: campaign.description || '',
                banner: campaign.banner || '',
                coupon_code: campaign.coupon_code || '',
                starts_at: campaign.starts_at ? campaign.starts_at.slice(0, 16) : '',
                ends_at: campaign.ends_at ? campaign.ends_at.slice(0, 16) : '',
                opt_in_deadline: campaign.opt_in_deadline ? campaign.opt_in_deadline.slice(0, 16) : '',
                discount_type: campaign.discount_type || 'percentage',
                min_discount_percentage: campaign.min_discount_percentage || 5.0,
                max_discount_percentage: campaign.max_discount_percentage || '',
                max_discount: campaign.max_discount || '',
                min_product_rating: campaign.min_product_rating || '',
                applicable_category_id: campaign.applicable_category_id || '',
                max_seller_products: campaign.max_seller_products || '',
                budget_limit: campaign.budget_limit || '',
                usage_limit: campaign.usage_limit || '',
                terms_and_conditions: campaign.terms_and_conditions || '',
                allow_opt_out: campaign.allow_opt_out !== false,
                participation_requires_approval: Boolean(campaign.participation_requires_approval),
                is_active: Boolean(campaign.is_active)
            };
            this.$dispatch('open-modal', 'campaign-modal');
        },
        closeModal() {
            this.$dispatch('close-modal', 'campaign-modal');
            this.editingCampaign = null;
        }
    };
}
</script>
@endsection
