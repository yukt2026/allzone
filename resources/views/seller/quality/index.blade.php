@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Dispatch Performance - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header with Breadcrumb & Ticket button (Screenshot 3) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center space-x-2">
                <span>Dispatch Performance</span>
                <span class="text-xs font-normal text-slate-400">(16 Oct 26 - 16 Nov 26)</span>
            </h1>
        </div>
        <div class="flex items-center space-x-3 text-xs">
            <span class="text-slate-500">Have any concerns?</span>
            <a href="{{ route('seller.support.create') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-800 font-bold hover:bg-slate-50 transition">
                Raise ticket
            </a>
        </div>
    </div>

    <!-- Main Grid: Dispatch Health Alert Banner + Fast Delivery Program -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Dispatch Health Bar & Insights (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Alert Card (Screenshot 3) -->
            <div class="p-5 rounded-xl bg-orange-50/70 border border-orange-200/90 text-xs shadow-2xs">
                <div class="flex items-start space-x-3 mb-4">
                    <span class="text-2xl text-amber-500">⚠️</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            94% dispatch health. Catalog visibility at risk
                        </h3>
                        <div class="flex items-center space-x-4 text-slate-600 mt-1">
                            <span>On time dispatch: <strong>30</strong></span>
                            <span>&bull;</span>
                            <span>Total dispatched orders: <strong>32</strong></span>
                            <span>&bull;</span>
                            <span>Dispatch health: <strong>30/32</strong></span>
                        </div>
                    </div>
                </div>

                <!-- 3 Phase Progress Bar -->
                <div class="grid grid-cols-3 gap-1.5 h-2 rounded-full overflow-hidden my-3">
                    <div class="bg-emerald-500 h-full rounded-l-full" title="Good dispatch health"></div>
                    <div class="bg-amber-400 h-full" title="Catalogs at risk"></div>
                    <div class="bg-rose-400 h-full rounded-r-full" title="Catalogs blocked"></div>
                </div>

                <div class="grid grid-cols-3 text-[11px] font-semibold text-center pt-1">
                    <div class="text-emerald-700 bg-emerald-50/80 py-1 rounded">
                        <div>Good dispatch health</div>
                        <div class="text-[10px] text-slate-500">100% - 95%</div>
                    </div>
                    <div class="text-amber-800 bg-amber-50/80 py-1 rounded">
                        <div>Catalogs at risk</div>
                        <div class="text-[10px] text-slate-500">95% - 75%</div>
                    </div>
                    <div class="text-rose-800 bg-rose-50/80 py-1 rounded">
                        <div>Catalogs blocked</div>
                        <div class="text-[10px] text-slate-500">Less than 75%</div>
                    </div>
                </div>
            </div>

            <!-- Dispatch Insights Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-900">Dispatch insights</h3>
                    <a href="#" class="text-xs font-bold text-[#53389e] hover:underline">View all</a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                    <div class="flex items-center space-x-2">
                        <span class="text-rose-500 font-bold">▲</span>
                        <span class="font-bold text-slate-900">1 catalog(s) at risk of blocking</span>
                        <span class="text-slate-400 text-[11px]">Improve dispatch health to avoid blocking</span>
                    </div>
                    <a href="{{ route('seller.inventory.index') }}" class="font-bold text-[#53389e] hover:underline">View</a>
                </div>
            </div>
        </div>

        <!-- Right: Fast Delivery Unlock Program (Screenshot 3 Right) -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded bg-amber-100 text-[10px] font-black text-amber-900">FAST</span>
                <span class="text-xs font-bold text-slate-900">Improve dispatch health to &gt;95% to unlock FAST delivery</span>
            </div>
            <p class="text-xs text-slate-500">
                Fast orders are delivered in 3-4 days (on eligible lanes)
            </p>
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 flex items-center space-x-1.5">
                <span>⏱</span>
                <span>Orders before 11 AM must be packed by 12 PM</span>
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-2 text-xs">
                <div class="font-bold text-slate-800 text-[11px] uppercase tracking-wider">Program benefits</div>
                <div class="flex items-center space-x-2 text-emerald-700 font-semibold">
                    <span>↗</span>
                    <span>Up to 7% more Order growth</span>
                </div>
                <div class="flex items-center space-x-2 text-emerald-700 font-semibold">
                    <span>↘</span>
                    <span>Up to 3% Lesser RTO rate</span>
                </div>
                <div class="flex items-center space-x-2 text-emerald-700 font-semibold">
                    <span>↘</span>
                    <span>Up to 3% Lesser user cancellation</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Catalogs Blocked / At Risk Tabs (Screenshot 3 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 pt-3">
            <div class="flex items-center space-x-6 text-xs font-bold">
                <a href="#" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Catalogs Blocked
                </a>
                <a href="#" class="pb-3 border-b-2 border-[#53389e] text-[#53389e] font-black">
                    At risk of blocking
                </a>
            </div>
            <a href="{{ route('seller.inventory.index') }}" class="text-xs font-semibold text-[#53389e] hover:underline pb-2 flex items-center space-x-1">
                <span>ⓘ</span>
                <span>View blocked catalogs in Inventory</span>
            </a>
        </div>

        <div class="p-4 border-b border-slate-100 flex items-center space-x-2 text-xs">
            <span class="text-slate-500 font-medium">Sort by:</span>
            <select class="py-1 px-3 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
                <option>Dispatch health - Low to High</option>
                <option>Dispatch health - High to Low</option>
            </select>
        </div>

        <!-- Empty state or healthy catalogs -->
        <div class="py-14 text-center flex flex-col items-center justify-center">
            <div class="w-16 h-16 grid grid-cols-2 gap-1.5 mb-3 text-slate-300">
                <div class="border-2 border-slate-300 rounded"></div>
                <div class="border-2 border-slate-300 rounded"></div>
                <div class="border-2 border-slate-300 rounded"></div>
                <div class="border-2 border-slate-300 rounded-full"></div>
            </div>
            <h4 class="text-sm font-bold text-slate-800">Great! Your catalogs are healthy</h4>
            <p class="text-xs text-slate-400 mt-0.5">Catalogs that are at risk of being blocked or are already blocked will appear here.</p>
        </div>
    </div>
</div>
@endsection
