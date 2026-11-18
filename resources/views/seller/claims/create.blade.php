@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Raise Claim - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16" x-data="{
    searchQuery: '',
    searchResults: [],
    loading: false,
    selectedItem: {{ $prefillItem ? Js::from([
        'id' => $prefillItem->id,
        'title' => $prefillItem->title,
        'sku' => $prefillItem->sku,
        'total' => (float) $prefillItem->total,
        'order_number' => $prefillItem->fulfilmentOrder?->order?->order_number,
        'fulfilment_order_number' => $prefillItem->fulfilmentOrder?->fulfilment_order_number,
        'is_eligible' => true
    ]) : 'null' }},
    claimedAmount: {{ $prefillItem ? (float)$prefillItem->total : '0' }},

    searchOrders() {
        if (this.searchQuery.length < 3) return;
        this.loading = true;
        fetch('{{ route('seller.claims.search_order') }}?q=' + encodeURIComponent(this.searchQuery))
            .then(res => res.json())
            .then(data => {
                this.searchResults = data.items || [];
                this.loading = false;
            })
            .catch(() => { this.loading = false; });
    },

    selectItem(item) {
        if (!item.is_eligible) return;
        this.selectedItem = item;
        this.claimedAmount = item.total;
    }
}">
    <!-- Header -->
    <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
        <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
        <span>&gt;</span>
        <a href="{{ route('seller.claims.index') }}" class="hover:underline">Claims</a>
        <span>&gt;</span>
        <span class="text-slate-800 font-semibold">Raise Claim</span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-2">
                <a href="{{ route('seller.claims.index') }}" class="text-slate-400 hover:text-slate-700">&larr;</a>
                <span>Raise a Dispute Claim</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Submit an incident claim for damaged RTOs, wrong customer returns, or in-transit logistics discrepancies.
            </p>
        </div>

        <a href="{{ route('seller.claims.index') }}" class="text-xs text-slate-600 hover:underline">
            &larr; Cancel and return
        </a>
    </div>

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 font-semibold flex items-center space-x-2">
            <span>⚠️</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('seller.claims.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($prefillReturnId)
            <input type="hidden" name="order_return_id" value="{{ $prefillReturnId }}">
        @endif

        <!-- Step 1: Select Order Item -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-5 h-5 rounded-full bg-slate-900 text-white text-[11px] flex items-center justify-center font-bold">1</span>
                <span>Select Order Item to Claim</span>
            </h3>

            <!-- Search Bar -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Search by Order ID, Package # or SKU</label>
                <div class="relative">
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        @input.debounce.300ms="searchOrders()" 
                        placeholder="e.g. ORD-1234, FO-5678, or SKU..."
                        class="w-full py-2 pl-3 pr-10 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-600"
                    />
                    <div class="absolute right-3 top-2.5 text-slate-400">
                        <span x-show="loading" class="text-xs">Searching...</span>
                        <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Search Results Dropdown / Cards -->
            <div x-show="searchResults.length > 0" class="border border-slate-200 rounded-xl max-h-56 overflow-y-auto divide-y divide-slate-100 bg-slate-50/50">
                <template x-for="item in searchResults" :key="item.id">
                    <div 
                        @click="selectItem(item)" 
                        class="p-3 flex items-center justify-between hover:bg-white cursor-pointer transition text-xs"
                        :class="!item.is_eligible ? 'opacity-50 cursor-not-allowed bg-slate-100' : ''"
                    >
                        <div class="flex items-center space-x-3">
                            <img :src="item.image_url" class="w-10 h-10 rounded-lg object-cover bg-slate-200 border border-slate-300">
                            <div>
                                <div class="font-bold text-slate-900" x-text="item.title"></div>
                                <div class="text-[10px] text-slate-500 flex items-center space-x-2">
                                    <span x-text="'SKU: ' + item.sku"></span>
                                    <span>&bull;</span>
                                    <span class="font-mono" x-text="item.order_number"></span>
                                    <span>&bull;</span>
                                    <span x-text="'Qty: ' + item.qty"></span>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="font-bold text-slate-900" x-text="'₹' + item.total"></div>
                            <template x-if="item.is_eligible">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Eligible &rarr;
                                </span>
                            </template>
                            <template x-if="!item.is_eligible">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" x-text="item.ineligibility_reason"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Selected Item Card -->
            <div x-show="selectedItem" class="p-4 bg-indigo-50/60 border border-indigo-200 rounded-xl flex items-center justify-between text-xs">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">📦</span>
                    <div>
                        <div class="font-extrabold text-indigo-950" x-text="selectedItem ? selectedItem.title : ''"></div>
                        <div class="text-[11px] text-indigo-800 flex items-center space-x-2 mt-0.5 font-mono">
                            <span x-text="'Order: ' + (selectedItem ? selectedItem.order_number : '')"></span>
                            <span>&bull;</span>
                            <span x-text="'Package: ' + (selectedItem ? selectedItem.fulfilment_order_number : '')"></span>
                            <span>&bull;</span>
                            <span x-text="'SKU: ' + (selectedItem ? selectedItem.sku : '')"></span>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-[10px] text-indigo-600 font-semibold uppercase">Item Value</div>
                    <div class="font-black text-slate-900 text-sm" x-text="'₹' + (selectedItem ? selectedItem.total : 0)"></div>
                </div>
            </div>

            <input type="hidden" name="order_item_id" :value="selectedItem ? selectedItem.id : ''" required>
        </div>

        <!-- Step 2: Claim Issue & Category -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-5 h-5 rounded-full bg-slate-900 text-white text-[11px] flex items-center justify-center font-bold">2</span>
                <span>Claim Classification &amp; Details</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Claim Category *</label>
                    <select 
                        name="claim_type" 
                        required 
                        class="w-full p-2.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-indigo-600"
                    >
                        <option value="">Select Category...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Claimed Compensation (₹) *</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="1" 
                        name="claimed_amount" 
                        x-model="claimedAmount" 
                        required
                        class="w-full p-2.5 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:border-indigo-600"
                    />
                    <p class="text-[10px] text-slate-400 mt-0.5">Maximum claimable amount is the order item total.</p>
                </div>
            </div>

            <div class="text-xs">
                <label class="block font-bold text-slate-700 mb-1">Subject / Summary of Issue *</label>
                <input 
                    type="text" 
                    name="reason" 
                    required 
                    placeholder="e.g. Received damaged parcel with torn outer packaging and missing contents"
                    class="w-full p-2.5 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-600"
                />
            </div>

            <div class="text-xs">
                <label class="block font-bold text-slate-700 mb-1">Detailed Description of Incident *</label>
                <textarea 
                    name="description" 
                    rows="4" 
                    required 
                    placeholder="Provide specific details: delivery date, condition of parcel upon carrier handover, tamper marks, mismatched serial numbers, or notes from courier..."
                    class="w-full p-2.5 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-600 leading-relaxed"
                ></textarea>
            </div>
        </div>

        <!-- Step 3: Supporting Proof & Evidence -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                <span class="w-5 h-5 rounded-full bg-slate-900 text-white text-[11px] flex items-center justify-center font-bold">3</span>
                <span>Supporting Proof &amp; Documentation</span>
            </h3>

            <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1">
                <strong>Mandatory Evidence Guidelines:</strong>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-amber-800">
                    <li>Clear photograph of the shipping label with visible AWB barcode.</li>
                    <li>Unboxing video or photos showing outer damage, torn security seal, or incorrect item.</li>
                    <li>Carrier Proof of Delivery (POD) endorsed with tamper remarks, if available.</li>
                </ul>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Upload Evidence (Images, Documents, MP4 - Up to 5 files)</label>
                <input 
                    type="file" 
                    name="evidence_files[]" 
                    multiple 
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4" 
                    class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-300 rounded-lg p-2"
                />
            </div>
        </div>

        <!-- Step 4: Policy Acknowledgment & Submission -->
        <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
            <div class="flex items-start space-x-3 text-xs text-slate-600">
                <input type="checkbox" required id="policy_ack" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="policy_ack" class="cursor-pointer">
                    I confirm that the submitted information and evidence are authentic and correspond directly to the selected order. I understand that claims are reviewed by the platform administration team in accordance with ALLZONE claim policies, and reimbursement adjustments are issued strictly upon formal verification.
                </label>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('seller.claims.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Cancel
                </a>
                <button 
                    type="submit" 
                    :disabled="!selectedItem"
                    class="px-5 py-2.5 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition disabled:opacity-50"
                >
                    Submit Claim for Verification &rarr;
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
