@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Global Orders Registry'])

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <x-page-header 
        title="Orders & Fulfilment Central" 
        subtitle="Platform-wide marketplace orders, multi-vendor split consignments, Shiprocket tracking, and payment reconciliations."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Orders' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                {{ number_format($tabCounts['all'] ?? $metrics['total_orders'] ?? 0) }} Total Orders
            </span>
        </x-slot>

        <x-slot name="actions">
            <a 
                href="{{ route('admin.orders.export', request()->query()) }}" 
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs"
                title="Export filtered orders list to CSV"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export Orders CSV</span>
            </a>
        </x-slot>
    </x-page-header>

    <!-- Metrics Cards Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Gross Merchandise Value</div>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ format_currency($metrics['total_revenue'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400">Total processed marketplace volume</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Fulfilment</div>
            <div class="text-xl sm:text-2xl font-black text-amber-500">
                {{ number_format($metrics['processing'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400">Active packaging &amp; dispatch SLA</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Commerce</div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 flex items-center space-x-1">
                <span>⚡ {{ number_format($metrics['quick_orders'] ?? 0) }}</span>
            </div>
            <div class="text-[10px] text-slate-400">10-30 min hyper-local routing</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Delivered Orders</div>
            <div class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400">
                {{ number_format($metrics['delivered'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400">Successfully fulfilled</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Cancelled / Returns</div>
            <div class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400">
                {{ number_format($metrics['cancelled'] ?? 0) }}
            </div>
            <div class="text-[10px] text-slate-400">Aborted or RMA requested</div>
        </div>
    </div>

    <!-- 10 Order Tabs Navigation Strip -->
    @php
        $activeTab = $status ?? $tab ?? 'all';
        $tabs = [
            'all' => ['label' => 'All Orders', 'icon' => '📦', 'color' => 'slate'],
            'pending' => ['label' => 'Pending', 'icon' => '⏳', 'color' => 'amber'],
            'processing' => ['label' => 'Processing', 'icon' => '⚙️', 'color' => 'blue'],
            'ready_to_ship' => ['label' => 'Ready to Ship', 'icon' => '🏷️', 'color' => 'indigo'],
            'shipped' => ['label' => 'Shipped', 'icon' => '🚚', 'color' => 'purple'],
            'delivered' => ['label' => 'Delivered', 'icon' => '✅', 'color' => 'emerald'],
            'cancelled' => ['label' => 'Cancelled', 'icon' => '❌', 'color' => 'rose'],
            'return_requested' => ['label' => 'Return Requested', 'icon' => '↩️', 'color' => 'amber'],
            'returned' => ['label' => 'Returned', 'icon' => '🔄', 'color' => 'teal'],
            'rto' => ['label' => 'RTO', 'icon' => '🚫', 'color' => 'red'],
        ];
    @endphp
    <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-none">
        <nav class="flex space-x-2 py-1 min-w-max" aria-label="Order Status Tabs">
            @foreach($tabs as $tabKey => $meta)
                @php
                    $isActive = ($activeTab === $tabKey);
                    $count = $tabCounts[$tabKey] ?? 0;
                    $url = route('admin.orders.index', array_merge(request()->query(), ['status' => $tabKey, 'page' => 1]));
                @endphp
                <a 
                    href="{{ $url }}" 
                    class="group inline-flex items-center space-x-2 px-3.5 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isActive ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200/80 dark:border-slate-800' }}"
                >
                    <span>{{ $meta['icon'] }}</span>
                    <span>{{ $meta['label'] }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        {{ number_format($count) }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="space-y-3">
            <input type="hidden" name="status" value="{{ $activeTab }}">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative lg:col-span-2">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search ?? '' }}" 
                        placeholder="Search order #, package #, customer name/email/phone, SKU, AWB..." 
                        class="w-full text-xs font-medium pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Seller Filter -->
                <div>
                    <select name="seller_id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">All Sellers</option>
                        @foreach($sellers as $sel)
                            <option value="{{ $sel->id }}" {{ ($sellerId ?? '') == $sel->id ? 'selected' : '' }}>
                                {{ $sel->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Fulfilment Type Filter -->
                <div>
                    <select name="fulfilment_type" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="all">All Fulfilment Modes</option>
                        <option value="quick" {{ ($fulfilmentType ?? 'all') === 'quick' ? 'selected' : '' }}>⚡ Quick Commerce</option>
                        <option value="standard" {{ ($fulfilmentType ?? 'all') === 'standard' ? 'selected' : '' }}>📦 Standard Shipping</option>
                    </select>
                </div>
            </div>

            <!-- Secondary Filters Row -->
            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <!-- Payment Status Filter -->
                <select name="payment_status" class="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="all">All Payment States</option>
                    <option value="paid" {{ ($paymentStatus ?? 'all') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ ($paymentStatus ?? 'all') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cod_pending" {{ ($paymentStatus ?? 'all') === 'cod_pending' ? 'selected' : '' }}>COD Pending</option>
                    <option value="refunded" {{ ($paymentStatus ?? 'all') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="failed" {{ ($paymentStatus ?? 'all') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                <!-- Payment Method Filter -->
                <select name="payment_method" class="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="all">All Payment Methods</option>
                    <option value="online" {{ ($paymentMethod ?? 'all') === 'online' ? 'selected' : '' }}>Online (Gateway)</option>
                    <option value="upi" {{ ($paymentMethod ?? 'all') === 'upi' ? 'selected' : '' }}>UPI</option>
                    <option value="cod" {{ ($paymentMethod ?? 'all') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                    <option value="wallet" {{ ($paymentMethod ?? 'all') === 'wallet' ? 'selected' : '' }}>Wallet</option>
                </select>

                <!-- Date Range Filter -->
                <select name="date_range" class="text-xs font-semibold px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="all">All Dates</option>
                    <option value="today" {{ ($dateRange ?? 'all') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ ($dateRange ?? 'all') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="week" {{ ($dateRange ?? 'all') === 'week' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="month" {{ ($dateRange ?? 'all') === 'month' ? 'selected' : '' }}>This Month</option>
                </select>

                <!-- Date From / To -->
                <div class="flex items-center space-x-1.5 text-xs text-slate-500">
                    <input 
                        type="date" 
                        name="from_date" 
                        value="{{ $fromDate ?? '' }}" 
                        placeholder="From Date" 
                        class="text-xs font-medium px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                    >
                    <span>to</span>
                    <input 
                        type="date" 
                        name="to_date" 
                        value="{{ $toDate ?? '' }}" 
                        placeholder="To Date" 
                        class="text-xs font-medium px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                    >
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shrink-0 shadow-xs">
                        Filter Orders
                    </button>

                    @if(!empty($search) || ($paymentStatus ?? 'all') !== 'all' || ($paymentMethod ?? 'all') !== 'all' || ($fulfilmentType ?? 'all') !== 'all' || ($dateRange ?? 'all') !== 'all' || !empty($sellerId) || !empty($fromDate) || !empty($toDate))
                        <a href="{{ route('admin.orders.index', ['status' => $activeTab]) }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        @if($orders->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-3xl">
                    📦
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No orders found in this section</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Try clearing your filters or selecting a different status tab above.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                        <tr>
                            <th class="p-4">Order # &amp; Date</th>
                            <th class="p-4">Customer Details</th>
                            <th class="p-4">Sellers &amp; Packages</th>
                            <th class="p-4">Fulfilment Mode</th>
                            <th class="p-4">Order Total</th>
                            <th class="p-4">Payment</th>
                            <th class="p-4">Order Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @foreach($orders as $ord)
                            @php
                                $isQuick = $ord->fulfilmentOrders->contains(fn($fo) => $fo->fulfilment_type === \App\Enums\FulfilmentType::QUICK);
                                $city = $ord->address_snapshot['city'] ?? null;
                                $state = $ord->address_snapshot['state'] ?? null;
                                $pincode = $ord->address_snapshot['pincode'] ?? null;
                                $packageCount = $ord->fulfilmentOrders->count();
                                $uniqueSellers = $ord->fulfilmentOrders->map(fn($fo) => $fo->seller?->business_name)->filter()->unique();
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        <a href="{{ route('admin.orders.show', $ord) }}" class="hover:text-emerald-600 transition flex items-center space-x-1.5">
                                            <span>#{{ $ord->order_number }}</span>
                                            @if($isQuick)
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                                                    ⚡ Quick
                                                </span>
                                            @endif
                                        </a>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        {{ $ord->created_at->format('d M Y, h:i A') }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ $ord->address_snapshot['name'] ?? $ord->customer?->user?->name ?? 'Shopper' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[180px]">
                                        {{ $ord->customer?->user?->email ?? $ord->address_snapshot['phone'] ?? 'N/A' }}
                                    </div>
                                    @if($city || $pincode)
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                            {{ $city }}{{ $city && $state ? ', ' . $state : '' }}{{ $pincode ? ' - ' . $pincode : '' }}
                                        </div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $packageCount }} package(s)
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-semibold truncate max-w-[200px] mt-0.5" title="{{ $uniqueSellers->join(', ') }}">
                                        {{ $uniqueSellers->take(2)->join(', ') }}{{ $uniqueSellers->count() > 2 ? ' +' . ($uniqueSellers->count() - 2) . ' more' : '' }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    @if($isQuick)
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                                            <span>⚡</span>
                                            <span>10-30 min Quick</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                            <span>📦</span>
                                            <span>Standard Cargo</span>
                                        </span>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ format_currency((float) $ord->total) }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        Subtotal: {{ format_currency((float) $ord->subtotal) }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 uppercase text-[11px]">
                                        {{ $ord->payment_method->value ?? 'Online' }}
                                    </div>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $ord->payment_status === \App\Enums\PaymentStatus::PAID ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400' }}">
                                        {{ strtoupper(str_replace('_', ' ', $ord->payment_status->value ?? 'Pending')) }}
                                    </span>
                                </td>

                                <td class="p-4">
                                    <x-order-status-badge :status="$ord->status" size="sm" />
                                </td>

                                <td class="p-4 text-right">
                                    <div class="inline-flex items-center space-x-1.5">
                                        <a 
                                            href="{{ route('admin.orders.invoice', $ord) }}" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Download Tax Invoice PDF"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>

                                        <a 
                                            href="{{ route('admin.orders.show', $ord) }}" 
                                            class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 text-xs font-bold transition inline-block"
                                        >
                                            Inspect &rarr;
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
