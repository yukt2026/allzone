@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Change Password - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Settings</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your merchant credentials and account security.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden divide-y divide-slate-100 text-xs font-semibold">
            <a href="{{ route('seller.settings.password') }}" class="flex items-center px-4 py-3 bg-[#f5f3ff] text-[#53389e] font-black border-l-4 border-[#53389e] transition">Change Password</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">WhatsApp Notifications</a>
            <a href="{{ route('seller.settings.bank') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Bank Details</a>
            <a href="{{ route('seller.settings.tax') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Tax Details</a>
            <a href="{{ route('seller.settings.signature') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Supplier Signature</a>
            <a href="#" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Legal and Policies</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Email Notifications</a>
        </div>

        <div class="md:col-span-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Change Password</h2>
                <p class="text-xs text-slate-500 mt-0.5">Ensure your account is protected with a strong, secure passphrase.</p>
            </div>

            <form action="{{ route('seller.settings.password.update') }}" method="POST" class="space-y-4 max-w-lg text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">New Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs">
                </div>

                <button type="submit" class="px-4 py-2 rounded-lg bg-[#3e2e84] text-white font-bold text-xs hover:bg-[#32236e] transition">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
