@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Catalog Uploads & Product Management - ALLZONE'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    activeTab: '{{ $tab ?? 'overview' }}',
    filter: '{{ $filter ?? 'all' }}',
    productType: 'simple',
    simpleMrp: 0,
    simplePrice: 0,
    simpleCost: 0,
    mediaFiles: [],
    skuChecking: false,
    skuAvailable: null,
    skuFeedback: '',
    specifications: [
        { group: 'General', key: 'Model Name', value: '' },
        { group: 'General', key: 'In The Box', value: '' }
    ],
    variants: [
        { name: 'Standard Edition', sku: '', mrp: '', selling_price: '', stock: 10, weight_grams: 500, length: 15, width: 10, height: 5 }
    ],
    checkSku(sku) {
        if (!sku || sku.trim().length < 3) {
            this.skuAvailable = null;
            this.skuFeedback = '';
            return;
        }
        this.skuChecking = true;
        fetch('{{ route('seller.products.check_sku') }}?sku=' + encodeURIComponent(sku))
            .then(res => res.json())
            .then(data => {
                this.skuChecking = false;
                this.skuAvailable = data.available;
                this.skuFeedback = data.message;
            })
            .catch(() => {
                this.skuChecking = false;
            });
    },
    addSpec() {
        this.specifications.push({ group: 'General', key: '', value: '' });
    },
    removeSpec(idx) {
        this.specifications.splice(idx, 1);
    },
    addVariant() {
        this.variants.push({
            name: 'Variant ' + (this.variants.length + 1),
            sku: '',
            mrp: '',
            selling_price: '',
            stock: 10,
            weight_grams: 500,
            length: 15,
            width: 10,
            height: 5
        });
    },
    removeVariant(idx) {
        if (this.variants.length > 1) {
            this.variants.splice(idx, 1);
        }
    },
    handleMediaSelect(e) {
        const files = Array.from(e.target.files || []);
        files.forEach(file => {
            this.mediaFiles.push({
                file: file,
                name: file.name,
                url: URL.createObjectURL(file),
                size: (file.size / 1024 / 1024).toFixed(2) + ' MB'
            });
        });
    },
    removeMedia(idx) {
        if (this.mediaFiles[idx] && this.mediaFiles[idx].url) {
            URL.revokeObjectURL(this.mediaFiles[idx].url);
        }
        this.mediaFiles.splice(idx, 1);
    }
}">

    <!-- Page Header & Main Tab Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-emerald-700 mb-1">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Supplier Studio &bull; Catalog Management</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Catalog Uploads &amp; Products
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Upload single products, bulk CSV catalogs, track QC moderation status, and manage inventory listings.
            </p>
        </div>

        <!-- Top Navigation Pills -->
        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-bold">
            <a 
                href="{{ route('seller.products.create', ['tab' => 'overview']) }}"
                @click.prevent="activeTab = 'overview'"
                :class="activeTab === 'overview' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
                class="px-4 py-2 rounded-lg transition flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Overview &amp; History</span>
            </a>
            <a 
                href="{{ route('seller.products.create', ['tab' => 'single']) }}"
                @click.prevent="activeTab = 'single'"
                :class="activeTab === 'single' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
                class="px-4 py-2 rounded-lg transition flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Single Product</span>
            </a>
            <a 
                href="{{ route('seller.products.create', ['tab' => 'bulk']) }}"
                @click.prevent="activeTab = 'bulk'"
                :class="activeTab === 'bulk' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
                class="px-4 py-2 rounded-lg transition flex items-center space-x-1.5"
            >
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span>Add in Bulk (CSV)</span>
            </a>
        </div>
    </div>

    <!-- Alert / Validation Banner Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ session('info') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-blue-600 hover:text-blue-900 font-bold">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-2xs">
            <div class="font-bold mb-1 flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Please fix the following issues before submitting:</span>
            </div>
            <ul class="list-disc pl-6 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- TAB 1: OVERVIEW & UPLOAD HISTORY                                          -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">

        <!-- Quality Standards Banner -->
        <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-slate-50 border border-emerald-200/90 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white font-black flex items-center justify-center text-xl shrink-0 shadow-xs">
                    📦
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">
                        ALLZONE Catalog Quality Standards
                    </h3>
                    <p class="text-xs text-slate-600 mt-0.5">
                        High-resolution front images, complete technical specifications, and accurate package dimensions increase customer conversion and reduce return risks.
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <button type="button" @click="activeTab = 'single'" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition shadow-xs">
                    + Add Single Product
                </button>
                <button type="button" @click="activeTab = 'bulk'" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50 transition shadow-2xs">
                    Bulk CSV Upload
                </button>
            </div>
        </div>

        <!-- 7 Overview KPI Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Products</span>
                <span class="text-2xl font-black text-slate-900 mt-1 block">{{ number_format($totalProducts) }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Catalog records</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-emerald-200 shadow-2xs bg-emerald-50/20">
                <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider block">QC Passed</span>
                <span class="text-2xl font-black text-emerald-700 mt-1 block">{{ number_format($activeCount) }}</span>
                <span class="text-[10px] text-emerald-600 mt-0.5 block">Live on marketplace</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-amber-200 shadow-2xs bg-amber-50/20">
                <span class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider block">In Review</span>
                <span class="text-2xl font-black text-amber-700 mt-1 block">{{ number_format($pendingCount) }}</span>
                <span class="text-[10px] text-amber-600 mt-0.5 block">Pending moderation</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-rose-200 shadow-2xs bg-rose-50/20">
                <span class="text-[11px] font-semibold text-rose-700 uppercase tracking-wider block">QC Error</span>
                <span class="text-2xl font-black text-rose-700 mt-1 block">{{ number_format($rejectedCount) }}</span>
                <span class="text-[10px] text-rose-600 mt-0.5 block">Action required</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Drafts</span>
                <span class="text-2xl font-black text-slate-700 mt-1 block">{{ number_format($draftCount) }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Saved locally</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-indigo-200 shadow-2xs bg-indigo-50/20">
                <span class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider block">Bulk Batches</span>
                <span class="text-2xl font-black text-indigo-700 mt-1 block">{{ number_format($bulkUploadsCount) }}</span>
                <span class="text-[10px] text-indigo-600 mt-0.5 block">CSV imports</span>
            </div>
            <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Single Uploads</span>
                <span class="text-2xl font-black text-slate-800 mt-1 block">{{ number_format($singleUploadsCount) }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Wizard created</span>
            </div>
        </div>

        <!-- History & Status Filter Tabs -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex flex-wrap items-center gap-1.5 font-bold">
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'all']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'all' ? 'bg-white text-slate-900 border border-slate-300 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        All Catalog Uploads ({{ $totalProducts }})
                    </a>
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'active']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'active' ? 'bg-white text-emerald-800 border border-emerald-300 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        ✔ QC Pass ({{ $activeCount }})
                    </a>
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'pending']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'pending' ? 'bg-white text-amber-800 border border-amber-300 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        ⏳ In Review ({{ $pendingCount }})
                    </a>
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'rejected']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'rejected' ? 'bg-white text-rose-800 border border-rose-300 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        ▲ QC Error ({{ $rejectedCount }})
                    </a>
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'draft']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'draft' ? 'bg-white text-slate-900 border border-slate-300 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Drafts ({{ $draftCount }})
                    </a>
                    <a 
                        href="{{ route('seller.products.create', ['tab' => 'overview', 'filter' => 'bulk_imports']) }}" 
                        class="px-3 py-1.5 rounded-lg transition {{ $filter === 'bulk_imports' ? 'bg-indigo-600 text-white shadow-2xs font-extrabold' : 'text-indigo-700 hover:text-indigo-900 bg-indigo-50 border border-indigo-200' }}"
                    >
                        📁 Bulk CSV Batches ({{ $bulkUploadsCount }})
                    </a>
                </div>

                <div class="text-[11px] text-slate-500">
                    Showing real authenticated catalog records
                </div>
            </div>

            <!-- Content when viewing Bulk Import Batches -->
            @if($filter === 'bulk_imports')
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                            <tr>
                                <th class="p-3">Batch ID</th>
                                <th class="p-3">File Name</th>
                                <th class="p-3">Upload Date</th>
                                <th class="p-3">Total Rows</th>
                                <th class="p-3">Imported</th>
                                <th class="p-3">Failed</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($bulkImports as $import)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3 font-mono font-bold text-slate-700">#{{ $import->id }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ $import->file_name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $import->store?->name ?? 'Primary Store' }}</div>
                                    </td>
                                    <td class="p-3 text-slate-600">{{ $import->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="p-3 font-bold text-slate-800">{{ $import->total_rows }}</td>
                                    <td class="p-3 font-bold text-emerald-600">{{ $import->imported_rows }}</td>
                                    <td class="p-3 font-bold {{ $import->failed_rows > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                        {{ $import->failed_rows }}
                                    </td>
                                    <td class="p-3">
                                        @if($import->status === 'completed')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✔ Completed
                                            </span>
                                        @elseif($import->status === 'processing')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                ⏳ Processing
                                            </span>
                                        @elseif($import->status === 'failed')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                ✖ Failed
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right space-x-1.5">
                                        @if($import->failed_rows > 0)
                                            <a 
                                                href="{{ route('seller.products.bulk_errors', $import->id) }}"
                                                class="px-2.5 py-1 rounded bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px] hover:bg-rose-100 transition inline-flex items-center space-x-1"
                                            >
                                                <span>Download Errors</span>
                                            </a>
                                        @endif
                                        @if($import->status === 'pending')
                                            <a 
                                                href="{{ route('seller.products.create', ['tab' => 'bulk', 'preview_id' => $import->id]) }}"
                                                class="px-2.5 py-1 rounded bg-indigo-600 text-white font-bold text-[11px] hover:bg-indigo-700 transition inline-block"
                                            >
                                                Review &amp; Import
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center text-slate-400">
                                        <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-slate-100 flex items-center justify-center text-xl text-slate-500">
                                            📁
                                        </div>
                                        <div class="font-bold text-slate-700">No bulk catalog batches yet</div>
                                        <div class="text-xs text-slate-400 mt-0.5">Upload a CSV template to bulk import dozens of products at once.</div>
                                        <button type="button" @click="activeTab = 'bulk'" class="mt-3 px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition">
                                            Start Bulk Upload
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($bulkImports->hasPages())
                    <div class="p-3 border-t border-slate-100">
                        {{ $bulkImports->links() }}
                    </div>
                @endif
            @else
                <!-- Content when viewing Products Upload History -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                            <tr>
                                <th class="p-3">S.no</th>
                                <th class="p-3">Category</th>
                                <th class="p-3">Product Title &amp; SKU</th>
                                <th class="p-3">Upload Date</th>
                                <th class="p-3">Variants</th>
                                <th class="p-3">QC Status</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentUploads as $idx => $prod)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3 font-semibold text-slate-700">{{ $recentUploads->firstItem() + $idx }}</td>
                                    <td class="p-3">
                                        <span class="font-bold text-slate-800">{{ $prod->category?->name ?? 'Electronics' }}</span>
                                        @if($prod->brand)
                                            <span class="block text-[10px] text-slate-400">{{ $prod->brand->name }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ $prod->title }}</div>
                                        <div class="font-mono text-[10px] text-slate-400">
                                            {{ $prod->defaultVariant?->sku ?? $prod->slug }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-slate-600">{{ $prod->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="p-3 font-bold text-slate-700">{{ $prod->variants->count() }}</td>
                                    <td class="p-3">
                                        @if($prod->status->value === 'active')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✔ QC Pass
                                            </span>
                                        @elseif($prod->status->value === 'pending_review')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                ⏳ In Review
                                            </span>
                                        @elseif($prod->status->value === 'rejected')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $prod->rejection_reason }}">
                                                ▲ QC Error
                                            </span>
                                            @if($prod->rejection_reason)
                                                <div class="text-[10px] text-rose-600 mt-0.5 italic max-w-xs truncate">
                                                    {{ $prod->rejection_reason }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                                Draft
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right space-x-1.5">
                                        <a href="{{ route('seller.products.edit', $prod->id) }}" class="px-3 py-1 rounded bg-slate-800 text-white font-bold text-xs hover:bg-slate-900 transition inline-block">
                                            Edit Product
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-12 text-center text-slate-400">
                                        <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-slate-100 flex items-center justify-center text-xl text-slate-500">
                                            📦
                                        </div>
                                        <div class="font-bold text-slate-700">No catalog uploads found</div>
                                        <div class="text-xs text-slate-400 mt-0.5">Start listing your electronics products to expand your store catalog.</div>
                                        <div class="mt-4 flex items-center justify-center space-x-3">
                                            <button type="button" @click="activeTab = 'single'" class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition">
                                                + Add Single Product
                                            </button>
                                            <button type="button" @click="activeTab = 'bulk'" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                                                Bulk CSV Upload
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recentUploads->hasPages())
                    <div class="p-3 border-t border-slate-100">
                        {{ $recentUploads->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: ADD SINGLE PRODUCT (COMPLETE WIZARD & ATTRIBUTE FORM)              -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'single'" x-cloak class="space-y-6">
        <form 
            id="single-product-form"
            method="POST" 
            action="{{ route('seller.products.store') }}" 
            enctype="multipart/form-data"
            class="space-y-6"
        >
            @csrf
            <input type="hidden" name="submit_action" id="submit_action_input" value="submit_review">

            <!-- Section 1: Basic Information -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center font-bold">1</span>
                        <span>Basic Product Details</span>
                    </h3>
                    <span class="text-xs text-slate-400">* Required for QC clearance</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Product Title *</label>
                        <input 
                            type="text" 
                            name="title" 
                            required 
                            placeholder="e.g. Sony Bravia 55 Inch 4K Ultra HD Smart LED TV (KD-55X74L)" 
                            class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium"
                            value="{{ old('title') }}"
                        >
                        <span class="text-[10px] text-slate-400 mt-1 block">ALLZONE is strictly Electronics &amp; Home Appliances. Non-electronics items are prohibited.</span>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Category &amp; Department *</label>
                        <select name="category_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium">
                            <option value="">Select Electronics Category...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat['id'] }}" {{ old('category_id') == $cat['id'] ? 'selected' : '' }}>
                                    {!! $cat['path'] !!}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Brand -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Brand Name</label>
                        <select name="brand_id" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium">
                            <option value="">Select or leave blank for Generic/OEM</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ old('brand_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }} {{ $b->is_verified ? '✔ Verified' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Short Description -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Short Summary / Highlights</label>
                        <input 
                            type="text" 
                            name="short_desc" 
                            placeholder="e.g. 4K HDR processor, Dolby Atmos audio, 3 HDMI ports, 1 Year Manufacturer Warranty" 
                            class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium"
                            value="{{ old('short_desc') }}"
                        >
                    </div>

                    <!-- Full Description -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Detailed Description &amp; Features</label>
                        <textarea 
                            name="description" 
                            rows="4" 
                            placeholder="Provide full technical specifications, features, warranty details, power consumption, and package contents..."
                            class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium"
                        >{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 2: Product Type & Pricing Matrix -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center font-bold">2</span>
                        <span>Product Type, Pricing &amp; Inventory</span>
                    </h3>

                    <!-- Simple vs Variable Toggle -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-lg text-xs font-bold">
                        <button 
                            type="button" 
                            @click="productType = 'simple'" 
                            :class="productType === 'simple' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1 rounded transition"
                        >
                            Simple Product
                        </button>
                        <button 
                            type="button" 
                            @click="productType = 'variable'" 
                            :class="productType === 'variable' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-3 py-1 rounded transition"
                        >
                            Variable (Multiple Variants)
                        </button>
                    </div>
                </div>
                <input type="hidden" name="product_type" :value="productType">

                <!-- SIMPLE PRODUCT FORM -->
                <div x-show="productType === 'simple'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- SKU -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">SKU (Stock Keeping Unit) *</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    name="simple_sku" 
                                    @input.debounce.400ms="checkSku($event.target.value)"
                                    placeholder="e.g. SONY-55-4K-LED" 
                                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 uppercase font-mono font-bold"
                                    value="{{ old('simple_sku') }}"
                                >
                                <span x-show="skuChecking" class="absolute right-3 top-2.5 text-[10px] text-slate-400">Checking...</span>
                            </div>
                            <span x-show="skuFeedback" :class="skuAvailable ? 'text-emerald-600' : 'text-rose-600'" class="text-[10px] mt-1 block font-bold" x-text="skuFeedback"></span>
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Barcode / EAN / UPC</label>
                            <input 
                                type="text" 
                                name="simple_barcode" 
                                placeholder="e.g. 8901234567890" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-mono"
                                value="{{ old('simple_barcode') }}"
                            >
                        </div>

                        <!-- Initial Stock -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Initial Stock Quantity *</label>
                            <input 
                                type="number" 
                                name="simple_stock" 
                                min="0" 
                                placeholder="10" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold"
                                value="{{ old('simple_stock', 10) }}"
                            >
                        </div>
                    </div>

                    <!-- Pricing & Live Margin Preview -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">MRP (₹) *</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                name="simple_mrp" 
                                x-model="simpleMrp" 
                                placeholder="59999.00" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold"
                                value="{{ old('simple_mrp') }}"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Selling Price (₹) *</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                name="simple_selling_price" 
                                x-model="simplePrice" 
                                placeholder="44999.00" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold text-emerald-800"
                                value="{{ old('simple_selling_price') }}"
                            >
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Must be &le; MRP</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Cost Price (₹, Private)</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="simple_cost_price" 
                                x-model="simpleCost" 
                                placeholder="38000.00" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                                value="{{ old('simple_cost_price') }}"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Low Stock Alert</label>
                            <input 
                                type="number" 
                                name="simple_low_stock_threshold" 
                                min="1" 
                                value="{{ old('simple_low_stock_threshold', 5) }}" 
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                            >
                        </div>
                    </div>

                    <!-- Shipping Dimensions & Weight (Required for Shiprocket) -->
                    <div class="p-4 bg-emerald-50/40 rounded-xl border border-emerald-200/80 space-y-3">
                        <div class="flex items-center space-x-2 text-xs font-bold text-emerald-900">
                            <span>🚀 Shiprocket Logistics Dimensions</span>
                            <span class="text-[10px] text-emerald-700 font-normal">(Used for automated courier rate and volumetric weight calculations)</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Dead Weight (Grams) *</label>
                                <input type="number" name="simple_weight_grams" min="1" placeholder="14500" class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg" value="{{ old('simple_weight_grams', 500) }}">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Length (cm) *</label>
                                <input type="number" step="0.1" name="simple_length" min="0.1" placeholder="124" class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg" value="{{ old('simple_length', 15) }}">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Width (cm) *</label>
                                <input type="number" step="0.1" name="simple_width" min="0.1" placeholder="15" class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg" value="{{ old('simple_width', 10) }}">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Height (cm) *</label>
                                <input type="number" step="0.1" name="simple_height" min="0.1" placeholder="78" class="w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg" value="{{ old('simple_height', 5) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VARIABLE PRODUCT MATRIX -->
                <div x-show="productType === 'variable'" x-cloak class="space-y-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Define Product Variations (Storage, Color, Screen Size, etc.)</span>
                        <button type="button" @click="addVariant()" class="px-3 py-1 bg-emerald-600 text-white rounded-lg font-bold text-xs hover:bg-emerald-700 transition">
                            + Add Variant Row
                        </button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-2.5">Variant Name</th>
                                    <th class="p-2.5">SKU *</th>
                                    <th class="p-2.5">MRP (₹) *</th>
                                    <th class="p-2.5">Selling Price (₹) *</th>
                                    <th class="p-2.5">Stock *</th>
                                    <th class="p-2.5">Weight (g)</th>
                                    <th class="p-2.5">Dimensions (L&times;W&times;H cm)</th>
                                    <th class="p-2.5 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(v, vIdx) in variants" :key="vIdx">
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-2">
                                            <input type="text" :name="'variants[' + vIdx + '][name]'" x-model="v.name" class="w-full px-2 py-1 text-xs border rounded" placeholder="e.g. 128GB - Midnight Black">
                                        </td>
                                        <td class="p-2">
                                            <input type="text" :name="'variants[' + vIdx + '][sku]'" x-model="v.sku" class="w-full px-2 py-1 text-xs border rounded font-mono uppercase font-bold" placeholder="SKU-VAR-1">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" :name="'variants[' + vIdx + '][mrp]'" x-model="v.mrp" class="w-20 px-2 py-1 text-xs border rounded font-bold" placeholder="59999">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" :name="'variants[' + vIdx + '][selling_price]'" x-model="v.selling_price" class="w-20 px-2 py-1 text-xs border rounded font-bold text-emerald-800" placeholder="44999">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" :name="'variants[' + vIdx + '][stock]'" x-model="v.stock" class="w-16 px-2 py-1 text-xs border rounded font-bold" placeholder="10">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" :name="'variants[' + vIdx + '][weight_grams]'" x-model="v.weight_grams" class="w-16 px-2 py-1 text-xs border rounded" placeholder="500">
                                        </td>
                                        <td class="p-2">
                                            <div class="flex items-center space-x-1">
                                                <input type="number" step="0.1" :name="'variants[' + vIdx + '][length]'" x-model="v.length" class="w-12 px-1 py-1 text-[11px] border rounded" placeholder="L">
                                                <span>&times;</span>
                                                <input type="number" step="0.1" :name="'variants[' + vIdx + '][width]'" x-model="v.width" class="w-12 px-1 py-1 text-[11px] border rounded" placeholder="W">
                                                <span>&times;</span>
                                                <input type="number" step="0.1" :name="'variants[' + vIdx + '][height]'" x-model="v.height" class="w-12 px-1 py-1 text-[11px] border rounded" placeholder="H">
                                            </div>
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="removeVariant(vIdx)" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Section 3: Tax, HSN & Shipping Compliance -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center font-bold">3</span>
                        <span>Tax, HSN &amp; Shipping Policies</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">HSN Code</label>
                        <input type="text" name="hsn_code" placeholder="e.g. 8528" value="{{ old('hsn_code', '8528') }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">GST Tax Rate (%) *</label>
                        <select name="tax_percent" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg font-bold">
                            <option value="18.00" {{ old('tax_percent', '18.00') == '18.00' ? 'selected' : '' }}>18% (Standard Electronics)</option>
                            <option value="28.00" {{ old('tax_percent') == '28.00' ? 'selected' : '' }}>28% (Luxury / Large Appliances)</option>
                            <option value="12.00" {{ old('tax_percent') == '12.00' ? 'selected' : '' }}>12% (IT Hardware)</option>
                            <option value="5.00" {{ old('tax_percent') == '5.00' ? 'selected' : '' }}>5% (Concessional)</option>
                            <option value="0.00" {{ old('tax_percent') == '0.00' ? 'selected' : '' }}>0% (Exempt)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Return Window (Days) *</label>
                        <input type="number" name="return_window_days" min="0" max="30" value="{{ old('return_window_days', 7) }}" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Policy Toggles</label>
                        <div class="space-y-1.5 pt-1">
                            <label class="flex items-center space-x-2 text-xs text-slate-700">
                                <input type="checkbox" name="is_cod_allowed" value="1" {{ old('is_cod_allowed', true) ? 'checked' : '' }} class="rounded text-emerald-600">
                                <span>Allow COD Orders</span>
                            </label>
                            <label class="flex items-center space-x-2 text-xs text-slate-700">
                                <input type="checkbox" name="is_returnable" value="1" {{ old('is_returnable', true) ? 'checked' : '' }} class="rounded text-emerald-600">
                                <span>Returnable Product</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Product Media Gallery -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center font-bold">4</span>
                        <span>Product Images &amp; Media Gallery</span>
                    </h3>
                    <span class="text-xs text-slate-400">JPG, PNG, WEBP, MP4 (Max 10MB per file)</span>
                </div>

                <!-- Dropzone -->
                <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-8 text-center bg-slate-50/50 transition">
                    <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                        📷
                    </div>
                    <h4 class="text-xs font-bold text-slate-900">Add Product Media</h4>
                    <p class="text-[11px] text-slate-400 mb-3">Upload clear front, back, angle, and package photos</p>
                    <input 
                        type="file" 
                        name="media[]" 
                        multiple 
                        accept="image/jpeg,image/png,image/webp,video/mp4" 
                        class="hidden" 
                        id="singleProductMediaInput" 
                        @change="handleMediaSelect($event)"
                    >
                    <label for="singleProductMediaInput" class="cursor-pointer px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition shadow-xs inline-block">
                        Browse Files
                    </label>
                </div>

                <!-- Preview list -->
                <div x-show="mediaFiles.length > 0" x-cloak class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3 pt-2">
                    <template x-for="(m, mIdx) in mediaFiles" :key="mIdx">
                        <div class="relative group rounded-xl border border-slate-200 overflow-hidden bg-slate-100 aspect-square">
                            <img :src="m.url" class="w-full h-full object-cover">
                            <span x-show="mIdx === 0" class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[9px] font-black uppercase">
                                Primary
                            </span>
                            <button type="button" @click="removeMedia(mIdx)" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white text-xs font-bold flex items-center justify-center shadow">
                                &times;
                            </button>
                            <span class="absolute bottom-1 left-1 right-1 text-[9px] text-slate-700 bg-white/90 px-1 py-0.5 rounded truncate" x-text="m.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Section 5: Technical Specifications -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-800 text-xs flex items-center justify-center font-bold">5</span>
                        <span>Technical Specifications &amp; Attributes</span>
                    </h3>
                    <button type="button" @click="addSpec()" class="px-3 py-1 bg-slate-100 border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-200 transition">
                        + Add Spec Row
                    </button>
                </div>

                <div class="space-y-2">
                    <template x-for="(s, sIdx) in specifications" :key="sIdx">
                        <div class="grid grid-cols-1 sm:grid-cols-7 gap-2 items-center">
                            <div class="sm:col-span-2">
                                <input type="text" :name="'specifications[' + sIdx + '][group]'" x-model="s.group" placeholder="Group (e.g. Display, Audio)" class="w-full px-2.5 py-1.5 text-xs border rounded-lg">
                            </div>
                            <div class="sm:col-span-2">
                                <input type="text" :name="'specifications[' + sIdx + '][key]'" x-model="s.key" placeholder="Key (e.g. Refresh Rate)" class="w-full px-2.5 py-1.5 text-xs border rounded-lg font-bold">
                            </div>
                            <div class="sm:col-span-2">
                                <input type="text" :name="'specifications[' + sIdx + '][value]'" x-model="s.value" placeholder="Value (e.g. 120Hz)" class="w-full px-2.5 py-1.5 text-xs border rounded-lg">
                            </div>
                            <div class="sm:col-span-1 text-right">
                                <button type="button" @click="removeSpec(sIdx)" class="text-rose-500 font-bold hover:text-rose-700 text-sm px-2">&times;</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky bottom-4 z-20 p-4 rounded-2xl bg-white border border-slate-200 shadow-xl flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    Ready to submit? Products will undergo quality moderation before becoming live.
                </div>
                <div class="flex items-center space-x-3">
                    <button 
                        type="button" 
                        @click="document.getElementById('submit_action_input').value = 'save_draft'; document.getElementById('single-product-form').submit();"
                        class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50 transition"
                    >
                        Save as Draft
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs flex items-center space-x-1.5"
                    >
                        <span>Submit for QC Review</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 3: ADD CATALOG IN BULK (CSV WORKFLOW & DRY-RUN PREVIEW)               -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'bulk'" x-cloak class="space-y-6">

        <!-- Step 1 & 2 Workflow Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Step 1: Download Template -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 space-y-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 font-black flex items-center justify-center text-base">
                    1
                </div>
                <h3 class="text-sm font-extrabold text-slate-900">Download Template</h3>
                <p class="text-xs text-slate-500">
                    Get the standard ALLZONE catalog spreadsheet prefilled with electronics sample rows, headers, and Shiprocket dimension specs.
                </p>
                <div class="pt-2">
                    <a 
                        href="{{ route('seller.products.bulk_template') }}" 
                        class="w-full inline-flex items-center justify-center space-x-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Download CSV Template</span>
                    </a>
                </div>
            </div>

            <!-- Step 2: Upload CSV Template -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 space-y-3 md:col-span-2">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 font-black flex items-center justify-center text-base">
                    2
                </div>
                <h3 class="text-sm font-extrabold text-slate-900">Upload Completed File &amp; Validate</h3>
                <p class="text-xs text-slate-500">
                    Our system validates every row, checks MRP and price compliance, verifies SKU uniqueness, and detects non-electronics items before importing.
                </p>

                <form method="POST" action="{{ route('seller.products.bulk_preview') }}" enctype="multipart/form-data" class="space-y-3 pt-1">
                    @csrf
                    <div class="flex items-center space-x-2">
                        <input 
                            type="file" 
                            name="file" 
                            required 
                            accept=".csv,text/csv,text/plain" 
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-300 rounded-lg p-1.5 cursor-pointer"
                        >
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shrink-0 shadow-xs">
                            Analyze File &rarr;
                        </button>
                    </div>
                    <span class="text-[10px] text-slate-400 block">Accepted formats: .csv, UTF-8 formatted, up to 10MB</span>
                </form>
            </div>
        </div>

        <!-- Preview Modal / Section when a file was analyzed -->
        @if(isset($previewImport) && $previewImport)
            <div class="bg-white rounded-xl border-2 border-indigo-200 shadow-md p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wide">
                            Dry-Run Analysis Complete
                        </span>
                        <h3 class="text-base font-black text-slate-900 mt-1">
                            Batch #{{ $previewImport->id }}: {{ $previewImport->file_name }}
                        </h3>
                    </div>

                    <div class="flex items-center space-x-3 text-xs">
                        <div class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200">
                            Total Rows: <strong class="text-slate-900">{{ $previewImport->total_rows }}</strong>
                        </div>
                        <div class="px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">
                            Valid Rows: <strong class="text-emerald-700">{{ $previewImport->total_rows - $previewImport->failed_rows }}</strong>
                        </div>
                        <div class="px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800">
                            Errors: <strong class="text-rose-700">{{ $previewImport->failed_rows }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Row-level Errors Table if errors exist -->
                @if(!empty($previewImport->errors) && count($previewImport->errors) > 0)
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-rose-700 flex items-center space-x-1.5">
                                <span>▲ Row-Level Validation Errors ({{ count($previewImport->errors) }} issues found)</span>
                            </h4>
                            <a 
                                href="{{ route('seller.products.bulk_errors', $previewImport->id) }}"
                                class="text-xs font-bold text-rose-600 hover:text-rose-800 underline flex items-center space-x-1"
                            >
                                <span>Download Error Report (.CSV)</span>
                            </a>
                        </div>
                        <div class="max-h-60 overflow-y-auto border border-rose-200 rounded-xl">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-rose-50/70 text-rose-800 font-bold border-b border-rose-200 sticky top-0">
                                    <tr>
                                        <th class="p-2">Row #</th>
                                        <th class="p-2">SKU</th>
                                        <th class="p-2">Product Title</th>
                                        <th class="p-2">Field</th>
                                        <th class="p-2">Error Reason</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-100 bg-white">
                                    @foreach(array_slice($previewImport->errors, 0, 50) as $err)
                                        <tr class="hover:bg-rose-50/30">
                                            <td class="p-2 font-mono font-bold text-slate-700">{{ $err['row'] ?? 'N/A' }}</td>
                                            <td class="p-2 font-mono text-slate-600">{{ $err['sku'] ?? 'N/A' }}</td>
                                            <td class="p-2 font-bold text-slate-800">{{ $err['title'] ?? 'N/A' }}</td>
                                            <td class="p-2 font-mono text-[11px] text-slate-600">{{ $err['field'] ?? 'N/A' }}</td>
                                            <td class="p-2 text-rose-700 font-medium">{{ $err['message'] ?? ($err['error'] ?? 'Invalid data') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Confirm Import Action Bar -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs text-slate-600">
                        @if($previewImport->failed_rows > 0)
                            <span>You can import the <strong>{{ $previewImport->total_rows - $previewImport->failed_rows }}</strong> valid records now, and re-upload failed records after fixing.</span>
                        @else
                            <span class="text-emerald-700 font-bold">✔ All {{ $previewImport->total_rows }} rows passed validation without any errors!</span>
                        @endif
                    </div>

                    <div class="flex items-center space-x-3">
                        <form method="POST" action="{{ route('seller.products.bulk_confirm', $previewImport->id) }}">
                            @csrf
                            <button 
                                type="submit" 
                                class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition shadow-xs"
                                @if($previewImport->total_rows - $previewImport->failed_rows <= 0) disabled @endif
                            >
                                Confirm &amp; Import {{ $previewImport->total_rows - $previewImport->failed_rows }} Products &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Guidelines on Column Rules -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-5 space-y-3">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                Catalog Upload Guidelines &amp; Formatting Rules
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600">
                <div class="p-3 bg-slate-50 rounded-lg space-y-1">
                    <strong class="text-slate-800">Exclusive Electronics Marketplace:</strong>
                    <p class="text-[11px]">ALLZONE only hosts consumer electronics, home appliances, IT accessories, and smart devices. Uploads containing fashion, food, groceries, or cosmetics will be rejected by validation.</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg space-y-1">
                    <strong class="text-slate-800">Pricing &amp; Discount Integrity:</strong>
                    <p class="text-[11px]">Selling price must always be strictly less than or equal to MRP. Negative prices or zero pricing will cause row-level rejection.</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg space-y-1">
                    <strong class="text-slate-800">Shiprocket Parcel Dimensions:</strong>
                    <p class="text-[11px]">Provide accurate dead weight in grams and box dimensions (Length, Width, Height) in centimeters to ensure automated courier pickup without rate disputes.</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg space-y-1">
                    <strong class="text-slate-800">SKU Uniqueness &amp; Barcode:</strong>
                    <p class="text-[11px]">Each variant must have a unique alphanumeric SKU across your store. Existing SKUs belonging to other merchants are rejected to protect brand ownership.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection