@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Reduce RTO & Returns - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    tab: 'applied'
}">
    <!-- Breadcrumb & Back header (Screenshot 11) -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('seller.pricing.index') }}" class="hover:underline">Pricing</a>
        <span>&gt;</span>
        <span class="text-slate-800 font-semibold">Reduce RTO &amp; Returns</span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center space-x-2">
            <a href="{{ route('seller.pricing.index') }}" class="text-slate-400 hover:text-slate-700">&larr;</a>
            <span>Reduce RTO &amp; Returns</span>
        </h1>
        <div class="relative">
            <input 
                type="text" 
                placeholder="Search by Subcategory" 
                class="py-1.5 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 w-64"
            />
            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- Dual Discount Header Cards (Screenshot 11) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Reduce RTO by Prepaid Discount -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center space-x-2 mb-3">
                <span class="text-emerald-600 font-bold">📦</span>
                <h3 class="text-xs font-bold text-slate-900">Reduce RTO by Prepaid Discount</h3>
            </div>
            <div class="grid grid-cols-3 gap-2 pb-3 border-b border-slate-100 text-xs">
                <div>
                    <div class="text-[10px] text-slate-400">COD RTO</div>
                    <div class="font-black text-slate-700 text-sm">35%</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400">Prepaid RTO</div>
                    <div class="font-black text-emerald-600 text-sm">7%</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400">Products with Prepaid Discount</div>
                    <div class="font-black text-slate-800 text-sm">30 / 30</div>
                </div>
            </div>
            <div class="pt-3 text-[11px] text-emerald-700 font-semibold flex items-center space-x-1">
                <span>✔</span>
                <span>Convert COD to prepaid, get more prepaid orders &amp; Reduce RTO risk</span>
            </div>
        </div>

        <!-- Reduce Returns by Wrong/Defective Return Price (WDRP) -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center space-x-2 mb-3">
                <span class="text-indigo-600 font-bold">🔄</span>
                <h3 class="text-xs font-bold text-slate-900">Reduce Returns by Wrong/Defective Return Price</h3>
            </div>
            <div class="grid grid-cols-3 gap-2 pb-3 border-b border-slate-100 text-xs">
                <div>
                    <div class="text-[10px] text-slate-400">All Returns</div>
                    <div class="font-black text-slate-700 text-sm">21%</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400">With WDRP</div>
                    <div class="font-black text-emerald-600 text-sm">16%</div>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400">Products with WDRP</div>
                    <div class="font-black text-slate-800 text-sm">30 / 30</div>
                </div>
            </div>
            <div class="pt-3 text-[11px] text-emerald-700 font-semibold flex items-center space-x-1">
                <span>✔</span>
                <span>Add WDRP avoid unwanted returns. Only get genuine ones</span>
            </div>
        </div>
    </div>

    <!-- Pro Tip Banner -->
    <div class="p-3 bg-indigo-50/70 border border-indigo-200/80 rounded-xl text-xs text-indigo-900 flex items-center space-x-2">
        <span>💡</span>
        <span>Sellers who apply both discounts see fewer RTOs and returns.</span>
    </div>

    <!-- Discounts Applied Table (Screenshot 11) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-3 text-xs font-bold">
            <button @click="tab = 'add'" :class="tab === 'add' ? 'border-[#53389e] text-[#53389e]' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Add Discount (0)
            </button>
            <button @click="tab = 'applied'" :class="tab === 'applied' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Discounts Applied (15)
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="p-3">Applied Discount on</th>
                        <th class="p-3">Reduce RTO by Prepaid ⓘ</th>
                        <th class="p-3">Reduce Returns by WDRP ⓘ</th>
                        <th class="p-3">Current RTO and Returns</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $discountRows = [
                            ['cat' => 'Kids Footwear, Accs & Toys > Remote Control Toys', 'range' => '₹201 - ₹500', 'prods' => '4 products', 'pre' => '₹ 28', 'wdrp' => '₹ 28', 'rto' => 'Prepaid RTO: 20%', 'ret' => 'WDRP Returns: 29%'],
                            ['cat' => 'Kids Footwear, Accs & Toys > Remote Control Toys', 'range' => '₹501 - ₹800', 'prods' => '3 products', 'pre' => '₹ 40', 'wdrp' => '₹ 28', 'rto' => 'Prepaid RTO: 11%', 'ret' => 'WDRP Returns: 0%', 'impact' => 'Medium Impact (Rec: ₹40)'],
                            ['cat' => 'Automotive Accessories > Car Remote Controls', 'range' => '₹201 - ₹500', 'prods' => '2 products', 'pre' => '₹ 25', 'wdrp' => '₹ 25', 'rto' => 'Prepaid RTO: 0%', 'ret' => 'WDRP Returns: 0%'],
                            ['cat' => 'Kids Footwear, Accs & Toys > Spinning & Battling Tops', 'range' => '₹201 - ₹500', 'prods' => '1 product', 'pre' => '₹ 20', 'wdrp' => '₹ 28', 'rto' => 'Prepaid RTO: 7%', 'ret' => 'WDRP Returns: 16%'],
                        ];
                    @endphp
                    @foreach($discountRows as $row)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3">
                                <div class="font-bold text-slate-900">{{ $row['cat'] }}</div>
                                <div class="text-[10px] text-slate-400">Price: {{ $row['range'] }} &bull; {{ $row['prods'] }}</div>
                            </td>
                            <td class="p-3">
                                <div class="text-[10px] text-slate-400">Prepaid Discount</div>
                                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                                    <span>{{ $row['pre'] }}</span>
                                    <span class="text-slate-400 cursor-pointer hover:text-slate-700">✏️</span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="text-[10px] text-slate-400">WDRP Discount</div>
                                <div class="font-bold text-slate-800 flex items-center space-x-1.5">
                                    <span>{{ $row['wdrp'] }}</span>
                                    <span class="text-slate-400 cursor-pointer hover:text-slate-700">✏️</span>
                                </div>
                                @if(!empty($row['impact']))
                                    <span class="text-[9px] px-1 py-0.2 rounded bg-amber-50 text-amber-800 border border-amber-200 block w-max mt-0.5">
                                        {{ $row['impact'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="font-semibold text-slate-800">{{ $row['rto'] }}</div>
                                <div class="text-[11px] text-slate-500">{{ $row['ret'] }}</div>
                            </td>
                            <td class="p-3 text-right">
                                <button type="button" class="text-rose-500 hover:text-rose-700 p-1">
                                    🗑
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
