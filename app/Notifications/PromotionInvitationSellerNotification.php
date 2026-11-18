<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Campaign;

class PromotionInvitationSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Campaign $campaign
    ) {
        $this->category = 'promotions';
        $this->eventKey = 'seller_promotion_invitation';
        $this->referenceId = $campaign->id;
        $name = $campaign->name;
        $discount = $campaign->discount_value ? ($campaign->discount_type === 'percentage' ? "{$campaign->discount_value}% OFF" : "₹{$campaign->discount_value} OFF") : 'Featured Deals';

        $this->title = "Campaign Invitation: {$name}";
        $this->message = "You are invited to participate in the '{$name}' ({$discount}) promotion campaign. Opt-in eligible products to boost visibility!";
        $this->actionUrl = route('seller.promotions.index');
        $this->actionText = 'Join Campaign';
        $this->extraData = [
            'campaign_id' => $campaign->id,
            'campaign_name' => $name,
            'start_date' => $campaign->start_date?->toDateString(),
            'end_date' => $campaign->end_date?->toDateString(),
        ];
    }
}
