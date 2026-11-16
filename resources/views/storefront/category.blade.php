@extends('layouts.storefront')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center space-x-2 text-xs text-slate-500 overflow-x-auto no-scrollbar">
        @foreach($breadcrumbs as $crumb)
            @if(!$loop->last)
                <a href="{{ $crumb['url'] }}" class="hover:text-indigo-600 font-medium whitespace-nowrap">{{ $crumb['name'] }}</a>
                <span class="text-slate-300">/</span>
            @else
                <span class="font-bold text-slate-800 whitespace-nowrap">{{ $crumb['name'] }}</span>
            @endif
        @endforeach
    </nav>

    @php
        $categoryVisualMap = [
            'mobiles-smartphones' => 'images/category-heroes/mobiles-smartphones.svg',
            'refrigerators-freezers' => 'images/category-heroes/refrigerators-freezers.svg',
            'televisions' => 'images/category-heroes/televisions.svg',
            'air-conditioners' => 'images/category-heroes/air-conditioners.svg',
            'laptops-computers' => 'images/category-heroes/laptops-computers.svg',
            'audio-headphones' => 'images/category-heroes/audio-headphones.svg',
            'home-appliances' => 'images/category-heroes/home-appliances.svg',
            'computer-accessories' => 'images/category-heroes/computer-accessories.svg',
        ];

        $heroVisualPath = $categoryVisualMap[$category->slug] ?? null;
        if ($heroVisualPath && file_exists(public_path($heroVisualPath))) {
            $heroVisualUrl = asset($heroVisualPath);
        } elseif (!empty($category->image) && file_exists(public_path('storage/' . $category->image))) {
            $heroVisualUrl = asset('storage/' . $category->image);
        } elseif ($products->isNotEmpty() && $products->first()->primary_image_url) {
            $heroVisualUrl = $products->first()->primary_image_url;
        } else {
            $heroVisualUrl = asset('images/category-heroes/default.svg');
        }
    @endphp

    <!-- Premium Category Hero Banner Component -->
    <div class="relative rounded-2xl bg-slate-950 text-white border border-slate-800/80 shadow-md shadow-slate-950/20 overflow-hidden"
         style="background: linear-gradient(135deg, #060D1D 0%, #0B152B 50%, #081022 100%);">
        <!-- Ambient Decorative Lighting Effects -->
        <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-indigo-600/15 blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute right-1/4 -bottom-20 w-80 h-80 rounded-full bg-blue-600/10 blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -left-16 -bottom-16 w-56 h-56 rounded-full bg-indigo-900/20 blur-2xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10 p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between gap-4 lg:gap-8">
                <!-- Left Section: Category label, heading, description, trust row, CTA -->
                <div class="flex-1 min-w-0 space-y-2.5 sm:space-y-3.5">
                    <!-- Badges Row -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                            Electronics Store
                        </span>
                        @if($category->is_quick_eligible)
                            <span class="inline-flex items-center space-x-1 px-2 sm:px-2.5 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                <span class="text-amber-400">⚡</span>
                                <span>10–30 Min Express Delivery</span>
                            </span>
                        @else
                            <span class="inline-flex items-center space-x-1 px-2 sm:px-2.5 py-0.5 rounded-md text-[10px] sm:text-[11px] font-bold uppercase tracking-wider bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Genuine Brand Warranty</span>
                            </span>
                        @endif
                    </div>

                    <!-- Category Heading -->
                    <h1 class="text-xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight leading-snug sm:leading-tight">
                        {{ $category->name }}
                    </h1>

                    <!-- Category Description -->
                    <p class="text-xs sm:text-sm text-slate-300 font-normal leading-relaxed line-clamp-2 sm:line-clamp-none max-w-2xl">
                        {{ $category->meta_description ?: $category->description ?: ('Explore verified ' . $category->name . ' with 100% genuine brand warranty, local store inventory, and certified doorstep installation on ALLZONE.') }}
                    </p>

                    <!-- Trust Indicators & Quick CTA (Desktop/Tablet) -->
                    <div class="hidden sm:flex flex-wrap items-center gap-x-5 gap-y-2 pt-1 text-xs text-slate-300">
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="font-medium">100% Genuine</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span class="font-medium">Brand Warranty</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            <span class="font-medium">Express Dispatch</span>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Large Attractive Electronics Product Visual -->
                <div class="w-24 h-24 sm:w-44 sm:h-36 md:w-64 md:h-48 lg:w-80 lg:h-56 flex-shrink-0 flex items-center justify-center relative">
                    <img src="{{ $heroVisualUrl }}" 
                         alt="{{ $category->name }}" 
                         class="max-w-full max-h-full object-contain filter drop-shadow-[0_10px_20px_rgba(0,0,0,0.45)] transition-transform duration-500 hover:scale-105 pointer-events-none select-none"
                         loading="eager"
                         onerror="this.onerror=null; this.src='{{ asset('images/category-heroes/default.svg') }}';">
                </div>
            </div>

            <!-- Subcategories Horizontal Pills (if available) -->
            @if($category->children && $category->children->isNotEmpty())
                <div class="pt-3 mt-3 border-t border-slate-800/80 flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <span class="text-[11px] font-bold text-slate-400 mr-1 hidden sm:inline">Subcategories:</span>
                    @foreach($category->children as $child)
                        <a href="{{ route('category.show', $child->slug) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white text-[11px] sm:text-xs font-semibold backdrop-blur-sm border border-white/10 transition">
                            <span>{{ $child->name }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Products & Filter Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start" x-data="{ mobileFilterOpen: false }">

        <!-- Mobile Filter Button Trigger -->
        <div class="lg:hidden flex items-center justify-between">
            <button type="button"
                    @click="mobileFilterOpen = !mobileFilterOpen"
                    class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-800 flex items-center space-x-2 shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Filters</span>
            </button>

            <!-- Sorting Dropdown (Mobile) -->
            <form method="GET" action="{{ url()->current() }}">
                <select name="sort" onchange="this.form.submit()" class="text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl py-2 pl-3 pr-8 focus:ring-indigo-500">
                    <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>Relevance & Nearby</option>
                    <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="discount_desc" {{ $sort === 'discount_desc' ? 'selected' : '' }}>Top Discount</option>
                    <option value="rating_desc" {{ $sort === 'rating_desc' ? 'selected' : '' }}>Rating</option>
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
                </select>
            </form>
        </div>

        <!-- Filter Sidebar -->
        <aside :class="mobileFilterOpen ? 'fixed inset-0 z-50 bg-white p-6 overflow-y-auto' : 'hidden lg:block'"
               class="bg-white rounded-3xl border border-slate-200/80 p-5 space-y-6 shadow-sm">

            @php
                $hasActiveFilters = !empty($filters['brands']) || !empty($filters['price_min']) || !empty($filters['price_max']) || !empty($filters['in_stock']) || !empty($filters['delivery_type']);
            @endphp

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Filters</h3>
                    @if($hasActiveFilters)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-blue-100 text-blue-700">Active</span>
                    @endif
                </div>
                <div class="flex items-center space-x-3">
                    @if($hasActiveFilters)
                        <a href="{{ url()->current() }}" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline">
                            Reset All
                        </a>
                    @endif
                    <button type="button" @click="mobileFilterOpen = false" class="lg:hidden p-1 text-slate-400 hover:text-slate-700" aria-label="Close filters">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ url()->current() }}" class="space-y-6 text-xs">
                <input type="hidden" name="sort" value="{{ $sort }}">

                <!-- Delivery Speed -->
                <div class="space-y-2">
                    <h4 class="font-bold text-slate-900 text-xs">Delivery Speed</h4>
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="radio" name="delivery_type" value="" {{ empty($filters['delivery_type']) ? 'checked' : '' }} onchange="this.form.submit()" class="text-blue-600 focus:ring-0">
                        <span class="text-slate-600">All Delivery Speeds</span>
                    </label>
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="radio" name="delivery_type" value="quick" {{ $filters['delivery_type'] === 'quick' ? 'checked' : '' }} onchange="this.form.submit()" class="text-blue-600 focus:ring-0">
                        <span class="font-extrabold text-blue-600 flex items-center">
                            <span>⚡ Express Dispatch</span>
                        </span>
                    </label>
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="radio" name="delivery_type" value="pickup" {{ $filters['delivery_type'] === 'pickup' ? 'checked' : '' }} onchange="this.form.submit()" class="text-blue-600 focus:ring-0">
                        <span class="text-slate-700">🛍️ Store Pickup</span>
                    </label>
                </div>

                <!-- Availability Toggle -->
                <div class="pt-3 border-t border-slate-100">
                    <label class="flex items-center justify-between cursor-pointer">
                        <span class="font-bold text-slate-900">In Stock Only</span>
                        <input type="checkbox" name="in_stock" value="1" {{ !empty($filters['in_stock']) ? 'checked' : '' }} onchange="this.form.submit()" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                    </label>
                </div>

                <!-- Price Range -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <h4 class="font-bold text-slate-900 text-xs">Price Range (₹)</h4>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="price_min" value="{{ $filters['price_min'] ?? '' }}" placeholder="Min" class="w-full text-xs p-2 rounded-xl border border-slate-200">
                        <span class="text-slate-400">-</span>
                        <input type="number" name="price_max" value="{{ $filters['price_max'] ?? '' }}" placeholder="Max" class="w-full text-xs p-2 rounded-xl border border-slate-200">
                        <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition">Go</button>
                    </div>
                </div>

                <!-- Brands Filter -->
                @if(isset($facets['brands']) && $facets['brands']->isNotEmpty())
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <h4 class="font-bold text-slate-900 text-xs">Brands</h4>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            @foreach($facets['brands'] as $b)
                                <label class="flex items-center justify-between text-slate-600 hover:text-slate-900 cursor-pointer">
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" name="brands[]" value="{{ $b->slug }}"
                                               {{ in_array($b->slug, (array) ($filters['brands'] ?? []), true) ? 'checked' : '' }}
                                               onchange="this.form.submit()"
                                               class="rounded text-blue-600 focus:ring-blue-500 h-3.5 w-3.5">
                                        <span class="truncate max-w-[140px]">{{ $b->name }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ $b->products_count }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center space-x-2 pt-2">
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                        Apply Filters
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ url()->current() }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs transition text-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </aside>

        <!-- Product Grid -->
        <main class="lg:col-span-3 space-y-5">
            <!-- Active Filter Chips & Reset Bar -->
            @if($hasActiveFilters)
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 p-2.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <span class="text-xs font-bold text-slate-500 mr-1">Active:</span>

                    @if(!empty($filters['delivery_type']))
                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 border border-slate-200 shadow-2xs">
                            <span>Delivery: {{ ucfirst($filters['delivery_type']) }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['delivery_type' => null]) }}" class="text-slate-400 hover:text-rose-600 ml-1 font-black">&times;</a>
                        </span>
                    @endif

                    @if(!empty($filters['in_stock']))
                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 border border-slate-200 shadow-2xs">
                            <span>In Stock Only</span>
                            <a href="{{ request()->fullUrlWithQuery(['in_stock' => null]) }}" class="text-slate-400 hover:text-rose-600 ml-1 font-black">&times;</a>
                        </span>
                    @endif

                    @if(!empty($filters['price_min']) || !empty($filters['price_max']))
                        <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 border border-slate-200 shadow-2xs">
                            <span>₹{{ $filters['price_min'] ?? '0' }} - ₹{{ $filters['price_max'] ?? 'Max' }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['price_min' => null, 'price_max' => null]) }}" class="text-slate-400 hover:text-rose-600 ml-1 font-black">&times;</a>
                        </span>
                    @endif

                    @if(!empty($filters['brands']))
                        @foreach((array)$filters['brands'] as $bSlug)
                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white text-slate-800 border border-slate-200 shadow-2xs">
                                <span>Brand: {{ ucfirst($bSlug) }}</span>
                                <a href="{{ request()->fullUrlWithQuery(['brands' => array_diff((array)$filters['brands'], [$bSlug])]) }}" class="text-slate-400 hover:text-rose-600 ml-1 font-black">&times;</a>
                            </span>
                        @endforeach
                    @endif

                    <a href="{{ url()->current() }}" class="text-xs font-bold text-rose-600 hover:text-rose-800 hover:underline ml-auto flex items-center space-x-1">
                        <span>Reset All Filters</span>
                        <span>↺</span>
                    </a>
                </div>
            @endif

            <!-- Desktop Header: Product Count & Sorting -->
            <div class="hidden sm:flex items-center justify-between pb-3 border-b border-slate-200/80">
                <span class="text-xs font-bold text-slate-700">{{ $products->total() }} Products Found</span>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-medium text-slate-500">Sort:</span>
                    <form method="GET" action="{{ url()->current() }}">
                        @foreach(request()->except(['sort', 'page']) as $k => $v)
                            @if(is_array($v))
                                @foreach($v as $subV)
                                    <input type="hidden" name="{{ $k }}[]" value="{{ $subV }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                        <select name="sort" onchange="this.form.submit()" class="text-xs font-bold text-slate-800 bg-white border border-slate-200 rounded-xl py-1.5 pl-3 pr-8 focus:ring-indigo-500 cursor-pointer">
                            <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>Relevance & Nearby</option>
                            <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="discount_desc" {{ $sort === 'discount_desc' ? 'selected' : '' }}>Top Discount</option>
                            <option value="rating_desc" {{ $sort === 'rating_desc' ? 'selected' : '' }}>Rating</option>
                            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
                        </select>
                    </form>
                </div>
            </div>

            @if($products->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 sm:gap-4">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="pt-6">
                    {{ $products->links() }}
                </div>
            @else
                <div class="p-12 text-center bg-white rounded-3xl border border-slate-200">
                    <p class="text-sm font-bold text-slate-800">No products found matching these filters in this category.</p>
                    <a href="{{ route('category.show', $category->slug) }}" class="inline-block mt-3 text-xs font-bold text-indigo-600 hover:underline">
                        Clear all filters
                    </a>
                </div>
            @endif
        </main>
    </div>

</div>
@endsection
