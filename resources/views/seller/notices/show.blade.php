@extends('layouts.dashboard', ['portal' => 'seller', 'title' => $notice->title . ' - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('seller.notices.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Notices</span>
        </a>

        @if($notice->attachment_path)
            <a href="{{ route('seller.notices.download', $notice) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-2xs transition">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download Attachment</span>
            </a>
        @endif
    </div>

    <!-- Notice Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        @if($notice->banner_image)
            <div class="w-full max-h-72 overflow-hidden bg-slate-100 border-b border-slate-200 flex items-center justify-center">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($notice->banner_image) }}" alt="{{ $notice->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <div class="p-6 sm:p-8 space-y-6">
            <!-- Header & Metadata -->
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider
                        {{ $notice->category_badge_color === 'rose' ? 'bg-rose-100 text-rose-700 border border-rose-200' :
                          ($notice->category_badge_color === 'amber' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                          ($notice->category_badge_color === 'sky' ? 'bg-sky-100 text-sky-800 border border-sky-200' :
                          ($notice->category_badge_color === 'emerald' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                          'bg-slate-100 text-slate-700 border border-slate-200'))) }}">
                        {{ $notice->category_label }}
                    </span>

                    @if($notice->priority === 'urgent')
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-black uppercase tracking-wider bg-rose-600 text-white">
                            Urgent Priority
                        </span>
                    @elseif($notice->priority === 'high')
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-amber-500 text-white">
                            High Priority
                        </span>
                    @endif

                    <span class="text-xs text-slate-400 font-medium">
                        Published on {{ $notice->published_at ? $notice->published_at->format('F d, Y \a\t h:i A') : 'Active' }}
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $notice->title }}
                </h1>

                <div class="flex items-center gap-2 text-xs text-slate-500 pt-1">
                    <span class="font-semibold text-slate-700">Issued by:</span>
                    <span>Platform Administration</span>
                    @if($notice->author)
                        <span class="text-slate-400">({{ $notice->author->name }})</span>
                    @endif
                </div>
            </div>

            <!-- Summary Box -->
            @if($notice->summary)
                <div class="p-4 rounded-xl bg-slate-50 border-l-4 border-indigo-600 text-xs sm:text-sm text-slate-700 font-medium leading-relaxed">
                    {{ $notice->summary }}
                </div>
            @endif

            <!-- Body Content -->
            <div class="prose prose-slate max-w-none text-xs sm:text-sm text-slate-700 leading-relaxed space-y-4 pt-2">
                {!! nl2br(e($notice->content)) !!}
            </div>

            <!-- Attachment Box -->
            @if($notice->attachment_path)
                <div class="mt-8 p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900">
                                {{ $notice->attachment_name ?: 'Official Notice Attachment' }}
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Official signed policy document / compliance guidelines
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('seller.notices.download', $notice) }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition shrink-0">
                        Download File
                    </a>
                </div>
            @endif
        </div>

        <!-- Card Footer -->
        <div class="px-6 sm:px-8 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">
                Notice ID: ANC-{{ str_pad((string)$notice->id, 5, '0', STR_PAD_LEFT) }}
            </span>
            <a href="{{ route('seller.notices.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">
                &larr; Back to all announcements
            </a>
        </div>
    </div>
</div>
@endsection
