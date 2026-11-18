@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Orders Management - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-16">
    <!-- Page Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Orders Management</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage order processing, packing slips, Shiprocket courier dispatch, and customer fulfilment.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Export Button -->
            <a href="{{ route('seller.orders.index', array_merge(request()->all(), ['export' => 'csv'])) }}"
               class="px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <!-- Dispatch & SLA Health Notice Banner -->
    <div class="p-3.5 sm:p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/60 flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs shadow-2xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-lg shrink-0">
                ⚡
            </div>
            <div>
                <div class="font-bold text-slate-900 dark:text-white">Next-Day Dispatch Standard Active</div>
                <div class="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">
                    Process and mark orders <strong>Ready to Ship</strong> within 24 hours of placement to maintain optimal seller score and courier allocation priority.
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('seller.orders.dispatch_performance') }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-2xs">
                Dispatch SLA Performance &rarr;
            </a>
        </div>
    </div>

    <!-- Orders Main Container -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden space-y-0">
        <!-- 9 Status Navigation Tabs -->
        @php
            $tabs = [
                'all' => ['label' => 'All Orders', 'count' => $counts['all'] ?? 0],
                'pending' => ['label' => 'New / Pending', 'count' => $counts['pending'] ?? 0],
                'on_hold' => ['label' => 'On Hold', 'count' => $counts['on_hold'] ?? 0],
                'processing' => ['label' => 'Processing', 'count' => $counts['processing'] ?? 0],
                'ready_to_ship' => ['label' => 'Ready to Ship', 'count' => $counts['ready_to_ship'] ?? 0],
                'shipped' => ['label' => 'Shipped', 'count' => $counts['shipped'] ?? 0],
                'delivered' => ['label' => 'Delivered', 'count' => $counts['delivered'] ?? 0],
                'cancelled' => ['label' => 'Cancelled', 'count' => $counts['cancelled'] ?? 0],
                'returned' => ['label' => 'Returned', 'count' => $counts['returned'] ?? 0],
            ];
            $currentStatus = $tab ?? 'all';
        @endphp

        <div class="flex items-center border-b border-slate-200 dark:border-slate-800 px-4 pt-2 overflow-x-auto text-xs font-bold scrollbar-none gap-1">
            @foreach($tabs as $key => $tabItem)
                <a href="{{ route('seller.orders.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}"
                   class="py-3 px-3.5 border-b-2 transition whitespace-nowrap flex items-center gap-1.5 {{ $currentStatus === $key ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400 font-extrabold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <span>{{ $tabItem['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentStatus === $key ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                        {{ $tabItem['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Filter & Search Sub-Bar -->
        <form action="{{ route('seller.orders.index') }}" method="GET" class="p-3.5 bg-slate-50/60 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            @if($tab !== 'all')
                <input type="hidden" name="status" value="{{ $tab }}">
            @endif
            @if(!empty($channel) && $channel !== 'all')
                <input type="hidden" name="channel" value="{{ $channel }}">
            @endif

            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-slate-400 font-semibold text-[11px]">Filters:</span>

                <!-- Payment Method Filter -->
                <select name="payment_method" onchange="this.form.submit()" class="py-1.5 px-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 font-medium focus:outline-hidden focus:border-emerald-500">
                    <option value="all" {{ ($paymentMethod ?? 'all') === 'all' ? 'selected' : '' }}>All Payment Types</option>
                    <option value="prepaid" {{ ($paymentMethod ?? '') === 'prepaid' ? 'selected' : '' }}>Prepaid (Razorpay)</option>
                    <option value="cod" {{ ($paymentMethod ?? '') === 'cod' ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                </select>

                <!-- Payment Status Filter -->
                <select name="payment_status" onchange="this.form.submit()" class="py-1.5 px-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 font-medium focus:outline-hidden focus:border-emerald-500">
                    <option value="all" {{ ($paymentStatus ?? 'all') === 'all' ? 'selected' : '' }}>All Payment Statuses</option>
                    <option value="paid" {{ ($paymentStatus ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ ($paymentStatus ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>

                <!-- Date Range Filter -->
                <select name="date_range" onchange="this.form.submit()" class="py-1.5 px-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 font-medium focus:outline-hidden focus:border-emerald-500">
                    <option value="all" {{ ($dateFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Dates</option>
                    <option value="today" {{ ($dateFilter ?? '') === 'today' ? 'selected' : '' }}>Placed Today</option>
                    <option value="yesterday" {{ ($dateFilter ?? '') === 'yesterday' ? 'selected' : '' }}>Placed Yesterday</option>
                    <option value="7d" {{ ($dateFilter ?? '') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30d" {{ ($dateFilter ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                </select>

                <!-- Multi-Store Outlet Selector (if applicable) -->
                @if(count($stores) > 1)
                    <select name="store_id" onchange="this.form.submit()" class="py-1.5 px-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 font-medium focus:outline-hidden focus:border-emerald-500">
                        <option value="">All Store Locations</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ ($storeId == $st->id) ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Search input -->
            <div class="flex items-center gap-2">
                <div class="relative w-64">
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search Order #, SKU, Item..."
                           class="w-full py-1.5 pl-8 pr-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-hidden focus:border-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                @if($search || $paymentMethod || $paymentStatus || $dateFilter !== 'all' || $storeId)
                    <a href="{{ route('seller.orders.index', ['status' => $tab]) }}" class="px-2 py-1 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-bold text-[10px] uppercase border-b border-slate-200 dark:border-slate-800 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 font-bold">Order &amp; Sub-Order</th>
                        <th class="py-3.5 px-4 font-bold">Product Items &amp; SKU</th>
                        <th class="py-3.5 px-4 font-bold">Customer Destination</th>
                        <th class="py-3.5 px-4 font-bold">Amount &amp; Payment</th>
                        <th class="py-3.5 px-4 font-bold">Status &amp; Dispatch SLA</th>
                        <th class="py-3.5 px-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($orders as $fo)
                        @php
                            $order = $fo->order;
                            $addr = is_array($order?->address_snapshot) ? $order->address_snapshot : [];
                            $customerPhone = $addr['phone'] ?? $order?->customer?->user?->phone;
                            $maskedPhone = $customerPhone ? (substr($customerPhone, 0, 3) . '***' . substr($customerPhone, -3)) : 'N/A';
                            $isCod = $order?->payment_method === \App\Enums\PaymentMethod::COD;
                            $isPaid = $order?->payment_status === \App\Enums\PaymentStatus::PAID;
                            $firstItem = $fo->items->first();
                            $expectedDispatch = $fo->created_at->addHours(24);
                            $isBreached = $expectedDispatch->isPast() && in_array($fo->status, ['placed', 'store_accepted', 'packing', 'processing']);
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                            <!-- 1. Order & Sub-Order IDs -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <a href="{{ route('seller.orders.show', $fo) }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">
                                        #{{ $fo->fulfilment_order_number }}
                                    </a>
                                </div>
                                @if($order)
                                    <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                        Parent: #{{ $order->order_number }}
                                    </div>
                                @endif
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $fo->created_at->format('d M Y, h:i A') }}
                                </div>
                                <div class="mt-1">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase
                                        {{ $fo->fulfilment_type === \App\Enums\FulfilmentType::QUICK ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                                        {{ $fo->fulfilment_type->label() }}
                                    </span>
                                </div>
                            </td>

                            <!-- 2. Products & SKU Details -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="space-y-1.5">
                                    @foreach($fo->items->take(2) as $item)
                                        <div class="flex items-center gap-2">
                                            @if($item->variant?->product?->thumbnail)
                                                <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden shrink-0">
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($item->variant->product->thumbnail) }}" alt="{{ $item->title }}" class="w-full h-full object-cover">
                                                </div>
                                            @else
                                                <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-400 shrink-0">
                                                    📦
                                                </div>
                                            @endif
                                            <div class="truncate">
                                                <div class="font-bold text-slate-900 dark:text-white truncate" title="{{ $item->title }}">{{ $item->title }}</div>
                                                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                                    <span class="font-mono">SKU: {{ $item->sku }}</span>
                                                    @if($item->variant_name)
                                                        <span>&bull; {{ $item->variant_name }}</span>
                                                    @endif
                                                    <span class="font-bold text-slate-700 dark:text-slate-300">x{{ $item->qty }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($fo->items->count() > 2)
                                        <div class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            +{{ $fo->items->count() - 2 }} more items
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 3. Customer Destination & Privacy Masking -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    {{ $addr['name'] ?? ($order?->customer?->user?->name ?? 'Verified Customer') }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $addr['city'] ?? 'Bengaluru' }}, {{ $addr['state'] ?? 'Karnataka' }}
                                    @if(!empty($addr['pincode']))
                                        <span class="font-mono text-slate-400">({{ $addr['pincode'] }})</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    Contact: {{ $maskedPhone }}
                                </div>
                            </td>

                            <!-- 4. Financials & Payment Method -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-extrabold text-slate-900 dark:text-white text-xs">
                                    ₹{{ number_format((float)$fo->total_amount, 2) }}
                                </div>
                                <div class="flex items-center gap-1 mt-1">
                                    @if($isCod)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            COD
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            Prepaid (Razorpay)
                                        </span>
                                    @endif

                                    @if($isPaid)
                                        <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">Paid ✓</span>
                                    @else
                                        <span class="text-[10px] font-semibold text-amber-600 dark:text-amber-400">Pending</span>
                                    @endif
                                </div>
                            </td>

                            <!-- 5. Status & Expected Dispatch SLA -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold border {{ $fo->statusBadgeClass() }}">
                                        {{ $fo->statusLabel() }}
                                    </span>
                                </div>

                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                                    <span>Dispatch SLA:</span>
                                    @if($isBreached)
                                        <span class="font-bold text-rose-600 dark:text-rose-400">SLA Overdue!</span>
                                    @else
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $expectedDispatch->format('d M, h:i A') }}</span>
                                    @endif
                                </div>

                                @if($fo->shipment?->awb)
                                    <div class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        AWB: {{ $fo->shipment->awb }}
                                    </div>
                                @endif
                            </td>

                            <!-- 6. Available Actions -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5" x-data="{ docMenu: false }">
                                    <!-- Context-sensitive processing actions -->
                                    @if($fo->status === 'placed')
                                        <form method="POST" action="{{ route('seller.orders.accept', $fo) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs">
                                                Accept Order
                                            </button>
                                        </form>
                                    @elseif(in_array($fo->status, ['store_accepted', 'packing', 'processing']))
                                        <form method="POST" action="{{ route('seller.orders.ready_to_ship', $fo) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs">
                                                Ready to Ship
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Documents dropdown -->
                                    <div class="relative">
                                        <button type="button" @click="docMenu = !docMenu" class="px-2 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 transition flex items-center gap-1">
                                            <span>Docs</span>
                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="docMenu" @click.outside="docMenu = false" style="display: none;" class="absolute right-0 mt-1 w-36 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-1 z-30 text-[11px]">
                                            <a href="{{ route('seller.orders.packing_slip', $fo) }}" target="_blank" class="block px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/80 font-medium">Packing Slip</a>
                                            <a href="{{ route('seller.orders.invoice', $fo) }}" target="_blank" class="block px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/80 font-medium">Tax Invoice</a>
                                            <a href="{{ route('seller.orders.shipping_label', $fo) }}" target="_blank" class="block px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/80 font-medium">Shipping Label</a>
                                        </div>
                                    </div>

                                    <!-- Detail Link -->
                                    <a href="{{ route('seller.orders.show', $fo) }}" class="px-2.5 py-1 text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-bold text-[11px] hover:underline">
                                        Details &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-4">
                                @php
                                    $emptyDesc = ($tab !== 'all') ? "There are currently no orders under '" . ($tabs[$tab]['label'] ?? ucfirst($tab)) . "'." : 'No customer orders have been received yet. As soon as orders are placed on ALLZONE, they will appear here.';
                                @endphp
                                <x-empty-state 
                                    icon="truck"
                                    title="No Orders in this Status"
                                    :description="$emptyDesc"
                                >
                                    @if($search || $tab !== 'all' || $paymentMethod || $paymentStatus)
                                        <a href="{{ route('seller.orders.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold transition">
                                            Reset Filters
                                        </a>
                                    @endif
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
