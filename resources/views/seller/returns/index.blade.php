@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Returns & RTO Orders - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    mainTab: '{{ request('view', 'overview') }}', // 'overview', 'tracking', 'claim'
    trackingSubTab: 'in_transit'
}">
    <!-- Top Header & Search Bar (Screenshot 4) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Return/RTO Orders
        </h1>

        <div class="flex items-center space-x-3 text-xs">
            <a href="#" class="flex items-center space-x-1.5 text-slate-700 font-semibold hover:text-slate-900">
                <span class="text-rose-600">▶</span>
                <span>How it works?</span>
            </a>
            <div class="relative">
                <input 
                    type="text" 
                    placeholder="Search by Order ID, SKU or AWB Number" 
                    class="py-1.5 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-64"
                />
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs: Overview, Return Tracking, Claim Tracking -->
    <div class="border-b border-slate-200 flex items-center space-x-8 text-xs font-bold">
        <a 
            href="{{ route('seller.returns.index', ['view' => 'overview']) }}"
            class="pb-3 border-b-2 transition {{ request('view', 'overview') === 'overview' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            Overview
        </a>
        <a 
            href="{{ route('seller.returns.index', ['view' => 'tracking']) }}"
            class="pb-3 border-b-2 transition {{ request('view') === 'tracking' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            Return Tracking
        </a>
        <a 
            href="{{ route('seller.claims.index') }}"
            class="pb-3 border-b-2 transition {{ request('view') === 'claim' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            Claim Tracking
        </a>
    </div>

    @if(request('view') === 'tracking')
        <!-- RETURN TRACKING VIEW (Screenshot 5) -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <!-- Tracking Sub-tabs -->
            <div class="flex items-center space-x-2 border-b border-slate-200 px-4 pt-2 overflow-x-auto text-xs font-bold scrollbar-none">
                @foreach(['In transit', 'Out for Delivery', 'Delivered', 'Lost', 'No Return No Charge', 'Disposed', 'Return Partner Rating'] as $sub)
                    <button 
                        @click="trackingSubTab = '{{ Str::snake($sub) }}'" 
                        :class="trackingSubTab === '{{ Str::snake($sub) }}' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="py-3 px-4 border-b-2 transition whitespace-nowrap"
                    >
                        {{ $sub }}
                        @if($sub === 'No Return No Charge' || $sub === 'Return Partner Rating')
                            <span class="ml-1 px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-[#d63384] text-white">NEW</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Tracking Filters Bar -->
            <div class="p-3.5 bg-slate-50/50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-slate-500 font-semibold text-[11px]">Filter by:</span>
                    <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
                        <option>Return Created</option>
                    </select>
                    <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
                        <option>Category</option>
                    </select>
                    <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
                        <option>Expected Delivery Date</option>
                    </select>
                    <select class="py-1.5 px-3 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
                        <option>All Filters</option>
                    </select>
                </div>
                <div class="text-xs font-semibold text-slate-500">
                    🗐 0/0 files ready
                </div>
            </div>

            <!-- In-Transit Tracking Table (Matching Screenshot 5) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="p-3">Product Details</th>
                            <th class="p-3">Suborder ID</th>
                            <th class="p-3">Return Reason</th>
                            <th class="p-3">Return Shipping Fee</th>
                            <th class="p-3">Created/Picked Up Date</th>
                            <th class="p-3">Expected Delivery</th>
                            <th class="p-3">AWB Number</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Mock row 1 -->
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3">
                                <div class="font-bold text-slate-900">Kids Remote Control Sports Car Toy with 27MHz RC Controller White</div>
                                <div class="text-[10px] text-slate-500">SKU ID: 1011152963 &bull; Free Size</div>
                            </td>
                            <td class="p-3 font-mono text-[11px] text-slate-600">351309845164_1</td>
                            <td class="p-3 text-slate-400">N/A</td>
                            <td class="p-3 font-bold text-slate-800">₹0 <span class="block text-[9px] text-purple-700 bg-purple-50 px-1 rounded w-max mt-0.5">Courier Return (RTO)</span></td>
                            <td class="p-3 text-slate-700">16 Nov'26</td>
                            <td class="p-3 text-emerald-700 font-semibold">25 Dec'26 <span class="text-[10px] block">🚚 On time</span></td>
                            <td class="p-3">
                                <div class="flex items-center space-x-1 text-indigo-700 font-mono font-bold">
                                    <span>📍</span>
                                    <span>VL0086067967542</span>
                                </div>
                                <span class="px-1.5 py-0.2 rounded bg-slate-200 text-slate-700 text-[10px] font-semibold mt-1 inline-block">Valmo</span>
                            </td>
                            <td class="p-3 text-right">
                                <button type="button" class="px-3 py-1.5 border border-slate-300 rounded font-bold text-xs hover:bg-slate-50">View Details</button>
                            </td>
                        </tr>
                        <!-- Mock row 2 -->
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3">
                                <div class="font-bold text-slate-900">Gyro Gun Launcher Toy Set with 4 Flying Discs &amp; 2 Light-Up Spinning Tops</div>
                                <div class="text-[10px] text-slate-500">SKU ID: OX GU UNO 26 &bull; Free Size</div>
                            </td>
                            <td class="p-3 font-mono text-[11px] text-slate-600">350275349202_1</td>
                            <td class="p-3 text-slate-400">N/A</td>
                            <td class="p-3 font-bold text-slate-800">₹0 <span class="block text-[9px] text-purple-700 bg-purple-50 px-1 rounded w-max mt-0.5">Courier Return (RTO)</span></td>
                            <td class="p-3 text-slate-700">13 Nov'26</td>
                            <td class="p-3 text-emerald-700 font-semibold">22 Dec'26 <span class="text-[10px] block">🚚 On time</span></td>
                            <td class="p-3">
                                <div class="flex items-center space-x-1 text-indigo-700 font-mono font-bold">
                                    <span>📍</span>
                                    <span>VL0086043229645</span>
                                </div>
                                <span class="px-1.5 py-0.2 rounded bg-slate-200 text-slate-700 text-[10px] font-semibold mt-1 inline-block">Valmo</span>
                            </td>
                            <td class="p-3 text-right">
                                <button type="button" class="px-3 py-1.5 border border-slate-300 rounded font-bold text-xs hover:bg-slate-50">View Details</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- OVERVIEW VIEW (Screenshot 4) -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <h2 class="text-sm font-bold text-slate-900">Summary</h2>
                    <span class="text-xs text-slate-400">(01 Oct'26 - 28 Oct'26)</span>
                    <select class="py-1 px-2 border border-slate-200 rounded text-xs font-semibold text-slate-700">
                        <option>Last 1 Month</option>
                    </select>
                </div>
                <a href="#" class="text-xs font-bold text-[#53389e] flex items-center space-x-1 hover:underline">
                    <span>📈</span>
                    <span>View Trend</span>
                </a>
            </div>

            <!-- Summary KPI Metric Cards (Screenshot 4) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-xl border border-slate-150 bg-slate-50/40">
                <!-- Customer Return -->
                <div>
                    <div class="text-xs text-slate-500 font-medium">Customer Return</div>
                    <div class="text-[11px] text-slate-400">Return Rate</div>
                    <div class="flex items-baseline space-x-2 my-1">
                        <span class="text-2xl font-black text-slate-900">40.00%</span>
                        <span class="text-xs font-bold text-emerald-600">&blacktriangledown; 2.86%</span>
                    </div>
                    <div class="text-[11px] text-slate-400">16 orders returned out of 40 delivered</div>
                </div>

                <!-- Reverse Shipping Cost -->
                <div>
                    <div class="text-xs text-slate-500 font-medium">Average Reverse Shipping Cost</div>
                    <div class="flex items-baseline space-x-2 my-1">
                        <span class="text-2xl font-black text-slate-900">₹ 158</span>
                        <span class="text-xs font-bold text-rose-500">&blacktriangle; 9.72%</span>
                    </div>
                    <div class="text-[11px] text-slate-400">For 16 customer returned orders</div>
                </div>

                <!-- Courier Return RTO Rate -->
                <div>
                    <div class="text-xs text-slate-500 font-medium">Courier Return (RTO) Rate</div>
                    <div class="flex items-baseline space-x-2 my-1">
                        <span class="text-2xl font-black text-slate-900">37.50%</span>
                        <span class="text-xs font-bold text-emerald-600">&blacktriangledown; 8.65%</span>
                    </div>
                    <div class="text-[11px] text-slate-400">24 RTO orders out of 64 dispatched</div>
                </div>

                <!-- Dual Pricing - Customer Return Rate -->
                <div>
                    <div class="text-xs text-slate-500 font-medium">Dual Pricing - Customer Return Rate</div>
                    <div class="flex items-center space-x-4 mt-2">
                        <div>
                            <div class="text-[10px] text-slate-400">Wrong/Defective Return Price</div>
                            <div class="text-sm font-extrabold text-emerald-600">21.43%</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-slate-400">Meesho Price</div>
                            <div class="text-sm font-extrabold text-rose-600">48.00%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Performance Table (Screenshot 4 Bottom) -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Product Performance</h3>
                    <span class="text-[11px] text-slate-400">(01 Oct'26 - 28 Oct'26)</span>
                </div>
                <div class="flex items-center space-x-3">
                    <select class="py-1 px-3 border border-slate-300 rounded text-xs text-slate-700">
                        <option>Most Recent Order</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="p-3">Product Details</th>
                            <th class="p-3">Orders Delivered</th>
                            <th class="p-3">Customer Return</th>
                            <th class="p-3">Action</th>
                            <th class="p-3">What Changed ⓘ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $items = [
                                ['name' => 'Kids Remote Control Sports Car Toy with 27MHz RC Controller White', 'pid' => '1021922155', 'cat' => 'Automotive Accessories', 'del' => 1, 'ret' => '0.00%', 'changed' => '₹78.5 Saved on return fees compared to last month', 'positive' => true],
                                ['name' => 'Mini RC Helicopter for Kids - Easy to Fly (BLUE)', 'pid' => '1047853505', 'cat' => 'Kids', 'del' => 5, 'ret' => '40.00% (2 Returns)', 'changed' => '40.00% Returns increased compared to last month', 'positive' => false],
                                ['name' => 'OXMORA "RC Stunt Car with 360° Rotation & Off-Road Wheels', 'pid' => '1060950389', 'cat' => 'Kids', 'del' => 7, 'ret' => '42.86% (3 Returns)', 'changed' => '42.86% Returns increased compared to last month', 'positive' => false],
                                ['name' => 'Dancing Cactus, Talking Cactus Toy, Repeats What You Say', 'pid' => '1068980142', 'cat' => 'Kids', 'del' => 14, 'ret' => '42.86% (6 Returns)', 'changed' => '42.86% Returns increased compared to last month', 'positive' => false],
                            ];
                        @endphp
                        @foreach($items as $row)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ $row['name'] }}</div>
                                    <div class="text-[10px] text-slate-500">Product ID: {{ $row['pid'] }} &bull; Category: {{ $row['cat'] }}</div>
                                    <span class="inline-block mt-1 px-1.5 py-0.2 rounded bg-purple-50 text-[9px] font-bold text-purple-700 border border-purple-200">Dual Pricing Enabled</span>
                                </td>
                                <td class="p-3 font-bold text-slate-800">{{ $row['del'] }}</td>
                                <td class="p-3 font-bold {{ $row['positive'] ? 'text-slate-700' : 'text-rose-600' }}">{{ $row['ret'] }}</td>
                                <td class="p-3">
                                    <button type="button" class="px-3 py-1 border border-slate-300 rounded font-bold text-xs hover:bg-slate-50">View Details</button>
                                </td>
                                <td class="p-3 font-medium {{ $row['positive'] ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $row['changed'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
