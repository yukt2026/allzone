@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Raise Support Ticket - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16" x-data="{
    categories: {{ Js::from($categories) }},
    selectedCategoryId: '{{ old('support_category_id', $selectedCategory?->id ?? '') }}',
    selectedQuestionId: '{{ old('support_question_id', $selectedQuestionId ?? '') }}',
    get currentQuestions() {
        if (!this.selectedCategoryId) return [];
        let cat = this.categories.find(c => c.id == this.selectedCategoryId);
        return cat && cat.active_questions ? cat.active_questions : [];
    }
}">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Raise Support Ticket</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Submit an inquiry or report an operational issue to ALLZONE platform support.
            </p>
        </div>

        <a href="{{ route('seller.support.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
            &larr; Back to Support Desk
        </a>
    </div>

    <!-- Create Ticket Form -->
    <form action="{{ route('seller.support.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. Issue Category & Question -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                1. Select Topic &amp; Subcategory
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Issue Category <span class="text-rose-500">*</span>
                    </label>
                    <select name="support_category_id"
                            x-model="selectedCategoryId"
                            required
                            class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition @error('support_category_id') border-rose-500 @enderror">
                        <option value="">-- Choose Category --</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" :selected="cat.id == selectedCategoryId" x-text="cat.name"></option>
                        </template>
                    </select>
                    @error('support_category_id')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Specific Issue / Question
                    </label>
                    <select name="support_question_id"
                            x-model="selectedQuestionId"
                            class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                        <option value="">-- General Issue in this category --</option>
                        <template x-for="q in currentQuestions" :key="q.id">
                            <option :value="q.id" :selected="q.id == selectedQuestionId" x-text="q.question"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Related Item References -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                2. Related Order, Product or Shipment (Optional)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Related Order -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Related Marketplace Order
                    </label>
                    <select name="order_id" class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                        <option value="">-- None / General Inquiry --</option>
                        @foreach($recentOrders as $ro)
                            @if($ro->order)
                                <option value="{{ $ro->order->id }}" {{ old('order_id') == $ro->order->id ? 'selected' : '' }}>
                                    Package #{{ $ro->fulfilment_order_number }} &bull; Order #{{ $ro->order->order_number }} (₹{{ number_format((float)$ro->total, 2) }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Related Product -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Related Catalog Product
                    </label>
                    <select name="product_id" class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                        <option value="">-- None / Not Product Specific --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->title }} (SKU: {{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- AWB Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Shiprocket AWB / Courier Tracking Number
                    </label>
                    <input type="text"
                           name="awb_number"
                           value="{{ old('awb_number') }}"
                           placeholder="e.g. 14328901842"
                           class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-mono focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                </div>

                <!-- Callback Phone -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Callback Contact Number
                    </label>
                    <input type="text"
                           name="callback_phone"
                           value="{{ old('callback_phone', auth()->user()->phone ?? '') }}"
                           placeholder="e.g. +91 9876543210"
                           class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                </div>
            </div>
        </div>

        <!-- 3. Ticket Details & Description -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-2xs space-y-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                3. Subject &amp; Detailed Explanation
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Subject / Short Summary <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="subject"
                           value="{{ old('subject') }}"
                           required
                           placeholder="e.g. Courier missed scheduled pickup for consignment #FO-9821"
                           class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition @error('subject') border-rose-500 @enderror">
                    @error('subject')
                        <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Priority Level
                    </label>
                    <select name="priority" class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-2.5 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low - General question</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium - Standard priority</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High - Operational impact</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent - Critical blocker</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Detailed Message &amp; Symptoms <span class="text-rose-500">*</span>
                </label>
                <textarea name="message"
                          rows="6"
                          required
                          placeholder="Provide all relevant details: what occurred, when it happened, consignments impacted, and any steps already attempted..."
                          class="w-full text-xs rounded-lg border-slate-200 bg-slate-50 p-3 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition @error('message') border-rose-500 @enderror">{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Attachments -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Supporting Images / Documents (Optional)
                </label>
                <input type="file"
                       name="attachments[]"
                       multiple
                       accept="image/png,image/jpeg,image/webp,application/pdf,.docx,.zip"
                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-lg p-1.5">
                <p class="text-[10px] text-slate-400 mt-1">
                    Upload parcel photos, proof of delivery, weight slips, or invoices. Max 5 files (JPG, PNG, PDF, DOCX, ZIP up to 10MB each).
                </p>
                @error('attachments.*')
                    <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('seller.support.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition">
                Submit Support Ticket
            </button>
        </div>
    </form>
</div>
@endsection
