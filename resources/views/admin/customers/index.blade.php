@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Customer Management'])

@section('content')
<div class="space-y-6">

    <!-- Page Header -->
    <x-page-header 
        title="Customer Management" 
        subtitle="Customer directory, purchasing analytics, account governance, security access, and dispute escalations."
        :breadcrumbs="['Command Central' => route('admin.dashboard'), 'Customers' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                {{ number_format($metrics['total_customers']) }} Registered
            </span>
        </x-slot>

        <x-slot name="actions">
            @can('export', \App\Models\Customer::class)
                <a 
                    href="{{ route('admin.customers.export', request()->query()) }}" 
                    class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition border border-slate-200 dark:border-slate-700"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            @endcan
        </x-slot>
    </x-page-header>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card 
            label="Total Customers" 
            :value="number_format($metrics['total_customers'])" 
            change="Registered Accounts" 
            changeType="neutral" 
            color="primary"
        />
        <x-stat-card 
            label="Active Accounts" 
            :value="number_format($metrics['active_customers'])" 
            change="Verified Shoppers" 
            changeType="up" 
            color="emerald"
        />
        <x-stat-card 
            label="Restricted Accounts" 
            :value="number_format($metrics['blocked_customers'])" 
            change="Under Policy Restriction" 
            changeType="neutral" 
            color="rose"
        />
        <x-stat-card 
            label="Total Platform Spend" 
            :value="'₹' . number_format($metrics['total_revenue'], 2)" 
            change="Cumulative GMV" 
            changeType="up" 
            color="sky"
        />
    </div>

    <!-- 3 Status Tabs Bar -->
    <div class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 rounded-2xl p-1.5 shadow-xs">
        <nav class="flex space-x-1 sm:space-x-1.5 overflow-x-auto scrollbar-thin" aria-label="Customer status tabs">
            @php
                $tabs = [
                    'all' => ['label' => 'All Customers', 'count' => $tabCounts['all']],
                    'active' => ['label' => 'Active Accounts', 'count' => $tabCounts['active']],
                    'restricted' => ['label' => 'Restricted / Suspended', 'count' => $tabCounts['restricted']],
                ];
            @endphp

            @foreach($tabs as $tabKey => $tab)
                @php
                    $isActive = $activeTab === $tabKey;
                    $url = route('admin.customers.index', array_merge(request()->except(['tab', 'page']), ['tab' => $tabKey]));
                @endphp
                <a 
                    href="{{ $url }}" 
                    class="px-3.5 py-2 rounded-xl text-xs transition-all flex items-center space-x-2 shrink-0 font-medium {{ $isActive ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                >
                    <span>{{ $tab['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold border transition-colors {{ $isActive ? 'bg-emerald-700/80 text-white border-transparent' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700' }}">
                        {{ number_format($tab['count']) }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Filters & Search Bar Card -->
    <x-card padding="p-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs">
            <input type="hidden" name="tab" value="{{ $activeTab }}">

            <!-- Left: Search input -->
            <div class="flex-1 max-w-md relative">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by Customer ID, name, email, or phone..." 
                    class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Right: Sort & Date Range Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <div>
                    <select 
                        name="sort" 
                        class="py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Latest Registered</option>
                        <option value="spend_desc" {{ request('sort') === 'spend_desc' ? 'selected' : '' }}>Highest Spend (LTV)</option>
                        <option value="orders_desc" {{ request('sort') === 'orders_desc' ? 'selected' : '' }}>Most Orders Placed</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest Registered</option>
                    </select>
                </div>

                <div class="flex items-center space-x-1">
                    <input 
                        type="date" 
                        name="date_from" 
                        value="{{ request('date_from') }}" 
                        class="py-1.5 px-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                        title="Registered From Date"
                    />
                    <span class="text-slate-400">&ndash;</span>
                    <input 
                        type="date" 
                        name="date_to" 
                        value="{{ request('date_to') }}" 
                        class="py-1.5 px-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                        title="Registered To Date"
                    />
                </div>

                <button 
                    type="submit" 
                    class="px-3.5 py-2 text-xs font-bold rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition"
                >
                    Filter
                </button>

                @if(request()->hasAny(['search', 'date_from', 'date_to', 'sort']) && (request('search') || request('date_from') || request('date_to') || request('sort') !== 'latest'))
                    <a 
                        href="{{ route('admin.customers.index', ['tab' => $activeTab]) }}" 
                        class="px-2.5 py-2 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition"
                    >
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Customers Directory Table Card -->
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4">Customer ID</th>
                        <th class="py-3 px-4">Name</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4 text-center">Registration Date</th>
                        <th class="py-3 px-4 text-center">Order Count</th>
                        <th class="py-3 px-4 text-right">Total Purchase Value</th>
                        <th class="py-3 px-4 text-center">Last Order Date</th>
                        <th class="py-3 px-4 text-center">Account Status</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($customers as $c)
                        @php
                            $uStatus = $c->user?->status;
                            $statusStr = $uStatus instanceof \App\Enums\UserStatus ? $uStatus->value : (string) ($uStatus ?? 'active');
                            $statusLabel = $uStatus instanceof \App\Enums\UserStatus ? $uStatus->label() : ucfirst($statusStr);
                            $badgeClass = $uStatus instanceof \App\Enums\UserStatus 
                                ? $uStatus->badgeClass() 
                                : ($statusStr === 'active' 
                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300' 
                                    : 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300');
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition-colors">
                            <!-- Customer ID -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <a href="{{ route('admin.customers.show', $c) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">
                                    #{{ $c->id }}
                                </a>
                            </td>

                            <!-- Name with initial avatar -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                        {{ substr($c->user?->name ?? 'G', 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.customers.show', $c) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                            {{ $c->user?->name ?? 'Guest Customer' }}
                                        </a>
                                        @if($c->defaultAddress)
                                            <div class="text-[10px] text-slate-400">{{ $c->defaultAddress->city }}, {{ $c->defaultAddress->state }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $c->user?->email ?? 'N/A' }}
                            </td>

                            <!-- Phone -->
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $c->user?->phone ?? 'Not provided' }}
                            </td>

                            <!-- Registration Date -->
                            <td class="py-3.5 px-4 text-center font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $c->created_at->format('M d, Y') }}
                            </td>

                            <!-- Order Count -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                    {{ number_format((int) ($c->orders_count ?? 0)) }}
                                </span>
                            </td>

                            <!-- Total Purchase Value (LTV) -->
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                ₹{{ number_format((float) ($c->total_purchase_value ?? 0), 2) }}
                            </td>

                            <!-- Last Order Date -->
                            <td class="py-3.5 px-4 text-center font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                @if($c->last_order_date)
                                    {{ date('M d, Y', strtotime((string)$c->last_order_date)) }}
                                @else
                                    <span class="text-slate-400 font-sans text-[11px]">Never placed</span>
                                @endif
                            </td>

                            <!-- Account Status -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <a 
                                    href="{{ route('admin.customers.show', $c) }}" 
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition"
                                >
                                    Dossier &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                No customers matching the selected filters found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-3">
            <x-pagination :paginator="$customers" />
        </div>
    </x-card>

</div>
@endsection
