@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Pricing Management - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    activeTab: '{{ $tab }}',
    editModal: false,
    bulkModal: false,
    previewMode: false,
    selectedIds: [],
    selectAll: false,
    bulkType: 'percent_discount',
    bulkValue: '5',
    bulkReason: '',
    bulkPreviewItems: [],
    bulkLoading: false,
    
    // Single edit state
    selectedItem: null,
    editPrice: '',
    editMrp: '',
    editReason: '',
    editEarnings: null,
    calculating: false,

    openEdit(item) {
        this.selectedItem = item;
        this.editPrice = item.current_price;
        this.editMrp = item.mrp || item.current_price;
        this.editReason = '';
        this.editEarnings = {
            net_payable: item.net_payable,
            commission_rate: item.commission_rate,
            total_commission: item.total_commission,
            gateway_fee: item.gateway_fee
        };
        this.editModal = true;
    },

    recalculateSingle() {
        if (!this.editPrice || this.editPrice <= 0) return;
        this.calculating = true;
        fetch('{{ route('seller.pricing.calculate_earnings') }}?price=' + this.editPrice + '&category_id=' + (this.selectedItem ? this.selectedItem.category_id : ''))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.editEarnings = data;
                }
                this.calculating = false;
            })
            .catch(() => { this.calculating = false; });
    },

    saveSinglePrice() {
        if (!this.selectedItem || !this.editPrice || this.editPrice <= 0) return;
        fetch('/seller/pricing/' + this.selectedItem.id + '/update-price', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                selling_price: this.editPrice,
                mrp: this.editMrp,
                reason: this.editReason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error updating price');
            }
        })
        .catch(err => alert('Request failed: ' + err.message));
    },

    toggleSelectAll(checked, allIds) {
        this.selectAll = checked;
        this.selectedIds = checked ? [...allIds] : [];
    },

    openBulkModal() {
        if (this.selectedIds.length === 0) {
            alert('Please select at least one product using the checkboxes.');
            return;
        }
        this.previewMode = false;
        this.bulkPreviewItems = [];
        this.bulkModal = true;
    },

    fetchBulkPreview() {
        if (!this.bulkValue || this.bulkValue <= 0) {
            alert('Please enter a valid adjustment value.');
            return;
        }
        this.bulkLoading = true;
        fetch('{{ route('seller.pricing.bulk_preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                variant_ids: this.selectedIds,
                adjustment_type: this.bulkType,
                adjustment_value: this.bulkValue
            })
        })
        .then(res => res.json())
        .then(data => {
            this.bulkLoading = false;
            if (data.success) {
                this.bulkPreviewItems = data.items;
                this.previewMode = true;
            } else {
                alert(data.message || 'Error generating preview');
            }
        })
        .catch(err => {
            this.bulkLoading = false;
            alert('Preview failed: ' + err.message);
        });
    },

    confirmBulkUpdate() {
        this.bulkLoading = true;
        fetch('{{ route('seller.pricing.bulk_update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                variant_ids: this.selectedIds,
                adjustment_type: this.bulkType,
                adjustment_value: this.bulkValue,
                reason: this.bulkReason
            })
        })
        .then(res => res.json())
        .then(data => {
            this.bulkLoading = false;
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error applying bulk update');
            }
        })
        .catch(err => {
            this.bulkLoading = false;
            alert('Bulk update failed: ' + err.message);
        });
    }
}">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
                <span>&gt;</span>
                <span class="text-slate-800 font-semibold">Pricing &amp; Margins</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-2">
                <span>Pricing Management</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-semibold border border-indigo-200">
                    Live Commission Engine
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Optimize selling prices, maintain retail compliance, and monitor rule-based seller payouts.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a 
                href="{{ route('seller.pricing.reduce_rto') }}" 
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg bg-emerald-50 text-emerald-700 font-semibold text-xs border border-emerald-200 hover:bg-emerald-100 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Return &amp; RTO Reduction Tools</span>
            </a>

            <button 
                @click="openBulkModal()"
                type="button" 
                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-lg bg-slate-900 text-white font-semibold text-xs hover:bg-slate-800 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Bulk Price Update</span>
                <span x-show="selectedIds.length > 0" class="ml-1 px-1.5 py-0.2 bg-indigo-500 text-white rounded-full text-[10px]" x-text="selectedIds.length"></span>
            </button>

            <a 
                href="{{ route('seller.pricing.index', array_merge(request()->query(), ['export' => 'csv'])) }}" 
                class="inline-flex items-center space-x-1.5 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition shadow-2xs"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Pricing Overview KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Active Products -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Active Listings</span>
                <span class="p-2 rounded-lg bg-blue-50 text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ number_format($metrics['total_active_products']) }}</div>
            <div class="mt-1 text-[11px] text-slate-400">Total catalog variants on sale</div>
        </div>

        <!-- Products with Pricing Issues -->
        <div class="bg-white rounded-xl border {{ $metrics['pricing_issues_count'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/90' }} p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold {{ $metrics['pricing_issues_count'] > 0 ? 'text-amber-800' : 'text-slate-500' }}">Pricing Issues</span>
                <span class="p-2 rounded-lg {{ $metrics['pricing_issues_count'] > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black {{ $metrics['pricing_issues_count'] > 0 ? 'text-amber-900' : 'text-slate-900' }}">
                {{ $metrics['pricing_issues_count'] }}
            </div>
            <div class="mt-1 text-[11px] {{ $metrics['pricing_issues_count'] > 0 ? 'text-amber-700 font-medium' : 'text-slate-400' }}">
                {{ $metrics['pricing_issues_count'] > 0 ? 'Price > MRP or ₹0 issues' : 'All prices compliant' }}
            </div>
        </div>

        <!-- Low Sales in 30 Days -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Low Sales (30 Days)</span>
                <span class="p-2 rounded-lg bg-orange-50 text-orange-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ number_format($metrics['low_sales_count']) }}</div>
            <div class="mt-1 text-[11px] text-slate-400">0 orders recorded in last 30 days</div>
        </div>

        <!-- Average Selling Price & Price Updates -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Avg. Selling Price</span>
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">₹</span>
            </div>
            <div class="mt-2 text-2xl font-black text-slate-900">₹{{ number_format($metrics['avg_selling_price'], 2) }}</div>
            <div class="mt-1 text-[11px] text-slate-500">
                <span class="font-bold text-slate-800">{{ $metrics['price_changes_30d'] }}</span> updates logged in 30 days
            </div>
        </div>
    </div>

    <!-- Data-Backed Pricing Recommendations (if any) -->
    @if(!empty($recommendations))
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-xl p-4 shadow-2xs">
            <div class="flex items-start space-x-3">
                <span class="text-xl">💡</span>
                <div class="space-y-1">
                    <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Data-Backed Pricing Insights &amp; Opportunities</h3>
                    <ul class="text-xs text-amber-900 space-y-1 list-disc list-inside">
                        @foreach(array_slice($recommendations, 0, 3) as $rec)
                            <li>{{ $rec }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Navigation Tabs & Search / Filter Console -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between border-b border-slate-200 px-4 pt-2 gap-3">
            <!-- Tabs -->
            <div class="flex items-center space-x-4 text-xs font-semibold overflow-x-auto">
                <a 
                    href="{{ route('seller.pricing.index', array_merge(request()->except('tab'), ['tab' => 'all'])) }}" 
                    class="py-3 border-b-2 transition whitespace-nowrap {{ $tab === 'all' ? 'border-slate-900 text-slate-900 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    All Products ({{ $counts['all'] }})
                </a>

                <a 
                    href="{{ route('seller.pricing.index', array_merge(request()->except('tab'), ['tab' => 'issues'])) }}" 
                    class="py-3 border-b-2 transition whitespace-nowrap {{ $tab === 'issues' ? 'border-amber-600 text-amber-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Pricing Issues ({{ $counts['issues'] }})
                </a>

                <a 
                    href="{{ route('seller.pricing.index', array_merge(request()->except('tab'), ['tab' => 'low_sales'])) }}" 
                    class="py-3 border-b-2 transition whitespace-nowrap {{ $tab === 'low_sales' ? 'border-orange-600 text-orange-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Low Sales ({{ $counts['low_sales'] }})
                </a>

                <a 
                    href="{{ route('seller.pricing.index', array_merge(request()->except('tab'), ['tab' => 'history'])) }}" 
                    class="py-3 border-b-2 transition whitespace-nowrap {{ $tab === 'history' ? 'border-indigo-600 text-indigo-700 font-bold' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Price Change History ({{ $counts['history'] }})
                </a>
            </div>

            <!-- Search & Category Filters -->
            @if($tab !== 'history')
                <form method="GET" action="{{ route('seller.pricing.index') }}" class="flex items-center space-x-2 pb-2 lg:pb-0">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    
                    <select 
                        name="category_id" 
                        onchange="this.form.submit()" 
                        class="py-1.5 px-2.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-700 focus:outline-none focus:border-indigo-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}"
                            placeholder="Search by SKU, Title..." 
                            class="py-1.5 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-52 sm:w-64"
                        />
                        <button type="submit" class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- Tab 1, 2, 3: Products Pricing Table -->
        @if($tab !== 'history')
            @if($items->isEmpty())
                <div class="py-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">No Products Found</h4>
                    <p class="text-xs text-slate-500 max-w-sm mt-1">
                        @if($tab === 'issues')
                            All of your active product prices comply with retail guidelines and MRP constraints.
                        @elseif($tab === 'low_sales')
                            No products currently match the low 30-day sales criteria.
                        @else
                            No products found in this view. Try adjusting your search or category filter.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3 w-8 text-center">
                                    <input 
                                        type="checkbox" 
                                        @change="toggleSelectAll($event.target.checked, {{ Js::from($items->pluck('id')) }})"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    >
                                </th>
                                <th class="p-3">Product &amp; SKU</th>
                                <th class="p-3">Stock</th>
                                <th class="p-3">Selling Price &amp; MRP</th>
                                <th class="p-3">Tax</th>
                                <th class="p-3">Est. Net Payout</th>
                                <th class="p-3">30D Orders</th>
                                <th class="p-3">Status &amp; Insights</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($items as $row)
                                <tr class="hover:bg-slate-50/80 transition {{ $row['has_issue'] ? 'bg-amber-50/20' : '' }}">
                                    <td class="p-3 text-center">
                                        <input 
                                            type="checkbox" 
                                            value="{{ $row['id'] }}" 
                                            x-model="selectedIds"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-3">
                                            <img 
                                                src="{{ $row['image'] }}" 
                                                alt="{{ $row['title'] }}" 
                                                class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 shrink-0"
                                                onerror="this.src='/images/placeholder.png'"
                                            />
                                            <div>
                                                <div class="font-bold text-slate-900 line-clamp-1">{{ $row['title'] }}</div>
                                                <div class="text-[10px] text-slate-500 flex items-center space-x-2 mt-0.5">
                                                    <span>SKU: <strong class="text-slate-700 font-mono">{{ $row['sku'] }}</strong></span>
                                                    <span>&bull;</span>
                                                    <span>{{ $row['size'] }}</span>
                                                    <span>&bull;</span>
                                                    <span class="text-slate-400">{{ $row['category_name'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="font-semibold {{ $row['stock'] <= 5 ? 'text-amber-600' : 'text-slate-800' }}">
                                            {{ $row['stock'] }} units
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-black text-slate-900 text-sm">₹{{ number_format($row['current_price'], 2) }}</div>
                                        <div class="text-[10px] text-slate-500 flex items-center space-x-1.5 mt-0.5">
                                            <span>MRP: ₹{{ number_format($row['mrp'], 2) }}</span>
                                            @if($row['discount_pct'] > 0)
                                                <span class="px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 font-bold text-[9px] border border-emerald-200">
                                                    {{ $row['discount_pct'] }}% OFF
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-3 text-slate-600 font-medium">
                                        {{ $row['tax_percent'] }}% GST
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-emerald-700 text-sm">
                                            ₹{{ number_format($row['net_payable'], 2) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Fee: {{ $row['commission_rate'] }}% + GST
                                        </div>
                                    </td>
                                    <td class="p-3 font-semibold text-slate-700">
                                        {{ $row['units_sold_30d'] }} units
                                    </td>
                                    <td class="p-3">
                                        @if($row['has_issue'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                ⚠️ {{ $row['pricing_issue'] }}
                                            </span>
                                        @elseif($row['is_low_sales'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                                📉 Low Demand
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✓ Active &amp; Healthy
                                            </span>
                                        @endif

                                        @if($row['recommendation'] && !$row['has_issue'])
                                            <div class="text-[10px] text-slate-400 mt-1 max-w-xs line-clamp-1" title="{{ $row['recommendation'] }}">
                                                💡 {{ $row['recommendation'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <button 
                                            @click="openEdit({{ Js::from($row) }})"
                                            type="button" 
                                            class="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition shadow-2xs"
                                        >
                                            Edit Price
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <!-- Tab 4: Price Change History Audit Trail -->
            @if(!$historyRecords || $historyRecords->isEmpty())
                <div class="py-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">No Price Changes Logged</h4>
                    <p class="text-xs text-slate-500 max-w-sm mt-1">
                        Any manual price modifications or bulk updates will automatically be recorded with timestamps and reason logs.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="p-3">Date &amp; Time</th>
                                <th class="p-3">Product &amp; SKU</th>
                                <th class="p-3">Price Change (Selling Price)</th>
                                <th class="p-3">Difference</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Reason</th>
                                <th class="p-3 text-right">Updated By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($historyRecords as $hist)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3 text-slate-500 whitespace-nowrap">
                                        <div class="font-medium text-slate-700">{{ $hist->created_at->format('d M Y') }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $hist->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ $hist->variant?->product?->title ?? 'Product' }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ $hist->variant?->sku }}</div>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-slate-400 line-through">₹{{ number_format((float)$hist->old_price, 2) }}</span>
                                            <span>&rarr;</span>
                                            <span class="font-black text-slate-900 text-sm">₹{{ number_format((float)$hist->new_price, 2) }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        @if($hist->price_difference < 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ₹{{ number_format($hist->price_difference, 2) }} ({{ $hist->percentage_change }}%)
                                            </span>
                                        @elseif($hist->price_difference > 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                +₹{{ number_format($hist->price_difference, 2) }} (+{{ $hist->percentage_change }}%)
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-[10px]">No change</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 uppercase">
                                            {{ $hist->change_type }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-600">
                                        {{ $hist->reason ?? '-' }}
                                    </td>
                                    <td class="p-3 text-right font-medium text-slate-700">
                                        {{ $hist->user?->name ?? 'Seller' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-200">
                    {{ $historyRecords->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Edit Individual Price Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200" @click.away="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Update Selling Price</h3>
                    <p class="text-xs text-slate-500" x-text="selectedItem ? selectedItem.title + ' (' + selectedItem.sku + ')' : ''"></p>
                </div>
                <button @click="editModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="space-y-4 text-xs">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Selling Price (₹) *</label>
                        <input 
                            type="number" 
                            step="0.01"
                            min="1"
                            x-model="editPrice" 
                            @input="recalculateSingle()"
                            class="w-full p-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:border-indigo-600"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">MRP (₹) *</label>
                        <input 
                            type="number" 
                            step="0.01"
                            min="1"
                            x-model="editMrp" 
                            class="w-full p-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:border-indigo-600"
                        />
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason for Adjustment (Optional)</label>
                    <input 
                        type="text" 
                        x-model="editReason" 
                        placeholder="e.g. Competitive pricing, cost decrease"
                        class="w-full p-2.5 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-indigo-600"
                    />
                </div>

                <!-- Live Rule-Based Commission Calculation Breakdown -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Platform Commission Rate:</span>
                        <span class="font-bold text-slate-900" x-text="editEarnings ? editEarnings.commission_rate + '%' : '10%'"></span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Platform Deductions (Fee + GST + Gateway):</span>
                        <span class="font-bold text-slate-700" x-text="editEarnings ? '₹' + (editEarnings.total_commission + (editEarnings.gateway_fee || 0)).toFixed(2) : 'Calculating...'"></span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                        <span class="font-bold text-slate-800">Estimated Net Bank Payout:</span>
                        <span class="font-extrabold text-emerald-700 text-base" x-text="editEarnings ? '₹' + Number(editEarnings.net_payable).toFixed(2) : '₹0.00'"></span>
                    </div>
                </div>

                <div x-show="Number(editMrp) < Number(editPrice)" class="p-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-[11px] font-semibold">
                    ⚠️ Selling price cannot be higher than Maximum Retail Price (MRP).
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button @click="editModal = false" type="button" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-xs text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>
                <button 
                    @click="saveSinglePrice()" 
                    :disabled="Number(editMrp) < Number(editPrice) || !editPrice || editPrice <= 0"
                    type="button" 
                    class="px-4 py-2 bg-slate-900 text-white rounded-lg font-bold text-xs hover:bg-slate-800 disabled:opacity-50"
                >
                    Save Price
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Price Update Modal -->
    <div x-show="bulkModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] flex flex-col" @click.away="bulkModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Bulk Price Update</h3>
                    <p class="text-xs text-slate-500">
                        Adjust selling prices simultaneously across <span class="font-bold text-slate-800" x-text="selectedIds.length"></span> selected products.
                    </p>
                </div>
                <button @click="bulkModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="space-y-4 text-xs overflow-y-auto flex-1 pr-1">
                <div x-show="!previewMode" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Adjustment Type *</label>
                            <select 
                                x-model="bulkType" 
                                class="w-full p-2.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-indigo-600"
                            >
                                <option value="percent_discount">Percentage Discount (% reduction)</option>
                                <option value="percent_increase">Percentage Increase (% increase)</option>
                                <option value="fixed_discount">Fixed Amount Discount (₹ reduction)</option>
                                <option value="set_fixed_price">Set Fixed Selling Price (₹ fixed)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Adjustment Value *</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                x-model="bulkValue" 
                                placeholder="e.g. 5 or 100"
                                class="w-full p-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:border-indigo-600"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Update Reason (Recorded in audit trail)</label>
                        <input 
                            type="text" 
                            x-model="bulkReason" 
                            placeholder="e.g. End of Season Sale, Raw Material Price Adjustment"
                            class="w-full p-2.5 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-indigo-600"
                        />
                    </div>

                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs">
                        <strong>Safety Assurance:</strong> Prices are automatically capped at MRP and constrained to a minimum of ₹1.00. You will have a chance to preview all changes before confirming.
                    </div>
                </div>

                <!-- Preview Mode Table -->
                <div x-show="previewMode" class="space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Previewing Proposed Adjustments:</span>
                        <button @click="previewMode = false" type="button" class="text-indigo-600 font-semibold hover:underline">&larr; Edit Adjustment</button>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-x-auto max-h-64">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold sticky top-0">
                                <tr>
                                    <th class="p-2.5">SKU / Title</th>
                                    <th class="p-2.5">Old Price</th>
                                    <th class="p-2.5">New Price</th>
                                    <th class="p-2.5">Difference</th>
                                    <th class="p-2.5">New Est. Payout</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="item in bulkPreviewItems" :key="item.id">
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-2.5">
                                            <div class="font-bold text-slate-900" x-text="item.title"></div>
                                            <div class="text-[10px] text-slate-500 font-mono" x-text="item.sku"></div>
                                        </td>
                                        <td class="p-2.5 text-slate-400 line-through" x-text="'₹' + Number(item.old_price).toFixed(2)"></td>
                                        <td class="p-2.5 font-bold text-slate-900" x-text="'₹' + Number(item.new_price).toFixed(2)"></td>
                                        <td class="p-2.5">
                                            <span 
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                                :class="item.difference < 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'"
                                                x-text="(item.difference > 0 ? '+' : '') + item.difference + ' (' + item.percentage + '%)'"
                                            ></span>
                                        </td>
                                        <td class="p-2.5 font-bold text-emerald-700" x-text="'₹' + Number(item.new_earnings).toFixed(2)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                <button @click="bulkModal = false" type="button" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-xs text-slate-600 hover:bg-slate-50">
                    Cancel
                </button>

                <button 
                    x-show="!previewMode"
                    @click="fetchBulkPreview()" 
                    :disabled="bulkLoading || !bulkValue || bulkValue <= 0"
                    type="button" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold text-xs hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span x-show="!bulkLoading">Preview Changes &rarr;</span>
                    <span x-show="bulkLoading">Calculating...</span>
                </button>

                <button 
                    x-show="previewMode"
                    @click="confirmBulkUpdate()" 
                    :disabled="bulkLoading"
                    type="button" 
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-bold text-xs hover:bg-emerald-700 disabled:opacity-50"
                >
                    <span x-show="!bulkLoading">Confirm &amp; Apply Update</span>
                    <span x-show="bulkLoading">Applying Update...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
