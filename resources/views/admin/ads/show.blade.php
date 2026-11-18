@extends('layouts.dashboard', ['portal' => 'admin', 'title' => "Ad Campaign Review: {$campaign->name}"])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    budgetModal: false,
    rejectModal: false,
    metricsModal: false,
    dailyBudget: {{ $campaign->daily_budget }},
    totalBudget: {{ $campaign->total_budget }},
    billingStatus: '{{ $campaign->billing_status ?? 'active_wallet' }}',
    billingNotes: '{{ addslashes($campaign->billing_notes ?? '') }}',
    rejectionReason: '',
    impressions: {{ $campaign->impressions_count }},
    clicks: {{ $campaign->clicks_count }},
    orders: {{ $campaign->attributed_orders_count }},
    revenue: {{ $campaign->attributed_revenue }},
    spend: {{ $campaign->spent_amount }}
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin Central</a>
                <span>&gt;</span>
                <a href="{{ route('admin.ads.index') }}" class="hover:underline">Ad Campaigns</a>
                <span>&gt;</span>
                <span class="text-slate-800 dark:text-white font-bold">#{{ $campaign->id }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $campaign->name }}</h1>
                @php
                    $badge = match($campaign->status) {
                        'active' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'pending_approval' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'paused' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                        'completed' => 'bg-blue-100 text-blue-800 border-blue-200',
                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                    };
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badge }}">
                    {{ ucfirst(str_replace('_', ' ', $campaign->status)) }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                    {{ str_replace('_', ' ', $campaign->type) }}
                </span>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="flex items-center space-x-2">
            @if($campaign->status === 'pending_approval' || $campaign->status === 'rejected')
                <form action="{{ route('admin.ads.approve', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Approve Campaign
                    </button>
                </form>
            @endif

            @if($campaign->status === 'active')
                <form action="{{ route('admin.ads.status', $campaign) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="paused">
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Pause Delivery
                    </button>
                </form>
            @elseif($campaign->status === 'paused')
                <form action="{{ route('admin.ads.status', $campaign) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Resume Delivery
                    </button>
                </form>
            @endif

            @if($campaign->status !== 'rejected')
                <button type="button" @click="rejectModal = true" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition">
                    Reject
                </button>
            @endif

            <button type="button" @click="budgetModal = true" class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold hover:bg-slate-50 transition shadow-xs">
                Budget &amp; Billing
            </button>

            <button type="button" @click="metricsModal = true" class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition shadow-xs">
                Update Metrics
            </button>
        </div>
    </div>

    <!-- Rejection Alert if rejected -->
    @if($campaign->status === 'rejected' && $campaign->rejection_reason)
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start space-x-2">
            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong class="font-bold">Rejection Reason:</strong>
                <p class="mt-0.5">{{ $campaign->rejection_reason }}</p>
            </div>
        </div>
    @endif

    <!-- Key Performance Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ad Spend</span>
            <div class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">₹{{ number_format((float) $campaign->spent_amount, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Cap: ₹{{ number_format((float) $campaign->total_budget, 2) }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-emerald-500">
            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Attributed Revenue</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">₹{{ number_format((float) $campaign->attributed_revenue, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ $campaign->attributed_orders_count }} orders converted</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-indigo-500">
            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Return on Ad Spend (ROI)</span>
            <div class="text-2xl font-black font-mono text-indigo-600 dark:text-indigo-400 mt-1">{{ $campaign->roi !== null ? $campaign->roi . 'x' : 'N/A' }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Revenue / Spend</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-teal-500">
            <span class="text-[11px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">Impressions</span>
            <div class="text-2xl font-black font-mono text-teal-600 dark:text-teal-400 mt-1">{{ number_format($campaign->impressions_count) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Verified ad views</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs border-l-4 border-l-purple-500">
            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Clicks &amp; CTR</span>
            <div class="text-2xl font-black font-mono text-purple-600 dark:text-purple-400 mt-1">{{ number_format($campaign->clicks_count) }}</div>
            <div class="text-[11px] text-purple-600 font-bold mt-0.5">{{ $campaign->ctr }}% CTR</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Billing Status</span>
            <div class="text-sm font-black font-mono uppercase text-slate-800 dark:text-slate-200 mt-2">
                {{ str_replace('_', ' ', $campaign->billing_status ?? 'active_wallet') }}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">Daily: ₹{{ number_format((float) $campaign->daily_budget, 2) }}</div>
        </div>
    </div>

    <!-- Creative Review & Targeting Specification -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Creative Asset & Headline -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Creative Asset &amp; Copy Review</h3>
                <p class="text-xs text-slate-400">Merchant submitted creative assets for placement audit.</p>
            </div>

            @if($campaign->creative_image_url)
                <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <img src="{{ $campaign->creative_image_url }}" alt="Creative banner" class="w-full h-48 object-cover">
                </div>
            @else
                <div class="h-32 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center text-slate-400 text-xs">
                    <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>No banner creative image attached</span>
                </div>
            @endif

            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Headline Copy</span>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl text-xs font-semibold text-slate-800 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700">
                    {{ $campaign->headline ?? 'No headline provided (Listing placement only).' }}
                </div>
            </div>

            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Targeting Payload</span>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl text-xs font-mono text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                    @if(!empty($campaign->targeting_payload))
                        <pre class="text-[11px] whitespace-pre-wrap">{{ json_encode($campaign->targeting_payload, JSON_PRETTY_PRINT) }}</pre>
                    @else
                        <span>Type: {{ ucfirst((string)$campaign->targeting_type) }} (Storewide / Category targeted)</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Merchant & Timeline Details -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Merchant &amp; Budget Details</h3>
                <p class="text-xs text-slate-400">Accountability and billing parameters.</p>
            </div>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Merchant Store</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $campaign->seller?->business_name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Seller Account Email</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ $campaign->seller?->user?->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Daily Spend Cap</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format((float) $campaign->daily_budget, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Total Campaign Cap</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format((float) $campaign->total_budget, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Schedule Window</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300">
                        {{ $campaign->starts_at?->format('d M Y') ?? 'Immediate' }} &rarr; {{ $campaign->ends_at?->format('d M Y') ?? 'Ongoing' }}
                    </span>
                </div>
                <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-slate-400">Approved By Admin</span>
                    <span class="text-slate-700 dark:text-slate-300">{{ $campaign->approvedBy?->name ?? 'Pending Review' }}</span>
                </div>
                <div class="flex justify-between py-1.5">
                    <span class="text-slate-400">Approved At</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ $campaign->approved_at?->format('d M Y, H:i') ?? 'N/A' }}</span>
                </div>
            </div>

            @if($campaign->billing_notes)
                <div class="p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-900 dark:text-amber-200">
                    <strong>Billing Notes:</strong> {{ $campaign->billing_notes }}
                </div>
            @endif
        </div>

        <!-- Promoted Products Listing -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Promoted Catalog Listings</h3>
                    <p class="text-xs text-slate-400">{{ $campaign->products->count() }} assigned product listings.</p>
                </div>
            </div>

            <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                @forelse($campaign->products as $adProd)
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700 text-xs flex items-center justify-between">
                        <div class="max-w-[180px]">
                            <div class="font-bold text-slate-900 dark:text-white truncate">
                                {{ $adProd->product?->title ?? 'Product #' . $adProd->product_id }}
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono">
                                Bid: ₹{{ number_format((float) $adProd->bid_amount, 2) }}
                            </div>
                        </div>
                        <div class="text-right font-mono text-[11px]">
                            <div class="font-bold text-emerald-600">₹{{ number_format((float) $adProd->attributed_revenue, 2) }}</div>
                            <div class="text-[10px] text-slate-400">{{ $adProd->impressions }} imp / {{ $adProd->clicks }} clk</div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400 text-xs">
                        No specific products attached to this campaign.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Budget & Billing Modal -->
    <div x-show="budgetModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="budgetModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Budget &amp; Billing Configuration</h3>
                <p class="text-xs text-slate-500 mb-4">Set verified daily/total budget caps and merchant deduction status.</p>

                <form action="{{ route('admin.ads.budget', $campaign) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Daily Budget (₹)</label>
                        <input type="number" step="10" min="10" name="daily_budget" x-model="dailyBudget" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Total Campaign Budget (₹)</label>
                        <input type="number" step="50" min="50" name="total_budget" x-model="totalBudget" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Billing Settlement Status</label>
                        <select name="billing_status" x-model="billingStatus" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            <option value="active_wallet">Active (Deducted from Ad Wallet)</option>
                            <option value="pending_deduction">Pending Deduction from Next Payout</option>
                            <option value="deducted_from_payout">Deducted from Settlement Payout</option>
                            <option value="settled">Fully Settled &amp; Reconciled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Billing Audit Notes</label>
                        <textarea name="billing_notes" rows="2" x-model="billingNotes" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="budgetModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 rounded-xl hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-xs">Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="rejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="rejectModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Reject Ad Campaign</h3>
                <p class="text-xs text-slate-500 mb-4">Mandatory reason will be visible to the seller on their Ad Console.</p>

                <form action="{{ route('admin.ads.reject', $campaign) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="3" required x-model="rejectionReason" placeholder="State creative violation, keyword mismatch, or policy reason..." class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="rejectModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 rounded-xl hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-xl shadow-xs">Reject Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Metrics Update Modal -->
    <div x-show="metricsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="metricsModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Record Verified Delivery Metrics</h3>
                <p class="text-xs text-slate-500 mb-4">Update authentic platform ad impressions, clicks, spend, and conversions.</p>

                <form action="{{ route('admin.ads.metrics.update', $campaign) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Ad Impressions</label>
                        <input type="number" min="0" name="impressions_count" x-model="impressions" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Clicks</label>
                        <input type="number" min="0" name="clicks_count" x-model="clicks" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attributed Orders Count</label>
                        <input type="number" min="0" name="attributed_orders_count" x-model="orders" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attributed Revenue (₹)</label>
                        <input type="number" step="0.01" min="0" name="attributed_revenue" x-model="revenue" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Actual Ad Spend (₹)</label>
                        <input type="number" step="0.01" min="0" name="spent_amount" x-model="spend" required class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="metricsModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 rounded-xl hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-slate-900 hover:bg-black text-white rounded-xl shadow-xs">Save Metrics</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
