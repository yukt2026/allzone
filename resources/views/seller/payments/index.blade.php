@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Payments & Seller Earnings - ALLZONE'])

@section('content')
<div 
    x-data="{
        modalOpen: false,
        loadingDetails: false,
        txn: null,
        errorMsg: null,
        async openDetails(id) {
            this.modalOpen = true;
            this.loadingDetails = true;
            this.txn = null;
            this.errorMsg = null;
            try {
                const res = await fetch(`{{ url('/seller/payments/transactions') }}/${id}`);
                if (!res.ok) throw new Error('Failed to load transaction details.');
                this.txn = await res.json();
            } catch (err) {
                this.errorMsg = err.message || 'Unable to retrieve transaction details.';
            } finally {
                this.loadingDetails = false;
            }
        }
    }" 
    class="space-y-6 max-w-7xl mx-auto pb-16"
>
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                Payments &amp; Seller Earnings
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Earnings &amp; Financial Ledger: Auditable marketplace ledger, upcoming settlements, platform deductions, and verified bank payouts.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('seller.settlements.index') }}" 
                class="px-3.5 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center space-x-1.5 shadow-2xs transition"
            >
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>View Settlement Batches</span>
            </a>

            <a 
                href="{{ route('seller.payments.export', request()->query()) }}" 
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center space-x-1.5 shadow-xs transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download Report (CSV)</span>
            </a>
        </div>
    </div>

    <!-- Payout Account Status Banner -->
    <div class="p-4 rounded-xl bg-slate-900 dark:bg-slate-900/90 border border-slate-800 text-white shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start sm:items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0 text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Payout Account</span>
                    @if($primaryBank && $primaryBank->is_verified)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold text-[10px] flex items-center space-x-1">
                            <span>✓</span>
                            <span>Verified Bank Account</span>
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold text-[10px]">
                            Pending Verification
                        </span>
                    @endif
                </div>
                <div class="text-sm font-black text-slate-100 mt-0.5">
                    {{ $primaryBank ? $primaryBank->bank_name : 'No Bank Account Configured' }}
                    @if($primaryBank && $primaryBank->branch_name)
                        <span class="text-xs font-normal text-slate-400">({{ $primaryBank->branch_name }})</span>
                    @endif
                </div>
                <div class="text-xs text-slate-400 mt-0.5 flex flex-wrap items-center gap-x-3">
                    @if($primaryBank)
                        <span>A/C: <span class="font-mono text-slate-200">{{ $primaryBank->masked_account_no }}</span></span>
                        <span>IFSC: <span class="font-mono text-slate-200">{{ $primaryBank->ifsc }}</span></span>
                        <span>Holder: <span class="text-slate-200">{{ $primaryBank->account_holder }}</span></span>
                    @else
                        <span class="text-rose-400">Add and verify your bank account to enable automated settlements.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="border-t md:border-t-0 md:border-l border-slate-800 pt-3 md:pt-0 md:pl-6 text-xs text-slate-400 shrink-0">
            <div>Settlement Mode: <strong class="text-slate-200">Automated NEFT / RTGS Wire</strong></div>
            <div class="text-[11px] text-slate-400 mt-0.5">Return Policy Window: <strong class="text-emerald-400">{{ $returnWindowDays }} Days Escrow</strong></div>
        </div>
    </div>

    <!-- Payment Dashboard KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Upcoming Settlements -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <span>Upcoming Settlements</span>
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">
                    ₹{{ number_format($upcomingSettlements, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Expected next settlement disbursement
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] space-y-1">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Matured (Ready to Settle):</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono">₹{{ number_format($upcomingMaturedAmount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>In Return Window ({{ $returnWindowDays }}d):</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">₹{{ number_format($upcomingMaturingAmount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Completed Settlements -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <span>Completed Settlements</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
                <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-2 font-mono">
                    ₹{{ number_format($completedSettlements, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Disbursed directly to bank account
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-600 dark:text-slate-400">Paid Batches:</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-400 font-bold text-[10px] border border-emerald-200 dark:border-emerald-800">
                    {{ $completedBatchesCount }} Settled Batches
                </span>
            </div>
        </div>

        <!-- Card 3: Pending & On-Hold Settlements -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <span>Pending &amp; On-Hold</span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                </div>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-2 font-mono">
                    ₹{{ number_format($pendingSettlements + $onHoldAmounts, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Batch in progress or compliance review
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] space-y-1">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Processing Payout:</span>
                    <span class="font-bold text-indigo-700 dark:text-indigo-400 font-mono">₹{{ number_format($pendingSettlements, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>On-Hold Amount:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">₹{{ number_format($onHoldAmounts, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Net Seller Payable & Deductions -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    <span>Net Seller Payable</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                </div>
                <div class="text-2xl font-black text-emerald-800 dark:text-emerald-400 mt-2 font-mono">
                    ₹{{ number_format($netSellerPayable, 2) }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    Lifetime net earned from ₹{{ number_format($grossSales, 2) }} gross
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] space-y-1">
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Commission Deductions:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400 font-mono">-₹{{ number_format($commissionDeductions, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Shipping &amp; Fees:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300 font-mono">-₹{{ number_format($shippingDeductions + $otherAuthorizedFees, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Deductions & Authorized Fees Breakdown Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs">
        <div class="px-3 py-1.5 border-r border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Commission Deductions</span>
            <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($commissionDeductions, 2) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500">Admin configured fees</div>
        </div>
        <div class="px-3 py-1.5 border-r border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Shipping Deductions</span>
            <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($shippingDeductions, 2) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500">Logistics &amp; weight diffs</div>
        </div>
        <div class="px-3 py-1.5 border-r border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Tax &amp; Authorized Fees</span>
            <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($otherAuthorizedFees, 2) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500">GST on fee + Gateway fees</div>
        </div>
        <div class="px-3 py-1.5">
            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Refund Adjustments</span>
            <div class="text-sm font-black text-rose-700 dark:text-rose-400 font-mono mt-0.5">₹{{ number_format($refundAdjustments, 2) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500">Reversals on returned items</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PAYMENT HISTORY & FINANCIAL LEDGER                                         -->
    <!-- ========================================================================= -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        
        <!-- Status Tabs Bar -->
        <div class="border-b border-slate-200 dark:border-slate-800 px-4 pt-3 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center space-x-1 sm:space-x-2 text-xs overflow-x-auto pb-[-1px]">
                <a 
                    href="{{ route('seller.payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'all'])) }}"
                    class="px-3.5 py-2.5 font-bold border-b-2 transition whitespace-nowrap {{ $status === 'all' ? 'border-emerald-600 text-emerald-800 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    All Transactions
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $status === 'all' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        {{ $countAll }}
                    </span>
                </a>

                <a 
                    href="{{ route('seller.payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'settled'])) }}"
                    class="px-3.5 py-2.5 font-bold border-b-2 transition whitespace-nowrap {{ $status === 'settled' ? 'border-emerald-600 text-emerald-800 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    Completed Settlements
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $status === 'settled' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        {{ $countSettled }}
                    </span>
                </a>

                <a 
                    href="{{ route('seller.payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'pending'])) }}"
                    class="px-3.5 py-2.5 font-bold border-b-2 transition whitespace-nowrap {{ $status === 'pending' ? 'border-emerald-600 text-emerald-800 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    Pending Settlement
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $status === 'pending' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        {{ $countPending }}
                    </span>
                </a>

                <a 
                    href="{{ route('seller.payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'on_hold'])) }}"
                    class="px-3.5 py-2.5 font-bold border-b-2 transition whitespace-nowrap {{ $status === 'on_hold' ? 'border-emerald-600 text-emerald-800 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    On Hold
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $status === 'on_hold' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        {{ $countOnHold }}
                    </span>
                </a>

                <a 
                    href="{{ route('seller.payments.index', array_merge(request()->except(['page', 'status']), ['status' => 'refunds'])) }}"
                    class="px-3.5 py-2.5 font-bold border-b-2 transition whitespace-nowrap {{ $status === 'refunds' ? 'border-emerald-600 text-emerald-800 dark:text-emerald-400 bg-emerald-50/50 dark:bg-emerald-950/40' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    Refunds &amp; Adjustments
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $status === 'refunds' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        {{ $countRefunds }}
                    </span>
                </a>
            </div>

            <span class="text-xs text-slate-400 dark:text-slate-500 font-semibold py-2">
                Showing {{ $entries->total() }} transaction(s)
            </span>
        </div>

        <!-- Search and Date Filter Controls -->
        <div class="p-4 bg-slate-50/60 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800">
            <form method="GET" action="{{ route('seller.payments.index') }}" class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <input type="hidden" name="status" value="{{ $status }}">
                @if($storeId)
                    <input type="hidden" name="store_id" value="{{ $storeId }}">
                @endif

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search by Order #, Sub-order #, TXN ID, UTR..."
                        class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
                    >
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Date Range Dropdown -->
                <div class="flex items-center space-x-2">
                    <select 
                        name="date_range" 
                        onchange="this.form.submit()" 
                        class="px-3 py-2 text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">All Time</option>
                        <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="7d" {{ $dateRange === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30d" {{ $dateRange === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
                    </select>

                    <button 
                        type="submit" 
                        class="px-3.5 py-2 rounded-lg bg-slate-900 dark:bg-emerald-600 hover:bg-slate-800 dark:hover:bg-emerald-500 text-white font-bold text-xs shadow-2xs transition"
                    >
                        Filter
                    </button>

                    @if($search || $dateRange || $dateFrom || $dateTo || $status !== 'all')
                        <a 
                            href="{{ route('seller.payments.index') }}" 
                            class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold transition"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Payment History Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold text-[10px] uppercase border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Transaction ID</th>
                        <th class="p-3">Order / Sub-Order</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Gross Amount</th>
                        <th class="p-3">Commission</th>
                        <th class="p-3">Shipping Charge</th>
                        <th class="p-3">Tax &amp; Fees</th>
                        <th class="p-3">Refund / Adjustment</th>
                        <th class="p-3">Net Amount</th>
                        <th class="p-3">Payment Date</th>
                        <th class="p-3">Settlement Status</th>
                        <th class="p-3">Reference / UTR</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($entries as $e)
                        @php
                            $settlement = $e->settlementItem?->settlement;
                            $fo = $e->fulfilmentOrder;
                            $order = $fo?->order;
                            $taxAndFees = (float) $e->tax_on_commission + (float) $e->gateway_fee;
                            $isReversal = $e->entry_type === 'reversal';
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <!-- Transaction ID -->
                            <td class="p-3 whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-900 dark:text-white">
                                    TXN-{{ str_pad((string)$e->id, 6, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>

                            <!-- Order / Sub-order -->
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    {{ $order?->order_number ?? 'N/A' }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500">
                                    Sub: {{ $fo?->fulfilment_order_number ?? 'N/A' }}
                                </div>
                            </td>

                            <!-- Transaction Type -->
                            <td class="p-3 whitespace-nowrap">
                                @if($isReversal)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                        Refund Adjustment
                                    </span>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 block font-mono">({{ $e->entry_type }})</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                        Order Settlement
                                    </span>
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 block font-mono">({{ $e->entry_type }})</span>
                                @endif
                            </td>

                            <!-- Gross Amount -->
                            <td class="p-3 whitespace-nowrap font-mono font-bold text-slate-900 dark:text-white">
                                {{ $isReversal ? '-' : '' }}₹{{ number_format((float)$e->base_amount, 2) }}
                            </td>

                            <!-- Commission -->
                            <td class="p-3 whitespace-nowrap font-mono text-rose-600 dark:text-rose-400">
                                {{ $e->commission > 0 ? '-₹' . number_format((float)$e->commission, 2) : '₹0.00' }}
                            </td>

                            <!-- Shipping Charge -->
                            <td class="p-3 whitespace-nowrap font-mono text-slate-600 dark:text-slate-400">
                                {{ $e->shipping_adjustment > 0 ? '-₹' . number_format((float)$e->shipping_adjustment, 2) : '₹0.00' }}
                            </td>

                            <!-- Tax & Applicable Deductions -->
                            <td class="p-3 whitespace-nowrap font-mono text-slate-600 dark:text-slate-400">
                                {{ $taxAndFees > 0 ? '-₹' . number_format($taxAndFees, 2) : '₹0.00' }}
                            </td>

                            <!-- Refund / Adjustment -->
                            <td class="p-3 whitespace-nowrap font-mono text-rose-600 dark:text-rose-400">
                                @if($isReversal)
                                    -₹{{ number_format((float)$e->base_amount, 2) }}
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
                                @endif
                            </td>

                            <!-- Net Amount -->
                            <td class="p-3 whitespace-nowrap font-mono font-extrabold {{ $e->net_payable >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                                ₹{{ number_format((float)$e->net_payable, 2) }}
                            </td>

                            <!-- Payment Date -->
                            <td class="p-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                <div>{{ $e->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $e->created_at->format('h:i A') }}</div>
                            </td>

                            <!-- Settlement Status -->
                            <td class="p-3 whitespace-nowrap">
                                @if($settlement)
                                    @php
                                        $st = $settlement->status;
                                        $badgeClass = match($st) {
                                            \App\Enums\SettlementStatus::PAID, \App\Enums\SettlementStatus::SETTLED => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                            \App\Enums\SettlementStatus::PROCESSING => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
                                            \App\Enums\SettlementStatus::ON_HOLD => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                                            default => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                                        {{ $st->label() }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                        Pending Payout
                                    </span>
                                @endif
                            </td>

                            <!-- Reference / UTR -->
                            <td class="p-3 whitespace-nowrap">
                                @if($settlement && $settlement->reference)
                                    <div class="font-mono text-slate-900 dark:text-white font-bold text-[11px]" title="Bank Wire UTR">
                                        {{ $settlement->reference }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 dark:text-slate-500 font-mono">
                                        Batch #{{ $settlement->settlement_number }}
                                    </div>
                                @elseif($settlement)
                                    <div class="font-mono text-slate-600 dark:text-slate-400 text-[10px]" title="Settlement Batch Number">
                                        Batch #{{ $settlement->settlement_number }}
                                    </div>
                                    <div class="text-[9px] text-amber-600 dark:text-amber-400 font-medium">Pending Wire UTR</div>
                                @else
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Unsettled</span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="p-3 whitespace-nowrap text-right">
                                <button 
                                    type="button" 
                                    @click="openDetails({{ $e->id }})" 
                                    class="px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-[11px] transition"
                                >
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="p-12 text-center">
                                <span class="text-4xl text-slate-300 dark:text-slate-600 block mb-2">💳</span>
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm">No payment records found</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                    No transactions match the selected filters. Delivered customer orders past return window will appear here.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($entries->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $entries->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- TRANSACTION FINANCIAL BREAKDOWN MODAL                                     -->
    <!-- ========================================================================= -->
    <div 
        x-show="modalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="modalOpen = false" 
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl max-w-2xl w-full overflow-hidden text-xs"
        >
            <!-- Modal Header -->
            <div class="p-4 bg-slate-900 dark:bg-slate-800 text-white flex items-center justify-between border-b border-slate-800">
                <div>
                    <h3 class="font-bold text-sm text-slate-100 flex items-center space-x-2">
                        <span>Transaction Financial Breakdown</span>
                        <template x-if="txn">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/20 text-emerald-300 border border-emerald-500/30" x-text="txn.transaction_id"></span>
                        </template>
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Auditable marketplace earnings and deduction breakdown</p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white font-bold text-lg leading-none">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
                <!-- Loading State -->
                <div x-show="loadingDetails" class="py-12 text-center text-slate-500 dark:text-slate-400">
                    <span class="animate-spin inline-block text-2xl mb-2">↻</span>
                    <div class="font-semibold text-xs">Loading transaction breakdown...</div>
                </div>

                <!-- Error State -->
                <div x-show="errorMsg" class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs">
                    <span x-text="errorMsg"></span>
                </div>

                <!-- Loaded Details -->
                <template x-if="txn && !loadingDetails">
                    <div class="space-y-4">
                        
                        <!-- Order & Sub-order Info Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-[11px]">
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">Order #</span>
                                <div class="font-bold text-slate-900 dark:text-white font-mono mt-0.5" x-text="txn.order_number"></div>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">Sub-Order #</span>
                                <div class="font-bold text-slate-900 dark:text-white font-mono mt-0.5" x-text="txn.sub_order_number"></div>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">Store Outlet</span>
                                <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5" x-text="txn.store_name"></div>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase">Date &amp; Time</span>
                                <div class="font-medium text-slate-700 dark:text-slate-300 mt-0.5" x-text="txn.created_at"></div>
                            </div>
                        </div>

                        <!-- Itemized Financial Arithmetic Tree -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="bg-slate-100/70 dark:bg-slate-800/80 px-4 py-2 text-[11px] font-extrabold uppercase text-slate-700 dark:text-slate-300 tracking-wider">
                                Financial Calculation (Actual Admin Rules)
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <!-- Gross Sales -->
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-slate-700 dark:text-slate-300 font-medium">+ Gross Order Base Amount:</span>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + txn.gross_amount.toFixed(2)"></span>
                                </div>

                                <!-- Platform Commission -->
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <div class="text-slate-600 dark:text-slate-400">
                                        <span>- Marketplace Commission</span>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500" x-text="'(' + txn.commission_rate + ' • ' + txn.rule_name + ')'"></span>:
                                    </div>
                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400" x-text="'-₹' + txn.commission.toFixed(2)"></span>
                                </div>

                                <!-- GST on Commission -->
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">- GST on Commission (18%):</span>
                                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400" x-text="'-₹' + txn.tax_on_commission.toFixed(2)"></span>
                                </div>

                                <!-- Gateway Fee -->
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">- Payment Gateway Processing Fee:</span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="'-₹' + txn.gateway_fee.toFixed(2)"></span>
                                </div>

                                <!-- Shipping Adjustment -->
                                <div class="px-4 py-2.5 flex items-center justify-between">
                                    <span class="text-slate-600 dark:text-slate-400">- Logistics / Shipping Charge:</span>
                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="'-₹' + txn.shipping_adjustment.toFixed(2)"></span>
                                </div>

                                <!-- Refund Reversal if applicable -->
                                <template x-if="txn.entry_type === 'reversal'">
                                    <div class="px-4 py-2.5 flex items-center justify-between bg-rose-50/50 dark:bg-rose-950/40">
                                        <span class="text-rose-800 dark:text-rose-300 font-medium">- Customer Refund Adjustment:</span>
                                        <span class="font-mono font-bold text-rose-700 dark:text-rose-400" x-text="'-₹' + txn.refund_deduction.toFixed(2)"></span>
                                    </div>
                                </template>

                                <!-- Net Seller Payable -->
                                <div class="px-4 py-3 bg-emerald-50/60 dark:bg-emerald-950/40 flex items-center justify-between font-black text-sm">
                                    <span class="text-emerald-950 dark:text-emerald-200">= Net Seller Payable:</span>
                                    <span class="font-mono text-emerald-800 dark:text-emerald-300 text-base" x-text="'₹' + txn.net_payable.toFixed(2)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Settlement Batch & Bank UTR Information -->
                        <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 space-y-1.5 text-xs">
                            <div class="font-bold text-slate-900 dark:text-white text-xs">Bank Wire &amp; Settlement Batch</div>
                            <template x-if="txn.settlement">
                                <div class="grid grid-cols-2 gap-3 text-[11px] pt-1">
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500">Batch Number:</span>
                                        <div class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="txn.settlement.number"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500">Status:</span>
                                        <div class="font-bold text-emerald-700 dark:text-emerald-400" x-text="txn.settlement.status_label"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500">Bank Wire Reference (UTR):</span>
                                        <div class="font-mono font-bold text-slate-900 dark:text-white" x-text="txn.settlement.reference"></div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500">Disbursed Date:</span>
                                        <div class="font-medium text-slate-700 dark:text-slate-300" x-text="txn.settlement.paid_at || 'Pending Disbursal'"></div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!txn.settlement">
                                <div class="text-[11px] text-amber-700 dark:text-amber-400 italic">
                                    This transaction has not yet been rolled into a settlement batch. Delivered orders past the {{ $returnWindowDays }}-day return window are batched automatically.
                                </div>
                            </template>
                        </div>

                        <!-- Product Items in Order -->
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <div class="bg-slate-100/70 dark:bg-slate-800/80 px-4 py-2 text-[10px] font-extrabold uppercase text-slate-600 dark:text-slate-300 tracking-wider">
                                Order Package Items
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                <template x-for="item in txn.items" :key="item.sku">
                                    <div class="p-3 flex items-center justify-between">
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white" x-text="item.title"></div>
                                            <div class="text-[10px] font-mono text-slate-400 dark:text-slate-500" x-text="'SKU: ' + item.sku + ' • Qty: ' + item.quantity"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + item.subtotal.toFixed(2)"></div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono" x-text="'@ ₹' + item.unit_price.toFixed(2)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 text-right">
                <button 
                    type="button" 
                    @click="modalOpen = false" 
                    class="px-4 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition shadow-2xs"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
