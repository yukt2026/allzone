@extends('layouts.dashboard', ['portal' => 'seller', 'title' => "Ad Campaign: {$campaign->name} - ALLZONE Supplier Hub"])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('seller.ads.index') }}" class="hover:text-slate-700">Advertising Hub</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">{{ $campaign->name }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $campaign->name }}</h1>
                <span class="px-2.5 py-0.5 rounded text-xs font-extrabold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ str_replace('_', ' ', $campaign->type) }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold 
                    {{ $campaign->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' }}
                    {{ $campaign->status === 'pending_approval' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                    {{ $campaign->status === 'paused' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}
                    {{ $campaign->status === 'rejected' ? 'bg-rose-100 text-rose-800 border border-rose-200' : '' }}
                    {{ $campaign->status === 'draft' ? 'bg-sky-100 text-sky-800 border border-sky-200' : '' }}
                ">
                    {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            @if(in_array($campaign->status, ['active', 'paused'], true))
                <form action="{{ route('seller.ads.toggle', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-bold border {{ $campaign->status === 'active' ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }} transition shadow-2xs">
                        {{ $campaign->status === 'active' ? 'Pause Campaign' : 'Resume Campaign' }}
                    </button>
                </form>
            @endif

            <a href="{{ route('seller.ads.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-2xs">
                &larr; Back to Campaigns
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

    @if($campaign->status === 'rejected' && $campaign->rejection_reason)
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs space-y-1">
            <div class="font-bold uppercase tracking-wider text-[10px]">Campaign Rejected by Administrator:</div>
            <p>{{ $campaign->rejection_reason }}</p>
        </div>
    @endif

    <!-- Performance Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ad Spend</span>
            <div class="text-xl font-black text-slate-900 font-mono mt-1">₹{{ number_format((float)$campaign->spent_amount, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Cap: ₹{{ number_format((float)$campaign->total_budget, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attributed Revenue</span>
            <div class="text-xl font-black text-emerald-700 font-mono mt-1">₹{{ number_format((float)$campaign->attributed_revenue, 2) }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">Direct purchases</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Campaign ROI</span>
            <div class="text-xl font-black font-mono mt-1 {{ $campaign->roi ? 'text-indigo-700' : 'text-slate-400' }}">
                {{ $campaign->roi ? "{$campaign->roi}x" : 'N/A' }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $campaign->roi ? 'Verified attribution' : 'Requires spend' }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Impressions</span>
            <div class="text-xl font-black text-slate-900 font-mono mt-1">{{ number_format($campaign->impressions_count) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Search &amp; category views</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Clicks</span>
            <div class="text-xl font-black text-slate-900 font-mono mt-1">{{ number_format($campaign->clicks_count) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">CTR: {{ $campaign->ctr }}%</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Orders</span>
            <div class="text-xl font-black text-indigo-700 font-mono mt-1">{{ $campaign->attributed_orders_count }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Attributed sales</div>
        </div>
    </div>

    <!-- Campaign Details Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Campaign Configuration</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="font-bold text-slate-500">Targeting Strategy</span>
                    <div class="font-black text-slate-900 text-sm capitalize mt-1">
                        {{ str_replace('_', ' ', $campaign->targeting_type) }}
                    </div>
                    @if($campaign->targeting_payload && !empty($campaign->targeting_payload['keywords']))
                        <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                            Keywords: {{ implode(', ', $campaign->targeting_payload['keywords']) }}
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="font-bold text-slate-500">Budget Structure</span>
                    <div class="font-black text-slate-900 text-sm mt-1">
                        ₹{{ number_format((float)$campaign->daily_budget, 2) }}/day
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                        Total Cap: ₹{{ number_format((float)$campaign->total_budget, 2) }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="font-bold text-slate-500">Schedule</span>
                    <div class="font-black text-slate-900 text-sm mt-1">
                        {{ $campaign->starts_at?->format('d M Y') ?? 'Immediate' }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                        Ends: {{ $campaign->ends_at?->format('d M Y') ?? 'Until Budget Depleted' }}
                    </div>
                </div>
            </div>

            @if($campaign->headline)
                <div class="text-xs text-slate-700 pt-1">
                    <span class="font-bold text-slate-900">Promotional Headline:</span> {{ $campaign->headline }}
                </div>
            @endif

            @if($campaign->approved_at)
                <div class="text-[11px] text-slate-400 font-mono pt-1">
                    Verified and approved by ALLZONE administration on {{ $campaign->approved_at->format('d M Y, h:i A') }}.
                </div>
            @endif
        </div>

        @if($campaign->creative_image_url)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Creative Banner</span>
                <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 aspect-video flex items-center justify-center">
                    <img src="{{ $campaign->creative_image_url }}" alt="" class="w-full h-full object-cover">
                </div>
            </div>
        @else
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 flex flex-col justify-center items-center text-center space-y-2">
                <span class="text-2xl">🏷️</span>
                <span class="text-xs font-bold text-slate-700">Native Sponsored Listings</span>
                <p class="text-[11px] text-slate-500 max-w-xs">
                    This campaign utilizes standard product thumbnails and headlines in search result placements.
                </p>
            </div>
        @endif
    </div>

    <!-- Enrolled Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900">Enrolled Products ({{ $campaign->products->count() }})</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Product Name</th>
                        <th class="p-3">SKU</th>
                        <th class="p-3">Base Price</th>
                        <th class="p-3">Max Click Bid</th>
                        <th class="p-3">Views</th>
                        <th class="p-3">Clicks</th>
                        <th class="p-3">Attributed Orders</th>
                        <th class="p-3">Attributed Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaign->products as $item)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3 font-bold text-slate-900 max-w-xs truncate">
                                {{ $item->product?->title ?? 'Product #' . $item->product_id }}
                            </td>
                            <td class="p-3 font-mono text-slate-500">
                                {{ $item->product?->sku ?? 'N/A' }}
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-800">
                                ₹{{ number_format((float)($item->product?->effective_price ?? 0), 2) }}
                            </td>
                            <td class="p-3 font-mono text-emerald-700 font-bold">
                                ₹{{ number_format((float)$item->bid_amount, 2) }}
                            </td>
                            <td class="p-3 font-mono">{{ number_format($item->impressions) }}</td>
                            <td class="p-3 font-mono">{{ number_format($item->clicks) }}</td>
                            <td class="p-3 font-mono font-bold text-indigo-700">{{ $item->attributed_orders }}</td>
                            <td class="p-3 font-mono font-bold text-emerald-700">₹{{ number_format((float)$item->attributed_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                No products associated with this campaign.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
