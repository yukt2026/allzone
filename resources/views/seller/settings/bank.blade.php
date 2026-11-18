@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Bank Details - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    editModal: false
}">
    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Settings
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Manage your merchant bank accounts, tax details, passwords and supplier preferences.
        </p>
    </div>

    <!-- 2-Column Settings Layout (Screenshot 8) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
        <!-- Settings Sidebar Navigation -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden divide-y divide-slate-100 text-xs font-semibold">
            <a href="{{ route('seller.settings.password') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                Change Password
            </a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                WhatsApp Notifications
            </a>
            <a href="{{ route('seller.settings.bank') }}" class="flex items-center px-4 py-3 bg-[#f5f3ff] text-[#53389e] font-black border-l-4 border-[#53389e] transition">
                Bank Details
            </a>
            <a href="{{ route('seller.settings.tax') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                Tax Details
            </a>
            <a href="{{ route('seller.settings.signature') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                Supplier Signature
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                Legal and Policies
            </a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">
                Email Notifications
            </a>
        </div>

        <!-- Main Content: Bank Details (Screenshot 8 Right) -->
        <div class="md:col-span-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Bank Details
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Earnings from your sales and weekly settlement payouts will be credited to this verified account.
                    </p>
                </div>

                <button
                    type="button"
                    @click="editModal = true"
                    class="px-4 py-2 rounded-lg bg-white border border-[#3e2e84] text-[#3e2e84] hover:bg-[#3e2e84]/5 font-bold text-xs shadow-2xs transition"
                >
                    Edit Bank Details
                </button>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
                <div class="p-4 rounded-lg bg-slate-50/70 border border-slate-100">
                    <div class="text-[11px] text-slate-400 font-medium">Beneficiary Name</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-1">
                        {{ $bankAccount->account_holder }}
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-slate-50/70 border border-slate-100">
                    <div class="text-[11px] text-slate-400 font-medium">Account Number</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-1 font-mono tracking-wider">
                        •••• •••• {{ substr($bankAccount->account_no, -4) }}
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-slate-50/70 border border-slate-100">
                    <div class="text-[11px] text-slate-400 font-medium">Bank Name</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-1">
                        {{ $bankAccount->bank_name }}
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-slate-50/70 border border-slate-100">
                    <div class="text-[11px] text-slate-400 font-medium">IFSC Code</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-1 font-mono">
                        {{ $bankAccount->ifsc }}
                    </div>
                </div>
            </div>

            <!-- Verification Status -->
            <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200/80 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-black text-sm">
                        ✓
                    </div>
                    <div>
                        <div class="font-extrabold text-emerald-900">
                            Bank Account Verified
                        </div>
                        <div class="text-[11px] text-emerald-700 mt-0.5">
                            Account verified via Penny Drop verification test. Payouts are active.
                        </div>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                    ACTIVE
                </span>
            </div>
        </div>
    </div>

    <!-- Edit Bank Details Modal -->
    <div
        x-show="editModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            @click.away="editModal = false"
            class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-slate-200"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Edit Bank Details</h3>
                <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form action="{{ route('seller.settings.bank.update') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Beneficiary Name (as per Bank)</label>
                    <input
                        type="text"
                        name="account_holder"
                        value="{{ old('account_holder', $bankAccount->account_holder) }}"
                        required
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-[#53389e]"
                    >
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Account Number</label>
                    <input
                        type="password"
                        name="account_no"
                        required
                        placeholder="Enter bank account number"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-[#53389e]"
                    >
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Confirm Account Number</label>
                    <input
                        type="text"
                        name="account_no_confirmation"
                        required
                        placeholder="Re-enter bank account number"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-[#53389e]"
                    >
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">IFSC Code</label>
                        <input
                            type="text"
                            name="ifsc"
                            value="{{ old('ifsc', $bankAccount->ifsc) }}"
                            required
                            placeholder="e.g. HDFC0001234"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs uppercase font-medium focus:outline-hidden focus:border-[#53389e]"
                        >
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input
                            type="text"
                            name="bank_name"
                            value="{{ old('bank_name', $bankAccount->bank_name) }}"
                            required
                            placeholder="e.g. HDFC Bank Ltd"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-[#53389e]"
                        >
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button
                        type="button"
                        @click="editModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-200 font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white font-bold shadow-xs transition"
                    >
                        Save &amp; Verify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
