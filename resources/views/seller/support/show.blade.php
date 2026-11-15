@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Ticket #' . $ticket->ticket_number])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Page Header -->
    <x-page-header 
        title="Ticket #{{ $ticket->ticket_number }}" 
        subtitle="Opened {{ $ticket->created_at->format('d M Y, h:i A') }} &bull; Category: {{ ucfirst($ticket->category) }}"
        :breadcrumbs="['Seller Studio' => route('seller.dashboard'), 'Support Desk' => route('seller.support.index'), '#' . $ticket->ticket_number => '#']"
    >
        <x-slot name="badge">
            @php
                $st = is_object($ticket->status) ? $ticket->status->value : (string)$ticket->status;
            @endphp
            @if(in_array($st, ['resolved', 'closed']))
                <x-badge variant="success" dot size="sm">Resolved</x-badge>
            @elseif($st === 'in_progress')
                <x-badge variant="info" dot size="sm">In Progress</x-badge>
            @else
                <x-badge variant="warning" dot size="sm">Open</x-badge>
            @endif
        </x-slot>
    </x-page-header>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Ticket Summary Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-3">
        <h2 class="text-base font-black text-slate-900 dark:text-white">{{ $ticket->subject }}</h2>

        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
            <div>Category: <strong class="text-slate-800 dark:text-slate-200 capitalize">{{ $ticket->category }}</strong></div>
            <div>Priority: <strong class="text-slate-800 dark:text-slate-200 capitalize">{{ is_object($ticket->priority) ? $ticket->priority->label() : ucfirst($ticket->priority) }}</strong></div>
            @if($ticket->order)
                <div>Order: <strong class="text-slate-800 dark:text-slate-200 font-mono">#{{ $ticket->order->order_number }}</strong></div>
            @endif
            @if($ticket->assignedAgent)
                <div>Assigned Agent: <strong class="text-slate-800 dark:text-slate-200">{{ $ticket->assignedAgent->name }}</strong></div>
            @endif
        </div>
    </div>

    <!-- Message Thread -->
    <div class="space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Discussion History</h3>

        @foreach($ticket->messages->where('is_internal', false) as $msg)
            @php
                $isFromAdmin = $msg->user && ($msg->user->hasRole('admin') || $msg->user->hasRole('super_admin') || $msg->user->hasRole('support_admin'));
            @endphp
            <div class="flex {{ $isFromAdmin ? 'justify-start' : 'justify-end' }}">
                <div class="max-w-2xl w-full rounded-3xl p-5 border shadow-xs space-y-2 {{ $isFromAdmin ? 'bg-indigo-50/70 dark:bg-slate-800/80 border-indigo-200 dark:border-slate-700' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800' }}">
                    <div class="flex items-center justify-between text-xs">
                        <div class="font-bold flex items-center space-x-2">
                            @if($isFromAdmin)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white">ALLZONE Support</span>
                                <span class="text-slate-900 dark:text-white">{{ $msg->user?->name ?: 'Support Representative' }}</span>
                            @else
                                <span class="text-slate-900 dark:text-white">{{ $msg->user?->name ?: 'You' }} (Merchant)</span>
                            @endif
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $msg->created_at->format('d M Y, h:i A') }}</span>
                    </div>

                    <div class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
                        {{ $msg->message }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Post Reply Form -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Post Reply</h3>

        <form method="POST" action="{{ route('seller.support.reply', $ticket) }}" class="space-y-4">
            @csrf
            <div>
                <textarea 
                    name="message" 
                    rows="4" 
                    required 
                    placeholder="Type your message to ALLZONE Support..."
                    class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5 focus:ring-1 focus:ring-emerald-500"
                ></textarea>
                @error('message')
                    <p class="text-rose-500 text-[11px] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition"
                >
                    Send Reply
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
