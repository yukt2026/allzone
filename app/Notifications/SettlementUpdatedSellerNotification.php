<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerSettlement;

class SettlementUpdatedSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly SellerSettlement $settlement,
        public readonly string $statusLabel,
        public readonly ?string $utr = null
    ) {
        $this->category = 'settlements';
        $this->eventKey = 'seller_settlement_update';
        $this->referenceId = $settlement->id;
        $stNumber = $settlement->settlement_number;
        $net = $settlement->net ?? ($settlement->net_amount ?? 0);
        $amount = function_exists('format_currency') ? format_currency((float) $net) : '₹' . number_format((float) $net, 2);

        $this->title = "Payout Update: #{$stNumber} ({$statusLabel})";
        $this->message = "Payout settlement #{$stNumber} for {$amount} status updated to '{$statusLabel}'." . ($utr ? " Bank UTR: {$utr}." : '');
        $this->actionUrl = route('seller.settlements.show', $settlement->id);
        $this->actionText = 'View Disbursement';
        $this->extraData = [
            'settlement_id' => $settlement->id,
            'settlement_number' => $stNumber,
            'amount' => (float) $net,
            'status' => $statusLabel,
            'utr' => $utr,
        ];
    }
}
