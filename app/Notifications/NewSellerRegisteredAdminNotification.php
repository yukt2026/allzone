<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Seller;

class NewSellerRegisteredAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Seller $seller
    ) {
        $this->category = 'sellers';
        $this->eventKey = 'admin_new_seller';
        $this->referenceId = $seller->id;
        $name = $seller->business_name;
        $owner = $seller->user?->name ?? 'Merchant';

        $this->title = "New Merchant Registered: {$name}";
        $this->message = "A new seller account for '{$name}' (Owner: {$owner}, Email: {$seller->user?->email}) has registered on ALLZONE.";
        $this->actionUrl = route('admin.sellers.show', $seller->id);
        $this->actionText = 'View Seller Profile';
        $this->extraData = [
            'seller_id' => $seller->id,
            'business_name' => $name,
            'owner_name' => $owner,
            'email' => $seller->user?->email,
        ];
    }
}
