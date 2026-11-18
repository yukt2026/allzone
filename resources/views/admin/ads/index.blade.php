@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Seller Ad Campaigns - ALLZONE Admin'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    rejectModalOpen: false,
    rejectCampaignId: null,
    rejectCampaignName: '',
    metricsModalOpen: false,
    metricsCampaignId: null,
    metricsCampaignName: '',
    impressions: 0,
    clicks: 0,
    orders: 0,
    revenue: 0,
    spend: 0,
    openReject(id, name) {
        this.rejectCampaignId = id;
        this.rejectCampaignName = name;
        this.rejectModalOpen = true;
    },
    openMetrics(id, name, imp, clk, ord, rev, spd) {
        this.metricsCampaignId = id;
        this.metricsCampaignName = name;
        this.impressions = imp;
        this.clicks = clk;
        this.orders = ord;
        this.revenue = rev;
        this.spend = spd;
        this.metricsModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin Console</a>
                <span>&gt;</span>
                <span class="text-slate-800 font-semibold">Advertising Management</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-2">
                <span>Seller Ad Campaigns</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-semibold">
                    Ad Governance &amp; Attribution
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Audit seller campaign submissions, enforce listing &amp; creative guidelines, and record verified delivery metrics.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.ads.export', request()->query()) }}" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('admin.influencers.index') }}" class="inline-flex items-center space-x-2 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Influencer Management</span>
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Platform Ad KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Total Campaigns</span>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($metrics['total']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-amber-600">Pending Review</span>
            <div class="text-2xl font-black text-amber-700 mt-1">{{ number_format($metrics['pending']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-emerald-600">Active</span>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ number_format($metrics['active']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Paused</span>
            <div class="text-2xl font-black text-slate-700 mt-1">{{ number_format($metrics['paused']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-rose-600">Rejected</span>
            <div class="text-2xl font-black text-rose-700 mt-1">{{ number_format($metrics['rejected']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500">Total Ad Spend</span>
            <div class="text-xl font-black text-slate-900 mt-1">₹{{ number_format($metrics['total_spend'], 2) }}</div>
        </div>
    </div>

    <!-- Filter & Status Tabs -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-4 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-1 overflow-x-auto text-xs pb-1 sm:pb-0">
                @php
                    $tabs = [
                        'all' => 'All Campaigns',
                        'pending_approval' => 'Pending Review (' . $metrics['pending'] . ')',
                        'active' => 'Active (' . $metrics['active'] . ')',
                        'paused' => 'Paused',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tabLabel)
                    <a href="{{ route('admin.ads.index', array_merge(request()->query(), ['status' => $tabKey, 'page' => 1])) }}"
                       class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition {{ $status === $tabKey ? 'bg-slate-900 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search -->
            <form method="GET" action="{{ route('admin.ads.index') }}" class="flex items-center space-x-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search campaign or seller..."
                           class="w-56 text-xs rounded-lg border border-slate-200 pl-8 pr-3 py-1.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Campaigns Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold">
                        <th class="py-3 px-3">Campaign &amp; Creative</th>
                        <th class="py-3 px-3">Seller</th>
                        <th class="py-3 px-3">Targeting Type</th>
                        <th class="py-3 px-3">Products</th>
                        <th class="py-3 px-3">Budget (Daily / Total)</th>
                        <th class="py-3 px-3">Performance (Spend / Rev)</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaigns as $campaign)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-3">
                                <div class="flex items-center space-x-3">
                                    @if($campaign->creative_image_url)
                                        <img src="{{ $campaign->creative_image_url }}" alt="Creative" class="w-10 h-10 object-cover rounded-lg border border-slate-200 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 font-bold flex items-center justify-center text-xs shrink-0 border border-emerald-100">
                                            AD
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $campaign->name }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $campaign->starts_at?->format('d M Y') }} - {{ $campaign->ends_at ? $campaign->ends_at->format('d M Y') : 'Ongoing' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-900">{{ $campaign->seller?->business_name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $campaign->seller?->user?->email ?? '' }}</div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="capitalize px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ str_replace('_', ' ', $campaign->type) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-semibold text-slate-800">
                                {{ $campaign->products_count }} {{ Str::plural('product', $campaign->products_count) }}
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-900">₹{{ number_format($campaign->daily_budget, 2) }}/day</div>
                                <div class="text-[11px] text-slate-500">Cap: ₹{{ number_format($campaign->total_budget, 2) }}</div>
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-900">Spend: ₹{{ number_format($campaign->spent_amount, 2) }}</div>
                                <div class="text-[11px] text-emerald-600 font-medium">Rev: ₹{{ number_format($campaign->attributed_revenue, 2) }} ({{ $campaign->attributed_orders_count }} ord)</div>
                            </td>
                            <td class="py-3 px-3">
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
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badge }} uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $campaign->status) }}
                                </span>
                                @if($campaign->rejection_reason)
                                    <div class="text-[10px] text-rose-600 mt-1 max-w-xs truncate" title="{{ $campaign->rejection_reason }}">
                                        Reason: {{ $campaign->rejection_reason }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('admin.ads.show', $campaign) }}" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-xs font-bold hover:bg-emerald-100 transition inline-block">
                                    Review
                                </a>

                                @if($campaign->status === 'pending_approval')
                                    <form method="POST" action="{{ route('admin.ads.approve', $campaign) }}" class="inline" data-confirm="Approve this campaign for live ad delivery?">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" @click="openReject({{ $campaign->id }}, '{{ addslashes($campaign->name) }}')"
                                            class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded text-xs font-semibold hover:bg-rose-100 transition">
                                        Reject
                                    </button>
                                @endif

                                @if(in_array($campaign->status, ['active', 'paused', 'completed']))
                                    <button type="button" @click="openMetrics({{ $campaign->id }}, '{{ addslashes($campaign->name) }}', {{ $campaign->impressions_count }}, {{ $campaign->clicks_count }}, {{ $campaign->attributed_orders_count }}, {{ $campaign->attributed_revenue }}, {{ $campaign->spent_amount }})"
                                            class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded text-xs font-semibold hover:bg-slate-200 transition">
                                        Update Analytics
                                    </button>
                                @endif

                                @if($campaign->status === 'active')
                                    <button type="button" @click="openReject({{ $campaign->id }}, '{{ addslashes($campaign->name) }}')"
                                            class="px-2.5 py-1 text-rose-600 hover:underline text-xs">
                                        Stop/Reject
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                No ad campaigns found matching current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-slate-100">
            {{ $campaigns->links() }}
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="rejectModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="rejectModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 text-base">Reject Campaign</h3>
                    <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form :action="'{{ url('admin/ads') }}/' + rejectCampaignId + '/reject'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <p class="text-xs text-slate-600 mb-2">
                            Rejecting <span class="font-bold text-slate-900" x-text="rejectCampaignName"></span>. Please provide a clear, factual reason for the seller.
                        </p>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" rows="3" required placeholder="e.g. Creative violates platform guidelines, non-compliant promotional text..."
                                  class="w-full text-xs rounded-lg border border-slate-200 p-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-xs font-semibold hover:bg-rose-700 transition">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Verified Analytics Modal -->
    <div x-show="metricsModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="metricsModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-slate-900 text-base">Record Verified Delivery Analytics</h3>
                        <p class="text-xs text-slate-500" x-text="'Campaign: ' + metricsCampaignName"></p>
                    </div>
                    <button type="button" @click="metricsModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form :action="'{{ url('admin/ads') }}/' + metricsCampaignId + '/metrics'" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Total Impressions</label>
                            <input type="number" name="impressions_count" x-model.number="impressions" min="0" required
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Total Clicks</label>
                            <input type="number" name="clicks_count" x-model.number="clicks" min="0" required
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Attributed Orders</label>
                            <input type="number" name="attributed_orders_count" x-model.number="orders" min="0" required
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Attributed Revenue (₹)</label>
                            <input type="number" step="0.01" name="attributed_revenue" x-model.number="revenue" min="0" required
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Actual Ad Spend (₹)</label>
                        <input type="number" step="0.01" name="spent_amount" x-model.number="spend" min="0" required
                               class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <p class="text-[11px] text-slate-400">
                        ROI and CTR will be strictly computed from these verified values. Never enter simulated or fabricated figures.
                    </p>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="metricsModalOpen = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition">
                            Update Metrics
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
