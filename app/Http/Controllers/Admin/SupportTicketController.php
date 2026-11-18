<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    /**
     * Display support tickets list.
     */
    public function index(Request $request): View
    {
        $tickets = $this->buildTicketsQuery($request)->paginate(15)->withQueryString();

        // Response-Time Reporting Computations
        $ticketsWithResponse = SupportTicket::whereNotNull('first_response_at')
            ->latest('id')
            ->take(500)
            ->get(['created_at', 'first_response_at']);

        $avgResponseMinutes = 0;
        if ($ticketsWithResponse->isNotEmpty()) {
            $totalMinutes = $ticketsWithResponse->sum(fn($t) => max(0, $t->created_at->diffInMinutes($t->first_response_at)));
            $avgResponseMinutes = round($totalMinutes / $ticketsWithResponse->count());
        }

        $avgResponseFormatted = $avgResponseMinutes >= 60
            ? round($avgResponseMinutes / 60, 1) . ' hrs'
            : ($avgResponseMinutes > 0 ? "{$avgResponseMinutes} mins" : '< 15 mins');

        $resolvedTickets = SupportTicket::whereNotNull('resolved_at')
            ->latest('id')
            ->take(500)
            ->get(['created_at', 'resolved_at']);

        $avgResolutionHours = 0;
        if ($resolvedTickets->isNotEmpty()) {
            $totalResHours = $resolvedTickets->sum(fn($t) => max(0, $t->created_at->diffInHours($t->resolved_at)));
            $avgResolutionHours = round($totalResHours / $resolvedTickets->count(), 1);
        }

        $avgResolutionFormatted = $avgResolutionHours >= 24
            ? round($avgResolutionHours / 24, 1) . ' days'
            : ($avgResolutionHours > 0 ? "{$avgResolutionHours} hrs" : '< 2 hrs');

        $slaPendingCount = SupportTicket::whereNull('first_response_at')
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        $metrics = [
            'total_tickets' => SupportTicket::count(),
            'open_tickets' => SupportTicket::where('status', TicketStatus::OPEN)->count(),
            'in_progress' => SupportTicket::where('status', TicketStatus::IN_PROGRESS)->count(),
            'waiting_customer' => SupportTicket::where('status', TicketStatus::WAITING_CUSTOMER)->count(),
            'resolved' => SupportTicket::where('status', TicketStatus::RESOLVED)->count(),
            'closed' => SupportTicket::where('status', TicketStatus::CLOSED)->count(),
            'escalated' => SupportTicket::where('is_escalated', true)->count(),
            'avg_first_response' => $avgResponseFormatted,
            'avg_resolution_time' => $avgResolutionFormatted,
            'sla_breached' => $slaPendingCount,
        ];

        $staff = $this->getStaffUsers();

        return view('admin.support.index', compact('tickets', 'metrics', 'staff'));
    }

    /**
     * Display single ticket thread.
     */
    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'seller', 'order', 'assignedAgent', 'messages.user']);
        $staff = $this->getStaffUsers();

        return view('admin.support.show', compact('ticket', 'staff'));
    }

    /**
     * Post a reply message or internal note.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,docx,zip', 'max:10240'],
        ]);

        $attachmentsData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $storedPath = $file->store("support/tickets/{$ticket->id}", 'public');
                $attachmentsData[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $storedPath,
                    'size' => $file->getSize(),
                ];
            }
        }

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'attachments' => $attachmentsData,
            'is_internal' => $request->boolean('is_internal'),
        ]);

        if (!$request->boolean('is_internal')) {
            if (!$ticket->first_response_at) {
                $ticket->first_response_at = now();
            }
            if ($ticket->status === TicketStatus::OPEN) {
                $ticket->status = TicketStatus::IN_PROGRESS;
            } elseif ($ticket->status === TicketStatus::IN_PROGRESS && $request->boolean('wait_seller')) {
                $ticket->status = TicketStatus::WAITING_CUSTOMER;
            }
            $ticket->save();
        }

        if (function_exists('activity')) {
            activity('support')
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log("Replied to ticket #{$ticket->ticket_number}" . ($request->boolean('is_internal') ? ' (internal note)' : ''));
        }

        return back()->with('success', 'Reply posted successfully.');
    }

    /**
     * Request additional information from customer or seller.
     */
    public function requestInfo(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,docx,zip', 'max:10240'],
        ]);

        $attachmentsData = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $storedPath = $file->store("support/tickets/{$ticket->id}", 'public');
                $attachmentsData[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $storedPath,
                    'size' => $file->getSize(),
                ];
            }
        }

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => "ℹ️ [INFORMATION REQUESTED]:\n" . $validated['message'],
            'attachments' => $attachmentsData,
            'is_internal' => false,
        ]);

        if (!$ticket->first_response_at) {
            $ticket->first_response_at = now();
        }
        $ticket->status = TicketStatus::WAITING_CUSTOMER;
        $ticket->save();

        if (function_exists('activity')) {
            activity('support')
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log("Requested information on ticket #{$ticket->ticket_number}");
        }

        return back()->with('success', "Information requested. Ticket marked as Waiting for Response.");
    }

    /**
     * Escalate ticket to Urgent priority.
     */
    public function escalate(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'escalation_reason' => ['required', 'string', 'max:1000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->escalate(
            $validated['escalation_reason'],
            !empty($validated['assigned_to']) ? (int) $validated['assigned_to'] : null
        );

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => "🚨 [ESCALATION TO URGENT]: " . $validated['escalation_reason'],
            'is_internal' => true,
        ]);

        if (function_exists('activity')) {
            activity('support')
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log("Ticket #{$ticket->ticket_number} escalated: {$validated['escalation_reason']}");
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been escalated to Urgent priority.");
    }

    /**
     * Assign ticket to an agent.
     */
    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->assigned_to = $validated['assigned_to'] ? (int)$validated['assigned_to'] : null;
        if ($ticket->status === TicketStatus::OPEN && $ticket->assigned_to) {
            $ticket->status = TicketStatus::IN_PROGRESS;
        }
        $ticket->save();

        return back()->with('success', 'Ticket assignment updated.');
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
        ]);

        $ticket->priority = TicketPriority::from($validated['priority']);
        $ticket->save();

        return back()->with('success', "Ticket priority updated to {$ticket->priority->label()}.");
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,waiting_customer,resolved,closed'],
        ]);

        $ticket->status = TicketStatus::from($validated['status']);
        if ($ticket->status === TicketStatus::RESOLVED) {
            $ticket->resolved_at = now();
        } elseif ($ticket->status === TicketStatus::CLOSED) {
            $ticket->closed_at = now();
        }
        $ticket->save();

        if (function_exists('activity')) {
            activity('support')
                ->performedOn($ticket)
                ->causedBy(auth()->user())
                ->log("Ticket #{$ticket->ticket_number} status changed to {$validated['status']}");
        }

        return back()->with('success', "Ticket marked as {$ticket->status->label()}.");
    }

    /**
     * AI: Generate contextual automated support reply or draft.
     */
    public function generateAiReply(Request $request, SupportTicket $ticket, \App\Services\AI\Contracts\SupportAiReplyInterface $aiReplyService): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $autoSend = $request->boolean('auto_send', false);
        $result = $aiReplyService->generateReply($ticket, $request->input('message'), $autoSend);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }

        if ($result['auto_posted']) {
            return back()->with('success', "AI assistant posted reply (Confidence: " . round($result['confidence'] * 100) . "%).");
        }

        return back()->with('info', "AI Suggested Reply:\n\n{$result['reply_text']}");
    }

    /**
     * Alias for updateStatus for route compatibility.
     */
    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        return $this->updateStatus($request, $ticket);
    }

    /**
     * Export support tickets as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tickets-' . now()->format('Ymd-His') . '.csv"',
        ];

        $query = $this->buildTicketsQuery($request);

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['Ticket #', 'Subject', 'Customer', 'Category', 'Priority', 'Status', 'Assigned Agent', 'Created At']);

            $query->chunk(100, function ($tickets) use ($handle) {
                foreach ($tickets as $t) {
                    $stVal = is_object($t->status) ? $t->status->value : (string) $t->status;
                    $prVal = is_object($t->priority) ? $t->priority->value : (string) $t->priority;
                    fputcsv($handle, [
                        $t->ticket_number,
                        $t->subject,
                        $t->user?->name ?? 'N/A',
                        $t->category ?? 'General',
                        ucfirst($prVal),
                        ucfirst(str_replace('_', ' ', $stVal)),
                        $t->assignedAgent?->name ?? 'Unassigned',
                        $t->created_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Build base query for tickets listing and export.
     */
    protected function buildTicketsQuery(Request $request)
    {
        $query = SupportTicket::query()->with(['user', 'seller', 'order', 'assignedAgent'])->latest('id');

        if ($search = $request->query('search')) {
            $search = trim((string) $search);
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('seller', fn($sq) => $sq->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if ($status === 'escalated') {
                $query->where('is_escalated', true);
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($priority = $request->query('priority')) {
            if ($priority !== 'all') {
                $query->where('priority', $priority);
            }
        }

        if ($assignedTo = $request->query('assigned_to')) {
            if ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif ($assignedTo !== 'all') {
                $query->where('assigned_to', (int) $assignedTo);
            }
        }

        return $query;
    }

    /**
     * Safely resolve admin & support staff users.
     */
    protected function getStaffUsers()
    {
        $adminRoles = ['super_admin', 'admin', 'support_admin'];
        $existingAdminRoles = \Spatie\Permission\Models\Role::whereIn('name', $adminRoles)->pluck('name')->toArray();
        return !empty($existingAdminRoles) ? User::role($existingAdminRoles)->get() : collect();
    }
}
