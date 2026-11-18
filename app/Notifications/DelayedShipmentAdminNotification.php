<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Shipment;

class DelayedShipmentAdminNotification extends BaseMarketplaceNotification
{
    public function __construct(
        public readonly Shipment $shipment,
        public readonly int $delayedHours
    ) {
        $this->category = 'logistics';
        $this->eventKey = 'admin_delayed_shipment';
        $this->referenceId = $shipment->id;
        $awb = $shipment->awb ?: ($shipment->awb_number ?: 'N/A');
        $courier = $shipment->courier_name ?: 'Courier';
        $foNumber = $shipment->fulfilmentOrder?->fulfilment_order_number ?? 'N/A';

        $this->title = "SLA Breach: Delayed Shipment (AWB #{$awb})";
        $this->message = "Shipment for Package #{$foNumber} via {$courier} (AWB: {$awb}) has breached dispatch/transit SLA by {$delayedHours}+ hours. Expedited action required.";
        $this->actionUrl = route('admin.shipping.index');
        $this->actionText = 'View Logistics Monitor';
        $this->extraData = [
            'shipment_id' => $shipment->id,
            'awb' => $awb,
            'courier' => $courier,
            'delayed_hours' => $delayedHours,
        ];
    }
}
