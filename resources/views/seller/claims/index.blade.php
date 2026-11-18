@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Claims & Compensation - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
                <span>&gt;</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Claims &amp; Disputes</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Claims Management</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-800">
                    Carrier &amp; Return Disputes
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                File and track reimbursement claims for transit damage, missing items, and customer return discrepancies.
            </p>
        </div>

        <div class="flex items-center space-x-2 text-xs">
            <a 
                href="{{ route('seller.claims.export', ['tab' => $tab]) }}" 
                class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>

            <a 
                href="{{ route('seller.claims.create') }}" 
                class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-xs transition"
            >
                <span>✚</span>
                <span>Raise Claim</span>
            </a>
        </div>
    </div>

    <!-- Overview Performance Section -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-5 shadow-2xs space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Claims Overview Performance</h3>
            <span class="text-xs text-slate-400 dark:text-slate-500">Based on your actual store return &amp; claim history</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Left Card: Claim Approval Rate -->
            <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span>Claim Approval Rate</span>
                    <a href="{{ route('seller.claims.index', ['tab' => 'approved']) }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                        View Approved ({{ $claimStats['approved'] }})
                    </a>
                </div>
                <div class="flex items-baseline space-x-3">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $claimStats['approval_rate'] }}%</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        ({{ $claimStats['approved'] }} approved of {{ $claimStats['approved'] + $claimStats['rejected'] }} decided)
                    </span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span>Open / Under Review: <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ $claimStats['open'] }}</strong></span>
                    <span>Rejected: <strong class="text-rose-600 dark:text-rose-400 font-bold">{{ $claimStats['rejected'] }}</strong></span>
                </div>
            </div>

            <!-- Right Card: Claim Rate vs Delivered Returns -->
            <div class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span>Claim Filing Rate</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[11px]">Claims / Total Returns &amp; RTOs</span>
                </div>
                <div class="flex items-baseline space-x-3">
                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $claimStats['claim_rate'] }}%</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        ({{ $claimStats['total'] }} claims raised for {{ $claimStats['total_returns_and_rtos'] }} returns/RTOs)
                    </span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span>Total Returns &amp; RTOs: <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ $claimStats['total_returns_and_rtos'] }}</strong></span>
                    <span>Total Claims Raised: <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ $claimStats['total'] }}</strong></span>
                </div>
            </div>
        </div>

        <!-- 14-Day Trend + Rejection Reasons Insights -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-2">
            <!-- 14-Day Visual Trend Chart -->
            <div class="p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 space-y-3">
                <div class="flex items-center justify-between text-xs font-bold text-slate-800 dark:text-slate-200">
                    <span>14-Day Claims Trend</span>
                    <div class="flex items-center space-x-3 text-[11px] font-medium">
                        <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span><span class="text-slate-600 dark:text-slate-400">Claims Raised</span></span>
                        <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-slate-600 dark:text-slate-400">Approved</span></span>
                    </div>
                </div>

                <div class="h-28 flex items-end justify-between gap-1 pt-4 border-b border-slate-200 dark:border-slate-800">
                    @php $maxVal = max(1, max($claimStats['trend']['raised']), max($claimStats['trend']['approved'])); @endphp
                    @foreach($claimStats['trend']['labels'] as $idx => $label)
                        @php
                            $raisedH = round(($claimStats['trend']['raised'][$idx] / $maxVal) * 80);
                            $approvedH = round(($claimStats['trend']['approved'][$idx] / $maxVal) * 80);
                        @endphp
                        <div class="flex-1 flex flex-col items-center group relative">
                            <div class="flex items-end space-x-0.5 w-full justify-center">
                                <div class="w-2 bg-indigo-400 dark:bg-indigo-500 rounded-t" style="height: {{ max(2, $raisedH) }}px;"></div>
                                <div class="w-2 bg-emerald-400 dark:bg-emerald-500 rounded-t" style="height: {{ max(2, $approvedH) }}px;"></div>
                            </div>
                            <span class="text-[9px] text-slate-400 dark:text-slate-500 mt-1 scale-90">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Actual Rejection Reasons & Guidelines -->
            <div class="p-4 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 flex flex-col justify-between space-y-3">
                <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs mb-2">Primary Rejection Reasons (Your History)</h4>
                    @if(empty($claimStats['rejection_reasons']))
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg text-slate-500 dark:text-slate-400 text-xs text-center">
                            No claims have been rejected. All submitted claims comply with platform documentation standards.
                        </div>
                    @else
                        <div class="space-y-1.5">
                            @foreach($claimStats['rejection_reasons'] as $reason => $count)
                                <div class="p-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700 rounded-lg text-xs flex items-center justify-between text-slate-700 dark:text-slate-300">
                                    <span class="font-semibold line-clamp-1">{{ $reason }}</span>
                                    <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 text-[10px] font-bold border border-rose-200 dark:border-rose-800 shrink-0">
                                        {{ $count }} {{ Str::plural('claim', $count) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg text-[11px] text-amber-900 dark:text-amber-300 space-y-1">
                    <div class="font-bold flex items-center space-x-1 text-amber-950 dark:text-amber-200">
                        <span>💡 Operational Tip for Smooth Approval:</span>
                    </div>
                    <p class="text-amber-800 dark:text-amber-400">
                        Always record an uninterrupted video while unpacking damaged RTOs or returned parcels. Ensure shipping label and AWB barcode are visibly shown in the recording.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Claim Tracking Console & Main Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
        <!-- Filter Tabs -->
        <div class="flex items-center space-x-6 border-b border-slate-200 dark:border-slate-800 px-5 pt-2 text-xs font-semibold overflow-x-auto">
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'all'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'all' ? 'border-slate-900 dark:border-white text-slate-900 dark:text-white font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                All ({{ $counts['all'] }})
            </a>
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'open'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'open' ? 'border-blue-600 text-blue-700 dark:text-blue-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Open ({{ $counts['open'] }})
            </a>
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'under_review'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'under_review' ? 'border-amber-600 text-amber-700 dark:text-amber-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Under Review ({{ $counts['under_review'] }})
            </a>
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'approved'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'approved' ? 'border-emerald-600 text-emerald-700 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Approved ({{ $counts['approved'] }})
            </a>
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'rejected'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'rejected' ? 'border-rose-600 text-rose-700 dark:text-rose-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Rejected ({{ $counts['rejected'] }})
            </a>
            <a 
                href="{{ route('seller.claims.index', array_merge(request()->except('tab'), ['tab' => 'closed'])) }}" 
                class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === 'closed' ? 'border-slate-600 text-slate-700 dark:text-slate-400 font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Closed ({{ $counts['closed'] }})
            </a>
        </div>

        <!-- Search & Category Filters -->
        <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800">
            <form method="GET" action="{{ route('seller.claims.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="flex items-center space-x-2">
                    <select 
                        name="claim_type" 
                        onchange="this.form.submit()" 
                        class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500"
                    >
                        <option value="">All Categories</option>
                        @foreach(\App\Enums\ClaimType::cases() as $type)
                            <option value="{{ $type->value }}" {{ $claimType === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search Claim #, Order #, SKU..." 
                        class="py-1.5 pl-3 pr-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 w-64"
                    />
                    <button type="submit" class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Claims Table -->
        @if($claims->isEmpty())
            <div class="py-16 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800 dark:text-slate-200">No Claims Found</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mt-1">
                    @if($search)
                        No claims match your search query "{{ $search }}".
                    @elseif($tab !== 'all')
                        No claims currently exist in the "{{ ucfirst(str_replace('_', ' ', $tab)) }}" tab.
                    @else
                        You have not filed any claims. If you received damaged returns or missing RTO parcels, click "Raise Claim".
                    @endif
                </p>
                @if($tab === 'all' && !$search)
                    <a href="{{ route('seller.claims.create') }}" class="mt-4 px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition">
                        Raise Your First Claim
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold text-[10px] uppercase border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5">Claim ID &amp; Date</th>
                            <th class="p-3.5">Order &amp; Package</th>
                            <th class="p-3.5">Product Details</th>
                            <th class="p-3.5">Issue &amp; Category</th>
                            <th class="p-3.5">Claim Amount</th>
                            <th class="p-3.5">Status</th>
                            <th class="p-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($claims as $claim)
                            @php
                                $item = $claim->orderItem;
                                $statusEnum = $claim->status;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="font-mono font-bold text-slate-900 dark:text-white text-xs">{{ $claim->claim_number }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $claim->created_at->format('d M Y, h:i A') }}</div>
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $claim->order?->order_number ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                        {{ $claim->fulfilmentOrder?->fulfilment_order_number ?? 'Package N/A' }}
                                    </div>
                                </td>

                                <td class="p-3.5 max-w-xs">
                                    <div class="flex items-center space-x-3">
                                        <img 
                                            src="{{ $item?->variant?->image_url ?? $item?->variant?->product?->primary_image_url ?? asset('images/placeholder.png') }}" 
                                            alt="{{ $item?->title ?? 'Product' }}" 
                                            class="w-10 h-10 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0"
                                            onerror="this.src='/images/placeholder.png'"
                                        />
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white line-clamp-1">{{ $item?->title ?? 'Product Item' }}</div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 flex items-center space-x-1">
                                                <span>SKU: <strong class="text-slate-600 dark:text-slate-300 font-mono">{{ $item?->sku ?? 'N/A' }}</strong></span>
                                                <span>&bull;</span>
                                                <span>Qty: {{ $item?->qty ?? 1 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-3.5 max-w-xs">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 line-clamp-1">{{ $claim->reason }}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ $claim->claim_type?->label() ?? (string)$claim->claim_type }}
                                    </div>
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">₹{{ number_format((float)$claim->claimed_amount, 2) }}</div>
                                    @if($claim->status === \App\Enums\ClaimStatus::APPROVED)
                                        <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                                            Approved: ₹{{ number_format((float)$claim->approved_amount, 2) }}
                                        </div>
                                    @endif
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusEnum->badgeClasses() }}">
                                        {{ $statusEnum->label() }}
                                    </span>
                                    @if($claim->rejection_reason && $claim->status === \App\Enums\ClaimStatus::REJECTED)
                                        <div class="text-[10px] text-rose-600 dark:text-rose-400 mt-0.5 max-w-xs line-clamp-1" title="{{ $claim->rejection_reason }}">
                                            {{ $claim->rejection_reason }}
                                        </div>
                                    @endif
                                </td>

                                <td class="p-3.5 text-right whitespace-nowrap">
                                    <a 
                                        href="{{ route('seller.claims.show', $claim) }}" 
                                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition"
                                    >
                                        View Claim &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $claims->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
