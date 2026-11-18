<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FulfilmentOrder;

class OrderCancelledSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly FulfilmentOrder $fulfilmentOrder,
        public readonly ?string $reason = null
    ) {
        $this->category = 'orders';
        $this->eventKey = 'seller_order_cancelled';
        $this->referenceId = $fulfilmentOrder->id;
        $foNumber = $fulfilmentOrder->fulfilment_order_number;
        $orderNumber = $fulfilmentOrder->order?->order_number ?? 'N/A';

        $this->title = "Order Cancelled: Package #{$foNumber}";
        $this->message = "Order package #{$foNumber} (Order #{$orderNumber}) was cancelled." . ($reason ? " Reason: {$reason}." : '') . " Do not ship this package.";
        $this->actionUrl = route('seller.orders.show', $fulfilmentOrder->id);
        $this->actionText = 'View Cancelled Order';
        $this->extraData = [
            'fulfilment_order_id' => $fulfilmentOrder->id,
            'fulfilment_order_number' => $foNumber,
            'order_number' => $orderNumber,
            'reason' => $reason,
        ];
    }
}
