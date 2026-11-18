@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Notifications Settings - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Settings</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your merchant notification preferences.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden divide-y divide-slate-100 text-xs font-semibold">
            <a href="{{ route('seller.settings.password') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Change Password</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 bg-[#f5f3ff] text-[#53389e] font-black border-l-4 border-[#53389e] transition">WhatsApp Notifications</a>
            <a href="{{ route('seller.settings.bank') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Bank Details</a>
            <a href="{{ route('seller.settings.tax') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Tax Details</a>
            <a href="{{ route('seller.settings.signature') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Supplier Signature</a>
            <a href="#" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Legal and Policies</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Email Notifications</a>
        </div>

        <div class="md:col-span-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Notifications</h2>
                <p class="text-xs text-slate-500 mt-0.5">Configure your WhatsApp, SMS, and Email alert preferences.</p>
            </div>

            <div class="space-y-4 text-xs">
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-900">New Order Alerts on WhatsApp</div>
                        <div class="text-[11px] text-slate-500">Receive instant dispatch pings and customer address updates.</div>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">ENABLED</span>
                </div>

                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-900">Return &amp; RTO Delivery Alerts</div>
                        <div class="text-[11px] text-slate-500">Get notified when a courier brings back an undelivered parcel.</div>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">ENABLED</span>
                </div>

                <div class="p-4 bg-slate-50 rounded-lg border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-slate-900">Weekly Payout Reports</div>
                        <div class="text-[11px] text-slate-500">Receive weekly PDF statements for bank settlements.</div>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">ENABLED</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
