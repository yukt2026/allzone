@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Claims - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    tab: 'all'
}">
    <!-- Top Header & Raise Claim (Screenshot 1) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Claims
        </h1>

        <div class="flex items-center space-x-3 text-xs">
            <div class="relative">
                <input type="text" placeholder="Search by Sub Order ID or Ticket ID" class="py-1.5 pl-3 pr-8 border border-slate-300 rounded-lg text-xs w-64">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold flex items-center space-x-1.5 shadow-xs">
                <span>➕</span>
                <span>Raise Claim</span>
            </button>
        </div>
    </div>

    <!-- Overview Performance Box (Screenshot 1 Top) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex items-center space-x-3 text-xs mb-4">
            <h3 class="font-bold text-slate-900">Overview</h3>
            <select class="py-1 px-2 border border-slate-200 rounded text-slate-700 font-medium">
                <option>Last 30 Days</option>
            </select>
            <span class="text-slate-400 text-[11px]">(Returns delivered from 19 Oct - 17 Nov)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 rounded-xl border border-slate-100 bg-slate-50/40 text-xs">
            <div>
                <div class="flex items-center justify-between text-slate-500">
                    <span>Total Approved Claims</span>
                    <a href="#" class="font-bold text-[#53389e] hover:underline">View Details</a>
                </div>
                <div class="flex items-baseline space-x-2 my-1">
                    <span class="text-2xl font-black text-slate-900">33.33%</span>
                    <span class="text-rose-500 font-bold">&blacktriangledown; 33.33%</span>
                </div>
                <div class="text-[11px] text-slate-400">1 claims approved out of 3 closed. 1 open</div>

                <div class="flex items-center space-x-4 mt-3 pt-3 border-t border-slate-200 text-[11px]">
                    <div>RTO Claims Approved: <strong>0%</strong></div>
                    <div>Customer Return Claims Approved: <strong>33.33%</strong></div>
                </div>
            </div>

            <div>
                <div class="text-slate-500">Claim Rate</div>
                <div class="flex items-baseline space-x-2 my-1">
                    <span class="text-2xl font-black text-slate-900">9.3%</span>
                    <span class="text-rose-500 font-bold">&blacktriangledown; 4.33%</span>
                </div>
                <div class="text-[11px] text-slate-400">4 claims raised by you out of 43 returns delivered</div>

                <div class="flex items-center space-x-4 mt-3 pt-3 border-t border-slate-200 text-[11px]">
                    <div>RTO Claim Rate: <strong>0%</strong></div>
                    <div>Customer Return Claim Rate: <strong>25%</strong></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Claim Tracking Table (Screenshot 1 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-900">Claim Tracking</h3>
        </div>

        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-2 text-xs font-bold">
            <button @click="tab = 'all'" :class="tab === 'all' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                All (22)
            </button>
            <button @click="tab = 'open'" :class="tab === 'open' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Open (1)
            </button>
            <button @click="tab = 'approved'" :class="tab === 'approved' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Approved (11)
            </button>
            <button @click="tab = 'rejected'" :class="tab === 'rejected' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500'" class="pb-3 border-b-2">
                Rejected (10)
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Product Details</th>
                        <th class="p-3">Created Date</th>
                        <th class="p-3">Suborder ID</th>
                        <th class="p-3">Issue</th>
                        <th class="p-3">Claim Status</th>
                        <th class="p-3">Last Updates</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3">
                            <div class="font-bold text-slate-900">OXMORA "RC Stunt Car with 360 Rotation</div>
                            <div class="text-[10px] text-slate-400">SKU ID: OXM-STD-GRE-26 &bull; Free Size</div>
                        </td>
                        <td class="p-3 font-semibold text-slate-700">16 Nov'26</td>
                        <td class="p-3 font-mono text-slate-600">3417688837_1</td>
                        <td class="p-3 text-slate-700 font-medium">I have not received my Return/RTO shipment</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Open
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="text-slate-600 text-[11px]">Ticket ID: 794813081646</div>
                            <div class="text-[10px] text-slate-400">Meesho support agent will share an update within 24 hours</div>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('seller.support.index') }}" class="px-3 py-1 border border-slate-300 rounded font-bold text-xs hover:bg-slate-50">View</a>
                        </td>
                    </tr>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3">
                            <div class="font-bold text-slate-900">Dancing Cactus, Talking Cactus Toy</div>
                            <div class="text-[10px] text-slate-400">SKU ID: OXM-DNG-2026 &bull; Free Size</div>
                        </td>
                        <td class="p-3 font-semibold text-slate-700">5 Nov'26</td>
                        <td class="p-3 font-mono text-slate-600">3408919659_1</td>
                        <td class="p-3 text-slate-700 font-medium">Item/s are missing in my return</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Approved
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="text-slate-600 text-[11px]">Ticket ID: 793864845281</div>
                            <div class="text-[10px] text-emerald-600 font-semibold">Claim payment of Rs 162.84 credited on 17 Nov</div>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('seller.support.index') }}" class="px-3 py-1 border border-slate-300 rounded font-bold text-xs hover:bg-slate-50">View</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
