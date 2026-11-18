@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Inventory - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    tab: 'all_stock',
    subFilter: 'all'
}">
    <!-- Top Header (Screenshot 3 & 4) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Inventory
        </h1>

        <div class="flex items-center space-x-3 text-xs">
            <a href="#" class="text-slate-600 font-semibold hover:text-slate-900">How it Works?</a>
            <div class="relative">
                <input type="text" placeholder="Search by Catalog ID / Style ID / SKU ID" class="py-1.5 pl-3 pr-8 border border-slate-300 rounded-lg text-xs w-64">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <a href="{{ route('seller.products.create') }}" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold flex items-center space-x-1 shadow-xs">
                <span>⬆</span>
                <span>Catalog Upload</span>
            </a>
        </div>
    </div>

    <!-- Maha Indian Savings Sale Countdown Banner (Screenshot 3 & 4) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <span class="px-1.5 py-0.2 rounded bg-rose-50 text-[10px] font-black text-rose-600 border border-rose-200">
                    MAHA INDIAN SAVINGS SALE
                </span>
                <span>⏱ 2h : 21m : 23s</span>
            </div>
            <h3 class="text-sm font-bold text-slate-900">
                Add timer on Nov &amp; Dec Sale events! Get extra 40% growth*
            </h3>
            <div class="mt-3">
                <a href="{{ route('seller.pricing.index') }}" class="px-4 py-1.5 rounded-lg bg-[#3e2e84] text-white text-xs font-bold hover:bg-[#32236e] transition">
                    Add flash discount
                </a>
            </div>
        </div>
        <div class="flex items-center space-x-6 text-xs text-slate-600 font-bold shrink-0">
            <div>
                <div class="text-[10px] text-slate-400 font-normal">current growth</div>
                <div>220%</div>
            </div>
            <div class="text-emerald-600">
                <div class="text-[10px] text-slate-400 font-normal">flash growth</div>
                <div>260% (+40%)</div>
            </div>
        </div>
    </div>

    <!-- Status Tabs Rail (Screenshot 3) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-3 text-xs font-bold">
            <button @click="tab = 'all_stock'" :class="tab === 'all_stock' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                All Stock (9)
            </button>
            <button @click="tab = 'out_of_stock'" :class="tab === 'out_of_stock' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Out of Stock (5)
            </button>
            <button @click="tab = 'low_stock'" :class="tab === 'low_stock' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Low Stock (0) ⓘ
            </button>
            <button class="pb-3 border-b-2 border-transparent text-slate-400">
                Blocked (2)
            </button>
            <button class="pb-3 border-b-2 border-transparent text-slate-400">
                Paused (11) ⓘ
            </button>
        </div>

        <!-- Filter Subbar -->
        <div class="p-3.5 bg-slate-50/50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center space-x-3">
                <select class="py-1 px-3 border border-slate-300 rounded text-slate-700 font-medium">
                    <option>Select Category</option>
                </select>
                <select class="py-1 px-3 border border-slate-300 rounded text-slate-700 font-medium">
                    <option>Out of Stock Since</option>
                </select>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-slate-500">Sort catalogs by:</span>
                <select class="py-1 px-3 border border-slate-300 rounded text-slate-700 font-bold">
                    <option>Highest Estimated Orders</option>
                    <option>Lowest Estimated Orders</option>
                </select>
                <button type="button" class="text-[#53389e] font-bold hover:underline flex items-center space-x-1">
                    <span>🗐</span>
                    <span>Bulk Stock Update</span>
                </button>
            </div>
        </div>

        <!-- Catalog Out of Stock Split View (Screenshot 4) -->
        <div class="grid grid-cols-1 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
            <!-- Left Rail: Catalog Thumbnails -->
            <div class="md:col-span-1 p-3 space-y-3 bg-slate-50/30">
                <div class="p-3 bg-white border border-purple-300 rounded-xl shadow-xs">
                    <div class="font-bold text-slate-900">Trendy Kids Gun Toys</div>
                    <div class="text-[10px] text-slate-400">Catalog ID: 559139947</div>
                    <div class="text-[10px] text-slate-400">Category: Gun Toys</div>
                </div>
                <div class="p-3 bg-white border border-slate-200 rounded-xl">
                    <div class="font-bold text-slate-900">Wonderful Kids Remote Control Toys</div>
                    <div class="text-[10px] text-slate-400">Catalog ID: 558019594</div>
                </div>
                <div class="p-3 bg-white border border-slate-200 rounded-xl">
                    <div class="font-bold text-slate-900">New Kids Remote Control Toys</div>
                    <div class="text-[10px] text-slate-400">Catalog ID: 538981047</div>
                </div>
            </div>

            <!-- Right Detail Table: SKU Variants & Stock Actions (Screenshot 4) -->
            <div class="md:col-span-3 p-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Trendy Kids Gun Toys</h4>
                        <div class="text-[11px] text-slate-400">Catalog ID: 559139947 &bull; Category: Gun Toys</div>
                    </div>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                        <tr>
                            <th class="p-2 w-8"><input type="checkbox"></th>
                            <th class="p-2">SKU</th>
                            <th class="p-2">Variation</th>
                            <th class="p-2">Estimated Order Per Day ⓘ</th>
                            <th class="p-2">Estimated Monthly Loss ⓘ</th>
                            <th class="p-2">Stock</th>
                            <th class="p-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-2"><input type="checkbox"></td>
                            <td class="p-2">
                                <div class="font-bold text-slate-900">FLYING ARMOR ELECTRIC BUBBLE GUN RICH</div>
                                <div class="text-[10px] text-slate-400">Style ID: OXM-WTR-GN-26 &bull; SKU: OXM-WTR-GN-26</div>
                                <div class="text-[10px] font-bold text-slate-700">Meesho Price: ₹376</div>
                            </td>
                            <td class="p-2 text-slate-600">Free Size</td>
                            <td class="p-2 text-slate-400">-</td>
                            <td class="p-2 text-slate-400">-</td>
                            <td class="p-2">
                                <span class="text-rose-600 font-bold flex items-center space-x-1">
                                    <span>⚠️</span>
                                    <span>Out of Stock</span>
                                </span>
                                <div class="text-[10px] text-slate-400">Since 37 days</div>
                                <div class="font-bold text-slate-800 mt-0.5 flex items-center space-x-1">
                                    <span>Current Stock: 0</span>
                                    <span class="cursor-pointer text-indigo-600">✏️</span>
                                </div>
                            </td>
                            <td class="p-2 text-right">
                                <button type="button" class="px-2.5 py-1 text-xs font-bold text-slate-700 hover:text-indigo-600">Edit</button>
                                <button type="button" class="px-2.5 py-1 text-xs font-bold text-slate-400 hover:text-slate-700">More</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
