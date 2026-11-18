<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Seller;

class SellerApprovalRequestedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Seller $seller
    ) {
        $this->category = 'sellers';
        $this->eventKey = 'admin_seller_approval_request';
        $this->referenceId = $seller->id;
        $name = $seller->business_name;

        $this->title = "Verification Pending: {$name}";
        $this->message = "Merchant '{$name}' has submitted documentation and requested store onboarding verification.";
        $this->actionUrl = route('admin.sellers.show', $seller->id);
        $this->actionText = 'Review & Verify Seller';
        $this->extraData = [
            'seller_id' => $seller->id,
            'business_name' => $name,
            'gstin' => $seller->gstin,
            'pan_number' => $seller->pan_number,
        ];
    }
}
