@extends('layouts.dashboard', ['portal' => 'admin', 'title' => "Campaign: {$campaign->name}"])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-700">Admin Central</a>
                <span>/</span>
                <a href="{{ route('admin.campaigns.index') }}" class="hover:text-slate-700">Campaigns</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white font-bold">#{{ $campaign->id }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $campaign->name }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $campaign->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                    {{ $campaign->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $campaign->type_label }}
                </span>
                @if($campaign->status)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                        {{ ucfirst($campaign->status) }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if($campaign->status !== 'active')
                <form action="{{ route('admin.campaigns.publish', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition">
                        Publish Campaign
                    </button>
                </form>
            @else
                <form action="{{ route('admin.campaigns.pause', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition">
                        Pause Campaign
                    </button>
                </form>
            @endif

            @if($campaign->status !== 'ended')
                <form action="{{ route('admin.campaigns.end', $campaign) }}" method="POST" onsubmit="return confirm('Immediately conclude and end this campaign?');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition">
                        End Campaign
                    </button>
                </form>
            @endif

            <form action="{{ route('admin.campaigns.toggle', $campaign) }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold border {{ $campaign->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }} transition">
                    {{ $campaign->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>

            <a href="{{ route('admin.campaigns.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white shadow-xs transition">
                Back to Campaigns
            </a>
        </div>
    </div>

    <!-- Campaign Performance & Health Metrics Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Enrolled Sellers</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $metrics['enrolled_sellers'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                @if($metrics['pending_approval_sellers'] > 0)
                    <span class="text-amber-600 font-bold">{{ $metrics['pending_approval_sellers'] }} awaiting approval</span>
                @else
                    {{ $metrics['opted_out_sellers'] }} opted out
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-emerald-500">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Enrolled Products</span>
            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 font-mono mt-1">{{ $metrics['active_products'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Total catalog items: {{ $metrics['total_products'] }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-indigo-500">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Average Discount</span>
            <div class="text-2xl font-black text-indigo-700 dark:text-indigo-400 font-mono mt-1">{{ $metrics['avg_discount'] }}%</div>
            <div class="text-[11px] text-slate-500 mt-1">Min required: {{ $campaign->min_discount_percentage }}%</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-teal-500">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Orders Generated</span>
            <div class="text-2xl font-black text-teal-600 dark:text-teal-400 font-mono mt-1">{{ number_format($metrics['orders_count']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Direct item conversions</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-purple-500">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Campaign GMV</span>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono mt-1">₹{{ number_format($metrics['gmv'], 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Total revenue generated</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Timeline Status</span>
            <div class="text-lg font-black text-slate-800 dark:text-slate-200 mt-1 uppercase">{{ $campaign->timeline_status }}</div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $campaign->starts_at?->format('d M') ?? 'Immediate' }} - {{ $campaign->ends_at?->format('d M Y') ?? 'Ongoing' }}
            </div>
        </div>
    </div>

    <!-- Campaign Rules & Configuration -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Promotional Rules &amp; Eligibility Policy</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                <span class="font-bold text-slate-500 block mb-1">Discount Constraints</span>
                <div class="font-bold text-slate-900 dark:text-white">
                    {{ $campaign->min_discount_percentage }}% minimum
                    @if($campaign->max_discount_percentage)
                        / {{ $campaign->max_discount_percentage }}% maximum
                    @endif
                </div>
                <div class="text-slate-500 text-[11px] mt-0.5">
                    Type: {{ ucfirst($campaign->discount_type ?? 'percentage') }}
                    @if($campaign->max_discount)
                        &bull; Cap: ₹{{ number_format((float) $campaign->max_discount, 0) }}
                    @endif
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                <span class="font-bold text-slate-500 block mb-1">Category &amp; Quality Scope</span>
                <div class="font-bold text-slate-900 dark:text-white">
                    {{ $campaign->category?->name ?? 'All Categories Allowed' }}
                </div>
                <div class="text-slate-500 text-[11px] mt-0.5">
                    Min Rating: {{ $campaign->min_product_rating ? "{$campaign->min_product_rating}★" : 'No minimum' }}
                    @if($campaign->max_seller_products)
                        &bull; Max {{ $campaign->max_seller_products }} SKUs/seller
                    @endif
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                <span class="font-bold text-slate-500 block mb-1">Opt-in &amp; Review Policy</span>
                <div class="font-bold text-slate-900 dark:text-white">
                    {{ $campaign->participation_requires_approval ? 'Manual Admin Approval Required' : 'Instant Automatic Enrollment' }}
                </div>
                <div class="text-slate-500 text-[11px] mt-0.5">
                    Opt-out: {{ $campaign->allow_opt_out ? 'Permitted anytime' : 'Locked after launch' }}
                </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                <span class="font-bold text-slate-500 block mb-1">Budget &amp; Usage Controls</span>
                <div class="font-bold text-slate-900 dark:text-white">
                    Budget: {{ $campaign->budget_limit ? '₹' . number_format((float) $campaign->budget_limit, 0) : 'Unlimited' }}
                </div>
                <div class="text-slate-500 text-[11px] mt-0.5">
                    Claims Limit: {{ $campaign->usage_limit ? number_format($campaign->usage_limit) : 'Unlimited' }}
                    @if($campaign->coupon_code)
                        &bull; Code: <span class="font-mono font-bold">{{ $campaign->coupon_code }}</span>
                    @endif
                </div>
            </div>
        </div>

        @if($campaign->description)
            <div class="pt-2 text-xs text-slate-600 dark:text-slate-300">
                <strong class="text-slate-900 dark:text-white">Description:</strong> {{ $campaign->description }}
            </div>
        @endif

        @if($campaign->terms_and_conditions)
            <div class="p-3.5 rounded-2xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 text-xs text-amber-900 dark:text-amber-200">
                <span class="font-bold">Terms &amp; Conditions:</span>
                <p class="mt-1 leading-relaxed">{{ $campaign->terms_and_conditions }}</p>
            </div>
        @endif
    </div>

    <!-- Participating Sellers List -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Participating Sellers ({{ $participations->count() }})</h2>
            <span class="text-xs text-slate-400">Merchant opt-ins and governance</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3.5">Seller</th>
                        <th class="p-3.5">Business Name</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Enrolled At</th>
                        <th class="p-3.5 text-center">Products Enrolled</th>
                        <th class="p-3.5 text-right">Admin Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($participations as $participation)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition">
                            <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                {{ $participation->seller?->user?->name ?? 'Merchant #' . $participation->seller_id }}
                                @if($participation->seller?->user?->email)
                                    <div class="text-[10px] text-slate-400 font-normal">{{ $participation->seller->user->email }}</div>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-300">
                                @if($participation->seller)
                                    <a href="{{ route('admin.sellers.show', $participation->seller) }}" class="hover:text-emerald-600 font-bold">
                                        {{ $participation->seller->business_name }}
                                    </a>
                                @else
                                    <span>N/A</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($participation->status === 'enrolled' || $participation->status === 'approved')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200">
                                        Enrolled
                                    </span>
                                @elseif($participation->status === 'pending_approval')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 animate-pulse">
                                        Pending Review
                                    </span>
                                @elseif($participation->status === 'rejected')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ ucfirst($participation->status) }}
                                    </span>
                                @endif

                                @if($participation->rejection_reason)
                                    <div class="text-[10px] text-rose-500 mt-1 max-w-xs">{{ $participation->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-500 font-mono text-[11px]">
                                {{ $participation->enrolled_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            </td>
                            <td class="p-3.5 text-center font-bold text-slate-800 dark:text-slate-200 font-mono">
                                {{ $participation->products->count() }} items
                            </td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    @if($participation->status !== 'enrolled')
                                        <form method="POST" action="{{ route('admin.campaigns.participations.approve', [$campaign, $participation]) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition" title="Approve seller participation">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if($participation->status !== 'rejected')
                                        <form method="POST" action="{{ route('admin.campaigns.participations.reject', [$campaign, $participation]) }}" onsubmit="return confirm('Reject this seller participation?');">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition" title="Reject seller participation">
                                                Reject
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                No sellers have enrolled in this campaign yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Participating Products List -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Enrolled Products ({{ $participatingProducts->total() }})</h2>
            <span class="text-xs text-slate-400">Discounted listings locked for campaign</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3.5">Product Title</th>
                        <th class="p-3.5">Merchant Seller</th>
                        <th class="p-3.5">Original Base Price</th>
                        <th class="p-3.5 text-center">Campaign Discount</th>
                        <th class="p-3.5">Campaign Price</th>
                        <th class="p-3.5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($participatingProducts as $cp)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition">
                            <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                {{ $cp->product?->title ?? 'Product #' . $cp->product_id }}
                            </td>
                            <td class="p-3.5 text-slate-600 dark:text-slate-300">
                                {{ $cp->seller?->business_name ?? 'Seller #' . $cp->seller_id }}
                            </td>
                            <td class="p-3.5 font-mono text-slate-500 line-through">
                                ₹{{ number_format((float) $cp->original_price, 2) }}
                            </td>
                            <td class="p-3.5 text-center">
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold text-[11px]">
                                    {{ $cp->discount_percentage }}% OFF
                                </span>
                            </td>
                            <td class="p-3.5 font-mono font-black text-emerald-600 dark:text-emerald-400">
                                ₹{{ number_format((float) $cp->campaign_price, 2) }}
                            </td>
                            <td class="p-3.5 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                    {{ $cp->status === 'enrolled' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $cp->status === 'pending_approval' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $cp->status === 'rejected' ? 'bg-rose-100 text-rose-800' : '' }}
                                    {{ $cp->status === 'opted_out' ? 'bg-slate-100 text-slate-600' : '' }}
                                ">
                                    {{ ucfirst(str_replace('_', ' ', (string) $cp->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                No products enrolled in this campaign yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($participatingProducts->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $participatingProducts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
