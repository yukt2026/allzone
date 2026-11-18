<?php

declare(strict_types=1);

namespace App\Notifications;

class ShiprocketFailureAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly string $operation,
        public readonly string $errorMessage,
        public readonly ?string $awbOrRef = null
    ) {
        $this->category = 'logistics';
        $this->eventKey = 'admin_shiprocket_failure';
        $this->referenceId = $awbOrRef ?: time();

        $this->title = "⚠️ Shiprocket API Failure ({$operation})";
        $this->message = "Shiprocket logistics failure encountered during '{$operation}'" . ($awbOrRef ? " (Ref: {$awbOrRef})" : '') . ": {$errorMessage}. Please check carrier connection status.";
        $this->actionUrl = route('admin.shipping.index');
        $this->actionText = 'Open Shipping Diagnostics';
        $this->extraData = [
            'operation' => $operation,
            'error_message' => $errorMessage,
            'reference' => $awbOrRef,
        ];
    }
}
