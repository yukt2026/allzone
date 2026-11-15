<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    /**
     * Display seller support tickets registry.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'No seller profile associated with this account.');

        $query = SupportTicket::query()
            ->where('seller_id', $seller->id)
            ->with(['messages', 'order'])
            ->latest('id');

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(15)->withQueryString();

        $metrics = [
            'total' => SupportTicket::where('seller_id', $seller->id)->count(),
            'open' => SupportTicket::where('seller_id', $seller->id)->whereIn('status', [TicketStatus::OPEN, TicketStatus::IN_PROGRESS])->count(),
            'resolved' => SupportTicket::where('seller_id', $seller->id)->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])->count(),
        ];

        return view('seller.support.index', compact('tickets', 'metrics'));
    }

    /**
     * Show form to open a new support ticket.
     */
    public function create(): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $recentOrders = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->with('order')
            ->latest('id')
            ->take(20)
            ->get();

        return view('seller.support.create', compact('recentOrders'));
    }

    /**
     * Store new support ticket and initial message.
     */
    public function store(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:payout,order,product,logistics,account,general'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'user_id' => Auth::id(),
            'seller_id' => $seller->id,
            'order_id' => $validated['order_id'] ?? null,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'priority' => TicketPriority::from($validated['priority']),
            'status' => TicketStatus::OPEN,
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_internal' => false,
        ]);

        return redirect()->route('seller.support.show', $ticket)
            ->with('success', "Support ticket #{$ticket->ticket_number} created. An ALLZONE representative will respond promptly.");
    }

    /**
     * Display ticket message thread.
     */
    public function show(SupportTicket $ticket): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || (int)$ticket->seller_id !== (int)$seller->id, 403, 'Unauthorized ticket access.');

        $ticket->load([
            'user',
            'order',
            'assignedAgent',
            'messages.user',
        ]);

        return view('seller.support.show', compact('ticket'));
    }

    /**
     * Post reply to support ticket.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || (int)$ticket->seller_id !== (int)$seller->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_internal' => false,
        ]);

        // If previously resolved, reopen ticket upon merchant response
        if (in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::WAITING_CUSTOMER])) {
            $ticket->status = TicketStatus::IN_PROGRESS;
            $ticket->save();
        }

        return back()->with('success', 'Reply posted successfully.');
    }
}
