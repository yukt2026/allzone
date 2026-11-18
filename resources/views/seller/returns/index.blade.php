@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Returns & RTO Orders - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ $tab ?? 'overview' }}'
}">

    <!-- Page Header & Action Bar -->
    <x-page-header 
        title="Returns & RTO Management" 
        subtitle="Track customer returns, courier non-delivery returns (RTO), reverse shipping costs, and product quality trends."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Returns & RTO' => '#']"
    >
        <x-slot name="badge">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5"></span>
                Reverse Logistics &amp; RMA
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('seller.returns.export', ['tab' => $tab ?? 'overview']) }}" 
                    class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-indigo-600 text-xs font-bold transition shadow-2xs flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Export {{ $tab === 'rto' ? 'RTO' : 'Returns' }} (CSV)</span>
                </a>
                <a 
                    href="{{ route('seller.claims.index') }}" 
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 text-white text-xs font-bold transition shadow-2xs"
                >
                    Claims Console &rarr;
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- 3 Main Tabs: Return Tracking, Overview, RTO Tracking -->
    <div class="border-b border-slate-200 dark:border-slate-800 flex items-center space-x-6 text-xs font-bold">
        <a 
            href="{{ route('seller.returns.index', ['tab' => 'returns']) }}"
            class="pb-3 border-b-2 transition flex items-center gap-1.5 {{ in_array($tab, ['returns', 'tracking']) ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <span>📦 Return Tracking</span>
            @if(($metrics['pending_approval'] ?? 0) > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400">
                    {{ $metrics['pending_approval'] }}
                </span>
            @endif
        </a>
        <a 
            href="{{ route('seller.returns.index', ['tab' => 'overview']) }}"
            class="pb-3 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'overview' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <span>📊 Overview &amp; Trends</span>
        </a>
        <a 
            href="{{ route('seller.returns.index', ['tab' => 'rto']) }}"
            class="pb-3 border-b-2 transition flex items-center gap-1.5 {{ $tab === 'rto' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
        >
            <span>🚚 RTO Tracking</span>
            @if(($metrics['courier_rto'] ?? 0) > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-400">
                    {{ $metrics['courier_rto'] }}
                </span>
            @endif
        </a>
    </div>

    @if(($tab ?? 'overview') === 'overview')
        <!-- OVERVIEW TAB -->

        <!-- KPI Metric Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <!-- Customer Return Rate -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Customer Return Rate</div>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $metrics['customer_return_rate'] }}%</div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['customer_returns']) }} returned parcels</div>
            </div>

            <!-- Courier RTO Rate -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Courier RTO Rate</div>
                <div class="text-2xl font-black text-amber-600 mt-1">{{ $metrics['courier_rto_rate'] }}%</div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['courier_rto']) }} non-delivery returns</div>
            </div>

            <!-- Delivered Orders -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Delivered Orders</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($metrics['delivered_orders']) }}</div>
                <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Retained sales base</div>
            </div>

            <!-- Total Returns -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Return Orders</div>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['total_returns']) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ $metrics['pending_approval'] }} awaiting review</div>
            </div>

            <!-- Reverse Shipping Costs -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reverse Freight Cost</div>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ format_currency($metrics['reverse_shipping_costs']) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Carrier reverse charges</div>
            </div>
        </div>

        <!-- Return Trends & RTO Trends Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer Return Trends Chart -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Trend Analysis</div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white mt-0.5">Customer Return Requests (14 Days)</h3>
                    </div>
                    <span class="text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 px-2.5 py-1 rounded-full">
                        {{ array_sum($trends['returns']) }} Total
                    </span>
                </div>

                <div class="pt-3">
                    @php $maxRet = max(1, ...$trends['returns']); @endphp
                    <div class="h-36 flex items-end gap-1.5 sm:gap-2">
                        @foreach($trends['labels'] as $idx => $label)
                            @php
                                $val = $trends['returns'][$idx] ?? 0;
                                $barH = round(($val / $maxRet) * 110);
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                <div class="absolute -top-7 bg-slate-900 text-white text-[9px] font-bold py-0.5 px-1.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">
                                    {{ $label }}: {{ $val }}
                                </div>
                                <div class="w-full bg-rose-500 rounded-t-sm" style="height: {{ max(4, $barH) }}px"></div>
                                <span class="text-[8px] font-mono text-slate-400 truncate max-w-full">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Courier RTO Trends Chart -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Courier Non-Delivery</div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white mt-0.5">Courier RTO Initiations (14 Days)</h3>
                    </div>
                    <span class="text-xs font-bold text-amber-600 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 rounded-full">
                        {{ array_sum($trends['rto']) }} Total
                    </span>
                </div>

                <div class="pt-3">
                    @php $maxRto = max(1, ...$trends['rto']); @endphp
                    <div class="h-36 flex items-end gap-1.5 sm:gap-2">
                        @foreach($trends['labels'] as $idx => $label)
                            @php
                                $val = $trends['rto'][$idx] ?? 0;
                                $barH = round(($val / $maxRto) * 110);
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                <div class="absolute -top-7 bg-slate-900 text-white text-[9px] font-bold py-0.5 px-1.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none">
                                    {{ $label }}: {{ $val }}
                                </div>
                                <div class="w-full bg-amber-500 rounded-t-sm" style="height: {{ max(4, $barH) }}px"></div>
                                <span class="text-[8px] font-mono text-slate-400 truncate max-w-full">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Performance Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="font-black text-sm text-slate-900 dark:text-white">Product-Wise Return Performance</h3>
                    <p class="text-xs text-slate-400">Delivered orders, return rates, courier RTO percentage, and top reported return reasons.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 font-bold uppercase text-slate-400 text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Product Details</th>
                            <th class="py-3 px-4">Delivered</th>
                            <th class="py-3 px-4">Customer Returns</th>
                            <th class="py-3 px-4">Return Rate</th>
                            <th class="py-3 px-4">Courier RTO</th>
                            <th class="py-3 px-4">Top Return Reasons</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($productPerformance as $prod)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        @if(!empty($prod['thumbnail']))
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($prod['thumbnail']) }}" alt="{{ $prod['title'] }}" class="w-full h-full object-cover">
                                            </div>
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs shrink-0">
                                                🛍️
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $prod['title'] }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ $prod['sku'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                    {{ number_format($prod['delivered_orders']) }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-rose-600 dark:text-rose-400">
                                    {{ number_format($prod['customer_returns']) }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $prod['customer_return_rate'] > 15 ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $prod['customer_return_rate'] }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-bold text-amber-600">{{ $prod['rto_count'] }}</span>
                                    <span class="text-[10px] text-slate-400">({{ $prod['rto_rate'] }}%)</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate">
                                    {{ $prod['reasons'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400">
                                    <div class="text-2xl mb-1">📦</div>
                                    <div class="font-bold text-slate-700 dark:text-slate-300">No product return metrics available</div>
                                    <div class="text-xs text-slate-400 mt-0.5">Performance metrics will compute dynamically once return requests occur.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @elseif(in_array($tab, ['returns', 'tracking']))
        <!-- RETURN TRACKING TAB -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden space-y-4">
            <!-- Toolbar & Status Filters -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs font-bold">
                    @php
                        $subStatuses = [
                            'all' => 'All Returns',
                            'requested' => 'Pending Review',
                            'approved' => 'Approved',
                            'in_transit' => 'In Transit',
                            'received' => 'Received at Hub',
                            'qc_passed' => 'QC Passed',
                            'refunded' => 'Refunded',
                            'rejected' => 'Rejected',
                        ];
                    @endphp
                    @foreach($subStatuses as $stVal => $stLabel)
                        <a 
                            href="{{ route('seller.returns.index', ['tab' => 'returns', 'status' => $stVal]) }}" 
                            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition {{ ($status ?? 'all') === $stVal ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                        >
                            {{ $stLabel }}
                        </a>
                    @endforeach
                </div>

                <!-- Search Input -->
                <form method="GET" action="{{ route('seller.returns.index') }}" class="relative w-full sm:w-64">
                    <input type="hidden" name="tab" value="returns">
                    <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search Return #, Order #, SKU..." 
                        class="w-full text-xs py-1.5 pl-8 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </form>
            </div>

            <!-- Table of Customer Return Records -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 font-bold uppercase text-slate-400 text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Return ID / Order</th>
                            <th class="py-3 px-4">Product Details</th>
                            <th class="py-3 px-4">Return Reason</th>
                            <th class="py-3 px-4">Tracking Waybill</th>
                            <th class="py-3 px-4">Status &amp; QC</th>
                            <th class="py-3 px-4">Refund Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($returns as $ret)
                            @php
                                $item = $ret->orderItem;
                                $fo = $item?->fulfilmentOrder;
                                $order = $fo?->order;
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <!-- Return ID & Order -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        <a href="{{ route('seller.returns.show', $ret) }}" class="hover:text-indigo-600 transition">
                                            #{{ $ret->return_number }}
                                        </a>
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        Order: #{{ $order?->order_number ?? 'N/A' }}
                                    </div>
                                    @if($fo)
                                        <div class="text-[10px] font-mono text-slate-500 mt-0.5">
                                            Pkg: #{{ $fo->fulfilment_order_number }}
                                        </div>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $ret->created_at->format('d M Y') }}
                                    </div>
                                </td>

                                <!-- Product Details & SKU -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-bold text-slate-900 dark:text-white truncate" title="{{ $item?->title }}">
                                        {{ $item?->title ?? 'Marketplace Item' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 flex items-center gap-1 mt-0.5">
                                        <span class="font-mono">SKU: {{ $item?->sku ?? $item?->variant?->sku ?? 'N/A' }}</span>
                                        <span>&bull; Qty: <strong>{{ $item?->qty ?? 1 }}</strong></span>
                                    </div>
                                </td>

                                <!-- Return Reason -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $ret->reason ?: 'Customer Return' }}
                                    </div>
                                    @if($ret->customer_notes)
                                        <div class="text-[10px] text-slate-400 italic mt-0.5 truncate" title="{{ $ret->customer_notes }}">
                                            "{{ $ret->customer_notes }}"
                                        </div>
                                    @endif
                                    @if(!empty($ret->photos) && is_array($ret->photos))
                                        <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">
                                            📷 {{ count($ret->photos) }} photo(s) attached
                                        </div>
                                    @endif
                                </td>

                                <!-- Tracking Waybill -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($ret->pickup_waybill)
                                        <div class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ $ret->pickup_waybill }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $ret->pickupShipment?->courier ?? 'Shiprocket Return' }}
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                            Pickup Pending
                                        </span>
                                    @endif
                                </td>

                                <!-- Status & QC -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $ret->status->badgeClass() }}">
                                            {{ $ret->status->label() }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">
                                        QC: <strong class="capitalize">{{ $ret->qc_status->label() }}</strong>
                                    </div>
                                </td>

                                <!-- Refund Status -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ format_currency((float)($ret->refund_amount ?? $item?->total ?? 0)) }}
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        @if($ret->status === \App\Enums\ReturnStatus::REFUNDED)
                                            <span class="font-bold text-emerald-600">Refund Issued ✓</span>
                                        @elseif($ret->status === \App\Enums\ReturnStatus::REJECTED)
                                            <span class="font-bold text-rose-600">Refund Denied</span>
                                        @else
                                            <span class="font-medium text-amber-600">Pending QC</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($ret->status === \App\Enums\ReturnStatus::REQUESTED)
                                            <form method="POST" action="{{ route('seller.returns.approve', $ret) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs">
                                                    Approve
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('seller.returns.show', $ret) }}" class="text-indigo-600 dark:text-indigo-400 font-bold text-[11px] hover:underline px-1">
                                            Details &rarr;
                                        </a>

                                        <!-- Raise Claim link -->
                                        <a href="{{ route('seller.claims.index', ['order_id' => $order?->id, 'return_id' => $ret->id]) }}" class="text-slate-400 hover:text-amber-600 text-[11px] font-semibold px-1" title="Raise a damage/discrepancy claim">
                                            Claim
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl mx-auto mb-2">
                                        📦
                                    </div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No Return Requests Found</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Customer returns matching your filter will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>

    @elseif($tab === 'rto')
        <!-- RTO TRACKING TAB -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden space-y-4">
            <!-- RTO Header Toolbar -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-black text-sm text-slate-900 dark:text-white">Courier Return to Origin (RTO) Consignments</h3>
                    <p class="text-xs text-slate-400">Parcels returning due to customer door-step rejection, uncontactable phone, or failed delivery attempts.</p>
                </div>

                <!-- Search form -->
                <form method="GET" action="{{ route('seller.returns.index') }}" class="relative w-full sm:w-64">
                    <input type="hidden" name="tab" value="rto">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search AWB, Order #, Package #..." 
                        class="w-full text-xs py-1.5 pl-8 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </form>
            </div>

            <!-- Table of RTO Consignments -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 font-bold uppercase text-slate-400 text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Order / Package #</th>
                            <th class="py-3 px-4">Product Details</th>
                            <th class="py-3 px-4">Courier Partner &amp; AWB</th>
                            <th class="py-3 px-4">RTO Reason</th>
                            <th class="py-3 px-4">RTO Status</th>
                            <th class="py-3 px-4">Restock Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($rtoShipments as $s)
                            @php
                                $fo = $s->fulfilmentOrder;
                                $order = $fo?->order;
                                $firstItem = $fo?->items?->first();
                                $rtoCase = $s->rtoCases?->first();
                                $isRestocked = $s->status === \App\Enums\ShipmentStatus::RTO_DELIVERED;
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                <!-- Order & Package -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        #{{ $fo?->fulfilment_order_number ?? 'PKG-' . $s->id }}
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        Parent: #{{ $order?->order_number ?? 'N/A' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        {{ $s->updated_at->format('d M Y, h:i A') }}
                                    </div>
                                </td>

                                <!-- Product Details -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-bold text-slate-900 dark:text-white truncate" title="{{ $firstItem?->title }}">
                                        {{ $firstItem?->title ?? 'Consignment Items' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        SKU: {{ $firstItem?->sku ?? $firstItem?->variant?->sku ?? 'N/A' }}
                                        &bull; Qty: {{ $firstItem?->qty ?? 1 }}
                                    </div>
                                </td>

                                <!-- Courier Partner & AWB -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ $s->courier }}
                                    </div>
                                    <div class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        AWB: <strong>{{ $s->awb ?? 'Pending' }}</strong>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Reverse Fee: {{ format_currency($s->charges) }}
                                    </div>
                                </td>

                                <!-- RTO Reason -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $rtoCase?->reason ?? 'Maximum delivery attempts exhausted / Doorstep refusal' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Carrier scan exception recorded
                                    </div>
                                </td>

                                <!-- RTO Status -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border
                                        {{ $s->status === \App\Enums\ShipmentStatus::RTO_DELIVERED ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300 animate-pulse' }}">
                                        {{ $s->status->label() }}
                                    </span>
                                </td>

                                <!-- Restock Status -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($isRestocked)
                                        <span class="text-emerald-600 font-bold flex items-center gap-1">
                                            <span>✓</span> Restocked at Depot
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[10px] italic">
                                            Awaiting Arrival at Origin
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Live Sync Tracking -->
                                        <form method="POST" action="{{ route('seller.shipments.sync_tracking', $s) }}" class="inline">
                                            @csrf
                                            <button type="submit" title="Sync live tracking from Shiprocket" class="p-1 rounded-md text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </form>

                                        <!-- Track Console Link -->
                                        <a href="{{ route('seller.shipments.show', $s) }}" class="text-indigo-600 dark:text-indigo-400 font-bold text-[11px] hover:underline px-1">
                                            Track &rarr;
                                        </a>

                                        <!-- Raise Claim link -->
                                        <a href="{{ route('seller.claims.index', ['order_id' => $order?->id]) }}" class="text-slate-400 hover:text-amber-600 text-[11px] font-semibold px-1" title="Raise a damage/loss claim">
                                            Claim
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl mx-auto mb-2">
                                        🚚
                                    </div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No RTO consignments</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Parcels returning from courier non-delivery will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rtoShipments->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $rtoShipments->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
