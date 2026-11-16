@extends('layouts.storefront')

@section('title', 'Track Package #' . $fo->fulfilment_order_number . ' - ' . setting('name', 'ALLZONE'))

@section('content')
<div class="min-h-screen bg-slate-50/70 py-5 sm:py-8" x-data="{
    copiedAwb: false,
    copyAwbText(text) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            this.copiedAwb = true;
            setTimeout(() => this.copiedAwb = false, 2000);
        });
    }
}">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

        <!-- Breadcrumb Navigation (No Awkward Wrapping) -->
        <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400 overflow-x-auto no-scrollbar whitespace-nowrap">
            <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Home</a>
            <span>&rsaquo;</span>
            <a href="{{ route('orders.index') }}" class="hover:text-blue-600 transition">My Orders</a>
            <span>&rsaquo;</span>
            <a href="{{ route('orders.show', $order->order_number) }}" class="hover:text-blue-600 transition">#{{ $order->order_number }}</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-bold">Track Package</span>
        </nav>

        <!-- Page Header Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                            Shiprocket Tracked
                        </span>
                        <span class="text-xs text-slate-500 font-medium">
                            Order <a href="{{ route('orders.show', $order->order_number) }}" class="font-mono font-bold text-blue-600 hover:underline">#{{ $order->order_number }}</a>
                        </span>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
                        Track Package #{{ $fo->fulfilment_order_number }}
                    </h1>

                    <p class="text-xs text-slate-500 flex flex-wrap items-center gap-1.5 pt-0.5">
                        <span>Courier Partner: <strong class="text-slate-800">{{ $fo->shipment?->courier ?? 'Shiprocket Express' }}</strong></span>
                        <span class="text-slate-300">&bull;</span>
                        <span>Fulfilled by <strong class="text-slate-800">{{ setting('name', 'ALLZONE') }} Assured</strong></span>
                    </p>
                </div>

                <!-- Status Badge Pill -->
                <div class="shrink-0 self-start sm:self-auto">
                    @php
                        $statusMap = [
                            'delivered' => ['bg' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-500', 'text' => 'Delivered'],
                            'out_for_delivery' => ['bg' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500 animate-pulse', 'text' => 'Out for Delivery'],
                            'in_transit' => ['bg' => 'bg-blue-50 text-blue-800 border-blue-200', 'dot' => 'bg-blue-500 animate-pulse', 'text' => 'In Transit'],
                            'picked_up' => ['bg' => 'bg-blue-50 text-blue-800 border-blue-200', 'dot' => 'bg-blue-500', 'text' => 'Picked Up'],
                            'ready_for_pickup' => ['bg' => 'bg-indigo-50 text-indigo-800 border-indigo-200', 'dot' => 'bg-indigo-500', 'text' => 'Ready to Ship'],
                            'packed' => ['bg' => 'bg-indigo-50 text-indigo-800 border-indigo-200', 'dot' => 'bg-indigo-500', 'text' => 'Packed'],
                            'processing' => ['bg' => 'bg-slate-100 text-slate-800 border-slate-200', 'dot' => 'bg-slate-400', 'text' => 'Processing'],
                            'placed' => ['bg' => 'bg-slate-100 text-slate-800 border-slate-200', 'dot' => 'bg-slate-400', 'text' => 'Order Placed'],
                            'cancelled' => ['bg' => 'bg-rose-50 text-rose-800 border-rose-200', 'dot' => 'bg-rose-500', 'text' => 'Cancelled'],
                        ];
                        $foStatusKey = strtolower($fo->status);
                        $badgeInfo = $statusMap[$foStatusKey] ?? ['bg' => 'bg-slate-100 text-slate-800 border-slate-200', 'dot' => 'bg-slate-400', 'text' => ucwords(str_replace('_', ' ', $fo->status))];
                    @endphp

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-black {{ $badgeInfo['bg'] }}">
                        <span class="w-2 h-2 rounded-full {{ $badgeInfo['dot'] }}"></span>
                        <span>{{ $badgeInfo['text'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipment Key Info (3-Card Clean Responsive Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- 1. Destination Address -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">📍 Delivery Destination</span>
                <div class="text-xs text-slate-600 space-y-0.5">
                    <p class="font-bold text-slate-900 text-sm">{{ $order->address_snapshot['name'] ?? 'Recipient' }}</p>
                    @if(isset($order->address_snapshot['phone']))
                        <p class="font-medium text-slate-700">+91 {{ $order->address_snapshot['phone'] }}</p>
                    @endif
                    <p class="line-clamp-2 text-slate-600">{{ $order->address_snapshot['line1'] ?? '' }}</p>
                    <p class="font-bold text-slate-900 pt-0.5">
                        {{ $order->address_snapshot['city'] ?? '' }}{{ isset($order->address_snapshot['state']) ? ', ' . $order->address_snapshot['state'] : '' }} - {{ $order->address_snapshot['pincode'] ?? '' }}
                    </p>
                </div>
            </div>

            <!-- 2. AWB & Courier Ref -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">🚚 Shipment Tracking Details</span>
                <div class="space-y-1 text-xs">
                    <div>
                        <span class="text-slate-500 font-medium">Carrier:</span>
                        <span class="font-bold text-slate-900 ml-1">{{ $fo->shipment?->courier ?? 'Shiprocket Surface/Air' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium">AWB Number:</span>
                        @if($fo->shipment?->awb)
                            <div class="flex items-center gap-2 mt-1">
                                <span class="font-mono font-bold text-sm text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100 select-all">
                                    {{ $fo->shipment->awb }}
                                </span>
                                <button type="button" 
                                        @click="copyAwbText('{{ $fo->shipment->awb }}')" 
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition cursor-pointer flex items-center gap-1">
                                    <span x-text="copiedAwb ? '✓ Copied' : 'Copy'">Copy</span>
                                </button>
                            </div>
                        @else
                            <span class="text-slate-400 italic ml-1">AWB assigned upon carrier pickup</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 3. Package Contents Snapshot -->
            <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-xs space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">📦 Package Contents ({{ $fo->items->sum('qty') }} Items)</span>
                <div class="space-y-2 pt-0.5">
                    @foreach($fo->items->take(2) as $it)
                        <div class="flex items-center space-x-2.5 text-xs">
                            <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200/80 p-0.5 shrink-0 overflow-hidden">
                                <img src="{{ $it->variant?->product?->primaryImageUrl() }}" alt="{{ $it->title }}" class="w-full h-full object-contain">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-slate-900 truncate">{{ $it->title }}</p>
                                <p class="text-[10px] text-slate-500">Qty: {{ $it->qty }} &bull; ₹{{ number_format((float) $it->price) }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if($fo->items->count() > 2)
                        <span class="text-[10px] font-bold text-blue-600 block">+ {{ $fo->items->count() - 2 }} more item(s) in this package</span>
                    @endif
                </div>
            </div>
        </div>

        @php
            $activeNdr = $fo->shipment?->ndrCases?->where('status', '!=', 'closed')?->first();
        @endphp

        @if($activeNdr)
            <!-- NDR Customer Resolution Box -->
            <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 space-y-3 shadow-xs" x-data="{ ndrChoice: 'reattempt' }">
                <div class="flex items-center space-x-2 font-black text-sm text-rose-700">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Delivery Attempt Incomplete: {{ $activeNdr->reason }}</span>
                </div>
                <p class="text-xs text-rose-700">
                    The delivery courier could not reach you. Please select how you would like the carrier to proceed:
                </p>

                <form method="POST" action="{{ route('orders.ndr.resolve', [$order->order_number, $fo->fulfilment_order_number]) }}" class="space-y-3 pt-1">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <label class="p-3 rounded-xl border bg-white cursor-pointer flex items-center space-x-2" :class="ndrChoice === 'reattempt' ? 'border-blue-600 ring-2 ring-blue-100' : 'border-slate-200'">
                            <input type="radio" name="action" value="reattempt" x-model="ndrChoice" class="text-blue-600">
                            <span class="font-bold text-slate-800">Re-attempt Tomorrow</span>
                        </label>
                        <label class="p-3 rounded-xl border bg-white cursor-pointer flex items-center space-x-2" :class="ndrChoice === 'change_address' ? 'border-blue-600 ring-2 ring-blue-100' : 'border-slate-200'">
                            <input type="radio" name="action" value="change_address" x-model="ndrChoice" class="text-blue-600">
                            <span class="font-bold text-slate-800">Update Phone/Landmark</span>
                        </label>
                        <label class="p-3 rounded-xl border bg-white cursor-pointer flex items-center space-x-2" :class="ndrChoice === 'rto' ? 'border-rose-600 ring-2 ring-rose-100' : 'border-slate-200'">
                            <input type="radio" name="action" value="rto" x-model="ndrChoice" class="text-rose-600">
                            <span class="font-bold text-slate-800">Cancel &amp; Return</span>
                        </label>
                    </div>

                    <div x-show="ndrChoice === 'change_address'" class="space-y-2">
                        <input type="text" name="new_phone" placeholder="Correct Phone Number" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white">
                        <input type="text" name="new_address" placeholder="Nearby landmark or building instructions" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white">
                    </div>

                    <div>
                        <input type="text" name="remarks" placeholder="Optional notes for delivery partner (e.g. Call before arrival, leave at security desk)" class="w-full text-xs rounded-xl border border-slate-300 p-2.5 bg-white">
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer">
                        Submit Delivery Instruction
                    </button>
                </form>
            </div>
        @endif

        <!-- Tracking Milestone Timeline Card -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-8 border border-slate-200/90 shadow-xs space-y-6">
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-600">Shipment Progress</span>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Delivery Milestones</h2>
            </div>

            @php
                // Map current status to stage 1-7
                $stageLevels = [
                    'placed' => 1,
                    'confirmed' => 2,
                    'processing' => 3,
                    'packed' => 3,
                    'ready_for_pickup' => 4,
                    'picked_up' => 5,
                    'in_transit' => 5,
                    'out_for_delivery' => 6,
                    'delivered' => 7,
                    'cancelled' => -1,
                ];
                $currentStage = $stageLevels[$foStatusKey] ?? 1;

                $milestones = [
                    ['stage' => 1, 'title' => 'Order Placed', 'desc' => 'Order details verified & payment confirmed'],
                    ['stage' => 2, 'title' => 'Order Confirmed', 'desc' => 'Merchant accepted & verified inventory'],
                    ['stage' => 3, 'title' => 'Packed & Prepared', 'desc' => 'Secured in tamper-evident packaging with warranty card'],
                    ['stage' => 4, 'title' => 'Ready to Ship', 'desc' => 'Manifested & handed over to Shiprocket courier partner'],
                    ['stage' => 5, 'title' => 'In Transit', 'desc' => 'Shipment traveling through carrier transit network'],
                    ['stage' => 6, 'title' => 'Out for Delivery', 'desc' => 'Courier executive assigned for final doorstep drop'],
                    ['stage' => 7, 'title' => 'Delivered', 'desc' => 'Package successfully received by customer'],
                ];

                $trackingEvents = $fo->shipment?->tracking ?? collect();
                $historyLogs = $fo->statusHistory()->latest()->get();
            @endphp

            @if($currentStage === -1)
                <div class="p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 space-y-1">
                    <span class="font-black text-sm block">⚠️ This package has been cancelled</span>
                    <p class="text-xs text-rose-600">The items in this shipment will not be dispatched. Any paid amounts are processed to your original payment mode or store credits.</p>
                </div>
            @else
                <!-- Timeline Steps -->
                <div class="relative pl-7 sm:pl-9 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-3 before:w-0.5 before:bg-slate-200">
                    @foreach($milestones as $m)
                        @php
                            $isCompleted = $currentStage >= $m['stage'];
                            $isCurrent = $currentStage === $m['stage'];
                            $isFuture = $currentStage < $m['stage'];
                        @endphp
                        <div class="relative group">
                            <!-- Bullet Icon -->
                            @if($isCompleted && !$isCurrent)
                                <div class="absolute -left-7 sm:-left-9 top-0.5 w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black shadow-xs ring-4 ring-emerald-50">
                                    ✓
                                </div>
                            @elseif($isCurrent)
                                <div class="absolute -left-7 sm:-left-9 top-0.5 w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black shadow-md ring-4 ring-blue-100 animate-pulse">
                                    ●
                                </div>
                            @else
                                <div class="absolute -left-7 sm:-left-9 top-0.5 w-6 h-6 rounded-full bg-slate-100 border-2 border-slate-300 text-slate-400 flex items-center justify-center text-[10px] font-bold">
                                    {{ $m['stage'] }}
                                </div>
                            @endif

                            <!-- Milestone Content -->
                            <div class="space-y-0.5">
                                <div class="flex items-center space-x-2">
                                    <h4 class="text-xs sm:text-sm font-bold {{ $isCompleted ? 'text-slate-900' : 'text-slate-400' }}">
                                        {{ $m['title'] }}
                                    </h4>
                                    @if($isCurrent)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-100 text-blue-800">
                                            Current Status
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs {{ $isCompleted ? 'text-slate-500' : 'text-slate-400' }}">
                                    {{ $m['desc'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Live Carrier Scans / Activity Logs Section -->
            @if($trackingEvents->isNotEmpty() || $historyLogs->isNotEmpty())
                <div class="pt-6 border-t border-slate-100 space-y-3">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                        Detailed Courier Activity Scans ({{ $trackingEvents->count() + $historyLogs->count() }})
                    </span>

                    <div class="divide-y divide-slate-100 rounded-2xl bg-slate-50/80 border border-slate-100 p-3 sm:p-4 text-xs space-y-2">
                        @foreach($historyLogs->take(4) as $log)
                            <div class="pt-2 first:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <div>
                                    <span class="font-bold text-slate-900 capitalize">{{ str_replace('_', ' ', $log->to_status) }}</span>
                                    <span class="text-slate-500 ml-1.5">{{ $log->note }}</span>
                                </div>
                                <span class="font-mono text-[11px] text-slate-400 shrink-0">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Footer Action Links -->
            <div class="pt-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <a href="{{ route('orders.show', $order->order_number) }}" class="font-bold text-blue-600 hover:text-blue-800 transition flex items-center gap-1">
                    <span>&larr;</span>
                    <span>Back to Order Details</span>
                </a>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('orders.invoice', $order->order_number) }}" class="font-bold text-slate-700 hover:text-blue-600 transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Download Invoice PDF</span>
                    </a>
                    <span class="text-slate-300">&bull;</span>
                    <a href="{{ route('contact') }}" class="font-semibold text-slate-500 hover:text-slate-800 transition">
                        Need Help?
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
