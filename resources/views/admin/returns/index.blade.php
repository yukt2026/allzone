@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Returns, RTO & Refund Administration'])

@section('content')
<div class="space-y-6" x-data="{ 
    approveModalReturnId: null, 
    approveModalRma: '',
    rejectModalReturnId: null, 
    rejectModalRma: '',
    refundModalReturn: null,
    refundMode: 'original'
}">

    <!-- Page Header & Action Bar -->
    <x-page-header 
        title="Returns & RMA Oversight" 
        subtitle="Centralized administration of customer return claims, merchant QC inspections, Shiprocket reverse logistics, and Razorpay/COD refund disbursements."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Returns & Refunds' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                {{ number_format($metrics['total_returns']) }} Total Returns Logged
            </span>
        </x-slot>

        <x-slot name="actions">
            <a 
                href="{{ route('admin.returns.export') }}" 
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs"
                title="Export returns and RMA audit report to CSV"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export Returns CSV</span>
            </a>
        </x-slot>
    </x-page-header>

    <!-- Actual Metrics Summary Grid (8 KPIs) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <!-- 1. Return Requests -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'requested']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-amber-400 transition">
            <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Requested</div>
            <div class="text-xl font-black text-amber-600 dark:text-amber-400">
                {{ number_format($metrics['return_requests']) }}
            </div>
            <div class="text-[9px] text-slate-400">Awaiting review</div>
        </a>

        <!-- 2. Approved Returns -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'approved']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-emerald-400 transition">
            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Approved</div>
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                {{ number_format($metrics['approved_returns']) }}
            </div>
            <div class="text-[9px] text-slate-400">Reverse pickup set</div>
        </a>

        <!-- 3. Pending Inspections / QC -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'pending_inspection']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-blue-400 transition">
            <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">QC Pending</div>
            <div class="text-xl font-black text-blue-600 dark:text-blue-400">
                {{ number_format($metrics['pending_inspections']) }}
            </div>
            <div class="text-[9px] text-slate-400">At warehouse/hub</div>
        </a>

        <!-- 4. Rejected Returns -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'rejected']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-rose-400 transition">
            <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Rejected</div>
            <div class="text-xl font-black text-rose-600 dark:text-rose-400">
                {{ number_format($metrics['rejected_returns']) }}
            </div>
            <div class="text-[9px] text-slate-400">Disallowed claims</div>
        </a>

        <!-- 5. RTO Shipments -->
        <a href="{{ route('admin.returns.index', ['view' => 'rto']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-red-400 transition">
            <div class="text-[10px] font-bold text-red-500 uppercase tracking-wider">RTO Cargo</div>
            <div class="text-xl font-black text-red-600 dark:text-red-400">
                {{ number_format($metrics['rto_shipments']) }}
            </div>
            <div class="text-[9px] text-slate-400">Undelivered origin</div>
        </a>

        <!-- 6. Refund Pending -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'refund_pending']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-teal-400 transition">
            <div class="text-[10px] font-bold text-teal-500 uppercase tracking-wider">Refund Pending</div>
            <div class="text-xl font-black text-teal-600 dark:text-teal-400">
                {{ number_format($metrics['refund_pending']) }}
            </div>
            <div class="text-[9px] text-slate-400">QC verified &amp; ready</div>
        </a>

        <!-- 7. Refund Completed -->
        <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => 'refunded']) }}" class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1 hover:border-emerald-400 transition">
            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Refunded</div>
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                {{ number_format($metrics['refund_completed']) }}
            </div>
            <div class="text-[9px] text-slate-400">Payment settled</div>
        </a>

        <!-- 8. Total Value Refunded -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Disbursed</div>
            <div class="text-base font-black text-slate-900 dark:text-white truncate" title="{{ format_currency($metrics['total_refunded_amount']) }}">
                {{ format_currency($metrics['total_refunded_amount']) }}
            </div>
            <div class="text-[9px] text-slate-400">Settled refunds</div>
        </div>
    </div>

    <!-- Analytics Cards: Return Reasons & Product-wise Trends -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Return Reasons Breakdown -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Customer Return Reasons Breakdown</h4>
                <span class="text-[11px] text-slate-400">Distribution by volume</span>
            </div>

            @if($returnReasons->isEmpty())
                <div class="text-xs text-slate-400 italic py-4">No return reasons recorded yet.</div>
            @else
                <div class="space-y-2">
                    @foreach($returnReasons as $rr)
                        <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-xs">{{ $rr->reason ?: 'Unspecified Reason' }}</span>
                            <div class="flex items-center space-x-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                                    {{ $rr->count }} claim(s)
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white text-[11px]">
                                    {{ format_currency((float) $rr->total_amount) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product-wise Return Trends -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Product-Wise Return Trends</h4>
                <span class="text-[11px] text-slate-400">Highest return frequency</span>
            </div>

            @if($productTrends->isEmpty())
                <div class="text-xs text-slate-400 italic py-4">No product returns data available yet.</div>
            @else
                <div class="space-y-2">
                    @foreach($productTrends as $pt)
                        <div class="flex items-center justify-between text-xs p-2 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <div class="truncate max-w-xs">
                                <div class="font-bold text-slate-900 dark:text-white truncate">{{ $pt->title }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $pt->sku }}</div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">
                                    {{ $pt->return_count }} returns
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white text-[11px]">
                                    {{ format_currency((float) $pt->total_refund_amount) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs: Return Claims vs. RTO Cargo -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-2 py-1" aria-label="Returns View Tabs">
            <a 
                href="{{ route('admin.returns.index', ['view' => 'returns']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $viewTab === 'returns' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>↩️</span>
                <span>Customer Return Claims</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $viewTab === 'returns' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($metrics['total_returns']) }}
                </span>
            </a>

            <a 
                href="{{ route('admin.returns.index', ['view' => 'rto']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $viewTab === 'rto' ? 'bg-red-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>🚫</span>
                <span>RTO Undelivered Consignments</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $viewTab === 'rto' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-400' }}">
                    {{ number_format($metrics['rto_shipments']) }}
                </span>
            </a>
        </nav>
    </div>

    <!-- ========================================== -->
    <!-- VIEW 1: CUSTOMER RETURN CLAIMS REGISTRY    -->
    <!-- ========================================== -->
    @if($viewTab === 'returns')
        @php
            $returnTabs = [
                'all' => 'All Claims',
                'requested' => 'Pending Review',
                'approved' => 'Approved / In Reverse Transit',
                'pending_inspection' => 'Pending QC Inspection',
                'qc_passed' => 'QC Passed',
                'refund_pending' => 'Refund Pending',
                'refunded' => 'Refund Completed',
                'rejected' => 'Rejected',
            ];
        @endphp

        <!-- Status Filter Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-none">
            <nav class="flex space-x-1.5 py-1 min-w-max">
                @foreach($returnTabs as $k => $label)
                    @php $isActive = ($statusTab === $k); @endphp
                    <a 
                        href="{{ route('admin.returns.index', array_merge(request()->query(), ['view' => 'returns', 'status' => $k, 'page' => 1])) }}" 
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $isActive ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200/80 dark:border-slate-800' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-xs">
            <form method="GET" action="{{ route('admin.returns.index') }}" class="space-y-3">
                <input type="hidden" name="view" value="returns">
                <input type="hidden" name="status" value="{{ $statusTab }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="relative lg:col-span-2">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Search RMA #, order #, waybill, customer name/email, SKU..." 
                            class="w-full text-xs font-medium pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <div>
                        <select name="seller_id" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">All Merchant Sellers</option>
                            @foreach($sellers as $sel)
                                <option value="{{ $sel->id }}" {{ request('seller_id') == $sel->id ? 'selected' : '' }}>
                                    {{ $sel->business_name ?? $sel->legal_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <select name="qc_status" class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="all">All QC States</option>
                            @foreach(\App\Enums\QCStatus::cases() as $qc)
                                <option value="{{ $qc->value }}" {{ request('qc_status') === $qc->value ? 'selected' : '' }}>
                                    {{ $qc->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2 text-xs text-slate-500">
                        <span>Date:</span>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                        <span>to</span>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                            Filter Claims
                        </button>
                        @if(request()->hasAny(['search', 'seller_id', 'qc_status', 'from_date', 'to_date']))
                            <a href="{{ route('admin.returns.index', ['view' => 'returns', 'status' => $statusTab]) }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Returns Claims Table -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            @if($returns->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center text-3xl">
                        ↩️
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No return claims matching criteria</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Try clearing search filters or checking other status tabs.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">RMA # &amp; Date</th>
                                <th class="p-4">Parent Order / Customer</th>
                                <th class="p-4">Merchant Origin</th>
                                <th class="p-4">Item &amp; Reason</th>
                                <th class="p-4">Claim Value</th>
                                <th class="p-4">Return Status</th>
                                <th class="p-4">QC Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @foreach($returns as $ret)
                                @php
                                    $item = $ret->orderItem;
                                    $fulfilment = $item?->fulfilmentOrder;
                                    $order = $fulfilment?->order;
                                    $user = $order?->customer?->user ?? $order?->user;
                                    $seller = $fulfilment?->seller;
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white font-mono">
                                            <a href="{{ route('admin.returns.show', $ret) }}" class="hover:text-emerald-600 transition">
                                                #{{ $ret->return_number }}
                                            </a>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-sans mt-0.5">
                                            {{ $ret->created_at->format('d M Y, h:i A') }}
                                        </div>
                                        @if($ret->pickup_waybill)
                                            <div class="mt-1 flex items-center text-[10px] text-indigo-600 dark:text-indigo-400 font-mono">
                                                <span>🚚 {{ $ret->pickup_waybill }}</span>
                                            </div>
                                        @endif
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
                                        <div class="text-[11px] text-slate-400">
                                            {{ $user?->name ?? 'Customer' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            {{ $user?->phone ?? $user?->email }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $seller?->business_name ?? 'Merchant' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Package #{{ $fulfilment?->fulfilment_order_number }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white truncate max-w-xs">
                                            {{ $item?->title ?? 'Item' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            SKU: {{ $item?->sku }} &bull; Qty: {{ $item?->qty ?? 1 }}
                                        </div>
                                        <div class="text-[10px] text-amber-600 dark:text-amber-400 truncate max-w-xs font-semibold mt-0.5">
                                            "{{ $ret->reason }}"
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ format_currency((float) ($ret->refund_amount ?? $item?->total ?? 0)) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 uppercase">
                                            Mode: {{ $ret->refund_mode ?? 'original' }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $ret->status->badgeClass() }}">
                                            {{ $ret->status->label() }}
                                        </span>
                                    </td>

                                    <td class="p-4">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold {{ $ret->qc_status === \App\Enums\QCStatus::PASSED ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : ($ret->qc_status === \App\Enums\QCStatus::FAILED ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300') }}">
                                            {{ $ret->qc_status->label() }}
                                        </span>
                                    </td>

                                    <td class="p-4 text-right">
                                        <div class="inline-flex items-center space-x-1.5">
                                            @if($ret->status === \App\Enums\ReturnStatus::REQUESTED)
                                                <button 
                                                    type="button" 
                                                    @click="approveModalReturnId = {{ $ret->id }}; approveModalRma = '{{ $ret->return_number }}'; $dispatch('open-modal', 'approve-return-modal')"
                                                    class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 text-[11px] font-bold transition"
                                                    title="Approve Return Request"
                                                >
                                                    Approve
                                                </button>

                                                <button 
                                                    type="button" 
                                                    @click="rejectModalReturnId = {{ $ret->id }}; rejectModalRma = '{{ $ret->return_number }}'; $dispatch('open-modal', 'reject-return-modal')"
                                                    class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-[11px] font-bold transition"
                                                    title="Reject Return Request"
                                                >
                                                    Reject
                                                </button>
                                            @endif

                                            @if($ret->status !== \App\Enums\ReturnStatus::REFUNDED && $ret->status !== \App\Enums\ReturnStatus::REJECTED)
                                                <button 
                                                    type="button" 
                                                    @click="refundModalReturn = {{ json_encode([
                                                        'id' => $ret->id,
                                                        'number' => $ret->return_number,
                                                        'amount' => (float) ($ret->refund_amount ?? $item?->total ?? 0),
                                                        'is_cod' => ($order?->payment_method?->value === 'cod'),
                                                        'resellable' => (bool) $ret->is_resellable,
                                                    ]) }}; $dispatch('open-modal', 'initiate-refund-modal')"
                                                    class="px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-700 dark:bg-teal-950/50 dark:text-teal-400 text-[11px] font-bold transition"
                                                    title="Initiate Gateway / Payout Refund"
                                                >
                                                    ⚡ Refund
                                                </button>
                                            @endif

                                            <a 
                                                href="{{ route('admin.returns.show', $ret) }}" 
                                                class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-block"
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
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- ========================================== -->
    <!-- VIEW 2: RTO UNDELIVERED CONSIGNMENTS       -->
    <!-- ========================================== -->
    @if($viewTab === 'rto')
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Return to Origin (RTO) Consignments</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Shipments marked for return by carrier due to delivery failures, customer refusal, or address unreachable.</p>
                </div>
            </div>

            @if(!$rtoShipments || $rtoShipments->isEmpty())
                <div class="p-12 text-center space-y-3">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center text-3xl">
                        ✅
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">No active RTO consignments!</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">All active platform deliveries are proceeding normally without return-to-origin incidents.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 tracking-wider">
                            <tr>
                                <th class="p-4">AWB &amp; Shipment #</th>
                                <th class="p-4">Parent Order / Package</th>
                                <th class="p-4">Carrier Partner</th>
                                <th class="p-4">Origin Merchant</th>
                                <th class="p-4">Destination Profile</th>
                                <th class="p-4">RTO Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @foreach($rtoShipments as $rtoShp)
                                @php
                                    $fo = $rtoShp->fulfilmentOrder;
                                    $order = $fo?->order;
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="p-4 font-mono font-bold text-slate-900 dark:text-white">
                                        <div class="text-red-600">{{ $rtoShp->awb ?? $rtoShp->shipment_number }}</div>
                                        <div class="text-[10px] text-slate-400 font-sans font-normal mt-0.5">
                                            {{ $rtoShp->created_at->format('d M Y') }}
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
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            Package #{{ $fo?->fulfilment_order_number }}
                                        </div>
                                    </td>

                                    <td class="p-4 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $rtoShp->courier ?? 'Shiprocket Carrier' }}
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $fo?->seller?->business_name ?? 'Merchant' }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $order?->customer?->user?->name ?? 'Shopper' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ $order?->address_snapshot['city'] ?? 'Bengaluru' }} ({{ $order?->address_snapshot['pincode'] ?? '' }})
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-400">
                                            {{ $rtoShp->status->label() }}
                                        </span>
                                    </td>

                                    <td class="p-4 text-right">
                                        <a 
                                            href="{{ route('admin.shipping.show_shipment', $rtoShp) }}" 
                                            class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition inline-block"
                                        >
                                            Inspect Consignment &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $rtoShipments->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- MODAL 1: Approve Return Modal -->
    <x-modal name="approve-return-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-emerald-600">✅</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Approve Return <span x-text="'#' + approveModalRma"></span>
                </h3>
            </div>
        </x-slot>

        <form 
            id="approveReturnForm" 
            method="POST" 
            :action="'{{ url('admin/returns') }}/' + approveModalReturnId + '/approve'" 
            class="space-y-4"
        >
            @csrf
            <p class="text-xs text-slate-500">
                Authorizes the customer return request. Reverse courier pickup can be scheduled with Shiprocket automatically.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Approval Notes / Remarks</label>
                <textarea 
                    name="notes" 
                    required 
                    rows="3" 
                    placeholder="Enter approval remarks and packaging instructions for customer..." 
                    class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                ></textarea>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" name="schedule_pickup" value="1" checked id="chk_schedule_pickup" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="chk_schedule_pickup" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                    Automatically schedule reverse pickup with Shiprocket
                </label>
            </div>
        </form>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'approve-return-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Cancel
            </button>
            <button 
                form="approveReturnForm"
                type="submit" 
                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
            >
                Confirm Approval
            </button>
        </x-slot>
    </x-modal>

    <!-- MODAL 2: Reject Return Modal -->
    <x-modal name="reject-return-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-rose-600">❌</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Reject Return <span x-text="'#' + rejectModalRma"></span>
                </h3>
            </div>
        </x-slot>

        <form 
            id="rejectReturnForm" 
            method="POST" 
            :action="'{{ url('admin/returns') }}/' + rejectModalReturnId + '/reject'" 
            class="space-y-4"
        >
            @csrf
            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">
                State clear policy grounds for rejecting this return claim. The reason will be notified to the customer.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mandatory Rejection Reason</label>
                <textarea 
                    name="reason" 
                    required 
                    rows="3" 
                    placeholder="Enter reason for rejection (e.g. return window expired, customer damage, non-returnable category)..." 
                    class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500"
                ></textarea>
            </div>
        </form>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'reject-return-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Cancel
            </button>
            <button 
                form="rejectReturnForm"
                type="submit" 
                class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
            >
                Confirm Rejection
            </button>
        </x-slot>
    </x-modal>

    <!-- MODAL 3: Initiate Refund Modal -->
    <x-modal name="initiate-refund-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-teal-600">⚡</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Execute Refund <span x-text="refundModalReturn ? '#' + refundModalReturn.number : ''"></span>
                </h3>
            </div>
        </x-slot>

        <form 
            id="initiateRefundForm" 
            method="POST" 
            :action="'{{ url('admin/returns') }}/' + (refundModalReturn ? refundModalReturn.id : 0) + '/initiate-refund'" 
            class="space-y-4"
        >
            @csrf

            <!-- Payment Method Alert -->
            <template x-if="refundModalReturn && refundModalReturn.is_cod">
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 space-y-1">
                    <div class="font-bold">⚠️ Cash on Delivery (COD) Order Notice</div>
                    <p>This order was placed via COD. Refunds cannot be processed via Razorpay gateway. Choose Store Wallet credit or enter verified Bank Transfer / NEFT payout reference.</p>
                </div>
            </template>

            <template x-if="refundModalReturn && !refundModalReturn.is_cod">
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-200 space-y-1">
                    <div class="font-bold">💳 Online Gateway Order (Razorpay)</div>
                    <p>Refund will be dispatched back to the customer's original online payment instrument via Razorpay API.</p>
                </div>
            </template>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Refund Amount (₹)</label>
                <input 
                    type="number" 
                    step="0.01" 
                    name="amount" 
                    :value="refundModalReturn ? refundModalReturn.amount : 0" 
                    required 
                    class="w-full text-xs font-bold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Refund Destination Mode</label>
                <select 
                    name="mode" 
                    x-model="refundMode"
                    class="w-full text-xs font-semibold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                >
                    <option value="original">Original Payment Instrument (Razorpay / Bank)</option>
                    <option value="wallet">Customer Store Wallet (Instant Credit)</option>
                    <option value="cod_payout">Verified Manual Payout (COD / Direct NEFT)</option>
                </select>
            </div>

            <template x-if="refundModalReturn && refundModalReturn.is_cod">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Bank Payout UTR / NEFT Reference</label>
                    <input 
                        type="text" 
                        name="payout_reference" 
                        placeholder="e.g. UTR1234567890 or Bank Ref #" 
                        class="w-full text-xs font-mono p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                    >
                    <input type="hidden" name="confirm_cod_payout" value="1">
                </div>
            </template>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="is_resellable" value="1" checked id="chk_refund_resellable" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <label for="chk_refund_resellable" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                    Restock inventory in merchant warehouse (Item is in resellable condition)
                </label>
            </div>
        </form>

        <x-slot name="footer">
            <button 
                type="button" 
                @click="$dispatch('close-modal', 'initiate-refund-modal')" 
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
            >
                Cancel
            </button>
            <button 
                form="initiateRefundForm"
                type="submit" 
                class="px-5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition"
            >
                Disburse Refund
            </button>
        </x-slot>
    </x-modal>

</div>
@endsection
