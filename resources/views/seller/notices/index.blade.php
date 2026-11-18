@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Notices & Announcements - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                <span>Notices &amp; Announcements</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Official platform policy updates, festival promotions, logistics guidelines, and compliance announcements.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form action="{{ route('seller.notices.mark_all_read') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg border border-indigo-200 transition flex items-center gap-1.5 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Mark All as Read ({{ $unreadCount }})</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Category Tabs & Search Bar -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-3 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs font-semibold">
            <a href="{{ route('seller.notices.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $category === 'all' && $status !== 'unread' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ route('seller.notices.index', array_merge(request()->except('status', 'page'), ['status' => 'unread'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition flex items-center gap-1 {{ $status === 'unread' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                <span>Unread</span>
                @if($unreadCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $status === 'unread' ? 'bg-white text-indigo-700 font-bold' : 'bg-rose-100 text-rose-700 font-bold' }}">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('seller.notices.index', array_merge(request()->except('category', 'page', 'status'), ['category' => 'important'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $category === 'important' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                Important ({{ $importantCount }})
            </a>
            <a href="{{ route('seller.notices.index', array_merge(request()->except('category', 'page', 'status'), ['category' => 'policy'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $category === 'policy' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                Policy
            </a>
            <a href="{{ route('seller.notices.index', array_merge(request()->except('category', 'page', 'status'), ['category' => 'logistics'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $category === 'logistics' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                Logistics &amp; SLA
            </a>
            <a href="{{ route('seller.notices.index', array_merge(request()->except('category', 'page', 'status'), ['category' => 'general'])) }}"
               class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $category === 'general' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100' }}">
                General ({{ $generalCount }})
            </a>
        </div>

        <!-- Search input -->
        <form action="{{ route('seller.notices.index') }}" method="GET" class="flex items-center gap-2">
            @if($category !== 'all')
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="relative w-full sm:w-64">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Search announcements..."
                       class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-hidden focus:border-indigo-600 focus:bg-white transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            @if($search)
                <a href="{{ route('seller.notices.index', ['category' => $category, 'status' => $status]) }}" class="px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- 2 Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Left: Announcements & Policy Notices List -->
        <div class="lg:col-span-2 space-y-4">
            @forelse($notices as $notice)
                @php
                    $isRead = $notice->isReadBy($seller);
                @endphp
                <div class="bg-white rounded-xl border {{ $isRead ? 'border-slate-200/90' : 'border-indigo-200 bg-indigo-50/10 ring-1 ring-indigo-500/10' }} p-5 shadow-2xs hover:shadow-xs transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <!-- Badges & Meta -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    {{ $notice->category_badge_color === 'rose' ? 'bg-rose-100 text-rose-700 border border-rose-200' :
                                      ($notice->category_badge_color === 'amber' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                                      ($notice->category_badge_color === 'sky' ? 'bg-sky-100 text-sky-800 border border-sky-200' :
                                      ($notice->category_badge_color === 'emerald' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                                      'bg-slate-100 text-slate-700 border border-slate-200'))) }}">
                                    {{ $notice->category_label }}
                                </span>

                                @if($notice->priority === 'urgent')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-rose-600 text-white animate-pulse">
                                        Urgent
                                    </span>
                                @elseif($notice->priority === 'high')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500 text-white">
                                        High Priority
                                    </span>
                                @endif

                                @if(!$isRead)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                        <span>New Notice</span>
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 font-medium">Read</span>
                                @endif

                                <span class="text-xs text-slate-400 font-medium">
                                    &bull; {{ $notice->published_at ? $notice->published_at->format('d M Y') : 'Active' }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h2 class="text-base sm:text-lg font-bold text-slate-900 pt-1 leading-snug">
                                <a href="{{ route('seller.notices.show', $notice) }}" class="hover:text-indigo-600 transition">
                                    {{ $notice->title }}
                                </a>
                            </h2>

                            <!-- Summary / Excerpt -->
                            <p class="text-xs text-slate-600 leading-relaxed">
                                {{ $notice->summary ?: \Illuminate\Support\Str::limit(strip_tags($notice->content), 180) }}
                            </p>

                            <!-- Attachment Indicator -->
                            @if($notice->attachment_path)
                                <div class="pt-1 flex items-center gap-1.5 text-[11px] font-semibold text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span>Attachment included: {{ $notice->attachment_name ?: 'Policy Document' }}</span>
                                </div>
                            @endif
                        </div>

                        @if($notice->banner_image)
                            <div class="hidden sm:block shrink-0 w-28 h-20 rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($notice->banner_image) }}" alt="{{ $notice->title }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                    </div>

                    <!-- Footer actions -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">Platform Administration</span>

                        <div class="flex items-center gap-3">
                            @if(!$isRead)
                                <form action="{{ route('seller.notices.mark_read', $notice) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-medium text-slate-500 hover:text-slate-800 transition">
                                        Mark as read
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('seller.notices.show', $notice) }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                                <span>Read Full Notice</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200/90 p-12 text-center shadow-2xs space-y-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">No Announcements Found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        @if($search)
                            No announcements matching "<span class="font-semibold">{{ $search }}</span>". Try clearing your search query.
                        @else
                            You are all caught up! There are no active announcements in this category right now.
                        @endif
                    </p>
                    @if($search || $category !== 'all' || $status !== 'all')
                        <div class="pt-2">
                            <a href="{{ route('seller.notices.index') }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                Reset Filters
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notices->hasPages())
                <div class="pt-2">
                    {{ $notices->links() }}
                </div>
            @endif
        </div>

        <!-- Right: Important Leave Request / Store Holiday Widget -->
        <div class="space-y-5">
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-rose-500 font-bold text-sm">⚠️</span>
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">
                            Important
                        </h3>
                    </div>
                    <h4 class="text-xs font-bold text-slate-800 mt-1">
                        Leave Request / Store Holiday
                    </h4>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Going on vacation or taking temporary leave? Submit your store holiday dates to pause order dispatch SLAs and avoid penalties.
                    </p>
                </div>

                <form action="{{ route('seller.notices.leave_request') }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            name="start_date"
                            min="{{ date('Y-m-d') }}"
                            required
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-indigo-600"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">End Date</label>
                        <input
                            type="date"
                            name="end_date"
                            min="{{ date('Y-m-d') }}"
                            required
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-indigo-600"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Reason</label>
                        <select
                            name="reason"
                            required
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-indigo-600"
                        >
                            <option value="">Select reason</option>
                            <option value="Festival / Annual Vacation">Festival / Annual Vacation</option>
                            <option value="Warehouse Inventory Stock Audit">Warehouse Inventory Stock Audit</option>
                            <option value="Operational Renovation / Relocation">Operational Renovation / Relocation</option>
                            <option value="Emergency Medical / Personal Leave">Emergency Medical / Personal Leave</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs tracking-wide transition shadow-2xs"
                    >
                        Submit Leave Request
                    </button>
                </form>
            </div>

            <!-- Help and Policy Support Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-3">
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                    Need Clarification?
                </h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    If you have questions regarding new platform compliance, dispatch standards, or commission guidelines, contact our merchant helpdesk.
                </p>
                <div class="pt-2">
                    <a href="{{ route('seller.support.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline flex items-center gap-1">
                        <span>Open a Support Ticket</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
