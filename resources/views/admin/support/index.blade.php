@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Support Desk'])

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <x-page-header 
        title="Customer & Seller Support Tickets" 
        subtitle="Centralized communication, seller dispute desk, response-time reporting, and resolution threads."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Support Desk' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                {{ $metrics['open_tickets'] }} Open Inquiries
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('admin.support.categories.index') }}" 
                    class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition shadow-xs"
                >
                    Help Topics
                </a>
                <a 
                    href="{{ route('admin.support.export', request()->query()) }}" 
                    class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 transition shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Response-Time & Operational KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Inquiries</span>
            <div class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ number_format($metrics['total_tickets']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Across all channels</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-rose-500">
            <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Open Inquiries</span>
            <div class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400 mt-1">{{ number_format($metrics['open_tickets']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Awaiting staff review</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-indigo-500">
            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Avg Response Time</span>
            <div class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $metrics['avg_first_response'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">First touch speed</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-emerald-500">
            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Avg Resolution</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['avg_resolution_time'] }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ number_format($metrics['resolved']) }} resolved tickets</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-amber-500">
            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Waiting on User</span>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ number_format($metrics['waiting_customer']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Info requested</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-purple-500">
            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Escalated</span>
            <div class="text-2xl font-black font-mono text-purple-600 dark:text-purple-400 mt-1">{{ number_format($metrics['escalated']) }}</div>
            <div class="text-[11px] text-rose-500 font-bold mt-0.5">{{ $metrics['sla_breached'] }} SLA Breaches</div>
        </div>
    </div>

    <!-- Status Tabs -->
    @php
        $currentStatus = request('status', 'all');
    @endphp
    <div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 dark:border-slate-800 pb-2">
        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>All Tickets</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'all' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">{{ $metrics['total_tickets'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'open'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'open' ? 'bg-rose-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>Open</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'open' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700' }}">{{ $metrics['open_tickets'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'in_progress'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'in_progress' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>In Progress</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'in_progress' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' }}">{{ $metrics['in_progress'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'waiting_customer'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'waiting_customer' ? 'bg-sky-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>Waiting for Response</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'waiting_customer' ? 'bg-white/20 text-white' : 'bg-sky-100 text-sky-700' }}">{{ $metrics['waiting_customer'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'resolved'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>Resolved</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'resolved' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700' }}">{{ $metrics['resolved'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'closed'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'closed' ? 'bg-slate-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}"
        >
            <span>Closed</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'closed' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $metrics['closed'] }}</span>
        </a>

        <a 
            href="{{ route('admin.support.index', array_merge(request()->except('status', 'page'), ['status' => 'escalated'])) }}" 
            class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap {{ $currentStatus === 'escalated' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}"
        >
            <span>🚨 Escalated</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === 'escalated' ? 'bg-white/20 text-white' : 'bg-purple-200 text-purple-800' }}">{{ $metrics['escalated'] }}</span>
        </a>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.support.index') }}" class="flex flex-wrap items-center gap-2.5 w-full">
            <input type="hidden" name="status" value="{{ $currentStatus }}">

            <div class="relative flex-1 min-w-[220px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search ticket #, subject, customer, or seller business..." 
                    class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white pl-8 pr-3 py-2.5 focus:ring-1 focus:ring-slate-900"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="priority" class="text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-2.5 px-3">
                <option value="all">All Priorities</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
            </select>

            <select name="assigned_to" class="text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-2.5 px-3">
                <option value="all">All Assignees</option>
                <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned Only</option>
                @foreach($staff as $st)
                    <option value="{{ $st->id }}" {{ request('assigned_to') == $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold shadow-xs transition">
                Filter
            </button>
            <a href="{{ route('admin.support.index') }}" class="px-3 py-2.5 text-xs text-slate-500 hover:text-slate-800 font-semibold">
                Reset
            </a>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                    <tr>
                        <th class="px-6 py-3.5">Ticket # &amp; Subject</th>
                        <th class="px-6 py-3.5">User / Merchant Initiator</th>
                        <th class="px-6 py-3.5">Linked Order</th>
                        <th class="px-6 py-3.5">Priority</th>
                        <th class="px-6 py-3.5">Assigned Staff</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($tickets as $t)
                    @php
                        $stVal = is_object($t->status) ? $t->status->value : (string) $t->status;
                        $prVal = is_object($t->priority) ? $t->priority->value : (string) $t->priority;
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono font-bold text-slate-900 dark:text-white">#{{ $t->ticket_number }}</span>
                                @if($t->is_escalated)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-purple-100 text-purple-800 border border-purple-200">
                                        Escalated
                                    </span>
                                @endif
                            </div>
                            <div class="font-bold text-slate-900 dark:text-white mt-0.5 max-w-sm truncate">{{ $t->subject }}</div>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                                <span>{{ $t->created_at->diffForHumans() }}</span>
                                <span>&bull;</span>
                                <span class="text-slate-500">{{ $t->category_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white">{{ $t->user?->name ?? 'Guest User' }}</div>
                            <div class="text-[10px] text-slate-400">{{ $t->user?->email }}</div>
                            @if($t->seller)
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span>Seller: {{ $t->seller->business_name }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($t->order)
                                <a href="{{ route('admin.orders.show', $t->order) }}" class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $t->order->order_number }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.support.priority', $t) }}">
                                @csrf
                                <select 
                                    name="priority" 
                                    onchange="this.form.submit()" 
                                    class="text-[10px] font-bold uppercase tracking-wider rounded-lg border py-1 px-2 cursor-pointer transition
                                        {{ $prVal === 'urgent' ? 'bg-rose-100 text-rose-800 border-rose-200' : '' }}
                                        {{ $prVal === 'high' ? 'bg-amber-100 text-amber-800 border-amber-200' : '' }}
                                        {{ $prVal === 'medium' ? 'bg-blue-100 text-blue-800 border-blue-200' : '' }}
                                        {{ $prVal === 'low' ? 'bg-slate-100 text-slate-700 border-slate-200' : '' }}
                                    "
                                >
                                    <option value="low" {{ $prVal === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ $prVal === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ $prVal === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ $prVal === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.support.assign', $t) }}">
                                @csrf
                                <select 
                                    name="assigned_to" 
                                    onchange="this.form.submit()" 
                                    class="text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-1 px-2"
                                >
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $st)
                                        <option value="{{ $st->id }}" {{ $t->assigned_to == $st->id ? 'selected' : '' }}>
                                            {{ $st->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.support.status', $t) }}">
                                @csrf
                                <select 
                                    name="status" 
                                    onchange="this.form.submit()" 
                                    class="text-xs font-semibold rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 py-1 px-2 transition
                                        {{ $stVal === 'open' ? 'text-rose-600 bg-rose-50 border-rose-200' : '' }}
                                        {{ $stVal === 'in_progress' ? 'text-amber-600 bg-amber-50 border-amber-200' : '' }}
                                        {{ $stVal === 'waiting_customer' ? 'text-sky-600 bg-sky-50 border-sky-200' : '' }}
                                        {{ $stVal === 'resolved' ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : '' }}
                                        {{ $stVal === 'closed' ? 'text-slate-600 bg-slate-100 border-slate-200' : '' }}
                                    "
                                >
                                    <option value="open" {{ $stVal === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ $stVal === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="waiting_customer" {{ $stVal === 'waiting_customer' ? 'selected' : '' }}>Waiting for Response</option>
                                    <option value="resolved" {{ $stVal === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ $stVal === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a 
                                href="{{ route('admin.support.show', $t) }}" 
                                class="px-3.5 py-1.5 bg-slate-900 hover:bg-black text-white rounded-xl font-bold text-xs shadow-xs transition"
                            >
                                Open Thread &rarr;
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            No support tickets found matching criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <x-pagination :paginator="$tickets" />
        </div>
    </div>

</div>
@endsection
