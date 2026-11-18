@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Inventory Management - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    activeTab: '{{ $tab }}',
    selectedIds: [],
    selectAll: false,

    // Single Edit Modal
    editModal: false,
    selectedInv: null,
    editQty: 0,
    editThreshold: 5,
    editReason: '',
    editLoading: false,

    // Bulk Update Modal
    bulkModal: false,
    bulkType: 'add_stock',
    bulkQty: 10,
    bulkReason: '',
    bulkLoading: false,

    // Movement History Modal
    historyModal: false,
    historyData: null,
    historyLoading: false,

    // CSV Import Modal
    importModal: false,

    openEdit(inv) {
        this.selectedInv = inv;
        this.editQty = inv.quantity_on_hand;
        this.editThreshold = inv.low_stock_threshold;
        this.editReason = '';
        this.editModal = true;
    },

    saveSingleStock() {
        if (!this.selectedInv || this.editQty < 0) return;
        this.editLoading = true;
        fetch('/seller/inventory/' + this.selectedInv.id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                quantity_on_hand: this.editQty,
                low_stock_threshold: this.editThreshold,
                reason: this.editReason
            })
        })
        .then(res => res.json())
        .then(data => {
            this.editLoading = false;
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error updating stock');
            }
        })
        .catch(err => {
            this.editLoading = false;
            alert('Request failed: ' + err.message);
        });
    },

    toggleSelectAll(checked, allIds) {
        this.selectAll = checked;
        this.selectedIds = checked ? [...allIds] : [];
    },

    openBulkModal() {
        if (this.selectedIds.length === 0) {
            alert('Please select at least one SKU using the checkboxes.');
            return;
        }
        this.bulkModal = true;
    },

    saveBulkStock() {
        if (this.selectedIds.length === 0 || this.bulkQty < 0) return;
        this.bulkLoading = true;
        fetch('{{ route('seller.inventory.bulk_update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                inventory_ids: this.selectedIds,
                action_type: this.bulkType,
                quantity: this.bulkQty,
                reason: this.bulkReason
            })
        })
        .then(res => res.json())
        .then(data => {
            this.bulkLoading = false;
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Bulk update failed');
            }
        })
        .catch(err => {
            this.bulkLoading = false;
            alert('Request failed: ' + err.message);
        });
    },

    viewHistory(invId) {
        this.historyLoading = true;
        this.historyData = null;
        this.historyModal = true;
        fetch('/seller/inventory/' + invId + '/movements')
            .then(res => res.json())
            .then(data => {
                this.historyLoading = false;
                if (data.success) {
                    this.historyData = data;
                }
            })
            .catch(() => { this.historyLoading = false; });
    }
}">

    <!-- Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
                <span>&gt;</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Inventory Management</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Inventory &amp; Stock Balances</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-semibold border border-emerald-200 dark:border-emerald-800">
                    Live Stock Ledger
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Track on-hand balances, reserved orders, automated safety thresholds, and transaction-safe audit trails.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button 
                @click="openBulkModal()"
                type="button" 
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg bg-slate-900 dark:bg-slate-800 text-white font-semibold text-xs hover:bg-slate-800 dark:hover:bg-slate-700 transition shadow-2xs border border-transparent dark:border-slate-700"
            >
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Bulk Stock Update</span>
                <span x-show="selectedIds.length > 0" class="ml-1 px-1.5 py-0.2 bg-emerald-600 text-white rounded-full text-[10px]" x-text="selectedIds.length"></span>
            </button>

            <button 
                @click="importModal = true" 
                type="button" 
                class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span>Import CSV</span>
            </button>

            <a 
                href="{{ route('seller.inventory.export', array_merge(request()->query(), ['tab' => $tab])) }}" 
                class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>

            <a 
                href="{{ route('seller.products.create') }}" 
                class="inline-flex items-center space-x-1 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition"
            >
                <span>✚</span>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    <!-- Inventory Dashboard KPI Metrics (8 Metrics) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <!-- Total Products -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Products</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ number_format($metrics['total_products']) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Catalog items</div>
        </div>

        <!-- Total SKUs -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">Total SKUs</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ number_format($metrics['total_skus']) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Active variants</div>
        </div>

        <!-- Available Stock -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs bg-emerald-50/20 dark:bg-emerald-950/20">
            <span class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-400">Available</span>
            <div class="text-xl font-black text-emerald-800 dark:text-emerald-300 mt-0.5">{{ number_format($metrics['total_available']) }}</div>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">Ready to fulfill</div>
        </div>

        <!-- Reserved Stock -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-400">Reserved</span>
            <div class="text-xl font-black text-amber-800 dark:text-amber-300 mt-0.5">{{ number_format($metrics['total_reserved']) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Locked in orders</div>
        </div>

        <!-- In-Stock SKUs -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">In Stock</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">{{ number_format($metrics['in_stock_skus']) }}</div>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">Healthy levels</div>
        </div>

        <!-- Low-Stock SKUs -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border {{ $metrics['low_stock_skus'] > 0 ? 'border-amber-300 dark:border-amber-700 bg-amber-50/20 dark:bg-amber-950/20' : 'border-slate-200/90 dark:border-slate-800' }} p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold {{ $metrics['low_stock_skus'] > 0 ? 'text-amber-800 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500' }}">Low Stock</span>
            <div class="text-xl font-black {{ $metrics['low_stock_skus'] > 0 ? 'text-amber-900 dark:text-amber-300' : 'text-slate-900 dark:text-white' }} mt-0.5">
                {{ number_format($metrics['low_stock_skus']) }}
            </div>
            <div class="text-[10px] text-amber-700 dark:text-amber-400 mt-0.5">Below threshold</div>
        </div>

        <!-- Out-of-Stock SKUs -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border {{ $metrics['out_of_stock_skus'] > 0 ? 'border-rose-300 dark:border-rose-700 bg-rose-50/20 dark:bg-rose-950/20' : 'border-slate-200/90 dark:border-slate-800' }} p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold {{ $metrics['out_of_stock_skus'] > 0 ? 'text-rose-800 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500' }}">Out of Stock</span>
            <div class="text-xl font-black {{ $metrics['out_of_stock_skus'] > 0 ? 'text-rose-900 dark:text-rose-300' : 'text-slate-900 dark:text-white' }} mt-0.5">
                {{ number_format($metrics['out_of_stock_skus']) }}
            </div>
            <div class="text-[10px] text-rose-600 dark:text-rose-400 mt-0.5">0 units available</div>
        </div>

        <!-- Inventory Changes (30D) -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 p-3.5 shadow-2xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 dark:text-slate-500">30D Changes</span>
            <div class="text-xl font-black text-indigo-700 dark:text-indigo-400 mt-0.5">{{ number_format($metrics['inventory_changes_30d']) }}</div>
            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Ledger movements</div>
        </div>
    </div>

    <!-- Inventory Console: Tabs & Main Table -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
        <!-- Inventory Tabs Rail (7 Tabs) -->
        <div class="flex items-center space-x-6 border-b border-slate-200 dark:border-slate-800 px-5 pt-2 text-xs font-semibold overflow-x-auto">
            @php
                $tabConfig = [
                    'all' => ['label' => 'All Stock', 'count' => $counts['all'], 'color' => 'slate-900 dark:border-white dark:text-white'],
                    'active' => ['label' => 'Active', 'count' => $counts['active'], 'color' => 'emerald-600 dark:border-emerald-400 dark:text-emerald-400'],
                    'out_of_stock' => ['label' => 'Out of Stock', 'count' => $counts['out_of_stock'], 'color' => 'rose-600 dark:border-rose-400 dark:text-rose-400'],
                    'low_stock' => ['label' => 'Low Stock', 'count' => $counts['low_stock'], 'color' => 'amber-600 dark:border-amber-400 dark:text-amber-400'],
                    'pending' => ['label' => 'Pending Approval', 'count' => $counts['pending'], 'color' => 'indigo-600 dark:border-indigo-400 dark:text-indigo-400'],
                    'blocked' => ['label' => 'Blocked', 'count' => $counts['blocked'], 'color' => 'rose-700 dark:border-rose-400 dark:text-rose-400'],
                    'paused' => ['label' => 'Paused', 'count' => $counts['paused'], 'color' => 'slate-500 dark:border-slate-400 dark:text-slate-400'],
                ];
            @endphp

            @foreach($tabConfig as $key => $conf)
                <a 
                    href="{{ route('seller.inventory.index', array_merge(request()->except('tab'), ['tab' => $key])) }}" 
                    class="pb-3 border-b-2 transition whitespace-nowrap {{ $tab === $key ? 'border-' . $conf['color'] . ' text-' . $conf['color'] . ' font-bold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}"
                >
                    {{ $conf['label'] }} ({{ $conf['count'] }})
                </a>
            @endforeach
        </div>

        <!-- Filter Subbar -->
        <div class="p-3.5 bg-slate-50/50 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <form method="GET" action="{{ route('seller.inventory.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <select 
                    name="category_id" 
                    onchange="this.form.submit()" 
                    class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                @if($stores->count() > 1)
                    <select 
                    name="store_id" 
                    onchange="this.form.submit()" 
                    class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200"
                >
                    <option value="">All Outlets</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ (string)$storeId === (string)$st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
                @endif

                <select 
                    name="sort" 
                    onchange="this.form.submit()" 
                    class="py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200"
                >
                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Sort: Recently Updated</option>
                    <option value="stock_asc" {{ $sort === 'stock_asc' ? 'selected' : '' }}>Stock: Low to High</option>
                    <option value="stock_desc" {{ $sort === 'stock_desc' ? 'selected' : '' }}>Stock: High to Low</option>
                    <option value="sku_asc" {{ $sort === 'sku_asc' ? 'selected' : '' }}>SKU: A to Z</option>
                </select>

                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search SKU or Title..." 
                        class="py-1.5 pl-3 pr-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 w-52 sm:w-60"
                    />
                    <button type="submit" class="absolute right-2.5 top-2 text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Product Inventory Table -->
        @if($inventories->isEmpty())
            <div class="py-16 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-500 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800 dark:text-slate-200">No Inventory Records Found</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mt-1">
                    @if($search)
                        No items match your search term "{{ $search }}".
                    @elseif($tab !== 'all')
                        No SKUs currently match the "{{ $tabConfig[$tab]['label'] ?? $tab }}" tab.
                    @else
                        No inventory balances exist in your catalog. Add products or import a CSV to populate.
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 font-semibold text-[10px] uppercase border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3.5 w-8 text-center">
                                <input 
                                    type="checkbox" 
                                    @change="toggleSelectAll($event.target.checked, {{ Js::from($inventories->pluck('id')) }})"
                                    class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800"
                                >
                            </th>
                            <th class="p-3.5">Product &amp; SKU</th>
                            <th class="p-3.5">Variation</th>
                            <th class="p-3.5">Category</th>
                            <th class="p-3.5">Current Stock</th>
                            <th class="p-3.5">Reserved</th>
                            <th class="p-3.5">Available</th>
                            <th class="p-3.5">Threshold</th>
                            <th class="p-3.5">Status</th>
                            <th class="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($inventories as $inv)
                            @php
                                $variant = $inv->variant;
                                $product = $variant?->product;
                                $available = $inv->available_quantity;
                                $status = $product?->status;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition {{ $available <= 0 ? 'bg-rose-50/20 dark:bg-rose-950/20' : ($available <= $inv->low_stock_threshold ? 'bg-amber-50/20 dark:bg-amber-950/20' : '') }}">
                                <td class="p-3.5 text-center">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $inv->id }}" 
                                        x-model="selectedIds"
                                        class="rounded border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-800"
                                    >
                                </td>

                                <td class="p-3.5 max-w-xs">
                                    <div class="flex items-center space-x-3">
                                        <img 
                                            src="{{ $variant?->image_url ?? $product?->primary_image_url ?? asset('images/placeholder.png') }}" 
                                            alt="{{ $product?->title }}" 
                                            class="w-10 h-10 rounded-lg object-cover bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0"
                                            onerror="this.src='/images/placeholder.png'"
                                        />
                                        <div>
                                            <a href="{{ route('seller.products.edit', $product) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 line-clamp-1">
                                                {{ $product?->title ?? 'Product' }}
                                            </a>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">
                                                SKU: <strong class="text-slate-700 dark:text-slate-300">{{ $variant?->sku }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-3.5 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    {{ $variant?->name ?: 'Standard' }}
                                </td>

                                <td class="p-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $product?->category?->name ?? 'General' }}
                                </td>

                                <td class="p-3.5 font-bold text-slate-900 dark:text-white">
                                    {{ number_format($inv->quantity_on_hand) }}
                                </td>

                                <td class="p-3.5">
                                    @if($inv->quantity_reserved > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800" title="Reserved for pending customer orders">
                                             {{ $inv->quantity_reserved }} locked
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 text-xs">0</span>
                                    @endif
                                </td>

                                <td class="p-3.5">
                                    @if($available <= 0)
                                        <span class="font-extrabold text-rose-600 dark:text-rose-400 text-sm">0</span>
                                        <span class="ml-1 text-[9px] font-bold px-1.5 py-0.2 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800 uppercase">Out of Stock</span>
                                    @elseif($available <= $inv->low_stock_threshold)
                                        <span class="font-extrabold text-amber-700 dark:text-amber-400 text-sm">{{ $available }}</span>
                                        <span class="ml-1 text-[9px] font-bold px-1.5 py-0.2 rounded bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 uppercase">Low Stock</span>
                                    @else
                                        <span class="font-extrabold text-emerald-700 dark:text-emerald-400 text-sm">{{ $available }}</span>
                                    @endif
                                </td>

                                <td class="p-3.5 text-slate-500 dark:text-slate-400 font-semibold">
                                    {{ $inv->low_stock_threshold }}
                                </td>

                                <td class="p-3.5 whitespace-nowrap">
                                    @if($status)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $status->badgeClass() }}">
                                            {{ $status->label() }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                    @endif
                                </td>

                                <td class="p-3.5 text-right whitespace-nowrap space-x-1.5">
                                    <button 
                                        @click="openEdit({{ Js::from([
                                            'id' => $inv->id,
                                            'sku' => $variant?->sku,
                                            'title' => $product?->title,
                                            'quantity_on_hand' => $inv->quantity_on_hand,
                                            'quantity_reserved' => $inv->quantity_reserved,
                                            'low_stock_threshold' => $inv->low_stock_threshold,
                                        ]) }})"
                                        type="button" 
                                        class="px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-2xs"
                                    >
                                        Update Stock
                                    </button>

                                    <button 
                                        @click="viewHistory({{ $inv->id }})" 
                                        type="button" 
                                        class="px-2 py-1 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-xs font-semibold"
                                        title="View Stock Ledger History"
                                    >
                                        History
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $inventories->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Edit Stock Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800" @click.away="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Update Stock Level</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="selectedInv ? selectedInv.title + ' (' + selectedInv.sku + ')' : ''"></p>
                </div>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">On-Hand Stock Quantity *</label>
                    <input 
                        type="number" 
                        min="0"
                        x-model="editQty" 
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-600"
                    />
                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 flex items-center justify-between">
                        <span>Currently Reserved in Orders:</span>
                        <strong class="text-amber-700 dark:text-amber-400" x-text="selectedInv ? selectedInv.quantity_reserved + ' units' : '0'"></strong>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Low-Stock Alert Threshold *</label>
                    <input 
                        type="number" 
                        min="1"
                        x-model="editThreshold" 
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-xs font-semibold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-600"
                    />
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason for Adjustment</label>
                    <input 
                        type="text" 
                        x-model="editReason" 
                        placeholder="e.g. Physical inventory count, warehouse replenishment"
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-600"
                    />
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 space-y-1">
                    <div class="flex items-center justify-between">
                        <span>New Available Balance:</span>
                        <span class="font-extrabold text-sm" :class="(editQty - (selectedInv ? selectedInv.quantity_reserved : 0)) > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="Math.max(0, editQty - (selectedInv ? selectedInv.quantity_reserved : 0)) + ' units'"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button @click="editModal = false" type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg font-bold text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button 
                    @click="saveSingleStock()" 
                    :disabled="editLoading || editQty < 0"
                    type="button" 
                    class="px-4 py-2 bg-slate-900 dark:bg-emerald-600 text-white rounded-lg font-bold text-xs hover:bg-slate-800 dark:hover:bg-emerald-500 disabled:opacity-50"
                >
                    <span x-show="!editLoading">Save Stock</span>
                    <span x-show="editLoading">Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Stock Update Modal -->
    <div x-show="bulkModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800" @click.away="bulkModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Bulk Stock Update</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Adjust stock levels simultaneously across <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedIds.length"></span> selected SKUs.
                    </p>
                </div>
                <button @click="bulkModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Adjustment Action *</label>
                    <select 
                        x-model="bulkType" 
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-xs font-semibold text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-600"
                    >
                        <option value="add_stock">Add Stock (+ Units Inward Replenishment)</option>
                        <option value="set_stock">Set Exact Quantity (Override on-hand stock)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quantity *</label>
                    <input 
                        type="number" 
                        min="0"
                        x-model="bulkQty" 
                        placeholder="e.g. 25"
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm font-bold text-slate-900 dark:text-white focus:outline-none focus:border-emerald-600"
                    />
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Adjustment Reason (Audit Log)</label>
                    <input 
                        type="text" 
                        x-model="bulkReason" 
                        placeholder="e.g. Warehouse batch delivery received"
                        class="w-full p-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:border-emerald-600"
                    />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button @click="bulkModal = false" type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg font-bold text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Cancel
                </button>
                <button 
                    @click="saveBulkStock()" 
                    :disabled="bulkLoading || bulkQty < 0"
                    type="button" 
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold text-xs hover:bg-emerald-700 disabled:opacity-50"
                >
                    <span x-show="!bulkLoading">Apply Bulk Update</span>
                    <span x-show="bulkLoading">Updating...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Stock Ledger Movements Modal -->
    <div x-show="historyModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[85vh] flex flex-col" @click.away="historyModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Stock Movement Audit Trail</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="historyData ? historyData.product_title + ' (' + historyData.sku + ')' : 'Loading history...'"></p>
                </div>
                <button @click="historyModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <div class="overflow-y-auto flex-1 text-xs">
                <div x-show="historyLoading" class="py-12 text-center text-slate-400 dark:text-slate-500">Loading ledger logs...</div>

                <div x-show="!historyLoading && historyData && historyData.movements.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500">
                    No movement records found for this SKU.
                </div>

                <div x-show="!historyLoading && historyData && historyData.movements.length > 0" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold sticky top-0">
                            <tr>
                                <th class="p-2.5">Date &amp; Time</th>
                                <th class="p-2.5">Type</th>
                                <th class="p-2.5">Delta</th>
                                <th class="p-2.5">Note</th>
                                <th class="p-2.5 text-right">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="m in historyData ? historyData.movements : []" :key="m.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="p-2.5 text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="m.created_at"></td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 uppercase" x-text="m.type_label"></span>
                                    </td>
                                    <td class="p-2.5 font-bold" :class="m.quantity > 0 ? 'text-emerald-700 dark:text-emerald-400' : (m.quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400')" x-text="(m.quantity > 0 ? '+' : '') + m.quantity"></td>
                                    <td class="p-2.5 text-slate-700 dark:text-slate-300" x-text="m.note || '-'"></td>
                                    <td class="p-2.5 text-right text-slate-600 dark:text-slate-400" x-text="m.user"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                <button @click="historyModal = false" type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- CSV Bulk Import Modal -->
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800" @click.away="importModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Import Inventory via CSV</h3>
                <button @click="importModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('seller.inventory.import_csv') }}" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-300 text-xs space-y-1">
                    <p>Upload a standard CSV file with columns: <strong>sku</strong>, <strong>quantity_on_hand</strong>, and optional <strong>low_stock_threshold</strong>.</p>
                    <a href="{{ route('seller.inventory.sample_csv') }}" class="inline-block text-emerald-600 dark:text-emerald-400 font-bold hover:underline text-[11px] mt-1">
                        📥 Download Sample CSV Template
                    </a>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select CSV File *</label>
                    <input 
                        type="file" 
                        name="csv_file" 
                        required 
                        accept=".csv,.txt"
                        class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-200 hover:file:bg-slate-200 dark:hover:file:bg-slate-700 border border-slate-300 dark:border-slate-700 rounded-lg p-1.5"
                    />
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="importModal = false" type="button" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-slate-900 dark:bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-slate-800 dark:hover:bg-emerald-500">Upload &amp; Reconcile</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
