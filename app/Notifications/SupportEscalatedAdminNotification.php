<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;

class SupportEscalatedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $escalationReason
    ) {
        $this->category = 'support';
        $this->eventKey = 'admin_support_escalated';
        $this->referenceId = $ticket->id;
        $ticketNumber = $ticket->ticket_number;
        $priority = strtoupper($ticket->priority instanceof \BackedEnum ? $ticket->priority->value : (string) $ticket->priority);

        $this->title = "🚨 Escalated Ticket [{$priority}]: #{$ticketNumber}";
        $this->message = "Support ticket #{$ticketNumber} ('{$ticket->subject}') was escalated. Reason: {$escalationReason}. Immediate resolution required.";
        $this->actionUrl = route('admin.support.show', $ticket->id);
        $this->actionText = 'Respond to Escalated Ticket';
        $this->extraData = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticketNumber,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'reason' => $escalationReason,
        ];
    }
}
