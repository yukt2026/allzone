@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Payments & Financial Management'])

@section('content')
<div class="space-y-6" x-data="{
    webhookPayload: null,
    activeTab: '{{ $tab }}',
    settlementToPay: null,
    settlementToFail: null,
    openWebhookModal(log) {
        this.webhookPayload = log.payload;
        $dispatch('open-modal', 'webhook-payload-modal');
    },
    openPayModal(id, num, net) {
        this.settlementToPay = { id: id, num: num, net: net };
        $dispatch('open-modal', 'settlement-pay-modal');
    },
    openFailModal(id, num) {
        this.settlementToFail = { id: id, num: num };
        $dispatch('open-modal', 'settlement-fail-modal');
    }
}">

    <!-- Page Header -->
    <x-page-header 
        title="Payments & Transactions Ledger" 
        subtitle="Centralized Financial Dashboard, gateway transaction records, Razorpay references, doorstep COD remittances, seller payables, and settlement workflows."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Financial Management' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                {{ format_currency($metrics['total_volume']) }} Total Volume
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.payments.refunds') }}" class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold flex items-center space-x-1.5 transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Refunds Audit</span>
                </a>
                <a href="{{ route('admin.payments.export_financials') }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 transition shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export Financials</span>
                </a>
                <a href="{{ route('admin.payments.export', request()->query()) }}" class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold flex items-center space-x-1.5 transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- 8 High-Impact Financial KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3.5">
        <!-- 1. Captured Volume -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Captured Volume</div>
            <div class="text-base sm:text-lg font-black font-mono text-emerald-600 dark:text-emerald-400 truncate">{{ format_currency($metrics['total_volume']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['online_count']) }} online</div>
        </div>

        <!-- 2. Doorstep COD Collections -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-1">Doorstep COD</div>
            <div class="text-base sm:text-lg font-black font-mono text-amber-500 truncate">{{ format_currency($metrics['cod_collected_doorstep']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ format_currency($metrics['cod_unremitted_amount']) }} unremitted</div>
        </div>

        <!-- 3. Net Platform Revenue -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Platform Revenue</div>
            <div class="text-base sm:text-lg font-black font-mono text-slate-900 dark:text-white truncate">{{ format_currency($metrics['net_platform_revenue']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Comm + 18% GST</div>
        </div>

        <!-- 4. Unsettled Seller Payable -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-sky-500 uppercase tracking-wider mb-1">Seller Payable</div>
            <div class="text-base sm:text-lg font-black font-mono text-sky-600 dark:text-sky-400 truncate">{{ format_currency($metrics['unsettled_seller_payable']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Awaiting payout</div>
        </div>

        <!-- 5. Settled Payouts -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Settled Payouts</div>
            <div class="text-base sm:text-lg font-black font-mono text-slate-900 dark:text-white truncate">{{ format_currency($metrics['settled_seller_payable']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Disbursed with UTR</div>
        </div>

        <!-- 6. Total Refunds -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1">Processed Refunds</div>
            <div class="text-base sm:text-lg font-black font-mono text-rose-600 dark:text-rose-400 truncate">{{ format_currency($metrics['total_refunds_amount']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['total_refunds_count']) }} orders</div>
        </div>

        <!-- 7. Reconciliation Status -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reconciliation</div>
            <div class="text-base sm:text-lg font-black font-mono text-emerald-600 dark:text-emerald-400">{{ number_format($metrics['reconciled_count']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['unreconciled_count']) }} pending</div>
        </div>

        <!-- 8. Webhook Health -->
        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Webhook Health</div>
            <div class="text-base sm:text-lg font-black font-mono text-emerald-600 dark:text-emerald-400">{{ $metrics['webhook_success_rate'] }}%</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ number_format($metrics['webhook_total']) }} events</div>
        </div>
    </div>

    <!-- Top-Level Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-2 py-1 overflow-x-auto" aria-label="Financial Console Navigation Tabs">
            <a 
                href="{{ route('admin.payments.index', ['tab' => 'payments']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'payments' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>💳</span>
                <span>Customer Payments</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $tab === 'payments' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                    {{ number_format($payments ? $payments->total() : 0) }}
                </span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'cod']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'cod' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>📦</span>
                <span>Doorstep COD Remittances</span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'refunds']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'refunds' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>🔄</span>
                <span>Refunds Audit Ledger</span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'webhooks']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'webhooks' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>⚡</span>
                <span>Razorpay Webhook Events</span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'sellers']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'sellers' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>🏪</span>
                <span>Seller Financials</span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'settlements']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'settlements' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>🏦</span>
                <span>Settlement Management</span>
            </a>

            <a 
                href="{{ route('admin.payments.index', ['tab' => 'reports']) }}" 
                class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $tab === 'reports' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
            >
                <span>📊</span>
                <span>Financial Analytics &amp; Reports</span>
            </a>
        </nav>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: CUSTOMER PAYMENTS & TRANSACTIONS                        -->
    <!-- ============================================================== -->
    @if($tab === 'payments')
        <div class="space-y-4">
            <!-- Filter Bar -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <input type="hidden" name="tab" value="payments">

                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="Order #, pay_ID, customer, email..." 
                            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                    </div>

                    <!-- Gateway / Method -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Gateway</label>
                        <select name="gateway" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="all">All Gateways</option>
                            <option value="razorpay" {{ request('gateway') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                            <option value="cod" {{ request('gateway') === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Payment Status</label>
                        <select name="status" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="all">All Statuses</option>
                            @foreach(\App\Enums\PaymentStatus::cases() as $st)
                                <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Reconciliation Status -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Reconciliation</label>
                        <select name="reconciliation" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="all">All</option>
                            @foreach(\App\Enums\ReconciliationStatus::cases() as $rc)
                                <option value="{{ $rc->value }}" {{ request('reconciliation') === $rc->value ? 'selected' : '' }}>
                                    {{ $rc->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit & Clear -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Filter
                        </button>
                        <a href="{{ route('admin.payments.index', ['tab' => 'payments']) }}" class="px-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-200 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Payment ID</th>
                                <th class="px-6 py-3.5">Parent Order #</th>
                                <th class="px-6 py-3.5">Customer</th>
                                <th class="px-6 py-3.5">Razorpay / Gateway Ref</th>
                                <th class="px-6 py-3.5 text-right">Amount</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Reconciliation</th>
                                <th class="px-6 py-3.5">Date</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($payments as $p)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                    <a href="{{ route('admin.payments.show', $p) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400">
                                        #{{ $p->id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-mono">
                                    @if($p->order)
                                        <a href="{{ route('admin.orders.show', $p->order) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-bold">
                                            {{ $p->order->order_number }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $p->order?->customer?->user?->name ?? 'Customer' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $p->order?->customer?->user?->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px]">
                                    @if($p->gateway_payment_id)
                                        <div class="text-slate-900 dark:text-white font-semibold select-all">{{ $p->gateway_payment_id }}</div>
                                        <div class="text-[10px] text-slate-400">Order: {{ $p->gateway_order_id ?? 'N/A' }}</div>
                                    @elseif($p->method === \App\Enums\PaymentMethod::COD)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                            COD Doorstep
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-sans italic text-[11px]">Not initiated</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-black text-sm text-slate-900 dark:text-white">
                                    {{ format_currency((float)$p->amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $p->status instanceof \App\Enums\PaymentStatus ? $p->status->badgeClass() : 'bg-slate-100 text-slate-800 border-slate-300' }}">
                                        {{ $p->status instanceof \App\Enums\PaymentStatus ? $p->status->label() : (string)$p->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $p->reconciliation_status instanceof \App\Enums\ReconciliationStatus ? $p->reconciliation_status->badgeClass() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $p->reconciliation_status instanceof \App\Enums\ReconciliationStatus ? $p->reconciliation_status->label() : ($p->reconciliation_status ?? 'Pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    {{ $p->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                    @if($p->reconciliation_status !== \App\Enums\ReconciliationStatus::RECONCILED && $p->status === \App\Enums\PaymentStatus::PAID)
                                        <form method="POST" action="{{ route('admin.payments.reconcile', $p) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold hover:bg-emerald-100 transition" title="Mark Reconciled">
                                                Reconcile
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.payments.show', $p) }}" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition">
                                        Details &rarr;
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    No customer payment transactions match the selected filters.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 p-4">
                    <x-pagination :paginator="$payments" />
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 2: DOORSTEP COD ORDERS & REMITTANCES                      -->
    <!-- ============================================================== -->
    @if($tab === 'cod')
        <div class="space-y-4">
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl p-4 flex items-start space-x-3 text-xs text-amber-800 dark:text-amber-300">
                <span class="text-base">💡</span>
                <div>
                    <span class="font-bold">COD Settlement Rules:</span>
                    Doorstep Cash on Delivery payments collected by couriers are reconciled against shipment delivery receipts. During seller batch payout calculation, unremitted COD amounts already collected are automatically reconciled and deducted.
                </div>
            </div>

            <!-- COD Remittances Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Remittance ID</th>
                                <th class="px-6 py-3.5">Seller Business</th>
                                <th class="px-6 py-3.5">Shipment &amp; AWB</th>
                                <th class="px-6 py-3.5">Order Ref</th>
                                <th class="px-6 py-3.5 text-right">Collected Amount</th>
                                <th class="px-6 py-3.5">Remittance Status</th>
                                <th class="px-6 py-3.5">Doorstep Collected At</th>
                                <th class="px-6 py-3.5">Remitted At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($codRemittances as $cr)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $cr->id }}
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $cr->seller?->business_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px]">
                                    @if($cr->shipment)
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $cr->shipment->awb ?: 'AWB Pending' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $cr->shipment->courier ?? 'Courier' }}</div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                    {{ $cr->reference ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-black text-sm text-slate-900 dark:text-white">
                                    {{ format_currency((float)$cr->amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $cr->status === \App\Enums\CODRemittanceStatus::REMITTED ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200' }}">
                                        {{ $cr->status instanceof \App\Enums\CODRemittanceStatus ? $cr->status->label() : (string)$cr->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-[11px]">
                                    {{ $cr->collected_at ? $cr->collected_at->format('d M Y, h:i A') : 'Pending Doorstep Receipt' }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-[11px]">
                                    {{ $cr->remitted_at ? $cr->remitted_at->format('d M Y, h:i A') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    No COD remittance transactions recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 p-4">
                    <x-pagination :paginator="$codRemittances" />
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 3: REFUNDS AUDIT & PROCESSING                             -->
    <!-- ============================================================== -->
    @if($tab === 'refunds')
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Refund ID</th>
                                <th class="px-6 py-3.5">Payment ID</th>
                                <th class="px-6 py-3.5">Order #</th>
                                <th class="px-6 py-3.5">Customer</th>
                                <th class="px-6 py-3.5">Razorpay Refund ID</th>
                                <th class="px-6 py-3.5">Reason</th>
                                <th class="px-6 py-3.5 text-right">Amount</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Processed At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($refunds as $rf)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $rf->id }}
                                </td>
                                <td class="px-6 py-4 font-mono">
                                    <a href="{{ route('admin.payments.show', $rf->payment_id) }}" class="text-emerald-600 hover:underline">
                                        #{{ $rf->payment_id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-900 dark:text-white font-semibold">
                                    {{ $rf->payment?->order?->order_number ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $rf->payment?->order?->customer?->user?->name ?? 'Customer' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] select-all text-slate-700 dark:text-slate-300">
                                    {{ $rf->gateway_refund_id ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 max-w-xs truncate">
                                    {{ $rf->reason }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-black text-sm text-rose-600 dark:text-rose-400">
                                    {{ format_currency((float)$rf->amount) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $rf->status instanceof \App\Enums\RefundStatus ? $rf->status->badgeClass() : 'bg-slate-100 text-slate-800' }}">
                                        {{ $rf->status instanceof \App\Enums\RefundStatus ? $rf->status->label() : (string)$rf->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-[11px]">
                                    {{ $rf->processed_at ? $rf->processed_at->format('d M Y, h:i A') : $rf->created_at->format('d M Y, h:i A') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    No refund transactions recorded.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 p-4">
                    <x-pagination :paginator="$refunds" />
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 4: RAZORPAY WEBHOOK EVENTS                                -->
    <!-- ============================================================== -->
    @if($tab === 'webhooks')
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Log ID</th>
                                <th class="px-6 py-3.5">Gateway</th>
                                <th class="px-6 py-3.5">Event Type</th>
                                <th class="px-6 py-3.5">Razorpay Order ID</th>
                                <th class="px-6 py-3.5">Razorpay Payment ID</th>
                                <th class="px-6 py-3.5">Signature</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Received At</th>
                                <th class="px-6 py-3.5 text-right">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($webhookLogs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                    #{{ $log->id }}
                                </td>
                                <td class="px-6 py-4 font-bold uppercase text-slate-700 dark:text-slate-300">
                                    {{ $log->gateway }}
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $log->event }}
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-slate-600 dark:text-slate-400 select-all">
                                    {{ $log->gateway_order_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-slate-900 dark:text-white font-semibold select-all">
                                    {{ $log->gateway_payment_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $log->is_valid_signature ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $log->is_valid_signature ? 'Verified' : 'Invalid' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $log->status === 'processed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        type="button" 
                                        @click="openWebhookModal({{ json_encode($log) }})"
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition"
                                    >
                                        Inspect JSON
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    No incoming Razorpay webhook events logged yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 p-4">
                    <x-pagination :paginator="$webhookLogs" />
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 5: SELLER FINANCIALS & OUTSTANDING BALANCES               -->
    <!-- ============================================================== -->
    @if($tab === 'sellers')
        <div class="space-y-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Seller</th>
                                <th class="px-6 py-3.5 text-right">Gross Sales</th>
                                <th class="px-6 py-3.5 text-right">Commission</th>
                                <th class="px-6 py-3.5 text-right">GST (18%)</th>
                                <th class="px-6 py-3.5 text-right">Refund Deductions</th>
                                <th class="px-6 py-3.5 text-right">Net Payable Total</th>
                                <th class="px-6 py-3.5 text-right">Settled (Paid)</th>
                                <th class="px-6 py-3.5 text-right">Pending Payout</th>
                                <th class="px-6 py-3.5 text-right">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($sellerFinancials as $sf)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $sf['seller']->business_name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $sf['seller']->legal_name }} &bull; Seller #{{ $sf['seller']->id }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                    {{ format_currency($sf['gross_sales']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                    -{{ format_currency($sf['commission_deducted']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-500">
                                    -{{ format_currency($sf['tax_deducted']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-rose-500">
                                    -{{ format_currency($sf['refund_adjustments']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ format_currency($sf['net_payable_total']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-emerald-600 font-semibold">
                                    {{ format_currency($sf['paid_settlements']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-amber-500 font-semibold">
                                    {{ format_currency($sf['pending_settlements']) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-black text-sm text-sky-600 dark:text-sky-400">
                                    {{ format_currency($sf['outstanding_balance']) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    No seller financial records available.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 6: SETTLEMENT MANAGEMENT & PAYOUTS                        -->
    <!-- ============================================================== -->
    @if($tab === 'settlements')
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Seller Settlement Batches</h3>
                    <p class="text-xs text-slate-500">Weekly and scheduled payout cycles for eligible delivered orders past the return window.</p>
                </div>
                <form method="POST" action="{{ route('admin.settlements.generate') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        ⚡ Generate Settlement Batch
                    </button>
                </form>
            </div>

            <!-- Settlements Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Settlement #</th>
                                <th class="px-6 py-3.5">Seller</th>
                                <th class="px-6 py-3.5">Cycle Period</th>
                                <th class="px-6 py-3.5 text-right">Gross Sales</th>
                                <th class="px-6 py-3.5 text-right">Commission &amp; Tax</th>
                                <th class="px-6 py-3.5 text-right">COD Collected</th>
                                <th class="px-6 py-3.5 text-right">Net Payable</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Bank UTR / Ref</th>
                                <th class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($settlements as $st)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                    <a href="{{ route('admin.settlements.show', $st) }}" class="text-emerald-600 hover:underline">
                                        {{ $st->settlement_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $st->seller?->business_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                    {{ $st->period_start?->format('d M') }} &ndash; {{ $st->period_end?->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                    {{ format_currency((float)$st->gross) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                    -{{ format_currency((float)$st->commission + (float)$st->tax_on_commission) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-amber-500">
                                    -{{ format_currency((float)$st->cod_collected) }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-black text-sm text-slate-900 dark:text-white">
                                    {{ format_currency((float)$st->net) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $st->status instanceof \App\Enums\SettlementStatus ? $st->status->badgeClass() : 'bg-slate-100 text-slate-800' }}">
                                        {{ $st->status instanceof \App\Enums\SettlementStatus ? $st->status->label() : (string)$st->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] select-all text-slate-700 dark:text-slate-300">
                                    {{ $st->reference ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                    @if($st->status === \App\Enums\SettlementStatus::PENDING)
                                        <form method="POST" action="{{ route('admin.settlements.approve', $st) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300 border border-sky-200 dark:border-sky-800 rounded-lg text-xs font-bold hover:bg-sky-100 transition">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if(in_array($st->status, [\App\Enums\SettlementStatus::PENDING, \App\Enums\SettlementStatus::PROCESSING, \App\Enums\SettlementStatus::APPROVED]))
                                        <button 
                                            type="button" 
                                            @click="openPayModal({{ $st->id }}, '{{ $st->settlement_number }}', '{{ format_currency((float)$st->net) }}')"
                                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-xs"
                                        >
                                            Mark Paid
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="openFailModal({{ $st->id }}, '{{ $st->settlement_number }}')"
                                            class="px-2 py-1.5 bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800 rounded-lg text-xs font-bold hover:bg-rose-100 transition"
                                        >
                                            Fail
                                        </button>
                                    @endif

                                    <a href="{{ route('admin.settlements.show', $st) }}" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition">
                                        View &rarr;
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                                    No settlement batches found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800 p-4">
                    <x-pagination :paginator="$settlements" />
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- TAB 7: FINANCIAL ANALYTICS & REPORTS                          -->
    <!-- ============================================================== -->
    @if($tab === 'reports')
        <div class="space-y-6">
            <!-- Daily Collection Breakdown (Last 14 Days) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Daily Collection Overview (Last 14 Days)</h3>
                        <p class="text-xs text-slate-500">Daily gross captured transaction volumes and payment success frequency.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3">
                    @for($i = 13; $i >= 0; $i--)
                        @php
                            $dStr = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
                            $dayData = $metrics['daily_trend'][$dStr] ?? null;
                            $amt = $dayData ? (float) $dayData->total : 0.00;
                            $cnt = $dayData ? (int) $dayData->count : 0;
                        @endphp
                        <div class="p-3 rounded-2xl border {{ $amt > 0 ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800' }} text-center">
                            <div class="text-[10px] font-bold text-slate-500 font-mono">{{ \Carbon\Carbon::parse($dStr)->format('d M, D') }}</div>
                            <div class="text-xs font-black font-mono {{ $amt > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }} mt-1">
                                {{ format_currency($amt) }}
                            </div>
                            <div class="text-[9px] text-slate-400">{{ $cnt }} payments</div>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Monthly Collections Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Monthly Collection Summary (Past 12 Months)</h3>
                        <p class="text-xs text-slate-500">Aggregated collection figures by calendar month.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="px-6 py-3">Calendar Month</th>
                                <th class="px-6 py-3 text-right">Transactions Count</th>
                                <th class="px-6 py-3 text-right">Total Captured Volume</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($metrics['monthly_collections'] as $m)
                            <tr>
                                <td class="px-6 py-3.5 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $m->month)->format('F Y') }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ number_format($m->count) }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    {{ format_currency((float)$m->total) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                    No historical monthly data recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 1: Inspect Razorpay Webhook Payload JSON -->
    <x-modal name="webhook-payload-modal" maxWidth="2xl">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Incoming Razorpay Webhook Payload</h3>
                <button type="button" @click="$dispatch('close-modal', 'webhook-payload-modal')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <div class="bg-slate-950 text-emerald-400 font-mono text-xs rounded-xl p-4 max-h-96 overflow-y-auto">
                <pre x-text="JSON.stringify(webhookPayload, null, 2)"></pre>
            </div>
            <div class="flex justify-end">
                <button type="button" @click="$dispatch('close-modal', 'webhook-payload-modal')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                    Close
                </button>
            </div>
        </div>
    </x-modal>

    <!-- MODAL 2: Mark Settlement Paid with Bank UTR -->
    <x-modal name="settlement-pay-modal" maxWidth="md">
        <form method="POST" :action="'/admin/settlements/' + (settlementToPay ? settlementToPay.id : '') + '/pay'" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Bank Payout</h3>
                <button type="button" @click="$dispatch('close-modal', 'settlement-pay-modal')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <div class="text-xs text-slate-600 dark:text-slate-400">
                Settlement <strong class="font-mono text-slate-900 dark:text-white" x-text="settlementToPay ? settlementToPay.num : ''"></strong> 
                for net amount <strong class="font-mono text-emerald-600" x-text="settlementToPay ? settlementToPay.net : ''"></strong>.
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Bank UTR / Transaction Reference Number <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="reference" 
                    required 
                    placeholder="e.g. UTR123456789012" 
                    class="w-full text-xs font-mono font-bold p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                >
                <p class="text-[10px] text-slate-400 mt-1">Never fabricate references. Enter the actual UTR from your bank's NEFT/RTGS/IMPS statement.</p>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" @click="$dispatch('close-modal', 'settlement-pay-modal')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Confirm Payout &amp; Mark Paid
                </button>
            </div>
        </form>
    </x-modal>

    <!-- MODAL 3: Mark Settlement Failed -->
    <x-modal name="settlement-fail-modal" maxWidth="md">
        <form method="POST" :action="'/admin/settlements/' + (settlementToFail ? settlementToFail.id : '') + '/fail'" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-rose-600 dark:text-rose-400">Record Payout Failure</h3>
                <button type="button" @click="$dispatch('close-modal', 'settlement-fail-modal')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Failure Reason / Bank Return Code <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="reason" 
                    required 
                    rows="3" 
                    placeholder="e.g. Beneficiary account closed / IFSC mismatch reported by bank."
                    class="w-full text-xs p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                ></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" @click="$dispatch('close-modal', 'settlement-fail-modal')" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Record Payout Failure
                </button>
            </div>
        </form>
    </x-modal>

</div>
@endsection
