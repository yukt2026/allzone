@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Advertisement - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12" x-data="{
    adTab: 'all'
}">
    <!-- Header with Campaign Action (Screenshot 10) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center space-x-3">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Advertisement
            </h1>
            <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                🪙 ₹0 Free Credits Available!
            </span>
        </div>

        <div class="flex items-center space-x-3 text-xs">
            <a href="#" class="text-slate-600 font-semibold hover:text-slate-900">How it works?</a>
            <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white font-bold text-xs shadow-xs transition">
                Create New Campaign
            </button>
        </div>
    </div>

    <!-- Maha Indian Savings Sale Header Banner (Screenshot 10) -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-50 via-rose-50 to-pink-50 border border-rose-200 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-xl bg-rose-500 text-white font-black flex items-center justify-center text-center text-xs p-1">
                MAHA SAVINGS
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                    2 big sales are coming up - Increase Ad budget &amp; get upto 3X more orders
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">22 Nov (MISS) + 06 Dec (FSMS)</p>
            </div>
        </div>
        <div class="flex items-center space-x-2 shrink-0">
            <a href="#" class="text-xs font-bold text-indigo-700 hover:underline">View All Sales &gt;</a>
            <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold text-xs hover:bg-[#32236e] transition shadow-xs">
                Add Budget
            </button>
        </div>
    </div>

    <!-- Ad Metrics Overview Grid (Screenshot 10 Middle) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex items-center justify-between mb-3 text-xs">
            <h3 class="font-bold text-slate-900">Overview</h3>
            <select class="py-1 px-2 border border-slate-200 rounded text-slate-700 font-medium">
                <option>Last 30 Days</option>
            </select>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">Ad Spend</div>
                <div class="text-base font-black text-slate-900 mt-0.5">₹ 63.72</div>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">Revenue ⓘ</div>
                <div class="text-base font-black text-slate-900 mt-0.5">₹ 0</div>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">ROI ⓘ</div>
                <div class="text-base font-black text-slate-900 mt-0.5">-</div>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">Views</div>
                <div class="text-base font-black text-slate-900 mt-0.5">14942</div>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">Clicks ⓘ</div>
                <div class="text-base font-black text-slate-900 mt-0.5">250</div>
            </div>
            <div class="p-3 bg-slate-50/70 rounded-lg border border-slate-100">
                <div class="text-[10px] text-slate-400">Orders ⓘ</div>
                <div class="text-base font-black text-slate-900 mt-0.5">-</div>
            </div>
        </div>
    </div>

    <!-- Listing Ads Campaign Table (Screenshot 10 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-900">Listing Ads</h3>
        </div>

        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-2 text-xs font-bold">
            <button @click="adTab = 'all'" :class="adTab === 'all' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                ALL (10)
            </button>
            <button @click="adTab = 'live'" :class="adTab === 'live' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                LIVE (0)
            </button>
            <button @click="adTab = 'paused'" :class="adTab === 'paused' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                PAUSED (10)
            </button>
            <button @click="adTab = 'upcoming'" :class="adTab === 'upcoming' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                UPCOMING (0)
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Campaign</th>
                        <th class="p-3">Budget</th>
                        <th class="p-3">Budget Utilized</th>
                        <th class="p-3">Impressions</th>
                        <th class="p-3">Orders</th>
                        <th class="p-3">Revenue</th>
                        <th class="p-3 text-right">Performance Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3">
                            <div class="font-bold text-slate-900">Ad_1 28 Oct 2026 | 21:46</div>
                            <div class="text-[10px] text-slate-400">ID 27765389 &bull; 1 Catalog</div>
                            <span class="px-1.5 py-0.2 rounded bg-rose-50 text-rose-700 text-[9px] font-bold mt-1 inline-block">PAUSED</span>
                        </td>
                        <td class="p-3 font-semibold text-slate-800">₹300 <span class="text-[10px] text-slate-400 block font-normal">Daily</span></td>
                        <td class="p-3 font-semibold text-slate-800">₹6.82</td>
                        <td class="p-3 text-slate-700">Views: 1669<br><span class="text-slate-400">Clicks: 11</span></td>
                        <td class="p-3 text-slate-400">-</td>
                        <td class="p-3 text-slate-400">-</td>
                        <td class="p-3 text-right">
                            <button type="button" class="text-[#53389e] font-bold hover:underline">See Details</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3">
                            <div class="font-bold text-slate-900">Ad_1 28 Oct 2026 | 21:42</div>
                            <div class="text-[10px] text-slate-400">ID 27765332 &bull; 1 Catalog</div>
                            <span class="px-1.5 py-0.2 rounded bg-rose-50 text-rose-700 text-[9px] font-bold mt-1 inline-block">PAUSED</span>
                        </td>
                        <td class="p-3 font-semibold text-slate-800">₹300 <span class="text-[10px] text-slate-400 block font-normal">Daily</span></td>
                        <td class="p-3 font-semibold text-slate-800">₹56.91</td>
                        <td class="p-3 text-slate-700">Views: 13273<br><span class="text-slate-400">Clicks: 239</span></td>
                        <td class="p-3 text-slate-400">-</td>
                        <td class="p-3 text-slate-400">-</td>
                        <td class="p-3 text-right">
                            <button type="button" class="text-[#53389e] font-bold hover:underline">See Details</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
