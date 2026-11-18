<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;

class SupportTicketReplySellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $responderName,
        public readonly string $excerpt
    ) {
        $this->category = 'support';
        $this->eventKey = 'seller_ticket_reply';
        $this->referenceId = $ticket->id;
        $ticketNumber = $ticket->ticket_number;

        $this->title = "New Reply: Ticket #{$ticketNumber}";
        $this->message = "{$responderName} replied to your support ticket #{$ticketNumber} ({$ticket->subject}): \"{$excerpt}\"";
        $this->actionUrl = route('seller.support.show', $ticket->id);
        $this->actionText = 'View Ticket Conversation';
        $this->extraData = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticketNumber,
            'subject' => $ticket->subject,
            'responder_name' => $responderName,
            'excerpt' => $excerpt,
        ];
    }
}
