@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Orders - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    currentTab: '{{ request('status', 'all') }}',
    filterModal: false
}">
    <!-- Page Header & Action Bar (Screenshot 2) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Orders
        </h1>

        <div class="flex items-center space-x-2">
            <a href="https://youtube.com" target="_blank" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center space-x-1.5">
                <span class="text-rose-600">▶</span>
                <span>Learn how to process your orders?</span>
            </a>

            <div class="relative" x-data="{ exportOpen: false }">
                <button 
                    @click="exportOpen = !exportOpen"
                    type="button" 
                    class="px-3.5 py-1.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Download Orders Data</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div 
                    x-show="exportOpen" 
                    @click.away="exportOpen = false" 
                    x-cloak 
                    class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-xl border border-slate-200 py-1 z-20 text-xs"
                >
                    <a href="{{ route('seller.orders.index', ['export' => 'csv']) }}" class="block px-3 py-2 text-slate-700 hover:bg-slate-50 font-medium">Export CSV</a>
                    <a href="{{ route('seller.orders.index', ['export' => 'excel']) }}" class="block px-3 py-2 text-slate-700 hover:bg-slate-50 font-medium">Export Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pay Later Promotional Banner (Screenshot 2 Top) -->
    <div class="p-4 rounded-xl bg-gradient-to-r from-purple-50 via-white to-pink-50 border border-purple-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-[#53389e] flex items-center justify-center text-white font-extrabold text-lg shrink-0">
                ₹
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="font-extrabold text-[#ff3f8b]">meesho</span>
                    <span class="font-bold text-slate-900">pay later <span class="text-[10px] text-slate-400 font-normal">lite</span></span>
                    <span class="px-1.5 py-0.2 bg-purple-100 text-[#53389e] rounded text-[9px] font-bold">2,00,000+ sellers enabled</span>
                </div>
                <div class="font-bold text-slate-800 text-sm">Sell more today by letting customers pay later</div>
                <div class="flex items-center space-x-4 text-slate-500 text-[11px] mt-0.5">
                    <span class="flex items-center space-x-1 text-emerald-600"><span>✔</span> <span>You get full payouts on time</span></span>
                    <span class="flex items-center space-x-1 text-emerald-600"><span>✔</span> <span>Customers buy more with Pay Later Lite</span></span>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <a href="#" class="text-xs text-indigo-600 font-bold hover:underline">Know more</a>
            <button type="button" class="px-4 py-1.5 rounded-lg border border-[#53389e] text-[#53389e] hover:bg-purple-50 font-bold text-xs transition">
                Enable now
            </button>
        </div>
    </div>

    <!-- Packaging Policy Notice (Screenshot 2) -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200/90 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs shadow-2xs">
        <div class="flex items-center space-x-3">
            <span class="text-xl">📦</span>
            <span class="text-slate-700">
                As per Meesho packaging policy, all sellers must use <strong>Transparent Barcoded Packaging</strong> for their products on the platform.
            </span>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition">
                Buy Barcoded Packets
            </button>
            <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition">
                Scan Barcoded Packets
            </button>
        </div>
    </div>

    <!-- Dispatch Health Visibility Card (Screenshot 2) -->
    <div class="p-3.5 rounded-xl bg-white border border-slate-200/90 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs shadow-2xs">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                🎯
            </div>
            <div>
                <div class="font-bold text-slate-900">Get better visibility into your dispatch health</div>
                <div class="text-slate-500 text-[11px]">Track dispatch health, catalog status and key order performance insights in one place.</div>
            </div>
        </div>
        <a href="{{ route('seller.orders.dispatch_performance') }}" class="px-4 py-1.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white font-bold text-xs transition shrink-0 text-center">
            Check Dispatch health
        </a>
    </div>

    <!-- Order Navigation Status Tabs (Screenshot 2) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center space-x-1 border-b border-slate-200 px-4 pt-2 overflow-x-auto text-xs font-bold scrollbar-none">
            @php
                $tabList = [
                    'on_hold' => ['label' => 'On Hold', 'count' => 0],
                    'pending' => ['label' => 'Pending', 'count' => $counts['action_needed'] ?? 0],
                    'ready' => ['label' => 'Ready to Ship', 'count' => 1],
                    'shipped' => ['label' => 'Shipped', 'count' => null],
                    'cancelled' => ['label' => 'Cancelled', 'count' => null],
                    'rating' => ['label' => 'Delivery Partner Rating', 'count' => null, 'badge' => 'New'],
                ];
                $activeTabKey = request('status', 'pending');
            @endphp

            @foreach($tabList as $key => $tabItem)
                <a 
                    href="{{ route('seller.orders.index', ['status' => $key]) }}"
                    class="py-3 px-4 border-b-2 transition whitespace-nowrap flex items-center space-x-1.5 {{ $activeTabKey === $key ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    <span>{{ $tabItem['label'] }}</span>
                    @if(isset($tabItem['count']))
                        <span>({{ $tabItem['count'] }})</span>
                    @endif
                    @if(!empty($tabItem['badge']))
                        <span class="ml-1 px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-[#d63384] text-white">NEW</span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Filter Sub-bar (Screenshot 2) -->
        <div class="p-3.5 bg-slate-50/50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-slate-500 font-semibold text-[11px]">Filter by:</span>

                <!-- SLA Status Dropdown -->
                <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:border-indigo-500">
                    <option value="">SLA Status</option>
                    <option value="breached">Breached</option>
                    <option value="critical">Breaching Soon (<2h)</option>
                    <option value="on_track">On Track</option>
                </select>

                <!-- Dispatch Date Dropdown -->
                <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:border-indigo-500">
                    <option value="">Dispatch Date</option>
                    <option value="today">Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="overdue">Overdue</option>
                </select>

                <!-- Order Date Dropdown -->
                <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:border-indigo-500">
                    <option value="">Order Date</option>
                    <option value="today">Today</option>
                    <option value="last_7_days">Last 7 Days</option>
                    <option value="last_30_days">Last 30 Days</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <div class="relative">
                    <input 
                        type="text" 
                        name="sku_search" 
                        placeholder="SKU ID / Suborder ID" 
                        class="py-1.5 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-48"
                    />
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Orders Content / Table or Empty State (Matching Screenshot 2) -->
        <div class="p-8">
            @if(count($orders) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3">Order Details</th>
                                <th class="p-3">Suborder ID</th>
                                <th class="p-3">Items &amp; SKU</th>
                                <th class="p-3">Customer Details</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Status / SLA</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($orders as $fo)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="p-3 font-semibold text-slate-900">
                                        #{{ $fo->fulfilment_order_number }}
                                        <div class="text-[10px] text-slate-400 font-normal">{{ $fo->created_at->format('d M, h:i A') }}</div>
                                    </td>
                                    <td class="p-3 font-mono text-[11px] text-slate-600">
                                        {{ $fo->id + 350000000000 }}
                                    </td>
                                    <td class="p-3">
                                        @foreach($fo->items as $item)
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-slate-800">{{ $item->variant?->title ?: 'Product' }}</span>
                                                <span class="text-[10px] font-mono text-slate-400">({{ $item->variant?->sku ?? 'SKU-N/A' }})</span>
                                                <span class="text-[10px] font-bold text-slate-600">x{{ $item->quantity }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ $fo->order?->customer?->user?->name ?? 'Meesho Customer' }}
                                    </td>
                                    <td class="p-3 font-bold text-slate-900">
                                        ₹{{ number_format((float)$fo->total_amount, 2) }}
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ ucwords(str_replace('_', ' ', $fo->status)) }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <a href="{{ route('seller.orders.show', $fo) }}" class="px-3 py-1 bg-[#3e2e84] text-white rounded font-bold text-xs hover:bg-[#32236e] transition">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Exact Empty State Box from Screenshot 2 -->
                <div class="py-12 flex flex-col items-center justify-center text-center">
                    <div class="w-24 h-24 mb-4 text-slate-300">
                        <svg class="w-full h-full" viewBox="0 0 80 80" fill="none" stroke="currentColor">
                            <rect x="15" y="25" width="50" height="40" rx="3" stroke-width="2.5" fill="#f8fafc"/>
                            <path d="M15 35h50" stroke-width="2"/>
                            <rect x="33" y="18" width="14" height="22" rx="2" fill="#64748b"/>
                            <path d="M22 48h12m-12 8h8" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No orders yet</h3>
                    <p class="text-xs text-slate-400 mt-1">But keep checking this section from time to time.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
