@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Pricing, Commission & Platform Fees'])

@section('content')
<div class="space-y-6" x-data="commissionSimulator()">

    <!-- Page Header -->
    <x-page-header 
        title="Pricing, Commission & Platform Fees" 
        subtitle="Tiered commission governance, product exceptions, seller bounds, dynamic fee simulator, and financial reconciliation ledgers."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Pricing & Commission' => '#']"
    >
        <x-slot name="badge">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                {{ $metrics['active_rules'] }} Active Rules
            </span>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                <a 
                    href="{{ route('admin.commissions.export_report') }}" 
                    class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:text-emerald-600 text-xs font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Export Ledger CSV</span>
                </a>
                <button 
                    @click="openCreateModal = true" 
                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center space-x-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Commission Rule</span>
                </button>
            </div>
        </x-slot>
    </x-page-header>

    <!-- Top KPI Grid: Financial Ledger & Commission Metrics (7 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- 1. Gross Sales -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Gross Sales</div>
            <div class="text-lg font-black text-slate-900 dark:text-white truncate">
                ₹{{ number_format($metrics['gross_sales'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">Total delivered volume</div>
        </div>

        <!-- 2. Platform Commission -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Commission</div>
            <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 truncate">
                ₹{{ number_format($metrics['platform_commission'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">Pre-tax platform take</div>
        </div>

        <!-- 3. GST on Commission -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-teal-500 uppercase tracking-wider">Tax (GST)</div>
            <div class="text-lg font-black text-teal-600 dark:text-teal-400 truncate">
                ₹{{ number_format($metrics['tax_on_commission'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">18% GST statutory pool</div>
        </div>

        <!-- 4. Gateway Fees -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider">Gateway Fees</div>
            <div class="text-lg font-black text-blue-600 dark:text-blue-400 truncate">
                ₹{{ number_format($metrics['gateway_fee_deductions'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">Online payment cost</div>
        </div>

        <!-- 5. Refund Reversals -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-rose-500 uppercase tracking-wider">Reversals</div>
            <div class="text-lg font-black text-rose-600 dark:text-rose-400 truncate">
                ₹{{ number_format($metrics['refund_adjustments'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">Returns &amp; refunds</div>
        </div>

        <!-- 6. Net Seller Payable -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider">Seller Payable</div>
            <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 truncate">
                ₹{{ number_format($metrics['net_seller_payable'], 2) }}
            </div>
            <div class="text-[9px] text-slate-400">Settlement obligations</div>
        </div>

        <!-- 7. Active Rules -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-3.5 border border-slate-200 dark:border-slate-800 shadow-xs space-y-1">
            <div class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Rules Active</div>
            <div class="text-lg font-black text-amber-600 dark:text-amber-400">
                {{ $metrics['active_rules'] }} / {{ $metrics['total_rules'] }}
            </div>
            <div class="text-[9px] text-slate-400">Priority tiers</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 px-5 pt-3 overflow-x-auto text-xs font-bold">
            <a 
                href="{{ route('admin.commissions.index', ['tab' => 'rules']) }}" 
                class="pb-3 px-3 border-b-2 transition whitespace-nowrap {{ $tab === 'rules' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Commission Rules &amp; Simulator
            </a>
            <a 
                href="{{ route('admin.commissions.index', ['tab' => 'charges']) }}" 
                class="pb-3 px-3 border-b-2 transition whitespace-nowrap {{ $tab === 'charges' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Platform Charges &amp; Pricing Controls
            </a>
            <a 
                href="{{ route('admin.commissions.index', ['tab' => 'history']) }}" 
                class="pb-3 px-3 border-b-2 transition whitespace-nowrap {{ $tab === 'history' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Commission History Ledger
            </a>
            <a 
                href="{{ route('admin.commissions.index', ['tab' => 'price_history']) }}" 
                class="pb-3 px-3 border-b-2 transition whitespace-nowrap {{ $tab === 'price_history' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Price Change Audit Trail
            </a>
            <a 
                href="{{ route('admin.commissions.index', ['tab' => 'reports']) }}" 
                class="pb-3 px-3 border-b-2 transition whitespace-nowrap {{ $tab === 'reports' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}"
            >
                Financial Reconciliation Reports
            </a>
        </div>

        <!-- TAB 1: RULES & SIMULATOR -->
        @if($tab === 'rules')
            <div class="p-6 space-y-6">
                <!-- Top Grid: Interactive Simulator & System Defaults -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Live Interactive Simulator Widget (Span 2) -->
                    <div class="lg:col-span-2 bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 dark:border-slate-700/80">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                                    ⚡
                                </div>
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Live Commission Engine Simulator</h2>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Strict priority resolution: Product Exception &gt; Seller Override &gt; Promo Window &gt; Category Rule &gt; Global Default</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                                Real-time
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Seller</label>
                                <select x-model="simSellerId" @change="runSimulation()" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2">
                                    <option value="">Any Seller (No override)</option>
                                    @foreach($sellers as $s)
                                        <option value="{{ $s->id }}">{{ $s->business_name }} (#{{ $s->id }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Product Category</label>
                                <select x-model="simCategoryId" @change="runSimulation()" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2">
                                    <option value="">Any Category</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Product Exception</label>
                                <select x-model="simProductId" @change="runSimulation()" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2">
                                    <option value="">No Product Exception</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ \Illuminate\Support\Str::limit($p->title, 25) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Order Value (₹)</label>
                                <input type="number" step="10" x-model.number="simAmount" @input.debounce.300ms="runSimulation()" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 font-bold" placeholder="1000.00">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Shipping Adj. (₹)</label>
                                <input type="number" step="5" x-model.number="simShipping" @input.debounce.300ms="runSimulation()" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2" placeholder="0.00">
                            </div>
                        </div>

                        <!-- Simulation Calculation Output -->
                        <div class="mt-4 p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <div class="flex items-center justify-between mb-3 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="text-slate-400 font-semibold">Winning Tier:</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="simResult.rule_name || 'System Default'"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300" x-text="simResult.tier_label || 'Default'"></span>
                                </div>
                                <div class="text-slate-500 dark:text-slate-400">
                                    Commission: <strong class="text-slate-900 dark:text-white" x-text="(simResult.rate_percent || 0) + '% + ₹' + (simResult.fixed_fee || 0)"></strong>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60">
                                    <div class="text-[10px] text-slate-400">Gross Sale</div>
                                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5" x-text="simResult.formatted?.base_amount || '₹0.00'"></div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60">
                                    <div class="text-[10px] text-rose-500">Platform Commission</div>
                                    <div class="text-sm font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5" x-text="'- ' + (simResult.formatted?.commission_amount || '₹0.00')"></div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700/60">
                                    <div class="text-[10px] text-rose-500">GST on Comm ({{ $commissionGstPct }}%)</div>
                                    <div class="text-sm font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5" x-text="'- ' + (simResult.formatted?.tax_on_commission || '₹0.00')"></div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Net Seller Payable</div>
                                    <div class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5" x-text="simResult.formatted?.net_payable || '₹0.00'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Global Platform Defaults Card -->
                    <div class="bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/80 rounded-2xl p-5 flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-1">Platform Charges Reference</h3>
                            <p class="text-xs text-slate-400 mb-4">Baseline rates applied when no higher priority tier matches.</p>

                            <div class="space-y-2.5 text-xs">
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                    <span class="text-slate-600 dark:text-slate-400 font-medium">Default Commission</span>
                                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $defaultCommissionPct }}%</span>
                                </div>
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                    <span class="text-slate-600 dark:text-slate-400 font-medium">GST on Commission</span>
                                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $commissionGstPct }}%</span>
                                </div>
                                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                    <span class="text-slate-600 dark:text-slate-400 font-medium">Payment Gateway Fee</span>
                                    <span class="font-bold font-mono text-slate-900 dark:text-white">{{ $paymentGatewayFeePct }}%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200/80 dark:border-slate-700">
                            <a href="{{ route('admin.commissions.index', ['tab' => 'charges']) }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline flex items-center space-x-1 font-bold">
                                <span>Modify Charges &amp; Pricing Controls</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Scope Filter & Rules Table -->
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 text-xs">
                        <div class="flex items-center space-x-1.5 overflow-x-auto">
                            @php $currentScope = request('scope', 'all'); @endphp
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'all']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'all' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">All Scopes</a>
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'product']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'product' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">Product Exceptions</a>
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'seller']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'seller' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">Seller Overrides</a>
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'promo']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'promo' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">Promo Windows</a>
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'category']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'category' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">Category Specific</a>
                            <a href="{{ route('admin.commissions.index', ['tab' => 'rules', 'scope' => 'global']) }}" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $currentScope === 'global' ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">Global Rules</a>
                        </div>

                        <form method="GET" action="{{ route('admin.commissions.index') }}" class="flex items-center space-x-2">
                            <input type="hidden" name="tab" value="rules">
                            <input type="hidden" name="scope" value="{{ $currentScope }}">
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ request('search') }}" 
                                placeholder="Search rule..." 
                                class="text-xs px-2.5 py-1 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                            <button type="submit" class="px-3 py-1 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 rounded-xl text-xs font-bold">Search</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-2xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                                <tr>
                                    <th class="px-5 py-3">Priority</th>
                                    <th class="px-5 py-3">Rule Name &amp; Scope</th>
                                    <th class="px-5 py-3">Target Entity</th>
                                    <th class="px-5 py-3">Rate (%)</th>
                                    <th class="px-5 py-3">Fixed Fee</th>
                                    <th class="px-5 py-3">Effective Window</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                @forelse($rules as $rule)
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                        <td class="px-5 py-3.5 font-mono font-bold text-slate-500">
                                            #{{ $rule->priority }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ $rule->name }}</div>
                                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase mt-1
                                                @if($rule->scope->value === 'product') bg-rose-50 text-rose-700 border border-rose-200
                                                @elseif($rule->scope->value === 'seller') bg-indigo-50 text-indigo-700 border border-indigo-200
                                                @elseif($rule->scope->value === 'promo') bg-amber-50 text-amber-700 border border-amber-200
                                                @elseif($rule->scope->value === 'category') bg-blue-50 text-blue-700 border border-blue-200
                                                @else bg-slate-100 text-slate-700 border border-slate-200
                                                @endif
                                            ">
                                                {{ $rule->scope->label() }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            @if($rule->scope->value === 'product')
                                                <div class="font-bold text-slate-900 dark:text-white">{{ $rule->product?->title ?? 'Product #' . $rule->product_id }}</div>
                                            @elseif($rule->scope->value === 'seller')
                                                <div class="font-bold text-slate-900 dark:text-white">{{ $rule->seller?->business_name ?? 'Seller #' . $rule->seller_id }}</div>
                                            @elseif($rule->scope->value === 'category')
                                                <div class="font-bold text-slate-900 dark:text-white">{{ $rule->category?->name ?? 'Category #' . $rule->category_id }}</div>
                                            @elseif($rule->scope->value === 'promo')
                                                <div class="text-amber-600 font-semibold">Promotional Campaign</div>
                                            @else
                                                <div class="text-slate-500">Platform Default</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5 font-mono font-bold text-emerald-600 text-sm">
                                            {{ $rule->percent }}%
                                        </td>
                                        <td class="px-5 py-3.5 font-mono">
                                            {{ format_currency((float)$rule->fixed_fee) }}
                                        </td>
                                        <td class="px-5 py-3.5 text-slate-500 text-[11px]">
                                            @if($rule->starts_at || $rule->ends_at)
                                                <div>{{ $rule->starts_at ? $rule->starts_at->format('d M Y') : 'Start' }} &rarr; {{ $rule->ends_at ? $rule->ends_at->format('d M Y') : 'Indefinite' }}</div>
                                            @else
                                                <span class="text-slate-400">Permanent</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $rule->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right space-x-2">
                                            <button 
                                                type="button"
                                                @click="openEditModal({{ json_encode($rule) }})" 
                                                class="text-slate-400 hover:text-emerald-600 transition"
                                                title="Edit Rule"
                                            >
                                                ✏️
                                            </button>
                                            <form method="POST" action="{{ route('admin.commissions.destroy', $rule) }}" class="inline" onsubmit="return confirm('Delete this commission rule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 transition" title="Delete Rule">
                                                    🗑️
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                            No commission rules found matching your filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rules)
                        <div class="p-3">
                            {{ $rules->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- TAB 2: PLATFORM CHARGES & PRICING CONTROLS -->
        @if($tab === 'charges')
            <div class="p-6 max-w-4xl space-y-6">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">Platform Charges &amp; Pricing Policies</h3>
                    <p class="text-xs text-slate-500">Configure global platform fee schedules, payment processing charges, and seller discount ceiling controls.</p>
                </div>

                <form method="POST" action="{{ route('admin.commissions.controls.update') }}" class="space-y-6 text-xs">
                    @csrf

                    <!-- Section A: Fee Structure -->
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-4">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center space-x-2">
                            <span>💳 Fee &amp; Commission Structure</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Default Commission (%) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="default_commission_pct" value="{{ $pricingControls['default_commission_pct'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">GST on Commission (%) *</label>
                                <input type="number" step="0.01" min="0" max="50" name="commission_gst_pct" value="{{ $pricingControls['commission_gst_pct'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Gateway Fee (%) *</label>
                                <input type="number" step="0.01" min="0" max="20" name="payment_gateway_fee_pct" value="{{ $pricingControls['payment_gateway_fee_pct'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" name="gateway_fee_borne_by_seller" id="gw_fee_check" value="1" {{ $pricingControls['gateway_fee_borne_by_seller'] ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <label for="gw_fee_check" class="font-semibold text-slate-700 dark:text-slate-300">Deduct payment gateway fee from merchant earnings</label>
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="checkbox" name="pass_delivery_fee_to_seller" id="pass_del_check" value="1" {{ $pricingControls['pass_delivery_fee_to_seller'] ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <label for="pass_del_check" class="font-semibold text-slate-700 dark:text-slate-300">Pass delivery fee to seller on merchant-fulfilled orders</label>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Fixed Platform Fee Per Order (₹)</label>
                            <input type="number" step="0.01" min="0" name="fixed_platform_fee_per_order" value="{{ $pricingControls['fixed_platform_fee_per_order'] }}" class="w-48 text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                    </div>

                    <!-- Section B: Pricing Controls & Safeguards -->
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-4">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center space-x-2">
                            <span>🛡️ Pricing Bounds &amp; Discount Safeguards</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Minimum Product Price (Floor) (₹) *</label>
                                <input type="number" step="0.01" min="0" name="min_product_price" value="{{ $pricingControls['min_product_price'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Maximum Product Price (Ceiling) (₹) *</label>
                                <input type="number" step="0.01" min="1" name="max_product_price" value="{{ $pricingControls['max_product_price'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Discount Ceiling (% of MRP) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="max_discount_percentage" value="{{ $pricingControls['max_discount_percentage'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Coupon Discount (%) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="max_coupon_discount_pct" value="{{ $pricingControls['max_coupon_discount_pct'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Max Flat Coupon Cap (₹) *</label>
                                <input type="number" step="0.01" min="0" name="max_coupon_discount_flat" value="{{ $pricingControls['max_coupon_discount_flat'] }}" required class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            </div>
                        </div>

                        <div class="pt-2">
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" name="require_admin_approval_for_large_price_drop" id="price_drop_check" value="1" {{ $pricingControls['require_admin_approval_for_large_price_drop'] ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <label for="price_drop_check" class="font-semibold text-slate-700 dark:text-slate-300">Require administrative review for price reductions greater than 50%</label>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                            Save Platform Pricing Policies
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- TAB 3: COMMISSION HISTORY LEDGER -->
        @if($tab === 'history')
            <div class="p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Commission Accounting History</h3>
                        <p class="text-xs text-slate-500">Real-time ledger entries created automatically on order deliveries and return reversals.</p>
                    </div>

                    <form method="GET" action="{{ route('admin.commissions.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="history">
                        <select name="entry_type" onchange="this.form.submit()" class="text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-1.5 font-semibold">
                            <option value="all">All Types</option>
                            <option value="standard" {{ request('entry_type') === 'standard' ? 'selected' : '' }}>Standard Delivery</option>
                            <option value="reversal" {{ request('entry_type') === 'reversal' ? 'selected' : '' }}>Refund Reversal</option>
                        </select>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search package / seller..." class="text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-1.5">
                        <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-bold rounded-xl">Filter</button>
                    </form>
                </div>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="p-3.5">Entry ID</th>
                                <th class="p-3.5">Fulfilment Order</th>
                                <th class="p-3.5">Merchant</th>
                                <th class="p-3.5">Gross Base</th>
                                <th class="p-3.5">Commission</th>
                                <th class="p-3.5">GST (18%)</th>
                                <th class="p-3.5">Gateway Fee</th>
                                <th class="p-3.5">Net Payable</th>
                                <th class="p-3.5">Type</th>
                                <th class="p-3.5">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($history as $entry)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                    <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">#{{ $entry->id }}</td>
                                    <td class="p-3.5 font-mono text-slate-700 dark:text-slate-300">
                                        {{ $entry->fulfilmentOrder?->fulfilment_order_number ?? 'FO #' . $entry->fulfilment_order_id }}
                                    </td>
                                    <td class="p-3.5 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $entry->fulfilmentOrder?->seller?->business_name ?? 'N/A' }}
                                    </td>
                                    <td class="p-3.5 font-mono font-bold">
                                        ₹{{ number_format((float) $entry->base_amount, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono text-rose-600">
                                        ₹{{ number_format((float) $entry->commission, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono text-rose-500">
                                        ₹{{ number_format((float) $entry->tax_on_commission, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono text-blue-600">
                                        ₹{{ number_format((float) $entry->gateway_fee, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono font-black text-emerald-600">
                                        ₹{{ number_format((float) $entry->net_payable, 2) }}
                                    </td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $entry->entry_type === 'standard' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                            {{ $entry->entry_type }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-slate-400 text-[11px] font-mono">
                                        {{ $entry->created_at->format('d M Y, h:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-8 text-center text-slate-400">No commission ledger records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($history)
                    <div class="p-3">
                        {{ $history->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 4: PRICE CHANGE AUDIT TRAIL -->
        @if($tab === 'price_history')
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-slate-900 dark:text-white">Product Price Change Audit Trail</h3>
                        <p class="text-xs text-slate-500">Immutable log of merchant and administrator price modifications, MRP changes, and reason justifications.</p>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 font-semibold">
                            <tr>
                                <th class="p-3.5">Timestamp</th>
                                <th class="p-3.5">Product &amp; SKU</th>
                                <th class="p-3.5">Merchant</th>
                                <th class="p-3.5">Old Selling Price</th>
                                <th class="p-3.5">New Selling Price</th>
                                <th class="p-3.5">MRP Adjustment</th>
                                <th class="p-3.5">Type &amp; Reason</th>
                                <th class="p-3.5">Changed By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            @forelse($priceHistory as $ph)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition">
                                    <td class="p-3.5 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                        {{ $ph->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="p-3.5">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $ph->variant?->product?->title ?? 'Product' }}</div>
                                        <div class="font-mono text-[10px] text-slate-400">SKU: {{ $ph->variant?->sku ?? 'N/A' }}</div>
                                    </td>
                                    <td class="p-3.5 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $ph->seller?->business_name ?? 'N/A' }}
                                    </td>
                                    <td class="p-3.5 font-mono">
                                        ₹{{ number_format((float) $ph->old_price, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono font-bold {{ $ph->new_price < $ph->old_price ? 'text-emerald-600' : 'text-slate-900 dark:text-white' }}">
                                        ₹{{ number_format((float) $ph->new_price, 2) }}
                                    </td>
                                    <td class="p-3.5 font-mono text-slate-500 text-[11px]">
                                        {{ $ph->old_mrp ? '₹' . number_format((float) $ph->old_mrp, 2) : '-' }} &rarr; {{ $ph->new_mrp ? '₹' . number_format((float) $ph->new_mrp, 2) : '-' }}
                                    </td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                            {{ $ph->change_type }}
                                        </span>
                                        <div class="text-[11px] text-slate-500 mt-0.5 italic">{{ $ph->reason ?? 'Manual adjustment' }}</div>
                                    </td>
                                    <td class="p-3.5 text-slate-600 dark:text-slate-400 text-[11px]">
                                        {{ $ph->user?->name ?? 'System' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-slate-400">No price change history recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($priceHistory)
                    <div class="p-3">
                        {{ $priceHistory->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 5: FINANCIAL RECONCILIATION REPORTS -->
        @if($tab === 'reports')
            <div class="p-6 space-y-6 max-w-5xl">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-base text-slate-900 dark:text-white">Platform Financial Ledger Reconciliation</h3>
                        <p class="text-xs text-slate-500">Comprehensive breakdown of sales, platform fee deductions, statutory GST, and merchant disbursements.</p>
                    </div>

                    <a 
                        href="{{ route('admin.commissions.export_report') }}" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5"
                    >
                        <span>⬇️ Download Financial CSV</span>
                    </a>
                </div>

                <!-- Ledger Summary Card -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Gross Marketplace Revenue</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-slate-500">Delivered Orders Base Sales:</span>
                                <span class="font-bold font-mono text-slate-900 dark:text-white">₹{{ number_format($metrics['gross_sales'], 2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-700/60 text-rose-600">
                                <span>Less: Refund &amp; Return Adjustments:</span>
                                <span class="font-bold font-mono">- ₹{{ number_format($metrics['refund_adjustments'], 2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 pt-2 font-black text-sm text-emerald-600">
                                <span>Net Platform Settled Sales:</span>
                                <span class="font-mono">₹{{ number_format($metrics['net_sales'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-3">
                        <h4 class="font-bold text-sm text-slate-900 dark:text-white">Platform Commission &amp; Deductions</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-slate-500">Net Platform Commission:</span>
                                <span class="font-bold font-mono text-emerald-600">₹{{ number_format($metrics['net_commission'], 2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-slate-500">Statutory GST on Commission (18%):</span>
                                <span class="font-bold font-mono text-teal-600">₹{{ number_format($metrics['net_tax'], 2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-700/60">
                                <span class="text-slate-500">Payment Gateway Costs:</span>
                                <span class="font-bold font-mono text-blue-600">₹{{ number_format($metrics['gateway_fee_deductions'], 2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 pt-2 font-black text-sm text-indigo-600">
                                <span>Total Net Seller Payable:</span>
                                <span class="font-mono">₹{{ number_format($metrics['net_seller_payable'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal with Alpine.js -->
    <div x-show="openCreateModal || editingRule !== null" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="closeModal()" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white" x-text="editingRule ? 'Edit Commission Rule' : 'Create New Commission Rule'"></h3>
                <button @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    ✕
                </button>
            </div>

            <form :action="editingRule ? ('/admin/commissions/' + editingRule.id) : '{{ route('admin.commissions.store') }}'" method="POST" class="mt-4 space-y-4 text-xs">
                @csrf
                <template x-if="editingRule">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Rule Name *</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5" placeholder="e.g. Smart Watch Q4 Promotional Exception">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Scope *</label>
                        <select name="scope" x-model="form.scope" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                            <option value="global">Global Default</option>
                            <option value="category">Category Specific</option>
                            <option value="seller">Seller Specific</option>
                            <option value="product">Product Specific Exception</option>
                            <option value="promo">Promotional Window</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Priority (0-999) *</label>
                        <input type="number" name="priority" x-model="form.priority" required min="0" max="999" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                    </div>
                </div>

                <div x-show="form.scope === 'product'" class="space-y-1">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300">Target Product</label>
                    <select name="product_id" x-model="form.product_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                        <option value="">Select Target Product</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="form.scope === 'seller'" class="space-y-1">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300">Target Seller</label>
                    <select name="seller_id" x-model="form.seller_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                        <option value="">Select Target Seller</option>
                        @foreach($sellers as $s)
                            <option value="{{ $s->id }}">{{ $s->business_name }} (#{{ $s->id }})</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="form.scope === 'category'" class="space-y-1">
                    <label class="block font-semibold text-slate-700 dark:text-slate-300">Target Category</label>
                    <select name="category_id" x-model="form.category_id" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                        <option value="">Select Target Category</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Commission Rate (%) *</label>
                        <input type="number" step="0.01" name="percent" x-model="form.percent" required min="0" max="100" class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Fixed Fee (₹)</label>
                        <input type="number" step="0.01" name="fixed_fee" x-model="form.fixed_fee" min="0" class="w-full text-xs font-bold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5" placeholder="0.00">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Starts At (Effective Window)</label>
                        <input type="datetime-local" name="starts_at" x-model="form.starts_at" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Ends At (Effective Window)</label>
                        <input type="datetime-local" name="ends_at" x-model="form.ends_at" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5">
                    </div>
                </div>

                <div class="flex items-center space-x-2 pt-1">
                    <input type="checkbox" id="is_active_box" name="is_active" value="1" x-model="form.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="is_active_box" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Rule is active immediately</label>
                </div>

                <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="closeModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs">
                        Save Commission Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function commissionSimulator() {
    return {
        openCreateModal: false,
        editingRule: null,
        simSellerId: '',
        simCategoryId: '',
        simProductId: '',
        simAmount: 1000.00,
        simShipping: 0.00,
        simResult: {},
        form: {
            name: '',
            scope: 'global',
            category_id: '',
            seller_id: '',
            product_id: '',
            percent: 10.00,
            fixed_fee: 0.00,
            starts_at: '',
            ends_at: '',
            priority: 10,
            is_active: true
        },
        init() {
            this.runSimulation();
        },
        runSimulation() {
            fetch('{{ route('admin.commissions.simulate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    seller_id: this.simSellerId || null,
                    category_id: this.simCategoryId || null,
                    product_id: this.simProductId || null,
                    amount: this.simAmount || 0,
                    shipping_adjustment: this.simShipping || 0
                })
            })
            .then(res => res.json())
            .then(data => {
                this.simResult = data;
            })
            .catch(err => console.error(err));
        },
        openEditModal(rule) {
            this.editingRule = rule;
            this.form = {
                name: rule.name,
                scope: rule.scope,
                category_id: rule.category_id || '',
                seller_id: rule.seller_id || '',
                product_id: rule.product_id || '',
                percent: rule.percent,
                fixed_fee: rule.fixed_fee || 0.00,
                starts_at: rule.starts_at ? rule.starts_at.slice(0, 16) : '',
                ends_at: rule.ends_at ? rule.ends_at.slice(0, 16) : '',
                priority: rule.priority,
                is_active: Boolean(rule.is_active)
            };
        },
        closeModal() {
            this.openCreateModal = false;
            this.editingRule = null;
        }
    };
}
</script>
@endsection
