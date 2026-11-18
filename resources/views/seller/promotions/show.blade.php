@extends('layouts.dashboard', ['portal' => 'seller', 'title' => "Campaign: {$campaign->name} - ALLZONE Supplier Hub"])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="campaignOptInManager({{ json_encode(['minDiscount' => (float)$campaign->min_discount_percentage, 'maxDiscount' => (float)($campaign->max_discount_percentage ?? 90)]) }})">
    <!-- Breadcrumb & Back -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <a href="{{ route('seller.promotions.index') }}" class="hover:text-slate-700">Promotions Hub</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">{{ $campaign->name }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $campaign->name }}</h1>
                <span class="px-2.5 py-0.5 rounded text-xs font-extrabold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $campaign->type_label }}
                </span>
                @if($participationStatus === 'enrolled')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ✓ Enrolled Participant
                    </span>
                @elseif($participationStatus === 'opted_out')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Opted Out
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        Not Enrolled
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center space-x-3">
            @if($participationStatus === 'enrolled' && ($campaign->allow_opt_out || !$campaign->isLive()))
                <form action="{{ route('seller.promotions.opt_out', $campaign) }}" method="POST" onsubmit="return confirm('Opt out of this campaign? Enrolled products will immediately return to standard selling prices.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-bold border border-rose-300 text-rose-700 hover:bg-rose-50 transition shadow-2xs">
                        Opt Out of Campaign
                    </button>
                </form>
            @endif

            <a href="{{ route('seller.promotions.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition shadow-2xs">
                &larr; Back to Campaigns
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

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1 shadow-2xs">
            @foreach($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <!-- Key Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Participation Status</span>
            <div class="text-lg font-black mt-1 {{ $participationStatus === 'enrolled' ? 'text-emerald-700' : 'text-slate-800' }}">
                {{ ucfirst(str_replace('_', ' ', $participationStatus)) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $participation?->enrolled_at ? 'Since ' . $participation->enrolled_at->format('d M Y') : 'Open to enroll' }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Your Enrolled Products</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $transparentBenefits['enrolled_count'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Active campaign listings</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Average Discount Locked</span>
            <div class="text-2xl font-black text-indigo-700 font-mono mt-1">{{ $transparentBenefits['avg_discount'] }}%</div>
            <div class="text-[11px] text-slate-500 mt-1">Min required: {{ $campaign->min_discount_percentage }}%</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customer Price Drop</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">₹{{ number_format($transparentBenefits['total_savings_per_unit'], 2) }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Total savings per order unit</div>
        </div>
    </div>

    <!-- Campaign Rules, Criteria & Transparent Calculation Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Rules & Criteria (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Campaign Eligibility &amp; Discount Rules</h2>
                <span class="text-xs font-mono font-bold text-slate-500">ID: #{{ $campaign->id }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="font-bold text-slate-500">Discount Range</span>
                    <div class="font-black text-slate-900 text-sm">
                        {{ $campaign->min_discount_percentage }}%
                        @if($campaign->max_discount_percentage)
                            - {{ $campaign->max_discount_percentage }}%
                        @else
                            or more
                        @endif
                    </div>
                    <div class="text-[11px] text-slate-500">Applied on base selling price</div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="font-bold text-slate-500">Category Scope</span>
                    <div class="font-black text-slate-900 text-sm truncate">
                        {{ $campaign->category?->name ?? 'All Categories' }}
                    </div>
                    <div class="text-[11px] text-slate-500">
                        {{ $campaign->min_product_rating ? "Min rating {$campaign->min_product_rating}★" : 'All ratings eligible' }}
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                    <span class="font-bold text-slate-500">Opt-out Policy</span>
                    <div class="font-black text-slate-900 text-sm">
                        {{ $campaign->allow_opt_out ? 'Permitted anytime' : 'Locked once live' }}
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Deadline: {{ $campaign->opt_in_deadline?->format('d M, h:i A') ?? 'Until campaign ends' }}
                    </div>
                </div>
            </div>

            @if($campaign->description)
                <div class="text-xs text-slate-600 leading-relaxed pt-1">
                    <span class="font-bold text-slate-800">Campaign Overview:</span> {{ $campaign->description }}
                </div>
            @endif

            @if($campaign->terms_and_conditions)
                <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 space-y-1">
                    <span class="font-black uppercase tracking-wider text-[10px]">ALLZONE Participation Terms &amp; Conditions:</span>
                    <p class="leading-relaxed">{{ $campaign->terms_and_conditions }}</p>
                </div>
            @endif
        </div>

        <!-- Transparent Benefits & Settlement Calculator (1 Column) -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-5 shadow-2xs space-y-3 flex flex-col justify-between">
            <div class="space-y-3 text-xs">
                <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500 text-slate-950 uppercase">Transparent Math</span>
                    <span class="text-slate-300 font-semibold text-[11px]">No Fabricated Claims</span>
                </div>
                <h3 class="text-sm font-black text-white">How Your Payout is Calculated</h3>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                    Unlike platforms that make unverified 10x order volume claims, ALLZONE provides an exact audit breakdown of price drops and merchant settlements.
                </p>

                <div class="space-y-2 pt-2 border-t border-slate-700/80 font-mono text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>Original Product Price:</span>
                        <span class="text-white font-bold">₹1,000.00</span>
                    </div>
                    <div class="flex justify-between text-rose-300">
                        <span>Campaign Discount (e.g. 10%):</span>
                        <span>- ₹100.00</span>
                    </div>
                    <div class="flex justify-between text-emerald-300 font-bold border-t border-slate-700 pt-1">
                        <span>Promotional Customer Price:</span>
                        <span>₹900.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400 text-[11px]">
                        <span>Est. Commission &amp; GST (~8%):</span>
                        <span>- ₹72.00</span>
                    </div>
                    <div class="flex justify-between text-indigo-300 font-black border-t border-slate-700 pt-1">
                        <span>Net Merchant Settlement:</span>
                        <span>₹828.00</span>
                    </div>
                </div>
            </div>

            <div class="text-[10px] text-slate-400 pt-2 border-t border-slate-700">
                Settlements are disbursed automatically based on confirmed deliveries and return windows.
            </div>
        </div>
    </div>

    <!-- Section 1: Enrolled Products Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900">Your Enrolled Products in this Campaign ({{ $enrolledProducts->count() }})</h2>
                <p class="text-xs text-slate-500 mt-0.5">These items receive verified promotional discount badges during the sale window.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="p-3">Product</th>
                        <th class="p-3">SKU</th>
                        <th class="p-3">Regular Price</th>
                        <th class="p-3">Discount Locked</th>
                        <th class="p-3">Campaign Price</th>
                        <th class="p-3">Buyer Savings</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($enrolledProducts as $item)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 max-w-xs truncate">
                                    {{ $item->product?->title ?? 'Product #' . $item->product_id }}
                                </div>
                            </td>
                            <td class="p-3 font-mono text-slate-500">
                                {{ $item->product?->sku ?? 'N/A' }}
                            </td>
                            <td class="p-3 font-mono text-slate-500 line-through">
                                ₹{{ number_format((float) $item->original_price, 2) }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded font-black text-xs bg-emerald-100 text-emerald-800">
                                    {{ $item->discount_percentage }}% OFF
                                </span>
                            </td>
                            <td class="p-3 font-mono font-black text-slate-900 text-sm">
                                ₹{{ number_format((float) $item->campaign_price, 2) }}
                            </td>
                            <td class="p-3 font-mono font-bold text-emerald-700">
                                ₹{{ number_format(max(0, (float)$item->original_price - (float)$item->campaign_price), 2) }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $item->status === 'enrolled' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <form action="{{ route('seller.promotions.remove_product', [$campaign, $item->product_id]) }}" method="POST" onsubmit="return confirm('Remove product from campaign?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-rose-600 hover:text-rose-800 font-bold hover:bg-rose-50 rounded transition text-xs">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400">
                                You have not enrolled any products in this campaign yet. Select eligible products below to participate.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Eligible Products & Opt-in Selection Form -->
    @if($campaign->isOptInOpen())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900">Eligible Products for Opt-in ({{ $eligibleProducts->count() }} Available)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Select products to add to this promotional campaign. Discounts must be at least {{ $campaign->min_discount_percentage }}%.</p>
                </div>
            </div>

            @if($eligibleProducts->isNotEmpty())
                <form action="{{ route('seller.promotions.opt_in', $campaign) }}" method="POST" class="p-5 space-y-5">
                    @csrf

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="p-3 w-10">
                                        <input type="checkbox" @change="toggleAll($event)" class="rounded text-emerald-600 focus:ring-emerald-500">
                                    </th>
                                    <th class="p-3">Product Name</th>
                                    <th class="p-3">SKU</th>
                                    <th class="p-3">Current Price</th>
                                    <th class="p-3">Campaign Discount (%)</th>
                                    <th class="p-3">Effective Campaign Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($eligibleProducts as $prod)
                                    @php
                                        $basePrice = (float) $prod->effective_price;
                                        if ($basePrice <= 0) $basePrice = 100.0;
                                        $minDisc = (float) $campaign->min_discount_percentage;
                                        $initialCampPrice = round($basePrice * (1 - ($minDisc / 100)), 2);
                                    @endphp
                                    <tr class="hover:bg-slate-50/60" x-data="{ 
                                        selected: false, 
                                        basePrice: {{ $basePrice }}, 
                                        discount: {{ $minDisc }},
                                        get campaignPrice() {
                                            return Math.round(this.basePrice * (1 - (this.discount / 100)) * 100) / 100;
                                        }
                                    }">
                                        <td class="p-3">
                                            <input 
                                                type="checkbox" 
                                                name="selected_products[]" 
                                                value="{{ $prod->id }}" 
                                                x-model="selected"
                                                class="product-checkbox rounded text-emerald-600 focus:ring-emerald-500"
                                            >
                                        </td>
                                        <td class="p-3">
                                            <div class="font-bold text-slate-900 max-w-sm truncate">{{ $prod->title }}</div>
                                        </td>
                                        <td class="p-3 font-mono text-slate-500">{{ $prod->sku ?? 'N/A' }}</td>
                                        <td class="p-3 font-mono font-bold text-slate-700">₹{{ number_format($basePrice, 2) }}</td>
                                        <td class="p-3">
                                            <div class="flex items-center space-x-1.5">
                                                <input 
                                                    type="number" 
                                                    step="0.5" 
                                                    min="{{ $campaign->min_discount_percentage }}" 
                                                    max="{{ $campaign->max_discount_percentage ?? 90 }}"
                                                    name="discounts[{{ $prod->id }}]" 
                                                    x-model.number="discount"
                                                    class="w-20 px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-emerald-500"
                                                >
                                                <span class="text-slate-500 font-bold">%</span>
                                            </div>
                                        </td>
                                        <td class="p-3 font-mono font-black text-emerald-700">
                                            ₹<span x-text="campaignPrice.toFixed(2)"></span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Terms Acceptance & Submit Actions -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <label class="flex items-start space-x-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="terms_accepted" 
                                value="1" 
                                required
                                class="rounded text-emerald-600 focus:ring-emerald-500 mt-0.5"
                            >
                            <span class="text-xs text-slate-700 font-medium leading-relaxed">
                                I confirm participation in <strong>{{ $campaign->name }}</strong> and agree to lock the specified discount prices for the duration of this promotional event. I understand my products will receive promotional placement and orders will be settled according to standard ALLZONE financial policies.
                            </span>
                        </label>

                        <div class="pt-2 flex items-center justify-end space-x-3">
                            <button 
                                type="submit" 
                                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition shadow-sm"
                            >
                                Confirm Opt-In &amp; Enroll Selected Products
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="p-8 text-center text-slate-400 text-xs">
                    All your eligible products are already enrolled in this campaign or no products match the category/rating criteria.
                </div>
            @endif
        </div>
    @else
        <div class="p-5 rounded-2xl bg-slate-100 text-slate-600 text-xs text-center font-medium">
            Opt-in is currently closed for this promotional event.
        </div>
    @endif
</div>

<script>
function campaignOptInManager(config) {
    return {
        minDiscount: config.minDiscount || 5,
        maxDiscount: config.maxDiscount || 90,
        toggleAll(event) {
            const checked = event.target.checked;
            document.querySelectorAll('.product-checkbox').forEach(cb => {
                cb.checked = checked;
                cb.dispatchEvent(new Event('change'));
            });
        }
    };
}
</script>
@endsection
