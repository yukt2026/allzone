@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Influencer Marketing - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="influencerHub()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('seller.dashboard') }}" class="hover:text-slate-700">Dashboard</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Influencer Marketing</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Influencer Marketing &amp; Creator Collaborations</h1>
            <p class="text-xs text-slate-500 mt-1">
                Connect with verified creators to produce authentic unboxings, reviews, and social demonstrations for your products.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('seller.ads.index') }}" class="px-4 py-2 rounded-lg text-xs font-bold border border-slate-300 text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                &larr; Sponsored Ads Hub
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-2xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Performance Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Approved Creators</span>
            <div class="text-2xl font-black text-slate-900 font-mono mt-1">{{ $metrics['approved_influencers'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Platform-vetted influencers</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Collaborations</span>
            <div class="text-2xl font-black text-indigo-700 font-mono mt-1">{{ $metrics['active_collaborations'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ $metrics['pending_proposals'] }} proposals pending</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attributed Revenue</span>
            <div class="text-2xl font-black text-emerald-700 font-mono mt-1">₹{{ number_format($metrics['total_revenue'], 2) }}</div>
            <div class="text-[11px] text-emerald-600 font-medium mt-1">From creator referral traffic</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs p-4 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Collaboration ROI</span>
            <div class="text-2xl font-black font-mono mt-1 {{ $metrics['overall_roi'] ? 'text-indigo-700' : 'text-slate-400' }}">
                {{ $metrics['overall_roi'] ? "{$metrics['overall_roi']}x" : 'N/A' }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                {{ $metrics['overall_roi'] ? 'Spend to revenue ratio' : 'Requires spend & revenue' }}
            </div>
        </div>
    </div>

    <!-- Transparent Policy Banner -->
    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-teal-900 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 uppercase">
                    ALLZONE VERIFIED CREATOR NETWORK
                </span>
                <span class="text-xs text-emerald-200 font-medium">Audited Deliverables</span>
            </div>
            <h3 class="text-base sm:text-lg font-black text-white">
                Authentic Video Deliverables &amp; Verified Attribution
            </h3>
            <p class="text-xs text-emerald-100/90 leading-relaxed max-w-2xl">
                ALLZONE audits creator channels to ensure genuine subscriber engagement. When a creator accepts your collaboration, live post links and performance metrics are verified directly in your dashboard.
            </p>
        </div>
        <div class="shrink-0 flex items-center space-x-3 bg-white/10 rounded-xl p-3 border border-white/10">
            <div class="text-right">
                <div class="text-[10px] uppercase font-bold text-emerald-200">Completed Campaigns</div>
                <div class="text-sm font-black text-white font-mono">{{ $metrics['completed_collaborations'] }} Finished</div>
            </div>
            <span class="w-8 h-8 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-300 font-bold">✓</span>
        </div>
    </div>

    <!-- Main Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center space-x-2 text-xs font-bold">
                <a 
                    href="{{ route('seller.influencer.index', ['tab' => 'explore', 'platform' => $platform]) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'explore' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    Explore Influencer Profiles ({{ $influencers->count() }})
                </a>
                <a 
                    href="{{ route('seller.influencer.index', ['tab' => 'my_collaborations']) }}"
                    class="px-3.5 py-1.5 rounded-lg transition {{ $tab === 'my_collaborations' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                >
                    My Collaborations ({{ $collaborations->count() }})
                </a>
            </div>

            @if($tab === 'explore')
                <!-- Platform Filters -->
                <div class="flex items-center space-x-1.5 text-xs">
                    <span class="text-slate-400 font-bold text-[10px] uppercase mr-1">Platform:</span>
                    @foreach(['all' => 'All', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $pKey => $pLabel)
                        <a 
                            href="{{ route('seller.influencer.index', ['tab' => 'explore', 'platform' => $pKey]) }}"
                            class="px-2.5 py-1 rounded text-xs font-semibold {{ $platform === $pKey ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                        >
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($tab === 'explore')
            <!-- Influencer Discovery Cards Grid -->
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @forelse($influencers as $inf)
                        <div class="bg-white rounded-2xl border border-slate-200 hover:border-slate-300 transition shadow-2xs hover:shadow-sm p-5 flex flex-col justify-between space-y-4">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-lg shrink-0 overflow-hidden">
                                            @if($inf->profile_photo)
                                                <img src="{{ $inf->profile_photo }}" alt="" class="w-full h-full object-cover">
                                            @else
                                                {{ strtoupper(substr($inf->name, 0, 1)) }}
                                            @endif
                                        </div>
                                        <div>
                                            <h3 class="font-extrabold text-sm text-slate-900">{{ $inf->name }}</h3>
                                            <div class="text-[11px] font-mono text-slate-500">{{ $inf->handle }}</div>
                                        </div>
                                    </div>

                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        {{ ucfirst($inf->platform) }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                    {{ $inf->bio ?: 'Verified tech and appliance content creator on ALLZONE.' }}
                                </p>

                                <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Followers</span>
                                        <div class="font-bold text-slate-900 font-mono">{{ number_format($inf->verified_followers) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Base Fee</span>
                                        <div class="font-bold text-emerald-700 font-mono">
                                            {{ $inf->collaboration_fee ? '₹' . number_format((float)$inf->collaboration_fee, 2) : 'Negotiable' }}
                                        </div>
                                    </div>
                                </div>

                                @if($inf->category)
                                    <div class="text-[11px] text-slate-500">
                                        Niche: <span class="font-bold text-slate-700">{{ $inf->category->name }}</span>
                                    </div>
                                @endif
                            </div>

                            <button 
                                type="button" 
                                @click="openProposalModal({{ json_encode($inf) }})"
                                class="w-full py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-2xs"
                            >
                                Send Collaboration Proposal &rarr;
                            </button>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-slate-400">
                            <div class="max-w-sm mx-auto space-y-2">
                                <div class="text-2xl">📱</div>
                                <div class="font-bold text-slate-700 text-sm">No approved influencers found</div>
                                <p class="text-xs text-slate-500">
                                    New verified creator profiles are audited and added regularly by ALLZONE.
                                </p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <!-- My Collaborations Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                        <tr>
                            <th class="p-3">Creator</th>
                            <th class="p-3">Campaign / Product</th>
                            <th class="p-3">Proposed Budget</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Deliverable</th>
                            <th class="p-3">Views &bull; Clicks</th>
                            <th class="p-3">Orders &bull; Revenue</th>
                            <th class="p-3">ROI</th>
                            <th class="p-3 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($collaborations as $collab)
                            <tr class="hover:bg-slate-50/60">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ $collab->influencer?->name ?? 'N/A' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $collab->influencer?->handle }}</div>
                                </td>

                                <td class="p-3">
                                    <div class="font-bold text-slate-800">{{ $collab->campaign_title }}</div>
                                    @if($collab->product)
                                        <div class="text-[10px] text-slate-500 truncate max-w-xs">{{ $collab->product->title }}</div>
                                    @endif
                                </td>

                                <td class="p-3 font-mono font-bold text-slate-800">
                                    ₹{{ number_format((float)$collab->proposed_budget, 2) }}
                                </td>

                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold 
                                        {{ $collab->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $collab->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                        {{ $collab->status === 'accepted' ? 'bg-sky-100 text-sky-800' : '' }}
                                        {{ $collab->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $collab->status === 'declined' ? 'bg-rose-100 text-rose-800' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $collab->status)) }}
                                    </span>
                                </td>

                                <td class="p-3">
                                    @if($collab->deliverable_url)
                                        <a href="{{ $collab->deliverable_url }}" target="_blank" rel="noopener" class="text-emerald-700 font-bold hover:underline inline-flex items-center space-x-1">
                                            <span>View Post</span>
                                            <span class="text-[10px]">↗</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px]">Pending submission</span>
                                    @endif
                                </td>

                                <td class="p-3 font-mono">
                                    <div class="text-slate-800 font-bold">{{ number_format($collab->impressions) }} views</div>
                                    <div class="text-[10px] text-slate-400">{{ number_format($collab->clicks) }} clicks</div>
                                </td>

                                <td class="p-3 font-mono">
                                    <div class="font-bold text-emerald-700">₹{{ number_format((float)$collab->attributed_revenue, 2) }}</div>
                                    <div class="text-[10px] text-indigo-600 font-bold">{{ $collab->attributed_orders }} orders</div>
                                </td>

                                <td class="p-3 font-mono font-bold {{ $collab->roi ? 'text-indigo-700' : 'text-slate-400' }}">
                                    {{ $collab->roi ? "{$collab->roi}x" : 'N/A' }}
                                </td>

                                <td class="p-3 text-right">
                                    <a href="{{ route('seller.influencer.show', $collab) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs inline-block">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-slate-400">
                                    You have not sent any collaboration requests yet. Explore creators to propose a campaign.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Collaboration Proposal Modal -->
    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        <div @click.away="modalOpen = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden text-xs">
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-sm text-slate-100">Send Collaboration Proposal</h3>
                    <p class="text-[11px] text-slate-400" x-text="`Target Creator: ${selectedInfluencer?.name || ''}`"></p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <form action="{{ route('seller.influencer.collaborate') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="influencer_profile_id" :value="selectedInfluencer?.id">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Campaign Title *</label>
                    <input 
                        type="text" 
                        name="campaign_title" 
                        required 
                        placeholder="e.g. YouTube Product Review &amp; Unboxing" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                    >
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Product (Optional)</label>
                    <select name="product_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500">
                        <option value="">Storewide / Multiple Products</option>
                        @foreach($sellerProducts as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Proposed Budget (₹) *</label>
                    <input 
                        type="number" 
                        name="proposed_budget" 
                        required 
                        min="100" 
                        max="500000"
                        :value="selectedInfluencer?.collaboration_fee || 2500"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-mono focus:ring-1 focus:ring-emerald-500"
                    >
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Proposal Brief / Deliverable Notes</label>
                    <textarea 
                        name="proposal_notes" 
                        rows="3" 
                        placeholder="Describe expectations: video length, specific features to highlight, delivery timeline..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-1 focus:ring-emerald-500"
                    ></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end space-x-2">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold">Submit Proposal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function influencerHub() {
    return {
        modalOpen: false,
        selectedInfluencer: null,
        openProposalModal(influencer) {
            this.selectedInfluencer = influencer;
            this.modalOpen = true;
        }
    };
}
</script>
@endsection
