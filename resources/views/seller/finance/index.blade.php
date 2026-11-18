@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Seller Growth Capital & Financing Facilitation - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Finance &amp; Capital</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Seller Growth Capital &amp; Financing</h1>
                @if($status === 'active_partner')
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                        ● Partner Live
                    </span>
                @elseif($status === 'coming_soon')
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">
                        ● Coming Soon
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300 uppercase tracking-wider">
                        ● Not Available
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Facilitating institutional working capital access for compliant ALLZONE marketplace sellers through regulated partners.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('seller.payments.index') }}" class="px-3.5 py-2 rounded-lg text-xs font-bold border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                &larr; Earnings &amp; Settlements
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('info'))
        <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Mandatory Legal & Regulatory Disclosure Alert -->
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs space-y-2">
        <div class="flex items-center space-x-2 text-slate-900 font-bold">
            <svg class="w-4 h-4 text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Regulatory Disclosure &amp; Platform Lending Disclaimer</span>
        </div>
        <p class="text-[11px] text-slate-600 leading-relaxed">
            {{ $disclaimer }}
        </p>
    </div>

    <!-- Real Marketplace Eligibility Track Record -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900">Marketplace Operational Track Record</h2>
                <p class="text-xs text-slate-500">Eligibility is strictly determined by your verified fulfillment and sales history on ALLZONE.</p>
            </div>
            <div>
                @if($isEligible)
                    <span class="inline-flex items-center space-x-1.5 px-3 py-1 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <span>Meets Baseline Eligibility</span>
                    </span>
                @else
                    <span class="inline-flex items-center space-x-1.5 px-3 py-1 bg-amber-50 text-amber-800 text-xs font-bold rounded-lg border border-amber-200">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Building Marketplace Track Record</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100">
                <span class="text-[11px] font-semibold text-slate-500">Active Tenure</span>
                <div class="text-lg font-black text-slate-900 mt-1">{{ $sellerTenureMonths }} {{ Str::plural('Month', $sellerTenureMonths) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Threshold: &ge; {{ $minMonths }} months</div>
            </div>

            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100">
                <span class="text-[11px] font-semibold text-slate-500">Delivered Orders</span>
                <div class="text-lg font-black text-slate-900 mt-1">{{ number_format($deliveredOrdersCount) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Threshold: &ge; {{ $minOrders }} orders</div>
            </div>

            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100">
                <span class="text-[11px] font-semibold text-slate-500">Delivered Sales Volume</span>
                <div class="text-lg font-black text-slate-900 mt-1">₹{{ number_format($deliveredRevenue, 2) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Verified Gross GMV</div>
            </div>

            <div class="p-3 bg-slate-50/70 rounded-xl border border-slate-100">
                <span class="text-[11px] font-semibold text-slate-500">Return &amp; RTO Rate</span>
                <div class="text-lg font-black {{ $returnRate > 15 ? 'text-rose-600' : 'text-slate-900' }} mt-1">{{ number_format($returnRate, 2) }}%</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Platform Avg: &le; 15%</div>
            </div>
        </div>
    </div>

    <!-- Active Partner Mode vs. Unavailable / Coming Soon Mode -->
    @if($status === 'active_partner')
        <!-- Approved Partner Credit Facility Summary (Zero Fake Numbers) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-1">
                <span class="text-xs font-semibold text-slate-500">Partner Approved Credit Limit</span>
                <div class="text-2xl font-black text-slate-900">
                    @if($approvedLimit > 0)
                        ₹{{ number_format($approvedLimit, 2) }}
                    @else
                        ₹0.00
                    @endif
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $application && $application->status === 'approved' ? 'Approved by ' . ($application->provider_name ?? 'Financial Partner') : 'No active credit facility limit' }}
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-1">
                <span class="text-xs font-semibold text-slate-500">Outstanding Balance</span>
                <div class="text-2xl font-black text-slate-900">
                    ₹{{ number_format($outstandingBalance, 2) }}
                </div>
                <div class="text-[11px] text-slate-400">
                    {{ $outstandingBalance > 0 ? 'Repayable per partner agreement' : 'No current outstanding dues' }}
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-1">
                <span class="text-xs font-semibold text-slate-500">Application / Facility Status</span>
                <div class="text-lg font-black text-slate-900 mt-1">
                    @if($application)
                        <span class="capitalize">{{ str_replace('_', ' ', $application->status) }}</span>
                    @else
                        <span>Not Applied</span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-400">
                    Partner: {{ $providerName ?: 'Licensed Partner' }}
                </div>
            </div>
        </div>

        @if(!$application || in_array($application->status, ['rejected', 'closed']))
            <!-- Application Form -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-2xs space-y-5">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Apply for Partner Credit Facility Referral</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Submit your business details for institutional underwriting by <span class="font-bold text-slate-800">{{ $providerName }}</span>.
                    </p>
                </div>

                <form action="{{ route('seller.finance.apply') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Requested Working Capital Limit (₹) *</label>
                            <input type="number" name="requested_amount" min="5000" max="5000000" step="1000" required placeholder="e.g. 100000"
                                   value="{{ old('requested_amount') }}"
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @error('requested_amount') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Business PAN Card Number *</label>
                            <input type="text" name="pan_number" maxlength="10" required placeholder="e.g. ABCDE1234F"
                                   value="{{ old('pan_number') }}"
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2.5 uppercase font-mono focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @error('pan_number') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">GSTIN (Optional / Recommended)</label>
                            <input type="text" name="gstin" maxlength="15" placeholder="e.g. 29ABCDE1234F1Z5"
                                   value="{{ old('gstin') }}"
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2.5 uppercase font-mono focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @error('gstin') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Estimated Annual Business Turnover (₹) *</label>
                            <input type="number" name="annual_turnover" min="10000" required placeholder="e.g. 1200000"
                                   value="{{ old('annual_turnover') }}"
                                   class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @error('annual_turnover') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Notes / Business Requirement (Optional)</label>
                        <textarea name="seller_notes" rows="2" placeholder="Describe inventory procurement requirements or seasonal peak plans..."
                                  class="w-full text-xs rounded-lg border border-slate-200 p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">{{ old('seller_notes') }}</textarea>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="flex items-start space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="terms_accepted" value="1" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-[11px] text-slate-600 leading-relaxed">
                                I confirm that all submitted business and tax details are true and accurate. I understand and consent that ALLZONE facilitates referral to <strong class="text-slate-900">{{ $providerName }}</strong>, who conducts independent credit underwriting. ALLZONE is not a lender, guarantor, or credit provider.
                            </span>
                        </label>
                        @error('terms_accepted') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition shadow-2xs">
                            Submit Financing Application &rarr;
                        </button>
                    </div>
                </form>
            </div>
        @else
            <!-- Application Status Tracker -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Application #{{ $application->id }} Status</h3>
                        <p class="text-xs text-slate-500">Submitted on {{ $application->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div>
                        @php
                            $badgeClass = match($application->status) {
                                'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'under_review' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }} uppercase tracking-wider">
                            {{ str_replace('_', ' ', $application->status) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400">Partner</span>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $application->provider_name ?? $providerName }}</div>
                    </div>
                    <div>
                        <span class="text-slate-400">Requested Amount</span>
                        <div class="font-bold text-slate-900 mt-0.5">₹{{ number_format($application->requested_amount, 2) }}</div>
                    </div>
                    <div>
                        <span class="text-slate-400">Approved Limit</span>
                        <div class="font-bold text-emerald-700 mt-0.5">₹{{ number_format($application->approved_limit, 2) }}</div>
                    </div>
                    <div>
                        <span class="text-slate-400">Partner Ref #</span>
                        <div class="font-mono font-bold text-slate-700 mt-0.5">{{ $application->provider_reference_id ?? 'Pending Ref' }}</div>
                    </div>
                </div>

                @if($application->admin_notes)
                    <div class="p-3 bg-slate-50 rounded-lg text-xs text-slate-700 border border-slate-200">
                        <span class="font-semibold text-slate-900">Partner Updates:</span> {{ $application->admin_notes }}
                    </div>
                @endif
            </div>

            <!-- Ledger Transactions Table -->
            @if($transactions->isNotEmpty())
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
                    <h3 class="text-sm font-extrabold text-slate-900">Repayment &amp; Facility Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold">
                                    <th class="py-2.5 px-3">Date</th>
                                    <th class="py-2.5 px-3">Type</th>
                                    <th class="py-2.5 px-3">Reference ID</th>
                                    <th class="py-2.5 px-3">Amount</th>
                                    <th class="py-2.5 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($transactions as $tx)
                                    <tr>
                                        <td class="py-2.5 px-3 text-slate-600">{{ $tx->transacted_at?->format('d M Y') }}</td>
                                        <td class="py-2.5 px-3 font-semibold capitalize">{{ $tx->transaction_type }}</td>
                                        <td class="py-2.5 px-3 font-mono text-slate-700">{{ $tx->reference_id }}</td>
                                        <td class="py-2.5 px-3 font-mono font-bold {{ $tx->transaction_type === 'repayment' ? 'text-emerald-700' : 'text-slate-900' }}">
                                            ₹{{ number_format($tx->amount, 2) }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                {{ strtoupper($tx->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

    @else
        <!-- Coming Soon / Not Available State (Controlled by Admin) -->
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center max-w-2xl mx-auto shadow-2xs space-y-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>

            <div>
                <h3 class="text-lg font-black text-slate-900">
                    @if($status === 'coming_soon')
                        Seller Finance Program – Coming Soon
                    @else
                        Seller Finance Features Not Currently Available
                    @endif
                </h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto leading-relaxed">
                    ALLZONE is actively engaging with licensed banking and NBFC institutions to bring transparent working capital referral programs to our merchant community. We do not operate proprietary lending or unverified credit schemes.
                </p>
            </div>

            @if($application && $application->application_type === 'waitlist')
                <!-- Already Registered -->
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs inline-block text-left space-y-1">
                    <div class="flex items-center space-x-2 font-bold">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>You are on the Priority Waitlist</span>
                    </div>
                    <p class="text-[11px] text-emerald-700">
                        Interest registered on {{ $application->created_at->format('d M Y') }}. You will receive direct updates as soon as regulatory-approved credit facilitation launches.
                    </p>
                </div>
            @else
                <!-- Waitlist Form -->
                <form action="{{ route('seller.finance.apply') }}" method="POST" class="pt-2 max-w-md mx-auto text-left space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Optional Note / Financing Interest</label>
                        <textarea name="seller_notes" rows="2" placeholder="e.g. Interested in inventory expansion capital during festive peaks..."
                                  class="w-full text-xs rounded-lg border border-slate-200 p-2.5 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 transition shadow-2xs">
                        Register Interest for Launch Notification &rarr;
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
