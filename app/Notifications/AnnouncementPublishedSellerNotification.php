<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;

class AnnouncementPublishedSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Announcement $announcement
    ) {
        $this->category = 'announcements';
        $this->eventKey = 'seller_announcement';
        $this->referenceId = $announcement->id;
        $title = $announcement->title;
        $priority = strtoupper($announcement->priority);

        $this->title = "📢 [{$priority}] {$title}";
        $this->message = $announcement->summary ?: substr(strip_tags($announcement->content), 0, 180) . '...';
        $this->actionUrl = route('seller.notices.show', $announcement->slug);
        $this->actionText = 'Read Announcement';
        $this->extraData = [
            'announcement_id' => $announcement->id,
            'slug' => $announcement->slug,
            'priority' => $announcement->priority,
            'category' => $announcement->category,
        ];
    }
}
