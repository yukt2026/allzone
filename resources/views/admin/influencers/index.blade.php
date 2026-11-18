@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Influencer Marketing Governance - ALLZONE Admin'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    createProfileModal: false,
    statusModalOpen: false,
    paymentModalOpen: false,
    selectedCollabId: null,
    selectedSeller: '',
    selectedInfluencer: '',
    currentStatus: 'pending',
    deliverableUrl: '',
    adminNotes: '',
    impressions: 0,
    clicks: 0,
    orders: 0,
    revenue: 0,
    paymentStatus: 'unpaid',
    paymentRef: '',
    openStatusModal(id, seller, influencer, status, deliverable, notes, imp, clk, ord, rev) {
        this.selectedCollabId = id;
        this.selectedSeller = seller;
        this.selectedInfluencer = influencer;
        this.currentStatus = status;
        this.deliverableUrl = deliverable || '';
        this.adminNotes = notes || '';
        this.impressions = imp || 0;
        this.clicks = clk || 0;
        this.orders = ord || 0;
        this.revenue = rev || 0;
        this.statusModalOpen = true;
    },
    openPaymentModal(id, seller, influencer, pStatus, pRef) {
        this.selectedCollabId = id;
        this.selectedSeller = seller;
        this.selectedInfluencer = influencer;
        this.paymentStatus = pStatus || 'unpaid';
        this.paymentRef = pRef || '';
        this.paymentModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin Central</a>
                <span>&gt;</span>
                <span class="text-slate-800 dark:text-white font-semibold">Influencer Governance</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Influencer Marketing Governance</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-semibold">
                    Admin Verification
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Onboard vetted creator profiles, govern seller brand collaborations, review deliverables, and audit payments.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.influencers.export') }}" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl hover:bg-slate-50 transition shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('admin.ads.index') }}" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl hover:bg-slate-50 transition shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <span>Ad Campaigns</span>
            </a>
            <button type="button" @click="createProfileModal = true" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Influencer Profile</span>
            </button>
        </div>
    </div>

    <!-- Influencer KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Influencers</span>
            <div class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ number_format($metrics['total_profiles']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ $metrics['verified_profiles'] }} verified creators</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs border-l-4 border-l-emerald-500">
            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Active Creators</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($metrics['active_profiles']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Ready for brand deals</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs border-l-4 border-l-amber-500">
            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Pending Proposals</span>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ number_format($metrics['pending_proposals']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Awaiting assignment</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs border-l-4 border-l-blue-500">
            <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">In Progress</span>
            <div class="text-2xl font-black font-mono text-blue-600 dark:text-blue-400 mt-1">{{ number_format($metrics['in_progress_collaborations']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">Deliverable creation</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs border-l-4 border-l-purple-500">
            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Completed</span>
            <div class="text-2xl font-black font-mono text-purple-600 dark:text-purple-400 mt-1">{{ number_format($metrics['completed_collaborations']) }}</div>
            <div class="text-[11px] text-slate-400 mt-0.5">{{ number_format($metrics['total_attributed_orders']) }} orders driven</div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs border-l-4 border-l-teal-500">
            <span class="text-[11px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">Pipeline Budget</span>
            <div class="text-xl font-black font-mono text-slate-900 dark:text-white mt-1">₹{{ number_format($metrics['total_spend'], 0) }}</div>
            <div class="text-[11px] text-emerald-600 font-bold mt-0.5">₹{{ number_format($metrics['total_paid'], 0) }} paid</div>
        </div>
    </div>

    <!-- Navigation Tabs: Profiles vs Seller Collaborations -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('admin.influencers.index', ['tab' => 'profiles']) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center space-x-2 {{ $tab === 'profiles' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Vetted Creator Profiles ({{ $profiles->total() }})</span>
        </a>
        <a href="{{ route('admin.influencers.index', ['tab' => 'collaborations']) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center space-x-2 {{ $tab === 'collaborations' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Seller Collaboration Requests &amp; Deliverables ({{ $collaborations->total() }})</span>
        </a>
    </div>

    @if($tab === 'profiles')
        <!-- Influencer Profiles Directory -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Platform Approved Influencer Directory</h3>
                    <p class="text-xs text-slate-400">Vetted creator network available for seller brand endorsement contracts.</p>
                </div>

                <form method="GET" action="{{ route('admin.influencers.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="tab" value="profiles">
                    <select name="platform" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        <option value="all">All Platforms</option>
                        <option value="instagram" {{ $platform === 'instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="youtube" {{ $platform === 'youtube' ? 'selected' : '' }}>YouTube</option>
                        <option value="x" {{ $platform === 'x' ? 'selected' : '' }}>X / Twitter</option>
                        <option value="blog" {{ $platform === 'blog' ? 'selected' : '' }}>Blog</option>
                    </select>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search name or handle..." class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 w-48">
                    <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3.5 px-4">Influencer / Creator</th>
                            <th class="py-3.5 px-3">Platform</th>
                            <th class="py-3.5 px-3">Category Focus</th>
                            <th class="py-3.5 px-3">Verified Reach</th>
                            <th class="py-3.5 px-3">Base Collab Fee</th>
                            <th class="py-3.5 px-3">Verification Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($profiles as $profile)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $profile->name }}</div>
                                    <div class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400">{{ $profile->handle }}</div>
                                    @if($profile->contact_email)
                                        <div class="text-[10px] text-slate-400">{{ $profile->contact_email }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    <span class="capitalize px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $profile->platform }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-slate-600 dark:text-slate-300">
                                    {{ $profile->category?->name ?? 'General / Lifestyle' }}
                                </td>
                                <td class="py-3.5 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                    {{ number_format($profile->verified_followers) }}
                                </td>
                                <td class="py-3.5 px-3 font-mono font-bold text-emerald-600">
                                    ₹{{ number_format((float) $profile->collaboration_fee, 2) }}
                                </td>
                                <td class="py-3.5 px-3">
                                    @if($profile->is_approved)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Unverified
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.influencers.profiles.toggle_verification', $profile) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border {{ $profile->is_approved ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }} transition">
                                            {{ $profile->is_approved ? 'Unverify' : 'Verify' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No influencer profiles found. Click "Add Influencer Profile" to register vetted creators.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $profiles->links() }}
            </div>
        </div>
    @else
        <!-- Seller Collaborations Tab -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Seller Brand Deals &amp; Deliverables</h3>
                    <p class="text-xs text-slate-400">Audit proposal contracts, track deliverable links, and verify conversion revenue.</p>
                </div>

                <form method="GET" action="{{ route('admin.influencers.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="tab" value="collaborations">
                    <select name="status" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        <option value="all">All Workflow Statuses</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Proposal</option>
                        <option value="accepted" {{ $status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <select name="payment_status" class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        <option value="all">All Payment Statuses</option>
                        <option value="unpaid" {{ $paymentStatus === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="in_escrow" {{ $paymentStatus === 'in_escrow' ? 'selected' : '' }}>In Escrow</option>
                        <option value="paid" {{ $paymentStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search title or seller..." class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 w-48">
                    <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="py-3.5 px-4">Collab #</th>
                            <th class="py-3.5 px-3">Merchant Seller</th>
                            <th class="py-3.5 px-3">Influencer</th>
                            <th class="py-3.5 px-3">Campaign Assignment &amp; Product</th>
                            <th class="py-3.5 px-3">Proposed Budget</th>
                            <th class="py-3.5 px-3">Payment Status</th>
                            <th class="py-3.5 px-3">Deliverable &amp; Performance</th>
                            <th class="py-3.5 px-3">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($collaborations as $collab)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                    #{{ $collab->id }}
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $collab->seller?->business_name ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $collab->seller?->user?->email }}</div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $collab->influencer?->name ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $collab->influencer?->handle }}</div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $collab->campaign_title }}</div>
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400">
                                        {{ $collab->product?->title ?? 'Storewide Promotion' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format((float) $collab->proposed_budget, 2) }}
                                </td>
                                <td class="py-3.5 px-3">
                                    @php
                                        $pBadge = match($collab->payment_status) {
                                            'paid' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'in_escrow' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $pBadge }}">
                                        {{ ucfirst((string)$collab->payment_status) }}
                                    </span>
                                    @if($collab->payment_reference)
                                        <div class="text-[9px] font-mono text-slate-400 mt-0.5">Ref: {{ $collab->payment_reference }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3">
                                    @if($collab->deliverable_url)
                                        <a href="{{ $collab->deliverable_url }}" target="_blank" class="text-indigo-600 hover:underline font-bold text-[11px] flex items-center space-x-1">
                                            <span>View Content &rarr;</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Awaiting Link</span>
                                    @endif
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        {{ number_format($collab->impressions) }} imp &bull; {{ number_format($collab->clicks) }} clk &bull; ₹{{ number_format((float) $collab->attributed_revenue, 0) }} rev
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    @php
                                        $collabBadge = match($collab->status) {
                                            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                            'accepted' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            'declined', 'cancelled' => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $collabBadge }} uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $collab->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button type="button" @click="openStatusModal({{ $collab->id }}, '{{ addslashes($collab->seller?->business_name ?? 'Seller') }}', '{{ addslashes($collab->influencer?->name ?? 'Influencer') }}', '{{ $collab->status }}', '{{ addslashes($collab->deliverable_url ?? '') }}', '{{ addslashes($collab->admin_notes ?? '') }}', {{ $collab->impressions }}, {{ $collab->clicks }}, {{ $collab->attributed_orders }}, {{ $collab->attributed_revenue }})"
                                                class="px-2.5 py-1 bg-slate-900 hover:bg-black text-white rounded-lg text-[11px] font-bold transition">
                                            Workflow
                                        </button>
                                        <button type="button" @click="openPaymentModal({{ $collab->id }}, '{{ addslashes($collab->seller?->business_name ?? 'Seller') }}', '{{ addslashes($collab->influencer?->name ?? 'Influencer') }}', '{{ $collab->payment_status }}', '{{ addslashes($collab->payment_reference ?? '') }}')"
                                                class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold transition" title="Update Payment Record">
                                            Payment
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">
                                    No collaboration proposals found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $collaborations->links() }}
            </div>
        </div>
    @endif

    <!-- Add Influencer Profile Modal -->
    <div x-show="createProfileModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="createProfileModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Onboard Verified Influencer Profile</h3>
                    <button type="button" @click="createProfileModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form action="{{ route('admin.influencers.profiles.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Creator Name *</label>
                            <input type="text" name="name" required placeholder="e.g. Priya Lifestyle" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Handle / Channel Link *</label>
                            <input type="text" name="handle" required placeholder="e.g. @priya_style" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Platform *</label>
                            <select name="platform" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                                <option value="instagram">Instagram</option>
                                <option value="youtube">YouTube</option>
                                <option value="x">X / Twitter</option>
                                <option value="blog">Blog</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                            <select name="category_id" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                                <option value="">Storewide</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Followers *</label>
                            <input type="number" name="verified_followers" required min="0" placeholder="e.g. 50000" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Collaboration Fee (₹)</label>
                            <input type="number" step="0.01" name="collaboration_fee" min="0" placeholder="e.g. 5000.00" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Contact Email</label>
                            <input type="email" name="contact_email" placeholder="contact@creator.in" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Bio &amp; Niche Description</label>
                        <textarea name="bio" rows="2" placeholder="Content focus, audience demographics..." class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="createProfileModal = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs">Register &amp; Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Collaboration Status Modal -->
    <div x-show="statusModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="statusModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Manage Collaboration Workflow</h3>
                        <p class="text-xs text-slate-500" x-text="selectedSeller + ' ↔ ' + selectedInfluencer"></p>
                    </div>
                    <button type="button" @click="statusModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form :action="'{{ url('admin/influencers/collaborations') }}/' + selectedCollabId + '/status'" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Collaboration Status *</label>
                        <select name="status" x-model="currentStatus" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="declined">Declined</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deliverable URL (Reel / YouTube video link)</label>
                        <input type="url" name="deliverable_url" x-model="deliverableUrl" placeholder="https://instagram.com/p/..." class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Impressions</label>
                            <input type="number" name="impressions" x-model.number="impressions" min="0" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Clicks</label>
                            <input type="number" name="clicks" x-model.number="clicks" min="0" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attributed Orders</label>
                            <input type="number" name="attributed_orders" x-model.number="orders" min="0" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attributed Revenue (₹)</label>
                            <input type="number" step="0.01" name="attributed_revenue" x-model.number="revenue" min="0" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Admin Notes</label>
                        <textarea name="admin_notes" x-model="adminNotes" rows="2" placeholder="Internal communication, delivery timeline, payment terms..." class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="statusModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Payment Record Modal -->
    <div x-show="paymentModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="paymentModalOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Record Payment Status</h3>
                    <button type="button" @click="paymentModalOpen = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <form :action="'{{ url('admin/influencers/collaborations') }}/' + selectedCollabId + '/payment'" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Status *</label>
                        <select name="payment_status" x-model="paymentStatus" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                            <option value="unpaid">Unpaid</option>
                            <option value="in_escrow">In Escrow (Held by Platform)</option>
                            <option value="paid">Paid to Creator</option>
                            <option value="cancelled">Cancelled / Refunded to Seller</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Reference / UTR #</label>
                        <input type="text" name="payment_reference" x-model="paymentRef" placeholder="e.g. UTR-987234823 or Razorpay Payout ID" class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-2.5">
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="paymentModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs">Save Payment Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
