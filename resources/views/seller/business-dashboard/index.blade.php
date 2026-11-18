@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Business Dashboard & Performance Analytics - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    customRangeOpen: '{{ $range }}' === 'custom',
    activeTab: '{{ $tab }}'
}">
    <!-- Header with Date Filters & Export -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Analytics Hub</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Business Analytics &amp; Intelligence</h1>
            <p class="text-xs text-slate-500 mt-1">
                Transparent revenue, sales volume, return metrics, and product performance computed directly from verified orders.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('seller.business_dashboard') }}" class="flex flex-wrap items-center gap-2">
                <div class="flex items-center bg-white border border-slate-200 rounded-lg p-1 shadow-2xs">
                    <a href="{{ route('seller.business_dashboard', ['range' => 'yesterday']) }}"
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $range === 'yesterday' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Yesterday
                    </a>
                    <a href="{{ route('seller.business_dashboard', ['range' => 'last_7']) }}"
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $range === 'last_7' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Last 7 Days
                    </a>
                    <a href="{{ route('seller.business_dashboard', ['range' => 'last_30']) }}"
                       class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $range === 'last_30' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Last 30 Days
                    </a>
                    <button type="button" @click="customRangeOpen = !customRangeOpen"
                            class="px-2.5 py-1 text-xs font-bold rounded-md transition {{ $range === 'custom' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                        Custom
                    </button>
                </div>

                <!-- Custom Range Dropdown/Inline Inputs -->
                <div x-show="customRangeOpen" class="flex items-center space-x-1.5 bg-white border border-slate-200 rounded-lg px-2 py-1 shadow-2xs" style="display: none;">
                    <input type="hidden" name="range" value="custom">
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="text-xs border-0 p-1 focus:ring-0 text-slate-700">
                    <span class="text-slate-400 text-xs">to</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="text-xs border-0 p-1 focus:ring-0 text-slate-700">
                    <button type="submit" class="px-2.5 py-1 bg-slate-900 text-white rounded text-xs font-bold hover:bg-slate-800">
                        Apply
                    </button>
                </div>
            </form>

            <!-- Export CSV -->
            <a href="{{ route('seller.business_dashboard.export', request()->query()) }}"
               class="px-3.5 py-2 rounded-lg text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition shadow-2xs flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Active Range Banner -->
    <div class="px-4 py-2 bg-emerald-50/70 border border-emerald-100 rounded-xl text-emerald-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Analytics Period: <strong>{{ $rangeLabel }}</strong></span>
        </div>
        <span class="text-[11px] text-emerald-600 font-normal">Real database values &bull; Multi-vendor isolated</span>
    </div>

    <!-- Primary Performance KPI Grid (Real Data Only) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Orders -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Total Orders</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metrics['total_orders']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['distinct_orders']) }} distinct baskets</div>
        </div>

        <!-- Gross Sales -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Gross Sales Value</span>
            <div class="text-xl font-black text-slate-900 mt-1">₹{{ number_format($metrics['gross_sales'], 2) }}</div>
            <div class="text-[10px] text-emerald-600 font-medium mt-0.5">Subtotal before deductions</div>
        </div>

        <!-- Net Revenue -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Net Seller Earnings</span>
            <div class="text-xl font-black text-emerald-700 mt-1">₹{{ number_format($metrics['net_revenue'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">After fees &amp; commissions</div>
        </div>

        <!-- Average Order Value -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Average Order (AOV)</span>
            <div class="text-xl font-black text-slate-900 mt-1">₹{{ number_format($metrics['aov'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Per fulfilment slice</div>
        </div>

        <!-- Delivered Orders -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Delivered Orders</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metrics['delivered_orders']) }}</div>
            <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">{{ $metrics['delivery_rate'] }}% delivery rate</div>
        </div>

        <!-- Returns & RTO -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Return &amp; RTO Rate</span>
            <div class="text-xl font-black {{ ($metrics['return_rate'] + $metrics['rto_rate']) > 15 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                {{ number_format($metrics['return_rate'] + $metrics['rto_rate'], 1) }}%
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ $metrics['returns_count'] }} ret / {{ $metrics['rto_count'] }} rto</div>
        </div>
    </div>

    <!-- Dynamic Visual Sales & Orders Chart Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Performance Trends Over Time</h3>
                <p class="text-xs text-slate-500">Daily breakdown of sales volume and order fulfilment within the selected period.</p>
            </div>
            <div class="flex items-center space-x-4 text-xs font-semibold">
                <span class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                    <span class="text-slate-700">Gross Sales (₹)</span>
                </span>
                <span class="flex items-center space-x-1.5">
                    <span class="w-3 h-3 rounded-full bg-slate-900"></span>
                    <span class="text-slate-700">Orders Placed</span>
                </span>
            </div>
        </div>

        @php
            $maxSales = max(1, collect($dailyTrends)->max('sales') ?? 1);
            $maxOrders = max(1, collect($dailyTrends)->max('orders') ?? 1);
            $pointsCount = count($dailyTrends);
        @endphp

        <!-- Visual Bar & Trend Chart -->
        <div class="pt-2">
            <div class="grid grid-cols-{{ min($pointsCount, 30) }} gap-1 sm:gap-2 items-end h-48 border-b border-slate-200 pb-2">
                @foreach($dailyTrends as $trend)
                    @php
                        $salesHeight = $maxSales > 0 ? max(4, round(($trend['sales'] / $maxSales) * 100)) : 4;
                        $ordersHeight = $maxOrders > 0 ? max(4, round(($trend['orders'] / $maxOrders) * 100)) : 4;
                    @endphp
                    <div class="flex flex-col items-center justify-end h-full group relative">
                        <!-- Tooltip -->
                        <div class="opacity-0 group-hover:opacity-100 pointer-events-none transition absolute -top-16 bg-slate-900 text-white text-[10px] rounded-lg py-1 px-2 whitespace-nowrap shadow-md z-10">
                            <div class="font-bold">{{ $trend['label'] }}</div>
                            <div>Sales: ₹{{ number_format($trend['sales'], 2) }}</div>
                            <div>Orders: {{ $trend['orders'] }} ({{ $trend['delivered'] }} del)</div>
                        </div>

                        <!-- Side by Side Bars -->
                        <div class="flex items-end space-x-0.5 sm:space-x-1 w-full justify-center">
                            <!-- Sales Bar -->
                            <div class="w-2 sm:w-3.5 bg-emerald-500 hover:bg-emerald-600 rounded-t-sm transition-all"
                                 style="height: {{ $salesHeight }}%;"></div>
                            <!-- Orders Bar -->
                            <div class="w-1.5 sm:w-2 bg-slate-800 hover:bg-slate-900 rounded-t-sm transition-all"
                                 style="height: {{ $ordersHeight }}%;"></div>
                        </div>

                        <!-- Date Label -->
                        <span class="text-[9px] sm:text-[10px] text-slate-400 mt-2 truncate w-full text-center">
                            {{ $trend['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Secondary Analytics Row: Order Distribution & Settlements -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Order Status Pipeline -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm">Order Status Pipeline</h3>
                <span class="text-xs text-slate-400 font-semibold">{{ $metrics['total_orders'] }} Total</span>
            </div>

            <div class="space-y-3 text-xs">
                @php
                    $pipeline = [
                        ['label' => 'Delivered Orders', 'count' => $statusDistribution['delivered'], 'color' => 'bg-emerald-500'],
                        ['label' => 'In-Transit / Out for Delivery', 'count' => $statusDistribution['in_transit'], 'color' => 'bg-sky-500'],
                        ['label' => 'Processing (Accepted / Packed)', 'count' => $statusDistribution['processing'], 'color' => 'bg-amber-500'],
                        ['label' => 'Pending Confirmation', 'count' => $statusDistribution['pending'], 'color' => 'bg-slate-400'],
                        ['label' => 'Cancelled Orders', 'count' => $statusDistribution['cancelled'], 'color' => 'bg-rose-400'],
                        ['label' => 'Customer Returns', 'count' => $statusDistribution['returned'], 'color' => 'bg-purple-500'],
                        ['label' => 'Courier RTO', 'count' => $statusDistribution['rto'], 'color' => 'bg-rose-600'],
                    ];
                @endphp

                @foreach($pipeline as $pipe)
                    @php
                        $pct = $metrics['total_orders'] > 0 ? round(($pipe['count'] / $metrics['total_orders']) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between font-semibold text-slate-700 mb-1">
                            <span>{{ $pipe['label'] }}</span>
                            <span class="font-bold text-slate-900">{{ number_format($pipe['count']) }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="{{ $pipe['color'] }} h-full rounded-full transition-all duration-300" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Settlements & Payment Summary -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm">Settlements &amp; Financial Ledger</h3>
                <a href="{{ route('seller.payments.index') }}" class="text-xs text-emerald-600 font-bold hover:underline">
                    View Ledger &rarr;
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-500">Gross Sales Value</span>
                    <div class="text-base font-black text-slate-900 mt-1">₹{{ number_format($settlementSummary['gross_ledger'], 2) }}</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-500">Commission Deductions</span>
                    <div class="text-base font-black text-rose-600 mt-1">-₹{{ number_format($settlementSummary['commission_deducted'], 2) }}</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-500">Shipping Deductions</span>
                    <div class="text-base font-black text-rose-600 mt-1">-₹{{ number_format($settlementSummary['shipping_deducted'], 2) }}</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-500">Net Seller Payable</span>
                    <div class="text-base font-black text-emerald-700 mt-1">₹{{ number_format($settlementSummary['net_payable'], 2) }}</div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-400">Completed Payouts:</span>
                    <span class="font-bold text-emerald-700 ml-1">₹{{ number_format($settlementSummary['settled'], 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-400">Pending Settlement:</span>
                    <span class="font-bold text-amber-700 ml-1">₹{{ number_format($settlementSummary['pending'], 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-400">On Hold:</span>
                    <span class="font-bold text-slate-700 ml-1">₹{{ number_format($settlementSummary['on_hold'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Analytics & Performance Tabs -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Product Catalog Performance</h3>
                <p class="text-xs text-slate-500">Identify top revenue drivers and listings requiring pricing or stock improvements.</p>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" @click="activeTab = 'top_selling'"
                        :class="activeTab === 'top_selling' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-lg text-xs transition">
                    Top-Selling Products ({{ $topSelling->count() }})
                </button>
                <button type="button" @click="activeTab = 'low_performing'"
                        :class="activeTab === 'low_performing' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-lg text-xs transition">
                    Low-Performing Products ({{ $lowPerforming->count() }})
                </button>
            </div>
        </div>

        <!-- Top Selling Tab -->
        <div x-show="activeTab === 'top_selling'" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold">
                        <th class="py-2.5 px-3">#</th>
                        <th class="py-2.5 px-3">Product Name</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Units Sold</th>
                        <th class="py-2.5 px-3">Orders Count</th>
                        <th class="py-2.5 px-3 text-right">Gross Sales (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topSelling as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5 px-3 font-bold text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900">{{ $item->product?->title ?? 'Deleted Product' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">ID #{{ $item->product_id }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $item->product?->category?->name ?? 'General' }}
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ number_format($item->total_units) }} units
                            </td>
                            <td class="py-2.5 px-3 text-slate-700">
                                {{ number_format($item->orders_count) }} orders
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                                ₹{{ number_format($item->total_revenue, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No sales recorded for this date range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Low Performing Tab -->
        <div x-show="activeTab === 'low_performing'" class="overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold">
                        <th class="py-2.5 px-3">Product Name</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Stock Units</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Recommended Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($lowPerforming as $prod)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900">{{ $prod->title }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">SKU: {{ $prod->variants->first()?->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-slate-600">
                                {{ $prod->category?->name ?? 'General' }}
                            </td>
                            <td class="py-2.5 px-3 font-semibold text-slate-800">
                                In Catalog
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    0 Sales in Period
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1">
                                <a href="{{ route('seller.pricing.index') }}" class="px-2.5 py-1 rounded bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200">
                                    Optimize Price
                                </a>
                                <a href="{{ route('seller.ads.create') }}" class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100">
                                    Create Ad
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                All active products have active sales!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
