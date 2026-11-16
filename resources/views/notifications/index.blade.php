@php
    $useDashboard = in_array(auth()->user()?->role, ['seller', 'admin']) && (request()->is('seller*') || request()->is('admin*'));
@endphp

@extends($useDashboard ? 'layouts.dashboard' : 'layouts.storefront', [
    'portal' => (auth()->user()?->role === 'seller' ? 'seller' : 'admin'),
    'title' => 'Notifications & Preferences - ' . setting('name', 'ALLZONE')
])

@section('content')
<div class="{{ $useDashboard ? 'space-y-6' : 'min-h-screen bg-slate-50/60 py-6 sm:py-10' }}" x-data="{ activeTab: 'inbox' }">
    <div class="{{ $useDashboard ? '' : 'max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6' }}">

        <!-- Breadcrumbs Navigation -->
        @if(!$useDashboard)
            <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400">
                <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('customer.profile') }}" class="hover:text-blue-600 transition">My Account</a>
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
        <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Notification Center</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $unreadCount > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $unreadCount }} Unread
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Review account alerts, shipment updates, and customize your delivery channels.</p>
            </div>

            <div class="flex items-center space-x-2">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.mark_all_read') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 transition">
                            Mark All Read
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('notifications.clear_all') }}" onsubmit="return confirm('Delete all notifications?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 transition">
                        Clear All
                    </button>
                </form>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 flex space-x-8 text-xs sm:text-sm font-bold">
            <button 
                type="button"
                @click="activeTab = 'inbox'"
                :class="activeTab === 'inbox' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Inbox Alerts</span>
                @if($unreadCount > 0)
                    <span class="ml-1.5 bg-blue-600 text-white rounded-full text-[10px] font-bold px-2 py-0.5">{{ $unreadCount }}</span>
                @endif
            </button>

            <button 
                type="button"
                @click="activeTab = 'preferences'"
                :class="activeTab === 'preferences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                class="py-3 px-1 border-b-2 flex items-center space-x-2 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Channel Preferences &amp; Marketing Consent</span>
            </button>
        </div>

        <!-- Tab 1: In-App Notifications Feed -->
        <div x-show="activeTab === 'inbox'" class="space-y-4">
            @if($notifications->isEmpty())
                <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center shadow-xs space-y-3">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-2xl">
                        🔔
                    </div>
                    <h3 class="text-base font-bold text-slate-900">You're all caught up!</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">No active alerts or messages in your inbox. Check back when your shipments update.</p>
                </div>
            @else
                <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden divide-y divide-slate-100">
                    @foreach($notifications as $n)
                        @php
                            $data = $n->data;
                            $isUnread = is_null($n->read_at);
                        @endphp
                        <div class="p-4 sm:p-5 flex items-start justify-between space-x-4 {{ $isUnread ? 'bg-blue-50/30' : 'hover:bg-slate-50/60' }} transition">
                            <div class="flex items-start space-x-3.5">
                                <div class="p-2.5 rounded-2xl {{ $isUnread ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-500' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-sm font-bold text-slate-900">
                                            {{ $data['title'] ?? 'Notification' }}
                                        </h4>
                                        @if($isUnread)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">New</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                        {{ $data['message'] ?? '' }}
                                    </p>
                                    <div class="mt-2.5 flex items-center space-x-4 text-[11px] text-slate-400">
                                        <span>{{ $n->created_at->diffForHumans() }}</span>
                                        @if(!empty($data['action_url']))
                                            <a href="{{ $data['action_url'] }}" class="font-bold text-blue-600 hover:underline flex items-center space-x-1">
                                                <span>{{ $data['action_text'] ?? 'View Details' }}</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                @if($isUnread)
                                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition" title="Mark as Read">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>

        <!-- Tab 2: Channel Preferences & Promotional Consent -->
        <div x-show="activeTab === 'preferences'" class="space-y-6" style="display: none;">
            <form method="POST" action="{{ route('notifications.preferences.update') }}" class="space-y-6">
                @csrf

                <!-- Channels Matrix Card -->
                <div class="bg-white border border-slate-200 rounded-3xl shadow-xs p-5 sm:p-7 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Delivery Channel Subscriptions</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Configure where you receive transactional alerts, shipment updates, and security notices.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                    <th class="py-3 px-3">Event Category</th>
                                    <th class="py-3 px-3 text-center">In-App</th>
                                    <th class="py-3 px-3 text-center">Email</th>
                                    <th class="py-3 px-3 text-center">SMS</th>
                                    <th class="py-3 px-3 text-center">WhatsApp</th>
                                    <th class="py-3 px-3 text-center">Push</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach(['orders' => 'Order Lifecycle & Shipping', 'account' => 'Account & Settlements', 'security' => 'Security & OTP Verification', 'promotions' => 'Discounts & Special Offers'] as $catKey => $catLabel)
                                    @php
                                        $p = $preferences[$catKey] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3.5 px-3 font-semibold text-slate-900">
                                            {{ $catLabel }}
                                            <div class="text-[11px] font-normal text-slate-400 mt-0.5">
                                                @if($catKey === 'orders') Shiprocket tracking, out for delivery, order delivered.
                                                @elseif($catKey === 'security') Login verifications, password changes, security OTPs.
                                                @elseif($catKey === 'account') Address updates, support responses, refund status.
                                                @else Exclusive member coupons, seasonal electronics flash sales.
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][database]" value="1" {{ ($p?->channel_database ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][mail]" value="1" {{ ($p?->channel_mail ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][sms]" value="1" {{ ($p?->channel_sms ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][whatsapp]" value="1" {{ ($p?->channel_whatsapp ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <input type="checkbox" name="preferences[{{ $catKey }}][push]" value="1" {{ ($p?->channel_push ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Promotional & Marketing Consent Guard -->
                <div class="bg-amber-50/60 border border-amber-200 rounded-3xl p-5 sm:p-6">
                    <div class="flex items-start space-x-3.5">
                        <div class="p-2 rounded-xl bg-amber-500 text-white mt-0.5 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-amber-900">Explicit Promotional Consent (Strict Opt-In)</h4>
                            <p class="text-xs text-amber-800/80 mt-1 leading-relaxed">
                                Under privacy governance, promotional vouchers and sale announcements are only sent if you explicitly opt in. You may toggle this off at any time.
                            </p>

                            <div class="mt-3 flex items-center space-x-3">
                                <input 
                                    type="checkbox" 
                                    id="promotional_consent" 
                                    name="promotional_consent" 
                                    value="1" 
                                    {{ ($preferences['promotions']?->promotional_consent ?? false) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-amber-300"
                                >
                                <label for="promotional_consent" class="text-xs font-semibold text-slate-800 cursor-pointer">
                                    I agree to receive deals, flash sale alerts, and promotional coupons from {{ setting('name', 'ALLZONE') }}.
                                </label>
                            </div>

                            @if(!empty($preferences['promotions']?->promotional_consent_at))
                                <div class="mt-2 text-[11px] text-amber-700">
                                    ✓ Consent recorded on {{ $preferences['promotions']->promotional_consent_at->format('M d, Y H:i') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition cursor-pointer">
                        Save Communication Preferences
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
