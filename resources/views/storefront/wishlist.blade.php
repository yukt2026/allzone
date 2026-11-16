@extends('layouts.storefront')

@section('title', $isSharedView ? ($sharedOwner . '\'s Wishlist - ' . setting('name', 'ALLZONE')) : ('My Wishlist - ' . setting('name', 'ALLZONE')))

@section('content')
<div class="min-h-screen bg-slate-50/60 py-6 sm:py-10"
     x-data="{
        copied: false,
        shareUrl: '{{ $shareUrl ?? '' }}',
        copyShareUrl() {
            if (!this.shareUrl) return;
            navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3000);
        }
     }">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Home</a>
            <span>&rsaquo;</span>
            <a href="{{ route('customer.profile') }}" class="hover:text-blue-600 transition">My Account</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-bold">{{ $isSharedView ? 'Shared Wishlist' : 'My Wishlist' }}</span>
        </nav>

        <!-- Flash messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-semibold flex items-center space-x-2 shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-semibold flex items-center space-x-2 shadow-xs">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Shared View Banner -->
        @if ($isSharedView)
            <div class="p-5 sm:p-6 rounded-3xl bg-blue-50/70 border border-blue-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold shrink-0">
                        🎁
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-black text-slate-900">Wishlist shared by {{ $sharedOwner }}</h2>
                        <p class="text-xs text-slate-600 mt-0.5">Explore their curated list of favorite products and move items directly to your cart.</p>
                    </div>
                </div>

                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 font-bold text-xs shadow-xs transition">
                    Explore {{ setting('name', 'ALLZONE') }}
                </a>
            </div>
        @endif

        <!-- Header -->
        <div class="bg-white rounded-3xl p-5 sm:p-7 border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                        {{ $isSharedView ? ($sharedOwner . '\'s Wishlist') : 'My Wishlist' }}
                    </h1>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                        {{ $products->total() }} items
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    {{ $isSharedView ? 'Curated electronics and gadgets chosen by this shopper' : 'Keep track of items you love and move them directly into your shopping cart' }}
                </p>
            </div>

            <!-- Share Wishlist Button -->
            @if (!$isSharedView && $shareUrl)
                <div class="flex items-center space-x-2">
                    <button type="button" @click="copyShareUrl()"
                            class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-300 text-slate-800 font-extrabold text-xs flex items-center space-x-2 shadow-2xs active:scale-95 transition cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        <span x-text="copied ? 'Link Copied! ✓' : 'Share Wishlist'">Share Wishlist</span>
                    </button>
                </div>
            @endif
        </div>

        @if ($products->isEmpty())
            <!-- Empty State -->
            <div class="bg-white rounded-3xl p-10 sm:p-14 text-center border border-slate-200 shadow-xs max-w-lg mx-auto my-6 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Your Wishlist is Empty</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    Explore products across electronics, appliances, and gadgets and click the heart icon on any product card to save it for later.
                </p>
                <div class="pt-2">
                    <a href="{{ route('home') }}"
                       class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-md transition inline-block">
                        Explore Trending Electronics &rarr;
                    </a>
                </div>
            </div>
        @else
            <!-- Product Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-5">
                @foreach ($products as $product)
                    @php
                        $variant = ($product->relationLoaded('variants') ? $product->variants->firstWhere('is_default', true) : null)
                            ?: $product->defaultVariant
                            ?: ($product->relationLoaded('variants') ? $product->variants->first() : null);
                        $price = (float) ($variant?->selling_price ?? $product->effective_price);
                        $mrp = (float) ($variant?->mrp ?? $product->effective_mrp);
                        $discountPct = ($mrp > $price && $mrp > 0) ? (int) round((($mrp - $price) / $mrp) * 100) : (int) $product->discount_percent;
                        
                        $hasInv = $variant && $variant->relationLoaded('inventories') && $variant->inventories->isNotEmpty();
                        $available = $hasInv ? (int) $variant->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved)) : 100;
                        $isOut = $hasInv && $available <= 0;

                        $imageUrl = $product->primaryImageUrl();
                    @endphp

                    <div class="group bg-white rounded-2xl sm:rounded-3xl border border-slate-200 overflow-hidden shadow-2xs hover:shadow-lg hover:border-blue-400 transition-all duration-300 flex flex-col justify-between p-3 sm:p-4">
                        <div>
                            <!-- Thumbnail Container -->
                            <div class="relative aspect-square rounded-xl sm:rounded-2xl bg-gradient-to-b from-slate-50 to-slate-100/60 border border-slate-100 overflow-hidden mb-3">
                                <a href="{{ route('product.show', $product->slug) }}" class="block w-full h-full p-2">
                                    <img 
                                        src="{{ $imageUrl }}" 
                                        alt="{{ $product->title }}" 
                                        class="w-full h-full object-contain group-hover:scale-105 transition duration-300"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><rect width=\'100\' height=\'100\' fill=\'%23f1f5f9\'/><text x=\'50\' y=\'55\' font-family=\'sans-serif\' font-size=\'10\' fill=\'%2394a3b8\' text-anchor=\'middle\'>ALLZONE</text></svg>';"
                                    >
                                </a>

                                @if ($discountPct > 0)
                                    <span class="absolute top-2 left-2 px-1.5 sm:px-2 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-black bg-rose-600 text-white shadow-xs">
                                        {{ $discountPct }}% OFF
                                    </span>
                                @endif

                                @if (!$isSharedView)
                                    <!-- Remove button -->
                                    <form action="{{ route('wishlist.remove', $product->id) }}" method="POST" class="absolute top-2 right-2">
                                        @csrf
                                        <button type="submit" @click.prevent="$store.wishlist.remove({{ $product->id }}); $el.closest('.group').remove()"
                                                class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 backdrop-blur-md hover:bg-white text-rose-500 hover:scale-110 flex items-center justify-center shadow-xs border border-slate-200/80 transition cursor-pointer"
                                                title="Remove from Wishlist">
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <!-- Product Info -->
                            <div class="space-y-1">
                                <div class="text-[10px] sm:text-[11px] text-blue-600 font-black uppercase tracking-wider truncate">
                                    {{ $product->brand?->name ?? 'ALLZONE' }}
                                </div>
                                <a href="{{ route('product.show', $product->slug) }}" class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-blue-600 line-clamp-2 transition leading-snug" title="{{ $product->title }}">
                                    {{ $product->title }}
                                </a>

                                <!-- Price -->
                                <div class="flex items-baseline gap-1.5 pt-1 flex-wrap">
                                    <span class="text-xs sm:text-base font-black text-slate-900">₹{{ number_format($price) }}</span>
                                    @if ($mrp > $price)
                                        <span class="text-[10px] sm:text-xs text-slate-400 line-through">₹{{ number_format($mrp) }}</span>
                                    @endif
                                </div>

                                <!-- Stock Status -->
                                <div class="pt-0.5">
                                    @if ($isOut)
                                        <span class="text-[9px] sm:text-[10px] font-black text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded">Out of Stock</span>
                                    @else
                                        <span class="text-[9px] sm:text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">✓ In Stock</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Card Action -->
                        <div class="mt-3 pt-2 border-t border-slate-100">
                            @if (!$isOut && $variant)
                                <form action="{{ route('wishlist.move-to-cart', $product->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" @click.prevent="$store.wishlist.moveToCart({{ $product->id }}); $el.closest('.group').remove()"
                                            class="w-full py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-xs active:scale-95 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        <span>Move to Cart</span>
                                    </button>
                                </form>
                            @else
                                <button disabled class="w-full py-2 px-3 rounded-xl bg-slate-100 text-slate-400 font-bold text-xs cursor-not-allowed">
                                    Unavailable
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
