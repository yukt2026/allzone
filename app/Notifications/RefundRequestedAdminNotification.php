<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Refund;

class RefundRequestedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Refund $refund,
        public readonly ?string $reason = null
    ) {
        $this->category = 'finance';
        $this->eventKey = 'admin_refund_request';
        $this->referenceId = $refund->id;
        $orderNumber = $refund->order?->order_number ?? 'N/A';
        $amount = function_exists('format_currency') ? format_currency((float) $refund->amount) : '₹' . number_format((float) $refund->amount, 2);

        $this->title = "Refund Action Required: Order #{$orderNumber}";
        $this->message = "Refund request of {$amount} for Order #{$orderNumber} is pending admin review and Razorpay workflow initiation." . ($reason ? " Reason: {$reason}." : '');
        $this->actionUrl = route('admin.returns.index');
        $this->actionText = 'Review Returns & Refunds';
        $this->extraData = [
            'refund_id' => $refund->id,
            'order_number' => $orderNumber,
            'amount' => (float) $refund->amount,
            'reason' => $reason,
        ];
    }
}
