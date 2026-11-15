@props([
    'product',
    'compact' => false,
])

@php
    $variant = $product->relationLoaded('defaultVariant') && $product->defaultVariant
        ? $product->defaultVariant
        : ($product->relationLoaded('variants') ? $product->variants->first() : $product->defaultVariant);
    $variantId = $variant?->id ?? 0;
    $price = $product->effective_price;
    $mrp = $product->effective_mrp;
    $discount = $product->discount_percent;
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

<div class="group relative flex flex-col justify-between bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 hover:border-blue-500 hover:shadow-xl transition-all duration-300 overflow-hidden p-3.5 sm:p-4 shadow-xs"
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
        <!-- Top Badges Row -->
        <div class="absolute top-2.5 left-2.5 right-2.5 flex items-start justify-between z-10 pointer-events-none">
            <div class="flex flex-col gap-1 items-start">
                @if(!$inStock)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] sm:text-[10px] font-black bg-slate-900 text-white shadow-xs">
                        OUT OF STOCK
                    </span>
                @else
                    @if($discount > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] sm:text-[10px] font-black bg-blue-600 text-white shadow-xs">
                            {{ $discount }}% OFF
                        </span>
                    @endif
                    
                    @if($availableQty <= 5 && $availableQty > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                            Only {{ $availableQty }} left!
                        </span>
                    @endif
                @endif
            </div>

            <!-- Wishlist Button -->
            <button type="button"
                    @click.prevent.stop="$store.wishlist.toggle(productId)"
                    class="pointer-events-auto w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/95 backdrop-blur-md shadow-xs border border-slate-200/80 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:scale-110 active:scale-95 transition"
                    :title="$store.wishlist.has(productId) ? 'Remove from wishlist' : 'Add to wishlist'"
                    :aria-label="$store.wishlist.has(productId) ? 'Remove from wishlist' : 'Add to wishlist'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 transition-colors"
                     :class="$store.wishlist.has(productId) ? 'text-rose-500 fill-rose-500' : 'text-slate-400 fill-none'"
                     stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>

        <!-- Product Image Container -->
        <a href="{{ route('product.show', $product->slug) }}" class="block relative aspect-square rounded-xl sm:rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden mb-2.5 sm:mb-3 group-hover:scale-[1.02] transition-transform duration-300">
            <img src="{{ $imgUrl }}"
                 alt="{{ $product->title }}"
                 loading="lazy"
                 decoding="async"
                 width="280"
                 height="280"
                 onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 300 300\' width=\'300\' height=\'300\'><rect width=\'300\' height=\'300\' rx=\'24\' fill=\'%23f8fafc\'/><circle cx=\'150\' cy=\'140\' r=\'56\' fill=\'%232563eb\' fill-opacity=\'0.1\'/><rect x=\'125\' y=\'115\' width=\'50\' height=\'50\' rx=\'8\' fill=\'%230f172a\' stroke=\'%232563eb\' stroke-width=\'3\'/><circle cx=\'150\' cy=\'140\' r=\'8\' fill=\'%2306b6d4\'/><path d=\'M115 130 h10 M115 150 h10 M175 130 h10 M175 150 h10 M140 105 v10 M160 105 v10 M140 165 v10 M160 165 v10\' stroke=\'%232563eb\' stroke-width=\'2.5\' stroke-linecap=\'round\'/><text x=\'150\' y=\'232\' font-family=\'sans-serif\' font-size=\'12\' font-weight=\'900\' fill=\'%232563eb\' letter-spacing=\'1.5\' text-anchor=\'middle\'>ALLZONE ELECTRONICS</text></svg>';"
                 class="w-full h-full object-contain p-2 sm:p-3 group-hover:scale-105 transition-transform duration-300">
        </a>

        <!-- Brand Name & Rating Row -->
        <div class="flex items-center justify-between text-[11px] mb-1">
            <a href="{{ $product->brand ? route('brand.show', $product->brand->slug) : '#' }}" class="truncate font-black uppercase tracking-wider text-blue-600 hover:text-blue-800 hover:underline max-w-[120px]">
                {{ $product->brand?->name ?? 'ALLZONE' }}
            </a>

            <div class="flex items-center text-amber-500 font-bold space-x-1 flex-shrink-0">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span class="text-slate-800 text-[11px] font-extrabold">{{ number_format($rating, 1) }}</span>
                <span class="text-slate-400 text-[10px] font-normal">({{ $reviewCount }})</span>
            </div>
        </div>

        <!-- Product Title (Clamped to 2 lines) -->
        <a href="{{ route('product.show', $product->slug) }}" class="block min-w-0">
            <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-blue-600 transition line-clamp-2 leading-snug"
                title="{{ $product->title }}">
                {{ $product->title }}
            </h3>
        </a>

        <!-- Stock Status & Warranty Indicator -->
        <div class="mt-1.5 flex items-center space-x-1.5 text-[10px] text-slate-500">
            @if($inStock)
                <span class="inline-flex items-center text-emerald-600 font-semibold space-x-0.5">
                    <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span>In Stock</span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500 truncate">Brand Warranty</span>
            @else
                <span class="text-rose-600 font-semibold">Currently Unavailable</span>
            @endif
        </div>
    </div>

    <!-- Price & Add-To-Cart Action Footer -->
    <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-end justify-between gap-1.5">
        <!-- Price Display -->
        <div class="min-w-0 flex-1">
            <div class="text-sm sm:text-base font-black text-slate-900 tracking-tight leading-none">
                {{ format_currency($price) }}
            </div>
            @if($mrp > $price)
                <div class="text-[10px] sm:text-[11px] text-slate-400 line-through leading-none mt-1">
                    {{ format_currency($mrp) }}
                </div>
            @endif
        </div>

        <!-- Add to Cart / Qty Stepper (Electric Blue #2563EB) -->
        <div class="flex-shrink-0">
            @if(!$inStock)
                <span class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-slate-100 text-slate-400 font-extrabold text-[9px] sm:text-[10px] uppercase tracking-wider border border-slate-200 cursor-not-allowed select-none">
                    SOLD OUT
                </span>
            @else
                <!-- Button when 0 in cart -->
                <template x-if="inCartQty === 0">
                    <button type="button"
                            @click.prevent.stop="addToCart()"
                            class="px-3 sm:px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-[10px] sm:text-xs uppercase tracking-wider transition-all shadow-sm hover:shadow flex items-center space-x-1 active:scale-95 cursor-pointer">
                        <span>ADD</span>
                        <span class="text-xs font-black">+</span>
                    </button>
                </template>

                <!-- Quantity Stepper when >= 1 in cart -->
                <template x-if="inCartQty > 0">
                    <div class="inline-flex items-center rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md border border-blue-700 overflow-hidden">
                        <button type="button"
                                @click.prevent.stop="decrement()"
                                class="px-2.5 py-1.5 hover:bg-blue-700 active:bg-blue-800 transition flex items-center justify-center font-black text-xs cursor-pointer"
                                aria-label="Decrease quantity">
                            -
                        </button>
                        <span class="px-2 py-0.5 text-xs font-black min-w-[20px] text-center" x-text="inCartQty"></span>
                        <button type="button"
                                @click.prevent.stop="increment()"
                                class="px-2.5 py-1.5 hover:bg-blue-700 active:bg-blue-800 transition flex items-center justify-center font-black text-xs cursor-pointer"
                                aria-label="Increase quantity">
                            +
                        </button>
                    </div>
                </template>
            @endif
        </div>
    </div>
</div>
