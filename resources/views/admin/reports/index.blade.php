@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Analytics & Marketplace Reports'])

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <x-page-header 
        title="Business Intelligence & Reports" 
        subtitle="Analyze marketplace sales velocity, seller GMV, financial ledgers, inventory valuation, refunds, and promotion ROI."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Reports' => '#']"
    >
        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                <!-- CSV Export -->
                <a 
                    href="{{ route('admin.reports.export', array_merge($filters, ['type' => $tab, 'format' => 'csv'])) }}" 
                    class="px-3 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                    title="Export as CSV"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>CSV</span>
                </a>

                <!-- XLSX Export -->
                <a 
                    href="{{ route('admin.reports.export', array_merge($filters, ['type' => $tab, 'format' => 'xlsx'])) }}" 
                    class="px-3 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                    title="Export as Excel XLSX"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>XLSX</span>
                </a>

                <!-- PDF Export -->
                <a 
                    href="{{ route('admin.reports.export', array_merge($filters, ['type' => $tab, 'format' => 'pdf'])) }}" 
                    class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                    title="Export as PDF Document"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>PDF</span>
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Global Financial Reconciliation KPI Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Gross GMV</span>
            <div class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">{{ format_currency($metrics['grossGmv']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">{{ number_format($metrics['ordersCount']) }} orders</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Net Sales</span>
            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ format_currency($metrics['netSales']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Post-refund volume</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Collected Payments</span>
            <div class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">{{ format_currency($metrics['collectedPayments']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Captured gateway & COD</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Platform Revenue</span>
            <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ format_currency($metrics['platformRevenue']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Net commissions</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Seller Payable</span>
            <div class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">{{ format_currency($metrics['sellerPayable']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Post-deduction liability</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 block">Total Refunds</span>
            <div class="text-lg font-bold text-rose-600 dark:text-rose-400 mt-0.5">{{ format_currency($metrics['totalRefunds']) }}</div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Approved & completed</span>
        </div>
    </div>

    <!-- Controls Filter Strip -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        
        <!-- Tab Bar (15 Pillars) -->
        <div class="border-b border-slate-100 dark:border-slate-800/80 pb-3 overflow-x-auto flex items-center space-x-1.5 no-scrollbar">
            @foreach($validTabs as $tKey => $tTitle)
                <a 
                    href="{{ route('admin.reports.index', array_merge($filters, ['tab' => $tKey])) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition {{ $tab === $tKey ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                >
                    {{ $tTitle }}
                </a>
            @endforeach
        </div>

        <!-- Presets and Multi-dimensional Filters -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pt-1">
            <!-- Quick Date Presets -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="text-slate-400 font-semibold text-[11px] mr-1">Presets:</span>
                @foreach([
                    'today' => 'Today',
                    '7d' => '7 Days',
                    '30d' => '30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month'
                ] as $pKey => $pLabel)
                    <a 
                        href="{{ route('admin.reports.index', array_merge($filters, ['tab' => $tab, 'preset' => $pKey])) }}" 
                        class="px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-emerald-500 text-slate-600 dark:text-slate-300 hover:text-emerald-600 font-medium transition text-[11px] {{ ($filters['preset'] ?? '') === $pKey ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500 text-emerald-700 dark:text-emerald-400 font-bold' : '' }}"
                    >
                        {{ $pLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Custom Filter Form -->
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <!-- Seller Filter -->
                <select name="seller_id" class="text-xs bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg p-1.5 text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Sellers</option>
                    @foreach($sellers as $s)
                        <option value="{{ $s->id }}" {{ ($filters['seller_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->business_name }}</option>
                    @endforeach
                </select>

                <!-- Category Filter -->
                <select name="category_id" class="text-xs bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg p-1.5 text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ ($filters['category_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>

                <!-- Date Range Inputs -->
                <div class="flex items-center space-x-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 text-xs">
                    <span class="text-[10px] uppercase font-bold text-slate-400">From</span>
                    <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="text-xs bg-transparent border-0 p-0 text-slate-700 dark:text-slate-300 focus:ring-0">
                    <span class="text-[10px] uppercase font-bold text-slate-400">To</span>
                    <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="text-xs bg-transparent border-0 p-0 text-slate-700 dark:text-slate-300 focus:ring-0">
                </div>

                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Tab 1: Sales Performance -->
    @if($tab === 'sales')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Gross Sales Revenue</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['totalRevenue']) }}</div>
                    <span class="text-[11px] text-slate-400">Pre-refund customer billing</span>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Settled Orders</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['totalOrders']) }}</div>
                    <span class="text-[11px] text-slate-400">Completed order transactions</span>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Shipping Fees Collected</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['totalDeliveryFees']) }}</div>
                    <span class="text-[11px] text-slate-400">Cargo & delivery logistics</span>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Average Order Value (AOV)</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['avgOrderValue']) }}</div>
                    <span class="text-[11px] text-slate-400">Mean basket checkout size</span>
                </div>
            </div>

            <!-- Daily Breakdown Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Daily Sales Aggregate Timeline
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4 font-semibold">Date</th>
                                <th class="py-3 px-4 font-semibold">Orders Count</th>
                                <th class="py-3 px-4 font-semibold">Gross Revenue</th>
                                <th class="py-3 px-4 font-semibold">Shipping Collected</th>
                                <th class="py-3 px-4 font-semibold">Net Cart Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['daily'] as $row)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ $row->date }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($row->order_count) }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ format_currency((float) $row->gross) }}</td>
                                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ format_currency((float) $row->delivery_fee) }}</td>
                                    <td class="py-3 px-4 text-emerald-600 font-medium">{{ format_currency((float) $row->net) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-slate-400">No sales transactions found in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 2: Orders & Channels -->
    @elseif($tab === 'orders')
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Status -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">By Order Status</h4>
                    <div class="space-y-2">
                        @foreach($data['byStatus'] as $row)
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                                <span class="text-slate-600 dark:text-slate-400">{{ $row->label }}</span>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $row->count }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ format_currency($row->total) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Method -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">By Payment Method</h4>
                    <div class="space-y-2">
                        @foreach($data['byMethod'] as $row)
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                                <span class="text-slate-600 dark:text-slate-400">{{ $row->label }}</span>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $row->count }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ format_currency($row->total) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Channel -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3 uppercase tracking-wider">By Fulfilment Channel</h4>
                    <div class="space-y-2">
                        @foreach($data['byFulfilment'] as $row)
                            <div class="flex items-center justify-between text-xs py-1 border-b border-slate-50 dark:border-slate-800/50 last:border-0">
                                <span class="text-slate-600 dark:text-slate-400">{{ $row->label }}</span>
                                <div class="text-right">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $row->count }} pkgs</span>
                                    <span class="text-[10px] text-slate-400 block">{{ format_currency($row->total) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Recent Orders in Period
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Order #</th>
                                <th class="py-2.5 px-3 font-semibold">Customer</th>
                                <th class="py-2.5 px-3 font-semibold">Total (INR)</th>
                                <th class="py-2.5 px-3 font-semibold">Payment</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($data['recentOrders'] as $ord)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $ord->order_number }}</td>
                                    <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ $ord->customer?->user?->name ?? 'Guest' }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ format_currency((float) $ord->total) }}</td>
                                    <td class="py-2.5 px-3">
                                        @php
                                            $pStatus = $ord->payment_status instanceof \BackedEnum ? $ord->payment_status->value : (string) ($ord->payment_status ?? 'pending');
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $pStatus === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ strtoupper($pStatus) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $ord->status?->value ?? (string) $ord->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $ord->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 3: Seller Leaderboard -->
    @elseif($tab === 'sellers')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between font-bold text-xs">
                <span>Seller GMV & Performance Leaderboard</span>
                <span class="text-slate-400 font-medium">{{ $data['totalSellersCount'] }} merchants active</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Seller</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold">Orders Count</th>
                            <th class="py-3 px-4 font-semibold">Gross GMV</th>
                            <th class="py-3 px-4 font-semibold">Commission</th>
                            <th class="py-3 px-4 font-semibold">Net Earnings</th>
                            <th class="py-3 px-4 font-semibold">Return Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($data['sellers'] as $s)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $s['name'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $s['status'] === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($s['status']) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($s['orders_count']) }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ format_currency($s['gross_gmv']) }}</td>
                                <td class="py-3 px-4 text-rose-600 font-medium">{{ format_currency($s['commission']) }}</td>
                                <td class="py-3 px-4 font-bold text-emerald-600">{{ format_currency($s['net_payout']) }}</td>
                                <td class="py-3 px-4 {{ $s['return_rate'] > 10 ? 'text-rose-600 font-bold' : 'text-slate-500' }}">{{ $s['return_rate'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-slate-400">No seller sales recorded for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 4: Customer Growth -->
    @elseif($tab === 'customers')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Registered Customers</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalCustomers']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">New Sign-ups in Period</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['newCustomers']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Active Buying Shoppers</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['activeCustomers']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-600">Repeat Purchase Rate</span>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $data['repeatRate'] }}%</div>
                </div>
            </div>

            <!-- Top Customers Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Top Customers by Lifetime Value (Period Spent)
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Customer</th>
                                <th class="py-2.5 px-3 font-semibold">Email</th>
                                <th class="py-2.5 px-3 font-semibold">Orders Count</th>
                                <th class="py-2.5 px-3 font-semibold">Total Spent (INR)</th>
                                <th class="py-2.5 px-3 font-semibold">Joined Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['topCustomers'] as $c)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $c['name'] }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $c['email'] }}</td>
                                    <td class="py-2.5 px-3 font-semibold text-slate-700 dark:text-slate-300">{{ $c['orders_count'] }}</td>
                                    <td class="py-2.5 px-3 font-bold text-emerald-600">{{ format_currency($c['total_spent']) }}</td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $c['registered_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400">No customer orders recorded in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 5: Product Performance -->
    @elseif($tab === 'products')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between font-bold text-xs">
                <span>Top Selling Products & Demand Volume</span>
                <span class="text-slate-400">{{ number_format($data['totalUnitsSold']) }} units sold</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Product Title</th>
                            <th class="py-3 px-4 font-semibold">Category</th>
                            <th class="py-3 px-4 font-semibold">Seller</th>
                            <th class="py-3 px-4 font-semibold">Units Sold</th>
                            <th class="py-3 px-4 font-semibold">Average Price</th>
                            <th class="py-3 px-4 font-semibold">Gross Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($data['products'] as $p)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $p->title }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $p->category_name }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ $p->seller_name }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200">{{ number_format((int) $p->units_sold) }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ format_currency((float) $p->avg_price) }}</td>
                                <td class="py-3 px-4 font-bold text-emerald-600">{{ format_currency((float) $p->total_revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">No product sales found matching criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 6: Categories -->
    @elseif($tab === 'categories')
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between font-bold text-xs">
                <span>Category Turnover & Distribution</span>
                <span class="text-slate-400">{{ $data['totalCategoriesCount'] }} categories monitored</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Category Name</th>
                            <th class="py-3 px-4 font-semibold">Units Sold</th>
                            <th class="py-3 px-4 font-semibold">Gross Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($data['categories'] as $c)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $c['name'] }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($c['units']) }}</td>
                                <td class="py-3 px-4 font-bold text-emerald-600">{{ format_currency($c['revenue']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-slate-400">No category transactions recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    <!-- Tab 7: Inventory & Valuation -->
    @elseif($tab === 'inventory')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Monitored SKUs</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalSkus']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Total Available Stock</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['totalAvailable']) }}</div>
                    <span class="text-[11px] text-slate-400">On-hand: {{ number_format($data['totalOnHand']) }} | Reserved: {{ number_format($data['totalReserved']) }}</span>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-amber-600">Low Stock SKUs</span>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($data['lowStockCount']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-600">Out of Stock SKUs</span>
                    <div class="text-2xl font-bold text-rose-600 mt-1">{{ number_format($data['outOfStockCount']) }}</div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Live Centralized Inventory Records
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">SKU</th>
                                <th class="py-2.5 px-3 font-semibold">Product Title</th>
                                <th class="py-2.5 px-3 font-semibold">Seller</th>
                                <th class="py-2.5 px-3 font-semibold">Available Qty</th>
                                <th class="py-2.5 px-3 font-semibold">Reserved Qty</th>
                                <th class="py-2.5 px-3 font-semibold">On-Hand Qty</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $inv)
                                @php
                                    $avail = max(0, $inv->quantity_on_hand - $inv->quantity_reserved);
                                    $isLow = $inv->quantity_on_hand <= $inv->low_stock_threshold && $inv->quantity_on_hand > 0;
                                    $isOut = $inv->quantity_on_hand <= 0;
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-800 dark:text-slate-200">{{ $inv->variant?->sku ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ $inv->variant?->product?->title ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $inv->variant?->product?->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 font-bold {{ $isOut ? 'text-rose-600' : ($isLow ? 'text-amber-600' : 'text-emerald-600') }}">{{ $avail }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $inv->quantity_reserved }}</td>
                                    <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ $inv->quantity_on_hand }}</td>
                                    <td class="py-2.5 px-3">
                                        @if($isOut)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Out of Stock</span>
                                        @elseif($isLow)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Low Stock</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">In Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No inventory entries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($data['items']->hasPages())
                    <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                        {{ $data['items']->links() }}
                    </div>
                @endif
            </div>
        </div>

    <!-- Tab 8: Returns & RTO -->
    @elseif($tab === 'returns')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Return Requests</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalReturns']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Approved Returns</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['approvedReturns']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-amber-600">Pending QC / In-Transit</span>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($data['pendingQc']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-600">Refunded Volume</span>
                    <div class="text-2xl font-bold text-rose-600 mt-1">{{ format_currency($data['totalRefundAmount']) }}</div>
                </div>
            </div>

            <!-- Returns Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Returns & RTO Incident Records
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Return #</th>
                                <th class="py-2.5 px-3 font-semibold">Product</th>
                                <th class="py-2.5 px-3 font-semibold">Seller</th>
                                <th class="py-2.5 px-3 font-semibold">Reason</th>
                                <th class="py-2.5 px-3 font-semibold">Refund (INR)</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $ret)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $ret->return_number }}</td>
                                    <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200">{{ $ret->orderItem?->product?->title ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $ret->orderItem?->fulfilmentOrder?->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $ret->reason }}</td>
                                    <td class="py-2.5 px-3 font-bold text-rose-600">{{ format_currency((float) $ret->refund_amount) }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ strtoupper($ret->status instanceof \BackedEnum ? $ret->status->value : (string) $ret->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $ret->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No return requests found in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 9: Claims & Disputes -->
    @elseif($tab === 'claims')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Seller Claims</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalClaims']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-amber-600">Open / Under Review</span>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($data['openClaims']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Claimed Value</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['totalClaimed']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Approved Compensation</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ format_currency($data['totalApproved']) }}</div>
                </div>
            </div>

            <!-- Claims Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Seller Dispute & Claim History
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Claim #</th>
                                <th class="py-2.5 px-3 font-semibold">Seller</th>
                                <th class="py-2.5 px-3 font-semibold">Type</th>
                                <th class="py-2.5 px-3 font-semibold">Claimed</th>
                                <th class="py-2.5 px-3 font-semibold">Approved</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $clm)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $clm->claim_number }}</td>
                                    <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200">{{ $clm->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $clm->claim_type instanceof \BackedEnum ? $clm->claim_type->value : (string) $clm->claim_type)) }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-700 dark:text-slate-300">{{ format_currency((float) $clm->claimed_amount) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-emerald-600">{{ format_currency((float) $clm->approved_amount) }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ strtoupper($clm->status instanceof \BackedEnum ? $clm->status->value : (string) $clm->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $clm->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No seller claims found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 10: Payments & Reconciliation -->
    @elseif($tab === 'payments')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Transactions</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalPayments']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Captured Volume</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ format_currency($data['totalCapturedAmount']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-amber-600">Pending Transactions</span>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($data['pendingPayments']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-600">Failed Transactions</span>
                    <div class="text-2xl font-bold text-rose-600 mt-1">{{ number_format($data['failedPayments']) }}</div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Payment Gateway & COD Records
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Payment ID</th>
                                <th class="py-2.5 px-3 font-semibold">Order #</th>
                                <th class="py-2.5 px-3 font-semibold">Method</th>
                                <th class="py-2.5 px-3 font-semibold">Amount</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Reconciliation</th>
                                <th class="py-2.5 px-3 font-semibold">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $p)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono text-slate-900 dark:text-white">#{{ $p->id }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold">{{ $p->order?->order_number ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 uppercase text-[11px] font-bold text-slate-600">{{ $p->method }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ format_currency((float) $p->amount) }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ in_array($p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status, ['captured', 'success', 'paid']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @php
                                            $reconVal = $p->reconciliation_status instanceof \BackedEnum ? $p->reconciliation_status->value : (string) ($p->reconciliation_status ?? 'unreconciled');
                                        @endphp
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $reconVal === 'reconciled' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ ucfirst($reconVal) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $p->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No payment records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 11: Refunds -->
    @elseif($tab === 'refunds')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Refund Events</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalRefunds']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Processed Refunds</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['processedRefunds']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-600">Total Refunded Amount</span>
                    <div class="text-2xl font-bold text-rose-600 mt-1">{{ format_currency($data['totalRefundAmount']) }}</div>
                </div>
            </div>

            <!-- Refunds Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Customer Refund Disbursements
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Refund ID</th>
                                <th class="py-2.5 px-3 font-semibold">Order #</th>
                                <th class="py-2.5 px-3 font-semibold">Seller</th>
                                <th class="py-2.5 px-3 font-semibold">Amount</th>
                                <th class="py-2.5 px-3 font-semibold">Reason</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                                <th class="py-2.5 px-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $ref)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">#{{ $ref->id }}</td>
                                    <td class="py-2.5 px-3 font-mono">{{ $ref->payment?->order?->order_number ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ $ref->fulfilmentOrder?->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 font-bold text-rose-600">{{ format_currency((float) $ref->amount) }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $ref->reason ?? 'Return Refund' }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            {{ strtoupper($ref->status instanceof \BackedEnum ? $ref->status->value : (string) $ref->status) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $ref->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No refund disbursements found in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 12: Platform Commission -->
    @elseif($tab === 'commission')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Base Commissionable GMV</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['totalBaseGmv'] ?? 0) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Platform Revenue (Pre-Tax)</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ format_currency($data['totalCommission']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-600">GST on Commission (18%)</span>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">{{ format_currency($data['totalTax']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Net Seller Disbursed</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['totalSettled']) }}</div>
                </div>
            </div>

            <!-- Commission Entries Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Platform Commission Transaction Ledger
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Entry ID</th>
                                <th class="py-2.5 px-3 font-semibold">Order #</th>
                                <th class="py-2.5 px-3 font-semibold">Merchant</th>
                                <th class="py-2.5 px-3 font-semibold">Base GMV</th>
                                <th class="py-2.5 px-3 font-semibold">Rate</th>
                                <th class="py-2.5 px-3 font-semibold">Commission</th>
                                <th class="py-2.5 px-3 font-semibold">Tax (18%)</th>
                                <th class="py-2.5 px-3 font-semibold">Net Payable</th>
                                <th class="py-2.5 px-3 font-semibold">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['entries'] as $entry)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">#{{ $entry->id }}</td>
                                    <td class="py-2.5 px-3 font-mono">{{ $entry->order?->order_number ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-700 dark:text-slate-300">{{ $entry->fulfilmentOrder?->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ format_currency((float) $entry->base_amount) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-indigo-600">{{ $entry->rate ?? 0 }}%</td>
                                    <td class="py-2.5 px-3 font-bold text-emerald-600">{{ format_currency((float) $entry->commission) }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ format_currency((float) $entry->tax_on_commission) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ format_currency((float) $entry->net_payable) }}</td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[11px]">{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-6 text-center text-slate-400">No commission entries recorded in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 13: Seller Settlements -->
    @elseif($tab === 'settlements')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Settlement Batches</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalSettlements']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Net Settled Disbursed</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ format_currency($data['totalNetPaid']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-amber-600">Pending Approval</span>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($data['pendingSettlements']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-rose-600">Payout Failures</span>
                    <div class="text-2xl font-bold text-rose-600 mt-1">{{ number_format($data['failedSettlements']) }}</div>
                </div>
            </div>

            <!-- Settlements Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Seller Payout & Settlement Records
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Settlement #</th>
                                <th class="py-2.5 px-3 font-semibold">Seller</th>
                                <th class="py-2.5 px-3 font-semibold">Period</th>
                                <th class="py-2.5 px-3 font-semibold">Gross</th>
                                <th class="py-2.5 px-3 font-semibold">Commission</th>
                                <th class="py-2.5 px-3 font-semibold">Net Payout</th>
                                <th class="py-2.5 px-3 font-semibold">UTR Reference</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $s)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $s->settlement_number }}</td>
                                    <td class="py-2.5 px-3 text-slate-800 dark:text-slate-200">{{ $s->seller?->business_name ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-500 text-[11px]">{{ $s->period_start?->format('M d') }} - {{ $s->period_end?->format('M d, Y') }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ format_currency((float) $s->gross) }}</td>
                                    <td class="py-2.5 px-3 text-rose-600">{{ format_currency((float) $s->commission) }}</td>
                                    <td class="py-2.5 px-3 font-bold text-emerald-600">{{ format_currency((float) $s->net) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">{{ $s->reference ?? 'Pending Transfer' }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ strtoupper($s->status?->value ?? (string) $s->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-6 text-center text-slate-400">No seller settlements generated for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 14: Promotions -->
    @elseif($tab === 'promotions')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Total Promotional Campaigns</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ number_format($data['totalCampaigns']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Active Live Campaigns</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($data['activeCampaigns']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-indigo-600">Total Campaign Usages</span>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($data['totalUsageCount']) }}</div>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 font-bold text-xs text-slate-800 dark:text-slate-200">
                    Promotions & Campaign Performance
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-2.5 px-3 font-semibold">Campaign Name</th>
                                <th class="py-2.5 px-3 font-semibold">Type</th>
                                <th class="py-2.5 px-3 font-semibold">Discount</th>
                                <th class="py-2.5 px-3 font-semibold">Starts At</th>
                                <th class="py-2.5 px-3 font-semibold">Ends At</th>
                                <th class="py-2.5 px-3 font-semibold">Usage Count</th>
                                <th class="py-2.5 px-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($data['items'] as $camp)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $camp->name }}</td>
                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $camp->type instanceof \BackedEnum ? $camp->type->value : (string) $camp->type)) }}</td>
                                    <td class="py-2.5 px-3 font-semibold text-emerald-600">{{ $camp->discount_value }}{{ $camp->discount_type === 'percentage' ? '%' : ' INR' }}</td>
                                    <td class="py-2.5 px-3 text-slate-500 text-[11px]">{{ $camp->starts_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 text-slate-500 text-[11px]">{{ $camp->ends_at?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200">{{ number_format($camp->usage_count) }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $camp->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $camp->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400">No campaigns found in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Tab 15: Tax & GST (Preserved) -->
    @elseif($tab === 'tax')
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Gross Paid Revenue</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['grossRevenue']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Product GST Collected</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['productTax']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-slate-500">Commission GST (18%)</span>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ format_currency($data['commissionTax']) }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-xs">
                    <span class="text-xs font-semibold text-emerald-600">Total Tax Liability</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ format_currency($data['totalTaxCollected']) }}</div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
