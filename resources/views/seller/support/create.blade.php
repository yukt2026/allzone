@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Open Support Ticket'])

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Page Header -->
    <x-page-header 
        title="Open Support Ticket" 
        subtitle="Submit a formal query or issue ticket to ALLZONE platform administrators."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Support Desk' => route('seller.support.index'), 'New Ticket' => '#']"
    />

    <form method="POST" action="{{ route('seller.support.store') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Subject / Summary *</label>
                <input 
                    type="text" 
                    name="subject" 
                    value="{{ old('subject') }}" 
                    required 
                    placeholder="Brief description of the query or issue"
                    class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                >
                @error('subject')
                    <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                    <select 
                        name="category" 
                        required 
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="payout" {{ old('category') === 'payout' ? 'selected' : '' }}>Payout / Settlements &amp; Commission</option>
                        <option value="order" {{ old('category') === 'order' ? 'selected' : '' }}>Order Fulfilment &amp; Customer Issues</option>
                        <option value="product" {{ old('category') === 'product' ? 'selected' : '' }}>Product Moderation &amp; Catalogue</option>
                        <option value="logistics" {{ old('category') === 'logistics' ? 'selected' : '' }}>Logistics, AWBs &amp; Courier Delivery</option>
                        <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Merchant Account, KYC &amp; Banking</option>
                        <option value="general" {{ old('category', 'general') === 'general' ? 'selected' : '' }}>General Inquiries</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Priority Level *</label>
                    <select 
                        name="priority" 
                        required 
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low - General question</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium - Normal priority</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High - Operational impact</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent - Critical blocker</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Associated Order / Consignment (Optional)</label>
                <select 
                    name="order_id" 
                    class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                >
                    <option value="">-- None / General Account Query --</option>
                    @foreach($recentOrders as $ro)
                        @if($ro->order)
                            <option value="{{ $ro->order->id }}" {{ old('order_id') == $ro->order->id ? 'selected' : '' }}>
                                Package #{{ $ro->fulfilment_order_number }} (Order #{{ $ro->order->order_number }}) - ₹{{ number_format((float)$ro->total, 2) }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Detailed Message / Description *</label>
                <textarea 
                    name="message" 
                    rows="6" 
                    required 
                    placeholder="Provide full details, order references, or questions for our support team..."
                    class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                >{{ old('message') }}</textarea>
                @error('message')
                    <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
            <a 
                href="{{ route('seller.support.index') }}" 
                class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
                Cancel
            </a>
            <button 
                type="submit" 
                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
            >
                Submit Ticket
            </button>
        </div>
    </form>
</div>
@endsection
