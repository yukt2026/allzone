@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Shiprocket Shipping Administration'])

@section('content')
<div class="space-y-6" x-data="{ 
    createModalFoId: null, 
    createModalFoNumber: '', 
    cancelModalShipmentId: null, 
    cancelModalAwb: '',
    webhookPayload: null 
}">

    <!-- Page Header & Action Bar -->
    <x-page-header 
        title="Shiprocket Shipping Administration" 
        subtitle="Centralized logistics management, Courier Fallback Priority Chain, live Shiprocket AWB consignments, and multi-carrier routing."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Shipping & Logistics' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $settings['shiprocket_enabled'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' }}">
                {{ $settings['shiprocket_enabled'] ? ($settings['shiprocket_fake'] ? 'Shiprocket Sandbox Active' : 'Shiprocket Live Production') : 'Shiprocket Disabled' }}
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.shipping.test_shiprocket') }}">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer"
                        title="Ping Shiprocket API to verify authentication & token validity"
                    >
                        <span>⚡</span>
                        <span>Test Connection</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.shipping.sync_all') }}">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer"
                        title="Sync live carrier tracking for in-transit shipments"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Sync Tracking</span>
                    </button>
                </form>

                <a 
                    href="{{ route('admin.shipping.pickup_locations.index') }}" 
                    class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
                >
                    <span>🏢</span>
                    <span>Pickup Locations</span>
                </a>

                <a 
                    href="{{ route('admin.shipping.failed_queue') }}" 
                    class="px-3.5 py-2 rounded-xl {{ $metrics['failed_delivery'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-400 dark:border-rose-800' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200' }} text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
                >
                    <span>⚠️ Failed Queue ({{ $metrics['failed_delivery'] }})</span>
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- 10 KPI Dashboard Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
        <!-- 1. Total Shipments -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Shipments</div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ number_format($metrics['total_shipments']) }}
            </div>
            <div class="text-[10px] text-slate-400">Total consignments booked</div>
        </div>

        <!-- 2. Creation Pending -->
        <a href="{{ route('admin.shipping.index', ['view' => 'pending_creation']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-amber-400 transition group">
            <div class="text-[11px] font-bold text-amber-500 uppercase tracking-wider flex items-center justify-between">
                <span>Creation Pending</span>
                <span>&rarr;</span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($metrics['creation_pending']) }}
            </div>
            <div class="text-[10px] text-slate-400">Awaiting Shiprocket AWB</div>
        </a>

        <!-- 3. Pickup Pending -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'pickup_pending']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-blue-400 transition">
            <div class="text-[11px] font-bold text-blue-500 uppercase tracking-wider">Pickup Pending</div>
            <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400">
                {{ number_format($metrics['pickup_pending']) }}
            </div>
            <div class="text-[10px] text-slate-400">Manifest generated</div>
        </a>

        <!-- 4. Picked Up -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'picked_up']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-indigo-400 transition">
            <div class="text-[11px] font-bold text-indigo-500 uppercase tracking-wider">Picked Up</div>
            <div class="text-xl sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">
                {{ number_format($metrics['picked_up']) }}
            </div>
            <div class="text-[10px] text-slate-400">Carrier in possession</div>
        </a>

        <!-- 5. In Transit -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'in_transit']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-purple-400 transition">
            <div class="text-[11px] font-bold text-purple-500 uppercase tracking-wider">In Transit</div>
            <div class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400">
                {{ number_format($metrics['in_transit']) }}
            </div>
            <div class="text-[10px] text-slate-400">Hub &amp; linehaul routing</div>
        </a>

        <!-- 6. Delivered -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'delivered']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-emerald-400 transition">
            <div class="text-[11px] font-bold text-emerald-500 uppercase tracking-wider">Delivered</div>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ number_format($metrics['delivered']) }}
            </div>
            <div class="text-[10px] text-slate-400">Successfully fulfilled</div>
        </a>

        <!-- 7. Delayed -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'delayed']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-amber-400 transition">
            <div class="text-[11px] font-bold text-amber-500 uppercase tracking-wider">Delayed</div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($metrics['delayed']) }}
            </div>
            <div class="text-[10px] text-slate-400">SLA breached</div>
        </a>

        <!-- 8. Failed Delivery / NDR -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'failed_delivery']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-rose-400 transition">
            <div class="text-[11px] font-bold text-rose-500 uppercase tracking-wider">Failed / NDR</div>
            <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400">
                {{ number_format($metrics['failed_delivery']) }}
            </div>
            <div class="text-[10px] text-slate-400">Non-delivery reported</div>
        </a>

        <!-- 9. RTO -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'rto']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-red-400 transition">
            <div class="text-[11px] font-bold text-red-500 uppercase tracking-wider">RTO (Return Origin)</div>
            <div class="text-xl sm:text-2xl font-black text-red-600 dark:text-red-400">
                {{ number_format($metrics['rto']) }}
            </div>
            <div class="text-[10px] text-slate-400">Returned to merchant</div>
        </a>

        <!-- 10. Cancelled -->
        <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => 'cancelled']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-slate-400 transition">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Cancelled</div>
            <div class="text-xl sm:text-2xl font-black text-slate-500 dark:text-slate-400">
                {{ number_format($metrics['cancelled']) }}
            </div>
            <div class="text-[10px] text-slate-400">Consignments aborted</div>
        </a>
    </div>

    <!-- Top-Level Module Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-2 py-1" aria-label="Shipping Navigation Tabs">
            <a 
                href="{{ route('admin.shipping.index', ['view' => 'registry']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'registry' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>🚚</span>
                <span>Shipments Registry</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'registry' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($metrics['total_shipments']) }}
                </span>
            </a>

            <a 
                href="{{ route('admin.shipping.index', ['view' => 'pending_creation']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'pending_creation' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>⏳</span>
                <span>Creation Pending Queue</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $activeTab === 'pending_creation' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400' }}">
                    {{ number_format($metrics['creation_pending']) }}
                </span>
            </a>

            <a 
                href="{{ route('admin.shipping.index', ['view' => 'config']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'config' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>⚙️</span>
                <span>Shiprocket API &amp; Rules</span>
            </a>

            <a 
                href="{{ route('admin.shipping.index', ['view' => 'serviceability']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'serviceability' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>📍</span>
                <span>Serviceability &amp; Rates</span>
            </a>

            <a 
                href="{{ route('admin.shipping.index', ['view' => 'webhooks']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $activeTab === 'webhooks' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>⚡</span>
                <span>Webhook Logs</span>
            </a>
        </nav>
    </div>

    <!-- ========================================== -->
    <!-- VIEW 1: SHIPMENTS REGISTRY (Default)       -->
    <!-- ========================================== -->
    @if($activeTab === 'registry')
        <!-- Status Filter Tabs -->
        @php
            $registryTabs = [
                'all' => 'All Shipments',
                'pickup_pending' => 'Pickup Pending',
                'picked_up' => 'Picked Up',
                'in_transit' => 'In Transit',
                'delivered' => 'Delivered',
                'delayed' => 'Delayed',
                'failed_delivery' => 'Failed Delivery',
                'rto' => 'RTO',
                'cancelled' => 'Cancelled',
            ];
        @endphp
        <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-none">
            <nav class="flex space-x-1.5 py-1 min-w-max">
                @foreach($registryTabs as $key => $label)
                    @php
                        $isTabActive = ($statusTab === $key);
                        $count = $tabCounts[$key] ?? 0;
                    @endphp
                    <a 
                        href="{{ route('admin.shipping.index', array_merge(request()->query(), ['view' => 'registry', 'status' => $key, 'page' => 1])) }}" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $isTabActive ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200/80 dark:border-slate-800' }}"
                    >
                        <span>{{ $label }}</span>
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $isTabActive ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                            {{ number_format($count) }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
            <form method="GET" action="{{ route('admin.shipping.index') }}" class="space-y-3">
                <input type="hidden" name="view" value="registry">
                <input type="hidden" name="status" value="{{ $statusTab }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="relative lg:col-span-2">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Search by AWB code, shipment #, order #, courier, customer..." 
                            class="w-full text-xs font-medium pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <div>
                        <select name="seller_id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">All Origin Sellers</option>
                            @foreach($sellers as $sel)
                                <option value="{{ $sel->id }}" {{ request('seller_id') == $sel->id ? 'selected' : '' }}>
                                    {{ $sel->business_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select name="date_range" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="all">All Dates</option>
                            <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ request('date_range') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="week" {{ request('date_range') === 'week' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="month" {{ request('date_range') === 'month' ? 'selected' : '' }}>This Month</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2 text-xs text-slate-500">
                        <span>Date Range:</span>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                        <span>to</span>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                            Filter Shipments
                        </button>
                        @if(request()->hasAny(['search', 'seller_id', 'date_range', 'from_date', 'to_date']))
                            <a href="{{ route('admin.shipping.index', ['view' => 'registry', 'status' => $statusTab]) }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Shipments Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            @if($shipments->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-3xl">
                        🚚
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No shipments matching criteria</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Try clearing search filters or checking the creation pending queue.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">AWB &amp; Shipment #</th>
                                <th class="p-4">Package / Order</th>
                                <th class="p-4">Origin Merchant</th>
                                <th class="p-4">Carrier &amp; Mode</th>
                                <th class="p-4">Weight &amp; Charges</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @foreach($shipments as $shp)
                                @php
                                    $fo = $shp->fulfilmentOrder;
                                    $order = $fo?->order;
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center space-x-1.5">
                                            @if($shp->awb)
                                                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ $shp->awb }}</span>
                                            @else
                                                <span class="text-slate-400 italic">No AWB Assigned</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                            #{{ $shp->shipment_number }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $shp->created_at->format('d M Y, h:i A') }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            @if($order)
                                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:text-emerald-600 transition">
                                                    Order #{{ $order->order_number }}
                                                </a>
                                            @else
                                                <span>N/A</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">
                                            Package #{{ $fo?->fulfilment_order_number }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-0.5">
                                            Destination: {{ $order?->address_snapshot['city'] ?? 'Bengaluru' }} ({{ $order?->address_snapshot['pincode'] ?? '' }})
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $fo?->seller?->business_name ?? 'Marketplace Seller' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            Store: {{ $fo?->store?->name ?? 'Main Hub' }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">
                                            {{ $shp->courier ?? 'Shiprocket Surface' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 uppercase">
                                            Service: {{ $shp->service ?? 'Surface' }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ format_currency((float) $shp->charges) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Weight: {{ number_format((float) $shp->weight, 2) }} kg
                                        </div>
                                        @if($shp->cod_amount > 0)
                                            <div class="text-[10px] text-amber-600 font-bold">
                                                COD: {{ format_currency((float) $shp->cod_amount) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="p-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold {{ $shp->status === \App\Enums\ShipmentStatus::DELIVERED ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : ($shp->status === \App\Enums\ShipmentStatus::CANCELLED ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300') }}">
                                            {{ $shp->status->label() }}
                                        </span>
                                    </td>

                                    <td class="p-4 text-right">
                                        <div class="inline-flex items-center space-x-1.5">
                                            @if($shp->awb)
                                                <form method="POST" action="{{ route('admin.shipping.track_shipment', $shp) }}">
                                                    @csrf
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Refresh Live Carrier Tracking">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($shp->label_url)
                                                <a href="{{ $shp->label_url }}" target="_blank" class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Download Shipping Label">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </a>
                                            @endif

                                            @if(!in_array($shp->status, [\App\Enums\ShipmentStatus::DELIVERED, \App\Enums\ShipmentStatus::CANCELLED]))
                                                <button 
                                                    type="button" 
                                                    @click="cancelModalShipmentId = {{ $shp->id }}; cancelModalAwb = '{{ $shp->awb ?? $shp->shipment_number }}'; $dispatch('open-modal', 'cancel-shipment-modal')"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                                    title="Cancel Shipment Consignment"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            @endif

                                            <a 
                                                href="{{ route('admin.shipping.show_shipment', $shp) }}" 
                                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-block"
                                            >
                                                Details &rarr;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- ========================================== -->
    <!-- VIEW 2: CREATION PENDING QUEUE             -->
    <!-- ========================================== -->
    @if($activeTab === 'pending_creation')
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Orders &amp; Consignments Awaiting Shiprocket Booking</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Approved packages ready for carrier consignment generation and AWB assignment.</p>
                </div>
            </div>

            @if(!$pendingFulfilmentOrders || $pendingFulfilmentOrders->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center text-3xl">
                        ✅
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">All orders dispatched!</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">There are currently no active merchant packages waiting for shipment creation.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">Package # &amp; Order #</th>
                                <th class="p-4">Origin Merchant</th>
                                <th class="p-4">Destination Pincode</th>
                                <th class="p-4">Items Count</th>
                                <th class="p-4">Order Value</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @foreach($pendingFulfilmentOrders as $pfo)
                                @php
                                    $pOrder = $pfo->order;
                                    $destPin = $pOrder?->customerAddress?->pincode ?? $pOrder?->address_snapshot['pincode'] ?? 'N/A';
                                    $originPin = $pfo->seller?->defaultPickupLocation?->pincode ?? $pfo->store?->pincode ?? '560038';
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            #{{ $pfo->fulfilment_order_number }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            Parent Order: <a href="{{ route('admin.orders.show', $pOrder) }}" class="font-bold text-emerald-600 hover:underline">#{{ $pOrder?->order_number }}</a>
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $pfo->seller?->business_name ?? 'Merchant' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Pickup Pin: {{ $originPin }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $pOrder?->address_snapshot['city'] ?? 'Bengaluru' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Delivery Pin: {{ $destPin }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $pfo->items->count() }} item(s)
                                        </span>
                                    </td>

                                    <td class="p-4 font-bold text-slate-900 dark:text-white">
                                        {{ format_currency((float) $pfo->subtotal) }}
                                    </td>

                                    <td class="p-4 text-right">
                                        <button 
                                            type="button" 
                                            @click="createModalFoId = {{ $pfo->id }}; createModalFoNumber = '{{ $pfo->fulfilment_order_number }}'; $dispatch('open-modal', 'create-shipment-modal')"
                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer"
                                        >
                                            Book Shiprocket &rarr;
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $pendingFulfilmentOrders->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- ========================================== -->
    <!-- VIEW 3: SHIPROCKET CONFIGURATION & RULES   -->
    <!-- ========================================== -->
    @if($activeTab === 'config')
        <form method="POST" action="{{ route('admin.shipping.update') }}" class="space-y-6">
            @csrf

            <!-- 1. Shiprocket Credentials & Auth -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="flex flex-wrap items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Shiprocket API Credentials &amp; Environment</h3>
                        <p class="text-xs text-slate-500">Configure credentials provided in your Shiprocket external developer dashboard.</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center space-x-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 cursor-pointer">
                            <input type="checkbox" name="shiprocket_enabled" value="1" {{ $settings['shiprocket_enabled'] ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Integration Enabled</span>
                        </label>

                        <label class="flex items-center space-x-2 text-xs font-bold text-amber-600 dark:text-amber-400 cursor-pointer">
                            <input type="checkbox" name="shiprocket_fake" value="1" {{ $settings['shiprocket_fake'] ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            <span>Sandbox / Simulation Mode</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Shiprocket Registered Email</label>
                        <input 
                            type="email" 
                            name="shiprocket_email" 
                            value="{{ $settings['shiprocket_email'] }}" 
                            placeholder="api@allzone.in" 
                            class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Shiprocket Password
                            @if($settings['shiprocket_password_set'])
                                <span class="text-[10px] text-emerald-600 font-normal ml-1">● Password configured</span>
                            @endif
                        </label>
                        <input 
                            type="password" 
                            name="shiprocket_password" 
                            value="" 
                            placeholder="{{ $settings['shiprocket_password_set'] ? '•••••••••••• (Leave blank to keep current)' : 'Enter API password' }}" 
                            class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Static Bearer Token / API Key (Optional)</label>
                        <input 
                            type="text" 
                            name="shiprocket_api_key" 
                            value="{{ $settings['shiprocket_api_key'] }}" 
                            placeholder="Optional: static JWT token override" 
                            class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Shiprocket Base URL</label>
                        <input 
                            type="text" 
                            name="shiprocket_base_url" 
                            value="{{ $settings['shiprocket_base_url'] }}" 
                            class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>
                </div>
            </div>

            <!-- 2. Package Dimensions & Volumetric Rules -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Default Package Weight &amp; Dimensions</h3>
                    <p class="text-xs text-slate-500">Standard packaging presets applied when merchant does not specify item box measurements.</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Default Weight (kg)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="default_weight_kg" 
                            value="{{ $settings['default_weight_kg'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Length (cm)</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            name="default_length_cm" 
                            value="{{ $settings['default_length_cm'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Width (cm)</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            name="default_width_cm" 
                            value="{{ $settings['default_width_cm'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Height (cm)</label>
                        <input 
                            type="number" 
                            step="0.1" 
                            name="default_height_cm" 
                            value="{{ $settings['default_height_cm'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Volumetric Divisor</label>
                        <input 
                            type="number" 
                            name="volumetric_divisor" 
                            value="{{ $settings['volumetric_divisor'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>
                </div>
            </div>

            <!-- 3. Shipping Charge Rules & Safeguards -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Shipping Charge Rules &amp; Limits</h3>
                    <p class="text-xs text-slate-500">Customer delivery fees, free delivery subsidies, and COD risk caps.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Base Delivery Fee (₹)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="standard_base_fee" 
                            value="{{ $settings['standard_base_fee'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Free Shipping Order Threshold (₹)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="free_shipping_threshold" 
                            value="{{ $settings['free_shipping_threshold'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Extra Weight Slab Fee (₹ / kg)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="extra_kg_slab_fee" 
                            value="{{ $settings['extra_kg_slab_fee'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Maximum COD Order Limit (₹)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            name="cod_max_limit" 
                            value="{{ $settings['cod_max_limit'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Max RTO Re-Delivery Attempts</label>
                        <input 
                            type="number" 
                            name="rto_max_attempts" 
                            value="{{ $settings['rto_max_attempts'] }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div class="flex items-center pt-6">
                        <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="auto_generate_awb" value="1" {{ $settings['auto_generate_awb'] ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Auto-Generate AWB on Consignment Creation</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 4. Webhook Security Token -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-3">
                <div class="pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Webhook Security &amp; Endpoints</h3>
                    <p class="text-xs text-slate-500">Paste your configured Shiprocket tracking webhook token here for request signature verification.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Webhook Secret Token</label>
                        <input 
                            type="text" 
                            name="shiprocket_webhook_token" 
                            value="{{ $settings['shiprocket_webhook_token'] }}" 
                            placeholder="Optional secret token expected in x-api-key header" 
                            class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Your Inbound Webhook URL</label>
                        <input 
                            type="text" 
                            readonly 
                            value="{{ $settings['shiprocket_webhook_url'] }}" 
                            class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 cursor-text"
                        >
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                    Save Configuration &amp; Rules
                </button>
            </div>
        </form>
    @endif

    <!-- ========================================== -->
    <!-- VIEW 4: SERVICEABILITY & RATES CALCULATOR  -->
    <!-- ========================================== -->
    @if($activeTab === 'serviceability')
        <div class="space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Live Courier Serviceability &amp; Delivery SLA Lookup</h3>
                    <p class="text-xs text-slate-500">Real-time check against Shiprocket API for courier availability, freight charges, and estimated delivery dates.</p>
                </div>

                <form method="POST" action="{{ route('admin.shipping.check_serviceability') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pickup Origin Pincode</label>
                        <input 
                            type="text" 
                            name="pickup_pincode" 
                            required 
                            maxlength="6" 
                            value="{{ session('serviceability_query.pickup_pincode', '560038') }}" 
                            class="w-full text-xs font-mono font-bold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Delivery Destination Pincode</label>
                        <input 
                            type="text" 
                            name="delivery_pincode" 
                            required 
                            maxlength="6" 
                            value="{{ session('serviceability_query.delivery_pincode', '110001') }}" 
                            class="w-full text-xs font-mono font-bold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Package Weight (kg)</label>
                        <input 
                            type="number" 
                            step="0.05" 
                            name="weight_kg" 
                            value="{{ session('serviceability_query.weight_kg', 0.5) }}" 
                            class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                            Query Shiprocket Couriers &rarr;
                        </button>
                    </div>
                </form>
            </div>

            <!-- Serviceability Results -->
            @if(session('serviceability_results'))
                @php
                    $results = session('serviceability_results');
                @endphp
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">
                            Available Couriers ({{ count($results) }})
                        </h4>
                        <span class="text-xs text-slate-400 font-mono">
                            Route: {{ session('serviceability_query.pickup_pincode') }} &rarr; {{ session('serviceability_query.delivery_pincode') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($results as $c)
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-xs font-bold text-slate-900 dark:text-white">
                                        {{ $c['courier_name'] ?? 'Shiprocket Courier' }}
                                    </h5>
                                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">
                                        {{ format_currency((float) ($c['rate'] ?? $c['freight_charge'] ?? 85)) }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 space-y-0.5">
                                    <div>Estimated Delivery: <strong>{{ $c['etd'] ?? ($c['estimated_delivery_days'] ? $c['estimated_delivery_days'] . ' Days' : '2-4 Days') }}</strong></div>
                                    <div>Courier ID: <span class="font-mono">{{ $c['courier_company_id'] ?? 'N/A' }}</span></div>
                                    <div>Rating: <strong>★ {{ number_format((float) ($c['rating'] ?? 4.5), 1) }}</strong></div>
                                    <div>COD Supported: <strong>{{ !empty($c['cod']) ? 'Yes' : 'No' }}</strong></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- ========================================== -->
    <!-- VIEW 5: WEBHOOK EVENT LOGS                 -->
    <!-- ========================================== -->
    @if($activeTab === 'webhooks')
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Inbound Shiprocket Webhook Event Audit</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Real-time carrier event logs, delivery callbacks, and authentication signatures.</p>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    Endpoint: <strong class="text-slate-700 dark:text-slate-300">{{ $settings['shiprocket_webhook_url'] }}</strong>
                </div>
            </div>

            @if(!$webhookLogs || $webhookLogs->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-3xl">
                        ⚡
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No webhook events logged yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Shiprocket delivery status callbacks will appear here automatically.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">Time</th>
                                <th class="p-4">Event / Status</th>
                                <th class="p-4">AWB &amp; Order ID</th>
                                <th class="p-4">Source IP</th>
                                <th class="p-4">Verification</th>
                                <th class="p-4 text-right">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium font-mono text-[11px]">
                            @foreach($webhookLogs as $log)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-4 text-slate-400 font-normal">
                                        {{ $log->created_at->format('d M, h:i:s A') }}
                                    </td>
                                    <td class="p-4 font-bold text-slate-900 dark:text-white capitalize font-sans">
                                        {{ str_replace('_', ' ', $log->event) }}
                                    </td>
                                    <td class="p-4">
                                        <div class="text-emerald-600 font-bold">{{ $log->awb ?? 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $log->order_id }}</div>
                                    </td>
                                    <td class="p-4 text-slate-500">
                                        {{ $log->source_ip }}
                                    </td>
                                    <td class="p-4 font-sans">
                                        @if($log->is_verified)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                Verified
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">
                                                Unverified
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right font-sans">
                                        <button 
                                            type="button" 
                                            @click="webhookPayload = {{ json_encode($log->payload) }}; $dispatch('open-modal', 'webhook-payload-modal')"
                                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-bold transition"
                                        >
                                            View JSON
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $webhookLogs->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- MODAL 1: Create Shipment Consignment -->
    <x-modal name="create-shipment-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-emerald-600">📦</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Create Shiprocket Consignment <span x-text="'#' + createModalFoNumber"></span>
                </h3>
            </div>
        </x-slot>

        <form 
            id="createShipmentForm" 
            method="POST" 
            :action="'{{ url('admin/shipping/orders') }}/' + createModalFoId + '/create-shipment'" 
            class="space-y-4"
        >
            @csrf
            <p class="text-xs text-slate-500">
                Book consignment with Shiprocket. AWB number, shipping label, and courier assignment will be generated.
            </p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Weight (kg)</label>
                    <input type="number" step="0.05" name="weight" value="{{ $settings['default_weight_kg'] }}" class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Length (cm)</label>
                    <input type="number" step="0.5" name="length" value="{{ $settings['default_length_cm'] }}" class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Breadth / Width (cm)</label>
                    <input type="number" step="0.5" name="breadth" value="{{ $settings['default_width_cm'] }}" class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Height (cm)</label>
                    <input type="number" step="0.5" name="height" value="{{ $settings['default_height_cm'] }}" class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>
        </form>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'create-shipment-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Cancel
            </button>
            <button 
                form="createShipmentForm"
                type="submit" 
                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
            >
                Confirm &amp; Book Consignment
            </button>
        </x-slot>
    </x-modal>

    <!-- MODAL 2: Cancel Consignment Modal -->
    <x-modal name="cancel-shipment-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-rose-600">🚫</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Cancel Consignment <span x-text="'#' + cancelModalAwb"></span>
                </h3>
            </div>
        </x-slot>

        <form 
            id="cancelShipmentForm" 
            method="POST" 
            :action="'{{ url('admin/shipping/shipments') }}/' + cancelModalShipmentId + '/cancel'" 
            class="space-y-4"
        >
            @csrf
            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">
                This will trigger courier cancellation with Shiprocket before courier pickup.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cancellation Reason</label>
                <textarea 
                    name="reason" 
                    required 
                    rows="3" 
                    placeholder="Enter reason for cancelling this consignment..." 
                    class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500"
                ></textarea>
            </div>
        </form>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'cancel-shipment-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Keep Shipment
            </button>
            <button 
                form="cancelShipmentForm"
                type="submit" 
                class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
            >
                Confirm Cancellation
            </button>
        </x-slot>
    </x-modal>

    <!-- MODAL 3: Webhook Payload JSON Viewer -->
    <x-modal name="webhook-payload-modal" size="lg">
        <x-slot name="header">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Webhook Payload JSON</h3>
        </x-slot>

        <div class="p-2 bg-slate-950 text-emerald-400 font-mono text-xs rounded-xl overflow-x-auto max-h-96">
            <pre x-text="JSON.stringify(webhookPayload, null, 2)"></pre>
        </div>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'webhook-payload-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Close
            </button>
        </x-slot>
    </x-modal>

</div>
@endsection
