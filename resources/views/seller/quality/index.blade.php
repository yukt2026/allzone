@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Product Quality & Recommendations - ALLZONE'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    activeTab: '{{ $activeTab ?? 'action_required' }}',
    reviewModalOpen: false,
    selectedProductTitle: '',
    selectedProductId: null,
    reviewsList: [],
    loadingReviews: false,
    replyText: '',
    replyingReviewId: null,

    openReviewsModal(productId) {
        this.selectedProductId = productId;
        this.reviewModalOpen = true;
        this.loadingReviews = true;
        this.reviewsList = [];

        fetch('{{ url('/seller/quality/products') }}/' + productId + '/reviews')
            .then(res => res.json())
            .then(data => {
                this.loadingReviews = false;
                this.selectedProductTitle = data.product_title;
                this.reviewsList = data.reviews;
            })
            .catch(() => {
                this.loadingReviews = false;
            });
    },

    submitReply(reviewId) {
        if (!this.replyText || this.replyText.trim().length < 3) return;

        fetch('{{ url('/seller/quality/reviews') }}/' + reviewId + '/reply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ response: this.replyText })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const target = this.reviewsList.find(r => r.id === reviewId);
                if (target) {
                    target.seller_response = data.seller_response;
                    target.seller_responded_at = data.seller_responded_at;
                }
                this.replyingReviewId = null;
                this.replyText = '';
            } else {
                alert(data.message || 'Failed to post reply.');
            }
        })
        .catch(err => alert('Network error occurred while saving reply.'));
    }
}">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-emerald-700 mb-1">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Supplier Studio &bull; Catalog Health &amp; Ratings</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Product Quality &amp; Recommendations
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Analyze verified customer feedback, monitor 1-2★ review rates, resolve listing defects, and prevent catalog deactivations.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a 
                href="{{ route('seller.products.index') }}" 
                class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50 transition shadow-2xs"
            >
                View Full Catalog
            </a>
            <a 
                href="{{ route('seller.support.index') }}" 
                class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs"
            >
                Quality Help Center
            </a>
        </div>
    </div>

    <!-- Alert / Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- Verified Ratings Banner -->
    <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-center justify-between text-xs text-emerald-950 font-medium shadow-2xs">
        <div class="flex items-center space-x-2.5">
            <span class="text-lg">🛡️</span>
            <span>
                <strong>Quality score updated:</strong> Calculated from actual ratings by verified marketplace buyers. High scores increase search discovery and prevent listing deactivations.
            </span>
        </div>
        <span class="px-2.5 py-1 rounded-full bg-emerald-600 text-white font-bold text-[10px] shrink-0 ml-2">
            Verified Data
        </span>
    </div>

    <!-- Quality Overview 3-Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Card 1: Quality Score & Tier Meter -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Quality Score Tier</h3>
                    @if($totalRatings > 0)
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-{{ $qualityTier['color'] }}-50 text-{{ $qualityTier['color'] }}-700 border border-{{ $qualityTier['color'] }}-200">
                            {{ $qualityTier['tier'] }}
                        </span>
                    @endif
                </div>

                <div class="mt-4 text-center py-4">
                    <div class="text-3xl font-black text-slate-900">
                        {{ $qualityTier['score'] }}
                    </div>
                    <div class="text-xs font-bold text-slate-800 mt-1">
                        {{ $qualityTier['label'] }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">
                        {{ $qualityTier['subtext'] }}
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 border-t border-slate-100 pt-3 flex items-center justify-between">
                    <span>1 &amp; 2 Star Ratings: <strong class="text-rose-600">{{ $lowRatingCount }}</strong></span>
                    <span>Total Ratings: <strong class="text-slate-900">{{ $totalRatings }}</strong></span>
                </div>
            </div>

            <!-- 4-Level Quality Category Scales (Meesho Reference Matching Cleanly) -->
            <div class="grid grid-cols-4 gap-1 text-center text-[10px] font-bold pt-3 border-t border-slate-100">
                <div class="p-1.5 rounded transition {{ $qualityTier['tier'] === 'green' ? 'ring-2 ring-emerald-500 bg-emerald-100 text-emerald-900' : 'bg-emerald-50/70 text-emerald-800' }}">
                    <div>1.6X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Green (0 - 15%)</div>
                </div>
                <div class="p-1.5 rounded transition {{ $qualityTier['tier'] === 'yellow' ? 'ring-2 ring-amber-500 bg-amber-100 text-amber-900' : 'bg-amber-50/70 text-amber-800' }}">
                    <div>1.0X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Yellow (15 - 22%)</div>
                </div>
                <div class="p-1.5 rounded transition {{ $qualityTier['tier'] === 'red' ? 'ring-2 ring-rose-500 bg-rose-100 text-rose-900' : 'bg-rose-50/70 text-rose-800' }}">
                    <div>0.6X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Red (22 - 25%)</div>
                </div>
                <div class="p-1.5 rounded transition {{ $qualityTier['tier'] === 'blocked' ? 'ring-2 ring-red-600 bg-red-200 text-red-950' : 'bg-red-50 text-red-800' }}">
                    <div>BLOCKED</div>
                    <div class="text-[9px] text-slate-500 font-normal">Block (&gt; 25%)</div>
                </div>
            </div>
        </div>

        <!-- Card 2: Rating Trends & Distribution -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 flex flex-col justify-between space-y-3">
            <div>
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider mb-2">Review Trends</h3>
                <div class="space-y-2 pt-1 text-xs">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        <div class="flex items-center space-x-2 text-[11px]">
                            <span class="w-12 font-bold text-slate-700 flex items-center space-x-1 shrink-0">
                                <span>{{ $star }}</span>
                                <span class="text-amber-400">★</span>
                            </span>
                            <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div 
                                    class="h-full rounded-full {{ $star >= 4 ? 'bg-emerald-500' : ($star === 3 ? 'bg-amber-500' : 'bg-rose-500') }}" 
                                    style="width: {{ $ratingTrends[$star]['percent'] }}%"
                                ></div>
                            </div>
                            <span class="w-16 text-right text-slate-500 font-mono text-[10px]">
                                {{ $ratingTrends[$star]['count'] }} ({{ $ratingTrends[$star]['percent'] }}%)
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                <span>Average Score:</span>
                <span class="font-black text-slate-900 text-sm">
                    {{ $totalRatings > 0 ? number_format($averageRating, 1) : '0.0' }} / 5.0
                </span>
            </div>
        </div>

        <!-- Card 3: Top Customer Feedback -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 flex flex-col justify-between space-y-3">
            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Top Customer Feedback</h3>

            @if(count($recentFeedback) > 0)
                <div class="space-y-2.5 overflow-y-auto max-h-48 pr-1">
                    @foreach($recentFeedback as $fb)
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $fb->rating ? 'text-amber-400' : 'text-slate-300' }} text-xs">★</span>
                                    @endfor
                                    @if($fb->is_verified_purchase)
                                        <span class="px-1 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[9px] font-bold ml-1">Verified</span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $fb->created_at->format('d M') }}</span>
                            </div>
                            @if($fb->title)
                                <div class="font-bold text-slate-900 text-xs truncate">{{ $fb->title }}</div>
                            @endif
                            <p class="text-slate-800 text-[11px] line-clamp-2">"{{ $fb->body }}"</p>
                            <div class="text-[10px] text-slate-500 font-medium truncate">
                                On: {{ $fb->product?->title }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center flex flex-col items-center justify-center">
                    <span class="text-3xl text-slate-300 mb-2">📋</span>
                    <div class="font-bold text-slate-700 text-xs">No customer feedback yet</div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Keep listing high-quality items to receive customer reviews.</div>
                </div>
            @endif

            <div class="pt-2 text-[10px] text-slate-400 text-right">
                Reviews updated in real time
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- QUALITY RECOMMENDATIONS & ACTION CONSOLE                                  -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        
        <!-- Tab Navigation & Search Bar -->
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <!-- 4 Quality Tabs -->
            <div class="flex flex-wrap items-center gap-2 font-bold">
                <a 
                    href="{{ route('seller.quality.index', ['tab' => 'action_required', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $activeTab === 'action_required' ? 'bg-slate-900 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 bg-slate-50 border border-slate-200' }}"
                >
                    Action Required ({{ $actionRequiredCount }})
                </a>
                <a 
                    href="{{ route('seller.quality.index', ['tab' => 'blocking_soon', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $activeTab === 'blocking_soon' ? 'bg-amber-600 text-white shadow-xs font-black' : 'text-amber-800 hover:text-amber-950 bg-amber-50 border border-amber-200' }}"
                >
                    Blocking Soon ({{ $blockingSoonCount }})
                </a>
                <a 
                    href="{{ route('seller.quality.index', ['tab' => 'fixed', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $activeTab === 'fixed' ? 'bg-emerald-600 text-white shadow-xs font-black' : 'text-emerald-800 hover:text-emerald-950 bg-emerald-50 border border-emerald-200' }}"
                >
                    Fixed / Resubmitted ({{ $fixedCount }})
                </a>
                <a 
                    href="{{ route('seller.quality.index', ['tab' => 'blocked', 'search' => $search]) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $activeTab === 'blocked' ? 'bg-rose-700 text-white shadow-xs font-black' : 'text-rose-800 hover:text-rose-950 bg-rose-50 border border-rose-200' }}"
                >
                    Blocked Products ({{ $blockedCount }})
                </a>
            </div>

            <!-- Search Field -->
            <form method="GET" action="{{ route('seller.quality.index') }}" class="flex items-center space-x-2">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search SKU / Product / Category..." 
                        class="py-1.5 pl-3 pr-8 border border-slate-300 rounded-lg text-xs w-64 focus:ring-2 focus:ring-emerald-500 font-medium"
                    >
                    @if($search)
                        <a href="{{ route('seller.quality.index', ['tab' => $activeTab]) }}" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 font-bold text-xs">&times;</a>
                    @endif
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold text-xs hover:bg-slate-900 transition">
                    Search
                </button>
            </form>
        </div>

        <!-- Product Quality Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Product &amp; SKU</th>
                        <th class="p-3">Customer Rating</th>
                        <th class="p-3">Customer Feedback</th>
                        <th class="p-3">Diagnosed Issues</th>
                        <th class="p-3">Recommended Actions</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($paginatedItems as $item)
                        @php
                            $prod = $item['product'];
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <!-- Product Details -->
                            <td class="p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                        @if($prod->media->isNotEmpty())
                                            <img src="{{ $prod->primary_image_url }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xs text-slate-400">No Img</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 max-w-xs truncate" title="{{ $prod->title }}">
                                            {{ $prod->title }}
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400">
                                            SKU: {{ $prod->defaultVariant?->sku ?? 'N/A' }} &bull; {{ $prod->category?->name ?? 'Electronics' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Rating -->
                            <td class="p-3 whitespace-nowrap">
                                @if($item['review_count'] > 0)
                                    <div class="flex items-center space-x-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black {{ ($item['avg_rating'] ?? 0) >= 3.5 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            ★ {{ number_format($item['avg_rating'] ?? 0, 1) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">({{ $item['review_count'] }})</span>
                                    </div>
                                    @if($item['low_rating_count'] > 0)
                                        <span class="text-[10px] text-rose-600 block mt-0.5">
                                            {{ $item['low_rating_count'] }} low rating(s)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[11px] text-slate-400 italic">No ratings yet</span>
                                @endif
                            </td>

                            <!-- Feedback excerpt -->
                            <td class="p-3 max-w-xs">
                                @if(!empty($item['latest_feedback']))
                                    <div class="text-[11px] text-slate-700 italic line-clamp-2">
                                        "{{ $item['latest_feedback'] }}"
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400">No customer remarks recorded</span>
                                @endif
                            </td>

                            <!-- Diagnosed Issues -->
                            <td class="p-3 max-w-xs space-y-1">
                                @forelse($item['issues'] as $issue)
                                    <div class="text-[10px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                        ▲ {{ $issue }}
                                    </div>
                                @empty
                                    <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        ✔ No quality defects detected
                                    </span>
                                @endforelse
                            </td>

                            <!-- Recommendations -->
                            <td class="p-3 max-w-xs space-y-1">
                                @forelse($item['recommendations'] as $rec)
                                    <div class="text-[10px] text-slate-700 flex items-start space-x-1">
                                        <span class="text-emerald-600 font-bold">&bull;</span>
                                        <span>{{ $rec }}</span>
                                    </div>
                                @empty
                                    <span class="text-[10px] text-slate-400">Catalog quality standards met</span>
                                @endforelse
                            </td>

                            <!-- Action Buttons -->
                            <td class="p-3 text-right space-x-1 whitespace-nowrap">
                                @if($item['review_count'] > 0)
                                    <button 
                                        type="button" 
                                        @click="openReviewsModal({{ $prod->id }})"
                                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition"
                                    >
                                        Reviews ({{ $item['review_count'] }})
                                    </button>
                                @endif

                                <a 
                                    href="{{ route('seller.products.edit', $prod->id) }}" 
                                    class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-900 text-white font-bold text-[11px] transition inline-block"
                                >
                                    Edit Details
                                </a>

                                @if($prod->status->value === 'rejected')
                                    <form method="POST" action="{{ route('seller.quality.request_review', $prod->id) }}" class="inline-block" onsubmit="return confirm('Request QC moderation re-review for this product?');">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition">
                                            Request Re-Review
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-16 text-center text-slate-400">
                                <div class="w-16 h-16 mx-auto mb-2 text-slate-300">
                                    <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-slate-700">No products in this category</h4>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    @if($activeTab === 'action_required')
                                        Awesome job! No products currently have quality or listing defects.
                                    @elseif($activeTab === 'blocking_soon')
                                        No active products are at risk of being blocked.
                                    @elseif($activeTab === 'fixed')
                                        Products awaiting QC re-review will appear here.
                                    @else
                                        No blocked or inactive products found.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginatedItems->hasPages())
            <div class="p-3 border-t border-slate-100">
                {{ $paginatedItems->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- INTERACTIVE CUSTOMER REVIEWS & MERCHANT REPLY MODAL                      -->
    <!-- ========================================================================= -->
    <div 
        x-show="reviewModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4"
        @keydown.escape.window="reviewModalOpen = false"
    >
        <div class="relative w-full max-w-2xl bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
            <!-- Modal Header -->
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="'Customer Reviews: ' + selectedProductTitle"></h3>
                    <p class="text-[11px] text-slate-500">Read verified customer experiences and post official merchant replies.</p>
                </div>
                <button type="button" @click="reviewModalOpen = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg">&times;</button>
            </div>

            <!-- Reviews Scroll Area -->
            <div class="p-5 overflow-y-auto space-y-4 flex-1">
                <div x-show="loadingReviews" class="py-12 text-center text-xs text-slate-400">
                    Loading customer reviews...
                </div>

                <div x-show="!loadingReviews && reviewsList.length === 0" class="py-12 text-center text-xs text-slate-400">
                    No verified customer reviews found for this product.
                </div>

                <template x-for="rev in reviewsList" :key="rev.id">
                    <div class="p-4 rounded-xl border border-slate-200 space-y-2 bg-slate-50/40">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-xs text-slate-900" x-text="rev.reviewer_name"></span>
                                <span x-show="rev.is_verified_purchase" class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[9px] font-bold">
                                    Verified Buyer
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400" x-text="rev.created_at"></span>
                        </div>

                        <!-- Stars -->
                        <div class="flex items-center space-x-1">
                            <template x-for="i in 5">
                                <span :class="i <= rev.rating ? 'text-amber-400' : 'text-slate-300'" class="text-xs">★</span>
                            </template>
                            <span class="text-xs font-bold text-slate-700 ml-1" x-text="rev.rating + '.0'"></span>
                            <span x-show="rev.title" class="text-xs font-bold text-slate-800 ml-2" x-text="rev.title"></span>
                        </div>

                        <!-- Body -->
                        <p class="text-xs text-slate-700" x-text="rev.body"></p>

                        <!-- Existing Seller Response -->
                        <div x-show="rev.seller_response" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs space-y-1">
                            <div class="flex items-center justify-between text-emerald-900 font-bold text-[10px]">
                                <span>Official Merchant Response:</span>
                                <span class="text-slate-400 font-normal" x-text="rev.seller_responded_at"></span>
                            </div>
                            <p class="text-emerald-800 text-[11px]" x-text="rev.seller_response"></p>
                        </div>

                        <!-- Reply Input if not yet responded -->
                        <div x-show="!rev.seller_response" class="pt-2">
                            <div x-show="replyingReviewId !== rev.id">
                                <button 
                                    type="button" 
                                    @click="replyingReviewId = rev.id; replyText = ''" 
                                    class="text-xs font-bold text-emerald-600 hover:text-emerald-800 underline"
                                >
                                    + Post Merchant Reply
                                </button>
                            </div>

                            <div x-show="replyingReviewId === rev.id" class="space-y-2 pt-1">
                                <textarea 
                                    x-model="replyText" 
                                    rows="2" 
                                    placeholder="Write a professional merchant reply to address customer feedback..."
                                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                                ></textarea>
                                <div class="flex items-center space-x-2 justify-end">
                                    <button type="button" @click="replyingReviewId = null" class="px-3 py-1 rounded text-xs font-bold text-slate-500 hover:bg-slate-100">
                                        Cancel
                                    </button>
                                    <button type="button" @click="submitReply(rev.id)" class="px-3.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
                                        Submit Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 text-right">
                <button type="button" @click="reviewModalOpen = false" class="px-4 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
