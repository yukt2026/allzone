@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Global Inventory Overview'])

@section('content')
<div class="space-y-6" x-data="{ adjustModalOpen: false, selectedInventory: null }">

    <!-- Header -->
    <x-page-header 
        title="Global Marketplace Inventory" 
        subtitle="Platform-wide inventory health audit, dark store balances, stock movement ledger, and seller warehouse levels."
        :breadcrumbs="['Command Central' => route('admin.dashboard'), 'Global Inventory' => '#']"
    >
        <x-slot name="badge">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                {{ number_format($metrics['total_items']) }} Tracked SKU Allocations
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                @if($tab === 'movements')
                    <a 
                        href="{{ route('admin.inventory.movements.export', request()->query()) }}" 
                        class="px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export Movements CSV</span>
                    </a>
                @else
                    <a 
                        href="{{ route('admin.inventory.export', request()->query()) }}" 
                        class="px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export CSV</span>
                    </a>
                @endif

                <a 
                    href="{{ route('admin.inventory.sync_logs') }}" 
                    class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xl text-xs font-bold shadow-xs flex items-center space-x-1.5 transition"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>POS Sync Logs</span>
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Overview KPI Metrics (7 stats) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- 1. Total SKUs -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-slate-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Total SKUs</span>
            <div class="text-xl font-black font-mono text-slate-900 dark:text-white">{{ number_format($metrics['total_skus']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Catalog variants</div>
        </div>

        <!-- 2. Total Physical Units -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-emerald-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Physical Units</span>
            <div class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">{{ number_format($metrics['total_on_hand']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">On-hand inventory</div>
        </div>

        <!-- 3. Available Stock -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-teal-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Available Stock</span>
            <div class="text-xl font-black font-mono text-teal-600 dark:text-teal-400">{{ number_format($metrics['available_stock']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Ready for checkout</div>
        </div>

        <!-- 4. Cart Locked Units -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-amber-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Cart Locked Units</span>
            <div class="text-xl font-black font-mono text-amber-500">{{ number_format($metrics['total_reserved']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Active reservations</div>
        </div>

        <!-- 5. Low Stock -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-orange-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Low Stock</span>
            <div class="text-xl font-black font-mono text-orange-600 dark:text-orange-400">{{ number_format($metrics['low_stock']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">&le; Threshold</div>
        </div>

        <!-- 6. Out of Stock -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 border-l-rose-500">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Stocked-Out SKUs</span>
            <div class="text-xl font-black font-mono text-rose-600 dark:text-rose-400">{{ number_format($metrics['out_of_stock']) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Zero available balance</div>
        </div>

        <!-- 7. Discrepancies -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs border-l-4 {{ $metrics['discrepancies'] > 0 ? 'border-l-red-600 bg-red-50/20 dark:bg-red-950/20' : 'border-l-slate-300' }}">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Discrepancies</span>
            <div class="text-xl font-black font-mono {{ $metrics['discrepancies'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-400' }}">
                {{ number_format($metrics['discrepancies']) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Requires audit</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-4">
            <a 
                href="{{ route('admin.inventory.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'registry'])) }}"
                class="py-3 px-3 border-b-2 text-xs font-bold transition flex items-center space-x-2 {{ $tab === 'registry' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span>Stock Registry</span>
            </a>

            <a 
                href="{{ route('admin.inventory.index', array_merge(request()->except(['tab', 'page']), ['tab' => 'movements'])) }}"
                class="py-3 px-3 border-b-2 text-xs font-bold transition flex items-center space-x-2 {{ $tab === 'movements' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span>Stock Movement Ledger</span>
            </a>

            <a 
                href="{{ route('admin.inventory.index', array_merge(request()->except(['tab', 'page', 'stock_status']), ['tab' => 'discrepancies'])) }}"
                class="py-3 px-3 border-b-2 text-xs font-bold transition flex items-center space-x-2 {{ $tab === 'discrepancies' ? 'border-red-600 text-red-600 dark:text-red-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300' }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Stock Discrepancies</span>
                @if($metrics['discrepancies'] > 0)
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300">
                        {{ $metrics['discrepancies'] }}
                    </span>
                @endif
            </a>
        </nav>
    </div>

    <!-- Filter Card -->
    <x-card padding="p-4 sm:p-5">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="space-y-4">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Search input -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Search Item / SKU / Seller
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="{{ $tab === 'movements' ? 'SKU, note, or admin name...' : 'Product title, SKU, location, or merchant...' }}" 
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>
                </div>

                <!-- Seller Filter -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                        Merchant Seller
                    </label>
                    <select 
                        name="seller_id" 
                        class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                    >
                        <option value="">All Merchants</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                {{ $seller->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($tab === 'movements')
                    <!-- Movement Type Filter -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Movement Type
                        </label>
                        <select 
                            name="type" 
                            class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        >
                            <option value="">All Movement Types</option>
                            @foreach(\App\Enums\StockMovementType::cases() as $movementType)
                                <option value="{{ $movementType->value }}" {{ request('type') === $movementType->value ? 'selected' : '' }}>
                                    {{ $movementType->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            From Date
                        </label>
                        <input 
                            type="date" 
                            name="from_date" 
                            value="{{ request('from_date') }}" 
                            class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            To Date
                        </label>
                        <input 
                            type="date" 
                            name="to_date" 
                            value="{{ request('to_date') }}" 
                            class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>
                @else
                    <!-- Category Filter -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Category
                        </label>
                        <select 
                            name="category_id" 
                            class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        >
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Health Status -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
                            Stock Health
                        </label>
                        <select 
                            name="stock_status" 
                            class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        >
                            <option value="">All Health States</option>
                            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock Warning</option>
                            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                            <option value="discrepancy" {{ request('stock_status') === 'discrepancy' ? 'selected' : '' }}>Discrepancy (Audit Alert)</option>
                        </select>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-end space-x-2">
                        <button 
                            type="submit" 
                            class="flex-1 py-2 px-3 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-all"
                        >
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'seller_id', 'category_id', 'location_type', 'stock_status', 'quick_available', 'type', 'from_date', 'to_date']))
                            <a 
                                href="{{ route('admin.inventory.index', ['tab' => $tab]) }}" 
                                class="py-2 px-3 text-xs font-semibold rounded-xl bg-slate-200 dark:bg-slate-750 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-all"
                                title="Reset filters"
                            >
                                Reset
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if($tab === 'movements')
                <div class="flex justify-end space-x-2 pt-2">
                    <button 
                        type="submit" 
                        class="py-2 px-5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-all"
                    >
                        Filter Movements
                    </button>
                    @if(request()->hasAny(['search', 'seller_id', 'type', 'from_date', 'to_date', 'inventory_id']))
                        <a 
                            href="{{ route('admin.inventory.index', ['tab' => 'movements']) }}" 
                            class="py-2 px-3 text-xs font-semibold rounded-xl bg-slate-200 dark:bg-slate-750 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-all"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            @endif
        </form>
    </x-card>

    @if($tab === 'movements')
        <!-- Stock Movement Ledger Table -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Stock Movement Ledger</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Immutable chronological record of restocks, sales, reservations, returns, and adjustments.</p>
                </div>
                <span class="text-xs font-mono text-slate-500">
                    {{ $movements->total() }} recorded entries
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 dark:text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800 font-semibold">
                        <tr>
                            <th class="py-3.5 px-4">Ref #</th>
                            <th class="py-3.5 px-4">Product &amp; SKU</th>
                            <th class="py-3.5 px-4">Seller &amp; Location</th>
                            <th class="py-3.5 px-4">Movement Type</th>
                            <th class="py-3.5 px-3 text-center">Delta Quantity</th>
                            <th class="py-3.5 px-4">Authorization Reason / Notes</th>
                            <th class="py-3.5 px-4">Actor</th>
                            <th class="py-3.5 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($movements as $m)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition">
                                <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]">
                                    #{{ $m->id }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white line-clamp-1">
                                        {{ $m->inventory?->variant?->product?->title ?? 'N/A' }}
                                    </div>
                                    <div class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        {{ $m->inventory?->variant?->sku ?? 'No SKU' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-900 dark:text-white font-medium">
                                        {{ $m->inventory?->location?->seller?->business_name ?? 'ALLZONE Direct' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $m->inventory?->location?->name ?? 'Default Location' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $mType = $m->type instanceof \App\Enums\StockMovementType ? $m->type : \App\Enums\StockMovementType::tryFrom((string)$m->type);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ring-1 ring-inset {{ $mType?->badgeClass() ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $mType?->label() ?? (string)$m->type }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-center font-mono font-bold {{ $m->quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $m->quantity > 0 ? '+' . number_format($m->quantity) : number_format($m->quantity) }}
                                </td>
                                <td class="py-3.5 px-4 max-w-xs text-slate-600 dark:text-slate-300">
                                    <div class="line-clamp-2 text-xs">
                                        {{ $m->note ?? 'Standard inventory event' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                    <div class="font-semibold">{{ $m->creator?->name ?? 'System Event' }}</div>
                                    @if($m->creator?->email)
                                        <div class="text-[10px] text-slate-400">{{ $m->creator->email }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-[11px] text-slate-400">
                                    {{ $m->created_at?->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <x-empty-state 
                                        title="No stock movements recorded" 
                                        description="No stock movements match the specified query filters."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800/80">
                <x-pagination :paginator="$movements" />
            </div>
        </div>
    @else
        <!-- Global Inventory Table Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl shadow-sm overflow-hidden">
            @if($tab === 'discrepancies')
                <div class="p-4 bg-red-50/50 dark:bg-red-950/20 border-b border-red-200/60 dark:border-red-900/40 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <h3 class="text-xs font-bold text-red-800 dark:text-red-300">Stock Discrepancy Audit</h3>
                            <p class="text-[11px] text-red-600 dark:text-red-400">SKUs where physical on-hand is less than reserved allocations or negative values detected.</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold font-mono text-red-700 bg-red-100 dark:bg-red-950 px-2.5 py-1 rounded-xl">
                        {{ $inventories->total() }} Alert Items
                    </span>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 dark:text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800 font-semibold">
                        <tr>
                            <th class="py-3.5 px-4">Merchant Seller</th>
                            <th class="py-3.5 px-4">Product &amp; Category</th>
                            <th class="py-3.5 px-4">SKU &amp; Variation</th>
                            <th class="py-3.5 px-3 text-center">Available</th>
                            <th class="py-3.5 px-3 text-center">Reserved</th>
                            <th class="py-3.5 px-3 text-center">Current Stock</th>
                            <th class="py-3.5 px-3 text-center">Threshold</th>
                            <th class="py-3.5 px-4 text-center">Last Updated</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($inventories as $inv)
                            @php
                                $available = $inv->quantity_on_hand - $inv->quantity_reserved;
                                $isLow = ($available > 0 && $available <= $inv->low_stock_threshold);
                                $isOut = ($available <= 0);
                                $isDiscrepancy = ($inv->quantity_on_hand < $inv->quantity_reserved || $inv->quantity_on_hand < 0 || $inv->quantity_reserved < 0);
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-850/50 transition {{ $isDiscrepancy ? 'bg-red-50/20 dark:bg-red-950/10' : '' }}">
                                <!-- Seller -->
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                    @if($inv->location?->seller)
                                        <a href="{{ route('admin.sellers.show', $inv->location->seller) }}" class="hover:text-emerald-600 transition flex items-center space-x-1">
                                            <span>{{ $inv->location->seller->business_name }}</span>
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span>{{ setting('name', 'ALLZONE') }} Direct</span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 font-normal">
                                        {{ $inv->location?->name ?? 'Default Location' }}
                                    </div>
                                </td>

                                <!-- Product & Category -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white line-clamp-1">
                                        {{ $inv->variant?->product?->title ?? 'N/A' }}
                                    </div>
                                    @if($inv->variant?->product?->category)
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 mt-0.5">
                                            {{ $inv->variant->product->category->name }}
                                        </span>
                                    @endif
                                </td>

                                <!-- SKU & Variation -->
                                <td class="py-3.5 px-4">
                                    <div class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ $inv->variant?->sku ?? 'No SKU' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        {{ $inv->variant?->name ?? 'Default Variant' }}
                                    </div>
                                </td>

                                <!-- Available Quantity -->
                                <td class="py-3.5 px-3 text-center font-mono font-bold {{ $isOut ? 'text-rose-600 dark:text-rose-400' : ($isLow ? 'text-amber-600' : 'text-emerald-600 dark:text-emerald-400') }}">
                                    {{ number_format(max(0, $available)) }}
                                    @if($isDiscrepancy)
                                        <span class="block text-[9px] text-red-500 font-bold uppercase">Negative Alloc</span>
                                    @endif
                                </td>

                                <!-- Reserved Quantity -->
                                <td class="py-3.5 px-3 text-center font-mono font-bold text-amber-600">
                                    {{ number_format($inv->quantity_reserved) }}
                                </td>

                                <!-- Current Stock (On Hand) -->
                                <td class="py-3.5 px-3 text-center font-mono font-black text-slate-900 dark:text-white">
                                    {{ number_format($inv->quantity_on_hand) }}
                                </td>

                                <!-- Low Stock Threshold -->
                                <td class="py-3.5 px-3 text-center font-mono text-slate-500 text-xs">
                                    {{ number_format($inv->low_stock_threshold) }}
                                </td>

                                <!-- Last Updated Date -->
                                <td class="py-3.5 px-4 text-center font-mono text-[11px] text-slate-400">
                                    {{ $inv->updated_at?->format('M d, Y H:i') ?? 'N/A' }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <!-- Adjustment Button -->
                                        <button 
                                            type="button"
                                            @click="selectedInventory = {
                                                id: {{ $inv->id }},
                                                sku: '{{ addslashes($inv->variant?->sku ?? 'N/A') }}',
                                                product: '{{ addslashes($inv->variant?->product?->title ?? 'N/A') }}',
                                                seller: '{{ addslashes($inv->location?->seller?->business_name ?? 'ALLZONE Direct') }}',
                                                location: '{{ addslashes($inv->location?->name ?? 'Default') }}',
                                                onHand: {{ $inv->quantity_on_hand }},
                                                reserved: {{ $inv->quantity_reserved }},
                                                available: {{ $available }}
                                            }; adjustModalOpen = true;"
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition"
                                            title="Authorized Stock Adjustment"
                                        >
                                            Adjust
                                        </button>

                                        <!-- History link -->
                                        <a 
                                            href="{{ route('admin.inventory.index', ['tab' => 'movements', 'inventory_id' => $inv->id]) }}"
                                            class="px-2 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 transition"
                                            title="View Stock Movement Ledger"
                                        >
                                            History
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center">
                                    <x-empty-state 
                                        title="No global inventory balances match criteria" 
                                        description="Try clearing search keywords, merchant selection, or stock health filters."
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800/80">
                <x-pagination :paginator="$inventories" />
            </div>
        </div>
    @endif

    <!-- Stock Adjustment Modal -->
    <div 
        x-show="adjustModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" 
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div 
                x-show="adjustModalOpen" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" 
                @click="adjustModalOpen = false"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div 
                x-show="adjustModalOpen" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <div class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Authorized Stock Adjustment</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Reconcile physical inventory balance with mandatory audit trail.</p>
                        </div>
                    </div>
                    <button @click="adjustModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="selectedInventory">
                    <form 
                        :action="'{{ url('admin/inventory') }}/' + selectedInventory.id + '/adjust'" 
                        method="POST" 
                        class="mt-4 space-y-4"
                    >
                        @csrf

                        <!-- Item Summary Box -->
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold uppercase text-[10px]">Product</span>
                                <span class="font-bold text-slate-900 dark:text-white" x-text="selectedInventory.product"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold uppercase text-[10px]">SKU</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="selectedInventory.sku"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400 font-semibold uppercase text-[10px]">Seller / Location</span>
                                <span class="text-slate-700 dark:text-slate-300" x-text="selectedInventory.seller + ' (' + selectedInventory.location + ')'"></span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700">
                                <span class="text-slate-500">Current On Hand: <strong class="text-slate-900 dark:text-white" x-text="selectedInventory.onHand"></strong></span>
                                <span class="text-slate-500">Reserved: <strong class="text-amber-600" x-text="selectedInventory.reserved"></strong></span>
                            </div>
                        </div>

                        <!-- New Quantity Input -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                New Quantity On Hand <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                name="new_quantity_on_hand" 
                                min="0" 
                                required 
                                :value="selectedInventory.onHand"
                                class="w-full px-3.5 py-2 text-sm font-mono rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Available balance will be recalculated as (New On Hand - Reserved Quantity).</p>
                        </div>

                        <!-- Authorization Reason -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Authorization Reason &amp; Audit Note <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                name="reason" 
                                rows="3" 
                                required 
                                placeholder="State the business rationale (e.g. Warehouse cycle count discrepancy, damaged stock write-off, or return audit adjustment)..."
                                class="w-full px-3.5 py-2 text-xs rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <!-- Compliance Notice -->
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl text-[11px] text-amber-800 dark:text-amber-300 flex items-start space-x-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>
                                <strong>Audit Requirement:</strong> Silent modifications are prohibited. This adjustment will write an immutable record to the Stock Movement Ledger with your Admin ID ({{ auth()->user()->name ?? 'Admin' }}) and the specified reason.
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button 
                                type="button" 
                                @click="adjustModalOpen = false" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition"
                            >
                                Commit Stock Adjustment
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
