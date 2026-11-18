@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Product Quality - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header (Screenshot 6) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Product Quality
        </h1>
        <a href="#" class="flex items-center space-x-1.5 text-xs text-slate-700 font-semibold hover:text-slate-900">
            <span class="text-rose-600">▶</span>
            <span>Learn about Quality</span>
        </a>
    </div>

    <!-- Verified Ratings Banner (Screenshot 6 Top) -->
    <div class="p-3 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-center space-x-2 text-xs text-emerald-950 font-medium">
        <span>ⓘ Quality score updated. Based on ratings from</span>
        <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white font-bold text-[10px] flex items-center space-x-1">
            <span>🛡️</span>
            <span>Trusted and verified customers</span>
        </span>
    </div>

    <!-- Quality Score & Feedback Cards Grid (Screenshot 6 Middle) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Quality Score Meter Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-900 mb-1">Quality Score ⓘ</h3>
                <div class="text-[11px] text-slate-400">1 and 2 star ratings <strong>0</strong> &bull; Total Ratings <strong>0</strong> &bull; Quality Score = <strong>N/A</strong></div>

                <div class="py-8 text-center flex flex-col items-center justify-center">
                    <span class="text-3xl mb-1">📊</span>
                    <div class="font-bold text-slate-800 text-xs">No score available</div>
                    <div class="text-[10px] text-slate-400">Since you don't have enough ratings.</div>
                </div>
            </div>

            <!-- 4-Level Quality Category Scales (Screenshot 6) -->
            <div class="grid grid-cols-4 gap-1 text-center text-[10px] font-bold pt-3 border-t border-slate-100">
                <div class="p-1.5 rounded bg-emerald-50 text-emerald-800">
                    <div>1.6X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Green (0 - 15%)</div>
                </div>
                <div class="p-1.5 rounded bg-amber-50 text-amber-800">
                    <div>1X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Yellow (15 - 22%)</div>
                </div>
                <div class="p-1.5 rounded bg-rose-50 text-rose-800">
                    <div>0.6X VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Red (22 - 25%)</div>
                </div>
                <div class="p-1.5 rounded bg-red-100 text-red-900">
                    <div>NO VIEWS</div>
                    <div class="text-[9px] text-slate-500 font-normal">Blocked (&gt; 25%)</div>
                </div>
            </div>
        </div>

        <!-- Top Customer Feedback Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
            <h3 class="text-xs font-bold text-slate-900">Top Customer Feedback</h3>
            <div class="py-12 text-center flex flex-col items-center justify-center">
                <span class="text-3xl text-slate-300 mb-2">📋</span>
                <div class="font-bold text-slate-800 text-xs">No feedback available</div>
                <div class="text-[10px] text-slate-400">Great work! Continue listing good quality products</div>
            </div>
            <div></div>
        </div>
    </div>

    <!-- Quality Recommendations Table (Screenshot 6 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <h3 class="font-bold text-slate-900">Quality Recommendations</h3>
            <div class="relative">
                <input type="text" placeholder="Search By Style / SKU / Catalog ID" class="py-1 pl-3 pr-8 border border-slate-300 rounded-lg text-xs w-60">
            </div>
        </div>

        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-2 text-xs font-bold">
            <button class="pb-3 border-b-2 border-transparent text-slate-500">Blocking Soon (0)</button>
            <button class="pb-3 border-b-2 border-[#53389e] text-[#53389e] font-black">Action Pending (0)</button>
            <button class="pb-3 border-b-2 border-transparent text-slate-500">Fixed (0)</button>
        </div>

        <div class="py-16 text-center flex flex-col items-center justify-center">
            <div class="w-16 h-16 mb-2 text-slate-300">
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h4 class="text-sm font-bold text-slate-800">No products as of now</h4>
            <p class="text-xs text-slate-400 mt-0.5">Products that have poor quality or listing issues will appear here.</p>
        </div>
    </div>
</div>
@endsection
