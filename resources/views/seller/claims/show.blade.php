@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Claim #' . $claim->claim_number . ' - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
        <a href="{{ route('seller.dashboard') }}" class="hover:underline">Dashboard</a>
        <span>&gt;</span>
        <a href="{{ route('seller.claims.index') }}" class="hover:underline">Claims</a>
        <span>&gt;</span>
        <span class="text-slate-800 font-semibold font-mono">{{ $claim->claim_number }}</span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight font-mono">
                    {{ $claim->claim_number }}
                </h1>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $claim->status->badgeClasses() }}">
                    {{ $claim->status->label() }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Raised on {{ $claim->created_at->format('d M Y, h:i A') }} &bull; Category: <strong class="text-slate-700">{{ $claim->claim_type?->label() }}</strong>
            </p>
        </div>

        <a 
            href="{{ route('seller.claims.index') }}" 
            class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs"
        >
            <span>&larr; Back to All Claims</span>
        </a>
    </div>

    <!-- Alerts based on status -->
    @if($claim->status === \App\Enums\ClaimStatus::MORE_INFO_NEEDED)
        <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-900 flex items-start space-x-3">
            <span class="text-lg">ℹ️</span>
            <div class="space-y-1">
                <h4 class="font-bold uppercase tracking-wider text-[11px] text-purple-950">Action Required: More Information Needed</h4>
                <p>The review team has requested additional clarification or evidence before evaluating compensation. Please scroll down to submit your response below.</p>
            </div>
        </div>
    @elseif($claim->status === \App\Enums\ClaimStatus::APPROVED)
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 flex items-start space-x-3">
            <span class="text-lg">🎉</span>
            <div class="space-y-1">
                <h4 class="font-bold uppercase tracking-wider text-[11px] text-emerald-950">Claim Approved &amp; Compensated</h4>
                <p>
                    Reimbursement of <strong>₹{{ number_format((float)$claim->approved_amount, 2) }}</strong> has been verified and credited to your seller ledger.
                    Reference: <span class="font-mono font-bold">{{ $claim->compensation_reference }}</span>
                </p>
            </div>
        </div>
    @elseif($claim->status === \App\Enums\ClaimStatus::REJECTED)
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 flex items-start space-x-3">
            <span class="text-lg">⚠️</span>
            <div class="space-y-1">
                <h4 class="font-bold uppercase tracking-wider text-[11px] text-rose-950">Claim Rejected</h4>
                <p><strong>Reason:</strong> {{ $claim->rejection_reason ?? 'Documentation did not meet verification criteria.' }}</p>
            </div>
        </div>
    @endif

    <!-- 2 Column Layout: Details Left / Timeline Right -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Details, Product, Evidence -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Product & Order Summary Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Associated Order &amp; Item</h3>

                @php $item = $claim->orderItem; @endphp
                <div class="flex items-start space-x-4">
                    <img 
                        src="{{ $item?->variant?->image_url ?? $item?->variant?->product?->primary_image_url ?? asset('images/placeholder.png') }}" 
                        alt="{{ $item?->title ?? 'Product' }}" 
                        class="w-16 h-16 rounded-xl object-cover bg-slate-100 border border-slate-200 shrink-0"
                        onerror="this.src='/images/placeholder.png'"
                    />
                    <div class="space-y-1 flex-1">
                        <h4 class="font-bold text-slate-900 text-sm">{{ $item?->title ?? 'Order Item' }}</h4>
                        <div class="text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono">
                            <span>SKU: <strong class="text-slate-800">{{ $item?->sku ?? 'N/A' }}</strong></span>
                            <span>&bull;</span>
                            <span>Qty: <strong class="text-slate-800">{{ $item?->qty ?? 1 }}</strong></span>
                            <span>&bull;</span>
                            <span>Item Total: <strong class="text-slate-900">₹{{ number_format((float)($item?->total ?? 0), 2) }}</strong></span>
                        </div>
                        <div class="text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1 pt-1 font-mono">
                            <span>Order #: <strong class="text-slate-800">{{ $claim->order?->order_number }}</strong></span>
                            <span>&bull;</span>
                            <span>Suborder: <strong class="text-slate-800">{{ $claim->fulfilmentOrder?->fulfilment_order_number }}</strong></span>
                            @if($claim->fulfilmentOrder?->shipment?->awb)
                                <span>&bull;</span>
                                <span>AWB: <strong class="text-slate-800">{{ $claim->fulfilmentOrder->shipment->awb }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claim Reason & Description -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Claim Description</h3>
                <div class="font-bold text-slate-900 text-sm">{{ $claim->reason }}</div>
                <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line bg-slate-50/60 p-3.5 rounded-lg border border-slate-100">
                    {{ $claim->description }}
                </p>
            </div>

            <!-- Evidence Files Gallery -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Supporting Proof &amp; Attachments</h3>

                @if(empty($claim->evidence_urls))
                    <div class="p-3 bg-slate-50 rounded-lg text-slate-400 text-xs text-center">
                        No initial photo or video attachments were uploaded with this claim.
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($claim->evidence_urls as $idx => $url)
                            @php $isImage = preg_match('/\.(jpg|jpeg|png|webp)$/i', $url); @endphp
                            <div class="group relative rounded-xl border border-slate-200 overflow-hidden bg-slate-50">
                                @if($isImage)
                                    <a href="{{ $url }}" target="_blank" class="block aspect-square">
                                        <img src="{{ $url }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                                    </a>
                                @else
                                    <a href="{{ $url }}" target="_blank" class="aspect-square flex flex-col items-center justify-center p-3 text-center hover:bg-slate-100 transition">
                                        <span class="text-2xl mb-1">📄</span>
                                        <span class="text-[10px] text-slate-600 font-bold truncate max-w-full">Evidence #{{ $idx + 1 }}</span>
                                        <span class="text-[9px] text-indigo-600 font-semibold mt-1">Open File &rarr;</span>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Provide Additional Information Form (when status is MORE_INFO_NEEDED) -->
            @if($claim->status === \App\Enums\ClaimStatus::MORE_INFO_NEEDED)
                <div class="bg-white rounded-xl border border-purple-200 p-5 shadow-2xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                        <span>💬</span>
                        <span>Provide Requested Information to Review Team</span>
                    </h3>

                    <form method="POST" action="{{ route('seller.claims.reply', $claim) }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                        @csrf
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Your Clarification / Response *</label>
                            <textarea 
                                name="message" 
                                rows="3" 
                                required 
                                placeholder="Explain details or address specific questions raised by the administrator..."
                                class="w-full p-2.5 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-indigo-600"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Attach Supplementary Files (Optional, Max 5 files)</label>
                            <input 
                                type="file" 
                                name="attachments[]" 
                                multiple 
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4" 
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 border border-slate-300 rounded-lg p-1.5"
                            />
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white font-bold rounded-lg text-xs transition">
                                Submit Clarification &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <!-- Right Col: Financial Overview & Timeline Stream -->
        <div class="space-y-6">
            <!-- Financial Claim Summary Box -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Financial Compensation</h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-100">
                        <span class="text-slate-500">Claimed Amount:</span>
                        <span class="font-bold text-slate-900 text-sm">₹{{ number_format((float)$claim->claimed_amount, 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-100">
                        <span class="text-slate-500">Approved Compensation:</span>
                        <span class="font-black {{ $claim->status === \App\Enums\ClaimStatus::APPROVED ? 'text-emerald-700 text-base' : 'text-slate-400 text-sm' }}">
                            {{ $claim->status === \App\Enums\ClaimStatus::APPROVED ? '₹' . number_format((float)$claim->approved_amount, 2) : 'Pending Review' }}
                        </span>
                    </div>

                    @if($claim->compensation_reference)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Credit Ref:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $claim->compensation_reference }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audit Trail & Message History -->
            <div class="bg-white rounded-xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Activity &amp; Message History</h3>

                <div class="space-y-4">
                    @forelse($claim->publicMessages as $msg)
                        <div class="relative pl-6 pb-2 border-l-2 border-slate-200 text-xs space-y-1">
                            <div class="absolute -left-1.5 top-0 w-3 h-3 rounded-full bg-slate-400 border-2 border-white"></div>
                            
                            <div class="flex items-center justify-between text-[10px] text-slate-400">
                                <span class="font-bold text-slate-700">{{ $msg->user?->name ?? 'System' }}</span>
                                <span>{{ $msg->created_at->format('d M, h:i A') }}</span>
                            </div>

                            <p class="text-slate-800 leading-normal">{{ $msg->message }}</p>

                            @if(!empty($msg->attachments))
                                <div class="flex flex-wrap gap-1.5 pt-1">
                                    @foreach($msg->attachments as $att)
                                        <a href="{{ $att }}" target="_blank" class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] hover:underline font-mono">
                                            📎 Attachment
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-slate-400 text-xs text-center py-4">
                            No messages recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
