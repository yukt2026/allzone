@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Edit Announcement - ALLZONE Admin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-16">

    <!-- Header -->
    <x-page-header 
        title="Edit Platform Announcement" 
        subtitle="Update content, target audience, schedule, or media for this notice."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Announcements' => route('admin.announcements.index'), 'Edit' => '#']"
    >
        <x-slot name="actions">
            <a 
                href="{{ route('admin.announcements.index') }}" 
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition"
            >
                &larr; Back to List
            </a>
        </x-slot>
    </x-page-header>

    <!-- Edit Form -->
    <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">
                1. Notice Content &amp; Details
            </h3>

            <!-- Title -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Announcement Title <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    value="{{ old('title', $announcement->title) }}" 
                    required 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5 focus:ring-1 focus:ring-indigo-500 @error('title') border-rose-500 @enderror"
                >
                @error('title')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category & Priority Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Category <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" required class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                        <option value="important" {{ old('category', $announcement->category) === 'important' ? 'selected' : '' }}>Important Notice</option>
                        <option value="general" {{ old('category', $announcement->category) === 'general' ? 'selected' : '' }}>General Announcement</option>
                        <option value="policy" {{ old('category', $announcement->category) === 'policy' ? 'selected' : '' }}>Policy Update</option>
                        <option value="logistics" {{ old('category', $announcement->category) === 'logistics' ? 'selected' : '' }}>Logistics &amp; SLA Guidelines</option>
                        <option value="feature" {{ old('category', $announcement->category) === 'feature' ? 'selected' : '' }}>New Platform Feature</option>
                    </select>
                    @error('category')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Priority Level <span class="text-rose-500">*</span>
                    </label>
                    <select name="priority" required class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                        <option value="normal" {{ old('priority', $announcement->priority) === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority', $announcement->priority) === 'high' ? 'selected' : '' }}>High Priority</option>
                        <option value="urgent" {{ old('priority', $announcement->priority) === 'urgent' ? 'selected' : '' }}>Urgent Alert (Banner Alert)</option>
                        <option value="low" {{ old('priority', $announcement->priority) === 'low' ? 'selected' : '' }}>Low Priority</option>
                    </select>
                    @error('priority')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Summary -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Summary / Excerpt
                </label>
                <textarea 
                    name="summary" 
                    rows="2" 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                >{{ old('summary', $announcement->summary) }}</textarea>
                @error('summary')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Full Content -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Full Notice Content <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="content" 
                    rows="8" 
                    required 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                >{{ old('content', $announcement->content) }}</textarea>
                @error('content')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Target Audience Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5" x-data="{ targetType: '{{ old('target_type', $announcement->target_type) }}' }">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">
                2. Target Seller Audience
            </h3>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    Who should receive this announcement? <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                           :class="targetType === 'all' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                        <input type="radio" name="target_type" value="all" x-model="targetType" class="text-indigo-600 focus:ring-indigo-500">
                        <span>All Sellers (Platform-wide)</span>
                    </label>

                    <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                           :class="targetType === 'seller_group' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                        <input type="radio" name="target_type" value="seller_group" x-model="targetType" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Specific Seller Group</span>
                    </label>

                    <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                           :class="targetType === 'specific_sellers' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                        <input type="radio" name="target_type" value="specific_sellers" x-model="targetType" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Specific Named Sellers</span>
                    </label>

                    <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition text-xs font-semibold"
                           :class="targetType === 'customers' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-900' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'">
                        <input type="radio" name="target_type" value="customers" x-model="targetType" class="text-indigo-600 focus:ring-indigo-500">
                        <span>Customers Only (Marketplace Buyers)</span>
                    </label>
                </div>
                @error('target_type')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Seller Group Selector -->
            <div x-show="targetType === 'seller_group'" class="space-y-2 pt-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Select Target Business Type
                </label>
                <select name="target_seller_type" class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2.5">
                    @foreach($sellerTypes as $type)
                        <option value="{{ $type->value }}" {{ old('target_seller_type', $announcement->target_seller_type) === $type->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type->value)) }} Sellers
                        </option>
                    @endforeach
                </select>
                @error('target_seller_type')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Specific Sellers Multi-Select -->
            @php
                $currentSellerIds = old('target_seller_ids', $announcement->target_seller_ids ?? []);
            @endphp
            <div x-show="targetType === 'specific_sellers'" class="space-y-2 pt-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Select One or More Sellers
                </label>
                <select name="target_seller_ids[]" multiple class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 min-h-32">
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ in_array($seller->id, $currentSellerIds) ? 'selected' : '' }}>
                            {{ $seller->business_name }} (#{{ $seller->id }}) - {{ ucfirst(str_replace('_', ' ', is_object($seller->seller_type) ? $seller->seller_type->value : (string)$seller->seller_type)) }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400">Hold Ctrl (Windows) or Cmd (Mac) to select multiple sellers.</p>
                @error('target_seller_ids')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Banner & Attachment Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">
                3. Banner Image &amp; Policy Document Attachment
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Banner Image -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Banner Image
                    </label>

                    @if($announcement->banner_image)
                        <div class="p-2 border border-slate-200 rounded-lg bg-slate-50 space-y-2">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($announcement->banner_image) }}" alt="Banner" class="w-full h-28 object-cover rounded">
                            <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer">
                                <input type="checkbox" name="remove_banner" value="1" class="text-rose-600 rounded">
                                <span>Remove current banner</span>
                            </label>
                        </div>
                    @endif

                    <input 
                        type="file" 
                        name="banner_image" 
                        accept="image/*" 
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-lg p-1.5"
                    >
                    <p class="text-[10px] text-slate-400">Upload new image to replace current.</p>
                </div>

                <!-- Attachment -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Document Attachment
                    </label>

                    @if($announcement->attachment_path)
                        <div class="p-3 border border-slate-200 rounded-lg bg-slate-50 space-y-2 text-xs">
                            <div class="flex items-center gap-2 font-medium text-slate-700">
                                <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="truncate">{{ $announcement->attachment_name ?: basename($announcement->attachment_path) }}</span>
                            </div>
                            <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer pt-1">
                                <input type="checkbox" name="remove_attachment" value="1" class="text-rose-600 rounded">
                                <span>Remove current attachment</span>
                            </label>
                        </div>
                    @endif

                    <input 
                        type="file" 
                        name="attachment" 
                        accept=".pdf,.doc,.docx,.zip,.png,.jpg" 
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-lg p-1.5"
                    >
                    <p class="text-[10px] text-slate-400">Upload new file to replace current attachment.</p>
                </div>
            </div>
        </div>

        <!-- Publication & Schedule Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-3">
                4. Schedule &amp; Publication Status
            </h3>

            <div class="flex items-center gap-3">
                <input 
                    type="checkbox" 
                    name="is_published" 
                    id="is_published" 
                    value="1" 
                    {{ old('is_published', $announcement->is_published) ? 'checked' : '' }} 
                    class="w-4 h-4 text-indigo-600 rounded-sm border-slate-300 focus:ring-indigo-500"
                >
                <label for="is_published" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                    Published / Active (Live to targeted sellers)
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Publication Date
                    </label>
                    <input 
                        type="datetime-local" 
                        name="published_at" 
                        value="{{ old('published_at', $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}" 
                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Expiry Date
                    </label>
                    <input 
                        type="datetime-local" 
                        name="expires_at" 
                        value="{{ old('expires_at', $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}" 
                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                    >
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                Update Announcement
            </button>
        </div>
    </form>
</div>
@endsection
