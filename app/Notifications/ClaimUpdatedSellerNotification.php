<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SellerClaim;

class ClaimUpdatedSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly SellerClaim $claim,
        public readonly string $statusLabel,
        public readonly ?string $notes = null
    ) {
        $this->category = 'returns_claims';
        $this->eventKey = 'seller_claim_update';
        $this->referenceId = $claim->id;
        $claimNumber = $claim->claim_number;

        $this->title = "Claim Update: #{$claimNumber} ({$statusLabel})";
        $this->message = "Your claim #{$claimNumber} status has been updated to '{$statusLabel}'." . ($notes ? " Admin Note: {$notes}." : '');
        $this->actionUrl = route('seller.claims.show', $claim->id);
        $this->actionText = 'View Claim Details';
        $this->extraData = [
            'claim_id' => $claim->id,
            'claim_number' => $claimNumber,
            'status' => $statusLabel,
            'notes' => $notes,
            'approved_amount' => (float) ($claim->approved_compensation_amount ?? 0),
        ];
    }
}
