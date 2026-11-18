<?php

declare(strict_types=1);

namespace App\Notifications;

class ReturnOrClaimRequestedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly string $requestType, // 'return' or 'claim'
        public readonly string $identifier,
        public readonly string $partyName,
        public readonly string $details,
        public readonly int $resourceId
    ) {
        $this->category = 'returns_claims';
        $this->eventKey = 'admin_return_or_claim';
        $this->referenceId = $resourceId;

        $typeLabel = $requestType === 'claim' ? 'Dispute Claim' : 'Return Request';
        $this->title = "New {$typeLabel}: #{$identifier}";
        $this->message = "{$partyName} submitted a {$typeLabel} (#{$identifier}). Summary: {$details}.";
        $this->actionUrl = $requestType === 'claim' ? route('admin.claims.index') : route('admin.returns.index');
        $this->actionText = "View {$typeLabel}";
        $this->extraData = [
            'request_type' => $requestType,
            'identifier' => $identifier,
            'party_name' => $partyName,
            'details' => $details,
            'resource_id' => $resourceId,
        ];
    }
}
