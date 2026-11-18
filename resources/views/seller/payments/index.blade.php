@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Payments - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header with Dropdown & Search (Screenshot 7) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Payments
        </h1>

        <div class="flex items-center space-x-3 text-xs">
            <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold flex items-center space-x-1.5 shadow-xs">
                <span>Download</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="relative">
                <input type="text" placeholder="Order / Sub Order No." class="py-1.5 pl-3 pr-8 border border-slate-300 rounded-lg text-xs w-56">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Dual Payment Cards: Upcoming Payments & Completed Payments (Screenshot 7 Top) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upcoming Payments Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h3 class="text-xs font-bold text-slate-900">Upcoming Payments</h3>
                </div>
                <span class="text-xs font-bold text-[#53389e]">Next 7 days (₹793.22) &gt;</span>
            </div>

            <!-- Dates Selector Pills -->
            <div class="flex items-center space-x-4 mb-4 text-xs font-semibold">
                <div class="px-3 py-1.5 rounded-lg bg-purple-50 text-purple-900 border border-purple-200">
                    <div>23 Nov, 26</div>
                    <div class="font-extrabold">₹567.14</div>
                </div>
                <div class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">
                    <div>26 Nov, 26</div>
                    <div>₹226.08</div>
                </div>
            </div>

            <!-- Breakdown rows -->
            <div class="space-y-2 text-xs divide-y divide-slate-100">
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Orders (Sales and returns)</span>
                    <span class="font-bold text-slate-900">₹567.14</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Net platform recovery (Ads Cost)</span>
                    <span class="font-bold text-slate-900">₹0</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Program Cost</span>
                    <span class="font-bold text-slate-900">₹0</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t font-black text-slate-900 text-sm">
                    <span>Net amount</span>
                    <span>₹567</span>
                </div>
            </div>
        </div>

        <!-- Completed Payments Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="text-xs font-bold text-slate-900">Completed Payments</h3>
                </div>
                <span class="text-xs font-bold text-[#53389e]">Last 30 days (₹13.11K) &gt;</span>
            </div>

            <!-- Dates Selector Pills -->
            <div class="flex items-center space-x-4 mb-4 text-xs font-semibold">
                <div class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-900 border border-emerald-200">
                    <div>17 Nov, 26</div>
                    <div class="font-extrabold">₹162.84</div>
                </div>
                <div class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">
                    <div>16 Nov, 26</div>
                    <div>₹450.8</div>
                </div>
                <div class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-50">
                    <div>12 Nov, 26</div>
                    <div>₹240.61</div>
                </div>
            </div>

            <!-- Breakdown rows -->
            <div class="space-y-2 text-xs divide-y divide-slate-100">
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Orders (Sales and returns)</span>
                    <span class="font-bold text-slate-900">₹162.84</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Net platform recovery (Ads Cost)</span>
                    <span class="font-bold text-slate-900">₹0</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-slate-600">Program Benefits</span>
                    <span class="font-bold text-slate-900">₹0</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t font-black text-slate-900 text-sm">
                    <span class="text-xs text-slate-400 font-normal">NEFT ID: AXISCN1536245573</span>
                    <span>₹162</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Unscheduled Payments Banner -->
    <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs flex items-center justify-between text-xs">
        <div class="flex items-center space-x-3">
            <span class="text-2xl">🚚</span>
            <div>
                <div class="font-bold text-slate-900">Unscheduled Payments</div>
                <div class="text-slate-500">Payments expected from your shipped orders. Once delivered, we will automatically move it to your upcoming payments.</div>
            </div>
        </div>
        <button type="button" class="px-4 py-1.5 rounded-lg border border-slate-300 font-bold text-slate-700 hover:bg-slate-50">View Details</button>
    </div>

    <!-- Payments Over Time Graph Box (Screenshot 7 Middle) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-slate-900">Payments over time</h3>
            <div class="flex items-center space-x-4 text-xs font-semibold text-slate-600">
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 bg-indigo-500 rounded-sm"></span> <span>Payments to Date</span></span>
                <span class="flex items-center space-x-1"><span class="w-2.5 h-2.5 bg-slate-300 rounded-sm"></span> <span>Outstanding Payment</span></span>
            </div>
        </div>
        <div class="h-44 relative">
            <canvas id="meeshoPaymentChart"></canvas>
        </div>
        <p class="text-[10px] text-slate-400 mt-2">The graph shows daily view of your 30 days payments</p>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('meeshoPaymentChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['20 Oct', '25 Oct', '02 Nov', '09 Nov', '16 Nov', '23 Nov'],
                    datasets: [{
                        label: 'Payments',
                        data: [1200, 400, 300, 450, 700, 162],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.05)',
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }
    });
</script>
@endpush
@endsection
