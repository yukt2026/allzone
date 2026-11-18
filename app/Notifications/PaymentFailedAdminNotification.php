<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;

class PaymentFailedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Payment $payment,
        public readonly string $reason
    ) {
        $this->category = 'finance';
        $this->eventKey = 'admin_payment_failed';
        $this->referenceId = $payment->id;
        $orderNumber = $payment->order?->order_number ?? 'N/A';
        $amount = function_exists('format_currency') ? format_currency((float) $payment->amount) : '₹' . number_format((float) $payment->amount, 2);
        $gateway = strtoupper((string) $payment->gateway);

        $this->title = "⚠️ Payment Failure: Order #{$orderNumber}";
        $this->message = "Online payment attempt via {$gateway} for Order #{$orderNumber} ({$amount}) failed. Reason: {$reason}. Payment marked unreconciled.";
        $this->actionUrl = route('admin.payments.show', $payment->id);
        $this->actionText = 'Inspect Payment Record';
        $this->extraData = [
            'payment_id' => $payment->id,
            'order_number' => $orderNumber,
            'gateway' => $gateway,
            'amount' => (float) $payment->amount,
            'reason' => $reason,
        ];
    }
}
