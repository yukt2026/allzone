@php
    $isSeller = auth()->user()?->role === 'seller' || request()->is('seller*');
    $isAdmin = auth()->user()?->hasRole(['super_admin', 'admin', 'operations_admin', 'catalog_manager', 'finance_manager', 'seller_support']) || request()->is('admin*');
    $useDashboard = $isSeller || $isAdmin;
    $portalName = $isSeller ? 'seller' : 'admin';
    $markAllRoute = $isSeller ? route('seller.notifications.mark_all_read') : route('notifications.mark_all_read');
    $clearAllRoute = $isSeller ? route('seller.notifications.clear_all') : route('notifications.clear_all');
    $preferencesRoute = $isSeller ? route('seller.notifications.preferences.update') : route('notifications.preferences.update');
    $brandName = function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE';
    $eventCats = $eventCategories ?? (is_array($categories ?? null) ? $categories : []);
@endphp

@extends($useDashboard ? 'layouts.dashboard' : 'layouts.storefront', [
    'portal' => $portalName,
    'title' => 'Notification Center - ' . $brandName
])

@section('content')
<div class="{{ $useDashboard ? 'space-y-6' : 'min-h-screen bg-slate-50/60 py-6 sm:py-10' }}" x-data="{ activeTab: 'inbox' }">
    <div class="{{ $useDashboard ? '' : 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6' }}">

        <!-- Breadcrumbs Navigation for Storefront -->
        @if(!$useDashboard)
            <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400">
                <a href="{{ route('home') }}" class="hover:text-emerald-600 transition">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('customer.profile') }}" class="hover:text-emerald-600 transition">My Account</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-bold">Notifications</span>
            </nav>
        @endif

        <!-- Session Feedback Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <span class="text-emerald-600 text-base font-black">✓</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-base font-bold">&times;</button>
            </div>
        @endif

        <!-- Page Header -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-7 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Notification Center</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Order updates, inventory notices, disbursement alerts and channel settings.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ $markAllRoute }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 transition cursor-pointer flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Mark All Read</span>
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ $clearAllRoute }}" onsubmit="return confirm('Delete all notifications?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-300 transition cursor-pointer flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Clear All</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Metrics Strip -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Alerts</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalCount ?? 0) }}</div>
                <span class="text-[10px] text-slate-400">Lifetime notifications</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-500">Unread</span>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($unreadCount ?? 0) }}</div>
                <span class="text-[10px] text-slate-400">New updates</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-500">Read</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($readCount ?? 0) }}</div>
                <span class="text-[10px] text-slate-400">Archived alerts</span>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-500">Today</span>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($todayCount ?? 0) }}</div>
                <span class="text-[10px] text-slate-400">Received today</span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex space-x-8 text-xs sm:text-sm font-bold">
            <button 
                type="button"
                @click="activeTab = 'inbox'"
                :class="activeTab === 'inbox' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Inbox Alerts</span>
                @if(($unreadCount ?? 0) > 0)
                    <span class="ml-1.5 bg-amber-500 text-white rounded-full text-[10px] font-bold px-2 py-0.5">{{ $unreadCount }}</span>
                @endif
            </button>

            <button 
                type="button"
                @click="activeTab = 'preferences'"
                :class="activeTab === 'preferences' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Channel Preferences &amp; Marketing Consent</span>
            </button>
        </div>

        <!-- Tab 1: In-App Notifications Feed -->
        <div x-show="activeTab === 'inbox'" class="space-y-4">

            <!-- Filter Controls -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 md:pb-0">
                    @php
                        $indexRoute = $isSeller ? route('seller.notifications.index') : route('notifications.index');
                    @endphp
                    <a href="{{ $indexRoute . '?filter=all' }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                        All ({{ $totalCount ?? 0 }})
                    </a>
                    <a href="{{ $indexRoute . '?filter=unread' }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'unread' ? 'bg-amber-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                        Unread ({{ $unreadCount ?? 0 }})
                    </a>
                    <a href="{{ $indexRoute . '?filter=read' }}"
                       class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($currentFilter ?? 'all') === 'read' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200' }}">
                        Read ({{ $readCount ?? 0 }})
                    </a>
                </div>

                <form method="GET" action="{{ $indexRoute }}" class="flex items-center gap-2">
                    <input type="hidden" name="filter" value="{{ $currentFilter ?? 'all' }}">
                    @if(!empty($eventCats))
                        <select name="category" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500">
                            <option value="">All Categories</option>
                            @foreach($eventCats as $catKey => $catLabel)
                                <option value="{{ $catKey }}" {{ ($currentCategory ?? '') === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    @endif

                    <div class="relative">
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search alerts..." class="pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-emerald-500">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    @if(!empty($currentCategory) || !empty($search))
                        <a href="{{ $indexRoute . '?filter=' . ($currentFilter ?? 'all') }}" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-bold px-1">Clear</a>
                    @endif
                </form>
            </div>

            @if($notifications->isEmpty())
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center shadow-xs space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-2xl font-black">
                        ✓
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">You're all caught up!</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">There are no new notifications or unhandled operational alerts.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($notifications as $n)
                        @php
                            $isUnread = ($n->read_at === null);
                            $category = $n->data['category'] ?? 'orders';
                            $goUrl = $isSeller ? route('seller.notifications.go', $n->id) : route('notifications.go', $n->id);
                            $readRoute = $isSeller ? route('seller.notifications.read', $n->id) : route('notifications.read', $n->id);
                            $badgeColor = match($category) {
                                'inventory' => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
                                'returns_claims', 'security' => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
                                'settlements' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
                                'catalogue' => 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
                                'announcements' => 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800',
                                'promotions' => 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-200 dark:bg-fuchsia-950/60 dark:text-fuchsia-300 dark:border-fuchsia-800',
                                default => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
                            };
                        @endphp
                        <div class="p-4 sm:p-5 rounded-2xl border transition flex items-start justify-between gap-4 {{ $isUnread ? 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 shadow-xs' : 'bg-slate-50/70 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800/80' }}">
                            <div class="flex items-start space-x-3.5 flex-1 min-w-0">
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
                                        <a href="{{ $goUrl }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition truncate">
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
                                            <a href="{{ $goUrl }}" class="font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                                {{ $n->data['action_text'] ?? 'View Related Resource &rarr;' }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 pt-0.5 shrink-0">
                                @if($isUnread)
                                    <form method="POST" action="{{ $readRoute }}">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Mark as read">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ $goUrl }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-400 text-slate-700 dark:text-slate-300 text-xs font-bold transition">
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

        <!-- Tab 2: Channel Preferences & Marketing Consent -->
        <div x-show="activeTab === 'preferences'" class="space-y-6">
            <form method="POST" action="{{ $preferencesRoute }}" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-6">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Delivery Channel Subscriptions</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control through which communication pathways you receive updates.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                    <th class="py-3 px-4">Event Category</th>
                                    <th class="py-3 px-4 text-center">In-App (Bell)</th>
                                    <th class="py-3 px-4 text-center">Email</th>
                                    <th class="py-3 px-4 text-center">SMS</th>
                                    <th class="py-3 px-4 text-center">WhatsApp</th>
                                    <th class="py-3 px-4 text-center">Browser Push</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-semibold">
                                @foreach($eventCats as $catKey => $catLabel)
                                    @php
                                        $pref = $preferences[$catKey] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                        <td class="py-3.5 px-4 text-slate-900 dark:text-white">
                                            <div class="font-bold">{{ $catLabel }}</div>
                                            <div class="text-[10px] text-slate-400 font-normal">Alerts regarding {{ strtolower($catLabel) }}</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][database]" value="1" {{ ($pref?->channel_database ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][mail]" value="1" {{ ($pref?->channel_mail ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][sms]" value="1" {{ ($pref?->channel_sms ?? ($catKey !== 'promotions')) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][whatsapp]" value="1" {{ ($pref?->channel_whatsapp ?? ($catKey !== 'promotions')) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][push]" value="1" {{ ($pref?->channel_push ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Promotional Consent Guard -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 rounded-2xl flex items-start space-x-3">
                        <input type="checkbox" id="promotional_consent" name="promotional_consent" value="1" {{ ($preferences['promotions']->promotional_consent ?? false) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mt-1 w-4 h-4">
                        <label for="promotional_consent" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <span class="font-bold text-slate-900 dark:text-white block">Promotional Communications Consent</span>
                            I consent to receive special promotions, seller campaign invitations, flash sale opportunities and curated offers across selected channels.
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-sm transition">
                            Save Preferences
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
