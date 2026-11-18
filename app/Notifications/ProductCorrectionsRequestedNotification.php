<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductCorrectionsRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public string $correctionNotes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Corrections Requested: '{$this->product->title}'")
            ->greeting("Hello {$this->product->seller->business_name},")
            ->line("Our catalogue QC team reviewed your product listing **'{$this->product->title}'** and requested adjustments.")
            ->line('Required adjustments & guidance:')
            ->line("> **{$this->correctionNotes}**")
            ->line('Please update your listing details or images accordingly and re-submit for quick approval.')
            ->action('Edit Product Listing', route('seller.dashboard'))
            ->line('Thank you for ensuring listing quality on ALLZONE.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'product_corrections_requested',
            'product_id' => $this->product->id,
            'title' => $this->product->title,
            'notes' => $this->correctionNotes,
            'message' => "Corrections requested for '{$this->product->title}': {$this->correctionNotes}",
        ];
    }
}
