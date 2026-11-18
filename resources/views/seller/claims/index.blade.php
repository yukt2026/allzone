@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Claim Tracking - Meesho Supplier Hub'])

@section('content')
<div class="space-y-5 max-w-7xl mx-auto pb-12">
    <!-- Header (Screenshot 6) -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Return/RTO Orders
        </h1>
    </div>

    <!-- Navigation Tabs: Overview, Return Tracking, Claim Tracking -->
    <div class="border-b border-slate-200 flex items-center space-x-8 text-xs font-bold">
        <a href="{{ route('seller.returns.index', ['view' => 'overview']) }}" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800">
            Overview
        </a>
        <a href="{{ route('seller.returns.index', ['view' => 'tracking']) }}" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800">
            Return Tracking
        </a>
        <a href="{{ route('seller.claims.index') }}" class="pb-3 border-b-2 border-[#53389e] text-[#53389e] font-black">
            Claim Tracking
        </a>
    </div>

    <!-- Claims Content Container (Screenshot 6) -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="flex items-center space-x-6 border-b border-slate-200 px-5 pt-3 text-xs font-bold">
            <a href="{{ route('seller.claims.index', ['tab' => 'all']) }}" class="pb-3 border-b-2 {{ request('tab', 'all') === 'all' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500' }}">
                All (22)
            </a>
            <a href="{{ route('seller.claims.index', ['tab' => 'open']) }}" class="pb-3 border-b-2 {{ request('tab') === 'open' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500' }}">
                Open (1)
            </a>
            <a href="{{ route('seller.claims.index', ['tab' => 'approved']) }}" class="pb-3 border-b-2 {{ request('tab') === 'approved' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500' }}">
                Approved (11)
            </a>
            <a href="{{ route('seller.claims.index', ['tab' => 'rejected']) }}" class="pb-3 border-b-2 {{ request('tab') === 'rejected' ? 'border-[#53389e] text-[#53389e] font-black' : 'border-transparent text-slate-500' }}">
                Rejected (10)
            </a>
        </div>

        <!-- Center Prompt Box (Screenshot 6) -->
        <div class="py-20 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-4">Looking for Claim Tracking?</h3>
            <a href="{{ route('seller.support.index') }}" class="px-5 py-2.5 rounded-lg bg-[#3e2e84] hover:bg-[#32236e] text-white text-xs font-bold shadow-xs transition">
                Go to Claims Tab
            </a>
        </div>
    </div>
</div>
@endsection
