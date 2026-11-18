@extends('layouts.dashboard', [
    'portal' => 'admin',
    'title' => 'Notifications & Alerts - ' . (function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE')
])

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ request('tab', 'inbox') }}' }">

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <span class="text-emerald-600 text-base font-black">✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-base font-bold">&times;</button>
        </div>
    @endif

    <!-- Page Header & Global Actions -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Admin Alerts &amp; Notifications</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time marketplace system events, high-priority operational flags, and notification preferences.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.mark_all_read') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 transition flex items-center space-x-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Mark All Read</span>
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.notifications.clear_all') }}" onsubmit="return confirm('Permanently clear all notification history?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 transition flex items-center space-x-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Clear All</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Metrics Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Alerts</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalCount) }}</div>
            <span class="text-[10px] text-slate-400">Recorded audit events</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Unread Alerts</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($unreadCount) }}</div>
            <span class="text-[10px] text-slate-400">Requiring review</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-500">Read &amp; Handled</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($readCount) }}</div>
            <span class="text-[10px] text-slate-400">Archived history</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-500">Today's New Alerts</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($todayCount) }}</div>
            <span class="text-[10px] text-slate-400">Events logged today</span>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-800 flex space-x-6 text-xs sm:text-sm font-bold">
        <button 
            type="button"
            @click="activeTab = 'inbox'"
            :class="activeTab === 'inbox' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
            class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <span>Alerts Feed</span>
            @if($unreadCount > 0)
                <span class="ml-1 bg-amber-500 text-white rounded-full text-[10px] font-bold px-2 py-0.5">{{ $unreadCount }}</span>
            @endif
        </button>

        <button 
            type="button"
            @click="activeTab = 'preferences'"
            :class="activeTab === 'preferences' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
            class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
            <span>Notification &amp; Channel Preferences</span>
        </button>
    </div>

    <!-- Tab 1: Alerts Feed -->
    <div x-show="activeTab === 'inbox'" class="space-y-4">

        <!-- Filters Strip -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Filter Pills -->
            <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $currentFilter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                    All ({{ $totalCount }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'unread'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $currentFilter === 'unread' ? 'bg-amber-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                    Unread ({{ $unreadCount }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->query(), ['filter' => 'read'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $currentFilter === 'read' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                    Read ({{ $readCount }})
                </a>
            </div>

            <!-- Category & Search Form -->
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="filter" value="{{ $currentFilter }}">
                <select name="category" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $catKey => $catLabel)
                        <option value="{{ $catKey }}" {{ $currentCategory === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                    @endforeach
                </select>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search alerts..." class="pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                @if($currentCategory || $search)
                    <a href="{{ route('admin.notifications.index', ['filter' => $currentFilter]) }}" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-bold px-1">Clear</a>
                @endif
            </form>
        </div>

        <!-- Notifications List -->
        @if($notifications->isEmpty())
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center shadow-xs space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-2xl font-black">
                    ✓
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">All Caught Up!</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">There are no matching notifications or unhandled operational alerts in this view.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($notifications as $n)
                    @php
                        $isUnread = ($n->read_at === null);
                        $category = $n->data['category'] ?? 'general';
                        $eventKey = $n->data['event_key'] ?? '';
                        $actionUrl = route('admin.notifications.go', $n->id);
                        $badgeColor = match($category) {
                            'security', 'finance' => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
                            'orders' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
                            'sellers' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
                            'catalogue' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                            'inventory' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                            'logistics' => 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
                            'support' => 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800',
                            default => 'bg-slate-100 text-slate-800 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                        };
                    @endphp

                    <div class="p-4 rounded-2xl border transition flex items-start justify-between gap-4 {{ $isUnread ? 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 shadow-xs' : 'bg-slate-50/70 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800/80' }}">
                        <div class="flex items-start space-x-3.5 flex-1 min-w-0">
                            <!-- Unread Indicator Dot -->
                            <div class="pt-1">
                                @if($isUnread)
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 block" title="Unread"></span>
                                @else
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700 block" title="Read"></span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md border uppercase tracking-wider {{ $badgeColor }}">
                                        {{ $categories[$category] ?? ucfirst(str_replace('_', ' ', $category)) }}
                                    </span>
                                    <a href="{{ $actionUrl }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition truncate">
                                        {{ $n->data['title'] ?? 'Notification' }}
                                    </a>
                                </div>

                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                                    {{ $n->data['message'] ?? '' }}
                                </p>

                                <div class="flex items-center space-x-3 mt-2 text-[11px] text-slate-400 font-medium">
                                    <span>{{ $n->created_at->diffForHumans() }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $n->created_at->format('d M Y, h:i A') }}</span>
                                    @if(!empty($n->data['action_text']) || !empty($n->data['action_url']))
                                        <span>&bull;</span>
                                        <a href="{{ $actionUrl }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                            {{ $n->data['action_text'] ?? 'View Related Resource &rarr;' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center space-x-2 pt-0.5 shrink-0">
                            @if($isUnread)
                                <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Mark as read">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            @endif

                            <a href="{{ $actionUrl }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-400 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
                                Open
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    <!-- Tab 2: Notification & Channel Preferences -->
    <div x-show="activeTab === 'preferences'" class="space-y-6">
        <form method="POST" action="{{ route('admin.notifications.preferences.update') }}" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Admin Channel Preferences</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Select how you want to be notified for critical operational events and anomalies across channels.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                <th class="py-3 px-4">Event Category</th>
                                <th class="py-3 px-4 text-center">In-App (Database)</th>
                                <th class="py-3 px-4 text-center">Email</th>
                                <th class="py-3 px-4 text-center">SMS</th>
                                <th class="py-3 px-4 text-center">WhatsApp</th>
                                <th class="py-3 px-4 text-center">Browser Push</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-semibold">
                            @foreach($categories as $catKey => $catLabel)
                                @php
                                    $pref = $preferences[$catKey] ?? null;
                                @endphp
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3.5 px-4 text-slate-900 dark:text-white">
                                        <div class="font-bold">{{ $catLabel }}</div>
                                        <div class="text-[10px] text-slate-400 font-normal">System events in {{ strtolower($catLabel) }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" name="preferences[{{ $catKey }}][database]" value="1" {{ ($pref?->channel_database ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" name="preferences[{{ $catKey }}][mail]" value="1" {{ ($pref?->channel_mail ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" name="preferences[{{ $catKey }}][sms]" value="1" {{ ($pref?->channel_sms ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" name="preferences[{{ $catKey }}][whatsapp]" value="1" {{ ($pref?->channel_whatsapp ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" name="preferences[{{ $catKey }}][push]" value="1" {{ ($pref?->channel_push ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition">
                        Save Preferences
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
