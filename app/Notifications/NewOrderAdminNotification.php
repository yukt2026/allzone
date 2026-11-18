<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;

class NewOrderAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Order $order
    ) {
        $this->category = 'orders';
        $this->eventKey = 'admin_new_order';
        $this->referenceId = $order->id;
        $orderNumber = $order->order_number;
        $total = function_exists('format_currency') ? format_currency((float) $order->total) : '₹' . number_format((float) $order->total, 2);
        $customerName = $order->customer?->name ?? 'Customer';

        $this->title = "New Platform Order: #{$orderNumber}";
        $this->message = "Customer {$customerName} placed order #{$orderNumber} amounting to {$total}. Packages have been routed to fulfillment.";
        $this->actionUrl = route('admin.orders.show', $order->id);
        $this->actionText = 'View Order Details';
        $this->extraData = [
            'order_id' => $order->id,
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
            'total' => (float) $order->total,
        ];
    }
}
