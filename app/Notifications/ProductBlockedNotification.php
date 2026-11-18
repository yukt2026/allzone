<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductBlockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Notice: Product '{$this->product->title}' has been Blocked")
            ->greeting("Hello {$this->product->seller->business_name},")
            ->line("The product listing **'{$this->product->title}'** has been blocked by ALLZONE Marketplace Administration.")
            ->line('Policy violation reason:')
            ->line("> **{$this->reason}**")
            ->line('Blocked listings are permanently removed from the storefront. If you believe this is an error, you may file an appeal through the Seller Support Desk.')
            ->action('View Seller Portal', route('seller.dashboard'))
            ->line('Thank you for adhering to ALLZONE Marketplace compliance policies.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_blocked',
            'product_id' => $this->product->id,
            'title' => $this->product->title,
            'reason' => $this->reason,
            'message' => "Product '{$this->product->title}' blocked: {$this->reason}",
        ];
    }
}
