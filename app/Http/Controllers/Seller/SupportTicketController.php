<?php

declare(strict_types=1);

namespace App\Http\Controllers\Seller;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\FulfilmentOrder;
use App\Models\Product;
use App\Models\SupportCategory;
use App\Models\SupportQuestion;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    /**
     * Display seller support desk and help categories.
     */
    public function index(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403, 'No seller profile associated with this account.');

        $tab = $request->query('tab', 'help');
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        // Help Categories
        $categoriesQuery = SupportCategory::query()->active()->with('activeQuestions');
        if ($search && $tab === 'help') {
            $categoriesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('questions', fn($sub) => $sub->where('question', 'like', "%{$search}%"));
            });
        }
        $categories = $categoriesQuery->get();

        // My Tickets
        $ticketsQuery = SupportTicket::query()
            ->where('seller_id', $seller->id)
            ->with(['categoryRelation', 'questionRelation', 'order', 'product'])
            ->latest('id');

        // Status filter
        if ($status && $status !== 'all') {
            if ($status === 'waiting_seller') {
                $ticketsQuery->where('status', TicketStatus::WAITING_CUSTOMER);
            } elseif ($status === 'open') {
                $ticketsQuery->where('status', TicketStatus::OPEN);
            } elseif ($status === 'in_progress') {
                $ticketsQuery->where('status', TicketStatus::IN_PROGRESS);
            } elseif ($status === 'resolved') {
                $ticketsQuery->where('status', TicketStatus::RESOLVED);
            } elseif ($status === 'closed') {
                $ticketsQuery->where('status', TicketStatus::CLOSED);
            }
        }

        if ($search && $tab === 'tickets') {
            $ticketsQuery->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $ticketsQuery->paginate(15)->withQueryString();

        // Real counts per status for authenticated seller
        $baseCountQuery = SupportTicket::where('seller_id', $seller->id);
        $metrics = [
            'total' => (clone $baseCountQuery)->count(),
            'open' => (clone $baseCountQuery)->where('status', TicketStatus::OPEN)->count(),
            'in_progress' => (clone $baseCountQuery)->where('status', TicketStatus::IN_PROGRESS)->count(),
            'waiting_seller' => (clone $baseCountQuery)->where('status', TicketStatus::WAITING_CUSTOMER)->count(),
            'resolved' => (clone $baseCountQuery)->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => (clone $baseCountQuery)->where('status', TicketStatus::CLOSED)->count(),
        ];

        return view('seller.support.index', compact(
            'seller',
            'tickets',
            'metrics',
            'categories',
            'tab',
            'status',
            'search'
        ));
    }

    /**
     * Show form to open a new support ticket.
     */
    public function create(Request $request): View
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $selectedCategorySlug = $request->query('category');
        $selectedQuestionId = $request->query('question');

        $categories = SupportCategory::active()->with('activeQuestions')->get();

        $selectedCategory = $selectedCategorySlug 
            ? $categories->firstWhere('slug', $selectedCategorySlug) 
            : null;

        // Fetch recent orders for selection
        $recentOrders = FulfilmentOrder::query()
            ->where('seller_id', $seller->id)
            ->with(['order', 'shipment'])
            ->latest('id')
            ->take(25)
            ->get();

        // Fetch seller products for selection
        $products = Product::query()
            ->where('seller_id', $seller->id)
            ->select(['id', 'title', 'sku'])
            ->orderBy('title')
            ->take(50)
            ->get();

        return view('seller.support.create', compact(
            'seller',
            'categories',
            'selectedCategory',
            'selectedQuestionId',
            'recentOrders',
            'products'
        ));
    }

    /**
     * Store new support ticket and initial message with attachments.
     */
    public function store(Request $request): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller, 403);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'support_category_id' => ['required', 'exists:support_categories,id'],
            'support_question_id' => ['nullable', 'exists:support_questions,id'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'awb_number' => ['nullable', 'string', 'max:100'],
            'callback_phone' => ['nullable', 'string', 'max:25'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,docx,zip', 'max:10240'],
        ]);

        return DB::transaction(function () use ($validated, $request, $seller) {
            $category = SupportCategory::find($validated['support_category_id']);

            // Unique ALLZONE ticket identifier
            $uniqueNumber = 'TCK-' . date('Y') . '-' . str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            while (SupportTicket::where('ticket_number', $uniqueNumber)->exists()) {
                $uniqueNumber = 'TCK-' . date('Y') . '-' . str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            }

            $ticket = SupportTicket::create([
                'ticket_number' => $uniqueNumber,
                'user_id' => Auth::id(),
                'seller_id' => $seller->id,
                'order_id' => !empty($validated['order_id']) ? (int) $validated['order_id'] : null,
                'product_id' => !empty($validated['product_id']) ? (int) $validated['product_id'] : null,
                'support_category_id' => $category->id,
                'support_question_id' => !empty($validated['support_question_id']) ? (int) $validated['support_question_id'] : null,
                'category' => $category->slug,
                'awb_number' => $validated['awb_number'] ?? null,
                'callback_phone' => $validated['callback_phone'] ?? (Auth::user()->phone ?? null),
                'subject' => $validated['subject'],
                'priority' => !empty($validated['priority']) ? TicketPriority::from($validated['priority']) : TicketPriority::MEDIUM,
                'status' => TicketStatus::OPEN,
            ]);

            // Handle file attachments
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

            // Create initial ticket message
            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $validated['message'],
                'attachments' => $attachmentsData,
                'is_internal' => false,
            ]);

            return redirect()->route('seller.support.show', $ticket)
                ->with('success', "Support ticket #{$ticket->ticket_number} created successfully. Our team will review your request.");
        });
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
            'order.fulfilmentOrders.shipment',
            'product',
            'categoryRelation',
            'questionRelation',
            'assignedAgent',
            'publicMessages.user',
        ]);

        return view('seller.support.show', compact('ticket'));
    }

    /**
     * Post reply to support ticket with optional attachments.
     */
    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || (int)$ticket->seller_id !== (int)$seller->id, 403, 'Unauthorized ticket action.');

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
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'attachments' => $attachmentsData,
            'is_internal' => false,
        ]);

        // If ticket was resolved or waiting for seller, transition back to in_progress
        if (in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::WAITING_CUSTOMER], true)) {
            $ticket->status = TicketStatus::IN_PROGRESS;
            $ticket->save();
        }

        return back()->with('success', 'Your reply has been submitted.');
    }

    /**
     * Reopen a closed or resolved ticket.
     */
    public function reopen(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $seller = Auth::user()->seller;
        abort_if(!$seller || (int)$ticket->seller_id !== (int)$seller->id, 403, 'Unauthorized ticket action.');

        if (!$ticket->canBeReopened()) {
            return back()->with('error', 'This ticket has been permanently closed and cannot be reopened. Please open a new ticket.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $ticket->reopen();

        TicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => "[Ticket Reopened by Merchant]\nReason: " . $validated['reason'],
            'is_internal' => false,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been reopened.");
    }
}
