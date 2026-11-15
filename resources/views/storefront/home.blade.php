@extends('layouts.storefront')

@section('content')
<div class="space-y-6 sm:space-y-12 pb-16">

    <!-- 1. Hero Banner Carousel (Admin Managed via banners table) -->
    <section class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 pt-2 sm:pt-5">
        <div x-data="{
                activeSlide: 0,
                slidesCount: {{ $heroBanners->count() > 0 ? $heroBanners->count() : 1 }},
                autoplay: null,
                start() {
                    this.autoplay = setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.slidesCount;
                    }, 5000);
                },
                stop() {
                    clearInterval(this.autoplay);
                }
             }"
             x-init="start()"
             @mouseenter="stop()"
             @mouseleave="start()"
             class="relative rounded-2xl sm:rounded-3xl overflow-hidden shadow-xl bg-slate-950 text-white border border-slate-800/80">

            <!-- Responsive Aspect Ratio Container (Guarantees visible height on mobile & desktop) -->
            <div class="relative w-full aspect-[21/9] sm:aspect-[24/9]" style="min-height: 175px;">
                @if($heroBanners->isNotEmpty())
                    @foreach($heroBanners as $index => $banner)
                        @php
                            $imageUrl = $banner->image_url;
                            $targetUrl = $banner->link ? (str_starts_with($banner->link, 'http') ? $banner->link : url($banner->link)) : route('home');
                        @endphp
                        <div x-show="activeSlide === {{ $index }}"
                             @if($index > 0) x-cloak @endif
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 -translate-x-4"
                             class="absolute inset-0 w-full h-full overflow-hidden bg-slate-950"
                             style="{{ $index === 0 ? 'display: block;' : '' }}">

                            @if($imageUrl)
                                <a href="{{ $targetUrl }}" class="block w-full h-full relative group" aria-label="{{ $banner->title }}">
                                    <!-- Full Hero Graphic Artwork -->
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $banner->title }}" 
                                         class="w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-[1.01]"
                                         loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                         onerror="this.onerror=null; this.src='{{ asset('storage/banners/hero-electronics-sale.jpg') }}';">
                                </a>
                            @else
                                <div class="w-full h-full flex items-center p-6 sm:p-12 bg-gradient-to-r from-slate-950 via-blue-950 to-slate-900">
                                    <div class="max-w-xl space-y-2">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-black bg-blue-600 text-white">⚡ ALLZONE SPECIAL</span>
                                        <h2 class="text-xl sm:text-3xl font-black text-white">{{ $banner->title }}</h2>
                                        <p class="text-xs sm:text-sm text-slate-300">{{ $banner->subtitle }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <!-- Fallback hero slide -->
                    <div class="w-full h-full flex items-center p-6 sm:p-12 bg-gradient-to-r from-slate-950 via-blue-950 to-slate-900">
                        <div class="max-w-xl space-y-3">
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] sm:text-xs font-black bg-blue-600 text-white">⚡ SK REFRIGERATION</span>
                            <h2 class="text-lg sm:text-3xl font-black text-white">Premium Electronics &amp; Appliances</h2>
                            <p class="text-xs sm:text-sm text-slate-300">Shop top smartphones, cooling appliances, laptops, TVs &amp; accessories with brand warranty.</p>
                            <a href="{{ route('search') }}" class="inline-block px-5 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs uppercase">Explore Catalog</a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Carousel Controls -->
            @if($heroBanners->count() > 1)
                <button type="button"
                        @click="activeSlide = (activeSlide - 1 + slidesCount) % slidesCount"
                        class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-20 w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-slate-900/80 hover:bg-blue-600 text-white flex items-center justify-center backdrop-blur-md transition shadow-lg cursor-pointer border border-white/20"
                        aria-label="Previous slide">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button"
                        @click="activeSlide = (activeSlide + 1) % slidesCount"
                        class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-20 w-7 h-7 sm:w-10 sm:h-10 rounded-full bg-slate-900/80 hover:bg-blue-600 text-white flex items-center justify-center backdrop-blur-md transition shadow-lg cursor-pointer border border-white/20"
                        aria-label="Next slide">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>

                <!-- Indicator Dots -->
                <div class="absolute bottom-2 sm:bottom-3 left-1/2 -translate-x-1/2 flex items-center space-x-1.5 z-20">
                    <template x-for="i in slidesCount" :key="i">
                        <button type="button"
                                @click="activeSlide = i - 1"
                                :class="activeSlide === (i - 1) ? 'w-5 sm:w-6 bg-blue-500' : 'w-2 bg-white/40'"
                                class="h-1.5 sm:h-2 rounded-full transition-all duration-300"
                                :aria-label="'Go to slide ' + i"></button>
                    </template>
                </div>
            @endif
        </div>
    </section>

    <!-- 2. Electronics Value Propositions Bar (Compact & Small on Mobile) -->
    <section class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <!-- Mobile View: Ultra-compact horizontal pill strip (small & non-intrusive) -->
        <div class="sm:hidden flex items-center overflow-x-auto no-scrollbar gap-2 py-1 px-0.5">
            <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-2xs flex-shrink-0 text-[11px] font-bold text-slate-800">
                <span class="text-xs">🛡️</span>
                <span>100% Genuine</span>
            </div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-2xs flex-shrink-0 text-[11px] font-bold text-slate-800">
                <span class="text-xs">🚚</span>
                <span>Secure Delivery</span>
            </div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-2xs flex-shrink-0 text-[11px] font-bold text-slate-800">
                <span class="text-xs">📜</span>
                <span>Brand Warranty</span>
            </div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-2xs flex-shrink-0 text-[11px] font-bold text-slate-800">
                <span class="text-xs">🔄</span>
                <span>7 Days Return</span>
            </div>
            <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-2xs flex-shrink-0 text-[11px] font-bold text-slate-800">
                <span class="text-xs">❄️</span>
                <span>SK Installation</span>
            </div>
        </div>

        <!-- Desktop View: Sleek 5-column grid -->
        <div class="hidden sm:grid grid-cols-5 gap-3 p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center space-x-2.5 p-1">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    🛡️
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 leading-tight">100% Genuine</h4>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">Brand sealed &amp; verified</p>
                </div>
            </div>

            <div class="flex items-center space-x-2.5 p-1">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    🚚
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 leading-tight">Secure Delivery</h4>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">Insured dispatch &amp; tracking</p>
                </div>
            </div>

            <div class="flex items-center space-x-2.5 p-1">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    📜
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 leading-tight">Brand Warranty</h4>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">Official GST invoice</p>
                </div>
            </div>

            <div class="flex items-center space-x-2.5 p-1">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    🔄
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 leading-tight">7 Days Return</h4>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">Hassle-free replacement</p>
                </div>
            </div>

            <div class="flex items-center space-x-2.5 p-1">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                    ❄️
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 leading-tight">Expert Installation</h4>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">SK Refrigeration certified</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Available Platform Coupons Showcase Bar (Modern Perforated Ticket Carousel) -->
    @if(isset($availableCoupons) && $availableCoupons->isNotEmpty())
        <section id="available-coupons" class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8" x-data="{ copiedCode: null }">
            <div class="p-3 sm:p-3.5 rounded-2xl bg-gradient-to-r from-blue-50/90 via-indigo-50/40 to-slate-50 border border-blue-100 shadow-2xs space-y-2.5">
                <!-- Header -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center space-x-2 min-w-0">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs shrink-0">
                            🎟️
                        </span>
                        <div class="flex items-center space-x-2 truncate">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 tracking-tight uppercase truncate">Storewide Vouchers</h3>
                            <span class="hidden sm:inline-block text-[11px] text-slate-500 font-medium truncate">• Use at checkout for instant savings</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1.5 shrink-0">
                        <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="whitespace-nowrap">Verified Active</span>
                        </span>
                    </div>
                </div>

                <!-- Ticket Strip -->
                <div class="flex items-center gap-2.5 overflow-x-auto w-full no-scrollbar pb-0.5">
                    @foreach($availableCoupons as $coup)
                        <div class="relative flex items-stretch bg-white rounded-xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition overflow-hidden shrink-0 w-[240px] sm:w-[260px]">
                            <!-- Left: Discount highlight badge -->
                            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white px-3 py-2 flex flex-col items-center justify-center min-w-[70px] shrink-0 text-center">
                                <span class="text-xs font-black tracking-tight leading-none">{{ $coup->formatted_discount }}</span>
                                <span class="text-[8px] uppercase tracking-wider font-extrabold text-blue-100 mt-0.5">OFF</span>
                            </div>

                            <!-- Ticket perforated divider with top/bottom semi-circle notches -->
                            <div class="relative flex flex-col justify-between py-0.5 -mx-1 z-10 shrink-0">
                                <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -mt-2 border-b border-slate-200"></div>
                                <div class="h-full border-r border-dashed border-slate-200 mx-auto my-0.5"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -mb-2 border-t border-slate-200"></div>
                            </div>

                            <!-- Right: Code, min order & Copy button -->
                            <div class="p-2 pl-2.5 flex-1 flex flex-col justify-center min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-mono font-black text-xs text-slate-900 tracking-wider bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 truncate select-all">
                                        {{ $coup->code }}
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="navigator.clipboard.writeText('{{ $coup->code }}'); copiedCode = '{{ $coup->code }}'; setTimeout(() => copiedCode = null, 2000)"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-md transition cursor-pointer shrink-0 flex items-center space-x-0.5"
                                        :class="copiedCode === '{{ $coup->code }}' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-blue-50 hover:bg-blue-100 text-blue-700'"
                                    >
                                        <span x-show="copiedCode !== '{{ $coup->code }}'" class="text-[9px]">📋</span>
                                        <span x-show="copiedCode === '{{ $coup->code }}'" class="text-[9px]">✓</span>
                                        <span x-text="copiedCode === '{{ $coup->code }}' ? 'Copied' : 'Copy'"></span>
                                    </button>
                                </div>
                                <p class="text-[9px] text-slate-500 font-medium mt-1 truncate">
                                    @if($coup->min_order > 0)
                                        Min. order ₹{{ number_format((float) $coup->min_order) }}
                                    @else
                                        No min. order required
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- 4. Categories Showcase (The 8 Primary Electronics Categories) -->
    <section id="categories" class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600">Curated Electronics Catalog</span>
                <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight">Explore Categories</h2>
            </div>
            <a href="{{ route('search') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                <span>View Full Catalog</span>
                <span class="ml-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 sm:gap-4">
            @foreach($categories->take(8) as $category)
                <a href="{{ route('category.show', $category->slug) }}"
                   class="group flex flex-col items-center p-3 rounded-2xl bg-white border border-slate-200/90 hover:border-blue-500 hover:shadow-lg transition-all duration-300 text-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-50 flex items-center justify-center p-1.5 mb-2 group-hover:scale-105 transition-transform overflow-hidden flex-shrink-0 border border-slate-100">
                        <img src="{{ $category->image_url }}"
                             alt="{{ $category->name }}"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='{{ asset('storage/categories/refrigerators-freezers.svg') }}';"
                             class="w-full h-full object-contain rounded-xl">
                    </div>
                    <span class="text-xs font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 leading-tight h-8 flex items-center justify-center transition">
                        {{ $category->name }}
                    </span>
                    <span class="text-[10px] font-medium text-slate-400 mt-1">
                        {{ $category->products_count ?: $category->products()->count() }} Products
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- 5. Flash Deals & High-Discount Electronics -->
    <section id="deals" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-6 rounded-3xl bg-gradient-to-r from-slate-950 via-blue-900 to-slate-900 text-white shadow-xl mb-6 border border-blue-950">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-blue-600/30 border border-blue-400/30 text-blue-300 text-[10px] font-black tracking-wider uppercase mb-1.5">
                        <span>🔥 MEGA ELECTRONICS SALE</span>
                    </div>
                    <h2 class="text-xl sm:text-3xl font-black tracking-tight text-white">Up to 40% OFF Top Electronics</h2>
                    <p class="text-xs text-blue-100 mt-0.5">Handpicked premium devices with brand warranty and fast doorstep dispatch</p>
                </div>
                <div x-data="{
                    timeLeft: '',
                    initTimer() {
                        const update = () => {
                            const now = new Date();
                            const end = new Date();
                            end.setHours(23, 59, 59, 999);
                            const diff = Math.max(0, end - now);
                            const h = String(Math.floor(diff / 3600000)).padStart(2, '0');
                            const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                            const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                            this.timeLeft = `${h}h : ${m}m : ${s}s`;
                        };
                        update();
                        setInterval(update, 1000);
                    }
                }" x-init="initTimer()" class="flex items-center space-x-2 text-xs font-black bg-blue-950/60 backdrop-blur-sm px-4 py-2 rounded-2xl border border-blue-500/30 text-white">
                    <span>⚡ Offer Ends In:</span>
                    <span class="font-mono text-cyan-400" x-text="timeLeft">06h : 30m : 00s</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3 sm:gap-5">
            @foreach($dealsProducts as $prod)
                <x-product-card :product="$prod" />
            @endforeach
        </div>
    </section>

    <!-- 6. Promotional Split Banners (Admin Managed) -->
    @if($promoBanners->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 {{ $promoBanners->count() > 1 ? 'md:grid-cols-2' : '' }} gap-4">
                @foreach($promoBanners->take(2) as $promo)
                    <div class="rounded-3xl overflow-hidden shadow-md border border-slate-200 bg-slate-900 group">
                        <a href="{{ $promo->link ? (str_starts_with($promo->link, 'http') ? $promo->link : url($promo->link)) : route('home') }}" class="block">
                            <img src="{{ $promo->image_url }}"
                                 alt="{{ $promo->title }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('storage/banners/promo-cooling-deals.svg') }}';"
                                 class="w-full h-auto max-h-52 object-cover group-hover:scale-102 transition duration-300">
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- 7. Best Sellers in Electronics -->
    <section id="bestsellers" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600">Top Rated &amp; Trending</span>
                <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight">Best Sellers on {{ setting('name', 'ALLZONE') }}</h2>
            </div>
            <a href="{{ route('search', ['sort' => 'rating_desc']) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                <span>Explore All</span>
                <span class="ml-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3 sm:gap-5">
            @foreach($bestSellers as $prod)
                <x-product-card :product="$prod" />
            @endforeach
        </div>
    </section>

    <!-- 8. Official Brand Stores -->
    <section id="brands" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600">Direct Brand Partnerships</span>
                <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight">Official Brand Stores</h2>
            </div>
            <a href="{{ route('search', ['tab' => 'brands']) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                <span>View All Brands</span>
                <span class="ml-1">&rarr;</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
            @foreach($brands as $brand)
                <a href="{{ route('brand.show', $brand->slug) }}"
                   class="group p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-blue-500 hover:shadow-md transition text-center flex flex-col items-center justify-between">
                    <div class="w-20 h-14 rounded-xl bg-slate-50 flex items-center justify-center p-2 mb-2 group-hover:scale-105 transition-transform overflow-hidden">
                        @if($brand->logo)
                            <img src="{{ asset('storage/' . $brand->logo) }}" 
                                 alt="{{ $brand->name }}" 
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.src='{{ asset('storage/brands/samsung.png') }}';"
                                 class="w-full h-full object-contain">
                        @else
                            <span class="text-base font-black text-blue-600">{{ substr($brand->name, 0, 3) }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center justify-center space-x-1">
                            <span class="text-xs font-bold text-slate-900 group-hover:text-blue-600 transition truncate max-w-[110px]">
                                {{ $brand->name }}
                            </span>
                            @if($brand->is_verified)
                                <svg class="w-3.5 h-3.5 text-blue-500 fill-current flex-shrink-0" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400">{{ $brand->products_count ?? $brand->products()->count() }} products</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- 9. ALLZONE Flagship Experience Hubs (Powered by SK Refrigeration) -->
    <section id="allzone-stores" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="p-6 rounded-3xl bg-slate-900 text-white shadow-xl mb-6 flex flex-col md:flex-row items-center justify-between gap-4 border border-slate-800">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-extrabold text-base shadow-md tracking-tighter">
                    AZ
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-400">Powered by SK Refrigeration</span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">{{ setting('name', 'ALLZONE') }} Flagship Centers</h2>
                </div>
            </div>
            <p class="text-xs text-slate-300 max-w-md">
                Verified inventory, direct factory pricing, expedited delivery, and certified technician installation.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($onestallStores as $osStore)
                <div class="p-5 rounded-3xl bg-white border border-slate-200/90 shadow-sm hover:shadow-lg transition">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm">
                            🏪
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">{{ $osStore->name }}</h3>
                            <span class="text-[11px] text-slate-500">{{ $osStore->location?->city ?? setting('default_city', 'Bengaluru') }} Hub</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 mb-4 line-clamp-2">
                        {{ $osStore->description ?: 'Authorized electronics fulfillment hub with genuine brand warranty and same-day dispatch.' }}
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                            ✓ Direct Quality Guarantee
                        </span>
                        <a href="{{ route('store.show', $osStore->slug) }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                            Visit Hub &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 10. Recommended Electronics Products Grid -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Personalized Selection</span>
                <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight">Recommended For You</h2>
            </div>
            <a href="{{ route('search') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                View More &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3 sm:gap-5">
            @foreach($recommendedProducts as $prod)
                <x-product-card :product="$prod" />
            @endforeach
        </div>
    </section>

</div>
@endsection
