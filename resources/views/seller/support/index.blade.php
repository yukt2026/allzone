@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Support Desk - Meesho Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" x-data="{
    tab: 'tickets'
}">
    <!-- Header with YouTube Link (Screenshot 2) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Support
        </h1>
        <a href="#" class="flex items-center space-x-1.5 text-xs text-slate-700 font-semibold hover:text-slate-900">
            <span class="text-rose-600">▶</span>
            <span>Learn how to solve issues and grow on Meesho</span>
        </a>
    </div>

    <!-- Sub-tabs: Help / My Tickets (Screenshot 2) -->
    <div class="border-b border-slate-200 flex items-center space-x-8 text-xs font-bold">
        <button class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800">
            Help
        </button>
        <button class="pb-3 border-b-2 border-[#53389e] text-[#53389e] font-black flex items-center space-x-1">
            <span>My Tickets</span>
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
        </button>
    </div>

    <!-- Ticket Detail Card (Screenshot 2) -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-6 shadow-2xs">
        <div class="text-[11px] text-slate-400 font-medium mb-1">
            My Tickets &gt; <span class="text-slate-700 font-semibold">Ticket 794813081646</span>
        </div>

        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 pb-6 border-b border-slate-100">
            <div class="space-y-4 max-w-xl">
                <div>
                    <h2 class="text-base font-bold text-slate-900">I have not received my Return/RTO shipment</h2>
                    <p class="text-[11px] text-slate-400">Created on 16 Nov'26 | 12:41 PM</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Status</div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 inline-block mt-0.5">
                            Issue Raised
                        </span>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Expected Response Time</div>
                        <div class="font-bold text-slate-800 text-xs">19 Nov'26 | 12:41 PM</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Sub Order Number</div>
                        <div class="font-mono text-slate-700 font-semibold">341768883786253568_1</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">AWB Number</div>
                        <div class="font-mono text-indigo-700 font-bold">VL0085758358510</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Callback Number</div>
                        <div class="font-medium text-slate-800">7508039267</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Logistics Partner Name</div>
                        <div class="font-bold text-slate-800">Valmo</div>
                    </div>

                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider">Description</div>
                        <div class="font-medium text-slate-800">my rto not recived</div>
                    </div>
                </div>
            </div>

            <!-- Reply Button & Timeline updates -->
            <div class="w-full md:w-80 space-y-4">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Updated on 16 Nov'26</span>
                    <button type="button" class="px-4 py-1.5 rounded-lg border border-[#3e2e84] text-[#3e2e84] font-bold text-xs hover:bg-purple-50 transition">
                        Reply
                    </button>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-3">
                    <h4 class="font-bold text-slate-900">Updates</h4>
                    <div class="flex items-start space-x-3">
                        <div class="text-center shrink-0">
                            <div class="text-[11px] font-bold text-slate-700">16</div>
                            <div class="text-[9px] text-slate-400 uppercase">Nov</div>
                            <span class="px-1 py-0.2 rounded bg-[#3e2e84] text-white text-[8px] font-bold uppercase block mt-0.5">NEW</span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900">Meesho support agent will share an update within 24 hours</div>
                            <div class="text-[10px] text-slate-400">12:41 PM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
