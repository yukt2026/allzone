@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Seller Pickup Locations - Admin Control'])

@section('content')
<div 
    x-data="{
        rejectModalOpen: false,
        rejectActionUrl: '',
        rejectLocationName: '',
        openRejectModal(url, name) {
            this.rejectActionUrl = url;
            this.rejectLocationName = name;
            this.rejectModalOpen = true;
        }
    }" 
    class="space-y-6 max-w-7xl mx-auto pb-16"
>
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Seller Pickup Addresses &amp; Shipping Config
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Audit and verify merchant warehouse pickup locations, Shiprocket pickup aliases, and carrier routing serviceability.
            </p>
        </div>

        <a 
            href="{{ route('admin.shipping.index') }}" 
            class="px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center space-x-1.5 shadow-2xs transition"
        >
            <span>← Return to Shipping Rules</span>
        </a>
    </div>

    <!-- Alert / Feedback Messages -->
    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Locations</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['total_locations'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Across all merchants</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Verified &amp; Approved</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">{{ $metrics['verified'] }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-0.5">Ready for auto dispatch</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pending Audit</span>
            <div class="text-2xl font-black text-amber-600 font-mono mt-1">{{ $metrics['pending'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Requires physical check</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rejected</span>
            <div class="text-2xl font-black text-rose-600 font-mono mt-1">{{ $metrics['rejected'] }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Address invalid / unserviceable</div>
        </div>
    </div>

    <!-- Filter & Table Console -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <!-- Filter Controls -->
        <div class="p-4 bg-slate-50/60 border-b border-slate-200">
            <form method="GET" action="{{ route('admin.shipping.pickup_locations.index') }}" class="flex flex-wrap items-center justify-between gap-3 text-xs">
                <!-- Search -->
                <div class="relative w-full sm:w-72">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Search seller, nickname, city, PIN, Shiprocket code..." 
                        class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center space-x-2">
                    <select name="status" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white text-slate-700 font-medium">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="verified" {{ $status === 'verified' ? 'selected' : '' }}>Verified Only</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Audit</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <select name="seller_id" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white text-slate-700 font-medium max-w-xs truncate">
                        <option value="">All Sellers</option>
                        @foreach($sellers as $s)
                            <option value="{{ $s->id }}" {{ (string)$sellerId === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->business_name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs">
                        Filter
                    </button>

                    @if($search || $status !== 'all' || $sellerId)
                        <a href="{{ route('admin.shipping.pickup_locations.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-100 font-bold text-xs">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Locations Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold text-[10px] uppercase border-b border-slate-200">
                    <tr>
                        <th class="p-3">Seller / Merchant</th>
                        <th class="p-3">Location Details</th>
                        <th class="p-3">Physical Address</th>
                        <th class="p-3">Contact Person</th>
                        <th class="p-3">Serviceability</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($locations as $loc)
                        <tr class="hover:bg-slate-50 transition">
                            <!-- Seller -->
                            <td class="p-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $loc->seller?->business_name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">ID: #{{ $loc->seller_id }}</div>
                            </td>

                            <!-- Location Details -->
                            <td class="p-3 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-bold text-slate-900">{{ $loc->nickname }}</span>
                                    @if($loc->is_default)
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 text-[9px] font-black">PRIMARY</span>
                                    @endif
                                </div>
                                <div class="text-[10px] font-mono text-slate-400">
                                    Tag: {{ $loc->shiprocket_pickup_name ?: 'None' }}
                                </div>
                            </td>

                            <!-- Physical Address -->
                            <td class="p-3 max-w-xs">
                                <div class="text-slate-800 text-[11px] truncate" title="{{ $loc->full_address }}">
                                    {{ $loc->full_address }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    PIN: {{ $loc->pincode }} &bull; {{ $loc->city }}, {{ $loc->state }}
                                </div>
                            </td>

                            <!-- Contact -->
                            <td class="p-3 whitespace-nowrap text-[11px]">
                                <div class="font-bold text-slate-800">{{ $loc->contact_person }}</div>
                                <div class="font-mono text-slate-500 text-[10px]">{{ $loc->contact_phone }}</div>
                            </td>

                            <!-- Serviceability -->
                            <td class="p-3 whitespace-nowrap">
                                @if($loc->is_serviceable)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ Serviceable
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Not Automated
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="p-3 whitespace-nowrap">
                                @if($loc->is_verified)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Verified
                                    </span>
                                    <div class="text-[9px] text-slate-400 mt-0.5">
                                        {{ $loc->verified_at?->format('d M Y') }}
                                    </div>
                                @elseif($loc->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Pending Audit
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-3 whitespace-nowrap text-right space-x-1">
                                @if(!$loc->is_verified)
                                    <form method="POST" action="{{ route('admin.shipping.pickup_locations.verify', $loc) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px]">
                                            Approve
                                        </button>
                                    </form>
                                @endif

                                @if($loc->status !== 'rejected')
                                    <button 
                                        type="button" 
                                        @click="openRejectModal('{{ route('admin.shipping.pickup_locations.reject', $loc) }}', '{{ addslashes($loc->nickname) }}')" 
                                        class="px-2.5 py-1 rounded border border-rose-300 text-rose-700 hover:bg-rose-50 font-bold text-[10px]"
                                    >
                                        Reject
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center text-slate-400 text-xs">
                                No seller pickup locations found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $locations->links() }}
            </div>
        @endif
    </div>

    <!-- Reject Modal -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.away="rejectModalOpen = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden text-xs">
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-100">Reject Pickup Address</h3>
                <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-white font-bold">&times;</button>
            </div>
            <form :action="rejectActionUrl" method="POST" class="p-5 space-y-3">
                @csrf
                <div>
                    <div class="font-bold text-slate-800 mb-1" x-text="`Location: ${rejectLocationName}`"></div>
                    <label class="block text-slate-600 mb-1">State mandatory reason for rejecting this pickup location:</label>
                    <textarea name="rejection_reason" rows="3" required placeholder="e.g. Incomplete building name / non-serviceable pin code / contact unreachable" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" @click="rejectModalOpen = false" class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-slate-600 font-bold">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
