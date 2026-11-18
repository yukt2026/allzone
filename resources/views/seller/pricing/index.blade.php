@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Pricing & Recommendations - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    activeTab: '{{ $tab }}',
    editModal: false,
    selectedItem: null,
    newPrice: '',
    openEdit(item) {
        this.selectedItem = item;
        this.newPrice = item.recommended_price || item.current_price;
        this.editModal = true;
    },
    savePrice() {
        if (!this.selectedItem) return;
        fetch('/seller/pricing/' + this.selectedItem.id + '/update-price', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: JSON.stringify({ price: this.newPrice })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.selectedItem.current_price = this.newPrice;
                this.editModal = false;
                $dispatch('toast', { message: 'Price updated successfully!', type: 'success' });
            }
        });
    }
}">
    <!-- Header with Search (Screenshot 7) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Pricing
        </h1>
        <div class="relative">
            <input 
                type="text" 
                placeholder="Search by Style/SKU ID or Catalog ID" 
                value="{{ $search }}"
                class="py-1.5 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-64"
            />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Overview Performance & Reduce RTO Cards (Screenshot 7 & 11) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Price Performance Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-900">Price Performance</h3>
                    <select class="py-1 px-2 border border-slate-200 rounded text-xs text-slate-700">
                        <option>Last 30 days</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4 pb-4">
                    <div>
                        <div class="text-[11px] text-slate-400">Order Growth</div>
                        <div class="text-xl font-black text-slate-900">0</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-slate-400">Price Updates</div>
                        <div class="text-xl font-black text-slate-900">0</div>
                    </div>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center space-x-2 text-xs text-amber-800">
                <span>⚠️</span>
                <span><strong>Losing Order: 0 Products</strong> (Reduce price and increase more orders)</span>
            </div>
        </div>

        <!-- Reduce RTO & Returns Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <span class="text-emerald-600">🛡️</span>
                    <h3 class="text-sm font-bold text-slate-900">Reduce RTO &amp; Returns</h3>
                </div>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800">Reduce RTO</span>
                            <span class="font-bold text-slate-600">35% &rarr; <span class="text-emerald-600">7%</span></span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                            <div class="bg-emerald-500 h-full w-full"></div>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Prepaid Discount – 30/30 Products</div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-800">Reduce Returns</span>
                            <span class="font-bold text-slate-600">21% &rarr; <span class="text-emerald-600">16%</span></span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden mt-1">
                            <div class="bg-emerald-500 h-full w-full"></div>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">WDRP Discount – 30/30 Products</div>
                    </div>
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100">
                <a href="{{ route('seller.pricing.reduce_rto') }}" class="w-full block text-center py-2 rounded-lg border border-[#53389e] text-[#53389e] font-bold text-xs hover:bg-purple-50 transition">
                    Reduce RTO &amp; Returns
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs: All Products / Losing Orders / Losing Views / Best Priced (Screenshots 7, 8, 9, 10) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 pt-2">
            <div class="flex items-center space-x-6 text-xs font-bold">
                <a 
                    href="{{ route('seller.pricing.index', ['tab' => 'all']) }}" 
                    class="py-3 border-b-2 transition {{ $tab === 'all' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    All Products ({{ $counts['all'] }})
                </a>
                <a 
                    href="{{ route('seller.pricing.index', ['tab' => 'losing_orders']) }}" 
                    class="py-3 border-b-2 transition {{ $tab === 'losing_orders' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Losing Orders ({{ $counts['losing_orders'] }})
                </a>
                <a 
                    href="{{ route('seller.pricing.index', ['tab' => 'losing_views']) }}" 
                    class="py-3 border-b-2 transition {{ $tab === 'losing_views' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Losing Views ({{ $counts['losing_views'] }})
                </a>
                <a 
                    href="{{ route('seller.pricing.index', ['tab' => 'best_priced']) }}" 
                    class="py-3 border-b-2 transition {{ $tab === 'best_priced' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    Best Priced ({{ $counts['best_priced'] }})
                </a>
            </div>

            <button type="button" class="text-xs font-bold text-[#53389e] flex items-center space-x-1 hover:underline">
                <span>🗐</span>
                <span>Bulk Price Update</span>
            </button>
        </div>

        @if($tab === 'losing_views')
            <!-- Warning banner for losing views -->
            <div class="p-3 bg-orange-50 border-b border-orange-200 text-xs text-amber-900 font-semibold flex items-center space-x-2">
                <span>⚠️</span>
                <span>These products are losing out on views. Get more views and orders by reducing price.</span>
            </div>
        @endif

        @if($tab === 'losing_orders' && count($items) === 0)
            <!-- Empty state matching Screenshot 8 -->
            <div class="py-16 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 text-slate-300 mb-2">
                    <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800">No recommendations available</h4>
                <p class="text-xs text-slate-400 mt-1">No active products live, keep checking.</p>
            </div>
        @else
            <!-- Pricing Table (Matching Screenshot 7, 9, 10) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="p-3 w-8"><input type="checkbox" class="rounded"></th>
                            <th class="p-3">Product Details</th>
                            <th class="p-3">Current Stock</th>
                            <th class="p-3">Growth in 30 days</th>
                            <th class="p-3">Current Customer Price</th>
                            <th class="p-3">Recommended Customer Price</th>
                            <th class="p-3">Insights</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $row)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3"><input type="checkbox" class="rounded"></td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ $row['title'] }}</div>
                                    <div class="text-[10px] text-slate-500">Catalog ID: {{ $row['catalog_id'] }} &bull; Style ID: {{ $row['style_id'] }} &bull; Size: {{ $row['size'] }}</div>
                                </td>
                                <td class="p-3 font-semibold text-slate-800">{{ $row['stock'] }}</td>
                                <td class="p-3 text-slate-400">-</td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">₹{{ (int)$row['current_price'] }}</div>
                                    <div class="text-[10px] text-slate-400">Bank Transfer: ₹{{ (int)$row['bank_transfer'] }}</div>
                                </td>
                                <td class="p-3">
                                    @if($row['recommended_price'])
                                        <div class="font-bold text-indigo-700">₹{{ (int)$row['recommended_price'] }}</div>
                                        <div class="text-[10px] text-slate-400">Bank Transfer: ₹{{ (int)$row['recommended_bank_transfer'] }}</div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if($row['is_best_priced'])
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            👍 Best Price
                                        </span>
                                    @elseif($row['is_losing_views'])
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-50 text-amber-800 border border-amber-200">
                                            ⚠️ Losing Views ⓘ
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right">
                                    @if($row['recommended_price'])
                                        <button 
                                            @click="openEdit({{ Js::from($row) }})"
                                            type="button" 
                                            class="px-3.5 py-1.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white font-bold text-xs shadow-xs transition"
                                        >
                                            Accept / Edit
                                        </button>
                                    @else
                                        <button 
                                            @click="openEdit({{ Js::from($row) }})"
                                            type="button" 
                                            class="px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition"
                                        >
                                            Edit
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Edit Price Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200" @click.away="editModal = false">
            <h3 class="text-base font-bold text-slate-900 mb-1">Update Product Price</h3>
            <p class="text-xs text-slate-500 mb-4" x-text="selectedItem ? selectedItem.title : ''"></p>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Customer Price (₹)</label>
                    <input 
                        type="number" 
                        x-model="newPrice" 
                        class="w-full p-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:border-indigo-600"
                    />
                </div>
                <div class="p-3 bg-purple-50 rounded-lg border border-purple-100 flex items-center justify-between">
                    <span class="text-slate-600">Estimated Bank Transfer Payout:</span>
                    <span class="font-extrabold text-indigo-700 text-sm" x-text="'₹' + Math.round(newPrice * 0.76)"></span>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-2">
                <button @click="editModal = false" type="button" class="px-4 py-2 border border-slate-300 rounded-lg font-bold text-xs text-slate-600 hover:bg-slate-50">Cancel</button>
                <button @click="savePrice()" type="button" class="px-4 py-2 bg-[#3e2e84] text-white rounded-lg font-bold text-xs hover:bg-[#32236e]">Save Price</button>
            </div>
        </div>
    </div>
</div>
@endsection
