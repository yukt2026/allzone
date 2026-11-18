@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'RMA Claim #' . $return->return_number])

@section('content')
@php
    $item = $return->orderItem;
    $fulfilment = $item?->fulfilmentOrder;
    $order = $fulfilment?->order;
    $customer = $order?->customer?->user ?? $order?->user;
    $seller = $fulfilment?->seller;
    $payment = $order?->payment;
    $isCod = $payment ? $payment->isCod() : ($order?->payment_method === 'cod');
    $refundRecord = $return->refund;
@endphp

<div class="space-y-6" x-data="{
    approveModalOpen: false,
    rejectModalOpen: false,
    requestInfoModalOpen: false,
    refundModalOpen: false,
    qcModalOpen: false,
    statusModalOpen: false,
    selectedPhoto: null,
    refundMode: '{{ $isCod ? 'wallet' : 'original' }}'
}">
    <!-- Header -->
    <x-page-header 
        title="Return Claim #{{ $return->return_number }}" 
        subtitle="Dispute resolution console, reverse shipment tracking, warehouse QC inspection, and verified refund settlement."
        :breadcrumbs="[
            'Admin Central' => route('admin.dashboard'), 
            'Returns & Refunds' => route('admin.returns.index'), 
            $return->return_number => '#'
        ]"
    >
        <x-slot name="badge">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $return->status->badgeClass() }}">
                    {{ $return->status->label() }}
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $return->qc_status === \App\Enums\QCStatus::PASSED ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : ($return->qc_status === \App\Enums\QCStatus::FAILED ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300') }}">
                    QC: {{ $return->qc_status->label() }}
                </span>
            </div>
        </x-slot>

        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                <x-button variant="outline" size="sm" href="{{ route('admin.returns.index') }}">
                    &larr; Back to Registry
                </x-button>

                @if($return->status === \App\Enums\ReturnStatus::REQUESTED)
                    <button 
                        type="button" 
                        @click="approveModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition"
                    >
                        ✓ Approve Claim
                    </button>
                    <button 
                        type="button" 
                        @click="rejectModalOpen = true" 
                        class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition"
                    >
                        ✕ Reject Claim
                    </button>
                @endif

                <button 
                    type="button" 
                    @click="requestInfoModalOpen = true" 
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-xs transition"
                >
                    💬 Request Info
                </button>

                <button 
                    type="button" 
                    @click="qcModalOpen = true" 
                    class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800 font-bold text-xs transition"
                >
                    🔍 Record QC
                </button>

                @if($return->status !== \App\Enums\ReturnStatus::REFUNDED && $return->status !== \App\Enums\ReturnStatus::REJECTED)
                    <button 
                        type="button" 
                        @click="refundModalOpen = true" 
                        class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center space-x-1"
                    >
                        <span>⚡ Initiate Refund</span>
                    </button>
                @endif

                <button 
                    type="button" 
                    @click="statusModalOpen = true" 
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition"
                >
                    ⚙ Update Status
                </button>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Administrative Notes Banner -->
    @if($return->admin_notes)
        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200">
            <div class="flex items-center space-x-2 font-bold text-xs uppercase tracking-wider">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Administrative Notes &amp; Intervention Log</span>
            </div>
            <div class="mt-1 text-xs text-amber-800 dark:text-amber-300 font-mono whitespace-pre-wrap">{{ $return->admin_notes }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Item Details & Claim -->
            <x-card>
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 flex items-center space-x-2">
                        <span>Claim Details &amp; Product Inspection</span>
                    </h3>
                    <span class="text-xs uppercase font-bold tracking-wider px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                        Type: {{ strtoupper($return->type ?? 'RETURN') }}
                    </span>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-4 items-start">
                    @if($item?->variant?->image_url || $item?->product?->image_url)
                        <img src="{{ $item->variant?->image_url ?? $item->product?->image_url }}" alt="" class="w-20 h-20 object-cover rounded-xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    @else
                        <div class="w-20 h-20 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-2xl border border-slate-200 dark:border-slate-700">
                            📦
                        </div>
                    @endif

                    <div class="flex-1 space-y-1">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                            {{ $item?->title ?? 'Product Item' }}
                        </h4>
                        <div class="text-xs text-slate-500">
                            SKU: <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $item?->sku ?? 'N/A' }}</span>
                            @if($item?->variant_title)
                                &bull; Variant: <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $item->variant_title }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-700 dark:text-slate-300 pt-1">
                            Unit Price: <span class="font-semibold">{{ format_currency($item?->unit_price ?? 0) }}</span> 
                            &times; Returning Qty: <span class="font-bold text-slate-900 dark:text-slate-100">{{ $return->qty ?? $item?->qty ?? 1 }}</span>
                        </div>
                        <div class="text-sm font-black text-emerald-600 dark:text-emerald-400">
                            Claim Disbursable Value: {{ format_currency($return->refund_amount ?? $item?->total ?? 0) }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                    <div>
                        <span class="text-[11px] text-slate-400 uppercase font-bold tracking-wider">Customer Stated Return Reason:</span>
                        <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $return->reason }}
                        </div>
                    </div>

                    @if($return->customer_notes)
                        <div>
                            <span class="text-[11px] text-slate-400 uppercase font-bold tracking-wider">Customer Statement &amp; Remarks:</span>
                            <div class="mt-1 text-xs text-slate-700 dark:text-slate-300 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 leading-relaxed">
                                "{{ $return->customer_notes }}"
                            </div>
                        </div>
                    @endif

                    @if($return->seller_notes)
                        <div>
                            <span class="text-[11px] text-slate-400 uppercase font-bold tracking-wider">Seller Response &amp; Observations:</span>
                            <div class="mt-1 text-xs text-slate-700 dark:text-slate-300 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 leading-relaxed">
                                "{{ $return->seller_notes }}"
                            </div>
                        </div>
                    @endif

                    <!-- Uploaded Photos Gallery -->
                    @if(!empty($return->photos) && is_array($return->photos))
                        <div>
                            <span class="text-[11px] text-slate-400 uppercase font-bold tracking-wider block mb-2">Customer Evidence Media ({{ count($return->photos) }} files):</span>
                            <div class="flex flex-wrap gap-2.5">
                                @foreach($return->photos as $photo)
                                    @php
                                        $photoUrl = str_starts_with($photo, 'http') ? $photo : Storage::url($photo);
                                    @endphp
                                    <button 
                                        type="button" 
                                        @click="selectedPhoto = '{{ $photoUrl }}'" 
                                        class="group relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs"
                                    >
                                        <img src="{{ $photoUrl }}" alt="Return proof" class="w-full h-full object-cover group-hover:scale-105 transition">
                                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                            🔍 Zoom
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Shiprocket Reverse Logistics Consignment -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                            Reverse Logistics Consignment (Shiprocket)
                        </h3>
                        <p class="text-xs text-slate-500">Reverse courier movement from customer doorstep to vendor warehouse</p>
                    </div>
                    @if($return->pickup_waybill)
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800">
                            AWB: {{ $return->pickup_waybill }}
                        </span>
                    @else
                        <span class="text-xs text-amber-600 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded">
                            Pickup Not Booked
                        </span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Designated Carrier:</span>
                        <div class="font-bold text-slate-800 dark:text-slate-200 mt-1">Shiprocket Reverse Cargo</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Reverse Pickup AWB:</span>
                        <div class="font-mono font-bold text-slate-900 dark:text-slate-100 mt-1">
                            {{ $return->pickup_waybill ?? 'Pending Dispatch' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Warehouse Arrival Time:</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200 mt-1">
                            {{ $return->received_at ? $return->received_at->format('d M Y, h:i A') : 'In Transit / Pending Arrival' }}
                        </div>
                    </div>
                </div>

                @if($return->pickupShipment)
                    <div class="mt-4 p-3 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs">
                        <div class="flex items-center justify-between font-semibold text-slate-700 dark:text-slate-300">
                            <span>Shipment Ref: #{{ $return->pickupShipment->id }} ({{ $return->pickupShipment->courier_name ?? 'Shiprocket' }})</span>
                            <span class="font-mono text-emerald-600 uppercase">{{ $return->pickupShipment->status ?? 'ACTIVE' }}</span>
                        </div>
                        @if($return->pickupShipment->tracking_history)
                            <div class="mt-2 space-y-1">
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Latest Tracking Checkpoint:</div>
                                <div class="text-xs text-slate-600 dark:text-slate-300">
                                    {{ is_array($return->pickupShipment->tracking_history) ? json_encode(end($return->pickupShipment->tracking_history)) : $return->pickupShipment->tracking_history }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </x-card>

            <!-- QC Inspection Results -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                            Quality Control (QC) &amp; Inventory Condition
                        </h3>
                        <p class="text-xs text-slate-500">Physical item verification prior to refund settlement and stock restoration</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full border {{ $return->qc_status === \App\Enums\QCStatus::PASSED ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : ($return->qc_status === \App\Enums\QCStatus::FAILED ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300') }}">
                            {{ $return->qc_status->label() }}
                        </span>
                        <button 
                            type="button" 
                            @click="qcModalOpen = true" 
                            class="text-xs text-blue-600 hover:text-blue-700 font-bold underline"
                        >
                            Edit QC
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Restock Eligibility:</span>
                        <div class="font-bold mt-1 {{ $return->is_resellable ? 'text-emerald-600' : 'text-slate-500' }}">
                            {{ $return->is_resellable ? '✓ Resellable (Restocked)' : '✕ Non-Resellable (Damaged/Scrapped)' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Inspection Timestamp:</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200 mt-1">
                            {{ $return->qc_completed_at ? $return->qc_completed_at->format('d M Y, h:i A') : 'Pending Inspection' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                        <span class="text-slate-400 font-semibold">Warehouse Findings:</span>
                        <div class="text-slate-700 dark:text-slate-300 mt-1 italic">
                            {{ $return->qc_notes ?? 'No inspection notes recorded yet.' }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Sidebar (1 col) -->
        <div class="space-y-6">
            <!-- Merchant & Order Snapshot -->
            <x-card>
                <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 pb-3 border-b border-slate-200 dark:border-slate-800">
                    Order &amp; Merchant Identity
                </h3>

                <div class="mt-3 space-y-3.5 text-xs">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Parent Marketplace Order:</span>
                        <div class="font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                            @if($order)
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-emerald-600 hover:underline">
                                    #{{ $order->order_number }}
                                </a>
                            @else
                                <span class="text-slate-400">N/A</span>
                            @endif
                        </div>
                    </div>

                    @if($fulfilment)
                        <div>
                            <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Sub-Order / Fulfilment ID:</span>
                            <div class="font-mono text-slate-800 dark:text-slate-200 font-bold mt-0.5">
                                #{{ $fulfilment->id }} ({{ $fulfilment->status }})
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Buyer Account:</span>
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mt-0.5">
                            {{ $customer?->name ?? 'Guest / Buyer' }}
                        </div>
                        <div class="text-slate-500 font-mono text-[11px]">{{ $customer?->email ?? 'No email' }}</div>
                        @if($customer?->phone)
                            <div class="text-slate-500 text-[11px]">{{ $customer->phone }}</div>
                        @endif
                    </div>

                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Origin Merchant / Seller:</span>
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mt-0.5">
                            @if($seller)
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="text-emerald-600 hover:underline">
                                    {{ $seller->business_name ?? $seller->legal_name ?? 'Seller #' . $seller->id }}
                                </a>
                            @else
                                Platform Direct Store
                            @endif
                        </div>
                    </div>

                    @if($order?->shippingAddress)
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider">Customer Return Pickup Address:</span>
                            <div class="text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">
                                {{ $order->shippingAddress->address_line1 }}, 
                                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} - {{ $order->shippingAddress->postal_code }}
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Financial Settlement & Refund Ledger -->
            <x-card>
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100">
                        Financial Settlement
                    </h3>
                    <span class="text-xs font-bold uppercase px-2 py-0.5 rounded {{ $isCod ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                        {{ $isCod ? 'COD (Cash)' : 'Online (Prepaid)' }}
                    </span>
                </div>

                <div class="mt-3 space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Refund Entitlement:</span>
                        <span class="font-black text-slate-900 dark:text-slate-100">
                            {{ format_currency($return->refund_amount ?? $item?->total ?? 0) }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">Commission Reversal:</span>
                        <span class="font-semibold text-emerald-600">
                            Automatic on Settlement
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-500">Payment Gateway:</span>
                        <span class="font-mono text-slate-800 dark:text-slate-200">
                            {{ $payment?->gateway ?? ($isCod ? 'MANUAL_CASH' : 'RAZORPAY') }}
                        </span>
                    </div>

                    @if($payment?->transaction_id)
                        <div class="flex justify-between">
                            <span class="text-slate-500">Original Gateway TXN:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[130px]">
                                {{ $payment->transaction_id }}
                            </span>
                        </div>
                    @endif

                    @if($refundRecord)
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Refund Status:</span>
                                <span class="font-bold text-emerald-600 uppercase">{{ $refundRecord->status }}</span>
                            </div>
                            @if($refundRecord->gateway_refund_id)
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Gateway Ref:</span>
                                    <span class="font-mono text-emerald-700 font-semibold truncate max-w-[140px]">{{ $refundRecord->gateway_refund_id }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($return->refunded_at)
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-slate-400">Refund Disbursed On:</span>
                            <div class="font-bold text-emerald-600 mt-0.5">
                                {{ $return->refunded_at->format('d M Y, h:i A') }}
                            </div>
                        </div>
                    @else
                        <div class="pt-3">
                            <button 
                                type="button" 
                                @click="refundModalOpen = true" 
                                class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition text-center"
                            >
                                ⚡ Disburse Refund Now
                            </button>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <!-- MODAL: Approve Return Claim -->
    <div 
        x-show="approveModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="approveModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Approve Return Claim</h3>
            <p class="text-xs text-slate-500 mt-1">
                Approving claim #{{ $return->return_number }} marks it approved and optionally triggers Shiprocket reverse logistics.
            </p>

            <form method="POST" action="{{ route('admin.returns.approve', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="schedule_reverse_pickup" id="schedule_reverse" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="schedule_reverse" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                        Automatically generate Shiprocket reverse courier pickup order
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Approval Remarks / Administrative Notes</label>
                    <textarea name="remarks" rows="3" placeholder="Claim verified against policy. Generating return docket." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 focus:ring-emerald-500"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="approveModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                        Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Reject Return Claim -->
    <div 
        x-show="rejectModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="rejectModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-rose-600">Reject Return Claim</h3>
            <p class="text-xs text-slate-500 mt-1">
                Disallowing claim #{{ $return->return_number }} closes the RMA and notifies the customer with mandatory justification.
            </p>

            <form method="POST" action="{{ route('admin.returns.reject', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rejection Reason (Visible to Customer &amp; Merchant) *</label>
                    <textarea name="rejection_reason" required rows="3" placeholder="e.g. Return window expired / physical damage caused by user / missing original packaging." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 focus:ring-rose-500"></textarea>
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

    <!-- MODAL: Request Additional Info -->
    <div 
        x-show="requestInfoModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="requestInfoModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Request Information / Evidence</h3>
            <p class="text-xs text-slate-500 mt-1">
                Sends a request to the customer/merchant for additional photos, unboxing video, or serial barcode details.
            </p>

            <form method="POST" action="{{ route('admin.returns.request_info', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Information Required *</label>
                    <textarea name="message" required rows="3" placeholder="Please upload clear photos showing the product serial number and intact security seal..." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="requestInfoModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">
                        Transmit Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Record Quality Control Inspection -->
    <div 
        x-show="qcModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="qcModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Record Quality Control (QC) Outcome</h3>
            <p class="text-xs text-slate-500 mt-1">
                Log physical inspection status and determine inventory restocking eligibility.
            </p>

            <form method="POST" action="{{ route('admin.returns.record_qc', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">QC Inspection Verdict *</label>
                    <select name="qc_status" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        <option value="passed" {{ $return->qc_status === \App\Enums\QCStatus::PASSED ? 'selected' : '' }}>Passed (Item conforms to claim / returned in acceptable state)</option>
                        <option value="failed" {{ $return->qc_status === \App\Enums\QCStatus::FAILED ? 'selected' : '' }}>Failed (Tampered / missing accessories / customer-damaged)</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_resellable" id="is_resellable_modal" value="1" {{ $return->is_resellable ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_resellable_modal" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                        Physical item is resellable (Restore inventory upon refund)
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Inspection Notes / Barcode Check</label>
                    <textarea name="qc_notes" rows="3" placeholder="Condition details, packaging seal check, accessory checklist..." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">{{ $return->qc_notes }}</textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="qcModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs">
                        Save QC Findings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Update Return Status -->
    <div 
        x-show="statusModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="statusModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-100">Update Return Status</h3>
            <p class="text-xs text-slate-500 mt-1">
                Advance or adjust the return lifecycle stage manually with full audit logging.
            </p>

            <form method="POST" action="{{ route('admin.returns.update_status', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Status *</label>
                    <select name="status" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        <option value="requested" {{ $return->status === \App\Enums\ReturnStatus::REQUESTED ? 'selected' : '' }}>Requested</option>
                        <option value="approved" {{ $return->status === \App\Enums\ReturnStatus::APPROVED ? 'selected' : '' }}>Approved</option>
                        <option value="pickup_scheduled" {{ ($return->status === \App\Enums\ReturnStatus::PICKUP_SCHEDULED || $return->status === \App\Enums\ReturnStatus::REVERSE_PICKUP_SCHEDULED) ? 'selected' : '' }}>Pickup Scheduled</option>
                        <option value="picked_up" {{ $return->status === \App\Enums\ReturnStatus::PICKED_UP ? 'selected' : '' }}>Item Picked Up (In Transit)</option>
                        <option value="received" {{ $return->status === \App\Enums\ReturnStatus::RECEIVED ? 'selected' : '' }}>Received at Hub</option>
                        <option value="qc_passed" {{ $return->status === \App\Enums\ReturnStatus::QC_PASSED ? 'selected' : '' }}>QC Passed</option>
                        <option value="qc_failed" {{ $return->status === \App\Enums\ReturnStatus::QC_FAILED ? 'selected' : '' }}>QC Failed</option>
                        <option value="refunded" {{ $return->status === \App\Enums\ReturnStatus::REFUNDED ? 'selected' : '' }}>Refunded</option>
                        <option value="rejected" {{ $return->status === \App\Enums\ReturnStatus::REJECTED ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ $return->status === \App\Enums\ReturnStatus::CANCELLED ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Administrative Reason / Transition Notes *</label>
                    <textarea name="notes" required rows="2" placeholder="Reason for manual status change..." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="statusModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Initiate / Disburse Refund -->
    <div 
        x-show="refundModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div @click.away="refundModalOpen = false" class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-base text-emerald-600 dark:text-emerald-400">Initiate Financial Refund</h3>
            <p class="text-xs text-slate-500 mt-1">
                Disburse verified refund for Claim #{{ $return->return_number }}.
            </p>

            @if($isCod)
                <!-- COD Safety Rule Notice -->
                <div class="mt-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-xs">
                    <span class="font-bold">⚠️ COD Order Detected:</span>
                    <p class="mt-0.5">
                        Cash on Delivery orders must follow a verified manual payout or store wallet credit. This transaction will <strong>NEVER</strong> be routed to Razorpay.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.returns.initiate_refund', $return) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Refund Amount (Default full value: {{ format_currency($return->refund_amount ?? $item?->total ?? 0) }})</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="amount" 
                        value="{{ $return->refund_amount ?? $item?->total ?? 0 }}"
                        class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5 font-bold"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Disbursement Channel *</label>
                    <select name="refund_mode" x-model="refundMode" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        @if(!$isCod)
                            <option value="original">Razorpay / Original Payment Source</option>
                        @endif
                        <option value="wallet">Customer Store Wallet</option>
                        <option value="bank_transfer">Verified Bank Transfer (NEFT/IMPS)</option>
                    </select>
                </div>

                <!-- COD / Bank Reference field -->
                <div x-show="refundMode === 'bank_transfer' || {{ $isCod ? 'true' : 'false' }}">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Bank Transfer Reference / UTR Number 
                        @if($isCod) <span class="text-rose-500">*</span> @endif
                    </label>
                    <input 
                        type="text" 
                        name="cod_payout_reference" 
                        placeholder="e.g. UTR-20261118-994821 or Bank Txn Ref"
                        class="w-full text-xs font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"
                    >
                    <span class="text-[10px] text-slate-400">Required for audited COD bank payouts</span>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="restock_inventory" id="restock_inventory_check" value="1" {{ $return->is_resellable ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="restock_inventory_check" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
                        Restore product inventory count (Based on QC resellable outcome)
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Administrative Notes / Ledger Justification *</label>
                    <textarea name="notes" required rows="2" placeholder="e.g. Return inspected and approved. Refunding to customer." class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                </div>

                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" @click="refundModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                        Execute Verified Refund
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Zoom Modal -->
    <div 
        x-show="selectedPhoto" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
    >
        <div @click.away="selectedPhoto = null" class="relative max-w-2xl max-h-[85vh] bg-transparent">
            <button @click="selectedPhoto = null" class="absolute -top-10 right-0 text-white hover:text-slate-300 font-bold text-sm bg-slate-800/80 px-3 py-1 rounded-full">
                ✕ Close
            </button>
            <img :src="selectedPhoto" alt="Zoomed view" class="rounded-2xl shadow-2xl max-w-full max-h-[80vh] object-contain border border-white/10">
        </div>
    </div>
</div>
@endsection
