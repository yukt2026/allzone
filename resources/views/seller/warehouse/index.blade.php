@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Meesho Warehouse - Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header (Screenshot 8) -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Meesho Warehouse
        </h1>
    </div>

    <!-- Onboarding Steps & Explainer Video Box (Screenshot 8 Top) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-2xs">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <!-- Steps -->
            <div>
                <h2 class="text-base font-bold text-slate-900 mb-4">Join Meesho Warehousing in 4 easy steps!</h2>
                <div class="space-y-3.5 text-xs text-slate-700">
                    <div class="flex items-center space-x-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">✔</span>
                        <span>Click on "Join Us Now" button below</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">✔</span>
                        <span>Complete GST and APOB registration (We'll help you)</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">✔</span>
                        <span>Dispatch inventory to the warehouse</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px]">✔</span>
                        <span>Watch your business takeoff on Meesho</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 mt-6">
                    <button type="button" class="px-5 py-2.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold transition shadow-xs">
                        Join Us Now
                    </button>
                    <button type="button" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                        Request Callback
                    </button>
                </div>
            </div>

            <!-- Video Thumbnail Card -->
            <div class="relative rounded-2xl overflow-hidden bg-gradient-to-tr from-purple-900 via-indigo-900 to-slate-900 p-6 text-white flex flex-col justify-between h-56 shadow-md">
                <div>
                    <span class="px-2 py-0.5 rounded bg-purple-600 text-[10px] font-black uppercase">meesho</span>
                    <h3 class="text-base font-extrabold mt-2">Explore Meesho Warehouse: Boost Your Business!</h3>
                </div>
                <div class="flex items-center justify-center">
                    <div class="w-12 h-12 rounded-full bg-rose-600 flex items-center justify-center shadow-lg text-lg text-white font-bold cursor-pointer hover:scale-105 transition">
                        ▶
                    </div>
                </div>
                <div class="text-[11px] text-slate-300">Watch on YouTube &bull; 4:20 mins</div>
            </div>
        </div>
    </div>

    <!-- Benefits Cards (Screenshot 8 Middle) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
        <div class="p-4 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-base mb-2">📈</div>
            <h4 class="font-bold text-slate-900">Grow Sales by 9%-11%</h4>
            <p class="text-[11px] text-slate-500 mt-1">Benefits from Meesho's upto Rs 5 customer discount without affecting your margins</p>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-base mb-2">🛡️</div>
            <h4 class="font-bold text-slate-900">100% Protection from Frauds</h4>
            <p class="text-[11px] text-slate-500 mt-1">Enjoy peace of mind with guaranteed approval on all valid claim requests</p>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-base mb-2">🔄</div>
            <h4 class="font-bold text-slate-900">Reduce Returns by 6-7%</h4>
            <p class="text-[11px] text-slate-500 mt-1">Reduce damage related returns with our expert fulfillment handling</p>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/90 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-base mb-2">🚀</div>
            <h4 class="font-bold text-slate-900">Supercharge Operations</h4>
            <p class="text-[11px] text-slate-500 mt-1">Access real-time inventory tracking and automated dispatch SLAs</p>
        </div>
    </div>

    <!-- Rate Card Tables: Warehouse Fees & Reverse Shipping Fees (Screenshot 8 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Warehouse Fees Structure</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Per Unit Charges</th>
                        <th class="p-3">Upto 150 gms</th>
                        <th class="p-3">151-500 gms</th>
                        <th class="p-3">501-1000 gms</th>
                        <th class="p-3">1001-2000 gms</th>
                        <th class="p-3">Above 2000 gms</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <tr>
                        <td class="p-3 font-medium">Delivered Orders (Local Zones &lt; 500km)</td>
                        <td class="p-3">₹4</td>
                        <td class="p-3">₹10</td>
                        <td class="p-3">₹13.50</td>
                        <td class="p-3">₹28</td>
                        <td class="p-3">₹38</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Delivered Orders (National Zones &gt; 500km)</td>
                        <td class="p-3">₹15</td>
                        <td class="p-3">₹20</td>
                        <td class="p-3">₹27</td>
                        <td class="p-3">₹40</td>
                        <td class="p-3">₹67</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">Return Orders</td>
                        <td class="p-3">₹12.75</td>
                        <td class="p-3">₹14</td>
                        <td class="p-3">₹14.75</td>
                        <td class="p-3">₹20.50</td>
                        <td class="p-3">₹20.50</td>
                    </tr>
                    <tr>
                        <td class="p-3 font-medium">RTO processing / Insurance charges</td>
                        <td class="p-3 font-bold text-emerald-600">FREE</td>
                        <td class="p-3 font-bold text-emerald-600">FREE</td>
                        <td class="p-3 font-bold text-emerald-600">FREE</td>
                        <td class="p-3 font-bold text-emerald-600">FREE</td>
                        <td class="p-3 font-bold text-emerald-600">FREE</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
