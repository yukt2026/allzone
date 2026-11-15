@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Merchant Wallet & Payouts'])

@section('content')
<div class="space-y-6 max-w-6xl mx-auto" x-data="{ withdrawModal: false }">
    <!-- Page Header -->
    <x-page-header 
        title="Merchant Wallet &amp; Payouts" 
        subtitle="Manage available matured payout balances, track orders in escrow, and initiate disbursement withdrawals."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Wallet & Withdrawals' => '#']"
    >
        <x-slot name="actions">
            @if($availableBalance > 0)
                <button 
                    type="button" 
                    @click="withdrawModal = true"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span>Request Payout</span>
                </button>
            @endif
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- KPI Balance Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Available to Withdraw</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ format_currency($availableBalance) }}
            </div>
            <p class="text-[11px] text-slate-400">Delivered orders matured past {{ $returnWindowDays }}-day return period.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">In Escrow (Hold)</div>
            <div class="text-2xl font-black text-amber-500">
                {{ format_currency($escrowBalance) }}
            </div>
            <p class="text-[11px] text-slate-400">Delivered orders currently inside customer return window.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pending Admin Payouts</div>
            <div class="text-2xl font-black text-sky-600 dark:text-sky-400">
                {{ format_currency($pendingPayouts) }}
            </div>
            <p class="text-[11px] text-slate-400">Withdrawals approved or undergoing bank NEFT/IMPS transfer.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs space-y-2">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Lifetime Settled</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ format_currency($lifetimeSettled) }}
            </div>
            <p class="text-[11px] text-slate-400">Total cumulative earnings successfully transferred to bank.</p>
        </div>
    </div>

    <!-- Bank Account Info Banner -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xl font-black">
                🏦
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Disbursement Bank Account</div>
                @if($primaryBank)
                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                        {{ $primaryBank->bank_name ?: 'Registered Bank' }} &bull; A/C: ••••••{{ substr((string)$primaryBank->account_no, -4) }}
                    </div>
                    <div class="text-[11px] text-slate-400 font-mono">
                        IFSC: {{ $primaryBank->ifsc }} &bull; {{ $primaryBank->branch_name }}
                    </div>
                @else
                    <div class="text-sm font-bold text-rose-500 mt-0.5">No primary bank account registered</div>
                @endif
            </div>
        </div>
        <div>
            <a 
                href="{{ route('seller.profile') }}" 
                class="px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition"
            >
                View Banking Details &rarr;
            </a>
        </div>
    </div>

    <!-- Withdrawals History Ledger -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Withdrawals &amp; Settlements History</h3>
                <p class="text-xs text-slate-500 mt-0.5">Official platform payout batches and direct bank transfers.</p>
            </div>
            <div>
                <a href="{{ route('seller.settlements.index') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                    View Complete Statements &rarr;
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-800 text-[10px]">
                    <tr>
                        <th class="py-3 px-6">Batch Reference</th>
                        <th class="py-3 px-6">Billing Window</th>
                        <th class="py-3 px-6">Packages</th>
                        <th class="py-3 px-6">Gross Sales</th>
                        <th class="py-3 px-6">Net Payout</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6">Bank Ref (UTR)</th>
                        <th class="py-3 px-6 text-right">Statement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-300">
                    @forelse($settlements as $s)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $s->settlement_number }}
                            </td>
                            <td class="py-4 px-6 text-slate-500 text-[11px]">
                                {{ $s->period_start?->format('d M') }} &ndash; {{ $s->period_end?->format('d M Y') }}
                            </td>
                            <td class="py-4 px-6">
                                {{ $s->items_count }} orders
                            </td>
                            <td class="py-4 px-6 font-semibold">
                                {{ format_currency((float)$s->gross) }}
                            </td>
                            <td class="py-4 px-6 font-black text-emerald-600 dark:text-emerald-400">
                                {{ format_currency((float)$s->net) }}
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $st = is_object($s->status) ? $s->status->value : (string)$s->status;
                                @endphp
                                @if(in_array($st, ['paid', 'settled']))
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400">Paid Out</span>
                                @elseif($st === 'processing')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-400">Processing</span>
                                @elseif($st === 'on_hold')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-400">On Hold</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400">Pending Review</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-mono text-[11px] text-slate-500">
                                {{ $s->reference ?: 'Pending Transfer' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a 
                                    href="{{ route('seller.settlements.show', $s) }}" 
                                    class="text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-emerald-600"
                                >
                                    Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 italic">
                                No withdrawal settlements generated yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($settlements->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>

    <!-- Payout Request Modal -->
    <div 
        x-show="withdrawModal" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
        <div 
            @click.away="withdrawModal = false"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Request Early Withdrawal</h3>
                <button type="button" @click="withdrawModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <div class="space-y-3">
                <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-800/40">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Available Matured Balance</div>
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                        {{ format_currency($availableBalance) }}
                    </div>
                </div>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Withdrawal requests are processed into your verified bank account on file. A settlement batch will be compiled for your delivered and matured consignments.
                </p>

                <div class="text-xs p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl space-y-1 text-slate-600 dark:text-slate-300">
                    <div>Bank: <strong>{{ $primaryBank?->bank_name ?: 'N/A' }}</strong></div>
                    <div>Account: <strong class="font-mono">••••••{{ substr((string)$primaryBank?->account_no, -4) }}</strong></div>
                    <div>IFSC: <strong class="font-mono">{{ $primaryBank?->ifsc ?: 'N/A' }}</strong></div>
                </div>
            </div>

            <form method="POST" action="{{ route('seller.wallet.withdraw') }}" class="pt-2">
                @csrf
                <div class="flex items-center justify-end space-x-2">
                    <button 
                        type="button" 
                        @click="withdrawModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        Confirm &amp; Request Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
