<?php

declare(strict_types=1);

namespace App\Notifications;

class SecurityOrInventoryAlertAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly string $alertType, // 'security' or 'inventory'
        public readonly string $heading,
        public readonly string $details,
        public readonly ?string $targetUrl = null,
        public readonly array $context = []
    ) {
        $this->category = $alertType === 'inventory' ? 'inventory' : 'security';
        $this->eventKey = "admin_{$alertType}_alert";
        $this->referenceId = $context['reference_id'] ?? time();

        $prefix = $alertType === 'security' ? '🔒 Security Incident' : '📦 Critical Inventory Alert';
        $this->title = "{$prefix}: {$heading}";
        $this->message = $details;
        $this->actionUrl = $targetUrl ?: ($alertType === 'security' ? route('admin.security.index') : route('admin.inventory.index'));
        $this->actionText = $alertType === 'security' ? 'Review Security Center' : 'Review Stock Ledger';
        $this->extraData = array_merge([
            'alert_type' => $alertType,
            'heading' => $heading,
            'details' => $details,
        ], $context);
    }
}
