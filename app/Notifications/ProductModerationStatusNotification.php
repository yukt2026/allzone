<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;

class ProductModerationStatusNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Product $product,
        public readonly string $statusLabel,
        public readonly ?string $reason = null
    ) {
        $this->category = 'catalogue';
        $this->eventKey = 'seller_product_moderation';
        $this->referenceId = $product->id;
        $title = $product->title;

        $this->title = "Product {$statusLabel}: {$title}";
        $this->message = "Your product '{$title}' listing status is now '{$statusLabel}'." . ($reason ? " Reason/Feedback: {$reason}." : '');
        $this->actionUrl = route('seller.products.edit', $product->id);
        $this->actionText = 'Review Listing';
        $this->extraData = [
            'product_id' => $product->id,
            'title' => $title,
            'status' => $statusLabel,
            'reason' => $reason,
        ];
    }
}
