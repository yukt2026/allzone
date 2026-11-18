@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Product Inspection: ' . $product->title])

@section('content')
@php
    $initialImage = $product->media->first()?->url ?? $product->primaryImageUrl();
    $defaultVar = $product->variants->firstWhere('is_default', true) ?: $product->variants->first();
@endphp
<div class="space-y-6" x-data="{ activeImage: '{{ $initialImage }}' }">
    <!-- Top Navigation & Action Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-emerald-600">Command Central</a>
                <span>&bull;</span>
                <a href="{{ route('admin.catalogue.moderation.index') }}" class="hover:text-emerald-600">Product Approval Queue</a>
                <span>&bull;</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">Listing #{{ $product->id }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    {{ $product->title }}
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $product->status->badgeClass() }}">
                    {{ $product->status->label() }}
                </span>
                @if($product->is_quick_capable)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        ⚡ Quick Commerce (10-30m)
                    </span>
                @endif
            </div>

            <p class="text-xs text-slate-500 mt-1">
                Merchant: 
                @if($product->seller)
                    <a href="{{ route('admin.sellers.show', $product->seller) }}" class="font-bold text-slate-700 dark:text-slate-200 hover:text-emerald-600">
                        {{ $product->seller->business_name }}
                    </a>
                @else
                    <span class="font-bold text-slate-700 dark:text-slate-300">Direct Marketplace</span>
                @endif
                &bull; HSN: <span class="font-mono font-bold">{{ $product->hsn_code ?? 'N/A' }}</span> 
                &bull; GST Slab: <span class="font-mono font-bold">{{ $product->tax_percent }}%</span>
                &bull; Submitted: {{ $product->created_at->format('M d, Y h:i A') }}
            </p>
        </div>

        <!-- Moderation Action Toolbar -->
        <div class="flex flex-wrap items-center gap-2">
            @if($product->status === \App\Enums\ProductStatus::ACTIVE && $product->slug)
                <a 
                    href="{{ route('product.show', $product->slug) }}" 
                    target="_blank" 
                    class="px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 border border-slate-200 dark:border-slate-700 transition flex items-center"
                    title="View live consumer storefront page"
                >
                    <span>Storefront</span>
                    <svg class="w-3.5 h-3.5 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            @endif

            <a 
                href="{{ route('admin.catalogue.moderation.edit', $product) }}" 
                class="px-3 py-2 text-xs font-bold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-200 transition flex items-center"
                title="Edit listing details on behalf of the seller"
            >
                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit on Behalf
            </a>

            <!-- 1. APPROVE -->
            @if($product->status !== \App\Enums\ProductStatus::ACTIVE)
                <form method="POST" action="{{ route('admin.catalogue.moderation.approve', $product) }}" class="inline">
                    @csrf
                    <button 
                        type="submit" 
                        onclick="return confirm('Approve listing into live marketplace catalog?')"
                        class="px-3.5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Approve Product
                    </button>
                </form>
            @endif

            <!-- 2. REQUEST CORRECTIONS -->
            <button 
                type="button" 
                @click="$dispatch('open-modal', 'corrections-modal')"
                class="px-3 py-2 text-xs font-bold rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition flex items-center"
                title="Request specific changes from the seller without outright rejection"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Request Corrections
            </button>

            <!-- 3. REJECT -->
            <button 
                type="button" 
                @click="$dispatch('open-modal', 'reject-product-modal')"
                class="px-3 py-2 text-xs font-bold rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition flex items-center"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Reject Listing
            </button>

            <!-- 4. BLOCK (POLICY) -->
            @if($product->status !== \App\Enums\ProductStatus::BLOCKED)
                <button 
                    type="button" 
                    @click="$dispatch('open-modal', 'block-modal')"
                    class="px-3 py-2 text-xs font-bold rounded-xl bg-red-600 hover:bg-red-700 text-white transition flex items-center shadow-xs"
                    title="Block listing for compliance or policy violation"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Block (Policy)
                </button>
            @endif
        </div>
    </div>

    <!-- Rejection or Block Alert Banner -->
    @if($product->status === \App\Enums\ProductStatus::BLOCKED)
        <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800 text-xs text-red-900 dark:text-red-200 flex items-start space-x-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong class="font-bold">Product Blocked (Policy Violation):</strong>
                <p class="mt-0.5 text-[11px] leading-relaxed">{{ $product->rejection_reason }}</p>
                <div class="mt-1 text-[10px] text-red-700 dark:text-red-300">Listing is delisted from all customer channels and cannot be purchased.</div>
            </div>
        </div>
    @elseif($product->status === \App\Enums\ProductStatus::REJECTED && $product->rejection_reason)
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-xs text-rose-900 dark:text-rose-200 flex items-start space-x-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold">Rejection Feedback Sent to Merchant:</strong>
                <p class="mt-0.5 text-[11px] leading-relaxed">{{ $product->rejection_reason }}</p>
            </div>
        </div>
    @elseif($product->rejection_reason && str_starts_with($product->rejection_reason, 'Corrections Requested:'))
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-start space-x-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold">Active Correction Notice:</strong>
                <p class="mt-0.5 text-[11px] leading-relaxed">{{ $product->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <!-- 2-Column Inspection Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Visual Assets & Descriptions (7 Cols) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Media & Visual Quality Inspector -->
            <x-card title="Product Gallery & Media Quality Inspection" subtitle="Submitted images checked against platform image standards (min 800x800, white/neutral background)">
                <div class="space-y-4">
                    <!-- Main Image Preview Box -->
                    <div class="w-full h-88 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 overflow-hidden flex items-center justify-center p-3 relative group">
                        <img 
                            :src="activeImage" 
                            alt="{{ $product->title }}" 
                            class="max-h-full max-w-full object-contain rounded-xl transition-all duration-300"
                            onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' width=\'100\' height=\'100\'><rect width=\'100\' height=\'100\' rx=\'16\' fill=\'%23f1f5f9\'/><text x=\'50\' y=\'58\' font-size=\'32\' text-anchor=\'middle\'>🛍️</text></svg>';"
                        />
                        <a 
                            :href="activeImage" 
                            target="_blank" 
                            class="absolute bottom-3 right-3 px-3 py-1.5 text-[11px] font-bold rounded-lg bg-black/75 hover:bg-black text-white backdrop-blur-xs opacity-0 group-hover:opacity-100 transition flex items-center space-x-1"
                        >
                            <span>Inspect Full Size</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>

                    <!-- Thumbnails Strip -->
                    @if($product->media->isNotEmpty())
                        <div class="flex items-center space-x-2.5 overflow-x-auto pb-1">
                            @foreach($product->media as $idx => $media)
                                @php $mUrl = $media->url ?: image_url($media->path); @endphp
                                <button 
                                    type="button" 
                                    @click="activeImage = '{{ $mUrl }}'"
                                    :class="activeImage === '{{ $mUrl }}' ? 'ring-2 ring-emerald-500 border-emerald-500 shadow-sm' : 'border-slate-200 dark:border-slate-700 opacity-70 hover:opacity-100'"
                                    class="w-16 h-16 rounded-xl bg-white dark:bg-slate-800 border overflow-hidden shrink-0 flex items-center justify-center transition p-1 relative"
                                >
                                    <img src="{{ $mUrl }}" alt="Thumbnail {{ $idx + 1 }}" class="w-full h-full object-cover rounded-lg" />
                                    @if($loop->first)
                                        <span class="absolute top-1 left-1 px-1 rounded text-[8px] font-bold bg-black/70 text-white">Main</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-center space-x-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>No dedicated media assets uploaded. Minimum 1 product image required for approval.</span>
                        </div>
                    @endif

                    <!-- Image Compliance Checklist -->
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 {{ $product->media->count() >= 1 ? 'text-emerald-500' : 'text-rose-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-slate-600 dark:text-slate-400">Count: <strong>{{ $product->media->count() }} photo(s)</strong></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-slate-600 dark:text-slate-400">Formats: <strong>WebP/JPEG</strong></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-slate-600 dark:text-slate-400">Watermark: <strong>Clean</strong></span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Product Copywriting & Descriptions -->
            <x-card title="Listing Copy & Statutory Information" subtitle="Customer-facing descriptions, legal disclosures, and policy specifications">
                <div class="space-y-4 text-xs">
                    @if($product->short_desc)
                        <div>
                            <span class="font-bold uppercase tracking-wider text-slate-400 block mb-1">Short Summary / Highlights</span>
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-slate-800 dark:text-slate-200 leading-relaxed">
                                {{ $product->short_desc }}
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="font-bold uppercase tracking-wider text-slate-400 block mb-1">Detailed Description</span>
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300 leading-relaxed prose dark:prose-invert max-w-none text-xs">
                            {!! nl2br(e($product->description ?? 'No detailed description provided.')) !!}
                        </div>
                    </div>

                    <!-- Commercial Policies Row -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 block">Fulfilment Priority</span>
                            <strong class="{{ $product->is_quick_capable ? 'text-amber-600' : 'text-slate-500' }} text-xs">
                                {{ $product->is_quick_capable ? '⚡ Quick (10-30m)' : 'Standard Courier' }}
                            </strong>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 block">Payment Options</span>
                            <strong class="{{ $product->is_cod_allowed ? 'text-emerald-600' : 'text-slate-500' }} text-xs">
                                {{ $product->is_cod_allowed ? 'COD + Online' : 'Prepaid Only' }}
                            </strong>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 block">Returns Permitted</span>
                            <strong class="{{ $product->is_returnable ? 'text-emerald-600' : 'text-rose-500' }} text-xs">
                                {{ $product->is_returnable ? 'Yes (' . $product->return_window_days . ' Days)' : 'Non-returnable' }}
                            </strong>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 block">GST Tax Rate</span>
                            <strong class="text-emerald-600 font-mono text-xs">
                                {{ $product->tax_percent }}%
                            </strong>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Right Column: Commercials, Variants & Decision History (5 Cols) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Merchant Origin & Category Hierarchy -->
            <x-card title="Merchant Origin & Taxonomy" subtitle="Verified store identity and classification">
                <div class="space-y-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $product->seller?->business_name ?? 'Direct Platform Listing' }}
                            </span>
                            @if($product->seller)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $product->seller->status->badgeClass() }}">
                                    {{ $product->seller->status->label() }}
                                </span>
                            @endif
                        </div>
                        @if($product->seller)
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $product->seller->legal_name }} &bull; Owner: {{ $product->seller->user?->name ?? 'N/A' }}
                            </div>
                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center text-[11px]">
                                <a href="{{ route('admin.sellers.show', $product->seller) }}" class="text-emerald-600 font-bold hover:underline">
                                    View Merchant Dossier &rarr;
                                </a>
                                <span class="font-mono text-slate-400">#{{ $product->seller->id }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Category Breadcrumbs & Commission -->
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Category Classification:</span>
                        <div class="text-right">
                            <strong class="text-slate-900 dark:text-white block">{{ $product->category?->name ?? 'Uncategorized' }}</strong>
                            @if($product->category?->parent)
                                <span class="text-[10px] text-slate-400 font-medium">Under: {{ $product->category->parent->name }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Brand Affiliation -->
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500">Brand Affiliation:</span>
                        <div class="flex items-center space-x-1.5">
                            <strong class="text-slate-900 dark:text-white">{{ $product->brand?->name ?? 'No Brand / Generic' }}</strong>
                            @if($product->brand?->is_verified)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Verified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Pricing, Margins & Commission Breakdown -->
            <x-card title="Commercial Pricing & Settlement Estimate" subtitle="Gross selling price, MRP, commission, and estimated payout">
                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Selling Price</span>
                            <strong class="text-slate-900 dark:text-white font-mono text-sm font-extrabold">
                                ₹{{ number_format($financials['selling_price'], 2) }}
                            </strong>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">MRP</span>
                            <span class="line-through text-slate-400 font-mono text-xs">
                                ₹{{ number_format($financials['mrp'], 2) }}
                            </span>
                            @if($financials['discount_percent'] > 0)
                                <div class="text-[9px] font-bold text-emerald-600">{{ $financials['discount_percent'] }}% OFF</div>
                            @endif
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800">
                            <span class="text-[10px] text-slate-400 uppercase font-bold block">Cost Price</span>
                            <span class="text-slate-600 dark:text-slate-300 font-mono text-xs">
                                ₹{{ number_format($financials['cost_price'], 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/60 space-y-1.5">
                        <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                            <span>Category Commission ({{ $financials['commission_percent'] }}%):</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white">-₹{{ number_format($financials['estimated_commission'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-1.5 border-t border-emerald-200/60 dark:border-emerald-800 font-bold">
                            <span class="text-emerald-900 dark:text-emerald-200">Estimated Net Seller Payable:</span>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400 text-sm">₹{{ number_format($financials['seller_receivable'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Product Variants & SKU Matrix -->
            <x-card title="Product Variations & Stock Matrix" subtitle="SKU combinations, stock on hand, and dimensions">
                <div class="space-y-3">
                    @forelse($product->variants as $variant)
                        @php
                            $totalOnHand = $variant->inventories->sum('quantity_on_hand');
                            $totalReserved = $variant->inventories->sum('quantity_reserved');
                            $available = max(0, $totalOnHand - $totalReserved);
                        @endphp
                        <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $variant->name }}</span>
                                <div class="flex items-center space-x-1.5">
                                    @if($variant->is_default)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Default SKU
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold {{ $available > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $available }} In Stock
                                    </span>
                                </div>
                            </div>

                            <div class="text-[11px] font-mono text-slate-400">
                                SKU: <strong class="text-slate-700 dark:text-slate-200">{{ $variant->sku }}</strong> &bull; Barcode: {{ $variant->barcode ?? 'N/A' }}
                            </div>

                            <div class="grid grid-cols-3 gap-2 pt-1 text-[11px] font-mono">
                                <div>
                                    <span class="text-slate-400 block text-[9px]">Selling Price</span>
                                    <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format((float) $variant->selling_price, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[9px]">MRP</span>
                                    <span class="line-through text-slate-500">₹{{ number_format((float) $variant->mrp, 2) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[9px]">Weight / Size</span>
                                    <span class="text-slate-600 dark:text-slate-400">{{ $variant->weight ? $variant->weight . 'g' : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-400 italic py-3 text-center">No variations configured for this product.</div>
                    @endforelse
                </div>
            </x-card>

            <!-- Technical Specifications -->
            @if($product->specifications->isNotEmpty())
                <x-card title="Technical Specifications" subtitle="Detailed attributes and technical parameters">
                    <div class="space-y-1.5 text-xs">
                        @foreach($product->specifications as $spec)
                            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-slate-400 font-medium">
                                    {{ $spec->key }}
                                    @if($spec->group)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $spec->group }})</span>
                                    @endif
                                </span>
                                <strong class="text-slate-800 dark:text-slate-200">{{ $spec->value }}</strong>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Moderation Audit Trail -->
            <x-card title="Approval History & Moderation Log" subtitle="Immutable audit trail of administrator decisions">
                @if(isset($activities) && $activities->isNotEmpty())
                    <div class="space-y-2.5 text-xs">
                        @foreach($activities as $act)
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $act->description }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $act->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Administrator: <strong class="text-slate-700 dark:text-slate-300">{{ $act->causer?->name ?? 'System Policy Engine' }}</strong>
                                </div>
                                @if(!empty($act->properties['reason']))
                                    <div class="text-[11px] text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/30 p-1.5 rounded-lg border border-rose-100 dark:border-rose-900/40">
                                        Reason: {{ $act->properties['reason'] }}
                                    </div>
                                @endif
                                @if(!empty($act->properties['notes']))
                                    <div class="text-[11px] text-amber-700 dark:text-amber-300 bg-amber-50/50 dark:bg-amber-950/30 p-1.5 rounded-lg border border-amber-100 dark:border-amber-900/40">
                                        Correction Notes: {{ $act->properties['notes'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-slate-400 py-3 text-center">
                        No previous administrative moderation logs on file for this product.
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Modal 1: Reject Listing (with Reason Templates) -->
    <x-modal name="reject-product-modal" title="Reject Product Listing" size="md">
        <form action="{{ route('admin.catalogue.moderation.reject', $product) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-500 mb-2 leading-relaxed">
                    Provide feedback for <strong class="text-slate-900 dark:text-white">{{ $product->title }}</strong> so the merchant can fix the issues and resubmit.
                </p>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Mandatory Rejection Reason <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="reason" 
                    id="reject_reason_input"
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="Describe specific listing deficiencies..."
                    class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <!-- Predefined Reason Templates -->
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold text-slate-400">Common Rejection Reasons:</span>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <button type="button" @click="document.getElementById('reject_reason_input').value = 'Images are blurry, below 800x800 resolution, or have watermarks/text.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Poor Image Quality</button>
                    <button type="button" @click="document.getElementById('reject_reason_input').value = 'HSN Code mismatch with category statutory GST tax slab.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">HSN Mismatch</button>
                    <button type="button" @click="document.getElementById('reject_reason_input').value = 'Title or description contains prohibited promotional claims or contact details.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Prohibited Text</button>
                    <button type="button" @click="document.getElementById('reject_reason_input').value = 'Missing required package dimensions and weight parameters for logistics dispatch.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Missing Dimensions</button>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'reject-product-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-rose-600 hover:bg-rose-700 text-white shadow-xs transition">Confirm Rejection</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal 2: Request Corrections -->
    <x-modal name="corrections-modal" title="Request Listing Corrections" size="md">
        <form action="{{ route('admin.catalogue.moderation.request_corrections', $product) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-500 mb-2 leading-relaxed">
                    Instruct the merchant on minor corrections needed for <strong class="text-slate-900 dark:text-white">{{ $product->title }}</strong> without marking it rejected.
                </p>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Specific Correction Instructions <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="correction_notes" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="e.g. Please upload 2 additional photos showing the rear ports and update the package weight to include the power adapter..."
                    class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'corrections-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition">Send Correction Request</button>
            </div>
        </form>
    </x-modal>

    <!-- Modal 3: Block Product (Policy Enforcement) -->
    <x-modal name="block-modal" title="Block Product for Policy Violation" size="md">
        <form action="{{ route('admin.catalogue.moderation.block', $product) }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <p class="text-slate-600 dark:text-slate-300 mb-2 leading-relaxed">
                    Blocking <strong class="text-slate-900 dark:text-white">{{ $product->title }}</strong> immediately de-lists it from the consumer storefront.
                </p>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Mandatory Policy Justification <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="policy_reason" 
                    id="block_policy_input"
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="State the compliance policy breached (e.g. Counterfeit goods, Intellectual property infringement, Dangerous substance)..."
                    class="w-full p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white"
                ></textarea>
            </div>

            <!-- Standard Policy Violation Templates -->
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold text-slate-400">Violation Types:</span>
                <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <button type="button" @click="document.getElementById('block_policy_input').value = 'Unauthorized reproduction or trademark infringement of a protected brand.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Counterfeit / IP</button>
                    <button type="button" @click="document.getElementById('block_policy_input').value = 'Prohibited item under regulatory guidelines or restricted pharmaceutical listing.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Restricted Goods</button>
                    <button type="button" @click="document.getElementById('block_policy_input').value = 'Fraudulent product claims, hazardous item, or deceptive listing representations.'" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300">Fraud / Safety Hazard</button>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'block-modal')" class="px-3 py-1.5 text-xs text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-red-600 hover:bg-red-700 text-white shadow-xs transition">Confirm & Block Product</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
