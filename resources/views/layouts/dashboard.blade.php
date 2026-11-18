<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - {{ setting('name', 'ALLZONE') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    <!-- Dark Mode Initializer (Prevents FOUC) -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>


@php
    $portal = $portal ?? (request()->is('seller*') ? 'seller' : 'admin');
    $menuSections = config("menu.{$portal}", []);
    $isSeller = $portal === 'seller';

    $user = auth()->user();
    $seller = ($isSeller && $user) ? $user->seller : null;
    $sellerStores = collect();
    $currentActiveStore = null;
    $effectiveStore = null;
    $isStoreOpen = true;
    $pendingOrdersCount = 0;
    $quickLiveCount = 0;
    $sellerProductCount = 0;
    $pendingReturnsCount = 0;
    $lowStockCount = 0;
    $storefrontUrl = route('home');

    if ($isSeller && $user) {
        $sellerStores = $user->accessibleStores();
        $activeStoreSessionId = session('seller_active_store_id');
        $currentActiveStore = $activeStoreSessionId ? $sellerStores->firstWhere('id', $activeStoreSessionId) : null;
        $effectiveStore = $currentActiveStore ?: $sellerStores->first();
        $isStoreOpen = $effectiveStore ? ($effectiveStore->status === \App\Enums\StoreStatus::ACTIVE) : true;
        if ($effectiveStore?->slug) {
            $storefrontUrl = route('store.show', $effectiveStore->slug);
        }

        if ($seller) {
            $sellerHeaderCounts = \Illuminate\Support\Facades\Cache::remember("seller_header_counts_{$seller->id}", 20, function () use ($seller) {
                return [
                    'pending_orders' => \App\Models\FulfilmentOrder::where('seller_id', $seller->id)
                        ->whereIn('status', ['placed', 'store_accepted', 'packing'])
                        ->count(),
                    'quick_live' => \App\Models\FulfilmentOrder::where('seller_id', $seller->id)
                        ->where('fulfilment_type', \App\Enums\FulfilmentType::QUICK)
                        ->whereIn('status', ['placed', 'store_accepted', 'packing'])
                        ->count(),
                    'products' => \App\Models\Product::where('seller_id', $seller->id)->count(),
                    'pending_returns' => \App\Models\OrderReturn::forSeller($seller->id)
                        ->whereIn('status', ['requested', 'approved', 'in_transit'])
                        ->count(),
                    'low_stock' => \App\Models\Inventory::whereHas('location', fn($q) => $q->where('seller_id', $seller->id))
                        ->whereRaw('(quantity_on_hand - quantity_reserved) <= low_stock_threshold')
                        ->count(),
                ];
            });

            $pendingOrdersCount = $sellerHeaderCounts['pending_orders'];
            $quickLiveCount = $sellerHeaderCounts['quick_live'];
            $sellerProductCount = $sellerHeaderCounts['products'];
            $pendingReturnsCount = $sellerHeaderCounts['pending_returns'];
            $lowStockCount = $sellerHeaderCounts['low_stock'];
        }
    }
@endphp

<script>
    function dashboardLayout(config) {
        return {
            mobileSidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
            darkMode: document.documentElement.classList.contains('dark'),
            isSeller: Boolean(config.isSeller),
            pendingOrdersCount: Number(config.pendingOrdersCount) || 0,
            quickLiveCount: Number(config.quickLiveCount) || 0,
            isStoreOpen: Boolean(config.isStoreOpen),
            isTogglingStore: false,
            pollInterval: null,
            audioCtx: null,
            soundMuted: localStorage.getItem('seller_sound_muted') === 'true',
            toasts: [],

            init() {
                if (config.successMessage) {
                    this.addToast(config.successMessage, 'success');
                }
                if (config.errorMessage) {
                    this.addToast(config.errorMessage, 'danger');
                }
                window.addEventListener('click', () => {
                    if (this.isSeller) this.initAudio();
                }, { once: true });
                window.addEventListener('toast', (e) => {
                    this.addToast(e.detail.message, e.detail.type || 'success');
                });
                if (this.isSeller) {
                    this.pollInterval = setInterval(() => this.checkPendingOrders(), 20000);
                }
            },

            toggleDarkMode() {
                this.darkMode = !this.darkMode;
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            },

            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
            },

            addToast(message, type = 'success') {
                const id = Date.now();
                this.toasts.push({ id, message, type });
                setTimeout(() => { this.removeToast(id); }, 4000);
            },

            removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            },

            toggleSound() {
                this.soundMuted = !this.soundMuted;
                localStorage.setItem('seller_sound_muted', this.soundMuted);
                this.addToast(this.soundMuted ? 'Order alerts muted' : 'Order alerts unmuted', 'info');
            },

            initAudio() {
                if (!this.audioCtx) {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (AudioContextClass) {
                        this.audioCtx = new AudioContextClass();
                    }
                }
                if (this.audioCtx && this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
            },

            async toggleStoreStatus(storeId) {
                if (this.isTogglingStore) return;
                this.isTogglingStore = true;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(config.toggleStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ store_id: storeId })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.isStoreOpen = data.is_open;
                        this.addToast(data.message, data.is_open ? 'success' : 'warning');
                    } else {
                        this.addToast(data.message || 'Error updating status', 'danger');
                    }
                } catch (e) {
                    this.addToast('Failed to toggle store status', 'danger');
                } finally {
                    this.isTogglingStore = false;
                }
            },

            async checkPendingOrders() {
                if (!this.isSeller || !config.pendingCountUrl) return;
                try {
                    const res = await fetch(config.pendingCountUrl, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.has_fresh && data.pending_count > this.pendingOrdersCount) {
                            this.playOrderChime();
                            this.addToast('🔔 New order received! Total pending: ' + data.pending_count, 'warning');
                        }
                        this.pendingOrdersCount = data.pending_count;
                    }
                } catch (e) {}
            },

            playOrderChime() {
                if (this.soundMuted) return;
                try {
                    this.initAudio();
                    if (!this.audioCtx) return;
                    const ctx = this.audioCtx;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    const now = ctx.currentTime;
                    osc.frequency.setValueAtTime(587.33, now);
                    osc.frequency.setValueAtTime(880, now + 0.15);
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                    osc.start(now);
                    osc.stop(now + 0.45);
                } catch (e) {
                    console.warn('Order chime notice:', e);
                }
            }
        };
    }
</script>
    @stack('styles')
</head>

<body 
    class="h-full flex flex-col font-sans antialiased bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-200"
    x-data="dashboardLayout({
        isSeller: {{ $isSeller ? 'true' : 'false' }},
        pendingOrdersCount: {{ (int) $pendingOrdersCount }},
        quickLiveCount: {{ (int) $quickLiveCount }},
        isStoreOpen: {{ $isStoreOpen ? 'true' : 'false' }},
        toggleStoreUrl: '{{ route('seller.store.toggle_status') }}',
        pendingCountUrl: '{{ route('seller.orders.pending_count') }}',
        successMessage: @js(session('success')),
        errorMessage: @js(session('error'))
    })"
>
    <!-- Toast Notification Overlay (Top Right) -->
    <div class="fixed top-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full px-4 sm:px-0">
        <template x-for="t in toasts" :key="t.id">
            <div 
                class="pointer-events-auto p-4 rounded-2xl shadow-2xl border text-xs font-semibold flex items-center justify-between space-x-3 transition-all duration-300 animate-slide-in-right"
                :class="{
                    'bg-emerald-500 text-white border-emerald-400': t.type === 'success',
                    'bg-rose-500 text-white border-rose-400': t.type === 'danger',
                    'bg-indigo-600 text-white border-indigo-500': t.type === 'info',
                    'bg-amber-500 text-slate-950 border-amber-400 font-bold': t.type === 'warning'
                }"
            >
                <div class="flex items-center space-x-2.5">
                    <template x-if="t.type === 'success'">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="t.type === 'danger'">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                    <span x-text="t.message"></span>
                </div>
                <button @click="removeToast(t.id)" class="opacity-70 hover:opacity-100 transition p-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <!-- Mobile Drawer Sidebar Backdrop -->
    <div 
        x-show="mobileSidebarOpen" 
        x-cloak 
        @click="mobileSidebarOpen = false" 
        class="fixed inset-0 z-40 bg-slate-950/75 backdrop-blur-xs lg:hidden transition-opacity"
    ></div>

    <div class="flex h-full w-full overflow-hidden">
        <!-- Sidebar Shell (Responsive Drawer on Mobile + Collapsible on Desktop) -->
        <aside 
            :class="{
                'translate-x-0': mobileSidebarOpen,
                '-translate-x-full': !mobileSidebarOpen,
                'lg:w-20': sidebarCollapsed,
                'lg:w-64': !sidebarCollapsed
            }"
            class="fixed inset-y-0 left-0 z-50 w-64 {{ $isSeller ? 'bg-[#181920] text-slate-200 border-r border-[#262833]' : 'bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800' }} transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col shrink-0 select-none shadow-sm dark:shadow-none"
        >
            @if($isSeller)
                <!-- Meesho Supplier Hub Left Header -->
                <div class="p-3 border-b border-[#262833]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-800 font-bold text-xs shrink-0">
                                <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div class="min-w-0 flex-1" x-show="!sidebarCollapsed">
                                <span class="font-bold text-xs uppercase tracking-wide text-white block truncate">
                                    {{ $seller?->business_name ?? ($effectiveStore?->name ?? 'DREESTI') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1" x-show="!sidebarCollapsed">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Notices & Support top sub-bar -->
                    <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-[#262833] text-[11px] text-slate-400 font-medium" x-show="!sidebarCollapsed">
                        <a href="{{ route('seller.notifications.index') }}" class="flex items-center space-x-1.5 hover:text-white transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span>Notices</span>
                        </a>
                        <span class="text-slate-600">|</span>
                        <a href="{{ route('seller.support.index') }}" class="flex items-center space-x-1.5 hover:text-white transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Support</span>
                        </a>
                    </div>
                </div>
            @else
                <!-- Sidebar Header / Logo (Admin) -->
                <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200/80 dark:border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-700 via-indigo-600 to-amber-400 shadow-indigo-500/20 flex items-center justify-center text-white font-extrabold text-sm shadow-md shrink-0 tracking-tighter">
                            AZ
                        </div>
                        <div x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap min-w-0">
                            <span class="text-base font-bold text-slate-900 dark:text-white tracking-tight">{{ setting('name', 'ALLZONE') }}</span>
                            <span class="block text-[10px] font-bold uppercase tracking-widest text-indigo-500 -mt-1 truncate max-w-[140px]">
                                Super Admin Central
                            </span>
                        </div>
                    </a>

                    <!-- Desktop Collapse Button -->
                    <button 
                        @click="toggleSidebar()" 
                        class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        title="Toggle Sidebar Collapse"
                    >
                        <svg class="w-4 h-4 transform transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                        </svg>
                    </button>
                </div>
            @endif

            <!-- Portal & Store Context Card -->
            @if(!$isSeller)
                <div x-show="!sidebarCollapsed" class="p-3 mx-3 my-2.5 rounded-xl bg-slate-100/70 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 text-xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Super Admin Active</span>
                        </div>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                            INR
                        </span>
                    </div>
                </div>
            @endif

            <!-- Dynamic Navigation Menu -->
            <nav class="flex-1 px-3 py-2 space-y-5 overflow-y-auto overflow-x-hidden text-sm">
                @foreach($menuSections as $section)
                    <div class="space-y-1">
                        @if(!empty($section['section']))
                            <div 
                                x-show="!sidebarCollapsed" 
                                class="px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                            >
                                {{ $section['section'] }}
                            </div>
                        @endif

                        @foreach($section['items'] as $item)
                            @php
                                $hasPermission = true;
                                if (!empty($item['can'])) {
                                    $hasPermission = auth()->check() && auth()->user()->can($item['can']);
                                }
                                if (!empty($item['store_permission']) && auth()->check()) {
                                    $hasPermission = $hasPermission && auth()->user()->hasStorePermission($item['store_permission']);
                                }
                                $isActive = request()->routeIs($item['active'] ?? '');
                                
                                // Dynamic Badge Logic
                                $badgeText = $item['badge'] ?? null;
                                $badgeColor = $item['badge_color'] ?? 'indigo';
                                $dynamicBadgeAlpine = null;
                                $dynamicShowAlpine = null;

                                if ($isSeller) {
                                    if ($item['route'] === 'seller.orders.index') {
                                        $badgeText = $pendingOrdersCount > 0 ? "{$pendingOrdersCount} New" : null;
                                        $badgeColor = 'amber';
                                        $dynamicBadgeAlpine = 'pendingOrdersCount + " New"';
                                        $dynamicShowAlpine = 'pendingOrdersCount > 0';
                                    } elseif ($item['route'] === 'seller.quick.live') {
                                        $badgeText = $quickLiveCount > 0 ? "⚡ {$quickLiveCount}" : '⚡ Live';
                                        $badgeColor = 'amber';
                                        $dynamicBadgeAlpine = 'quickLiveCount > 0 ? ("⚡ " + quickLiveCount) : "⚡ Live"';
                                    } elseif ($item['route'] === 'seller.products.index') {
                                        $badgeText = $sellerProductCount > 0 ? (string)$sellerProductCount : null;
                                        $badgeColor = 'slate';
                                    } elseif ($item['route'] === 'seller.returns.index' && $pendingReturnsCount > 0) {
                                        $badgeText = "{$pendingReturnsCount} Req";
                                        $badgeColor = 'rose';
                                    } elseif ($item['route'] === 'seller.inventory.index' && $lowStockCount > 0) {
                                        $badgeText = "{$lowStockCount} Low";
                                        $badgeColor = 'rose';
                                    } elseif ($item['route'] === 'seller.stores.index' && $sellerStores->count() > 0) {
                                        $badgeText = $sellerStores->count() . ' Outlets';
                                        $badgeColor = 'slate';
                                    } elseif ($item['route'] === 'seller.store.edit') {
                                        $badgeText = $isStoreOpen ? 'Open' : 'Closed';
                                        $badgeColor = $isStoreOpen ? 'emerald' : 'slate';
                                        $dynamicBadgeAlpine = 'isStoreOpen ? "Open" : "Closed"';
                                    }
                                }

                                $badgeClass = match($badgeColor) {
                                    'emerald' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                    'amber' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                    'sky' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
                                    'rose' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                    'slate' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-transparent',
                                    default => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
                                };
                            @endphp

                            @if($hasPermission)
                                <div class="space-y-0.5">
                                    <a 
                                        href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                                        class="flex items-center px-3 py-2 rounded-lg font-medium transition-all duration-150 group {{ $isActive ? ($isSeller ? 'bg-[#2b2542] text-white font-semibold' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 font-bold shadow-xs border-l-4 border-indigo-500') : ($isSeller ? 'text-[#b2b5c4] hover:text-white hover:bg-[#20222c]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/80') }}"
                                        title="{{ $item['title'] }}"
                                    >
                                        <!-- Dynamic Heroicon Set -->
                                        <div class="shrink-0 mr-3">
                                            @if($item['icon'] === 'home')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                            @elseif($item['icon'] === 'cube')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            @elseif($item['icon'] === 'truck')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : 'text-sky-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                            @elseif($item['icon'] === 'refresh' || $item['icon'] === 'arrow-path')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            @elseif($item['icon'] === 'currency-rupee')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : 'text-emerald-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 8h6m-5 0a3 3 0 110 6H9l5 6M9 4h6"/></svg>
                                            @elseif($item['icon'] === 'document-text')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            @elseif($item['icon'] === 'arrow-up-tray')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            @elseif($item['icon'] === 'photograph')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @elseif($item['icon'] === 'sparkles')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                            @elseif($item['icon'] === 'building-storefront')
                                                <svg class="w-4 h-4 {{ $isSeller ? 'text-slate-400' : 'text-teal-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                            @endif
                                        </div>

                                        <!-- Menu Title & Badges -->
                                        <div x-show="!sidebarCollapsed" class="flex-1 flex items-center justify-between overflow-hidden">
                                            <span class="truncate text-xs">{{ $item['title'] }}</span>

                                            @if($isSeller && !empty($item['badge']))
                                                @if($item['badge'] === 'NEW')
                                                    <span class="ml-2 px-1.5 py-0.2 rounded text-[9px] font-black uppercase tracking-wider bg-[#d63384] text-white">
                                                        NEW
                                                    </span>
                                                @elseif(str_contains($item['badge'], 'Out of Stock'))
                                                    <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#ffd5d5] text-[#b31d1d]">
                                                        Out of Stock (5)
                                                    </span>
                                                @else
                                                    <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $badgeClass }}">
                                                        {{ $item['badge'] }}
                                                    </span>
                                                @endif
                                            @elseif(!empty($badgeText))
                                                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $badgeClass }}">
                                                    {{ $badgeText }}
                                                </span>
                                            @endif

                                            @if(!empty($item['children']))
                                                <svg class="w-3.5 h-3.5 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            @endif
                                        </div>
                                    </a>

                                    @if(!empty($item['children']) && $isSeller && (request()->routeIs($item['active']) || true))
                                        <div class="ml-7 pl-2 border-l border-[#2e313f] space-y-1 mt-1 mb-1" x-show="!sidebarCollapsed">
                                            @foreach($item['children'] as $child)
                                                @php $isChildActive = request()->routeIs($child['active'] ?? ''); @endphp
                                                <a 
                                                    href="{{ \Illuminate\Support\Facades\Route::has($child['route']) ? route($child['route']) : '#' }}" 
                                                    class="block py-1 px-2 rounded text-[11px] font-medium transition {{ $isChildActive ? 'text-white bg-[#252837] font-semibold' : 'text-slate-400 hover:text-slate-200' }}"
                                                >
                                                    {{ $child['title'] }}
                                                    @if(!empty($child['badge']))
                                                        <span class="ml-1 px-1 py-0.2 rounded text-[8px] font-bold bg-[#d63384] text-white">NEW</span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <!-- Bottom User & Platform Meta -->
            @if($isSeller)
                <div class="p-3 border-t border-[#262833] bg-[#14151b]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-extrabold tracking-tight text-[#ff3f8b]">meesho</span>
                            <span class="text-[11px] text-slate-400 font-medium">Supplier Hub</span>
                        </div>
                        <form method="POST" action="{{ route('seller.logout') }}" class="inline shrink-0" x-show="!sidebarCollapsed">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 transition" title="Logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="p-3 border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <x-avatar name="{{ auth()->check() ? auth()->user()->name : 'Admin' }}" status="online" size="sm" />
                            <div x-show="!sidebarCollapsed" class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ auth()->check() ? auth()->user()->name : 'Admin' }}
                                </p>
                                <p class="text-[10px] text-slate-400 truncate flex items-center space-x-1">
                                    <span>{{ auth()->user()->email ?? 'admin@allzone.in' }}</span>
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}" class="inline shrink-0" x-show="!sidebarCollapsed">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="Logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </aside>

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50 dark:bg-slate-950">
            <!-- Sticky Topbar -->
            <header class="h-16 bg-white/85 dark:bg-slate-900/85 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6">
                <!-- Left: Mobile Trigger & Search Bar -->
                <div class="flex items-center space-x-3 sm:space-x-4 flex-1 max-w-lg">
                    <button 
                        @click="mobileSidebarOpen = true" 
                        class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>

                    <!-- Global Quick Search Input -->
                    <form 
                        method="GET" 
                        action="{{ $isSeller ? route('seller.orders.index') : route('admin.orders.index') }}" 
                        class="relative w-full hidden sm:block"
                    >
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input 
                            type="text" 
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ $isSeller ? 'Search orders, SKU, customer name, AWBs...' : 'Search orders, sellers, stores, cargo waybills...' }}" 
                            class="w-full pl-9 pr-14 py-2 text-xs bg-slate-100 dark:bg-slate-800/80 border border-transparent dark:border-slate-700/60 rounded-xl focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-900 dark:text-slate-100 placeholder-slate-400 transition"
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <kbd class="hidden md:inline-block px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-400 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded">Ctrl+K</kbd>
                        </div>
                    </form>
                </div>

                <!-- Right Topbar Controls -->
                <div class="flex items-center space-x-2 sm:space-x-3">
                    @if($isSeller)
                        <!-- Notifications & Orders Quick Bell -->
                        <a 
                            href="{{ route('seller.notifications.index') }}" 
                            class="relative p-2 rounded-lg text-slate-500 hover:text-slate-800 transition"
                            title="Notifications"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </a>
                        <a 
                            href="{{ route('seller.support.index') }}" 
                            class="relative p-2 rounded-lg text-slate-500 hover:text-slate-800 transition"
                            title="Support"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </a>
                    @else
                        <!-- Dark/Light Theme Mode Switcher (Admin Only) -->
                        <button 
                            type="button" 
                            @click="toggleDarkMode()" 
                            class="p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            title="Toggle Light/Dark Theme"
                        >
                            <template x-if="!darkMode">
                                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            </template>
                            <template x-if="darkMode">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </template>
                        </button>

                        <!-- Notifications Dropdown (Admin Only) -->
                        @php
                            $unreadNotificationsCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                            $recentNotifications = auth()->check() ? auth()->user()->unreadNotifications()->latest()->limit(5)->get() : collect();
                        @endphp
                        <x-dropdown align="right" width="80">
                            <x-slot name="trigger">
                                <button class="relative p-2 rounded-xl text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="Notifications">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                    @if($unreadNotificationsCount > 0)
                                        <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                    @endif
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Alerts & Notifications</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $unreadNotificationsCount > 0 ? 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $unreadNotificationsCount }} New
                                    </span>
                                </div>
                                <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-64 overflow-y-auto">
                                    @forelse($recentNotifications as $rn)
                                        <a href="{{ $rn->data['action_url'] ?? route('notifications.index') }}" class="p-3.5 block hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                            <div class="flex items-center justify-between text-xs font-bold text-slate-900 dark:text-white">
                                                <span>{{ $rn->data['title'] ?? 'Notification' }}</span>
                                                <span class="text-[10px] text-slate-400">{{ $rn->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                                {{ $rn->data['body'] ?? ($rn->data['message'] ?? 'New event occurred in the system.') }}
                                            </p>
                                        </a>
                                    @empty
                                        <div class="p-4 text-center text-xs text-slate-400">
                                            No new notifications
                                        </div>
                                    @endforelse
                                </div>
                            </x-slot>
                        </x-dropdown>
                    @endif
                                            <span>{{ $rn->data['title'] ?? 'Notification' }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $rn->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ $rn->data['message'] ?? '' }}</p>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-400">
                                        No unread notifications.
                                    </div>
                                @endforelse
                            </div>
                            <div class="p-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                @if($unreadNotificationsCount > 0)
                                    <form method="POST" action="{{ route('notifications.mark_all_read') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">Mark all read</button>
                                    </form>
                                @else
                                    <span></span>
                                @endif
                                <a href="{{ route('notifications.index') }}" class="font-semibold text-slate-600 dark:text-slate-300 hover:underline">View All</a>
                            </div>
                        </x-slot>
                    </x-dropdown>

                    <!-- Quick Actions Menu -->
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="px-3 py-1.5 rounded-xl {{ $isSeller ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-500/20' : 'bg-indigo-600 hover:bg-indigo-500 shadow-indigo-500/20' }} text-white text-xs font-bold shadow-sm transition flex items-center space-x-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span class="hidden sm:inline">Create</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="py-1 text-xs font-medium">
                                @if($isSeller)
                                    <a href="{{ route('seller.products.create') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                                        Add New Product
                                    </a>
                                    <a href="{{ route('seller.quick.live') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-amber-400 mr-2"></span>
                                        Quick Live Dispatch
                                    </a>
                                    <a href="{{ route('seller.shipments.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-sky-400 mr-2"></span>
                                        Book Cargo Waybills
                                    </a>
                                    <a href="{{ route('seller.stores.create') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-teal-400 mr-2"></span>
                                        Add Store Outlet
                                    </a>
                                    <a href="{{ route('seller.connections.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-indigo-400 mr-2"></span>
                                        Connect Shop / Feeds
                                    </a>
                                @else
                                    <a href="{{ route('admin.catalogue.moderation.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-indigo-400 mr-2"></span>
                                        Product Moderation
                                    </a>
                                    <a href="{{ route('admin.coupons.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                                        Create New Coupon
                                    </a>
                                    <a href="{{ route('admin.campaigns.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-amber-400 mr-2"></span>
                                        Launch Campaign
                                    </a>
                                    <a href="{{ route('admin.stores.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                        <span class="w-2 h-2 rounded-full bg-sky-400 mr-2"></span>
                                        Register Dark Store
                                    </a>
                                @endif
                            </div>
                        </x-slot>
                    </x-dropdown>

                    <!-- User Profile Dropdown -->
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <button class="flex items-center space-x-2 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                <x-avatar name="{{ auth()->check() ? auth()->user()->name : ($isSeller ? 'Seller' : 'Admin') }}" size="sm" />
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ auth()->check() ? auth()->user()->name : ($isSeller ? 'Merchant' : 'Admin') }}
                                </p>
                                <p class="text-[11px] text-slate-400 truncate mb-1">
                                    {{ $isSeller ? ($seller?->business_name ?? auth()->user()->email) : auth()->user()->email }}
                                </p>
                                @if($isSeller)
                                    @if($seller?->isKycVerified())
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                            ✓ KYC Verified
                                        </span>
                                    @elseif($seller?->isApproved())
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                            ● Active Merchant
                                        </span>
                                    @elseif($seller?->isKycSubmitted() || ($seller && $seller->hasPendingDocuments()))
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800">
                                            ⏳ KYC In Review
                                        </span>
                                    @elseif($seller?->status === \App\Enums\SellerStatus::SUSPENDED)
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800">
                                            ⚠️ Suspended
                                        </span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            Pending Onboarding
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                                        Super Admin
                                    </span>
                                @endif
                            </div>

                            <div class="py-1 text-xs">
                                @if($isSeller)
                                    <a href="{{ route('seller.store.edit') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                                        Storefront Settings
                                    </a>
                                    <a href="{{ route('seller.settlements.index') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                                        Earnings & Payouts
                                    </a>
                                    <a href="{{ $storefrontUrl }}" target="_blank" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-semibold transition">
                                        Public Storefront ↗
                                    </a>
                                @else
                                    <a href="{{ route('home') }}" target="_blank" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition">
                                        Customer Storefront ↗
                                    </a>
                                @endif

                                @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('admin')))
                                    <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                                    @if($isSeller)
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-indigo-600 dark:text-indigo-400 font-bold transition">
                                            Switch to Admin Central
                                        </a>
                                    @else
                                        <a href="{{ route('seller.dashboard') }}" class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-bold transition">
                                            Switch to Seller Studio
                                        </a>
                                    @endif
                                @endif

                                <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                                <form method="POST" action="{{ $isSeller ? route('seller.logout') : route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center space-x-2 px-4 py-2 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-600 dark:text-rose-400 font-semibold transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            <!-- Main Body View Container -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
