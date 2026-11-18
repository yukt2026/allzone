@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Create Announcement - ALLZONE Admin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-16">

    <!-- Header -->
    <x-page-header 
        title="Create Platform Announcement" 
        subtitle="Broadcast policy notices, promotional sales, or compliance rules to sellers."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Announcements' => route('admin.announcements.index'), 'Create' => '#']"
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

    <!-- Create Form -->
    <form action="{{ route('admin.announcements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

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
                    value="{{ old('title') }}" 
                    required 
                    placeholder="e.g. Mandatory Next-Day Dispatch Compliance Active" 
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
                        <option value="important" {{ old('category') === 'important' ? 'selected' : '' }}>Important Notice</option>
                        <option value="general" {{ old('category', 'general') === 'general' ? 'selected' : '' }}>General Announcement</option>
                        <option value="policy" {{ old('category') === 'policy' ? 'selected' : '' }}>Policy Update</option>
                        <option value="logistics" {{ old('category') === 'logistics' ? 'selected' : '' }}>Logistics &amp; SLA Guidelines</option>
                        <option value="feature" {{ old('category') === 'feature' ? 'selected' : '' }}>New Platform Feature</option>
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
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High Priority</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent Alert (Banner Alert)</option>
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low Priority</option>
                    </select>
                    @error('priority')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Summary -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Summary / Excerpt (Displayed in notice cards)
                </label>
                <textarea 
                    name="summary" 
                    rows="2" 
                    placeholder="Brief 1-2 sentence overview of the policy or announcement..." 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                >{{ old('summary') }}</textarea>
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
                    placeholder="Detailed notice body, terms, steps required by sellers, deadlines, and contact information..." 
                    class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                >{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Target Audience Section -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-5" x-data="{ targetType: '{{ old('target_type', 'all') }}' }">
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
                        <option value="{{ $type->value }}" {{ old('target_seller_type') === $type->value ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type->value)) }} Sellers
                        </option>
                    @endforeach
                </select>
                @error('target_seller_type')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Specific Sellers Multi-Select -->
            <div x-show="targetType === 'specific_sellers'" class="space-y-2 pt-2">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Select One or More Sellers
                </label>
                <select name="target_seller_ids[]" multiple class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 min-h-32">
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ (is_array(old('target_seller_ids')) && in_array($seller->id, old('target_seller_ids'))) ? 'selected' : '' }}>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Announcement Banner Image (Optional)
                    </label>
                    <input 
                        type="file" 
                        name="banner_image" 
                        accept="image/*" 
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-lg p-1.5"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">PNG, JPG, or WebP up to 4MB.</p>
                    @error('banner_image')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Attachment / Policy Document (Optional)
                    </label>
                    <input 
                        type="file" 
                        name="attachment" 
                        accept=".pdf,.doc,.docx,.zip,.png,.jpg" 
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 dark:border-slate-700 rounded-lg p-1.5"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">PDF, DOC, DOCX, ZIP up to 10MB.</p>
                    @error('attachment')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
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
                    {{ old('is_published', true) ? 'checked' : '' }} 
                    class="w-4 h-4 text-indigo-600 rounded-sm border-slate-300 focus:ring-indigo-500"
                >
                <label for="is_published" class="text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer">
                    Publish Immediately (Visible to targeted sellers once saved)
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Schedule Publication Date (Optional)
                    </label>
                    <input 
                        type="datetime-local" 
                        name="published_at" 
                        value="{{ old('published_at') }}" 
                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Leave blank to publish immediately.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Expiry Date (Optional)
                    </label>
                    <input 
                        type="datetime-local" 
                        name="expires_at" 
                        value="{{ old('expires_at') }}" 
                        class="w-full text-xs rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white px-3 py-2 focus:ring-1 focus:ring-indigo-500"
                    >
                    <p class="text-[10px] text-slate-400 mt-1">Notice will be auto-archived after this timestamp.</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                Save &amp; Broadcast Announcement
            </button>
        </div>
    </form>
</div>
@endsection
