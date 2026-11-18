@extends('layouts.storefront')

@section('title', 'Order #' . $order->order_number . ' Status - ' . setting('name', 'ALLZONE'))

@section('content')
@php
    $orderStatusVal = $order->status instanceof \App\Enums\OrderStatus ? $order->status->value : (string) ($order->status ?? 'placed');
    $orderStatusVal = strtolower($orderStatusVal);

    $foStatuses = $order->fulfilmentOrders->pluck('status')->map(fn($s) => strtolower((string)$s))->all();
    $allDelivered = !empty($foStatuses) && count(array_filter($foStatuses, fn($s) => $s === 'delivered')) === count($foStatuses);
    $anyOutForDelivery = in_array('out_for_delivery', $foStatuses, true) || $orderStatusVal === 'out_for_delivery';
    $anyDispatched = in_array('in_transit', $foStatuses, true) || in_array('picked_up', $foStatuses, true) || $anyOutForDelivery;
    $anyProcessing = in_array('store_accepted', $foStatuses, true) || in_array('confirmed', $foStatuses, true) || in_array('packing', $foStatuses, true) || in_array('packed', $foStatuses, true) || in_array('ready', $foStatuses, true) || in_array('ready_to_ship', $foStatuses, true) || in_array($orderStatusVal, ['processing', 'confirmed', 'ready_for_pickup'], true);
    $isCancelled = in_array($orderStatusVal, ['cancelled', 'rejected', 'refunded'], true) || (!empty($foStatuses) && count(array_filter($foStatuses, fn($s) => in_array($s, ['cancelled', 'rejected'], true))) === count($foStatuses));

    if ($orderStatusVal === 'delivered' || $allDelivered) {
        $stepRank = 4; // Delivered
    } elseif ($anyDispatched) {
        $stepRank = 3; // Dispatched / In Transit / Out for Delivery
    } elseif ($anyProcessing) {
        $stepRank = 2; // Processing / Packing / Ready to Ship
    } elseif ($isCancelled) {
        $stepRank = -1; // Cancelled
    } else {
        $stepRank = 1; // Placed
    }

    $firstFo = $order->fulfilmentOrders->first();
@endphp

<div class="min-h-screen bg-slate-50/70 py-6 sm:py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Top Navigation & Action Strip -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-1">
            <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400 overflow-x-auto no-scrollbar whitespace-nowrap">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('orders.index') }}" class="hover:text-indigo-600 transition">My Orders</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-bold">#{{ $order->order_number }}</span>
            </nav>

            <div class="flex items-center gap-2 flex-wrap">
                @if($firstFo)
                    <a href="{{ route('orders.track', [$order->order_number, $firstFo->fulfilment_order_number]) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <span>Live Tracking</span>
                    </a>
                @endif
                <a href="{{ route('orders.show', $order->order_number) }}" class="inline-flex items-center space-x-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-xs font-bold transition shadow-2xs">
                    <span>Order Details</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- Dynamic Status Celebration Banner Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-slate-200/90 text-center shadow-xs space-y-5">
            @if($stepRank === 4)
                <!-- Delivered State -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        Order Delivered Successfully
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Your Order Has Been Delivered!</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Order Reference: <strong class="text-indigo-600 font-black">{{ $order->order_number }}</strong>
                    </p>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                    All items in this order have been successfully handed over. Thank you for choosing ALLZONE!
                </p>
            @elseif($stepRank === 3)
                <!-- Out for Delivery / In Transit State -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-blue-100 text-blue-600 flex items-center justify-center text-3xl sm:text-4xl shadow-inner animate-bounce">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-300">
                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                        {{ $anyOutForDelivery ? 'Out for Delivery' : 'In Transit with Courier' }}
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Your Order Is On The Way!</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Order Reference: <strong class="text-indigo-600 font-black">{{ $order->order_number }}</strong>
                    </p>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                    Your package has been dispatched from the seller's hub and is in transit via courier partner.
                </p>
            @elseif($stepRank === 2)
                <!-- Processing / Packing State -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 border border-indigo-300">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-ping"></span>
                        Order Being Processed
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Seller Processing Order</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Order Reference: <strong class="text-indigo-600 font-black">{{ $order->order_number }}</strong>
                    </p>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                    The seller has confirmed your order and is currently picking and packing your items for courier handover.
                </p>
            @elseif($stepRank === -1)
                <!-- Cancelled State -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-rose-100 text-rose-600 flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300">
                        Order Cancelled
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Order Cancelled</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Order Reference: <strong class="text-rose-600 font-black">{{ $order->order_number }}</strong>
                    </p>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                    This order was cancelled. Any eligible refund has been credited back to your original payment method.
                </p>
            @else
                <!-- Placed State (Default) -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                    <svg class="w-9 h-9 sm:w-11 sm:h-11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Order Confirmed &amp; Placed
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Thank You For Your Order!</h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Order Reference: <strong class="text-indigo-600 font-black">{{ $order->order_number }}</strong>
                    </p>
                </div>
                <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                    We've received your order and initiated fulfillment. Your items have been automatically routed into {{ count($order->fulfilmentOrders) }} package{{ count($order->fulfilmentOrders) > 1 ? 's' : '' }} for quickest delivery.
                </p>
            @endif

            <!-- 100% Dynamic Order Stepper Timeline -->
            @if(!$isCancelled)
                <div class="pt-6 sm:pt-8 border-t border-slate-100">
                    <div class="flex items-center justify-between max-w-md mx-auto text-xs font-bold">
                        <!-- Step 1: Placed -->
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-xs {{ $stepRank >= 1 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="mt-1.5 text-[11px] font-bold {{ $stepRank >= 1 ? 'text-emerald-700' : 'text-slate-400' }}">Placed</span>
                        </div>

                        <!-- Line 1-2 -->
                        <div class="flex-1 h-1 mx-2 rounded-full {{ $stepRank >= 2 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>

                        <!-- Step 2: Processing -->
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-xs {{ $stepRank >= 2 ? ($stepRank === 2 ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 animate-pulse' : 'bg-emerald-600 text-white') : 'bg-slate-100 text-slate-400' }}">
                                @if($stepRank > 2)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <span>2</span>
                                @endif
                            </div>
                            <span class="mt-1.5 text-[11px] font-bold {{ $stepRank >= 2 ? ($stepRank === 2 ? 'text-indigo-700' : 'text-emerald-700') : 'text-slate-400' }}">Processing</span>
                        </div>

                        <!-- Line 2-3 -->
                        <div class="flex-1 h-1 mx-2 rounded-full {{ $stepRank >= 3 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>

                        <!-- Step 3: Dispatched -->
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-xs {{ $stepRank >= 3 ? ($stepRank === 3 ? 'bg-blue-600 text-white ring-4 ring-blue-100 animate-pulse' : 'bg-emerald-600 text-white') : 'bg-slate-100 text-slate-400' }}">
                                @if($stepRank > 3)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                @endif
                            </div>
                            <span class="mt-1.5 text-[11px] font-bold {{ $stepRank >= 3 ? ($stepRank === 3 ? 'text-blue-700' : 'text-emerald-700') : 'text-slate-400' }}">Dispatched</span>
                        </div>

                        <!-- Line 3-4 -->
                        <div class="flex-1 h-1 mx-2 rounded-full {{ $stepRank >= 4 ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>

                        <!-- Step 4: Delivered -->
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-xs {{ $stepRank === 4 ? 'bg-emerald-600 text-white ring-4 ring-emerald-100' : 'bg-slate-100 text-slate-400' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="mt-1.5 text-[11px] font-bold {{ $stepRank === 4 ? 'text-emerald-700' : 'text-slate-400' }}">Delivered</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Split Fulfilment Packages (With Live Per-Package Status) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                    Fulfilment Packages ({{ count($order->fulfilmentOrders) }})
                </h2>
                <span class="text-xs text-slate-500 font-semibold">Live status updated by seller &amp; logistics</span>
            </div>

            @foreach ($order->fulfilmentOrders as $index => $fo)
                @php
                    $foStatusStr = strtolower((string)$fo->status);
                    $foBadge = match($foStatusStr) {
                        'delivered' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-300', 'label' => 'Delivered', 'dot' => 'bg-emerald-500'],
                        'out_for_delivery' => ['bg' => 'bg-amber-100 text-amber-800 border-amber-300', 'label' => 'Out for Delivery', 'dot' => 'bg-amber-500 animate-pulse'],
                        'in_transit', 'picked_up' => ['bg' => 'bg-blue-100 text-blue-800 border-blue-300', 'label' => 'In Transit', 'dot' => 'bg-blue-500 animate-pulse'],
                        'ready', 'ready_to_ship' => ['bg' => 'bg-purple-100 text-purple-800 border-purple-300', 'label' => 'Ready to Ship', 'dot' => 'bg-purple-500'],
                        'packing', 'packed' => ['bg' => 'bg-indigo-100 text-indigo-800 border-indigo-300', 'label' => 'Packing Items', 'dot' => 'bg-indigo-500'],
                        'store_accepted', 'confirmed' => ['bg' => 'bg-sky-100 text-sky-800 border-sky-300', 'label' => 'Order Confirmed', 'dot' => 'bg-sky-500'],
                        'cancelled', 'rejected' => ['bg' => 'bg-rose-100 text-rose-800 border-rose-300', 'label' => 'Cancelled', 'dot' => 'bg-rose-500'],
                        default => ['bg' => 'bg-slate-100 text-slate-800 border-slate-300', 'label' => 'Order Placed', 'dot' => 'bg-slate-400'],
                    };
                @endphp

                <div class="bg-white rounded-3xl border border-slate-200/90 overflow-hidden shadow-xs">
                    <!-- Package Header -->
                    <div class="p-4 sm:p-5 bg-slate-50/90 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-black">
                                {{ $index + 1 }}
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs sm:text-sm font-black text-slate-900">
                                        Package: {{ $fo->fulfilment_order_number }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $foBadge['bg'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $foBadge['dot'] }}"></span>
                                        {{ $foBadge['label'] }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span>Fulfilled by <strong class="text-slate-700">ALLZONE Assured</strong></span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span>Seller: <strong class="text-slate-700">{{ $fo->seller?->business_name ?? 'Verified Partner' }}</strong></span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span>Dispatched via <strong class="text-slate-700">{{ $fo->shipment?->courier ?? 'Shiprocket Express' }}</strong></span>
                                    @if($fo->shipment?->awb)
                                        <span class="text-slate-300">&bull;</span>
                                        <span>AWB: <strong class="text-indigo-600 font-mono">{{ $fo->shipment->awb }}</strong></span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a 
                                href="{{ route('orders.track', [$order->order_number, $fo->fulfilment_order_number]) }}" 
                                class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 hover:border-indigo-400 hover:text-indigo-600 text-slate-700 text-xs font-bold transition shadow-2xs inline-flex items-center space-x-1"
                            >
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <span>Track</span>
                            </a>
                        </div>
                    </div>

                    <!-- Items in Package -->
                    <div class="p-4 sm:p-5 divide-y divide-slate-100">
                        @foreach ($fo->items as $item)
                            <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4 text-xs">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                        @if($item->variant?->product?->primary_image_url)
                                            <img src="{{ $item->variant->product->primary_image_url }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate">{{ $item->title }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            Qty: <strong>{{ $item->qty }}</strong> &bull; SKU: <span class="font-mono">{{ $item->sku }}</span>
                                            @if($item->variant_name && $item->variant_name !== 'Default')
                                                &bull; <span class="text-indigo-600 font-semibold">{{ $item->variant_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <div class="font-black text-slate-900">{{ format_currency($item->total) }}</div>
                                    <div class="text-[10px] text-slate-400">({{ format_currency($item->price) }} each)</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Package Subtotal -->
                    <div class="p-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
                        <span>Package Subtotal:</span>
                        <span class="text-slate-900 font-black">{{ format_currency($fo->subtotal) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Order Snapshot Details: Address & Payment Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
            
            <!-- Shipping Address Snapshot -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Delivery Address</h3>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                @php $addr = $order->address_snapshot; @endphp
                <div class="text-xs text-slate-800 space-y-1">
                    <p class="font-black text-slate-900 text-sm">{{ $addr['name'] ?? 'Customer' }}</p>
                    <p class="text-slate-600 leading-relaxed">{{ $addr['line1'] ?? '' }}{{ !empty($addr['line2']) ? ', ' . $addr['line2'] : '' }}</p>
                    @if (!empty($addr['landmark']))
                        <p class="text-slate-500">Landmark: {{ $addr['landmark'] }}</p>
                    @endif
                    <p class="font-bold text-slate-900">{{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} - {{ $addr['pincode'] ?? '' }}</p>
                    @if(!empty($addr['phone']))
                        <p class="text-slate-500 pt-1">Phone: <strong class="text-slate-700 font-mono">+91 {{ $addr['phone'] }}</strong></p>
                    @endif
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Payment Breakdown</h3>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div class="text-xs text-slate-600 space-y-2">
                    <div class="flex items-center justify-between">
                        <span>Payment Method:</span>
                        <span class="font-black text-slate-900 uppercase">{{ $order->payment_method?->value ?? 'Online' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Items Subtotal:</span>
                        <span class="font-bold text-slate-900">{{ format_currency($order->subtotal) }}</span>
                    </div>
                    @if ($order->discount > 0)
                        <div class="flex items-center justify-between text-emerald-600">
                            <span>Discount / Coupon:</span>
                            <span class="font-bold">- {{ format_currency($order->discount) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span>Delivery Fee:</span>
                        <span class="font-bold text-slate-900">{{ (float) $order->delivery_fee == 0 ? 'FREE' : format_currency($order->delivery_fee) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200 text-sm font-black text-slate-900">
                        <span>Total Paid:</span>
                        <span class="text-indigo-600 text-base">{{ format_currency($order->total) }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Back to Shopping & Orders CTAs -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('orders.index') }}"
               class="w-full sm:w-auto px-6 py-3 rounded-2xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-extrabold text-xs shadow-2xs text-center transition">
                View All My Orders
            </a>
            <a href="{{ route('home') }}"
               class="w-full sm:w-auto px-8 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md shadow-indigo-500/20 active:scale-95 text-center transition">
                Continue Shopping &rarr;
            </a>
        </div>

    </div>
</div>
@endsection
