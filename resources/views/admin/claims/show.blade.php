@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Review Claim #' . $claim->claim_number])

@section('content')
@php
    $item = $claim->orderItem;
    $order = $claim->order;
    $fulfilment = $claim->fulfilmentOrder;
    $shipment = $fulfilment?->shipment;
    $seller = $claim->seller;
    $customer = $order?->customer?->user ?? $order?->user;
@endphp

<div class="space-y-6" x-data="{
    selectedEvidence: null,
    escalateModalOpen: false,
    closeModalOpen: false,
    rejectModalOpen: false,
    approveModalOpen: false,
    requestInfoModalOpen: false
}">
    <!-- Header -->
    <x-page-header 
        title="Claim #{{ $claim->claim_number }}" 
        subtitle="Adjudication console for merchant courier damage, transit loss, RTO discrepancies, and ledger compensation settlements."
        :breadcrumbs="[
            'Admin Central' => route('admin.dashboard'), 
            'Claims & Disputes' => route('admin.claims.index'), 
            $claim->claim_number => '#'
        ]"
    >
        <x-slot name="badge">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $claim->status->badgeClasses() }}">
                    {{ $claim->status->label() }}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700">
                    {{ $claim->claim_type?->label() }}
                </span>
            </div>
        </x-slot>

        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="outline" size="sm" href="{{ route('admin.claims.index') }}">
                    &larr; Back to Claims
                </x-button>

                @if($claim->status !== \App\Enums\ClaimStatus::APPROVED && $claim->status !== \App\Enums\ClaimStatus::REJECTED && $claim->status !== \App\Enums\ClaimStatus::CLOSED)
                    <button 
                        type="button" 
                        @click="approveModalOpen = true" 
                        class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition"
                    >
                        ✓ Authorize Payout
                    </button>
                    <button 
                        type="button" 
                        @click="requestInfoModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-bold text-xs transition"
                    >
                        💬 Request Proof
                    </button>
                    <button 
                        type="button" 
                        @click="escalateModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-bold text-xs transition"
                    >
                        ⚠️ Escalate Dispute
                    </button>
                    <button 
                        type="button" 
                        @click="rejectModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold text-xs transition"
                    >
                        ✕ Reject
                    </button>
                    <button 
                        type="button" 
                        @click="closeModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition"
                    >
                        Close Claim
                    </button>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    <!-- Administrative Notes Banner -->
    @if($claim->admin_notes)
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200">
            <div class="flex items-center space-x-2 font-bold text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Administrative Notes &amp; Escalation Logs</span>
            </div>
            <div class="mt-1 text-xs text-amber-800 dark:text-amber-300 font-mono whitespace-pre-wrap">{{ $claim->admin_notes }}</div>
        </div>
    @endif

    <!-- 2 Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Details, Evidence, Messages -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Item & Consignment Overview -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Product &amp; Package Verification
                    </h3>
                    <span class="text-xs uppercase font-bold tracking-wider px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Category: {{ $claim->claim_type?->label() }}
                    </span>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-4 items-start">
                    @if($item?->variant?->image_url || $item?->variant?->product?->primary_image_url)
                        <img 
                            src="{{ $item->variant?->image_url ?? $item->variant?->product?->primary_image_url }}" 
                            alt="" 
                            class="w-20 h-20 rounded-xl object-cover bg-slate-100 border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0"
                        />
                    @else
                        <div class="w-20 h-20 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-2xl shrink-0">
                            📦
                        </div>
                    @endif

                    <div class="flex-1 space-y-1 text-xs">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                            {{ $item?->title ?? 'Product Consignment' }}
                        </h4>
                        <div class="text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono">
                            <span>SKU: <strong class="text-slate-800 dark:text-slate-200">{{ $item?->sku ?? 'N/A' }}</strong></span>
                            <span>&bull;</span>
                            <span>Claimed Qty: <strong class="text-slate-800 dark:text-slate-200">{{ $item?->qty ?? 1 }}</strong></span>
                            <span>&bull;</span>
                            <span>Line Total: <strong class="text-slate-900 dark:text-slate-100">₹{{ number_format((float)($item?->total ?? 0), 2) }}</strong></span>
                        </div>
                        <div class="text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 font-mono">
                            <span>Order #: 
                                @if($order)
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-emerald-600 hover:underline font-bold">
                                        {{ $order->order_number }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </span>
                            @if($fulfilment)
                                <span>&bull;</span>
                                <span>Suborder #: <strong class="text-slate-800 dark:text-slate-200">{{ $fulfilment->fulfilment_order_number }}</strong></span>
                            @endif
                            @if($shipment?->awb)
                                <span>&bull;</span>
                                <span>Courier AWB: <strong class="text-slate-800 dark:text-slate-200">{{ $shipment->awb }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Claim Issue Description -->
            <x-card>
                <div class="pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Merchant Statement &amp; Reason
                    </h3>
                </div>

                <div class="mt-4 space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold uppercase text-[10px] tracking-wider">Primary Issue Title:</span>
                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                            {{ $claim->reason }}
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-semibold uppercase text-[10px] tracking-wider">Merchant Detailed Explanation:</span>
                        <div class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 mt-1">
                            {{ $claim->description }}
                        </div>
                    </div>

                    @if($claim->rejection_reason)
                        <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200">
                            <span class="font-bold text-[11px] uppercase tracking-wider block">Official Rejection Reason:</span>
                            <p class="mt-1 leading-relaxed">{{ $claim->rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Evidence Gallery with Lightbox -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Uploaded Evidence Files
                    </h3>
                    <span class="text-xs text-slate-400">
                        {{ !empty($claim->evidence_urls) ? count($claim->evidence_urls) . ' files attached' : '0 files' }}
                    </span>
                </div>

                <div class="mt-4">
                    @if(empty($claim->evidence_urls))
                        <div class="text-slate-400 text-xs py-6 text-center">
                            No evidence files attached to this claim.
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($claim->evidence_urls as $idx => $url)
                                @php 
                                    $fileUrl = str_starts_with($url, 'http') ? $url : Storage::url($url);
                                    $isImage = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $url); 
                                @endphp
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800 group relative">
                                    @if($isImage)
                                        <button 
                                            type="button" 
                                            @click="selectedEvidence = '{{ $fileUrl }}'"
                                            class="block aspect-square w-full relative focus:outline-none"
                                        >
                                            <img src="{{ $fileUrl }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                                🔍 Zoom
                                            </div>
                                        </button>
                                    @else
                                        <a href="{{ $fileUrl }}" target="_blank" class="aspect-square flex flex-col items-center justify-center p-3 text-center hover:bg-slate-100 dark:hover:bg-slate-700">
                                            <span class="text-2xl mb-1">📄</span>
                                            <span class="text-[10px] text-slate-700 dark:text-slate-200 font-bold truncate max-w-full">Document #{{ $idx + 1 }}</span>
                                            <span class="text-[9px] text-emerald-600 font-semibold mt-1">Open File &rarr;</span>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Message & Activity History -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Audit Trail &amp; Adjudication Messages
                    </h3>
                    <span class="text-xs text-slate-400">
                        {{ $claim->messages->count() }} interactions recorded
                    </span>
                </div>

                <div class="mt-4 space-y-3.5 text-xs">
                    @forelse($claim->messages as $msg)
                        <div class="p-3.5 rounded-xl border {{ $msg->is_internal ? 'bg-amber-50/70 border-amber-200 text-amber-950 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-200' : 'bg-slate-50 border-slate-200 text-slate-900 dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-200' }} space-y-1.5">
                            <div class="flex items-center justify-between text-[10px]">
                                <span class="font-bold flex items-center space-x-1.5">
                                    <span>{{ $msg->user?->name ?? 'Staff Operator' }}</span>
                                    @if($msg->is_internal)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 font-black text-[9px] dark:bg-amber-800 dark:text-amber-100">
                                            INTERNAL STAFF NOTE
                                        </span>
                                    @endif
                                </span>
                                <span class="text-slate-400 font-mono">{{ $msg->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <p class="leading-relaxed whitespace-pre-line">{{ $msg->message }}</p>
                            @if(!empty($msg->attachments))
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @foreach($msg->attachments as $att)
                                        <a href="{{ str_starts_with($att, 'http') ? $att : Storage::url($att) }}" target="_blank" class="text-[10px] text-emerald-600 hover:underline flex items-center space-x-1">
                                            <span>📎</span>
                                            <span>Attachment</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-slate-400 text-center py-6 text-xs">
                            No messages or interactions recorded on this claim yet.
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Right Col: Financial Settlement & Adjudication Console -->
        <div class="space-y-6">
            <!-- Merchant Profile Snapshot -->
            <x-card>
                <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 pb-3 border-b border-slate-200 dark:border-slate-800">
                    Merchant &amp; Customer Snapshot
                </h3>

                <div class="mt-3.5 space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Merchant / Claimant:</span>
                        <div class="font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                            @if($seller)
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="text-emerald-600 hover:underline">
                                    {{ $seller->business_name ?? $seller->legal_name }}
                                </a>
                            @else
                                Platform Direct
                            @endif
                        </div>
                        <div class="text-slate-500 font-mono text-[11px]">{{ $seller?->user?->email }}</div>
                    </div>

                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Customer / Buyer:</span>
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mt-0.5">
                            {{ $customer?->name ?? 'Guest Buyer' }}
                        </div>
                        <div class="text-slate-500 font-mono text-[11px]">{{ $customer?->email }}</div>
                    </div>

                    @if($shipment)
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Shiprocket Cargo Waybill:</span>
                            <div class="font-mono text-emerald-600 font-bold mt-0.5">
                                {{ $shipment->awb ?? 'Pending Waybill' }} ({{ $shipment->status?->label() ?? $shipment->status }})
                            </div>
                            <div class="text-slate-500 text-[11px]">{{ $shipment->courier_name ?? 'Shiprocket Logistics' }}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Financial Settlement Card -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Financial Impact &amp; Ledger
                    </h3>
                    <span class="text-xs font-bold uppercase px-2 py-0.5 rounded {{ $claim->status === \App\Enums\ClaimStatus::APPROVED ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                        {{ $claim->status->label() }}
                    </span>
                </div>

                <div class="mt-3.5 space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Claimed Amount:</span>
                        <span class="font-extrabold text-slate-900 dark:text-slate-100 text-base">
                            ₹{{ number_format((float)$claim->claimed_amount, 2) }}
                        </span>
                    </div>

                    @if($claim->status === \App\Enums\ClaimStatus::APPROVED)
                        <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span>Authorized Payout:</span>
                            <span class="text-base font-black">₹{{ number_format((float)$claim->approved_amount, 2) }}</span>
                        </div>
                        <div class="space-y-1 text-[11px] font-mono text-slate-500 pt-1">
                            <div>Reference: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $claim->compensation_reference }}</span></div>
                            <div>Compensated At: <span class="text-slate-700 dark:text-slate-300">{{ $claim->compensated_at?->format('d M Y, h:i A') }}</span></div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Add Internal Staff Note -->
            <x-card>
                <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 pb-2 border-b border-slate-200 dark:border-slate-800">
                    Internal Staff Note
                </h3>
                <p class="text-[11px] text-slate-400 mt-1 mb-3">
                    Internal notes are strictly confidential and visible ONLY to platform staff.
                </p>

                <form method="POST" action="{{ route('admin.claims.internal_note', $claim) }}" class="space-y-3">
                    @csrf
                    <textarea 
                        name="note" 
                        rows="3" 
                        required 
                        placeholder="Carrier investigation findings, logistics tickets, or internal adjudication notes..."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 focus:ring-emerald-500"
                    ></textarea>
                    <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 font-bold text-xs rounded-xl transition">
                        Save Internal Note
                    </button>
                </form>
            </x-card>
        </div>
    </div>

    <!-- MODAL: Authorize Payout / Approve Claim -->
    <div 
        x-show="approveModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="approveModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-emerald-600 dark:text-emerald-400">Authorize Compensation Payout</h3>
            <p class="text-xs text-slate-500 mt-1">
                Approving Claim #{{ $claim->claim_number }} immediately credits the merchant wallet ledger.
            </p>

            <form method="POST" action="{{ route('admin.claims.approve', $claim) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Approved Payout Amount (₹) *</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        max="{{ $claim->claimed_amount }}"
                        name="approved_amount" 
                        value="{{ $claim->claimed_amount }}"
                        required 
                        class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    />
                    <span class="text-[10px] text-slate-400">Maximum claimed amount: ₹{{ number_format((float)$claim->claimed_amount, 2) }}</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Approval Notes / Carrier Reference</label>
                    <textarea 
                        name="approval_notes" 
                        rows="2" 
                        placeholder="e.g. Approved after Shiprocket damage inspection report."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="approveModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                        Confirm &amp; Disburse
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Request More Information -->
    <div 
        x-show="requestInfoModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="requestInfoModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-indigo-600 dark:text-indigo-400">Request Documentation from Merchant</h3>
            <p class="text-xs text-slate-500 mt-1">
                Updates status to "More Info Needed" and notifies the merchant.
            </p>

            <form method="POST" action="{{ route('admin.claims.request_info', $claim) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Required Evidence Instructions *</label>
                    <textarea 
                        name="instructions" 
                        rows="3" 
                        required 
                        placeholder="Please provide unboxing video proof, barcode close-up, or carrier courier delivery slip..."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="requestInfoModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs">
                        Send Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Escalate Dispute -->
    <div 
        x-show="escalateModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="escalateModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-amber-600 dark:text-amber-400">Escalate Dispute to Senior Review</h3>
            <p class="text-xs text-slate-500 mt-1">
                Flags claim for senior carrier management review and marks status as "Under Review".
            </p>

            <form method="POST" action="{{ route('admin.claims.escalate', $claim) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Escalation Reason / Escalation Details *</label>
                    <textarea 
                        name="escalation_reason" 
                        rows="3" 
                        required 
                        placeholder="e.g. Discrepancy between carrier pod weight and package declared weight."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="escalateModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs">
                        Escalate Dispute
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Reject Claim -->
    <div 
        x-show="rejectModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="rejectModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-rose-600 dark:text-rose-400">Reject Seller Claim</h3>
            <p class="text-xs text-slate-500 mt-1">
                Disallows claim with customer/merchant visible justification.
            </p>

            <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rejection Reason (Visible to Merchant) *</label>
                    <textarea 
                        name="rejection_reason" 
                        rows="3" 
                        required 
                        placeholder="e.g. Claim filing deadline exceeded / Evidence does not show shipping label / Package intact upon delivery."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="rejectModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Close Claim -->
    <div 
        x-show="closeModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="closeModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Close Dispute Thread</h3>
            <p class="text-xs text-slate-500 mt-1">
                Formally closes this claim ticket after dispute resolution.
            </p>

            <form method="POST" action="{{ route('admin.claims.close', $claim) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Closure Remarks</label>
                    <textarea 
                        name="close_reason" 
                        rows="2" 
                        placeholder="e.g. Resolved via direct merchant phone settlement."
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    ></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="closeModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 shadow-xs">
                        Close Claim
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Zoom Modal -->
    <div 
        x-show="selectedEvidence" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
    >
        <div @click.away="selectedEvidence = null" class="relative max-w-3xl max-h-[85vh] bg-transparent">
            <button @click="selectedEvidence = null" class="absolute -top-10 right-0 text-white hover:text-slate-300 font-bold text-sm bg-slate-800/80 px-3 py-1 rounded-full">
                ✕ Close
            </button>
            <img :src="selectedEvidence" alt="Zoomed evidence" class="rounded-2xl shadow-2xl max-w-full max-h-[80vh] object-contain border border-white/10">
        </div>
    </div>
</div>
@endsection
