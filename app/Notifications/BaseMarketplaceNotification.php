<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseMarketplaceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $category = 'orders';
    public string $eventKey = 'marketplace_alert';
    public string $title = 'ALLZONE Alert';
    public string $message = '';
    public ?string $actionUrl = null;
    public ?string $actionText = 'View Details';
    public string|int|null $referenceId = null;
    public array $extraData = [];

    /**
     * Determine the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        $allowed = ['database', 'mail'];

        if ($notifiable instanceof User) {
            $effective = $notifiable->getEffectiveNotificationChannels($this->category, $allowed);
            return !empty($effective) ? $effective : ['database'];
        }

        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $brandName = function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE';

        $mail = (new MailMessage)
            ->subject("{$brandName}: {$this->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action($this->actionText ?? 'View Details', $this->actionUrl);
        }

        return $mail->line("Thank you for choosing {$brandName} Marketplace.");
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'reference_id' => $this->referenceId !== null ? (string) $this->referenceId : null,
            'data' => $this->extraData,
        ];
    }
}
