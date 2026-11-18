@extends('layouts.dashboard', ['portal' => 'seller', 'title' => 'Ticket #' . $ticket->ticket_number . ' - ALLZONE Supplier Hub'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('seller.support.index', ['tab' => 'tickets']) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to My Tickets</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-mono">{{ $ticket->ticket_number }}</span>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold
                {{ $ticket->status_badge_color === 'rose' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                  ($ticket->status_badge_color === 'amber' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                  ($ticket->status_badge_color === 'indigo' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                  ($ticket->status_badge_color === 'emerald' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                  'bg-slate-100 text-slate-600 border border-slate-200'))) }}">
                {{ $ticket->status_label }}
            </span>
        </div>
    </div>

    <!-- Ticket Title -->
    <div>
        <div class="flex flex-wrap items-center gap-2 mb-1.5">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                {{ $ticket->category_name }}
            </span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                {{ $ticket->priority->value === 'urgent' ? 'bg-rose-600 text-white' :
                  ($ticket->priority->value === 'high' ? 'bg-amber-500 text-white' :
                  'bg-slate-100 text-slate-600') }}">
                {{ $ticket->priority->label() }} Priority
            </span>
            <span class="text-xs text-slate-400 font-medium">
                &bull; Opened {{ $ticket->created_at->format('d M Y, h:i A') }}
            </span>
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight leading-snug">
            {{ $ticket->subject }}
        </h1>
    </div>

    <!-- Main Content 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Left: Conversation History & Reply Box -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Messages Stream -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center justify-between">
                    <span>Conversation History</span>
                    <span class="text-slate-400 text-[11px] font-medium">{{ $ticket->publicMessages->count() }} messages</span>
                </h3>

                <div class="space-y-6">
                    @forelse($ticket->publicMessages as $msg)
                        @php
                            $isSeller = (int)$msg->user_id === (int)auth()->id();
                        @endphp
                        <div class="flex items-start gap-3.5 {{ $isSeller ? 'flex-row-reverse' : '' }}">
                            <!-- Avatar -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold
                                {{ $isSeller ? 'bg-indigo-600 text-white' : 'bg-emerald-600 text-white' }}">
                                {{ $isSeller ? 'You' : 'AZ' }}
                            </div>

                            <!-- Bubble -->
                            <div class="max-w-xl space-y-1.5 {{ $isSeller ? 'items-end' : '' }}">
                                <div class="flex items-center gap-2 text-[11px] {{ $isSeller ? 'justify-end' : '' }}">
                                    <span class="font-bold text-slate-800">
                                        {{ $isSeller ? 'You (Seller)' : ($msg->user?->name ?? 'ALLZONE Support Staff') }}
                                    </span>
                                    <span class="text-slate-400">&bull; {{ $msg->created_at->format('d M, h:i A') }}</span>
                                </div>

                                <div class="p-4 rounded-2xl text-xs sm:text-sm leading-relaxed whitespace-pre-line shadow-2xs
                                    {{ $isSeller ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-slate-50 border border-slate-200/90 text-slate-800 rounded-tl-none' }}">
                                    {!! e($msg->message) !!}

                                    <!-- Attachments -->
                                    @if($msg->hasAttachments())
                                        <div class="mt-3 pt-3 border-t {{ $isSeller ? 'border-indigo-500/50' : 'border-slate-200' }} space-y-2">
                                            <div class="text-[11px] font-bold uppercase tracking-wider {{ $isSeller ? 'text-indigo-200' : 'text-slate-500' }}">
                                                Attached Files ({{ count($msg->attachments) }})
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach($msg->attachment_urls as $att)
                                                    @if($att['is_image'])
                                                        <a href="{{ $att['url'] }}" target="_blank" class="block rounded-lg overflow-hidden border {{ $isSeller ? 'border-indigo-400' : 'border-slate-200' }} hover:opacity-90 transition">
                                                            <img src="{{ $att['url'] }}" alt="{{ $att['name'] }}" class="w-full h-24 object-cover">
                                                        </a>
                                                    @else
                                                        <a href="{{ $att['url'] }}" target="_blank" download="{{ $att['name'] }}"
                                                           class="p-2 rounded-lg flex items-center gap-2 text-xs font-medium transition {{ $isSeller ? 'bg-indigo-700/60 hover:bg-indigo-700 text-white' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span class="truncate">{{ $att['name'] }}</span>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs">
                            No messages recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Reply Box (When ticket is open or in progress) -->
            @if($ticket->isOpen())
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span>Post a Response</span>
                    </h3>

                    <form action="{{ route('seller.support.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <textarea name="message"
                                      rows="4"
                                      required
                                      placeholder="Type your reply, provide requested order details, or add clarification..."
                                      class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50 p-3 font-medium focus:ring-1 focus:ring-indigo-600 focus:bg-white transition"></textarea>
                            @error('message')
                                <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Attach Supporting Files (Optional)</label>
                            <input type="file"
                                   name="attachments[]"
                                   multiple
                                   accept="image/png,image/jpeg,image/webp,application/pdf,.docx,.zip"
                                   class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-lg p-1.5">
                            <p class="text-[10px] text-slate-400 mt-1">Up to 5 files (JPG, PNG, PDF, DOCX, ZIP max 10MB each).</p>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Reopen Ticket Box (When ticket is resolved or closed and within reopen window) -->
            @if(!$ticket->isOpen() && $ticket->canBeReopened())
                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-6 shadow-2xs space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-600 text-lg">⚠️</span>
                        <h3 class="text-xs font-bold text-amber-900 uppercase tracking-wider">
                            Ticket {{ $ticket->status_label }}
                        </h3>
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        This issue was marked as {{ strtolower($ticket->status_label) }}. If your problem remains unresolved or further assistance is required, you can reopen this ticket within 14 days of resolution.
                    </p>

                    <form action="{{ route('seller.support.reopen', $ticket) }}" method="POST" class="space-y-3 pt-2">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-amber-900 mb-1">Reason for Reopening *</label>
                            <textarea name="reason"
                                      rows="2"
                                      required
                                      placeholder="State why the issue is still pending or what further help is needed..."
                                      class="w-full text-xs rounded-lg border border-amber-300 bg-white p-2.5 font-medium focus:ring-1 focus:ring-amber-500 transition"></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                            Reopen Support Ticket
                        </button>
                    </form>
                </div>
            @elseif(!$ticket->isOpen())
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-center text-xs text-slate-500">
                    This support ticket has been permanently archived. If you have a new issue, please <a href="{{ route('seller.support.create') }}" class="text-indigo-600 font-bold hover:underline">raise a new support ticket</a>.
                </div>
            @endif
        </div>

        <!-- Right: Ticket Information & Metadata -->
        <div class="space-y-5">
            <!-- Ticket Info Card -->
            <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs space-y-4 text-xs">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                    Ticket Overview
                </h3>

                <!-- Status -->
                <div>
                    <div class="text-[11px] text-slate-400 font-medium">Ticket Status</div>
                    <div class="mt-1">
                        <span class="px-2.5 py-1 rounded text-xs font-bold
                            {{ $ticket->status_badge_color === 'rose' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                              ($ticket->status_badge_color === 'amber' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                              ($ticket->status_badge_color === 'indigo' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                              ($ticket->status_badge_color === 'emerald' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                              'bg-slate-100 text-slate-600 border border-slate-200'))) }}">
                            {{ $ticket->status_label }}
                        </span>
                    </div>
                </div>

                <!-- SLA Expected Response Time -->
                <div>
                    <div class="text-[11px] text-slate-400 font-medium">Expected SLA Resolution</div>
                    <div class="font-bold text-slate-800 mt-0.5">
                        {{ $ticket->created_at->addHours(24)->format('d M Y, h:i A') }}
                    </div>
                </div>

                <!-- Category & Question -->
                <div>
                    <div class="text-[11px] text-slate-400 font-medium">Issue Category</div>
                    <div class="font-bold text-slate-900 mt-0.5">{{ $ticket->category_name }}</div>
                    @if($ticket->questionRelation)
                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $ticket->questionRelation->question }}</div>
                    @endif
                </div>

                <!-- Assigned Support Agent -->
                <div>
                    <div class="text-[11px] text-slate-400 font-medium">Assigned Support Agent</div>
                    <div class="font-bold text-slate-800 mt-0.5">
                        {{ $ticket->assignedAgent?->name ?? 'ALLZONE Support Desk (Unassigned)' }}
                    </div>
                </div>

                <!-- Associated Order -->
                @if($ticket->order)
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[11px] text-slate-400 font-medium">Associated Marketplace Order</div>
                        <div class="font-bold text-indigo-600 font-mono mt-0.5">
                            Order #{{ $ticket->order->order_number }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            Amount: ₹{{ number_format((float)$ticket->order->total, 2) }}
                        </div>
                    </div>
                @endif

                <!-- Associated Product -->
                @if($ticket->product)
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[11px] text-slate-400 font-medium">Associated Catalog Product</div>
                        <div class="font-bold text-slate-900 mt-0.5 truncate">
                            {{ $ticket->product->title }}
                        </div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                            SKU: {{ $ticket->product->sku }}
                        </div>
                    </div>
                @endif

                <!-- AWB Number -->
                @if($ticket->awb_number)
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[11px] text-slate-400 font-medium">Shiprocket AWB Number</div>
                        <div class="font-bold text-slate-900 font-mono mt-0.5">
                            {{ $ticket->awb_number }}
                        </div>
                    </div>
                @endif

                <!-- Callback Contact -->
                @if($ticket->callback_phone)
                    <div class="pt-2 border-t border-slate-100">
                        <div class="text-[11px] text-slate-400 font-medium">Callback Phone Number</div>
                        <div class="font-bold text-slate-900 font-mono mt-0.5">
                            {{ $ticket->callback_phone }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Help Guidelines Card -->
            <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-5 shadow-2xs space-y-2 text-xs">
                <h4 class="font-bold text-indigo-900 uppercase tracking-wider text-[11px]">
                    ALLZONE Merchant SLA
                </h4>
                <p class="text-indigo-800 leading-relaxed text-[11px]">
                    Our seller operations team responds to all tickets within 24 business hours. For urgent order dispatch blockers, tickets marked High or Urgent are escalated directly to the logistics desk.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
