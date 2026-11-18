@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Return & RTO Reduction Tools - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16">
    <!-- Breadcrumb & Back header -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
        <span>&gt;</span>
        <a href="{{ route('seller.pricing.index') }}" class="hover:underline">Pricing</a>
        <span>&gt;</span>
        <span class="text-slate-800 font-semibold">Return &amp; RTO Reduction</span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-2">
                <a href="{{ route('seller.pricing.index') }}" class="text-slate-400 hover:text-slate-700">&larr;</a>
                <span>Return &amp; RTO Reduction Tools</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Data-driven strategies and platform incentives to minimize Courier RTOs and customer returns.
            </p>
        </div>

        <a 
            href="{{ route('seller.pricing.index') }}" 
            class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs"
        >
            <span>&larr; Back to Pricing Console</span>
        </a>
    </div>

    <!-- Comparative Courier RTO Performance: COD vs Prepaid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Cash on Delivery (COD) Performance -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 rounded-md bg-amber-50 text-amber-600 font-bold text-xs">COD</span>
                    <h3 class="text-sm font-bold text-slate-900">Cash on Delivery (COD) Performance</h3>
                </div>
                <span class="text-[11px] font-semibold text-slate-500">Your Actual Data</span>
            </div>

            <div class="grid grid-cols-3 gap-3 py-4 border-b border-slate-100 text-xs">
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">Shipped Packages</div>
                    <div class="font-extrabold text-slate-900 text-lg mt-0.5">{{ number_format($totalCod) }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">Courier RTOs</div>
                    <div class="font-extrabold text-amber-600 text-lg mt-0.5">{{ number_format($codRto) }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">COD RTO Rate</div>
                    <div class="font-black text-slate-800 text-lg mt-0.5">{{ $codRtoRate }}%</div>
                </div>
            </div>

            <div class="pt-3 text-[11px] text-slate-600 flex items-start space-x-1.5">
                <span class="text-amber-500 font-bold shrink-0">ℹ</span>
                <span>Doorstep refusal on COD is the primary driver of courier return shipping costs.</span>
            </div>
        </div>

        <!-- Prepaid Orders (Razorpay) Performance -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 rounded-md bg-emerald-50 text-emerald-600 font-bold text-xs">UPI/Card</span>
                    <h3 class="text-sm font-bold text-slate-900">Prepaid (Razorpay) Performance</h3>
                </div>
                <span class="text-[11px] font-semibold text-emerald-700">Recommended</span>
            </div>

            <div class="grid grid-cols-3 gap-3 py-4 border-b border-slate-100 text-xs">
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">Shipped Packages</div>
                    <div class="font-extrabold text-slate-900 text-lg mt-0.5">{{ number_format($totalPrepaid) }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">Courier RTOs</div>
                    <div class="font-extrabold text-emerald-600 text-lg mt-0.5">{{ number_format($prepaidRto) }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-medium">Prepaid RTO Rate</div>
                    <div class="font-black text-emerald-700 text-lg mt-0.5">{{ $prepaidRtoRate }}%</div>
                </div>
            </div>

            <div class="pt-3 text-[11px] text-emerald-700 font-medium flex items-start space-x-1.5">
                <span class="font-bold shrink-0">✓</span>
                <span>Prepaid buyers exhibit significantly higher delivery acceptance rates across Indian PIN codes.</span>
            </div>
        </div>
    </div>

    <!-- Platform Prepaid Discount Status -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <h3 class="text-sm font-bold text-slate-900">Admin-Configured Prepaid Discount Incentive</h3>
                    @if($prepaidDiscountEnabled)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                            Active ({{ $prepaidDiscountPct }}% Off)
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold text-[10px]">
                            Standard Rates
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500">
                    When active, customers receive an automated percentage discount at Razorpay checkout when selecting online payments over COD.
                </p>
            </div>

            <div class="shrink-0">
                @if($prepaidDiscountEnabled)
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-900 text-xs font-semibold">
                        Discount of {{ $prepaidDiscountPct }}% applied automatically on checkout.
                    </div>
                @else
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 text-xs">
                        Platform prepaid incentive is managed at the administrative platform level.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Seller Active Coupons and Promotional Offers -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Your Active Coupons &amp; Discount Offers</h3>
                <p class="text-xs text-slate-500 mt-0.5">Product-specific and cart-level promotional discounts configured for your store.</p>
            </div>
            <a 
                href="{{ route('seller.promotions.index') }}" 
                class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-semibold text-xs border border-indigo-200 hover:bg-indigo-100 transition"
            >
                Manage Store Promotions &rarr;
            </a>
        </div>

        @if($coupons->isEmpty())
            <div class="py-12 text-center flex flex-col items-center justify-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <h4 class="text-xs font-bold text-slate-700">No active seller coupons</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">Create store coupons to offer promotional savings on selected products.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="p-3">Coupon Code</th>
                            <th class="p-3">Discount Value</th>
                            <th class="p-3">Min. Order Value</th>
                            <th class="p-3">Valid Until</th>
                            <th class="p-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($coupons as $coupon)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3 font-mono font-bold text-slate-900">{{ $coupon->code }}</td>
                                <td class="p-3 font-semibold text-slate-800">{{ $coupon->formatted_discount }}</td>
                                <td class="p-3 text-slate-600">₹{{ number_format((float)$coupon->min_order, 2) }}</td>
                                <td class="p-3 text-slate-500">{{ $coupon->ends_at ? $coupon->ends_at->format('d M Y') : 'No expiry' }}</td>
                                <td class="p-3 text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $coupon->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Practical Return-Prevention Checklist (Best Practices) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
        <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
            <span>🛡️</span>
            <span>Return Prevention Operational Checklist</span>
        </h3>
        <p class="text-xs text-slate-500">
            Analysis of customer return requests indicates that over 70% of non-defective returns stem from catalog mismatch or sizing ambiguities.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-lg border border-slate-100 bg-slate-50/50 space-y-1">
                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <span class="text-indigo-600">1.</span>
                    <span>Accurate Size Charts &amp; Dimensions</span>
                </div>
                <p class="text-slate-500 text-[11px]">
                    Ensure detailed garment measurements (chest, length, sleeve) or product dimensions are explicitly listed in catalog descriptions.
                </p>
            </div>

            <div class="p-3.5 rounded-lg border border-slate-100 bg-slate-50/50 space-y-1">
                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <span class="text-indigo-600">2.</span>
                    <span>High-Fidelity Product Photography</span>
                </div>
                <p class="text-slate-500 text-[11px]">
                    Use daylight-balanced photography without excessive filters to represent fabric textures and color shades accurately.
                </p>
            </div>

            <div class="p-3.5 rounded-lg border border-slate-100 bg-slate-50/50 space-y-1">
                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <span class="text-indigo-600">3.</span>
                    <span>Rapid Order Dispatch (Under 24h)</span>
                </div>
                <p class="text-slate-500 text-[11px]">
                    Fulfill orders promptly via Shiprocket. Prolonged transit times significantly increase COD doorstep cancellation rates.
                </p>
            </div>

            <div class="p-3.5 rounded-lg border border-slate-100 bg-slate-50/50 space-y-1">
                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <span class="text-indigo-600">4.</span>
                    <span>Tamper-Evident Packaging</span>
                </div>
                <p class="text-slate-500 text-[11px]">
                    Secure packages with reinforced branded tape and bubble wrap to eliminate transit wear and tear before reaching the customer.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
