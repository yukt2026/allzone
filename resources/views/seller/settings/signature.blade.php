@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Supplier Signature - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Settings</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your merchant credentials and digital signature.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden divide-y divide-slate-100 text-xs font-semibold">
            <a href="{{ route('seller.settings.password') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Change Password</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">WhatsApp Notifications</a>
            <a href="{{ route('seller.settings.bank') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Bank Details</a>
            <a href="{{ route('seller.settings.tax') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Tax Details</a>
            <a href="{{ route('seller.settings.signature') }}" class="flex items-center px-4 py-3 bg-[#f5f3ff] text-[#53389e] font-black border-l-4 border-[#53389e] transition">Supplier Signature</a>
            <a href="#" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Legal and Policies</a>
            <a href="{{ route('seller.settings.notifications') }}" class="flex items-center px-4 py-3 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition">Email Notifications</a>
        </div>

        <div class="md:col-span-3 bg-white rounded-xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Supplier Signature</h2>
                <p class="text-xs text-slate-500 mt-0.5">Your digital signature used on automated GST tax invoices.</p>
            </div>

            <div class="p-6 border-2 border-dashed border-slate-200 rounded-xl text-center space-y-3">
                <div class="text-3xl">✍️</div>
                <div class="text-xs font-bold text-slate-800">Digital Signature Active</div>
                <div class="font-serif italic text-lg text-slate-700">{{ $seller->legal_name ?? $seller->business_name }}</div>
                <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">VERIFIED ON INVOICES</span>
            </div>
        </div>
    </div>
</div>
@endsection
