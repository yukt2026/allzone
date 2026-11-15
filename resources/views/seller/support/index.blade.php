@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Seller Support Desk'])

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Page Header -->
    <x-page-header 
        title="Seller Support Desk" 
        subtitle="Submit queries, resolve settlement discrepancies, and communicate directly with ALLZONE marketplace operations."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Support Desk' => '#']"
    >
        <x-slot name="actions">
            <a 
                href="{{ route('seller.support.create') }}" 
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Support Ticket</span>
            </a>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Inquiries</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Open &amp; In Progress</div>
            <div class="text-2xl font-black text-amber-500 mt-1">{{ $metrics['open'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Resolved &amp; Closed</div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['resolved'] }}</div>
        </div>
    </div>

    <!-- Tickets List Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <a href="{{ route('seller.support.index') }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ !request('status') || request('status') === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    All
                </a>
                <a href="{{ route('seller.support.index', ['status' => 'open']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('status') === 'open' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    Open
                </a>
                <a href="{{ route('seller.support.index', ['status' => 'in_progress']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('status') === 'in_progress' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    In Progress
                </a>
                <a href="{{ route('seller.support.index', ['status' => 'resolved']) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold {{ request('status') === 'resolved' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                    Resolved
                </a>
            </div>

            <form method="GET" action="{{ route('seller.support.index') }}" class="flex items-center space-x-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search ticket # or subject..." 
                    class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-1.5 w-56"
                >
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-100 dark:border-slate-800 text-[10px]">
                    <tr>
                        <th class="py-3 px-6">Ticket Reference</th>
                        <th class="py-3 px-6">Subject</th>
                        <th class="py-3 px-6">Category</th>
                        <th class="py-3 px-6">Priority</th>
                        <th class="py-3 px-6">Status</th>
                        <th class="py-3 px-6">Updated</th>
                        <th class="py-3 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-300">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $ticket->ticket_number }}
                            </td>
                            <td class="py-4 px-6">
                                <a href="{{ route('seller.support.show', $ticket) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 transition">
                                    {{ $ticket->subject }}
                                </a>
                                @if($ticket->order)
                                    <div class="text-[11px] text-slate-400">Order: #{{ $ticket->order->order_number }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6 capitalize">
                                {{ $ticket->category }}
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $prio = is_object($ticket->priority) ? $ticket->priority->value : (string)$ticket->priority;
                                @endphp
                                @if($prio === 'urgent')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400">Urgent</span>
                                @elseif($prio === 'high')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">High</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ ucfirst($prio) }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $st = is_object($ticket->status) ? $ticket->status->value : (string)$ticket->status;
                                @endphp
                                @if(in_array($st, ['resolved', 'closed']))
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400">Resolved</span>
                                @elseif($st === 'in_progress')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-400">In Progress</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400">Open</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-[11px] text-slate-400">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a 
                                    href="{{ route('seller.support.show', $ticket) }}" 
                                    class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    Conversation &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                No support tickets found. Click "New Support Ticket" to start an inquiry.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
