@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Merchant Operations Hub - ' . ($seller->business_name ?? 'Seller Studio')])

@section('content')
<div class="space-y-8 max-w-7xl mx-auto pb-16" x-data="sellerDashboard({
    charts: {{ Js::from($charts) }},
    chartDataUrl: '{{ route('seller.chart_data') }}',
    activeStoreId: {{ $activeStore?->id ? (int) $activeStore->id : 'null' }},
    range: '{{ $range }}',
    startDate: '{{ $startDate }}',
    endDate: '{{ $endDate }}',
})">

    <!-- 1. Sleek Meesho-Style Supplier Panel Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200/80 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Supplier Panel</h1>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5 flex-wrap">
                <span>Welcome back, {{ $seller->business_name }}</span>
                <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                <span>Fulfillment operations &amp; daily sales performance</span>
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('seller.products.create') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700/60 transition shadow-2xs">
                <span>Orders Console &rarr;</span>
            </a>
        </div>
    </div>

    <!-- 2. Important Announcements & Admin Policy Notices (Section 2 & 8) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                <span>Important Announcements &amp; Platform Notices</span>
            </h2>
            <a href="{{ route('seller.notices.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                View All Notices &rarr;
            </a>
        </div>
        @if(!empty($announcements) && count($announcements) > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($announcements as $anc)
                    <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col justify-between space-y-2">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $anc['badge_color'] }}-50 text-{{ $anc['badge_color'] }}-700 dark:bg-{{ $anc['badge_color'] }}-950/60 dark:text-{{ $anc['badge_color'] }}-400 border border-{{ $anc['badge_color'] }}-200">
                                    {{ $anc['badge'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $anc['date'] }}</span>
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white line-clamp-1">{{ $anc['title'] }}</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ $anc['description'] }}</p>
                        </div>
                        @if(!empty($anc['url']))
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <a href="{{ $anc['url'] }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                    <span>Learn more</span>
                                    <span>&rsaquo;</span>
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/40 border border-dashed border-slate-200 dark:border-slate-800 text-center py-5">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">No active alerts. All marketplace operations, dispatch windows, and seller policies are up to date.</p>
            </div>
        @endif
    </div>

    <!-- 3. To-Do List / Operational Action Items (Section 3) -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    <span>Action Required / Seller To-Do List</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Prioritized operational items requiring your immediate store fulfillment or inventory action.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ array_sum($todo) }} items pending
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 text-xs">
            <!-- Pending Orders -->
            <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Pending Orders</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black text-slate-900 dark:text-white group-hover:text-indigo-600">{{ $todo['pending_orders'] }}</span>
                    <span class="text-xs text-indigo-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Orders Ready to Process -->
            <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Ready to Process</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black text-slate-900 dark:text-white group-hover:text-indigo-600">{{ $todo['orders_ready_to_process'] }}</span>
                    <span class="text-xs text-indigo-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Shipping Labels Pending -->
            <a href="{{ route('seller.orders.index', ['status' => 'ready']) }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Labels Pending</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black text-slate-900 dark:text-white group-hover:text-indigo-600">{{ $todo['shipping_labels_pending'] }}</span>
                    <span class="text-xs text-indigo-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Out of Stock -->
            <a href="{{ route('seller.inventory.index', ['stock_status' => 'out_of_stock']) }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-rose-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Out of Stock</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black {{ $todo['out_of_stock_products'] > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ $todo['out_of_stock_products'] }}</span>
                    <span class="text-xs text-rose-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Low Stock -->
            <a href="{{ route('seller.inventory.index', ['stock_status' => 'low_stock']) }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-amber-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Low Stock</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black {{ $todo['low_stock_products'] > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white' }}">{{ $todo['low_stock_products'] }}</span>
                    <span class="text-xs text-amber-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Pending Catalog Review -->
            <a href="{{ route('seller.products.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-sky-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Catalog Approvals</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black text-slate-900 dark:text-white group-hover:text-sky-600">{{ $todo['pending_catalog_approvals'] }}</span>
                    <span class="text-xs text-sky-500 font-bold">&rsaquo;</span>
                </div>
            </a>

            <!-- Open Support Tickets -->
            <a href="{{ route('seller.support.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 transition group flex flex-col justify-between">
                <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400">Open Tickets</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-lg font-black text-slate-900 dark:text-white group-hover:text-indigo-600">{{ $todo['open_support_tickets'] }}</span>
                    <span class="text-xs text-indigo-500 font-bold">&rsaquo;</span>
                </div>
            </a>
        </div>
    </div>

    <!-- 4. Date Filters Toolbar (Section 5) -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center space-x-2">
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Filter By Date:</span>
            <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold">({{ $rangeLabel }})</span>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <a 
                href="{{ route('seller.dashboard', ['range' => 'yesterday', 'store_id' => $activeStore?->id]) }}" 
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $range === 'yesterday' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
            >
                Yesterday
            </a>
            <a 
                href="{{ route('seller.dashboard', ['range' => '7d', 'store_id' => $activeStore?->id]) }}" 
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $range === '7d' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
            >
                Last 7 Days
            </a>
            <a 
                href="{{ route('seller.dashboard', ['range' => '30d', 'store_id' => $activeStore?->id]) }}" 
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $range === '30d' || empty($range) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
            >
                Last 30 Days
            </a>

            <!-- Custom Date Dropdown / Form -->
            <div x-data="{ open: {{ $range === 'custom' ? 'true' : 'false' }} }" class="relative inline-block">
                <button 
                    type="button" 
                    @click="open = !open" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ $range === 'custom' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
                >
                    <span>Custom Date</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div 
                    x-show="open" 
                    @click.away="open = false" 
                    x-cloak 
                    class="absolute right-0 mt-2 w-72 p-4 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 z-50 text-xs space-y-3"
                >
                    <form method="GET" action="{{ route('seller.dashboard') }}" class="space-y-3">
                        <input type="hidden" name="range" value="custom">
                        @if($activeStore)
                            <input type="hidden" name="store_id" value="{{ $activeStore->id }}">
                        @endif
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-xs" required>
                        </div>
                        <div class="flex items-center justify-end space-x-2 pt-1">
                            <button type="button" @click="open = false" class="px-2.5 py-1 text-slate-500 hover:text-slate-700">Cancel</button>
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-xs">Apply Range</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Business Insights & Order Performance -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📈 Business Insights &amp; Performance</span>
                <span class="text-xs text-slate-400 font-normal">({{ $rangeLabel }})</span>
            </h2>
            <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800">
                Net Seller Revenue: {{ $businessInsights['formatted_revenue'] }}
            </div>
        </div>

        <!-- 8 Standard KPI Cards Grid (Matches Integration Test Suite) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($kpi as $metricKey => $stat)
                @if(in_array($metricKey, ['today_sales', 'total_orders', 'ready_to_dispatch', 'shipped_orders', 'pending_orders', 'delivered_orders', 'returned_orders', 'low_stock']))
                    <a href="{{ $stat['url'] }}" class="block group transition transform hover:-translate-y-0.5">
                        <x-stat-card 
                            :label="$stat['label']" 
                            :value="$stat['formatted_value'] ?? $stat['value']" 
                            :change="$stat['change']" 
                            :changeType="$stat['change_type']"
                            :badge="$stat['badge']"
                            :color="$stat['color']" 
                        />
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- 6. Dispatch Performance Console (Section 6) -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🚚 Dispatch Performance &amp; Logistics SLA</span>
                </h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Track fulfillment throughput and on-time shipment carrier SLA standards.</p>
            </div>
            <a href="{{ route('seller.orders.dispatch_performance') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                View Performance Report &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <!-- Awaiting Processing -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Awaiting Processing</span>
                <span class="text-xl font-black text-slate-900 dark:text-white mt-1 block">{{ $dispatchPerformance['awaiting_processing'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Placed &amp; Accepted</span>
            </div>

            <!-- Awaiting Shipment -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Awaiting Shipment</span>
                <span class="text-xl font-black text-slate-900 dark:text-white mt-1 block">{{ $dispatchPerformance['awaiting_shipment'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Packed &amp; Ready</span>
            </div>

            <!-- Shipments In Transit -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">In Transit</span>
                <span class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1 block">{{ $dispatchPerformance['in_transit'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">With Courier Partner</span>
            </div>

            <!-- Delayed Shipments -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Delayed Shipments</span>
                <span class="text-xl font-black {{ $dispatchPerformance['delayed_shipments'] > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }} mt-1 block">{{ $dispatchPerformance['delayed_shipments'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">&gt;24h unfulfilled SLA</span>
            </div>

            <!-- Delivered Orders -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Delivered Orders</span>
                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1 block">{{ $dispatchPerformance['delivered_orders'] }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Successfully Handed Over</span>
            </div>

            <!-- On-Time Dispatch Rate -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">On-Time Dispatch</span>
                <div class="mt-1 flex items-baseline space-x-1.5">
                    @if($dispatchPerformance['on_time_rate'] !== null)
                        <span class="text-xl font-black {{ $dispatchPerformance['on_time_rate'] >= 95 ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $dispatchPerformance['on_time_rate'] }}%
                        </span>
                        <span class="text-[10px] font-bold {{ $dispatchPerformance['on_time_rate'] >= 95 ? 'text-emerald-700 bg-emerald-50' : 'text-amber-700 bg-amber-50' }} px-1.5 py-0.2 rounded">
                            {{ $dispatchPerformance['on_time_rate'] >= 95 ? 'Good' : 'At Risk' }}
                        </span>
                    @else
                        <span class="text-xs font-bold text-slate-400">No Dispatches Yet</span>
                    @endif
                </div>
                <span class="text-[10px] text-slate-400 mt-0.5 block">{{ $dispatchPerformance['total_dispatched'] }} orders dispatched</span>
            </div>
        </div>
    </div>

    <!-- Charts Section: Sales & Order Trajectory, Channels, Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales & Orders Trajectory Chart (2 cols) -->
        <div class="lg:col-span-2">
            <x-card title="Sales & Order Trajectory" subtitle="Revenue (₹) and order counts across the selected date range">
                <div class="h-72 w-full relative">
                    <canvas id="sellerSalesChart"></canvas>
                </div>
            </x-card>
        </div>

        <!-- Fulfillment Distribution Breakdown (1 col) -->
        <div>
            <x-card title="Fulfillment Distribution" subtitle="Marketplace order logistics breakdown">
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="sellerChannelChart"></canvas>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-3 gap-2 text-center text-xs">
                    <div>
                        <div class="text-[10px] text-slate-400 font-semibold">Standard</div>
                        <div class="font-extrabold text-indigo-600">{{ $charts['channel']['data'][1] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 font-semibold">Express</div>
                        <div class="font-extrabold text-amber-600">{{ $charts['channel']['data'][0] ?? 0 }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-400 font-semibold">Direct</div>
                        <div class="font-extrabold text-emerald-600">{{ $charts['channel']['data'][2] ?? 0 }}</div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- 7. Product Performance and Inventory Alerts (Section 7) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Urgent Orders Needing Immediate Action -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Orders Needing Immediate Action</h3>
                </div>
                <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    View All ({{ $actionOrders->count() }}) &rarr;
                </a>
            </div>

            @if($actionOrders->isEmpty())
                <div class="p-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center space-y-2">
                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="font-bold text-slate-600 dark:text-slate-400">Order queue is all clear!</span>
                    <span>No incoming orders are pending acceptance or packing right now.</span>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($actionOrders as $fo)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-slate-900 dark:text-white">#{{ $fo->fulfilment_order_number }}</span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-extrabold uppercase {{ $fo->fulfilment_type === \App\Enums\FulfilmentType::QUICK ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $fo->fulfilment_type->label() ?? $fo->fulfilment_type->value }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">
                                    {{ $fo->items->first()?->title ?? 'Order Items' }} &bull; ₹{{ number_format((float)$fo->seller_payable, 2) }}
                                </div>
                            </div>

                            <div class="flex items-center space-x-1.5 shrink-0">
                                @if($fo->status === 'placed')
                                    <form method="POST" action="{{ route('seller.orders.accept', $fo) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                                            Accept
                                        </button>
                                    </form>
                                @elseif($fo->status === 'store_accepted')
                                    <form method="POST" action="{{ route('seller.orders.pack', $fo) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition">
                                            Pack
                                        </button>
                                    </form>
                                @elseif($fo->status === 'packing')
                                    <form method="POST" action="{{ route('seller.orders.ready', $fo) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition">
                                            Ready
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('seller.orders.show', $fo) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Low Stock Warning & Inventory Restock -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Low Stock Warning &amp; Inventory Alerts</h3>
                </div>
                <a href="{{ route('seller.inventory.index', ['stock_status' => 'low_stock']) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    View Inventory &rarr;
                </a>
            </div>

            @if($lowStock->isEmpty())
                <div class="p-8 text-center text-slate-400 text-xs flex flex-col items-center justify-center space-y-2">
                    <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                    <span class="font-bold text-slate-600 dark:text-slate-400">Inventory levels are healthy!</span>
                    <span>No variants currently have stock balances below their alert thresholds.</span>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($lowStock as $inv)
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 dark:text-white truncate">
                                    {{ $inv->variant?->product?->title ?? 'Product SKU' }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span class="font-mono">{{ $inv->variant?->sku ?? 'SKU' }}</span>
                                    <span>&bull;</span>
                                    <span class="font-bold text-rose-600">{{ $inv->available_quantity }} left</span>
                                    <span>(alert at &le; {{ $inv->low_stock_threshold }})</span>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="openRestockModal('{{ $inv->id }}', '{{ addslashes($inv->variant?->product?->title ?? $inv->variant?->sku) }}', {{ $inv->available_quantity }})" 
                                class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs shrink-0 transition"
                            >
                                + Restock
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- 8. Top Products & Multi-Store Revenue Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Products by Sales -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs p-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Top Selling Products</h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-4">Highest revenue-generating SKUs during the active period.</p>
            <div class="h-60 relative">
                <canvas id="sellerTopProductsChart"></canvas>
            </div>
        </div>

        <!-- Multi-Store Comparison (or Settlements & Payouts Overview) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Settlements &amp; Payouts Overview</h3>
                    <a href="{{ route('seller.settlements.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Ledger &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Accrued Payable Balance</div>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $settlement['formatted_accrued'] }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Delivered orders balance</div>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Next Scheduled Payout</div>
                        <div class="text-xs font-black text-slate-900 dark:text-white mt-1">{{ $settlement['next_payout_date'] }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Weekly bank transfer cycle</div>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl border border-slate-200/60 dark:border-slate-800 text-xs flex items-center justify-between">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center font-bold text-sm">🏦</span>
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $settlement['primary_bank']?->bank_name ?? 'Bank Account Configured' }}</span>
                            <span class="text-[10px] text-slate-400">Account: •••• {{ substr($settlement['primary_bank']?->account_no ?? '1234', -4) }} &bull; {{ $settlement['primary_bank']?->ifsc ?? 'IFSC Verified' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('seller.settings.bank') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Manage
                    </a>
                </div>
            </div>

            <!-- Customer Feedback Widget -->
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-900 dark:text-white">Customer Feedback</span>
                    <span class="text-[11px] text-slate-400">{{ $reviews->count() }} recent reviews</span>
                </div>
                @if($reviews->isEmpty())
                    <p class="text-[11px] text-slate-400 italic">No customer reviews received yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach($reviews->take(2) as $rev)
                            <div class="text-[11px] text-slate-600 dark:text-slate-400 flex items-center justify-between">
                                <span class="font-medium truncate max-w-[200px]">{{ $rev->product?->title }}</span>
                                <span class="text-amber-500 font-bold shrink-0">★ {{ $rev->rating }}.0</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 9. Quick Action Links to Seller Modules (Section 9) -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Quick Module Shortcuts</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 text-center text-xs">
            <a href="{{ route('seller.products.create') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">➕</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Add Product</span>
            </a>
            <a href="{{ route('seller.products.bulk_upload') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">📁</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Bulk Upload</span>
            </a>
            <a href="{{ route('seller.orders.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">📦</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Orders</span>
            </a>
            <a href="{{ route('seller.inventory.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">📊</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Inventory</span>
            </a>
            <a href="{{ route('seller.pricing.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">🏷️</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Pricing</span>
            </a>
            <a href="{{ route('seller.promotions.sales') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">🎟️</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Promotions</span>
            </a>
            <a href="{{ route('seller.settlements.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">💰</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Payouts</span>
            </a>
            <a href="{{ route('seller.support.index') }}" class="p-3 rounded-xl border border-slate-150 dark:border-slate-800 hover:border-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex flex-col items-center">
                <span class="text-lg mb-1">🎧</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Support</span>
            </a>
        </div>
    </div>

    <!-- Store Readiness & Checklist -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Merchant Readiness</h3>
            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $checklist['progress'] }}% completed</span>
        </div>
        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $checklist['progress'] }}%"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 text-xs pt-1">
            @foreach($checklist['items'] as $item)
                <a href="{{ $item['url'] }}" class="flex items-center space-x-1.5 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <span class="text-sm">{{ $item['done'] ? '✅' : '⚪' }}</span>
                    <span class="truncate font-semibold {{ $item['done'] ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400' }}">{{ $item['title'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Quick Restock Modal -->
    <div 
        x-show="restockModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
        <div @click.away="restockModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Quick Restock SKU</h3>
                <button type="button" @click="restockModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="restockVariantTitle"></p>
                <p class="text-xs font-bold text-rose-600 mt-1">Current available: <span x-text="restockCurrentQty"></span> units</p>
            </div>
            <form :action="'/seller/inventory/' + restockInventoryId + '/quick-restock'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Units to Add to Stock</label>
                    <input type="number" name="added_quantity" min="1" max="10000" value="25" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-sm font-bold" required>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="restockModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs">Replenish Stock</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function sellerDashboard(config) {
        return {
            chartsData: config.charts,
            chartDataUrl: config.chartDataUrl,
            activeStoreId: config.activeStoreId,
            range: config.range,
            restockModalOpen: false,
            restockInventoryId: null,
            restockVariantTitle: '',
            restockCurrentQty: 0,
            salesChart: null,
            channelChart: null,
            topProductsChart: null,

            init() {
                this.renderSalesChart(this.chartsData.sales_trend);
                this.renderChannelChart(this.chartsData.channel);
                this.renderTopProductsChart(this.chartsData.top_products);
            },

            openRestockModal(id, title, qty) {
                this.restockInventoryId = id;
                this.restockVariantTitle = title;
                this.restockCurrentQty = qty;
                this.restockModalOpen = true;
            },

            renderSalesChart(data) {
                const ctx = document.getElementById('sellerSalesChart');
                if (!ctx) return;

                if (this.salesChart) {
                    this.salesChart.destroy();
                }

                this.salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels && data.labels.length > 0 ? data.labels : ['No data'],
                        datasets: [
                            {
                                label: 'Revenue (₹)',
                                data: data.sales && data.sales.length > 0 ? data.sales : [0],
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                fill: true,
                                tension: 0.35,
                                yAxisID: 'y',
                                pointRadius: 3,
                            },
                            {
                                label: 'Orders Count',
                                data: data.orders && data.orders.length > 0 ? data.orders : [0],
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.08)',
                                fill: false,
                                tension: 0.35,
                                yAxisID: 'y1',
                                pointRadius: 3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } } }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                grid: { color: 'rgba(148, 163, 184, 0.1)' },
                                ticks: { font: { size: 10 } }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { font: { size: 10 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 }, maxTicksLimit: 12 }
                            }
                        }
                    }
                });
            },

            renderChannelChart(data) {
                const ctx = document.getElementById('sellerChannelChart');
                if (!ctx) return;

                if (this.channelChart) {
                    this.channelChart.destroy();
                }

                const total = (data.data || []).reduce((a, b) => a + b, 0);

                this.channelChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: total > 0 ? data.data : [1, 0, 0],
                            backgroundColor: total > 0 ? data.colors : ['#e2e8f0', '#e2e8f0', '#e2e8f0'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        cutout: '72%'
                    }
                });
            },

            renderTopProductsChart(data) {
                const ctx = document.getElementById('sellerTopProductsChart');
                if (!ctx) return;

                if (this.topProductsChart) {
                    this.topProductsChart.destroy();
                }

                const hasData = data.labels && data.labels.length > 0;

                this.topProductsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: hasData ? data.labels : ['No orders recorded'],
                        datasets: [{
                            label: 'Sales (₹)',
                            data: hasData ? data.data : [0],
                            backgroundColor: '#6366f1',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { font: { size: 10 } } },
                            x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                        }
                    }
                });
            }
        };
    }
</script>
@endpush
@endsection
