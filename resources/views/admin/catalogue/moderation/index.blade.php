@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Product Approval & Catalog Management'])

@section('content')
@php
    $currentStatus = $filters['status'] ?? 'pending_review';
@endphp
<div class="space-y-6" x-data="productModerationQueue()">
    <!-- Header with Breadcrumbs & Action Toolbar -->
    <x-page-header 
        title="Product Moderation & Catalog Approval" 
        subtitle="Review merchant submissions, verify product attributes, check pricing compliance, inspect media quality, and govern live marketplace listings."
        :breadcrumbs="['Command Central' => route('admin.dashboard'), 'Catalogue' => '#', 'Product Approval' => '#']"
    >
        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('admin.catalogue.standards.index') }}" 
                    class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition flex items-center"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                    Standards & Units
                </a>

                <a 
                    href="{{ route('admin.catalogue.moderation.import_template') }}" 
                    class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition flex items-center"
                    title="Download standardized CSV catalog import template"
                >
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                    CSV Template
                </a>

                <a 
                    href="{{ route('admin.catalogue.moderation.export', request()->query()) }}" 
                    class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center"
                >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            </div>
        </x-slot>
    </x-page-header>

    <!-- 6 Status Tabs with Dynamic Counters -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-2 sm:space-x-4 overflow-x-auto" aria-label="Product Approval Tabs">
            <!-- Tab 1: All Products -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'all'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ $currentStatus === 'all' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>All Products</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $currentStatus === 'all' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ number_format($stats['all_count']) }}
                </span>
            </a>

            <!-- Tab 2: Pending Review -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'pending_review'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ $currentStatus === 'pending_review' ? 'border-amber-500 text-amber-600 dark:text-amber-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>Pending Review</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $currentStatus === 'pending_review' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600' }}">
                    {{ number_format($stats['pending_count']) }}
                </span>
            </a>

            <!-- Tab 3: Approved (Active) -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ in_array($currentStatus, ['active', 'approved']) ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>Approved (Live)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ in_array($currentStatus, ['active', 'approved']) ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ number_format($stats['approved_count']) }}
                </span>
            </a>

            <!-- Tab 4: Rejected -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ $currentStatus === 'rejected' ? 'border-rose-500 text-rose-600 dark:text-rose-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>Rejected</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $currentStatus === 'rejected' ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ number_format($stats['rejected_count']) }}
                </span>
            </a>

            <!-- Tab 5: Blocked -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'blocked'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ $currentStatus === 'blocked' ? 'border-red-500 text-red-600 dark:text-red-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>Blocked</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $currentStatus === 'blocked' ? 'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ number_format($stats['blocked_count']) }}
                </span>
            </a>

            <!-- Tab 6: Inactive -->
            <a 
                href="{{ route('admin.catalogue.moderation.index', array_merge(request()->except('page'), ['status' => 'inactive'])) }}"
                class="py-3 px-3 text-xs border-b-2 transition whitespace-nowrap flex items-center space-x-2 {{ $currentStatus === 'inactive' ? 'border-slate-500 text-slate-800 dark:text-slate-200 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 font-medium' }}"
            >
                <span>Inactive / Archived</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono {{ $currentStatus === 'inactive' ? 'bg-slate-200 text-slate-800 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    {{ number_format($stats['inactive_count']) }}
                </span>
            </a>
        </nav>
    </div>

    <!-- Search & Filters Card -->
    <x-card padding="p-4">
        <form method="GET" action="{{ route('admin.catalogue.moderation.index') }}" class="space-y-3">
            <input type="hidden" name="status" value="{{ $currentStatus }}">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Products</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}" 
                        placeholder="Title, SKU, Barcode, HSN, or merchant..." 
                        class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Category</label>
                    <select name="category_id" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Brand</label>
                    <select name="brand_id" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="">All Brands</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" {{ ($filters['brand_id'] ?? '') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Merchant / Seller</label>
                    <select name="seller_id" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="">All Merchants</option>
                        @foreach($sellers as $s)
                            <option value="{{ $s->id }}" {{ ($filters['seller_id'] ?? '') == $s->id ? 'selected' : '' }}>
                                {{ $s->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Min Selling Price (₹)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="min_price" 
                        value="{{ $filters['min_price'] ?? '' }}" 
                        placeholder="Min ₹" 
                        class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Max Selling Price (₹)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="max_price" 
                        value="{{ $filters['max_price'] ?? '' }}" 
                        placeholder="Max ₹" 
                        class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Fulfilment Mode</label>
                    <select name="is_quick_capable" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="">All Fulfilment Modes</option>
                        <option value="1" {{ ($filters['is_quick_capable'] ?? '') === '1' ? 'selected' : '' }}>Quick Commerce (10-30m)</option>
                        <option value="0" {{ ($filters['is_quick_capable'] ?? '') === '0' ? 'selected' : '' }}>Standard Courier Dispatch</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Sort Order</label>
                    <select name="sort" class="w-full py-2 px-3 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                        <option value="latest" {{ ($filters['sort'] ?? '') === 'latest' ? 'selected' : '' }}>Latest Submitted</option>
                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="title_asc" {{ ($filters['sort'] ?? '') === 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                        <option value="title_desc" {{ ($filters['sort'] ?? '') === 'title_desc' ? 'selected' : '' }}>Title (Z-A)</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 py-2 px-3 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.catalogue.moderation.index', ['status' => $currentStatus]) }}" class="py-2 px-3 text-xs font-semibold rounded-xl bg-slate-200 dark:bg-slate-750 text-slate-700 dark:text-slate-300 hover:bg-slate-300 transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </x-card>

    <!-- Floating Bulk Operations Banner -->
    <div 
        x-show="selectedIds.length > 0" 
        x-cloak
        class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/80 border border-emerald-200 dark:border-emerald-800 shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-3"
    >
        <div class="text-xs font-semibold text-emerald-950 dark:text-emerald-100 flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span><strong class="font-extrabold" x-text="selectedIds.length"></strong> product(s) selected for bulk operation</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- 1. Bulk Approve -->
            <form method="POST" action="{{ route('admin.catalogue.moderation.bulk_approve') }}" class="inline">
                @csrf
                <template x-for="id in selectedIds" :key="'app_' + id">
                    <input type="hidden" name="product_ids[]" :value="id">
                </template>
                <button 
                    type="submit" 
                    onclick="return confirm('Approve all selected listings into the active catalog?')"
                    class="px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
                >
                    Bulk Approve
                </button>
            </form>

            <!-- 2. Bulk Status Change Button -->
            <button 
                type="button" 
                @click="$dispatch('open-modal', 'bulk-status-modal')"
                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-800 text-white hover:bg-slate-900 transition"
            >
                Change Status
            </button>

            <!-- 3. Bulk Category Assign Button -->
            <button 
                type="button" 
                @click="$dispatch('open-modal', 'bulk-category-modal')"
                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition"
            >
                Reassign Category
            </button>

            <!-- Clear selection -->
            <button 
                type="button" 
                @click="selectedIds = []" 
                class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-semibold"
            >
                Clear
            </button>
        </div>
    </div>

    <!-- Products Table Card -->
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4 w-10 text-center">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="py-3 px-4">Product Details</th>
                        <th class="py-3 px-4">Merchant Origin</th>
                        <th class="py-3 px-4">Category & Brand</th>
                        <th class="py-3 px-4 text-right">Selling / MRP</th>
                        <th class="py-3 px-4 text-center">Commercials</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-6 text-right">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($products as $product)
                        @php
                            $defaultVar = $product->variants->firstWhere('is_default', true) ?: $product->variants->first();
                            $mrp = (float) ($defaultVar?->mrp ?? 0);
                            $price = (float) ($defaultVar?->selling_price ?? 0);
                            $discount = $mrp > 0 && $price < $mrp ? (int) round((($mrp - $price) / $mrp) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50">
                            <td class="py-3.5 px-4 text-center">
                                <input type="checkbox" :value="{{ $product->id }}" x-model="selectedIds" class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            </td>

                            <!-- Product Info -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden shrink-0 flex items-center justify-center">
                                        <img 
                                            src="{{ $product->primaryImageUrl() }}" 
                                            alt="{{ $product->title }}" 
                                            loading="lazy" 
                                            class="w-full h-full object-cover" 
                                            onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'100\' height=\'100\'><rect width=\'100\' height=\'100\' rx=\'16\' fill=\'%23f1f5f9\'/><text x=\'50\' y=\'58\' font-size=\'32\' text-anchor=\'middle\'>🛍️</text></svg>';"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.catalogue.moderation.show', $product) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 transition truncate block max-w-xs">
                                            {{ $product->title }}
                                        </a>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            ID: <span class="font-mono font-bold text-slate-600 dark:text-slate-300">#{{ $product->id }}</span> &bull; 
                                            SKU: <span class="font-mono">{{ $defaultVar?->sku ?? 'N/A' }}</span> &bull; 
                                            HSN: <span class="font-mono">{{ $product->hsn_code ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Seller Origin -->
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                @if($product->seller)
                                    <a href="{{ route('admin.sellers.show', $product->seller) }}" class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 transition">
                                        {{ $product->seller->business_name }}
                                    </a>
                                    <div class="text-[11px] text-slate-400">
                                        Owner: {{ $product->seller->user?->name ?? 'N/A' }}
                                    </div>
                                @else
                                    <span class="text-slate-400">Direct / No Merchant</span>
                                @endif
                            </td>

                            <!-- Category & Brand -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900 dark:text-white">
                                    {{ $product->category?->name ?? 'Uncategorized' }}
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center space-x-1 mt-0.5">
                                    <span>Brand: {{ $product->brand?->name ?? 'Generic / No Brand' }}</span>
                                    @if($product->brand?->is_verified)
                                        <svg class="w-3.5 h-3.5 text-emerald-500 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    @endif
                                </div>
                            </td>

                            <!-- Pricing -->
                            <td class="py-3.5 px-4 text-right">
                                @if($defaultVar)
                                    <div class="font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format($price, 2) }}</div>
                                    @if($mrp > $price)
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            <span class="line-through">₹{{ number_format($mrp, 2) }}</span>
                                            <span class="text-emerald-600 font-bold ml-1">{{ $discount }}% OFF</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">No variants</span>
                                @endif
                            </td>

                            <!-- Commercials & Fulfilment -->
                            <td class="py-3.5 px-4 text-center space-y-1">
                                @if($product->is_quick_capable)
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⚡ Quick (10-30m)
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        Standard
                                    </span>
                                @endif
                                <div class="text-[10px] text-slate-400">
                                    Tax: {{ $product->tax_percent }}% &bull; {{ $product->is_cod_allowed ? 'COD' : 'Prepaid' }}
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $product->status->badgeClass() }}">
                                    {{ $product->status->label() }}
                                </span>
                                @if($product->rejection_reason)
                                    <div class="text-[10px] text-rose-500 truncate max-w-[140px] mx-auto mt-0.5" title="{{ $product->rejection_reason }}">
                                        {{ $product->rejection_reason }}
                                    </div>
                                @endif
                            </td>

                            <!-- Moderation Action Buttons -->
                            <td class="py-3.5 px-6 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a 
                                        href="{{ route('admin.catalogue.moderation.show', $product) }}" 
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition"
                                    >
                                        Inspect &rarr;
                                    </a>

                                    @if($product->status !== \App\Enums\ProductStatus::ACTIVE)
                                        <form method="POST" action="{{ route('admin.catalogue.moderation.approve', $product) }}" class="inline">
                                            @csrf
                                            <button 
                                                type="submit" 
                                                onclick="return confirm('Approve listing into live catalogue?')"
                                                class="px-2 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition"
                                                title="Approve Listing"
                                            >
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    @if($product->status !== \App\Enums\ProductStatus::BLOCKED)
                                        <button 
                                            type="button" 
                                            @click="openBlockModal({{ $product->id }}, '{{ addslashes($product->title) }}')"
                                            class="px-2 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 transition"
                                            title="Block Product for Policy Violation"
                                        >
                                            Block
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-2 text-xl">
                                    📦
                                </div>
                                <div class="font-bold text-slate-700 dark:text-slate-300">No products found in this queue.</div>
                                <div class="text-xs text-slate-400 mt-0.5">Try adjusting your filters, searching another keyword, or checking another status tab.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $products->links() }}
            </div>
        @endif
    </x-card>

    <!-- Modal: Bulk Status Update -->
    <x-modal name="bulk-status-modal" title="Bulk Status Update" size="md">
        <form action="{{ route('admin.catalogue.moderation.bulk_status') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <template x-for="id in selectedIds" :key="'status_' + id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>

            <div>
                <p class="text-slate-500 mb-2">
                    Update status for <strong class="text-slate-900 dark:text-white" x-text="selectedIds.length"></strong> selected product(s):
                </p>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Status <span class="text-rose-500">*</span></label>
                <select name="status" required class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="active">Approved / Active (Live on Storefront)</option>
                    <option value="inactive">Inactive / Archived</option>
                    <option value="rejected">Rejected (Requires merchant edits)</option>
                    <option value="blocked">Blocked (Policy Violation)</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Note (Optional or required for Block/Reject)</label>
                <textarea name="reason" rows="2" placeholder="e.g. Bulk seasonal archival, policy review..." class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'bulk-status-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-black transition">Apply Status</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal: Bulk Category Reassignment -->
    <x-modal name="bulk-category-modal" title="Bulk Category Reassignment" size="md">
        <form action="{{ route('admin.catalogue.moderation.bulk_category') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <template x-for="id in selectedIds" :key="'cat_' + id">
                <input type="hidden" name="product_ids[]" :value="id">
            </template>

            <div>
                <p class="text-slate-500 mb-2">
                    Move <strong class="text-slate-900 dark:text-white" x-text="selectedIds.length"></strong> selected product(s) to a different category:
                </p>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">New Target Category <span class="text-rose-500">*</span></label>
                <select name="category_id" required class="w-full py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                    <option value="">Select Category...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'bulk-category-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition">Reassign Category</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal: Block Single Product -->
    <x-modal name="block-product-modal" title="Block Product Listing (Policy Enforcement)" size="md">
        <form :action="'/admin/catalogue/moderation/' + activeProductId + '/block'" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-600 dark:text-slate-300 mb-3">
                    Blocking <strong class="text-slate-900 dark:text-white" x-text="activeProductTitle"></strong> will immediately take it down from the consumer storefront and notify the seller.
                </p>

                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Mandatory Policy Violation Justification <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="policy_reason" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="e.g. Counterfeit brand claim, copyright infringement, prohibited hazardous item, restricted drug category..."
                    class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <!-- Quick Policy Reason Chips -->
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold text-slate-400">Standard Policy Violation Templates:</span>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <button type="button" @click="$el.closest('form').querySelector('textarea[name=policy_reason]').value = 'Counterfeit / unauthorized brand trademark reproduction without licensing.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Counterfeit</button>
                    <button type="button" @click="$el.closest('form').querySelector('textarea[name=policy_reason]').value = 'Prohibited item under Drugs & Cosmetics Act or Dangerous Goods category.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Prohibited Goods</button>
                    <button type="button" @click="$el.closest('form').querySelector('textarea[name=policy_reason]').value = 'Severe safety hazard or recalled consumer item.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Safety Hazard</button>
                    <button type="button" @click="$el.closest('form').querySelector('textarea[name=policy_reason]').value = 'Misleading pricing, inflated MRP, or deceptive customer representation.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Deceptive Pricing</button>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'block-product-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-red-600 hover:bg-red-700 text-white shadow-xs transition">Confirm & Block Product</button>
            </div>
        </form>
    </x-modal>
</div>

<script>
function productModerationQueue() {
    return {
        selectedIds: [],
        activeProductId: null,
        activeProductTitle: '',
        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = Array.from(document.querySelectorAll('input[type="checkbox"][value]'))
                    .map(el => parseInt(el.value))
                    .filter(val => !isNaN(val));
            } else {
                this.selectedIds = [];
            }
        },
        openBlockModal(id, title) {
            this.activeProductId = id;
            this.activeProductTitle = title;
            this.$dispatch('open-modal', 'block-product-modal');
        }
    };
}
</script>
@endsection
