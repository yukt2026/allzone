@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Merchant Profile & Identity'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Page Header -->
    <x-page-header 
        title="Merchant Profile & Compliance" 
        subtitle="Manage your registered legal entity details, contact information, banking channels, and KYC verification records."
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Merchant Profile' => '#']"
    >
        <x-slot name="badge">
            @php
                $statusVal = is_object($seller->status) ? $seller->status->value : (string) $seller->status;
                $statusBadge = match($statusVal) {
                    'approved' => ['variant' => 'success', 'label' => 'Verified & Approved Merchant'],
                    'kyc_submitted' => ['variant' => 'info', 'label' => 'KYC In Review'],
                    'rejected' => ['variant' => 'danger', 'label' => 'KYC Needs Correction'],
                    'suspended' => ['variant' => 'danger', 'label' => 'Merchant Suspended'],
                    default => ['variant' => 'warning', 'label' => 'Pending Verification'],
                };
            @endphp
            <x-badge :variant="$statusBadge['variant']" dot size="sm">
                {{ $statusBadge['label'] }}
            </x-badge>
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Overview Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
            <div class="text-center space-y-3 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-gradient-to-tr from-emerald-600 to-indigo-600 flex items-center justify-center text-white text-2xl font-black shadow-lg">
                    {{ strtoupper(substr($seller->business_name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900 dark:text-white">{{ $seller->business_name }}</h2>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">Seller ID: #{{ str_pad((string)$seller->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="inline-flex px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    {{ ucfirst(str_replace('_', ' ', is_object($seller->seller_type) ? $seller->seller_type->value : (string) $seller->seller_type)) }}
                </div>
            </div>

            <!-- Tax & Legal Identifiers -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Statutory Compliance</h3>
                
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 space-y-1">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase">GSTIN (Goods &amp; Services Tax)</div>
                    <div class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                        {{ $seller->gstin ? substr($seller->gstin, 0, 4) . '••••••' . substr($seller->gstin, -4) : 'Not Provided' }}
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 space-y-1">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase">PAN (Permanent Account Number)</div>
                    <div class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                        {{ $seller->pan ? substr($seller->pan, 0, 2) . '•••••' . substr($seller->pan, -2) : 'Not Provided' }}
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50 space-y-1">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase">Commission Structure</div>
                    <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $seller->effectiveCommissionRate() }}% Platform Fee
                    </div>
                </div>
            </div>

            <!-- Primary Bank Account Preview -->
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Disbursement Bank Account</h3>
                @if($primaryBank)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $primaryBank->bank_name ?: 'Bank Account' }}</span>
                            @if($primaryBank->verified_at)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">Verified</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">Pending</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 font-mono">
                            A/C: ••••••{{ substr((string)$primaryBank->account_no, -4) }}
                        </div>
                        <div class="text-[11px] text-slate-400 font-mono">
                            IFSC: {{ $primaryBank->ifsc }} &bull; {{ $primaryBank->branch_name }}
                        </div>
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No bank account linked yet.</p>
                @endif
            </div>
        </div>

        <!-- Edit Form -->
        <div class="md:col-span-2 space-y-6">
            <form method="POST" action="{{ route('seller.profile.update') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">General Merchant Identity</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Details reflected across buyer invoices, tax receipts, and platform records.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Display Business Name *</label>
                        <input 
                            type="text" 
                            name="business_name" 
                            value="{{ old('business_name', $seller->business_name) }}" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Registered Legal Entity Name *</label>
                        <input 
                            type="text" 
                            name="legal_name" 
                            value="{{ old('legal_name', $seller->legal_name) }}" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Registered Phone / Contact Number *</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            value="{{ old('phone', $seller->user?->phone) }}" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Merchant Login Email *</label>
                        <input 
                            type="email" 
                            name="email" 
                            value="{{ old('email', $seller->user?->email) }}" 
                            required 
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category Specialization / Focus</label>
                        <input 
                            type="text" 
                            name="category_focus" 
                            value="{{ old('category_focus', $seller->category_focus) }}" 
                            placeholder="e.g. Commercial Refrigeration, Air Conditioning Units, Spare Parts"
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                        >
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button 
                        type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
                    >
                        Save Merchant Profile
                    </button>
                </div>
            </form>

            <!-- KYC Document Audit Cards -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Submitted KYC Verification Documents</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Government ID, GST certificate, and business proof files reviewed by ALLZONE compliance.</p>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($seller->documents as $doc)
                        <div class="py-3 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    📄
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $doc->type->value ?? (string)$doc->type)) }}</div>
                                    <div class="text-[11px] text-slate-400">Submitted: {{ $doc->created_at->format('d M Y') }}</div>
                                </div>
                            </div>
                            <div>
                                @php
                                    $docStatus = $doc->status->value ?? (string)$doc->status;
                                @endphp
                                @if($docStatus === 'approved')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-400">Verified</span>
                                @elseif($docStatus === 'rejected')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-400">Rejected</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400">Under Review</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 italic">No documents uploaded during registration.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
