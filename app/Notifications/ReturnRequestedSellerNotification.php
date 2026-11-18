<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\OrderReturn;

class ReturnRequestedSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly OrderReturn $orderReturn
    ) {
        $this->category = 'returns_claims';
        $this->eventKey = 'seller_return_request';
        $this->referenceId = $orderReturn->id;
        $retNumber = $orderReturn->return_number;
        $orderNumber = $orderReturn->orderItem?->fulfilmentOrder?->order?->order_number ?? ($orderReturn->order?->order_number ?? 'N/A');
        $reason = $orderReturn->reason ?? 'Customer return request';
        $amount = function_exists('format_currency') ? format_currency((float) $orderReturn->refund_amount) : '₹' . number_format((float) $orderReturn->refund_amount, 2);

        $this->title = "Return Requested: #{$retNumber}";
        $this->message = "Customer requested a return for Order #{$orderNumber} (Return #{$retNumber}, {$amount}). Reason: {$reason}.";
        $this->actionUrl = route('seller.returns.index');
        $this->actionText = 'Review Return Request';
        $this->extraData = [
            'return_id' => $orderReturn->id,
            'return_number' => $retNumber,
            'order_number' => $orderNumber,
            'reason' => $reason,
            'refund_amount' => (float) $orderReturn->refund_amount,
        ];
    }
}
