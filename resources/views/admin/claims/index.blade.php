@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Seller Claims & Disputes Management'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header 
        title="Seller Claims & Disputes" 
        subtitle="Adjudication console for merchant courier damage, transit loss, RTO discrepancies, and ledger compensation settlements."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Claims & Disputes' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                {{ number_format($metrics['total']) }} Claims Logged
            </span>
        </x-slot>

        <x-slot name="actions">
            <span class="text-xs text-slate-500 font-semibold">
                Total Authorized Compensation: <strong class="text-emerald-600 dark:text-emerald-400 font-black">₹{{ number_format($metrics['total_compensated'], 2) }}</strong>
            </span>
        </x-slot>
    </x-page-header>

    <!-- Platform Claim KPI Cards (7 Metrics) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- 1. Total Claims -->
        <a href="{{ route('admin.claims.index', ['status' => 'all']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-slate-400 transition">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Claims</div>
            <div class="text-xl font-black text-slate-900 dark:text-white">
                {{ number_format($metrics['total']) }}
            </div>
            <div class="text-[9px] text-slate-400">All registered</div>
        </a>

        <!-- 2. Open Claims -->
        <a href="{{ route('admin.claims.index', ['status' => 'open']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-blue-400 transition">
            <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Open</div>
            <div class="text-xl font-black text-blue-600 dark:text-blue-400">
                {{ number_format($metrics['open']) }}
            </div>
            <div class="text-[9px] text-slate-400">Awaiting triage</div>
        </a>

        <!-- 3. Under Review -->
        <a href="{{ route('admin.claims.index', ['status' => 'under_review']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-amber-400 transition">
            <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Under Review</div>
            <div class="text-xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($metrics['under_review']) }}
            </div>
            <div class="text-[9px] text-slate-400">Investigation / Info</div>
        </a>

        <!-- 4. Approved -->
        <a href="{{ route('admin.claims.index', ['status' => 'approved']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-emerald-400 transition">
            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Approved</div>
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                {{ number_format($metrics['approved']) }}
            </div>
            <div class="text-[9px] text-slate-400">Compensated</div>
        </a>

        <!-- 5. Rejected -->
        <a href="{{ route('admin.claims.index', ['status' => 'rejected']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-rose-400 transition">
            <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Rejected</div>
            <div class="text-xl font-black text-rose-600 dark:text-rose-400">
                {{ number_format($metrics['rejected']) }}
            </div>
            <div class="text-[9px] text-slate-400">Disallowed</div>
        </a>

        <!-- 6. Closed -->
        <a href="{{ route('admin.claims.index', ['status' => 'closed']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-slate-400 transition">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Closed</div>
            <div class="text-xl font-black text-slate-700 dark:text-slate-300">
                {{ number_format($metrics['closed']) }}
            </div>
            <div class="text-[9px] text-slate-400">Resolved / Final</div>
        </a>

        <!-- 7. Total Compensated -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Disbursed</div>
            <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 truncate">
                ₹{{ number_format($metrics['total_compensated'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">From ₹{{ number_format($metrics['total_claimed'], 2) }}</div>
        </div>
    </div>

    <!-- Reason Breakdown & Trends Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Claim Categories & Reasons Breakdown -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Claim Categories &amp; Root Reasons</h3>
                    <p class="text-xs text-slate-400">Distribution of claims submitted across logistics failure types</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($reasons as $r)
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800 dark:text-slate-200 truncate pr-2">{{ $r['label'] }}</span>
                            <span class="font-black text-slate-900 dark:text-white shrink-0">{{ $r['count'] }} ({{ $r['percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-1.5 rounded-full" style="width: {{ $r['percentage'] }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 pt-0.5">
                            <span>Claimed: ₹{{ number_format($r['claimed_amount'], 2) }}</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Appr: ₹{{ number_format($r['approved_amount'], 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Claim Trends & High-Claiming Sellers -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white">Merchant Claim Trends</h3>
                    <p class="text-xs text-slate-400">Highest claim frequency accounts</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($trends['top_sellers'] as $ts)
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 text-xs">
                        <div class="truncate pr-2">
                            <div class="font-bold text-slate-900 dark:text-white truncate">
                                {{ $ts->seller?->business_name ?? 'Seller #' . $ts->seller_id }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">
                                Total Value: ₹{{ number_format((float) $ts->total_claimed, 2) }}
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400 shrink-0">
                            {{ $ts->claims_count }} claims
                        </span>
                    </div>
                @empty
                    <div class="text-xs text-slate-400 py-6 text-center">
                        No seller claim concentration recorded.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Filter & Table Console -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <!-- Status Filter Tabs -->
        <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 px-5 pt-3 overflow-x-auto text-xs font-bold">
            @php 
                $tabs = [
                    'all' => 'All Claims', 
                    'open' => 'Open', 
                    'under_review' => 'Under Review', 
                    'approved' => 'Approved', 
                    'rejected' => 'Rejected', 
                    'closed' => 'Closed'
                ]; 
            @endphp
            @foreach($tabs as $key => $label)
                <a 
                    href="{{ route('admin.claims.index', array_merge(request()->except('status'), ['status' => $key])) }}" 
                    class="pb-3 px-2 border-b-2 transition whitespace-nowrap {{ $status === $key ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Filter Form -->
        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800">
            <form method="GET" action="{{ route('admin.claims.index') }}" class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="flex flex-wrap items-center gap-2.5">
                    <select 
                        name="seller_id" 
                        onchange="this.form.submit()" 
                        class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 text-xs font-semibold"
                    >
                        <option value="">All Sellers</option>
                        @foreach($sellers as $s)
                            <option value="{{ $s->id }}" {{ (string)$sellerId === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->business_name }}
                            </option>
                        @endforeach
                    </select>

                    <select 
                        name="claim_type" 
                        onchange="this.form.submit()" 
                        class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 text-xs font-semibold"
                    >
                        <option value="">All Categories</option>
                        @foreach($types as $t)
                            <option value="{{ $t->value }}" {{ $claimType === $t->value ? 'selected' : '' }}>
                                {{ $t->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}"
                        placeholder="Search Claim #, Order #, Merchant..." 
                        class="py-1.5 pl-3 pr-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 w-64"
                    />
                    <button type="submit" class="absolute right-2.5 top-2 text-slate-400 hover:text-emerald-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        @if($claims->isEmpty())
            <div class="py-16 text-center text-slate-400 text-xs">
                No seller claims match the selected criteria.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold text-[10px] uppercase border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5">Claim ID</th>
                            <th class="p-3.5">Merchant</th>
                            <th class="p-3.5">Order / Suborder</th>
                            <th class="p-3.5">Category &amp; Issue</th>
                            <th class="p-3.5">Claimed / Approved</th>
                            <th class="p-3.5">Status</th>
                            <th class="p-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @foreach($claims as $claim)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ $claim->claim_number }}
                                    <div class="text-[10px] font-normal text-slate-400">{{ $claim->created_at->format('d M Y, h:i A') }}</div>
                                </td>

                                <td class="p-3.5 font-semibold text-slate-800 dark:text-slate-200">
                                    {{ $claim->seller?->business_name ?? 'N/A' }}
                                </td>

                                <td class="p-3.5 font-mono text-[11px] text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                    <div>{{ $claim->order?->order_number ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $claim->fulfilmentOrder?->fulfilment_order_number }}</div>
                                </td>

                                <td class="p-3.5 max-w-xs">
                                    <div class="font-semibold text-slate-900 dark:text-white line-clamp-1">{{ $claim->reason }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $claim->claim_type?->label() }}</div>
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">₹{{ number_format((float)$claim->claimed_amount, 2) }}</div>
                                    @if($claim->status === \App\Enums\ClaimStatus::APPROVED)
                                        <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format((float)$claim->approved_amount, 2) }} Appr.</div>
                                    @endif
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $claim->status->badgeClasses() }}">
                                        {{ $claim->status->label() }}
                                    </span>
                                </td>

                                <td class="p-3.5 text-right whitespace-nowrap">
                                    <a 
                                        href="{{ route('admin.claims.show', $claim) }}" 
                                        class="px-3 py-1.5 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs hover:bg-slate-800 shadow-xs transition"
                                    >
                                        Review Claim &rarr;
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
