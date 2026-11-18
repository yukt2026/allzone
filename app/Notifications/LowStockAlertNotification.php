<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inventory;
use Illuminate\Notifications\Messages\MailMessage;

class LowStockAlertNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public Inventory $inventory
    ) {
        $variant = $this->inventory->variant;
        $product = $variant?->product;
        $locationName = $this->inventory->location?->name ?? 'Default Hub';
        $available = $this->inventory->available_quantity;
        $threshold = $this->inventory->low_stock_threshold;
        $sku = $variant?->sku ?? 'SKU';
        $title = $product?->title ?? 'Product';

        $this->category = 'inventory';
        $this->eventKey = 'seller_low_stock';
        $this->referenceId = $this->inventory->id;
        $this->title = "⚠️ Low Stock Alert: {$title} ({$sku})";
        $this->message = "Inventory for SKU '{$sku}' ({$title}) at {$locationName} has reached critical threshold: {$available} units remaining (Threshold: {$threshold}).";
        $this->actionUrl = route('seller.inventory.index');
        $this->actionText = 'Restock Inventory';
        $this->extraData = [
            'inventory_id' => $this->inventory->id,
            'product_id' => $product?->id,
            'variant_id' => $variant?->id,
            'sku' => $sku,
            'product_title' => $title,
            'location_name' => $locationName,
            'available_quantity' => $available,
            'threshold' => $threshold,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->inventory->variant?->product;
        $variant = $this->inventory->variant;
        $location = $this->inventory->location;
        $available = $this->inventory->available_quantity;
        $threshold = $this->inventory->low_stock_threshold;

        $businessName = $product?->seller?->business_name ?? $notifiable->name;
        $productTitle = $product?->title ?? 'Product';
        $sku = $variant?->sku ?? 'SKU';
        $locName = $location?->name ?? 'Default Hub';

        return (new MailMessage)
            ->subject("⚠️ Low Stock Alert: '{$productTitle}' ({$sku})")
            ->greeting("Attention {$businessName},")
            ->line("Inventory for **{$productTitle}** (SKU: `{$sku}`) at location **{$locName}** has reached critical threshold.")
            ->line("Current Available Units: **{$available}** (Alert Threshold: **{$threshold}**)")
            ->action('Restock Inventory in Portal', route('seller.inventory.index'))
            ->line('Please replenish inventory immediately to avoid order cancellations and stock-outs.');
    }
}
