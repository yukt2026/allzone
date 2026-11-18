@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Dispatch Performance & Shiprocket Logistics - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6" x-data="{
    dispatchModal: false,
    selectedFo: null,
    foNumber: '',
    length: 10,
    breadth: 10,
    height: 10,
    weight: 0.5,
    couriers: [],
    loadingCouriers: false,
    selectedCourierId: '',
    selectedCourierName: '',
    customRange: '{{ $dateFilter === 'custom' ? 'true' : 'false' }}' === 'true',

    openDispatch(foId, number, defaultWeight) {
        this.selectedFo = foId;
        this.foNumber = number;
        this.weight = defaultWeight || 0.5;
        this.couriers = [];
        this.selectedCourierId = '';
        this.selectedCourierName = '';
        this.dispatchModal = true;
        this.fetchRates();
    },

    async fetchRates() {
        if (!this.selectedFo) return;
        this.loadingCouriers = true;
        try {
            const res = await fetch(`{{ url('/seller/orders') }}/${this.selectedFo}/check-rates?weight=${this.weight}`);
            const data = await res.json();
            if (data.success && data.couriers) {
                this.couriers = data.couriers;
                if (this.couriers.length > 0) {
                    this.selectedCourierId = this.couriers[0].courier_company_id;
                    this.selectedCourierName = this.couriers[0].courier_name;
                }
            }
        } catch (e) {
            console.error('Rate check failed', e);
        } finally {
            this.loadingCouriers = false;
        }
    }
}">

    <!-- Page Header & Toolbar -->
    <x-page-header 
        title="Dispatch Performance & Shiprocket Logistics" 
        subtitle="Real-time courier SLAs, parcel dispatch monitoring, and automated Shiprocket carrier allocation."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Orders' => route('seller.orders.index'), 'Dispatch Performance' => '#']"
    >
        <x-slot name="badge">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse mr-1.5"></span>
                Shiprocket Integrated Logistics
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <a 
                    href="{{ route('seller.orders.dispatch_performance', array_merge(request()->query(), ['export' => 'csv'])) }}" 
                    class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-indigo-600 text-xs font-bold transition shadow-2xs flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Export Report (CSV)</span>
                </a>
                <a 
                    href="{{ route('seller.shipments.index') }}" 
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 text-white text-xs font-bold transition shadow-2xs"
                >
                    All Consignments &rarr;
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Date Range Filter Sub-bar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">Time Horizon:</span>
            @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days', 'custom' => 'Custom Date'] as $val => $label)
                <a 
                    href="{{ route('seller.orders.dispatch_performance', array_merge(request()->except(['date_range', 'date_filter', 'page']), ['date_range' => $val])) }}" 
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($dateFilter ?? '30d') === $val ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 text-slate-700' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if($dateFilter === 'custom')
            <form method="GET" action="{{ route('seller.orders.dispatch_performance') }}" class="flex items-center gap-2">
                <input type="hidden" name="date_range" value="custom">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="date" name="start_date" value="{{ $startDateParam }}" required class="text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                <span class="text-slate-400 text-xs">to</span>
                <input type="date" name="end_date" value="{{ $endDateParam }}" required class="text-xs p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                <button type="submit" class="px-3 py-1.5 bg-slate-900 dark:bg-slate-100 dark:text-slate-900 text-white text-xs font-bold rounded-lg transition">
                    Apply
                </button>
            </form>
        @endif
    </div>

    <!-- 10 Mandatory Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <!-- 1. Pending Dispatch -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">1. Pending Dispatch</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['pending_dispatch']) }}</div>
            <div class="text-[10px] text-amber-600 font-semibold mt-0.5">Orders in processing</div>
        </div>

        <!-- 2. Ready to Ship -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">2. Ready to Ship</div>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($metrics['ready_to_ship']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Packed &amp; labeled</div>
        </div>

        <!-- 3. Shipment Created -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">3. Shipment Created</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['shipment_created']) }}</div>
            <div class="text-[10px] text-sky-600 font-semibold mt-0.5">Manifest generated</div>
        </div>

        <!-- 4. Pickup Pending -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">4. Pickup Pending</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($metrics['pickup_pending']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Awaiting courier scan</div>
        </div>

        <!-- 5. Picked Up -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">5. Picked Up</div>
            <div class="text-2xl font-black text-purple-600 mt-1">{{ number_format($metrics['picked_up']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Handed to carrier</div>
        </div>

        <!-- 6. In Transit -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">6. In Transit</div>
            <div class="text-2xl font-black text-sky-600 mt-1">{{ number_format($metrics['in_transit']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">In courier network</div>
        </div>

        <!-- 7. Delivered -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">7. Delivered</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($metrics['delivered']) }}</div>
            <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Successful arrival</div>
        </div>

        <!-- 8. Delayed Shipments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs {{ $metrics['delayed_shipments'] > 0 ? 'bg-rose-50/50 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900' : '' }}">
            <div class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">8. Delayed Shipments</div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($metrics['delayed_shipments']) }}</div>
            <div class="text-[10px] text-rose-500 mt-0.5">SLA deadline passed</div>
        </div>

        <!-- 9. Failed Delivery -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">9. Failed Delivery</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['failed_delivery']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">NDR raised / attempt issue</div>
        </div>

        <!-- 10. RTO Shipments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-2xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">10. RTO Shipments</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ number_format($metrics['rto_shipments']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Return to origin</div>
        </div>
    </div>

    <!-- Dispatch Performance Summary & Daily Trend Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- SLA Health Card (1 col) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-4 flex flex-col justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Dispatch SLA Compliance</div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-4xl font-black text-slate-900 dark:text-white">{{ $kpis['on_time_rate'] }}%</span>
                    <span class="text-xs font-bold {{ $kpis['on_time_rate'] >= 95 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $kpis['on_time_rate'] >= 95 ? 'Excellent Health' : 'Needs Attention' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Calculated from packages dispatched within the mandatory 24-hour SLA window.</p>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                    <div class="bg-indigo-600 h-3 rounded-full transition-all duration-500" style="width: {{ min(100, $kpis['on_time_rate']) }}%"></div>
                </div>
                <div class="flex justify-between text-[11px] text-slate-500">
                    <span>Target: 95.0%</span>
                    <span>Actual: <strong>{{ $kpis['on_time_rate'] }}%</strong></span>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Total Parcels</span>
                    <span class="font-black text-slate-800 dark:text-slate-200 text-sm">{{ number_format($kpis['total_orders']) }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">RTO Rate</span>
                    <span class="font-black text-amber-600 text-sm">{{ $kpis['rto_rate'] }}%</span>
                </div>
            </div>
        </div>

        <!-- Daily Trends Visual Bar Chart (2 cols) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Daily Dispatch Trends</div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white mt-0.5">Dispatched vs Delivered Activity</h3>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-bold">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-600 inline-block"></span> Dispatched</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span> Delivered</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-rose-500 inline-block"></span> Delayed</span>
                </div>
            </div>

            <!-- Responsive Visual Chart -->
            <div class="pt-4 pb-2">
                @php
                    $maxVal = max(1, ...$chartData['dispatched'], ...$chartData['delivered'], ...$chartData['delayed']);
                @endphp
                <div class="h-44 flex items-end gap-2 sm:gap-3 overflow-x-auto pb-2">
                    @forelse($chartData['labels'] as $idx => $label)
                        @php
                            $disp = $chartData['dispatched'][$idx] ?? 0;
                            $delv = $chartData['delivered'][$idx] ?? 0;
                            $dly = $chartData['delayed'][$idx] ?? 0;
                            $dispHeight = round(($disp / $maxVal) * 140);
                            $delvHeight = round(($delv / $maxVal) * 140);
                            $dlyHeight = round(($dly / $maxVal) * 140);
                        @endphp
                        <div class="flex-1 min-w-[28px] flex flex-col items-center gap-1 group relative">
                            <!-- Tooltip -->
                            <div class="absolute -top-10 bg-slate-900 text-white text-[10px] font-bold py-1 px-2 rounded shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition z-20 pointer-events-none">
                                {{ $label }}: {{ $disp }} disp, {{ $delv }} delv, {{ $dly }} dly
                            </div>
                            <div class="w-full flex items-end justify-center gap-0.5 h-36">
                                <div class="w-2 bg-indigo-600 rounded-t-sm" style="height: {{ max(4, $dispHeight) }}px"></div>
                                <div class="w-2 bg-emerald-500 rounded-t-sm" style="height: {{ max(4, $delvHeight) }}px"></div>
                                @if($dly > 0)
                                    <div class="w-2 bg-rose-500 rounded-t-sm" style="height: {{ max(4, $dlyHeight) }}px"></div>
                                @endif
                            </div>
                            <span class="text-[9px] font-mono text-slate-400 truncate max-w-full">{{ $label }}</span>
                        </div>
                    @empty
                        <div class="w-full h-full flex items-center justify-center text-xs text-slate-400 italic">
                            No dispatch activity recorded in selected timeframe.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Order-level Dispatch Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <!-- Sub-tabs and Search Bar -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <!-- Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0 text-xs font-bold">
                @php
                    $tabs = [
                        'all' => 'All Orders',
                        'pending' => 'Pending Dispatch',
                        'ready_to_ship' => 'Ready to Ship',
                        'manifested' => 'Manifested',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'delayed' => 'SLA Delayed',
                        'ndr_failed' => 'NDR / Failed',
                        'rto' => 'RTO',
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tabLabel)
                    <a 
                        href="{{ route('seller.orders.dispatch_performance', array_merge(request()->except(['tab', 'page']), ['tab' => $tabKey])) }}" 
                        class="px-3 py-1.5 rounded-xl whitespace-nowrap transition {{ ($tab ?? 'all') === $tabKey ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}"
                    >
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search form -->
            <form method="GET" action="{{ route('seller.orders.dispatch_performance') }}" class="relative w-full sm:w-64">
                <input type="hidden" name="date_range" value="{{ $dateFilter }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search package, AWB, SKU..." 
                    class="w-full text-xs py-1.5 pl-8 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 font-bold uppercase text-slate-400 text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Package / Order</th>
                        <th class="py-3 px-4">Destination</th>
                        <th class="py-3 px-4">Dispatch SLA</th>
                        <th class="py-3 px-4">Shiprocket Courier &amp; AWB</th>
                        <th class="py-3 px-4">Pickup Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($records as $fo)
                        @php
                            $order = $fo->order;
                            $shipment = $fo->shipment;
                            $addr = is_array($order?->address_snapshot) ? $order->address_snapshot : [];
                            $expectedDispatch = $fo->created_at->addHours(24);
                            $isOverdue = $expectedDispatch->isPast() && in_array($fo->status, ['placed', 'store_accepted', 'packing', 'confirmed', 'ready', 'ready_to_ship']);
                            $calculatedWeight = $fo->items->sum(fn($i) => ((float)($i->variant?->weight_grams ?? 500) / 1000) * $i->qty);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            <!-- Package & Order Info -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <a href="{{ route('seller.orders.show', $fo) }}" class="hover:text-indigo-600 transition">
                                        #{{ $fo->fulfilment_order_number }}
                                    </a>
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                    Parent: #{{ $order?->order_number ?? 'N/A' }} &bull; {{ $fo->created_at->format('d M, h:i A') }}
                                </div>
                                <div class="mt-1">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $fo->statusBadgeClass() }}">
                                        {{ $fo->statusLabel() }}
                                    </span>
                                </div>
                            </td>

                            <!-- Destination -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $addr['city'] ?? 'Bengaluru' }}, {{ $addr['state'] ?? 'Karnataka' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    PIN: {{ $addr['pincode'] ?? '560001' }}
                                </div>
                            </td>

                            <!-- Dispatch SLA -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-bold {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">
                                    @if($isOverdue)
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span>SLA Overdue!</span>
                                    @else
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>On-Time</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    Target: {{ $expectedDispatch->format('d M, h:i A') }}
                                </div>
                            </td>

                            <!-- Shiprocket Courier & AWB -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($shipment && $shipment->awb)
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                        <span>{{ $shipment->courier }}</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        AWB: <strong>{{ $shipment->awb }}</strong>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">
                                        Weight: {{ $shipment->weight }} kg &bull; {{ format_currency($shipment->charges) }}
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                        Pending Shiprocket Allocation
                                    </span>
                                @endif
                            </td>

                            <!-- Pickup Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($shipment)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize
                                        {{ $shipment->pickup_status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($shipment->pickup_status === 'scheduled' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $shipment->pickup_status ?? 'Pending' }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px] italic">Not Dispatched</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(!$shipment || !$shipment->awb)
                                        <button 
                                            type="button" 
                                            @click="openDispatch('{{ $fo->id }}', '{{ $fo->fulfilment_order_number }}', {{ max(0.2, round($calculatedWeight, 2)) }})"
                                            class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs"
                                        >
                                            Dispatch (Shiprocket)
                                        </button>
                                    @else
                                        <!-- Live Sync Tracking -->
                                        <form method="POST" action="{{ route('seller.shipments.sync_tracking', $shipment) }}" class="inline">
                                            @csrf
                                            <button type="submit" title="Sync live tracking from Shiprocket" class="p-1 rounded-md text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </form>

                                        <!-- Download Label -->
                                        <a href="{{ route('seller.shipments.label', $shipment) }}" target="_blank" title="Print Shipping Label" class="p-1 rounded-md text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Track Console -->
                                        <a href="{{ route('seller.shipments.show', $shipment) }}" class="text-indigo-600 dark:text-indigo-400 font-bold text-[11px] hover:underline px-1">
                                            Track &rarr;
                                        </a>
                                    @endif

                                    <a href="{{ route('seller.orders.show', $fo) }}" class="text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-bold text-[11px] px-1">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl mx-auto mb-2">
                                    🚚
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm">No dispatches match criteria</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Try selecting another tab or broadening your date filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $records->links() }}
            </div>
        @endif
    </div>

    <!-- Shiprocket Dispatch & Dimension Validation Modal -->
    <div 
        x-show="dispatchModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
        @keydown.escape.window="dispatchModal = false"
    >
        <div 
            @click.away="dispatchModal = false" 
            class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200 dark:border-slate-800"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Shiprocket Carrier Allocation</h3>
                    <p class="text-xs text-slate-500">Package #<span x-text="foNumber"></span> &bull; Enter parcel dimensions and weight</p>
                </div>
                <button type="button" @click="dispatchModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" :action="`{{ url('/seller/orders') }}/${selectedFo}/create-shipment`" class="space-y-4">
                @csrf
                
                <!-- Package Dimensions & Weight -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Package Dimensions (cm) &amp; Weight (kg) *</label>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <span class="text-[10px] text-slate-400">Length</span>
                            <input type="number" step="0.1" min="0.5" max="200" name="length" x-model="length" required class="w-full text-xs p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400">Breadth</span>
                            <input type="number" step="0.1" min="0.5" max="200" name="breadth" x-model="breadth" required class="w-full text-xs p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400">Height</span>
                            <input type="number" step="0.1" min="0.5" max="200" name="height" x-model="height" required class="w-full text-xs p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400">Weight (kg)</span>
                            <input type="number" step="0.01" min="0.05" max="100" name="weight" x-model="weight" @change="fetchRates()" required class="w-full text-xs p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-bold text-indigo-600">
                        </div>
                    </div>
                </div>

                <!-- Available Shiprocket Couriers & Live Rates -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Available Shiprocket Courier Partners</label>
                        <button type="button" @click="fetchRates()" class="text-[10px] font-bold text-indigo-600 hover:underline">
                            Refresh Rates ⟳
                        </button>
                    </div>

                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        <div x-show="loadingCouriers" class="py-4 text-center text-xs text-slate-400">
                            Querying live Shiprocket courier serviceability &amp; freight rates...
                        </div>

                        <template x-for="c in couriers" :key="c.courier_company_id">
                            <label class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer transition text-xs" :class="selectedCourierId == c.courier_company_id ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="courier_id" :value="c.courier_company_id" x-model="selectedCourierId" @change="selectedCourierName = c.courier_name" class="text-indigo-600">
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white" x-text="c.courier_name"></div>
                                        <div class="text-[10px] text-slate-400" x-text="`ETD: ${c.etd || '2-4 Days'} • Rating: ${c.rating || 4.2} ★`"></div>
                                    </div>
                                </div>
                                <div class="font-black text-slate-900 dark:text-white" x-text="`₹${Number(c.rate).toFixed(2)}`"></div>
                            </label>
                        </template>

                        <div x-show="!loadingCouriers && couriers.length === 0" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-center text-xs text-slate-400">
                            Default Shiprocket Surface carrier will be auto-assigned.
                        </div>
                    </div>
                    <input type="hidden" name="courier_name" :value="selectedCourierName">
                </div>

                <!-- Submit / Cancel -->
                <div class="flex items-center space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button 
                        type="button" 
                        @click="dispatchModal = false" 
                        class="w-1/2 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="w-1/2 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        Confirm &amp; Generate AWB
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
