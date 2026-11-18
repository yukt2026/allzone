@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Read Status - ' . $announcement->title])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <x-page-header 
        title="Notice Read Status" 
        :subtitle="'Auditing seller engagement for: ' . $announcement->title"
        :breadcrumbs="[
            'Admin' => route('admin.dashboard'),
            'Announcements' => route('admin.announcements.index'),
            'Read Audit' => '#'
        ]"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                {{ $announcement->reads->count() }} Reads
            </span>
        </x-slot>

        <x-slot name="actions">
            <a 
                href="{{ route('admin.announcements.index') }}" 
                class="px-4 py-2 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition"
            >
                &larr; Back to Notices
            </a>
        </x-slot>
    </x-page-header>

    <!-- Notice Summary Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-{{ $announcement->priority_badge_color }}-100 text-{{ $announcement->priority_badge_color }}-800">
                    {{ ucfirst($announcement->priority) }} Priority
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-{{ $announcement->category_badge_color }}-100 text-{{ $announcement->category_badge_color }}-800">
                    {{ $announcement->category_label }}
                </span>
                <span class="text-xs text-slate-400">Target: <strong class="text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $announcement->target_type)) }}</strong></span>
            </div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white mt-1.5">{{ $announcement->title }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Published on {{ $announcement->published_at?->format('d M Y, h:i A') ?? 'Draft' }}</p>
        </div>

        <div class="text-right">
            <span class="text-xs text-slate-400 uppercase font-semibold">Total Reads Recorded</span>
            <div class="text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400">{{ $announcement->reads->count() }}</div>
        </div>
    </div>

    <!-- Reads Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Confirmed Seller Reads</h3>
            <span class="text-xs text-slate-400">{{ $announcement->reads->count() }} merchant(s) viewed</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                    <tr>
                        <th class="px-6 py-3.5">Seller Business Name</th>
                        <th class="px-6 py-3.5">Owner / Contact</th>
                        <th class="px-6 py-3.5">Seller Type</th>
                        <th class="px-6 py-3.5 text-right">Read Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($announcement->reads as $read)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    {{ $read->seller?->business_name ?? 'N/A' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">ID: #{{ $read->seller_id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-800 dark:text-slate-200 font-medium">{{ $read->seller?->user?->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $read->seller?->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ is_object($read->seller?->seller_type) ? $read->seller?->seller_type->value : (string) $read->seller?->seller_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                    {{ $read->read_at?->format('d M Y, h:i A') ?? $read->created_at?->format('d M Y, h:i A') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                No sellers have confirmed reading this notice yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
