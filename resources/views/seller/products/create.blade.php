@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Upload Catalog - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    tab: 'bulk',
    qcTab: 'all'
}">
    <!-- Header (Screenshot 5) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Upload Catalog
        </h1>
        <div class="flex items-center space-x-3 text-xs">
            <a href="#" class="flex items-center space-x-1.5 text-slate-700 font-semibold hover:text-slate-900">
                <span class="text-rose-600">▶</span>
                <span>Learn how to upload catalogs</span>
            </a>
            <a href="{{ route('seller.support.index') }}" class="flex items-center space-x-1 text-slate-700 font-semibold hover:text-slate-900">
                <span>🎧</span>
                <span>Need Help?</span>
            </a>
        </div>
    </div>

    <!-- Product Optimizer Banner (Screenshot 5 Top) -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-pink-50 border border-orange-200/90 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-orange-500 text-white font-extrabold flex items-center justify-center text-xl shrink-0">
                📦
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                    Get upto 50% more orders + upto 10% lesser returns
                </h3>
                <p class="text-xs text-slate-600 mt-0.5">Add/edit the catalogs and improve the quality. Plus, prevent catalogs from deactivations / low visibility *T&amp;C</p>
            </div>
        </div>
        <button type="button" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold text-xs hover:bg-[#32236e] transition shadow-xs shrink-0">
            Improve products here
        </button>
    </div>

    <!-- Add Option Buttons (Screenshot 5) -->
    <div>
        <p class="text-xs text-slate-500 font-medium mb-2">Have unique products to sell? Choose from the options below</p>
        <div class="flex items-center space-x-3">
            <button @click="tab = 'bulk'" :class="tab === 'bulk' ? 'bg-[#3e2e84] text-white shadow-xs' : 'bg-white border border-slate-300 text-slate-700'" class="px-5 py-2.5 rounded-lg text-xs font-bold transition">
                Add Catalog in Bulk
            </button>
            <a href="{{ route('seller.products.bulk_upload') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                Add Single Catalog
            </a>
        </div>
    </div>

    <!-- Overview Counters (Screenshot 5 Middle) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs">
        <h4 class="text-xs font-bold text-slate-900 mb-3">Overview</h4>
        <div class="grid grid-cols-3 gap-6 max-w-lg text-xs">
            <div>
                <div class="text-[11px] text-slate-400">Total Uploads Done</div>
                <div class="text-xl font-black text-slate-900 mt-1">30</div>
            </div>
            <div>
                <div class="text-[11px] text-slate-400">Using Bulk Uploads</div>
                <div class="text-xl font-black text-slate-900 mt-1">1</div>
            </div>
            <div>
                <div class="text-[11px] text-slate-400">Using Single Uploads</div>
                <div class="text-xl font-black text-slate-900 mt-1">29</div>
            </div>
        </div>
    </div>

    <!-- Uploads List Table with QC Status (Screenshot 5 Bottom) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-3 text-xs font-bold">
            <button class="pb-3 border-b-2 border-[#53389e] text-[#53389e] font-black">
                Bulk Uploads (1)
            </button>
            <button class="pb-3 border-b-2 border-transparent text-slate-500">
                Single Uploads (29)
            </button>
        </div>

        <!-- QC Sub-filter Pills -->
        <div class="p-3.5 bg-slate-50/50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center space-x-2">
                <button class="px-3 py-1 rounded-full bg-white border border-slate-300 font-bold text-slate-800 shadow-2xs">All</button>
                <button class="px-3 py-1 rounded-full text-slate-500 font-semibold hover:bg-white">Action Required (1)</button>
                <button class="px-3 py-1 rounded-full text-slate-500 font-semibold hover:bg-white">QC in Progress (0)</button>
                <button class="px-3 py-1 rounded-full text-slate-500 font-semibold hover:bg-white">QC Error (1)</button>
                <button class="px-3 py-1 rounded-full text-slate-500 font-semibold hover:bg-white">QC Pass (0)</button>
            </div>
            <div class="relative">
                <input type="text" placeholder="Search By File Id" class="py-1 px-3 border border-slate-300 rounded-lg text-xs w-48">
            </div>
        </div>

        <!-- QC Notice -->
        <div class="p-3 bg-amber-50/70 border-b border-amber-200 text-xs text-amber-900 flex items-center space-x-2">
            <span>💡</span>
            <span>QC (Quality-Check) error products can now be fixed as they appear. Try to fix QC errors faster to speed up your catalog creation process.</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">S.no</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">File Id</th>
                        <th class="p-3">Created Date</th>
                        <th class="p-3">Rows</th>
                        <th class="p-3">QC Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-3 font-semibold text-slate-800">1</td>
                        <td class="p-3 font-semibold text-slate-800">Playing Cards</td>
                        <td class="p-3 font-mono text-slate-600">1789750357101-4591669-13558-51-UNO_DARE_Meesho_Updated</td>
                        <td class="p-3 text-slate-700">18 Sept '26 | 10:22 PM</td>
                        <td class="p-3 text-slate-400">-</td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                ▲ QC Error
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-2">
                            <button type="button" class="px-3 py-1 rounded bg-[#3e2e84] text-white font-bold text-xs hover:bg-[#32236e]">Download Error File</button>
                            <button type="button" class="px-3 py-1 rounded border border-slate-300 font-bold text-slate-700 text-xs hover:bg-slate-50">Upload New File</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
