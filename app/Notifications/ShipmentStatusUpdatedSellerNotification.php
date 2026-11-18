<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Shipment;

class ShipmentStatusUpdatedSellerNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Shipment $shipment,
        public readonly string $statusLabel,
        public readonly ?string $notes = null
    ) {
        $this->category = 'shipments';
        $this->eventKey = 'seller_shipment_status';
        $this->referenceId = $shipment->id;
        $awb = $shipment->awb ?: ($shipment->awb_number ?: 'N/A');
        $foNumber = $shipment->fulfilmentOrder?->fulfilment_order_number ?? 'N/A';

        $this->title = "Shipment {$statusLabel}: AWB #{$awb}";
        $this->message = "Shipment for Package #{$foNumber} (AWB: {$awb}) status changed to '{$statusLabel}'." . ($notes ? " Details: {$notes}." : '');
        $this->actionUrl = route('seller.shipments.index');
        $this->actionText = 'View Logistics Hub';
        $this->extraData = [
            'shipment_id' => $shipment->id,
            'awb' => $awb,
            'status' => $statusLabel,
            'notes' => $notes,
        ];
    }
}
