@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Shipment Consignment #' . $shipment->shipment_number])

@section('content')
<div class="space-y-6" x-data="{ cancelReason: '' }">

    <!-- Page Header & Action Bar -->
    <x-page-header 
        title="Shipment #{{ $shipment->shipment_number }}" 
        subtitle="Booked with {{ $shipment->courier ?? 'Shiprocket' }} on {{ $shipment->created_at->format('l, d M Y, h:i A') }}"
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Shipping' => route('admin.shipping.index'), '#' . $shipment->shipment_number => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $shipment->status === \App\Enums\ShipmentStatus::DELIVERED ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : ($shipment->status === \App\Enums\ShipmentStatus::CANCELLED ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300') }}">
                {{ $shipment->status->label() }}
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                @if($shipment->awb)
                    <form method="POST" action="{{ route('admin.shipping.track_shipment', $shipment) }}">
                        @csrf
                        <button 
                            type="submit" 
                            class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer"
                        >
                            <span>⚡</span>
                            <span>Refresh Live Tracking</span>
                        </button>
                    </form>
                @endif

                @if($shipment->label_url)
                    <a 
                        href="{{ $shipment->label_url }}" 
                        target="_blank" 
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs flex items-center space-x-1.5"
                    >
                        <span>Download Shipping Label &nearr;</span>
                    </a>
                @endif

                @if(!in_array($shipment->status, [\App\Enums\ShipmentStatus::DELIVERED, \App\Enums\ShipmentStatus::CANCELLED]))
                    <button 
                        type="button" 
                        @click="$dispatch('open-modal', 'cancel-shipment-modal')"
                        class="px-3.5 py-2 rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-xs font-bold transition cursor-pointer"
                    >
                        Cancel Consignment
                    </button>
                @endif
            </div>
        </x-slot>
    </x-page-header>

    @php
        $fo = $shipment->fulfilmentOrder;
        $order = $fo?->order;
    @endphp

    <!-- Consignment Core Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 1. Carrier & Tracking Information -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="text-xs font-black uppercase tracking-wider text-slate-400">Shiprocket Carrier &amp; Routing</div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Courier Partner</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $shipment->courier ?? 'Shiprocket Surface' }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">AWB Code</span>
                <span class="font-mono font-bold text-emerald-600">{{ $shipment->awb ?? 'Pending Assignment' }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Service Type</span>
                <span class="font-semibold uppercase text-slate-700 dark:text-slate-300">{{ $shipment->service ?? 'Surface' }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Pickup Status</span>
                <span class="font-semibold capitalize text-slate-700 dark:text-slate-300">{{ $shipment->pickup_status ?? 'Pending' }}</span>
            </div>
            @if($fo && $fo->estimated_delivery_at)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 flex justify-between">
                    <span>Estimated Delivery SLA:</span>
                    <strong class="text-slate-800 dark:text-slate-200">{{ $fo->estimated_delivery_at->format('d M Y') }}</strong>
                </div>
            @endif
        </div>

        <!-- 2. Origin & Destination Profiles -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
            <div class="text-xs font-black uppercase tracking-wider text-slate-400">Route &amp; Destination Profile</div>
            <div class="text-xs space-y-1">
                <div class="text-slate-500">Origin Merchant:</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ $fo?->seller?->business_name ?? 'Vendor' }}</div>
                <div class="text-[11px] text-slate-400 font-mono">Store Hub: {{ $fo?->store?->name ?? 'Main Hub' }} (Pin: {{ $fo?->store?->pincode ?? '560038' }})</div>
            </div>
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs space-y-1">
                <div class="text-slate-500">Consignee Destination:</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ $order?->customer?->user?->name ?? $order?->address_snapshot['name'] ?? 'Shopper' }}</div>
                <div class="text-[11px] text-slate-500">
                    {{ $order?->address_snapshot['line1'] ?? '' }}<br>
                    {{ $order?->address_snapshot['city'] ?? '' }}, {{ $order?->address_snapshot['state'] ?? '' }} - <span class="font-mono font-bold">{{ $order?->address_snapshot['pincode'] ?? '' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Financial Reconciliation & Packaging -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-2.5">
            <div class="text-xs font-black uppercase tracking-wider text-slate-400">Financial Reconciliation</div>
            <div class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                <span>Shiprocket Courier Charges</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ format_currency((float) $shipment->charges) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                <span>Customer Paid Delivery Fee</span>
                <span>{{ (float) ($order?->delivery_fee ?? 0) == 0 ? 'FREE' : format_currency((float) $order->delivery_fee) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-600 dark:text-slate-300">
                <span>Consignment Weight</span>
                <span>{{ number_format((float) $shipment->weight, 2) }} kg</span>
            </div>
            @if($shipment->cod_amount > 0)
                <div class="flex justify-between text-xs text-amber-600 font-bold">
                    <span>COD Collectable</span>
                    <span>{{ format_currency((float) $shipment->cod_amount) }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-between text-xs font-bold text-slate-900 dark:text-white">
                <span>Parent Order #</span>
                @if($order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-emerald-600 hover:underline">#{{ $order->order_number }}</a>
                @else
                    <span>N/A</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Consignment Milestone Tracking Events -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                Shiprocket Milestone Tracking History
            </h3>
            <span class="text-xs text-slate-400 font-mono">
                AWB: {{ $shipment->awb ?? 'None' }}
            </span>
        </div>

        <div class="space-y-3">
            @forelse($shipment->tracking as $trk)
                <div class="flex items-start space-x-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 text-xs">
                    <span class="w-2.5 h-2.5 rounded-full {{ str_contains(strtolower($trk->status), 'delivered') ? 'bg-emerald-500' : (str_contains(strtolower($trk->status), 'cancel') || str_contains(strtolower($trk->status), 'failed') ? 'bg-rose-500' : 'bg-indigo-500') }} mt-1 shrink-0"></span>
                    <div class="flex-1 space-y-0.5">
                        <div class="flex items-center justify-between font-bold text-slate-900 dark:text-white">
                            <span class="capitalize text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', $trk->status) }}</span>
                            <span class="text-[11px] text-slate-400 font-mono font-normal">{{ $trk->event_time?->format('d M Y, h:i A') ?? $trk->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <p class="text-slate-500">Location: <strong class="text-slate-700 dark:text-slate-300">{{ $trk->location ?? 'Carrier Hub' }}</strong></p>
                    </div>
                </div>
            @empty
                <div class="text-xs text-slate-400 italic py-4">No milestone tracking events recorded yet. Click "Refresh Live Tracking" to fetch events.</div>
            @endforelse
        </div>
    </div>

    <!-- Package Items -->
    @if($fo && $fo->items)
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                Package Contents ({{ $fo->items->count() }} items)
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 dark:border-slate-800 text-[10px] uppercase font-bold text-slate-400">
                        <tr>
                            <th class="pb-2">Item</th>
                            <th class="pb-2">SKU</th>
                            <th class="pb-2 text-center">Qty</th>
                            <th class="pb-2 text-right">Price</th>
                            <th class="pb-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @foreach($fo->items as $it)
                            <tr>
                                <td class="py-2.5 font-bold text-slate-900 dark:text-white">
                                    {{ $it->title }}
                                    @if($it->variant_name && $it->variant_name !== 'Default')
                                        <span class="text-slate-400 font-normal text-[11px]">({{ $it->variant_name }})</span>
                                    @endif
                                </td>
                                <td class="py-2.5 font-mono text-slate-400">{{ $it->sku }}</td>
                                <td class="py-2.5 text-center font-bold">{{ $it->qty }}</td>
                                <td class="py-2.5 text-right">{{ format_currency((float) $it->price) }}</td>
                                <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">{{ format_currency((float) $it->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Cancel Consignment Modal -->
    <x-modal name="cancel-shipment-modal" size="md">
        <x-slot name="header">
            <div class="flex items-center space-x-2">
                <span class="text-rose-600">🚫</span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Cancel Consignment</h3>
            </div>
        </x-slot>

        <form method="POST" action="{{ route('admin.shipping.cancel_shipment', $shipment) }}" class="space-y-4">
            @csrf
            <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold">
                This will inform Shiprocket to cancel carrier pickup for AWB {{ $shipment->awb ?? $shipment->shipment_number }}.
            </p>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cancellation Reason</label>
                <textarea 
                    name="reason" 
                    required 
                    rows="3" 
                    placeholder="Enter reason for cancelling this consignment..." 
                    class="w-full text-xs font-medium p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500"
                ></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-2">
                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'cancel-shipment-modal')" 
                    class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition"
                >
                    Keep Consignment
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition"
                >
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </x-modal>

</div>
@endsection
