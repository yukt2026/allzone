@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Seller Finance Governance - ALLZONE Admin'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" x-data="{
    appModalOpen: false,
    selectedAppId: null,
    selectedSellerName: '',
    currentStatus: 'under_review',
    approvedLimit: 0,
    adminNotes: '',
    rejectionReason: '',
    providerRef: '',
    txModalOpen: false,
    txAppId: null,
    txSellerName: '',
    txType: 'disbursement',
    txAmount: 0,
    txRef: '',
    txNotes: '',
    openAppModal(id, seller, status, limit, notes, reason, ref) {
        this.selectedAppId = id;
        this.selectedSellerName = seller;
        this.currentStatus = status;
        this.approvedLimit = limit || 0;
        this.adminNotes = notes || '';
        this.rejectionReason = reason || '';
        this.providerRef = ref || '';
        this.appModalOpen = true;
    },
    openTxModal(id, seller) {
        this.txAppId = id;
        this.txSellerName = seller;
        this.txType = 'disbursement';
        this.txAmount = 0;
        this.txRef = 'TXN-' + Math.floor(100000 + Math.random() * 900000);
        this.txNotes = '';
        this.txModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">Admin Console</a>
                <span>&gt;</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold">Financial Governance</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center space-x-2">
                <span>Seller Finance Governance</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-900 dark:bg-slate-800 text-white dark:text-slate-200 font-semibold border border-slate-700">
                    Regulatory Compliance
                </span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Oversee seller working capital facilitation, authorized partner integrations, and mandatory statutory disclosures.
            </p>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-xs flex items-center space-x-2">
            <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Governance Settings Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white">Program Availability &amp; Regulatory Disclosures</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Configure whether financing is active, coming soon, or unavailable on the seller panel.</p>
            </div>
            <div>
                @php
                    $statusBadge = match($settings['status']) {
                        'active_partner' => 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                        'coming_soon' => 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }} uppercase tracking-wider">
                    {{ str_replace('_', ' ', $settings['status']) }}
                </span>
            </div>
        </div>

        <form action="{{ route('admin.finance.settings.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Feature Status *</label>
                    <select name="seller_finance_status" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        <option value="not_available" {{ $settings['status'] === 'not_available' ? 'selected' : '' }}>Not Available (Hidden / Inactive)</option>
                        <option value="coming_soon" {{ $settings['status'] === 'coming_soon' ? 'selected' : '' }}>Coming Soon (Waitlist Open)</option>
                        <option value="active_partner" {{ $settings['status'] === 'active_partner' ? 'selected' : '' }}>Active Partner (Referrals Live)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Licensed Partner Name</label>
                    <input type="text" name="seller_finance_provider_name" value="{{ $settings['provider_name'] }}" placeholder="e.g. Authorized RBI Regulated Bank/NBFC"
                           class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Min Active Months</label>
                        <input type="number" name="seller_finance_min_months_active" value="{{ $settings['min_months'] }}" min="0" required
                               class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Min Orders</label>
                        <input type="number" name="seller_finance_min_orders" value="{{ $settings['min_orders'] }}" min="0" required
                               class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Mandatory Regulatory Disclaimer Text *</label>
                <textarea name="seller_finance_disclaimer" rows="2" required
                          class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">{{ $settings['disclaimer'] }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-2xs">
                    Save Governance Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Inquiries</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($metrics['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400">Waitlist Registered</span>
            <div class="text-2xl font-black text-blue-700 dark:text-blue-300 mt-1">{{ number_format($metrics['waitlist']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">Under Review</span>
            <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1">{{ number_format($metrics['under_review']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Approved</span>
            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ number_format($metrics['approved']) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Approved Limit</span>
            <div class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-1">₹{{ number_format($metrics['total_approved_limit'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Total Outstanding</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1">₹{{ number_format($metrics['total_outstanding'], 2) }}</div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-1 overflow-x-auto text-xs pb-1 sm:pb-0 scrollbar-none">
                @php
                    $tabs = [
                        'all' => 'All Inquiries',
                        'registered' => 'Registered / Waitlist',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tabLabel)
                    <a href="{{ route('admin.finance.index', array_merge(request()->query(), ['status' => $tabKey, 'page' => 1])) }}"
                       class="px-3 py-1.5 rounded-xl font-semibold whitespace-nowrap transition {{ $status === $tabKey ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Search -->
            <form method="GET" action="{{ route('admin.finance.index') }}" class="flex items-center space-x-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search seller, PAN or GSTIN..."
                           class="w-56 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white pl-8 pr-3 py-1.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-semibold rounded-xl transition shadow-2xs">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 font-bold text-[10px] uppercase tracking-wider">
                        <th class="py-3 px-3">Inquiry #</th>
                        <th class="py-3 px-3">Seller</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3">Requested / Approved Limit</th>
                        <th class="py-3 px-3">Outstanding</th>
                        <th class="py-3 px-3">PAN / Tax Ref</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">#{{ $app->id }}</td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $app->seller?->business_name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ $app->seller?->user?->email }}</div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="capitalize px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ str_replace('_', ' ', $app->application_type) }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900 dark:text-white">Req: ₹{{ number_format($app->requested_amount, 2) }}</div>
                                <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">Appr: ₹{{ number_format($app->approved_limit, 2) }}</div>
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                ₹{{ number_format($app->outstanding_balance, 2) }}
                            </td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-600 dark:text-slate-400">
                                {{ $app->pan_number ?: 'N/A' }}
                                @if($app->gstin)
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $app->gstin }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @php
                                    $appBadge = match($app->status) {
                                        'approved' => 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                        'under_review' => 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                        'rejected' => 'bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $appBadge }} uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $app->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right space-x-1 whitespace-nowrap">
                                <button type="button" @click="openAppModal({{ $app->id }}, '{{ addslashes($app->seller?->business_name ?? 'Seller') }}', '{{ $app->status }}', {{ $app->approved_limit }}, '{{ addslashes($app->admin_notes ?? '') }}', '{{ addslashes($app->rejection_reason ?? '') }}', '{{ addslashes($app->provider_reference_id ?? '') }}')"
                                        class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-lg text-xs font-semibold transition">
                                    Review
                                </button>
                                @if($app->status === 'approved')
                                    <button type="button" @click="openTxModal({{ $app->id }}, '{{ addslashes($app->seller?->business_name ?? 'Seller') }}')"
                                            class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-semibold hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition">
                                        + Transaction
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 px-4">
                                <x-empty-state 
                                    icon="currency-rupee"
                                    title="No Financing Applications"
                                    description="No seller financing records or applications found matching the selected filter criteria."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applications->hasPages())
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                {{ $applications->links() }}
            </div>
        @endif
    </div>

    <!-- Review Application Modal -->
    <div x-show="appModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="appModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Review Seller Finance Record</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="selectedSellerName"></p>
                    </div>
                    <button type="button" @click="appModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
                </div>

                <form :action="'{{ url('admin/seller-finance/applications') }}/' + selectedAppId" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Application Status *</label>
                            <select name="status" x-model="currentStatus" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="registered">Registered / Waitlist</option>
                                <option value="under_review">Under Review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Approved Facility Limit (₹)</label>
                            <input type="number" step="0.01" name="approved_limit" x-model.number="approvedLimit" min="0"
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Partner Reference ID</label>
                        <input type="text" name="provider_reference_id" x-model="providerRef" placeholder="e.g. NBFC-APP-2026-99"
                               class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div x-show="currentStatus === 'rejected'">
                        <label class="block text-xs font-semibold text-rose-700 dark:text-rose-400 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" x-model="rejectionReason" rows="2" placeholder="Explain underwriting decline reason..."
                                  class="w-full text-xs rounded-xl border border-rose-300 dark:border-rose-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:border-rose-500 focus:ring-1 focus:rose-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Internal Admin Notes</label>
                        <textarea name="admin_notes" x-model="adminNotes" rows="2" placeholder="Record partner underwriting findings or documentation status..."
                                  class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="appModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-2xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Record Ledger Transaction Modal -->
    <div x-show="txModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="txModalOpen = false"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-base">Record Ledger Transaction</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="txSellerName"></p>
                    </div>
                    <button type="button" @click="txModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none">&times;</button>
                </div>

                <form :action="'{{ url('admin/seller-finance/applications') }}/' + txAppId + '/transactions'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Transaction Type *</label>
                        <select name="transaction_type" x-model="txType" required class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="disbursement">Disbursement (Increases Balance)</option>
                            <option value="repayment">Repayment (Decreases Balance)</option>
                            <option value="fee">Fee / Charge</option>
                            <option value="adjustment">Balance Adjustment</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Amount (₹) *</label>
                            <input type="number" step="0.01" name="amount" x-model.number="txAmount" min="1" required
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference ID *</label>
                            <input type="text" name="reference_id" x-model="txRef" required
                                   class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Transaction Notes</label>
                        <textarea name="notes" x-model="txNotes" rows="2" placeholder="e.g. Bank UTR / Settlement deduction reference..."
                                  class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" @click="txModalOpen = false" class="px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition shadow-2xs">
                            Save Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
