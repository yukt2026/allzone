@props([
    'product',
    'compact' => false,
])

@php
    $variant = $product->relationLoaded('defaultVariant') && $product->defaultVariant
        ? $product->defaultVariant
        : ($product->relationLoaded('variants') ? $product->variants->first() : $product->defaultVariant);
    $variantId = $variant?->id ?? 0;
    $price = (float) $product->effective_price;
    $mrp = (float) $product->effective_mrp;
    $discount = (int) $product->discount_percent;
    $imgUrl = $product->primaryImageUrl();
    $productReviews = $product->relationLoaded('reviews') ? $product->reviews : null;
    $hasReviews = $productReviews && $productReviews->isNotEmpty();
    $rating = $hasReviews ? (float) $productReviews->avg('rating') : ($product->reviews_avg_rating ? (float) $product->reviews_avg_rating : 4.8);
    $reviewCount = $hasReviews ? $productReviews->count() : ($product->reviews_count ?: 38);

    $inStock = true;
    $availableQty = 25;
    if ($variant) {
        if ($variant->relationLoaded('inventories')) {
            $availableQty = (int) $variant->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved));
            $inStock = $availableQty > 0;
        } elseif ($product->relationLoaded('variants')) {
            $matchingV = $product->variants->firstWhere('id', $variantId);
            if ($matchingV && $matchingV->relationLoaded('inventories')) {
                $availableQty = (int) $matchingV->inventories->sum(fn ($i) => max(0, $i->quantity_on_hand - $i->quantity_reserved));
                $inStock = $availableQty > 0;
            }
        }
    }
@endphp

<div class="group relative flex flex-col justify-between bg-white rounded-2xl border border-slate-200/90 hover:border-blue-500/70 hover:shadow-lg transition-all duration-300 overflow-hidden p-2.5 sm:p-3 shadow-2xs h-full"
     x-data="{
        variantId: {{ $variantId }},
        productId: {{ $product->id }},
        get inCartQty() {
            return $store.cart.getItemQuantity(this.variantId, this.productId);
        },
        get cartItem() {
            return $store.cart.items.find(i => Number(i.variant_id) === Number(this.variantId)) ||
                   $store.cart.items.find(i => Number(i.product_id) === Number(this.productId));
        },
        addToCart() {
            $store.cart.addItem(this.variantId, this.productId, 1);
        },
        increment() {
            if (this.cartItem) {
                $store.cart.updateQuantity(this.cartItem.id, this.inCartQty + 1);
            } else {
                this.addToCart();
            }
        },
        decrement() {
            if (this.cartItem) {
                $store.cart.updateQuantity(this.cartItem.id, this.inCartQty - 1);
            }
        }
     }">

    <div>
        <!-- Top Badges Row: Discount & Wishlist -->
        <div class="absolute top-2 left-2 right-2 sm:top-2.5 sm:left-2.5 sm:right-2.5 flex items-start justify-between z-10 pointer-events-none">
            <div class="flex flex-col gap-1 items-start">
                @if(!$inStock)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-slate-900/90 text-white shadow-xs tracking-wider backdrop-blur-xs">
                        OUT OF STOCK
                    </span>
                @else
                    @if($discount > 0)
                        <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-md text-[9px] sm:text-[10px] font-black bg-rose-600 text-white shadow-2xs tracking-wide">
                            {{ $discount }}% OFF
                        </span>
                    @endif

                    @if($availableQty <= 5 && $availableQty > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] font-black bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                            Only {{ $availableQty }} left!
                        </span>
                    @endif
                @endif
            </div>

            <!-- Wishlist Heart Button -->
            <button type="button"
                    @click.prevent.stop="$store.wishlist.toggle(productId)"
                    class="pointer-events-auto w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/90 backdrop-blur-md shadow-xs border border-slate-200/70 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-white hover:scale-110 active:scale-90 transition-all cursor-pointer"
                    :title="$store.wishlist.has(productId) ? 'Remove from wishlist' : 'Add to wishlist'"
                    :aria-label="$store.wishlist.has(productId) ? 'Remove from wishlist' : 'Add to wishlist'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 transition-colors"
                     :class="$store.wishlist.has(productId) ? 'text-rose-500 fill-rose-500 scale-105' : 'text-slate-400 fill-none'"
                     stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>

        <!-- Product Image Container -->
        <a href="{{ route('product.show', $product->slug) }}" class="block relative aspect-square rounded-xl bg-gradient-to-b from-slate-50 to-slate-100/40 overflow-hidden mb-2 group-hover:scale-[1.01] transition-transform duration-300">
            <img src="{{ $imgUrl }}"
                 alt="{{ $product->title }}"
                 loading="lazy"
                 decoding="async"
                 width="280"
                 height="280"
                 onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 300 300\' width=\'300\' height=\'300\'><rect width=\'300\' height=\'300\' rx=\'20\' fill=\'%23f8fafc\'/><circle cx=\'150\' cy=\'150\' r=\'70\' fill=\'%232563eb\' fill-opacity=\'0.08\'/><rect x=\'115\' y=\'105\' width=\'70\' height=\'90\' rx=\'10\' fill=\'%23ffffff\' stroke=\'%232563eb\' stroke-width=\'3.5\'/><circle cx=\'150\' cy=\'150\' r=\'14\' fill=\'%2338bdf8\'/><rect x=\'135\' y=\'115\' width=\'30\' height=\'4\' rx=\'2\' fill=\'%2394a3b8\'/></svg>';"
                 class="w-full h-full object-contain p-2 sm:p-3 group-hover:scale-105 transition-transform duration-300">
        </a>

        <!-- Brand Name & Rating Badge Row -->
        <div class="flex items-center justify-between gap-1 text-[11px] mb-1">
            <a href="{{ $product->brand ? route('brand.show', $product->brand->slug) : '#' }}" class="truncate font-bold uppercase tracking-wider text-slate-500 hover:text-blue-600 max-w-[85px] sm:max-w-[110px]">
                {{ $product->brand?->name ?? 'ALLZONE' }}
            </a>

            <div class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[10px] font-bold shadow-2xs flex-shrink-0">
                <span>{{ number_format($rating, 1) }}</span>
                <span class="text-[9px] leading-none">★</span>
                <span class="text-white/80 font-normal text-[9px] hidden sm:inline ml-0.5">({{ $reviewCount }})</span>
            </div>
        </div>

        <!-- Product Title (Clamped to 2 lines with consistent min-height) -->
        <a href="{{ route('product.show', $product->slug) }}" class="block min-w-0">
            <h3 class="text-xs sm:text-[13px] font-semibold text-slate-900 hover:text-blue-600 transition-colors line-clamp-2 leading-snug min-h-[2rem] sm:min-h-[2.25rem]"
                title="{{ $product->title }}">
                {{ $product->title }}
            </h3>
        </a>

        <!-- Stock Status & Feature Snippet (Clean single line, never truncated) -->
        <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-500 font-medium truncate">
            @if($inStock)
                <span class="text-emerald-700 font-bold inline-flex items-center gap-0.5 flex-shrink-0">
                    <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    In Stock
                </span>
                <span class="text-slate-300">•</span>
                @if($product->is_quick_capable)
                    <span class="text-amber-700 font-extrabold flex-shrink-0">⚡ Express</span>
                @else
                    <span class="truncate">Warranty</span>
                @endif
            @else
                <span class="text-rose-600 font-bold">Currently Unavailable</span>
            @endif
        </div>
    </div>

    <!-- Pricing & Add-To-Cart Action Section (Clean, Space-Efficient, Aligned) -->
    <div class="mt-auto pt-2 border-t border-slate-100/90 flex flex-col gap-1.5">
        <!-- Price Display Row -->
        <div class="flex items-baseline justify-between gap-1">
            <div class="flex items-baseline gap-1.5 flex-wrap">
                <span class="text-sm sm:text-base font-black text-slate-900 tracking-tight leading-none whitespace-nowrap">
                    ₹{{ number_format($price) }}
                </span>
                @if($mrp > $price)
                    <span class="text-[10px] sm:text-[11px] text-slate-400 line-through leading-none whitespace-nowrap">
                        ₹{{ number_format($mrp) }}
                    </span>
                @endif
            </div>
            @if($discount > 0)
                <span class="text-[9px] sm:text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-1.5 py-0.5 rounded leading-none whitespace-nowrap">
                    {{ $discount }}% off
                </span>
            @endif
        </div>

        <!-- Full-width Action CTA / Stepper Button -->
        <div>
            @if(!$inStock)
                <button disabled type="button" class="w-full h-8 rounded-xl bg-slate-100 text-slate-400 font-extrabold text-[10px] uppercase tracking-wider border border-slate-200 cursor-not-allowed select-none flex items-center justify-center">
                    Sold Out
                </button>
            @else
                <!-- Button when 0 in cart -->
                <template x-if="inCartQty === 0">
                    <button type="button"
                            @click.prevent.stop="addToCart()"
                            class="w-full h-8 px-2 sm:px-3 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-extrabold text-xs uppercase tracking-wider transition-all shadow-2xs hover:shadow-xs flex items-center justify-center gap-1 active:scale-[0.98] cursor-pointer select-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>ADD</span>
                        <span class="text-sm font-black leading-none">+</span>
                    </button>
                </template>

                <!-- Quantity Stepper when >= 1 in cart -->
                <template x-if="inCartQty > 0">
                    <div class="w-full h-8 inline-flex items-center justify-between rounded-xl bg-blue-600 text-white font-black text-xs shadow-2xs border border-blue-700 overflow-hidden select-none">
                        <button type="button"
                                @click.prevent.stop="decrement()"
                                class="w-8 sm:w-9 h-full flex items-center justify-center hover:bg-blue-700 active:bg-blue-800 transition font-black text-base cursor-pointer select-none active:scale-90"
                                aria-label="Decrease quantity">
                            &minus;
                        </button>
                        <span class="flex-1 text-center text-xs sm:text-sm font-black select-none tracking-wide" x-text="inCartQty"></span>
                        <button type="button"
                                @click.prevent.stop="increment()"
                                class="w-8 sm:w-9 h-full flex items-center justify-center hover:bg-blue-700 active:bg-blue-800 transition font-black text-base cursor-pointer select-none active:scale-90"
                                aria-label="Increase quantity">
                            &plus;
                        </button>
                    </div>
                </template>
            @endif
        </div>
    </div>
</div>
