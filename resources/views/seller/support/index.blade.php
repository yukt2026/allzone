@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Support Desk - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Support &amp; Help Desk</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Raise queries, track operational complaints, and get fast assistance on orders, returns, and payments.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('seller.support.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-2xs transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Raise New Ticket</span>
            </a>
        </div>
    </div>

    <!-- Navigation Tabs: Help & My Tickets with Badge -->
    <div class="flex items-center space-x-6 border-b border-slate-200 dark:border-slate-800 text-sm font-bold">
        <a href="{{ route('seller.support.index', ['tab' => 'help']) }}"
           class="pb-3 border-b-2 transition flex items-center space-x-2 {{ $tab === 'help' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-extrabold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            <span>Browse Help Topics</span>
        </a>
        <a href="{{ route('seller.support.index', ['tab' => 'tickets']) }}"
           class="pb-3 border-b-2 transition flex items-center space-x-2 {{ $tab === 'tickets' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-extrabold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            <span>My Tickets</span>
            @if(($metrics['open'] + $metrics['in_progress'] + $metrics['waiting_seller']) > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 font-bold">
                    {{ $metrics['open'] + $metrics['in_progress'] + $metrics['waiting_seller'] }}
                </span>
            @endif
        </a>
    </div>

    @if($tab === 'help')
        <!-- Search Bar for Help Categories -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-4 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                Select an issue category to get help or submit a query
            </div>
            <form action="{{ route('seller.support.index') }}" method="GET" class="relative w-full sm:w-80">
                <input type="hidden" name="tab" value="help">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search issue categories or questions..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:border-indigo-600 focus:bg-white dark:focus:bg-slate-800 transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if($search)
                    <a href="{{ route('seller.support.index', ['tab' => 'help']) }}" class="absolute right-2.5 top-2 text-[11px] font-bold text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">Clear</a>
                @endif
            </form>
        </div>

        <!-- Issue Categories Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @forelse($categories as $cat)
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-xs transition p-5 flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center transition">
                                @php
                                    $iconName = $cat->icon ?: 'chat-bubble';
                                @endphp
                                @if($iconName === 'truck')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                @elseif($iconName === 'refresh' || $iconName === 'arrow-path')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                @elseif($iconName === 'tag')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                @elseif($iconName === 'cube')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                @elseif($iconName === 'credit-card')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                @elseif($iconName === 'currency-rupee')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-9h6m-6 3h4"/></svg>
                                @elseif($iconName === 'shield-check')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                @elseif($iconName === 'sparkles')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                @elseif($iconName === 'user')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ $cat->activeQuestions->count() }} questions</span>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                            {{ $cat->name }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                            {{ $cat->description }}
                        </p>
                    </div>

                    <!-- Common questions preview -->
                    @if($cat->activeQuestions->isNotEmpty())
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1.5">
                            @foreach($cat->activeQuestions->take(2) as $q)
                                <a href="{{ route('seller.support.create', ['category' => $cat->slug, 'question' => $q->id]) }}"
                                   class="text-[11px] text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-between gap-1 transition">
                                    <span class="truncate">&bull; {{ $q->question }}</span>
                                    <span class="text-slate-300 dark:text-slate-600 shrink-0">&rsaquo;</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 pt-2">
                        <a href="{{ route('seller.support.create', ['category' => $cat->slug]) }}"
                           class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1">
                            <span>Open ticket in this topic</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-500 dark:text-slate-400 text-xs">
                    No issue categories matching your search query. <a href="{{ route('seller.support.index', ['tab' => 'help']) }}" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Reset search</a>
                </div>
            @endforelse
        </div>

        <!-- Recently Raised Tickets Section -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden mt-6">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Recently Raised Tickets</span>
                </h2>
                <a href="{{ route('seller.support.index', ['tab' => 'tickets']) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                    View All ({{ $metrics['total'] }}) &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100 dark:border-slate-800 tracking-wider">
                        <tr>
                            <th class="p-3">Ticket ID &amp; Subject</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Created</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        @forelse($tickets->take(5) as $ticket)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                                <td class="p-3">
                                    <a href="{{ route('seller.support.show', $ticket) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                        {{ $ticket->subject }}
                                    </a>
                                    <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                        {{ $ticket->ticket_number }}
                                    </div>
                                </td>
                                <td class="p-3 text-slate-700 dark:text-slate-300 font-medium">
                                    {{ $ticket->category_name }}
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold
                                        {{ $ticket->status_badge_color === 'rose' ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' :
                                          ($ticket->status_badge_color === 'amber' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' :
                                          ($ticket->status_badge_color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800' :
                                          ($ticket->status_badge_color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' :
                                          'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'))) }}">
                                        {{ $ticket->status_label }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-500 dark:text-slate-400">
                                    {{ $ticket->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('seller.support.show', $ticket) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        View Thread &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-400 text-xs">
                                    You have not raised any support tickets yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- My Tickets Tab (Status Filters & Search) -->
        <div class="space-y-4">
            <!-- Filter Strip -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 p-3 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Status Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs font-semibold scrollbar-none">
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'all']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'all' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        All ({{ $metrics['total'] }})
                    </a>
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'open']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 {{ $status === 'open' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span>Open</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'open' ? 'bg-white text-indigo-700 font-bold' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 font-bold' }}">{{ $metrics['open'] }}</span>
                    </a>
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'in_progress']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 {{ $status === 'in_progress' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span>In Progress</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'in_progress' ? 'bg-white text-indigo-700 font-bold' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-400 font-bold' }}">{{ $metrics['in_progress'] }}</span>
                    </a>
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'waiting_seller']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 {{ $status === 'waiting_seller' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <span>Waiting for Seller</span>
                        @if($metrics['waiting_seller'] > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'waiting_seller' ? 'bg-white text-indigo-700 font-bold' : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold' }}">{{ $metrics['waiting_seller'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'resolved']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'resolved' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        Resolved ({{ $metrics['resolved'] }})
                    </a>
                    <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => 'closed']) }}"
                       class="px-3 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'closed' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        Closed ({{ $metrics['closed'] }})
                    </a>
                </div>

                <!-- Search Form -->
                <form action="{{ route('seller.support.index') }}" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="tickets">
                    @if($status !== 'all')
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <div class="relative w-full sm:w-60">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search ticket # or subject..."
                               class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:border-indigo-600 focus:bg-white dark:focus:bg-slate-800 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    @if($search)
                        <a href="{{ route('seller.support.index', ['tab' => 'tickets', 'status' => $status]) }}" class="px-2 py-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100 dark:border-slate-800 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Ticket Number &amp; Subject</th>
                                <th class="py-3.5 px-4">Issue Category</th>
                                <th class="py-3.5 px-4">Related Reference</th>
                                <th class="py-3.5 px-4">Priority</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Last Updated</th>
                                <th class="py-3.5 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($tickets as $ticket)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="font-bold text-slate-900 dark:text-white leading-snug">
                                            <a href="{{ route('seller.support.show', $ticket) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                {{ $ticket->subject }}
                                            </a>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                            {{ $ticket->ticket_number }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $ticket->category_name }}</div>
                                        @if($ticket->questionRelation)
                                            <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $ticket->questionRelation->question }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @if($ticket->order)
                                            <div class="font-mono text-[11px] text-indigo-700 dark:text-indigo-400">Order: {{ $ticket->order->order_number }}</div>
                                        @endif
                                        @if($ticket->product)
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-xs">SKU: {{ $ticket->product->sku }}</div>
                                        @endif
                                        @if($ticket->awb_number)
                                            <div class="font-mono text-[10px] text-slate-400">AWB: {{ $ticket->awb_number }}</div>
                                        @endif
                                        @if(!$ticket->order && !$ticket->product && !$ticket->awb_number)
                                            <span class="text-slate-400 text-[11px]">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                            {{ $ticket->priority->value === 'urgent' ? 'bg-rose-600 text-white' :
                                              ($ticket->priority->value === 'high' ? 'bg-amber-500 text-white' :
                                              'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') }}">
                                            {{ $ticket->priority->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded text-[10px] font-bold
                                            {{ $ticket->status_badge_color === 'rose' ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800' :
                                              ($ticket->status_badge_color === 'amber' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' :
                                              ($ticket->status_badge_color === 'indigo' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800' :
                                              ($ticket->status_badge_color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' :
                                              'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'))) }}">
                                            {{ $ticket->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-[11px]">
                                        {{ $ticket->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <a href="{{ route('seller.support.show', $ticket) }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline">
                                            <span>View Thread</span>
                                            <span>&rarr;</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 px-4">
                                        <x-empty-state 
                                            icon="chat-bubble"
                                            title="No Support Tickets Found"
                                            description="{{ ($status !== 'all') ? 'There are no tickets currently in ' . ucfirst(str_replace('_', ' ', $status)) . ' status.' : 'You do not have any open support tickets at this moment.' }}"
                                        >
                                            <div class="mt-2">
                                                <a href="{{ route('seller.support.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-2xs transition">
                                                    Raise New Ticket
                                                </a>
                                            </div>
                                        </x-empty-state>
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
    @endif
</div>
@endsection
