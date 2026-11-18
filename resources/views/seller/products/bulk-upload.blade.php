@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Image Bulk Upload - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    files: [],
    dragging: false,
    handleDrop(e) {
        this.dragging = false;
        let dropped = e.dataTransfer.files;
        for (let i = 0; i < dropped.length; i++) {
            this.files.push(dropped[i]);
        }
    }
}">
    <!-- Header (Screenshot 5) -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Image Bulk Upload
        </h1>
    </div>

    <!-- Main Grid: Dropzone + Disallowed Guidelines Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left: Dropzone Area (3 Cols) -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Tip Banner -->
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200/80 rounded-xl flex items-center justify-between text-xs text-emerald-900">
                <div class="flex items-center space-x-2.5">
                    <span class="text-lg">📁</span>
                    <span>
                        <strong>Introducing pre-filled templates for faster bulk uploads:</strong> Add product front images and get prefilled catalog template.
                    </span>
                </div>
                <button type="button" class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-bold hover:bg-slate-50 transition shrink-0 ml-2">
                    Get Started
                </button>
            </div>

            <!-- Drag & Drop Box -->
            <div 
                class="border-2 border-dashed rounded-2xl p-16 text-center transition flex flex-col items-center justify-center bg-white shadow-2xs"
                :class="dragging ? 'border-purple-600 bg-purple-50/30' : 'border-slate-250 hover:border-purple-400'"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="handleDrop($event)"
            >
                <div class="w-14 h-14 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold mb-3 shadow-xs">
                    +
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">Add Images</h3>
                <p class="text-xs text-slate-400 mb-4">You can drop images here or browse from computer</p>

                <input type="file" multiple class="hidden" id="bulkImageInput" @change="for(let i=0; i<$event.target.files.length; i++) files.push($event.target.files[i])">
                <label for="bulkImageInput" class="cursor-pointer px-5 py-2.5 rounded-lg bg-[#53389e] hover:bg-[#432c81] text-white text-xs font-bold transition shadow-xs">
                    Browse Files
                </label>
            </div>

            <!-- Uploaded preview list -->
            <div x-show="files.length > 0" x-cloak class="p-4 bg-white rounded-xl border border-slate-200">
                <h4 class="text-xs font-bold text-slate-800 mb-2" x-text="files.length + ' image(s) queued for upload'"></h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                    <template x-for="(f, idx) in files" :key="idx">
                        <div class="p-2 border rounded-lg flex items-center justify-between bg-slate-50">
                            <span class="truncate font-mono text-[10px]" x-text="f.name"></span>
                            <button type="button" @click="files.splice(idx, 1)" class="text-rose-500 font-bold ml-1">&times;</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right: Non-Allowed Image Guidelines (Matching Screenshot 5 Right) -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs text-xs space-y-3">
            <div class="flex items-center space-x-1.5 text-rose-600 font-bold text-[11px]">
                <span>🚫</span>
                <span>Image types which are not allowed:</span>
            </div>

            <div class="space-y-2.5 divide-y divide-slate-100 text-[11px]">
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Watermark image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Fake branded/1st copy</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Image with price</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Pixelated image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Inverted image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Blur/unclear image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Incomplete image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Stretched/shrunk image</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Image with props</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="font-medium text-slate-700">Image with text</span>
                    <span class="text-rose-600 font-bold text-[10px]">🚫 NOT ALLOWED</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
