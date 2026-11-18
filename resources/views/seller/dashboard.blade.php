@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    activeTab: 'daily',
    flashModal: false,
}">
    <!-- Top Greeting Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                Welcome back, {{ $seller?->business_name ?? 'DREESTI' }}
            </h1>
            <p class="text-xs text-slate-500">
                Manage and grow your business with Meesho
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('seller.products.create') }}" class="px-4 py-2 rounded-lg bg-[#53389e] hover:bg-[#432c81] text-white text-xs font-semibold shadow-xs transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    <!-- Policy Update Banner (Screenshot 1) -->
    <div class="p-3.5 rounded-xl bg-orange-50/70 border border-orange-200/80 flex items-center justify-between text-xs text-slate-700">
        <div class="flex items-center space-x-2.5">
            <span class="text-base">📢</span>
            <span>
                <strong class="font-semibold text-slate-900">Upcoming Policy Update:</strong> Next Day Dispatch is becoming the new platform standard for all orders.
            </span>
        </div>
        <a href="{{ route('seller.orders.dispatch_performance') }}" class="font-semibold text-indigo-600 hover:text-indigo-800 underline ml-2 shrink-0">
            Know more
        </a>
    </div>

    <!-- To Do List Action Cards (Screenshot 1) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex items-center space-x-2 mb-4">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h2 class="text-sm font-bold text-slate-800">To do list</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Pending Orders -->
            <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}" class="flex items-center justify-between p-3.5 rounded-lg border border-slate-150 hover:border-indigo-400 bg-slate-50/50 hover:bg-white transition group">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-600 font-medium">Pending Orders</div>
                        <div class="text-base font-extrabold text-slate-900 group-hover:text-indigo-600">{{ $actionOrders->count() ?? 0 }} &rsaquo;</div>
                    </div>
                </div>
            </a>

            <!-- Download Labels -->
            <a href="{{ route('seller.orders.index', ['status' => 'ready']) }}" class="flex items-center justify-between p-3.5 rounded-lg border border-slate-150 hover:border-indigo-400 bg-slate-50/50 hover:bg-white transition group">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-50 border border-sky-200/80 flex items-center justify-center text-sky-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-600 font-medium">Download Labels</div>
                        <div class="text-base font-extrabold text-slate-900 group-hover:text-indigo-600">0 &rsaquo;</div>
                    </div>
                </div>
            </a>

            <!-- Out of Stock -->
            <a href="{{ route('seller.inventory.index') }}" class="flex items-center justify-between p-3.5 rounded-lg border border-slate-150 hover:border-rose-400 bg-slate-50/50 hover:bg-white transition group">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-200/80 flex items-center justify-center text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-600 font-medium">Out of Stock</div>
                        <div class="text-base font-extrabold text-rose-600">5 &rsaquo;</div>
                    </div>
                </div>
            </a>

            <!-- Low Stock -->
            <a href="{{ route('seller.inventory.index') }}" class="flex items-center justify-between p-3.5 rounded-lg border border-slate-150 hover:border-amber-400 bg-slate-50/50 hover:bg-white transition group">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-600 font-medium">Low Stock</div>
                        <div class="text-base font-extrabold text-slate-900 group-hover:text-indigo-600">0 &rsaquo;</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Main Grid: Business Insights Graph + Right Promotion Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Business Insights (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        <h2 class="text-sm font-bold text-slate-900">Business Insights</h2>
                    </div>
                    <div class="text-xs font-semibold text-slate-600 bg-slate-100 px-3 py-1 rounded-md">
                        Daily
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="md:col-span-2 relative h-48 sm:h-56">
                        <canvas id="meeshoInsightsChart"></canvas>
                    </div>
                    <div class="flex flex-col justify-center space-y-4 border-t md:border-t-0 md:border-l border-slate-100 md:pl-5">
                        <div>
                            <div class="text-[11px] text-slate-500 font-medium">Views (17 Nov)</div>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-black text-slate-900">496</span>
                                <span class="text-xs font-bold text-emerald-600 flex items-center">&blacktriangle; 2655.56%</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] text-slate-500 font-medium">Orders (17 Nov)</div>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-2xl font-black text-slate-900">0</span>
                                <span class="text-xs font-bold text-rose-500 flex items-center">&blacktriangledown; 100.00%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100">
                <a href="{{ route('seller.stores.reports') }}" class="inline-block px-4 py-2 rounded-lg border border-slate-200 text-xs font-bold text-[#53389e] hover:bg-slate-50 transition">
                    View More Details
                </a>
            </div>
        </div>

        <!-- Right: Maha Indian Savings Sale / Flash Discount Promotion -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                    <span class="flex items-center space-x-1 font-mono text-[11px] font-semibold">
                        <span>⏱</span>
                        <span>2h : 21m : 23s</span>
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-rose-50 text-[10px] font-black text-rose-600 border border-rose-200">
                        MAHA INDIAN SAVINGS SALE
                    </span>
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">
                    Add timer on Nov &amp; Dec Sale events! Get extra 40% growth*
                </h3>

                <!-- Small Sparkline / Curve -->
                <div class="py-3 flex items-center justify-between text-xs font-bold text-slate-600">
                    <div>
                        <div class="text-slate-400 font-normal text-[10px]">current growth</div>
                        <div>220%</div>
                    </div>
                    <div class="text-emerald-600 text-right">
                        <div class="text-slate-400 font-normal text-[10px]">flash growth</div>
                        <div>260% (+40%)</div>
                    </div>
                </div>

                <a href="{{ route('seller.pricing.index') }}" class="w-full block text-center py-2.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold transition shadow-xs">
                    Add flash discount
                </a>
                <p class="text-[10px] text-slate-400 text-center mt-2">
                    *Based on internal projections. Actual figures may vary.
                </p>
            </div>

            <!-- Important Announcements -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
                <h4 class="text-xs font-bold text-slate-800 mb-2">Important Announcements</h4>
                <a href="#" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 text-xs text-slate-700 transition">
                    <div class="flex items-center space-x-2 truncate pr-2">
                        <span class="text-base shrink-0">📢</span>
                        <span class="truncate font-medium">Pay Later Lite is Live - Enable NOW!! Grow Your Business...</span>
                    </div>
                    <span class="text-slate-400">&rsaquo;</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Dispatch Performance & Insights (Screenshot 1 & 3) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex items-center space-x-2 mb-4">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h2 class="text-sm font-bold text-slate-900">Dispatch Performance &amp; Insights</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 rounded-xl border border-slate-200/80 bg-slate-50/30">
            <!-- Health Rating Bar -->
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="text-2xl font-black text-slate-900">94%</span>
                    <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        dispatch health – catalogs at risk
                    </span>
                </div>

                <!-- 3-Segment Progress bar -->
                <div class="grid grid-cols-3 gap-1.5 h-2 rounded-full overflow-hidden my-3">
                    <div class="bg-emerald-500 h-full rounded-l-full" title="Good: 100% - 95%"></div>
                    <div class="bg-amber-400 h-full" title="At Risk: 95% - 75%"></div>
                    <div class="bg-rose-400 h-full rounded-r-full" title="Blocked: Less than 75%"></div>
                </div>

                <div class="grid grid-cols-3 text-[10px] text-slate-500 font-semibold pt-1">
                    <div>
                        <div class="text-emerald-700">Good</div>
                        <div>100% - 95%</div>
                    </div>
                    <div>
                        <div class="text-amber-700">At Risk</div>
                        <div>95% - 75%</div>
                    </div>
                    <div>
                        <div class="text-rose-700">Blocked</div>
                        <div>Less than 75%</div>
                    </div>
                </div>
            </div>

            <!-- Catalogs to Review -->
            <div class="flex flex-col justify-between border-t md:border-t-0 md:border-l border-slate-200/80 md:pl-6 pt-3 md:pt-0">
                <div>
                    <h3 class="text-xs font-bold text-slate-800 mb-2">Catalogs to Review</h3>
                    <div class="flex items-center space-x-6 text-xs font-medium text-slate-600">
                        <a href="{{ route('seller.quality.index') }}" class="hover:text-slate-900 flex items-center space-x-1">
                            <span class="text-slate-400">&Delta;</span>
                            <span>0 Catalog(s) blocked</span>
                            <span class="text-slate-400">&rsaquo;</span>
                        </a>
                        <a href="{{ route('seller.quality.index') }}" class="text-amber-600 hover:text-amber-700 flex items-center space-x-1 font-semibold">
                            <span>&Delta;</span>
                            <span>1 Catalog(s) at risk</span>
                            <span class="text-slate-400">&rsaquo;</span>
                        </a>
                    </div>
                </div>

                <div class="pt-3">
                    <a href="{{ route('seller.orders.dispatch_performance') }}" class="inline-block px-4 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                        View More
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotional Ads Banner (Screenshot 1 Bottom) -->
    <div class="max-w-md bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="p-3 bg-amber-50/60 rounded-lg border border-amber-100 flex items-center space-x-2 text-xs font-bold text-amber-900 mb-3">
            <span>✨ Get upto <strong>3X more orders</strong> for the upcoming sales with Ads</span>
        </div>
        <h4 class="text-sm font-extrabold text-slate-900 mb-1">Go Bigger this sale season.</h4>
        <p class="text-xs text-slate-500 mb-4">
            Drive more visibility for your products and get more orders with Ads.
        </p>
        <a href="{{ route('seller.products.index') }}" class="block text-center py-2.5 px-4 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold transition shadow-xs">
            Participate in ads now!
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('meeshoInsightsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['11th', '12th', '13th', '14th', '15th', '16th', '17th Nov'],
                    datasets: [{
                        label: 'Views',
                        data: [1500, 10, 15, 380, 390, 350, 496],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#6366f1',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.6)' },
                            ticks: { font: { size: 10 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
