@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Platform Security & Sessions'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">

    <!-- Header -->
    <x-page-header 
        title="Platform Security & Session Management" 
        subtitle="Monitor active administrator sessions, track failed login attempts, inspect authentication logs, and revoke suspicious sessions."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Security Center' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                SOC Live Monitor
            </span>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 text-xs font-semibold flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Active Sessions</span>
                <span class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($metrics['active_sessions']) }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Live authenticated tokens</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Failed Logins (24h)</span>
                <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold {{ $metrics['failed_logins_24h'] > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white' }}">{{ number_format($metrics['failed_logins_24h']) }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Invalid password attempts</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Rate Lockouts (24h)</span>
                <span class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold {{ $metrics['lockouts_24h'] > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">{{ number_format($metrics['lockouts_24h']) }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Brute-force blocks triggered</span>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Unauthorized Roles (24h)</span>
                <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($metrics['unauthorized_roles_24h']) }}</div>
            <span class="text-[11px] text-slate-400 mt-1 block">Access denials by policy</span>
        </div>
    </div>

    <!-- Active Sessions Section -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Active User Sessions
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Currently active authenticated sessions across all devices.</p>
            </div>
            <div>
                <form method="POST" action="{{ route('admin.security.terminate_all_other') }}" onsubmit="return confirm('Revoke all other active sessions? Only this current browser session will remain logged in.')">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 transition">
                        Terminate Other Sessions
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4 font-semibold">User</th>
                        <th class="py-3 px-4 font-semibold">IP Address</th>
                        <th class="py-3 px-4 font-semibold">Browser / Device</th>
                        <th class="py-3 px-4 font-semibold">Last Active</th>
                        <th class="py-3 px-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($sessions as $s)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition {{ $s->is_current ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : '' }}">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    {{ $s->user_name ?: 'User #' . $s->user_id }}
                                    @if($s->is_current)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                                            Current Device
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400">{{ $s->user_email }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                                {{ $s->ip_address ?: 'Unknown IP' }}
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate text-[11px] text-slate-500" title="{{ $s->user_agent }}">
                                {{ $s->user_agent ?: 'Unknown User Agent' }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $s->last_seen }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if(!$s->is_current)
                                    <form method="POST" action="{{ route('admin.security.terminate_session', $s->id) }}" onsubmit="return confirm('Terminate this user session immediately?')">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 rounded border border-rose-200 dark:border-rose-800">
                                            Revoke
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-emerald-600 font-semibold">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                No active database sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security Incidents & Failed Logins Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Failed Login Monitoring -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Failed Logins & Access Denials
                </h3>
                <span class="text-[11px] font-medium text-slate-400">{{ $failedLogins->total() }} recorded</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800 text-[11px]">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold">Event</th>
                            <th class="py-2.5 px-3 font-semibold">Attempted Email</th>
                            <th class="py-2.5 px-3 font-semibold">IP</th>
                            <th class="py-2.5 px-3 font-semibold text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($failedLogins as $fl)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-2.5 px-3">
                                    @if($fl->event === 'failed_login')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                                            Failed Auth
                                        </span>
                                    @elseif($fl->event === 'account_lockout')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">
                                            Lockout
                                        </span>
                                    @elseif($fl->event === 'unauthorized_admin_access')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-400">
                                            Role Blocked
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $fl->event }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $fl->properties['attempted_email'] ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                    {{ $fl->properties['ip'] ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $fl->created_at?->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 text-xs">
                                    No security incidents or failed logins recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($failedLogins->hasPages())
                <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $failedLogins->links() }}
                </div>
            @endif
        </div>

        <!-- Authentication History -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Staff Authentication History
                </h3>
                <span class="text-[11px] font-medium text-slate-400">{{ $loginHistory->total() }} sign-ins</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 dark:bg-slate-800/40 text-slate-500 border-b border-slate-200 dark:border-slate-800 text-[11px]">
                        <tr>
                            <th class="py-2.5 px-3 font-semibold">User</th>
                            <th class="py-2.5 px-3 font-semibold">Event</th>
                            <th class="py-2.5 px-3 font-semibold">IP Address</th>
                            <th class="py-2.5 px-3 font-semibold text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($loginHistory as $lh)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-2.5 px-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $lh->causer?->name ?? 'System Process' }}
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ $lh->causer?->email }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300">
                                    {{ $lh->description }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500">
                                    {{ $lh->properties['ip'] ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $lh->created_at?->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 text-xs">
                                    No authentication events recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($loginHistory->hasPages())
                <div class="p-3 border-t border-slate-100 dark:border-slate-800">
                    {{ $loginHistory->links() }}
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
