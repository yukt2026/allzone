<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;

class ProductSubmittedAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Product $product
    ) {
        $this->category = 'catalogue';
        $this->eventKey = 'admin_product_submitted';
        $this->referenceId = $product->id;
        $title = $product->title;
        $sellerName = $product->seller?->business_name ?? 'Merchant';

        $this->title = "New Product Submitted: {$title}";
        $this->message = "Merchant '{$sellerName}' submitted a new product listing '{$title}' for quality check (QC) and catalog approval.";
        $this->actionUrl = route('admin.catalogue.moderation.show', $product->id);
        $this->actionText = 'Moderate Product';
        $this->extraData = [
            'product_id' => $product->id,
            'title' => $title,
            'seller_id' => $product->seller_id,
            'seller_name' => $sellerName,
        ];
    }
}
