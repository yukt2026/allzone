@extends('layouts.dashboard', ['portal' => 'admin', 'title' => 'Ticket #' . $ticket->ticket_number])

@section('content')
<div class="space-y-6 max-w-6xl mx-auto" x-data="{ escalateModal: false, requestInfoModal: false }">

    <!-- Page Header -->
    <x-page-header 
        :title="'Ticket #' . $ticket->ticket_number . ': ' . $ticket->subject" 
        subtitle="Customer resolution thread, escalation logs, and internal staff notes."
        :breadcrumbs="['Admin Central' => route('admin.dashboard'), 'Support Tickets' => route('admin.support.index'), '#' . $ticket->ticket_number => '#']"
    >
        <x-slot name="badge">
            @php
                $stVal = is_object($ticket->status) ? $ticket->status->value : (string) $ticket->status;
                $prVal = is_object($ticket->priority) ? $ticket->priority->value : (string) $ticket->priority;
            @endphp
            <div class="flex items-center space-x-1.5">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border
                    {{ $stVal === 'open' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-400' : '' }}
                    {{ $stVal === 'in_progress' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-400' : '' }}
                    {{ $stVal === 'waiting_customer' ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/60 dark:text-sky-400' : '' }}
                    {{ $stVal === 'resolved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400' : '' }}
                    {{ $stVal === 'closed' ? 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400' : '' }}
                ">
                    {{ ucfirst(str_replace('_', ' ', $stVal)) }}
                </span>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase
                    {{ $prVal === 'urgent' ? 'bg-rose-600 text-white' : '' }}
                    {{ $prVal === 'high' ? 'bg-amber-500 text-white' : '' }}
                    {{ $prVal === 'medium' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $prVal === 'low' ? 'bg-slate-100 text-slate-600' : '' }}
                ">
                    {{ $prVal }}
                </span>

                @if($ticket->is_escalated)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black uppercase bg-purple-600 text-white shadow-xs">
                        🚨 Escalated
                    </span>
                @endif
            </div>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center space-x-2">
                @if(!$ticket->is_escalated)
                    <button 
                        type="button" 
                        @click="escalateModal = true"
                        class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-xl text-xs font-bold transition shadow-xs"
                    >
                        🚨 Escalate Ticket
                    </button>
                @endif

                <button 
                    type="button" 
                    @click="requestInfoModal = true"
                    class="px-3.5 py-2 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-xl text-xs font-bold transition shadow-xs"
                >
                    ℹ️ Request Info
                </button>

                <a 
                    href="{{ route('admin.support.index') }}" 
                    class="px-3.5 py-2 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition shadow-xs"
                >
                    &larr; Back to Desk
                </a>
            </div>
        </x-slot>
    </x-page-header>

    @if($ticket->is_escalated && $ticket->escalation_reason)
        <div class="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-xs text-purple-900 flex items-start space-x-3">
            <svg class="w-5 h-5 text-purple-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong class="font-bold text-purple-950">Active Ticket Escalation (Priority: URGENT)</strong>
                <p class="mt-0.5 text-purple-800">{{ $ticket->escalation_reason }}</p>
                <div class="text-[10px] text-purple-600 mt-1">Escalated on {{ $ticket->escalated_at?->format('d M Y, h:i A') ?? 'N/A' }}</div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Messages Thread -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Message History Container -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Conversation Timeline &amp; History</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Full audit trail of customer communication and internal notes.</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-slate-500">{{ $ticket->messages->count() }} Message(s)</span>
                </div>

                <div class="space-y-4">
                    @forelse($ticket->messages as $msg)
                        <div class="p-4 rounded-2xl border transition {{ $msg->is_internal ? 'bg-amber-50/70 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/40' : ($msg->user_id === auth()->id() ? 'bg-slate-50 border-slate-200 dark:bg-slate-800/40 dark:border-slate-700' : 'bg-white border-slate-200 dark:bg-slate-900 dark:border-slate-800') }}">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-xs text-slate-900 dark:text-white">{{ $msg->user?->name ?? 'User' }}</span>
                                    @if($msg->user?->hasRole(['super_admin', 'admin', 'support_admin']))
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400 uppercase">Support Staff</span>
                                    @endif
                                    @if($msg->is_internal)
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-amber-200 text-amber-900 uppercase tracking-wider">
                                            🔒 Internal Staff Note
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('d M Y, h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                            </div>

                            <div class="text-xs text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
                                {{ $msg->message }}
                            </div>

                            <!-- Attachments Preview -->
                            @if($msg->hasAttachments())
                                <div class="mt-3 pt-3 border-t border-slate-200/60 dark:border-slate-700/60 flex flex-wrap gap-2">
                                    @foreach($msg->attachment_urls as $att)
                                        <a 
                                            href="{{ $att['url'] }}" 
                                            target="_blank" 
                                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400 hover:bg-slate-50 transition shadow-2xs"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span class="max-w-[180px] truncate">{{ $att['name'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-xs text-slate-400">
                            No messages posted yet.
                        </div>
                    @endforelse
                </div>

                <!-- Reply Composer -->
                <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800" id="ticketReplyForm">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Respond to Ticket or Add Internal Note</label>
                            <button 
                                type="button" 
                                id="aiSuggestBtn"
                                class="inline-flex items-center space-x-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:hover:bg-purple-900/40 px-2.5 py-1 rounded-xl transition"
                                title="Synthesize smart automated response draft via AI"
                            >
                                <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span>AI Suggest Reply</span>
                            </button>
                        </div>
                        <textarea 
                            name="message" 
                            id="replyMessage"
                            rows="4" 
                            required 
                            placeholder="Type your official reply to the ticket initiator or document private internal notes..." 
                            class="w-full text-xs rounded-2xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-3.5 focus:ring-1 focus:ring-slate-900"
                        ></textarea>
                    </div>

                    <!-- File Attachments Upload -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Attach Supporting Files / Invoices / Proofs (Optional)</label>
                        <input 
                            type="file" 
                            name="attachments[]" 
                            multiple 
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                        >
                        <span class="text-[10px] text-slate-400 mt-1 block">Supported: PDF, Images (JPG, PNG), DOCX, ZIP up to 10MB each.</span>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="space-y-1">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="is_internal" value="1" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Post as private internal note (hidden from customer &amp; seller)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="wait_seller" value="1" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                <span class="text-xs font-medium text-sky-700 dark:text-sky-400">Mark status as Waiting for Response</span>
                            </label>
                        </div>

                        <button 
                            type="submit" 
                            class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold shadow-xs transition"
                        >
                            Post Message
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Sidebar / Meta Details & Status Control -->
        <div class="space-y-6">

            <!-- Status & Assignment Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Ticket Governance</h3>

                <!-- Status Update -->
                <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="space-y-2">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Ticket Status</label>
                    <div class="flex space-x-2">
                        <select name="status" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5">
                            <option value="open" {{ $stVal === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $stVal === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="waiting_customer" {{ $stVal === 'waiting_customer' ? 'selected' : '' }}>Waiting for Response</option>
                            <option value="resolved" {{ $stVal === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $stVal === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Update
                        </button>
                    </div>
                </form>

                <!-- Priority Update -->
                <form method="POST" action="{{ route('admin.support.priority', $ticket) }}" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Ticket Priority</label>
                    <div class="flex space-x-2">
                        <select name="priority" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5">
                            <option value="low" {{ $prVal === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $prVal === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $prVal === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ $prVal === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Set
                        </button>
                    </div>
                </form>

                <!-- Assignee -->
                <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Assigned Support Staff</label>
                    <div class="flex space-x-2">
                        <select name="assigned_to" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5">
                            <option value="">Unassigned</option>
                            @foreach($staff as $st)
                                <option value="{{ $st->id }}" {{ $ticket->assigned_to == $st->id ? 'selected' : '' }}>
                                    {{ $st->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            Assign
                        </button>
                    </div>
                </form>
            </div>

            <!-- Customer & Order Information -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Initiator &amp; Context</h3>

                <div>
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Customer</div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white mt-0.5">{{ $ticket->user?->name ?? 'Guest User' }}</div>
                    <div class="text-xs text-slate-500">{{ $ticket->user?->email }}</div>
                    @if($ticket->user?->phone)
                        <div class="text-xs text-slate-500">{{ $ticket->user->phone }}</div>
                    @endif
                </div>

                @if($ticket->seller)
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Seller / Merchant</div>
                    <div class="text-xs font-bold text-slate-900 dark:text-white mt-0.5">{{ $ticket->seller->business_name }}</div>
                    <div class="text-xs text-slate-500">Owner: {{ $ticket->seller->user?->name ?? 'N/A' }}</div>
                </div>
                @endif

                @if($ticket->order)
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Referenced Order</div>
                    <a href="{{ route('admin.orders.show', $ticket->order) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline block mt-0.5">
                        {{ $ticket->order->order_number }}
                    </a>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Total: {{ format_currency((float)$ticket->order->total) }} &bull; {{ $ticket->order->status instanceof \App\Enums\OrderStatus ? $ticket->order->status->label() : ucfirst($ticket->order->status->value ?? (string) $ticket->order->status) }}
                    </div>
                </div>
                @endif

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Created:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $ticket->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($ticket->first_response_at)
                    <div class="flex justify-between text-slate-500">
                        <span>First Staff Response:</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $ticket->first_response_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @endif
                    @if($ticket->resolved_at)
                    <div class="flex justify-between text-slate-500">
                        <span>Resolved At:</span>
                        <span class="font-mono text-emerald-600 font-bold">{{ $ticket->resolved_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

    <!-- Escalate Ticket Modal -->
    <div x-show="escalateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="escalateModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">🚨 Escalate Support Ticket</h3>
                <p class="text-xs text-slate-500 mb-4">Mark priority as URGENT and assign to lead supervisor for immediate intervention.</p>

                <form action="{{ route('admin.support.escalate', $ticket) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Escalation Justification &amp; Notes <span class="text-rose-500">*</span></label>
                        <textarea 
                            name="escalation_reason" 
                            rows="3" 
                            required 
                            placeholder="Detail why this dispute/ticket requires supervisor escalation..." 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-3"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Assign to Supervisor (Optional)</label>
                        <select name="assigned_to" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-2.5">
                            <option value="">Leave Current Assignee</option>
                            @foreach($staff as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="escalateModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 rounded-xl hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white rounded-xl shadow-xs">Confirm Escalation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Request Info Modal -->
    <div x-show="requestInfoModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="requestInfoModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">ℹ️ Request Information</h3>
                <p class="text-xs text-slate-500 mb-4">Prompt the user or seller for missing order details, unboxing video, or evidence. Automatically switches ticket status to Waiting for Response.</p>

                <form action="{{ route('admin.support.request_info', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Information Required <span class="text-rose-500">*</span></label>
                        <textarea 
                            name="message" 
                            rows="4" 
                            required 
                            placeholder="Specify clearly what photos, documents, or details are required..." 
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white p-3"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Attach Guidance Document (Optional)</label>
                        <input type="file" name="attachments[]" multiple class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700">
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="requestInfoModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 rounded-xl hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white rounded-xl shadow-xs">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const aiBtn = document.getElementById('aiSuggestBtn');
    const replyInput = document.getElementById('replyMessage');

    if (aiBtn && replyInput) {
        aiBtn.addEventListener('click', async function () {
            const originalHtml = aiBtn.innerHTML;
            aiBtn.disabled = true;
            aiBtn.classList.add('opacity-70', 'cursor-not-allowed');
            aiBtn.innerHTML = `
                <svg class="animate-spin w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Synthesizing...</span>
            `;

            try {
                const response = await fetch("{{ route('admin.support.ai_reply', $ticket) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ auto_send: false })
                });

                const data = await response.json();
                if (data.success && data.data && data.data.reply_text) {
                    replyInput.value = data.data.reply_text;
                    replyInput.focus();
                } else {
                    alert('Could not synthesize AI reply. Please draft manually.');
                }
            } catch (err) {
                console.error('AI draft generation failed', err);
                alert('AI service currently unavailable.');
            } finally {
                aiBtn.disabled = false;
                aiBtn.classList.remove('opacity-70', 'cursor-not-allowed');
                aiBtn.innerHTML = originalHtml;
            }
        });
    }
});
</script>
@endsection
