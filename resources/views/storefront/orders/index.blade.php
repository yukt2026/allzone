@extends('layouts.storefront')

@section('title', 'My Orders - ' . setting('name', 'ALLZONE'))

@section('content')
<div class="min-h-screen bg-slate-50/70 py-5 sm:py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

        <!-- Breadcrumbs & Heading -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-400 mb-1.5 overflow-x-auto no-scrollbar whitespace-nowrap">
                    <a href="{{ route('home') }}" class="hover:text-blue-600 transition">Home</a>
                    <span>&rsaquo;</span>
                    <a href="{{ route('customer.profile') }}" class="hover:text-blue-600 transition">My Account</a>
                    <span>&rsaquo;</span>
                    <span class="text-slate-800 font-bold">My Orders</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">My Orders</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Track your past purchases, shipment statuses, invoices, and reorders.</p>
            </div>

            <!-- Search form -->
            <form method="GET" action="{{ route('orders.index') }}" class="flex items-center space-x-2 w-full md:w-80">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <div class="relative w-full">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search by Order # or item..." 
                        class="w-full text-xs font-medium pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition shrink-0 shadow-xs cursor-pointer">
                    Search
                </button>
            </form>
        </div>

        <!-- Status Filter Tabs (Touch-Friendly, Horizontal Scroll on Mobile) -->
        <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar pb-1 border-b border-slate-200 text-xs font-bold whitespace-nowrap">
            <a 
                href="{{ route('orders.index', ['status' => 'all', 'search' => $search]) }}"
                class="px-4 py-2 rounded-xl transition {{ $currentStatus === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
            >
                All Orders
            </a>
            <a 
                href="{{ route('orders.index', ['status' => 'processing', 'search' => $search]) }}"
                class="px-4 py-2 rounded-xl transition flex items-center space-x-1.5 {{ $currentStatus === 'processing' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
            >
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>In Progress</span>
            </a>
            <a 
                href="{{ route('orders.index', ['status' => 'delivered', 'search' => $search]) }}"
                class="px-4 py-2 rounded-xl transition {{ $currentStatus === 'delivered' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
            >
                Delivered
            </a>
            <a 
                href="{{ route('orders.index', ['status' => 'cancelled', 'search' => $search]) }}"
                class="px-4 py-2 rounded-xl transition {{ $currentStatus === 'cancelled' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
            >
                Cancelled
            </a>
        </div>

        <!-- Orders List -->
        @if($orders->isEmpty())
            <div class="bg-white rounded-3xl p-8 sm:p-14 text-center border border-slate-200 shadow-xs space-y-4 max-w-xl mx-auto my-6">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl">
                    📦
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">No orders found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                    @if($search || $currentStatus !== 'all')
                        No orders match your filter criteria. Try resetting the search or filter tab.
                    @else
                        You haven't placed any orders yet. Discover authentic electronics, home appliances, and gadgets on ALLZONE.
                    @endif
                </p>
                <div class="pt-2">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-extrabold hover:bg-blue-700 shadow-sm transition">
                        Start Shopping &rarr;
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-4 sm:space-y-5">
                @foreach($orders as $order)
                    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition hover:shadow-md hover:border-slate-300">
                        <!-- Order Header Bar -->
                        <div class="bg-slate-50/90 px-4 sm:px-6 py-3.5 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex flex-wrap items-center gap-x-5 sm:gap-x-7 gap-y-2">
                                <div>
                                    <span class="text-slate-400 font-semibold block text-[10px] uppercase tracking-wider">Order ID</span>
                                    <span class="font-mono font-bold text-slate-900">#{{ $order->order_number }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block text-[10px] uppercase tracking-wider">Order Placed</span>
                                    <span class="font-bold text-slate-800">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 font-semibold block text-[10px] uppercase tracking-wider">Total</span>
                                    <span class="font-black text-slate-900 text-sm sm:text-base">₹{{ number_format((float) $order->total) }}</span>
                                </div>
                                @if(isset($order->address_snapshot['name']))
                                    <div class="hidden sm:block">
                                        <span class="text-slate-400 font-semibold block text-[10px] uppercase tracking-wider">Ship To</span>
                                        <span class="font-bold text-slate-800 truncate max-w-[140px] inline-block" title="{{ $order->address_snapshot['name'] }}">
                                            {{ $order->address_snapshot['name'] }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center space-x-2.5">
                                <x-order-status-badge :status="$order->status" size="sm" />
                            </div>
                        </div>

                        <!-- Packages Container -->
                        <div class="p-4 sm:p-6 space-y-4 sm:space-y-5 divide-y divide-slate-100">
                            @foreach($order->fulfilmentOrders as $fo)
                                <div class="pt-4 first:pt-0">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                📦 Shiprocket Express
                                            </span>
                                            <span class="text-xs font-bold text-slate-800">
                                                Package #{{ $fo->fulfilment_order_number }}
                                            </span>
                                            <span class="text-xs text-slate-400 font-medium">
                                                &bull; {{ setting('name', 'ALLZONE') }} Assured
                                            </span>
                                        </div>

                                        <div class="text-xs font-semibold text-slate-600">
                                            Status: <span class="font-bold text-slate-900 capitalize">{{ str_replace('_', ' ', $fo->status) }}</span>
                                        </div>
                                    </div>

                                    <!-- Items Preview List -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($fo->items as $item)
                                            @php
                                                $itemImg = $item->variant?->product?->primaryImageUrl();
                                            @endphp
                                            <div class="flex items-center space-x-3 p-3 rounded-2xl bg-slate-50/70 border border-slate-100 hover:bg-slate-50 transition">
                                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center shrink-0 overflow-hidden p-1">
                                                    <img 
                                                        src="{{ $itemImg }}" 
                                                        alt="{{ $item->title }}" 
                                                        class="w-full h-full object-contain"
                                                        onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><rect width=\'100\' height=\'100\' fill=\'%23f1f5f9\'/><text x=\'50\' y=\'55\' font-family=\'sans-serif\' font-size=\'10\' fill=\'%2394a3b8\' text-anchor=\'middle\'>ALLZONE</text></svg>';"
                                                    >
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-xs sm:text-sm font-bold text-slate-900 line-clamp-2 leading-snug" title="{{ $item->title }}">
                                                        {{ $item->title }}
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 mt-1">
                                                        Qty: <span class="font-bold text-slate-700">{{ $item->qty }}</span> &bull; 
                                                        <span class="font-black text-slate-900">₹{{ number_format((float) $item->price) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Footer Actions Bar -->
                        <div class="bg-slate-50/80 px-4 sm:px-6 py-3 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center space-x-2 text-xs">
                                <span class="text-slate-500 font-medium">Payment:</span>
                                <span class="font-bold text-slate-900 uppercase">{{ $order->payment_method->value ?? 'Online' }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ $order->payment_status === \App\Enums\PaymentStatus::PAID ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ strtoupper(str_replace('_', ' ', $order->payment_status->value ?? 'Pending')) }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a 
                                    href="{{ route('orders.show', $order->order_number) }}" 
                                    class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-xs transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <span>View Details &amp; Track</span>
                                    <span>&rarr;</span>
                                </a>

                                <form method="POST" action="{{ route('orders.reorder', $order->order_number) }}" class="inline">
                                    @csrf
                                    <button 
                                        type="submit" 
                                        class="px-3.5 py-2 rounded-xl bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 text-xs font-bold transition shadow-2xs cursor-pointer"
                                    >
                                        Reorder
                                    </button>
                                </form>

                                <a 
                                    href="{{ route('orders.invoice', $order->order_number) }}" 
                                    class="px-3 py-2 rounded-xl bg-white text-slate-600 hover:text-blue-600 hover:bg-slate-100 border border-slate-200 text-xs font-bold transition shadow-2xs"
                                    title="Download Tax Invoice PDF"
                                >
                                    Invoice PDF
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
