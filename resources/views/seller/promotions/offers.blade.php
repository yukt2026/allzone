@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Daily Offers & Flexi Deals - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('seller.promotions.index') }}" class="hover:text-slate-700">Promotions Hub</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Daily Offers &amp; Flexi Deals</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daily Offers &amp; Flexi Deals</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure immediate item discounts, spotlight flash deals, and manage daily store promotions.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('seller.promotions.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-2xs">
                &larr; Promotional Campaigns &amp; Sales
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Assurance Banner -->
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 uppercase">
                    DAILY PROMOTIONAL DISCOUNTS
                </span>
                <span class="text-xs text-emerald-200 font-medium">Immediate Buyer Activation</span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-white">
                Boost Specific Catalog Listings
            </h3>
            <p class="text-xs text-emerald-100/90 leading-relaxed max-w-2xl">
                Set selective item-level discounts directly from your inventory. Flexi deals allow flexible discounting without waiting for organized platform mega sales.
            </p>
        </div>
        <div class="shrink-0 flex items-center space-x-2">
            <a href="{{ route('seller.pricing.index') }}" class="px-4 py-2 rounded-xl bg-white text-emerald-900 font-black text-xs hover:bg-emerald-50 transition shadow-2xs">
                Manage Base Pricing &rarr;
            </a>
        </div>
    </div>

    <!-- Product Offers Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form action="{{ route('seller.promotions.offers') }}" method="GET" class="relative max-w-sm w-full">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search your products by title or ID..."
                    class="w-full pl-9 pr-3 py-1.5 border border-slate-300 rounded-lg text-xs placeholder-slate-400 focus:outline-hidden focus:border-emerald-500"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </form>

            <div>
                <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold font-mono">
                    Total Products: {{ $products->total() }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Product</th>
                        <th class="p-3">Standard Selling Price</th>
                        <th class="p-3">Suggested Flexi Discount</th>
                        <th class="p-3">Promotional Price</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        @php
                            $variant = $product->variants->first();
                            $price = (float) ($variant?->selling_price ?? $variant?->price ?? 499);
                            $discountPercent = 10;
                            $afterDiscount = round($price * (1 - ($discountPercent / 100)), 2);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 max-w-xs truncate">{{ $product->title }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">ID: #{{ $product->id }} &bull; SKU: {{ $variant?->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="p-3 font-mono font-bold text-slate-700">
                                ₹{{ number_format($price, 2) }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded font-bold text-xs bg-emerald-100 text-emerald-800">
                                    {{ $discountPercent }}% OFF
                                </span>
                            </td>
                            <td class="p-3 font-mono font-black text-emerald-700 text-sm">
                                ₹{{ number_format($afterDiscount, 2) }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold">
                                    Eligible for Deals
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('seller.pricing.index', ['search' => $product->id]) }}" class="px-3 py-1 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition">
                                    Set Deal Price
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <div class="text-2xl">📦</div>
                                    <div class="font-bold text-slate-700 text-sm">No products found</div>
                                    <p class="text-xs text-slate-500">
                                        Add products to your catalog to unlock flexi growth deals and promotional discounts.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
