@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Notices & Announcements - ALLZONE Admin'])

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <x-page-header 
        title="Seller Notices & Announcements" 
        subtitle="Publish, schedule, and target policy notices, festival events, and operational guidelines across sellers."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Announcements' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                {{ $metrics['total'] ?? 0 }} Total
            </span>
        </x-slot>

        <x-slot name="actions">
            <a 
                href="{{ route('admin.announcements.create') }}" 
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg shadow-sm flex items-center space-x-1.5 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Create Announcement</span>
            </a>
        </x-slot>
    </x-page-header>

    <!-- Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Notices</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Published &amp; Active</div>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $metrics['published'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Scheduled / Future</div>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $metrics['scheduled'] }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Drafts / Offline</div>
            <div class="text-2xl font-bold text-slate-600 dark:text-slate-300 mt-1">{{ $metrics['drafts'] }}</div>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <form method="GET" action="{{ route('admin.announcements.index') }}" class="flex flex-wrap items-center gap-2.5 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search by title, summary, or keyword..." 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white pl-8 pr-3 py-2 focus:ring-1 focus:ring-indigo-500"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-2 px-3">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published Only</option>
                <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Scheduled Only</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Drafts Only</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired Only</option>
            </select>

            <select name="category" class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-2 px-3">
                <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
                <option value="important" {{ $category === 'important' ? 'selected' : '' }}>Important Notices</option>
                <option value="general" {{ $category === 'general' ? 'selected' : '' }}>General Announcements</option>
                <option value="policy" {{ $category === 'policy' ? 'selected' : '' }}>Policy Updates</option>
                <option value="logistics" {{ $category === 'logistics' ? 'selected' : '' }}>Logistics &amp; SLA</option>
                <option value="feature" {{ $category === 'feature' ? 'selected' : '' }}>Platform Features</option>
            </select>

            <select name="priority" class="text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white py-2 px-3">
                <option value="all" {{ $priority === 'all' ? 'selected' : '' }}>All Priorities</option>
                <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                Filter
            </button>
            @if($search || $status !== 'all' || $category !== 'all' || $priority !== 'all')
                <a href="{{ route('admin.announcements.index') }}" class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Announcements Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Title &amp; Notice</th>
                        <th class="py-3 px-4">Category &amp; Priority</th>
                        <th class="py-3 px-4">Target Sellers</th>
                        <th class="py-3 px-4">Status &amp; Schedule</th>
                        <th class="py-3 px-4 text-center">Reads</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($announcements as $announcement)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4 max-w-xs sm:max-w-md">
                                <div class="font-bold text-slate-900 dark:text-white line-clamp-1">
                                    {{ $announcement->title }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                                    {{ $announcement->summary ?: \Illuminate\Support\Str::limit(strip_tags($announcement->content), 80) }}
                                </div>
                                @if($announcement->attachment_path)
                                    <span class="inline-flex items-center gap-1 text-[10px] text-indigo-600 dark:text-indigo-400 mt-1 font-semibold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <span>Attachment: {{ $announcement->attachment_name ?: 'File' }}</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $announcement->category_badge_color === 'rose' ? 'bg-rose-100 text-rose-700 border border-rose-200' :
                                          ($announcement->category_badge_color === 'amber' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                                          ($announcement->category_badge_color === 'sky' ? 'bg-sky-100 text-sky-800 border border-sky-200' :
                                          ($announcement->category_badge_color === 'emerald' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                                          'bg-slate-100 text-slate-700 border border-slate-200'))) }}">
                                        {{ $announcement->category_label }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $announcement->priority === 'urgent' ? 'bg-rose-600 text-white' :
                                          ($announcement->priority === 'high' ? 'bg-amber-500 text-white' :
                                          'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300') }}">
                                        {{ ucfirst($announcement->priority) }} Priority
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-xs">
                                @if($announcement->target_type === 'all')
                                    <span class="font-bold text-slate-800 dark:text-slate-200">All Sellers</span>
                                @elseif($announcement->target_type === 'customers')
                                    <span class="font-bold text-purple-600 dark:text-purple-400">All Customers</span>
                                @elseif($announcement->target_type === 'seller_group')
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Group:</span>
                                    <span class="capitalize">{{ str_replace('_', ' ', $announcement->target_seller_type) }}</span>
                                @elseif($announcement->target_type === 'specific_sellers')
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">Specific:</span>
                                    <span>{{ is_array($announcement->target_seller_ids) ? count($announcement->target_seller_ids) : 0 }} Sellers</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div>
                                    @if(!$announcement->is_published)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200">
                                            Draft
                                        </span>
                                    @elseif($announcement->expires_at && $announcement->expires_at->isPast())
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                            Expired
                                        </span>
                                    @elseif($announcement->published_at && $announcement->published_at->isFuture())
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Scheduled: {{ $announcement->published_at->format('d M, h:i A') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Live &amp; Active
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">
                                    {{ $announcement->published_at ? 'Published: ' . $announcement->published_at->format('d M Y') : 'Created: ' . $announcement->created_at->format('d M Y') }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-center">
                                <a href="{{ route('admin.announcements.reads', $announcement) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 font-bold rounded-lg text-xs text-slate-700 dark:text-slate-300 transition" title="View read confirmation log">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>{{ $announcement->reads->count() }} Reads</span>
                                </a>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <form action="{{ route('admin.announcements.toggle', $announcement) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-md border transition {{ $announcement->is_published ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $announcement->is_published ? 'Unpublish' : 'Publish' }}
                                        </button>
                                    </form>

                                    @if($announcement->is_published && (!$announcement->expires_at || $announcement->expires_at->isFuture()))
                                        <form action="{{ route('admin.announcements.archive', $announcement) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-md border border-slate-200 text-slate-600 hover:bg-slate-100 transition" title="Archive / Expire notice">
                                                Archive
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" data-confirm="Are you sure you want to permanently delete this announcement?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-md text-rose-600 hover:bg-rose-50 transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                    <div class="text-sm font-bold text-slate-700 dark:text-slate-300">No Announcements Found</div>
                                    <p class="text-xs text-slate-500">Create your first seller announcement to broadcast updates platform-wide or to target seller groups.</p>
                                    <div class="pt-2">
                                        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-500 transition">
                                            Create Notice
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($announcements->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
