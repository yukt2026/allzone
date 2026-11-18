<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FulfilmentOrder;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly FulfilmentOrder $fulfilmentOrder
    ) {
        $this->category = 'orders';
        $this->eventKey = 'seller_new_order';
        $this->referenceId = $fulfilmentOrder->id;
        $orderNumber = $fulfilmentOrder->order?->order_number ?? 'N/A';
        $foNumber = $fulfilmentOrder->fulfilment_order_number;
        $type = $fulfilmentOrder->fulfilment_type ? $fulfilmentOrder->fulfilment_type->label() : 'Standard';
        $amount = function_exists('format_currency') ? format_currency((float) $fulfilmentOrder->total) : '₹' . number_format((float) $fulfilmentOrder->total, 2);

        $this->title = "New {$type} Order: #{$foNumber}";
        $this->message = "You received a new order package (#{$foNumber} / Order #{$orderNumber}) worth {$amount}. Please process and pack within dispatch SLA.";
        $this->actionUrl = route('seller.orders.show', $fulfilmentOrder->id);
        $this->actionText = 'View & Process Order';
        $this->extraData = [
            'fulfilment_order_id' => $fulfilmentOrder->id,
            'fulfilment_order_number' => $foNumber,
            'order_number' => $orderNumber,
            'total' => (float) $fulfilmentOrder->total,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $foNumber = $this->fulfilmentOrder->fulfilment_order_number;
        $orderNumber = $this->fulfilmentOrder->order?->order_number ?? 'N/A';
        $type = $this->fulfilmentOrder->fulfilment_type ? $this->fulfilmentOrder->fulfilment_type->label() : 'Standard';
        $amount = function_exists('format_currency') ? format_currency((float) $this->fulfilmentOrder->total) : '₹' . number_format((float) $this->fulfilmentOrder->total, 2);
        $brandName = function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE';

        return (new MailMessage)
            ->subject("New {$type} Order Received: #{$foNumber}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have received a new {$type} order (#{$orderNumber} / Package #{$foNumber}) amounting to **{$amount}**.")
            ->line("Please accept and pack this package promptly according to dispatch SLA.")
            ->action('View & Process Order', route('seller.orders.show', $this->fulfilmentOrder))
            ->line("Thank you for being a valued merchant partner on {$brandName}!");
    }
}
