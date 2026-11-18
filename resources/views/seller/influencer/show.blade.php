@extends('layouts.dashboard', ['portal' => 'seller', 'title' => "Collaboration: {$collaboration->campaign_title} - ALLZONE Supplier Hub"])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('seller.influencer.index', ['tab' => 'my_collaborations']) }}" class="hover:text-slate-700">Influencer Collaborations</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">#{{ $collaboration->id }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $collaboration->campaign_title }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold 
                    {{ $collaboration->status === 'completed' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : '' }}
                    {{ $collaboration->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : '' }}
                    {{ $collaboration->status === 'accepted' ? 'bg-sky-100 text-sky-800 border border-sky-200' : '' }}
                    {{ $collaboration->status === 'pending' ? 'bg-amber-100 text-amber-800 border border-amber-200' : '' }}
                    {{ $collaboration->status === 'declined' ? 'bg-rose-100 text-rose-800 border border-rose-200' : '' }}
                ">
                    {{ ucfirst(str_replace('_', ' ', $collaboration->status)) }}
                </span>
            </div>
        </div>

        <div>
            <a href="{{ route('seller.influencer.index', ['tab' => 'my_collaborations']) }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-2xs">
                &larr; Back to Collaborations
            </a>
        </div>
    </div>

    <!-- Performance Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Agreed Budget</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">₹{{ number_format((float)$collaboration->proposed_budget, 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Creator collaboration fee</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attributed Revenue</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">₹{{ number_format((float)$collaboration->attributed_revenue, 2) }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">{{ $collaboration->attributed_orders }} verified orders</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Collaboration ROI</span>
            <div class="text-2xl font-black font-mono mt-1 {{ $collaboration->roi ? 'text-indigo-700' : 'text-slate-400' }}">
                {{ $collaboration->roi ? "{$collaboration->roi}x" : 'N/A' }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $collaboration->roi ? 'Attributed return ratio' : 'Pending orders' }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Audience Engagement</span>
            <div class="text-2xl font-black text-slate-800 font-mono mt-1">{{ number_format($collaboration->impressions) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ number_format($collaboration->clicks) }} referral clicks</div>
        </div>
    </div>

    <!-- Details Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Collaboration Proposal Details (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Proposal Details &amp; Deliverables</h2>

            @if($collaboration->deliverable_url)
                <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center justify-between">
                    <div>
                        <div class="font-bold">Verified Content Deliverable Published</div>
                        <div class="text-[11px] text-emerald-700 mt-0.5 truncate">{{ $collaboration->deliverable_url }}</div>
                    </div>
                    <a href="{{ $collaboration->deliverable_url }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition">
                        Open Post ↗
                    </a>
                </div>
            @endif

            <div class="space-y-2 text-xs">
                @if($collaboration->product)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-slate-400 font-bold text-[10px] uppercase">Target Product</span>
                            <div class="font-bold text-slate-900 mt-0.5">{{ $collaboration->product->title }}</div>
                        </div>
                        <span class="font-mono font-bold text-slate-700">₹{{ number_format((float)$collaboration->product->effective_price, 2) }}</span>
                    </div>
                @endif

                <div>
                    <span class="font-bold text-slate-700">Proposal Brief / Notes:</span>
                    <p class="text-slate-600 mt-1 leading-relaxed whitespace-pre-line">{{ $collaboration->proposal_notes ?: 'No specific notes provided.' }}</p>
                </div>

                @if($collaboration->admin_notes)
                    <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900">
                        <span class="font-bold">Admin Audit Notes:</span>
                        <p class="mt-0.5">{{ $collaboration->admin_notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Creator Profile Card (1 Column) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Creator Information</h2>

            <div class="space-y-3 text-xs">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-lg shrink-0">
                        {{ strtoupper(substr($collaboration->influencer?->name ?? 'C', 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-extrabold text-sm text-slate-900">{{ $collaboration->influencer?->name }}</div>
                        <div class="text-[11px] font-mono text-slate-500">{{ $collaboration->influencer?->handle }}</div>
                    </div>
                </div>

                <div class="space-y-1.5 pt-2 border-t border-slate-100">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Platform:</span>
                        <span class="font-bold text-slate-800 capitalize">{{ $collaboration->influencer?->platform }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Followers:</span>
                        <span class="font-mono font-bold text-slate-800">{{ number_format($collaboration->influencer?->verified_followers ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Niche:</span>
                        <span class="font-bold text-slate-800">{{ $collaboration->influencer?->category?->name ?? 'General' }}</span>
                    </div>
                </div>

                @if($collaboration->influencer?->bio)
                    <p class="text-[11px] text-slate-600 pt-2 border-t border-slate-100 leading-relaxed">
                        {{ $collaboration->influencer->bio }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
