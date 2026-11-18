@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Promotions & Sale Opt-in - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Promotions Hub</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Promotions &amp; Sale Opt-in</h1>
            <p class="text-xs text-slate-500 mt-1">
                Participate in verified ALLZONE seasonal campaigns, flash sales, and festival events to boost product discoverability.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('seller.promotions.offers') }}" class="px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                Daily Offers &amp; Flexi Deals &rarr;
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Top Metrics Overview Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Metric 1: Open Campaigns -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Campaigns</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['available_campaigns'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Open for seller opt-in</div>
        </div>

        <!-- Metric 2: Live Sales -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Live On Storefront</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">{{ $metrics['live_campaigns'] }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">✓ Active promotional events</div>
        </div>

        <!-- Metric 3: Enrolled Campaigns -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">My Enrolled Sales</span>
            <div class="text-2xl font-black text-indigo-700 font-mono mt-1">{{ $metrics['participating_campaigns'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Active seller participations</div>
        </div>

        <!-- Metric 4: Enrolled Products -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Products Enrolled</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['enrolled_products_count'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Avg discount: {{ $metrics['avg_discount'] }}%</div>
        </div>
    </div>

    <!-- ALLZONE Discount Control Assurance Banner -->
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #134e4a 100%); color: #ffffff;">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/30 border border-emerald-400/40 text-emerald-200">
                    ALLZONE TRANSPARENT GUARANTEE
                </span>
                <span class="text-xs text-emerald-200 font-medium">100% Seller Price Control</span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-white">
                Seller-Locked Discounts &amp; Direct Margins
            </h3>
            <p class="text-xs text-emerald-100/90 leading-relaxed max-w-2xl">
                ALLZONE never alters your product prices without explicit authorization. You decide which products enter promotional campaigns, select your own discount levels, and retain full visibility over settlement proceeds.
            </p>
        </div>
        <div class="shrink-0 flex items-center space-x-3 bg-white/10 rounded-xl p-3 border border-white/10">
            <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-emerald-200">Transparent Margin</div>
                <div class="text-xs font-extrabold text-white">Audit Formula Active</div>
            </div>
            <span class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-300 font-bold">✓</span>
        </div>
    </div>

    <!-- Tabs & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Navigation Tabs -->
            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                @php
                    $tabs = [
                        'available' => 'Available Campaigns',
                        'upcoming' => 'Upcoming (' . $metrics['upcoming_campaigns'] . ')',
                        'live' => 'Live Sales (' . $metrics['live_campaigns'] . ')',
                        'participating' => 'My Campaigns (' . $metrics['participating_campaigns'] . ')',
                        'expired' => 'Expired',
                        'history' => 'Participation History',
                    ];
                @endphp

                @foreach($tabs as $tabKey => $tabLabel)
                    <a 
                        href="{{ route('seller.promotions.index', ['tab' => $tabKey, 'search' => $search]) }}"
                        class="px-3.5 py-1.5 rounded-lg transition {{ $tab === $tabKey ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search Bar -->
            <form action="{{ route('seller.promotions.index') }}" method="GET" class="flex items-center space-x-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search campaigns..." 
                        class="w-56 px-3 py-1.5 pl-8 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold">Search</button>
                @if($search !== '')
                    <a href="{{ route('seller.promotions.index', ['tab' => $tab]) }}" class="px-2 py-1.5 text-xs text-slate-500 hover:text-slate-800">Clear</a>
                @endif
            </form>
        </div>

        <!-- Campaigns Grid -->
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($campaigns as $camp)
                    @php
                        $userPart = $participations->get($camp->id);
                        $isEnrolled = $userPart && $userPart->status === 'enrolled';
                        $isOptedOut = $userPart && $userPart->status === 'opted_out';
                        $enrolledItemsCount = $camp->participating_products_count ?? 0;
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 transition shadow-2xs hover:shadow-sm overflow-hidden flex flex-col justify-between">
                        <div>
                            <!-- Card Top Header -->
                            <div class="p-4 bg-slate-50/70 border-b border-slate-100 flex items-start justify-between gap-3">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ $camp->type_label }}
                                    </span>
                                    <h3 class="font-extrabold text-sm text-slate-900 mt-1 line-clamp-1">
                                        {{ $camp->name }}
                                    </h3>
                                </div>

                                <!-- Status Badge -->
                                <div>
                                    @if($isEnrolled)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1">
                                            <span>✓</span>
                                            <span>Enrolled</span>
                                        </span>
                                    @elseif($camp->isLive())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                            ● Live
                                        </span>
                                    @elseif($camp->isUpcoming())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 border border-sky-200">
                                            Upcoming
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            Ended
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 space-y-3 text-xs">
                                <p class="text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ $camp->description ?: 'Admin-curated promotional campaign with dedicated customer placement.' }}
                                </p>

                                <!-- Discount & Criteria Rules -->
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1.5">
                                    <div class="flex items-center justify-between text-slate-700">
                                        <span class="font-semibold text-slate-500">Discount Requirement:</span>
                                        <span class="font-bold text-slate-900 font-mono">
                                            Min {{ $camp->min_discount_percentage }}%
                                            @if($camp->max_discount_percentage)
                                                - {{ $camp->max_discount_percentage }}%
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-700">
                                        <span class="font-semibold text-slate-500">Eligible Category:</span>
                                        <span class="font-bold text-slate-900">
                                            {{ $camp->category?->name ?? 'All Categories' }}
                                        </span>
                                    </div>
                                    @if($camp->min_product_rating)
                                        <div class="flex items-center justify-between text-slate-700">
                                            <span class="font-semibold text-slate-500">Min Rating:</span>
                                            <span class="font-bold text-amber-700 font-mono">
                                                {{ $camp->min_product_rating }}★ &amp; above
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Timeline Info -->
                                <div class="text-[11px] text-slate-500 space-y-1 font-mono">
                                    <div>
                                        <span class="font-sans text-slate-400">Sale Dates:</span>
                                        <span class="font-bold text-slate-700">
                                            {{ $camp->starts_at?->format('d M') ?? 'Now' }} &rarr; {{ $camp->ends_at?->format('d M Y') ?? 'Ongoing' }}
                                        </span>
                                    </div>
                                    @if($camp->opt_in_deadline)
                                        <div class="text-rose-600 font-semibold">
                                            <span class="font-sans">Opt-in Deadline:</span>
                                            <span>{{ $camp->opt_in_deadline->format('d M Y, h:i A') }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($isEnrolled)
                                    <div class="p-2 rounded-lg bg-emerald-50 text-emerald-900 font-semibold text-[11px] flex items-center justify-between">
                                        <span>Your participating products:</span>
                                        <span class="font-mono font-bold">{{ $enrolledItemsCount }} active</span>
                                    </div>
                                @elseif($isOptedOut)
                                    <div class="p-2 rounded-lg bg-slate-100 text-slate-600 font-semibold text-[11px]">
                                        You opted out of this campaign.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer Action -->
                        <div class="p-4 pt-0">
                            <a 
                                href="{{ route('seller.promotions.show', $camp) }}"
                                class="w-full py-2 rounded-xl text-xs font-bold text-center block transition shadow-2xs {{ $isEnrolled ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}"
                            >
                                @if($isEnrolled)
                                    Manage Products &amp; Pricing &rarr;
                                @elseif($camp->isOptInOpen())
                                    Check Eligibility &amp; Opt In &rarr;
                                @else
                                    View Campaign Details &rarr;
                                @endif
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        <div class="max-w-sm mx-auto space-y-2">
                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-lg">
                                %
                            </div>
                            <div class="font-bold text-slate-700 text-sm">No promotional campaigns found</div>
                            <p class="text-xs text-slate-500">
                                @if($tab === 'participating')
                                    You have not enrolled in any campaigns yet. Browse available campaigns to opt in.
                                @else
                                    Check back soon. ALLZONE administrators configure festive and seasonal promotions periodically.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        @if($campaigns->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
