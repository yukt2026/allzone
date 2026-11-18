@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Image Bulk Upload & Gallery Studio - ALLZONE'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    uploadMode: 'auto', // 'auto' (direct/auto-match) or 'csv' (csv mapping)
    files: [],
    dragging: false,
    selectedProductId: '{{ $selectedProductId ?? '' }}',
    setPrimary: true,
    isUploading: false,
    uploadProgress: 0,
    previewModalUrl: null,
    replaceModalMediaId: null,

    handleDrop(e) {
        this.dragging = false;
        const dropped = Array.from(e.dataTransfer.files || []);
        this.addFiles(dropped);
    },

    handleFileSelect(e) {
        const selected = Array.from(e.target.files || []);
        this.addFiles(selected);
    },

    addFiles(newFiles) {
        newFiles.forEach(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            const validExts = ['jpg', 'jpeg', 'png', 'webp', 'mp4'];
            if (!validExts.includes(ext)) {
                alert('File ' + file.name + ' is not a supported format (JPG, PNG, WEBP, MP4).');
                return;
            }

            const url = URL.createObjectURL(file);
            this.files.push({
                file: file,
                name: file.name,
                url: url,
                size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
                type: file.type
            });
        });
        this.syncFileInput();
    },

    removeFile(idx) {
        if (this.files[idx] && this.files[idx].url) {
            URL.revokeObjectURL(this.files[idx].url);
        }
        this.files.splice(idx, 1);
        this.syncFileInput();
    },

    clearAll() {
        this.files.forEach(f => {
            if (f.url) URL.revokeObjectURL(f.url);
        });
        this.files = [];
        this.syncFileInput();
    },

    syncFileInput() {
        const input = document.getElementById('bulkUploadFileInput');
        if (!input) return;
        try {
            const dt = new DataTransfer();
            this.files.forEach(item => {
                if (item.file) dt.items.add(item.file);
            });
            input.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer sync fallback:', e);
        }
    },

    openPreview(url) {
        this.previewModalUrl = url;
    },

    openReplaceModal(mediaId) {
        this.replaceModalMediaId = mediaId;
        const form = document.getElementById('replace-image-form');
        form.action = '{{ url('/seller/images/replace') }}/' + mediaId;
        document.getElementById('replaceFileInput').click();
    }
}">

    <!-- Header & Mode Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-emerald-700 mb-1">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Supplier Studio &bull; Photography Studio</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                Image Bulk Upload &amp; Gallery
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Bulk upload high-res product photos, auto-associate by SKU, map with CSV, and organize gallery cover photos.
            </p>
        </div>

        <!-- Mode Toggle -->
        <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-bold">
            <button 
                type="button" 
                @click="uploadMode = 'auto'"
                :class="uploadMode === 'auto' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
                class="px-4 py-2 rounded-lg transition flex items-center space-x-1.5"
            >
                <span>⚡ Drag &amp; Drop / Auto-Match</span>
            </button>
            <button 
                type="button" 
                @click="uploadMode = 'csv'"
                :class="uploadMode === 'csv' ? 'bg-white text-slate-900 shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
                class="px-4 py-2 rounded-lg transition flex items-center space-x-1.5"
            >
                <span>📄 CSV Mapping Upload</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
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

    @if(session('upload_errors') && count(session('upload_errors')) > 0)
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-2xs">
            <div class="font-bold mb-1 flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Some uploaded images could not be processed:</span>
            </div>
            <ul class="list-disc pl-6 space-y-0.5">
                @foreach(session('upload_errors') as $uErr)
                    <li>{{ $uErr }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 3 Metrics KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 bg-white rounded-xl border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total Gallery Photos</span>
                <span class="text-2xl font-black text-slate-900 mt-0.5 block">{{ number_format($totalImages) }}</span>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Stored securely in cloud</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-bold">
                📷
            </div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-emerald-200 shadow-2xs bg-emerald-50/20 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider block">Products With Photos</span>
                <span class="text-2xl font-black text-emerald-700 mt-0.5 block">{{ number_format($productsWithImages) }}</span>
                <span class="text-[10px] text-emerald-600 mt-0.5 block">Ready for storefront display</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg font-bold">
                ✔
            </div>
        </div>
        <div class="p-4 bg-white rounded-xl border border-amber-200 shadow-2xs bg-amber-50/20 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider block">Needing Photos</span>
                <span class="text-2xl font-black text-amber-700 mt-0.5 block">{{ number_format($productsWithoutImages) }}</span>
                <span class="text-[10px] text-amber-600 mt-0.5 block">Zero gallery media items</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg font-bold">
                ⏳
            </div>
        </div>
    </div>

    <!-- Main Grid: Dropzone & Settings + Disallowed Guidelines Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Left Column: Upload Area (3 Cols) -->
        <div class="lg:col-span-3 space-y-6">

            <!-- MODE 1: AUTO-MATCH & DIRECT DRAG-AND-DROP -->
            <div x-show="uploadMode === 'auto'" class="space-y-4">

                <!-- Smart Auto-match Tip Banner -->
                <div class="p-4 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-center justify-between text-xs text-emerald-900 shadow-2xs">
                    <div class="flex items-center space-x-3">
                        <span class="text-xl">💡</span>
                        <span>
                            <strong>Smart Filename Matching:</strong> Name your image files with your product SKU (e.g. <code class="bg-white px-1.5 py-0.5 rounded border border-emerald-300 font-mono text-[11px]">SONY-55-4K_front.jpg</code> or <code class="bg-white px-1.5 py-0.5 rounded border border-emerald-300 font-mono text-[11px]">SONY-55-4K_1.jpg</code>), and ALLZONE will automatically attach them to the correct product!
                        </span>
                    </div>
                </div>

                <!-- Upload Form with Target Product Override -->
                <form 
                    id="bulkUploadForm"
                    method="POST" 
                    action="{{ route('seller.images.upload') }}" 
                    enctype="multipart/form-data" 
                    class="space-y-4"
                    @submit="isUploading = true"
                >
                    @csrf

                    <!-- Target Selection Bar -->
                    <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex-1 min-w-[240px]">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Target Product Destination:</label>
                            <select 
                                name="product_id" 
                                x-model="selectedProductId"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 font-medium"
                            >
                                <option value="">⚡ Auto-Match by Filename (Default)</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                                        #{{ $p->id }} - {{ $p->title }} ({{ $p->media->count() }} photos)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center space-x-4 pt-4 sm:pt-0">
                            <label class="flex items-center space-x-2 text-xs text-slate-700 cursor-pointer">
                                <input type="checkbox" name="set_primary" value="1" x-model="setPrimary" class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="font-semibold">Set 1st photo as cover</span>
                            </label>

                            <button 
                                type="button" 
                                @click="clearAll()" 
                                x-show="files.length > 0"
                                class="text-rose-600 hover:text-rose-800 font-bold text-xs"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Drag & Drop Dropzone Box -->
                    <div 
                        class="border-2 border-dashed rounded-2xl p-12 text-center transition flex flex-col items-center justify-center bg-white shadow-2xs cursor-pointer"
                        :class="dragging ? 'border-emerald-600 bg-emerald-50/40' : 'border-slate-300 hover:border-emerald-500'"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="handleDrop($event)"
                        @click="document.getElementById('bulkUploadFileInput').click()"
                    >
                        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold mb-3 shadow-xs">
                            +
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 mb-1">Add Product Photography</h3>
                        <p class="text-xs text-slate-400 mb-4">Drag and drop images here, or click to browse from your device</p>

                        <input 
                            type="file" 
                            name="images[]" 
                            multiple 
                            accept="image/jpeg,image/png,image/webp,video/mp4" 
                            class="hidden" 
                            id="bulkUploadFileInput" 
                            @change="handleFileSelect($event)"
                        >
                        <button 
                            type="button" 
                            class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs"
                        >
                            Browse Files
                        </button>
                    </div>

                    <!-- Queued Image Preview Grid -->
                    <div x-show="files.length > 0" x-cloak class="p-5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h4 class="text-xs font-bold text-slate-900" x-text="files.length + ' image(s) queued for upload'"></h4>
                            <span class="text-[11px] text-slate-400">Review images before uploading</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                            <template x-for="(f, idx) in files" :key="idx">
                                <div class="relative group rounded-xl border border-slate-200 overflow-hidden bg-slate-100 aspect-square">
                                    <img :src="f.url" class="w-full h-full object-cover">
                                    <span x-show="idx === 0 && setPrimary" class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[9px] font-black uppercase">
                                        Cover
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="removeFile(idx)" 
                                        class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-rose-600 text-white text-xs font-bold flex items-center justify-center shadow"
                                    >
                                        &times;
                                    </button>
                                    <div class="absolute bottom-0 inset-x-0 bg-slate-900/80 p-1 text-[9px] text-white truncate font-mono" x-text="f.name"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Submit Button Bar -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div class="text-xs text-slate-500">
                                Files ready for upload: <strong x-text="files.length"></strong>
                            </div>
                            <button 
                                type="submit" 
                                class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs flex items-center space-x-2"
                                :disabled="isUploading || files.length === 0"
                            >
                                <span x-show="!isUploading">Upload &amp; Assign Images &rarr;</span>
                                <span x-show="isUploading">Uploading Images...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- MODE 2: CSV MAPPING WORKFLOW -->
            <div x-show="uploadMode === 'csv'" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Download Template Card -->
                    <div class="p-5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm">
                            1
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Download Mapping File</h4>
                        <p class="text-[11px] text-slate-500">
                            Get the CSV template with SKU, image filename, sort order, and primary image columns.
                        </p>
                        <a 
                            href="{{ route('seller.images.template') }}" 
                            class="w-full inline-flex items-center justify-center space-x-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs"
                        >
                            <span>Download Mapping CSV</span>
                        </a>
                    </div>

                    <!-- Upload Form Card -->
                    <div class="p-5 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-3 md:col-span-2">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-sm">
                            2
                        </div>
                        <h4 class="text-xs font-bold text-slate-900">Upload CSV and Images Together</h4>
                        <p class="text-[11px] text-slate-500">
                            Select your completed CSV mapping file and browse the photos specified in the file.
                        </p>

                        <form method="POST" action="{{ route('seller.images.map_csv') }}" enctype="multipart/form-data" class="space-y-3 pt-1">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">1. Mapping CSV File:</label>
                                <input type="file" name="csv_file" required accept=".csv,text/csv" class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-700 border border-slate-300 rounded-lg p-1.5 cursor-pointer">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">2. Image Files Referenced in CSV:</label>
                                <input type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp,video/mp4" class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 border border-slate-300 rounded-lg p-1.5 cursor-pointer">
                            </div>

                            <button type="submit" class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-xs">
                                Process CSV Mapping &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Disallowed Guidelines Sidebar (Matching Meesho Specs Cleanly) -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs text-xs space-y-3">
                <div class="flex items-center space-x-1.5 text-rose-600 font-bold text-[11px]">
                    <span>🚫</span>
                    <span>Image types which are not allowed:</span>
                </div>

                <div class="space-y-2 divide-y divide-slate-100 text-[11px]">
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Watermark image</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Fake branded / 1st copy</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Image with price sticker</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Pixelated / low-res</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Inverted or flipped</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Blurry or unclear</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Incomplete or cut off</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Stretched aspect ratio</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                    <div class="pt-2 flex items-center justify-between">
                        <span class="font-medium text-slate-700">Heavy promotional text</span>
                        <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-[10px] text-slate-500">
                    💡 <strong>Tip:</strong> Square photos with pure white or light grey backgrounds perform best in search results and zoom previews.
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- GALLERY MANAGEMENT CONSOLE (PREVIEW, PRIMARY, REPLACE, REORDER, DELETE)   -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden mt-8">
        <!-- Gallery Bar -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Current Product Gallery</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Manage cover photos, replace outdated pictures, or preview high-resolution zooms.</p>
            </div>

            <!-- Filter by Product -->
            <form method="GET" action="{{ route('seller.products.bulk_upload') }}" class="flex items-center space-x-2">
                <select name="product_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg font-medium">
                    <option value="">All Products Gallery</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $selectedProductId == $p->id ? 'selected' : '' }}>
                            {{ Str::limit($p->title, 35) }} ({{ $p->media->count() }})
                        </option>
                    @endforeach
                </select>
                @if($selectedProductId)
                    <a href="{{ route('seller.products.bulk_upload') }}" class="px-2 py-1 rounded text-slate-500 hover:text-slate-800 text-xs font-bold">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Gallery Grid -->
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                @forelse($mediaGallery as $item)
                    <div class="group relative rounded-xl border border-slate-200 overflow-hidden bg-slate-50 aspect-square flex flex-col justify-between shadow-2xs">
                        <img 
                            src="{{ $item->url }}" 
                            alt="Product photo" 
                            class="w-full h-full object-cover cursor-pointer hover:scale-105 transition duration-200"
                            @click="openPreview('{{ $item->url }}')"
                        >

                        <!-- Primary Cover Badge -->
                        @if($item->sort_order === 1)
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[9px] font-black uppercase shadow-xs">
                                Primary Cover
                            </span>
                        @endif

                        <!-- Top Right Dropdown / Actions -->
                        <div class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition flex items-center space-x-1">
                            <button 
                                type="button" 
                                @click="openReplaceModal({{ $item->id }})" 
                                title="Replace image"
                                class="w-6 h-6 rounded bg-white/90 hover:bg-white text-slate-700 text-xs font-bold flex items-center justify-center shadow-xs"
                            >
                                🔄
                            </button>

                            <form method="POST" action="{{ route('seller.images.destroy', $item->id) }}" onsubmit="return confirm('Remove this image from product gallery?');">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    title="Delete image"
                                    class="w-6 h-6 rounded bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold flex items-center justify-center shadow-xs"
                                >
                                    &times;
                                </button>
                            </form>
                        </div>

                        <!-- Bottom Product Info & Set Primary button -->
                        <div class="absolute bottom-0 inset-x-0 bg-slate-900/80 p-1.5 text-white opacity-0 group-hover:opacity-100 transition space-y-1">
                            <div class="text-[9px] font-bold truncate">
                                {{ $item->product?->title ?? 'Product #' . $item->product_id }}
                            </div>
                            @if($item->sort_order !== 1)
                                <form method="POST" action="{{ route('seller.images.set_primary', $item->id) }}">
                                    @csrf
                                    <button type="submit" class="w-full py-0.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-[9px] font-bold">
                                        Make Cover
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-12 text-center text-slate-400">
                        <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-slate-100 flex items-center justify-center text-xl text-slate-500">
                            📷
                        </div>
                        <div class="font-bold text-slate-700">No product images in this gallery view</div>
                        <div class="text-xs text-slate-400 mt-0.5">Drop new images into the dropzone above to populate your gallery.</div>
                    </div>
                @endforelse
            </div>

            @if($mediaGallery->hasPages())
                <div class="pt-5 border-t border-slate-100 mt-5">
                    {{ $mediaGallery->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Hidden Replace Image Form -->
    <form id="replace-image-form" method="POST" action="" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="file" id="replaceFileInput" name="new_image" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
    </form>

    <!-- Modal Zoom Preview -->
    <div 
        x-show="previewModalUrl" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4"
        @keydown.escape.window="previewModalUrl = null"
    >
        <div class="relative max-w-4xl max-h-[90vh] bg-white rounded-2xl overflow-hidden p-2 shadow-2xl">
            <button 
                type="button" 
                @click="previewModalUrl = null" 
                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-slate-900/80 text-white flex items-center justify-center font-bold text-lg hover:bg-slate-900 z-10"
            >
                &times;
            </button>
            <img :src="previewModalUrl" class="max-h-[85vh] object-contain rounded-xl">
        </div>
    </div>
</div>
@endsection
