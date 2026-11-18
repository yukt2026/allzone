@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Create Ad Campaign - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="adCampaignCreator()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('seller.ads.index') }}" class="hover:text-slate-700">Advertising Hub</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">New Campaign</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Advertisement Campaign</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure your sponsored listings, upload marketing creatives, and specify targeting parameters for admin review.
            </p>
        </div>

        <div>
            <a href="{{ route('seller.ads.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                &larr; Back to Campaigns
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1 shadow-2xs">
            @foreach($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('seller.ads.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        <!-- Left 2 Columns: Campaign Details & Product Selection -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 1. Campaign Basics -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">1. Campaign Details</h2>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Title *</label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="campaignName"
                        required 
                        placeholder="e.g. Wireless Audio Festival Boost" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                    >
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Placement Type *</label>
                        <select name="type" x-model="campaignType" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500">
                            <option value="sponsored_listing">Sponsored Product Listing</option>
                            <option value="banner_display">Category Header Banner</option>
                            <option value="featured_category">Featured Collection Spotlight</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Targeting Type *</label>
                        <select name="targeting_type" x-model="targetingType" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500">
                            <option value="category">Category-Based Placement</option>
                            <option value="search_keywords">Search Keyword Match</option>
                            <option value="storewide">Storewide Featured Catalog</option>
                        </select>
                    </div>
                </div>

                <div x-show="targetingType === 'search_keywords'" class="pt-1">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Keywords (Comma-separated)</label>
                    <input 
                        type="text" 
                        name="targeting_keywords" 
                        placeholder="e.g. bluetooth earbuds, noise cancelling, audio" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500 font-mono"
                    >
                    <p class="text-[11px] text-slate-400 mt-0.5">Your sponsored listings appear when shoppers search for these terms.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Promotional Headline / Ad Copy</label>
                    <input 
                        type="text" 
                        name="headline" 
                        x-model="headline"
                        placeholder="e.g. Premium Sound with 30-Hr Battery Life" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Upload Creative Banner / Graphic (Optional)</label>
                    <input 
                        type="file" 
                        name="creative_image" 
                        id="creativeImageInput"
                        accept="image/jpeg,image/png,image/webp"
                        @change="previewImage($event)"
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Recommended: 1200x400px JPEG/PNG/WebP, max 2MB.</p>

                    <!-- Instant Live Image Preview -->
                    <template x-if="imagePreview">
                        <div class="mt-3 relative rounded-xl overflow-hidden border border-slate-200 bg-slate-50 p-2">
                            <div class="flex items-center justify-between pb-1.5 text-xs font-bold text-slate-700">
                                <span class="flex items-center gap-1 text-emerald-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Image Selected &amp; Ready
                                </span>
                                <button type="button" @click="clearImage()" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold">Remove</button>
                            </div>
                            <img :src="imagePreview" alt="Banner Preview" class="w-full h-36 object-cover rounded-lg">
                        </div>
                    </template>
                </div>
            </div>

            <!-- 2. Budget & Schedule -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">2. Budget &amp; Validity Schedule</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Daily Budget (₹) *</label>
                        <input 
                            type="number" 
                            step="10" 
                            min="50" 
                            max="100000" 
                            name="daily_budget" 
                            x-model.number="dailyBudget"
                            required 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-emerald-500"
                        >
                        <p class="text-[11px] text-slate-400 mt-0.5">Minimum ₹50.00 / day</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Total Budget Cap (₹) *</label>
                        <input 
                            type="number" 
                            step="50" 
                            min="50" 
                            max="1000000" 
                            name="total_budget" 
                            x-model.number="totalBudget"
                            required 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-emerald-500"
                        >
                        <p class="text-[11px] text-slate-400 mt-0.5">Campaign pauses automatically when cap is reached</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Start Date</label>
                        <input 
                            type="datetime-local" 
                            name="starts_at" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">End Date (Optional)</label>
                        <input 
                            type="datetime-local" 
                            name="ends_at" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                </div>
            </div>

            <!-- 3. Product Selection -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">3. Select Products to Advertise *</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Choose catalog listings and optionally specify custom click bids.</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-500">
                        {{ $products->count() }} Available
                    </span>
                </div>

                <div class="overflow-x-auto max-h-96">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] sticky top-0 z-10">
                            <tr>
                                <th class="p-3 w-10">
                                    <input type="checkbox" @change="toggleAll($event)" class="rounded text-emerald-600 focus:ring-emerald-500">
                                </th>
                                <th class="p-3">Product Name</th>
                                <th class="p-3">SKU</th>
                                <th class="p-3">Current Price</th>
                                <th class="p-3">Suggested Bid (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($products as $p)
                                <tr class="hover:bg-slate-50/60" x-data="{ checked: false }">
                                    <td class="p-3">
                                        <input 
                                            type="checkbox" 
                                            name="selected_products[]" 
                                            value="{{ $p->id }}" 
                                            x-model="checked"
                                            @change="updateSelectedCount()"
                                            class="product-ad-checkbox rounded text-emerald-600 focus:ring-emerald-500"
                                        >
                                    </td>
                                    <td class="p-3 font-bold text-slate-900 max-w-xs truncate">
                                        {{ $p->title }}
                                    </td>
                                    <td class="p-3 font-mono text-slate-500">
                                        {{ $p->sku ?? 'N/A' }}
                                    </td>
                                    <td class="p-3 font-mono font-bold text-slate-800">
                                        ₹{{ number_format((float)$p->effective_price, 2) }}
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-slate-400">₹</span>
                                            <input 
                                                type="number" 
                                                step="0.5" 
                                                min="1" 
                                                max="500" 
                                                name="bids[{{ $p->id }}]" 
                                                value="5.00"
                                                class="w-20 px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-emerald-500"
                                            >
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        No active products found in your catalog. Please add products first.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right 1 Column: Live Ad Preview & Submission Card -->
        <div class="space-y-6">
            <!-- Live Preview Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Live Ad Preview</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Ad Preview
                    </span>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50/50 space-y-2">
                    <div class="flex items-center space-x-1.5 text-[10px] text-slate-400 font-bold uppercase">
                        <span class="px-1.5 py-0.2 rounded bg-slate-200 text-slate-700">Sponsored</span>
                        <span>&bull;</span>
                        <span x-text="campaignType.replace('_', ' ')"></span>
                    </div>

                    <!-- Live Creative Banner in Preview Card -->
                    <template x-if="imagePreview">
                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-2xs">
                            <img :src="imagePreview" alt="Creative banner preview" class="w-full h-28 object-cover">
                        </div>
                    </template>

                    <div class="font-extrabold text-sm text-slate-900" x-text="headline || campaignName || 'Featured Product Title'"></div>

                    <div class="p-2 bg-white rounded-lg border border-slate-100 flex items-center space-x-2.5">
                        <div class="w-10 h-10 rounded bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                            🏷️
                        </div>
                        <div class="text-xs">
                            <div class="font-bold text-slate-800" x-text="`${selectedCount} Selected Products`"></div>
                            <div class="text-[10px] text-emerald-600 font-medium">Targeted discovery active</div>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-500 pt-1 flex justify-between font-mono">
                        <span>Daily Budget:</span>
                        <span class="font-bold text-slate-800" x-text="`₹${dailyBudget.toFixed(2)}`"></span>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-200 text-xs text-emerald-900 leading-relaxed space-y-1">
                    <span class="font-bold">Transparent Delivery Note:</span>
                    <p class="text-[11px]">
                        Ads do not go live until verified by ALLZONE administrators. Clicks and impressions are recorded strictly from authentic buyer interactions.
                    </p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-3">
                <button 
                    type="submit" 
                    name="action" 
                    value="submit"
                    class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition shadow-sm"
                >
                    Submit Campaign for Admin Review
                </button>

                <button 
                    type="submit" 
                    name="action" 
                    value="save_draft"
                    class="w-full py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs transition shadow-2xs"
                >
                    Save as Draft
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function adCampaignCreator() {
    return {
        campaignName: '',
        campaignType: 'sponsored_listing',
        targetingType: 'category',
        headline: '',
        dailyBudget: 100,
        totalBudget: 1000,
        selectedCount: 0,
        imagePreview: null,
        previewImage(event) {
            const file = event.target.files ? event.target.files[0] : null;
            if (file) {
                if (this.imagePreview) {
                    URL.revokeObjectURL(this.imagePreview);
                }
                this.imagePreview = URL.createObjectURL(file);
            } else {
                this.imagePreview = null;
            }
        },
        clearImage() {
            if (this.imagePreview) {
                URL.revokeObjectURL(this.imagePreview);
            }
            this.imagePreview = null;
            const input = document.getElementById('creativeImageInput');
            if (input) input.value = '';
        },
        toggleAll(event) {
            const checked = event.target.checked;
            document.querySelectorAll('.product-ad-checkbox').forEach(cb => {
                cb.checked = checked;
                cb.dispatchEvent(new Event('change'));
            });
            this.updateSelectedCount();
        },
        updateSelectedCount() {
            this.selectedCount = document.querySelectorAll('.product-ad-checkbox:checked').length;
        }
    };
}
</script>
@endsection
