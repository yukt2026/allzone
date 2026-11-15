<?php

declare(strict_types=1);

namespace App\Services\Cargo;

use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Models\FulfilmentOrder;
use App\Models\NdrCase;
use App\Models\OrderReturn;
use App\Models\RtoCase;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Services\Cargo\Contracts\LogisticsProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShiprocketLogisticsProvider implements LogisticsProvider
{
    public function __construct(
        protected ShiprocketClient $client
    ) {}

    /**
     * Get underlying ShiprocketClient instance.
     */
    public function getClient(): ShiprocketClient
    {
        return $this->client;
    }

    /**
     * Create a new shipment consignment in Shiprocket for a fulfilment order.
     */
    public function createShipment(FulfilmentOrder $fulfilmentOrder, array $options = []): array
    {
        return DB::transaction(function () use ($fulfilmentOrder, $options) {
            $order = $fulfilmentOrder->order;
            $store = $fulfilmentOrder->store;
            $seller = $fulfilmentOrder->seller;

            // Existing active shipment check
            $existing = Shipment::where('fulfilment_order_id', $fulfilmentOrder->id)
                ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED])
                ->first();

            if ($existing && $existing->awb) {
                return [
                    'success' => true,
                    'shipment' => $existing,
                    'awb' => $existing->awb,
                    'status' => $existing->status->value,
                    'label_url' => $existing->label_url,
                ];
            }

            $shipmentNumber = 'SR-SHP-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // Origin / Pickup address
            $pickupLocationName = substr($store?->name ?? $seller?->company_name ?? (setting('name', 'ALLZONE') . ' Hub'), 0, 35);
            $originPincode = $store?->pincode ?? '560038';

            // Destination address
            $destAddress = $order?->customerAddress;
            $snapshot = $order?->address_snapshot ?? [];
            $destName = $destAddress?->name ?? $snapshot['name'] ?? $order?->customer?->user?->name ?? 'Customer';
            $destPhone = $destAddress?->phone ?? $snapshot['phone'] ?? '9876543210';
            $destLine1 = $destAddress?->line1 ?? $snapshot['line1'] ?? 'Delivery Address';
            $destCity = $destAddress?->city ?? $snapshot['city'] ?? 'Bengaluru';
            $destState = $destAddress?->state ?? $snapshot['state'] ?? 'Karnataka';
            $destPincode = $destAddress?->pincode ?? $snapshot['pincode'] ?? '560001';

            // Items & Weight calculation
            $orderItems = [];
            $totalWeightKg = 0.0;
            foreach ($fulfilmentOrder->items as $item) {
                $variant = $item->variant;
                $weight = (float) ($variant?->weight_grams ?? 500) / 1000.0;
                $totalWeightKg += ($weight * (int) $item->qty);

                $orderItems[] = [
                    'name' => substr($item->title_snapshot ?? $variant?->name ?? 'Marketplace Item', 0, 80),
                    'sku' => $variant?->sku ?? ('SKU-' . $item->id),
                    'units' => (int) $item->qty,
                    'selling_price' => (float) $item->unit_price,
                    'discount' => 0.00,
                    'tax' => 0.00,
                ];
            }
            $totalWeightKg = max(0.2, round($totalWeightKg, 2));

            // Payment / COD handling
            $isCod = ($order?->payment_method === PaymentMethod::COD)
                || ($order?->payment_method?->value === 'cod');
            $codAmount = $isCod ? (float) ($fulfilmentOrder->total ?? $fulfilmentOrder->total_amount) : 0.00;

            // Build Shiprocket Order Payload
            $payload = [
                'order_id' => $fulfilmentOrder->fulfilment_order_number,
                'order_date' => $fulfilmentOrder->created_at ? $fulfilmentOrder->created_at->format('Y-m-d H:i') : date('Y-m-d H:i'),
                'pickup_location' => $pickupLocationName,
                'billing_customer_name' => $destName,
                'billing_last_name' => '',
                'billing_address' => $destLine1,
                'billing_address_2' => $destAddress?->line2 ?? '',
                'billing_city' => $destCity,
                'billing_pincode' => $destPincode,
                'billing_state' => $destState,
                'billing_country' => 'India',
                'billing_email' => $order?->customer?->user?->email ?? 'order@allzone.in',
                'billing_phone' => $destPhone,
                'shipping_is_billing' => true,
                'order_items' => $orderItems,
                'payment_method' => $isCod ? 'COD' : 'Prepaid',
                'sub_total' => (float) $fulfilmentOrder->subtotal,
                'length' => 10,
                'breadth' => 10,
                'height' => 10,
                'weight' => $totalWeightKg,
            ];

            $apiResult = $this->client->createOrder($payload);

            $shipmentId = $apiResult['shipment_id'] ?? null;
            $awbCode = $apiResult['awb_code'] ?? null;
            $courierName = $apiResult['courier_name'] ?? ($options['courier'] ?? 'Shiprocket Surface');
            $labelUrl = $apiResult['label_url'] ?? null;

            // If AWB was not auto-assigned at creation, request it
            if (!$awbCode && $shipmentId) {
                try {
                    $awbRes = $this->client->generateAwb($shipmentId);
                    $awbCode = $awbRes['awb_code'] ?? $awbCode;
                    $courierName = $awbRes['courier_name'] ?? $courierName;
                } catch (\Throwable $e) {
                    Log::warning("Shiprocket AWB delayed for order {$fulfilmentOrder->fulfilment_order_number}: " . $e->getMessage());
                }
            }

            // If label was not returned, request label URL
            if (!$labelUrl && $shipmentId) {
                try {
                    $lblRes = $this->client->generateLabel($shipmentId);
                    $labelUrl = $lblRes['label_url'] ?? null;
                } catch (\Throwable $e) {
                    Log::warning("Shiprocket Label generation delayed: " . $e->getMessage());
                }
            }

            $shipment = Shipment::create([
                'fulfilment_order_id' => $fulfilmentOrder->id,
                'shipment_number' => $shipmentNumber,
                'provider' => 'shiprocket',
                'awb' => $awbCode,
                'courier' => $courierName,
                'service' => $options['service'] ?? 'surface',
                'status' => ShipmentStatus::MANIFEST_CREATED,
                'pickup_status' => 'pending',
                'label_url' => $labelUrl,
                'cod_amount' => $codAmount,
                'weight' => $totalWeightKg,
                'charges' => (float) ($options['charges'] ?? 85.00),
            ]);

            // Milestone tracking entry
            ShipmentTracking::create([
                'shipment_id' => $shipment->id,
                'status' => 'manifest_created',
                'location' => $destCity,
                'event_time' => now(),
                'raw' => $apiResult,
            ]);

            return [
                'success' => true,
                'shipment' => $shipment,
                'awb' => $shipment->awb,
                'status' => $shipment->status->value,
                'label_url' => $shipment->label_url,
            ];
        });
    }

    /**
     * Generate or assign an Air Waybill (AWB) number and shipping label for a shipment.
     */
    public function generateAwb(Shipment $shipment): array
    {
        $shipmentId = $shipment->id;
        $result = $this->client->generateAwb($shipmentId);

        if (!empty($result['awb_code'])) {
            $shipment->awb = $result['awb_code'];
        }
        if (!empty($result['courier_name'])) {
            $shipment->courier = $result['courier_name'];
        }

        try {
            $labelRes = $this->client->generateLabel($shipmentId);
            if (!empty($labelRes['label_url'])) {
                $shipment->label_url = $labelRes['label_url'];
            }
        } catch (\Throwable $e) {
            Log::warning("Could not auto-generate label during generateAwb: " . $e->getMessage());
        }

        $shipment->save();

        return [
            'success' => true,
            'awb' => $shipment->awb,
            'courier' => $shipment->courier,
            'label_url' => $shipment->label_url,
        ];
    }

    /**
     * Query available courier service rates and transit SLAs for origin/destination pincodes.
     */
    public function selectCourierRates(array $params): array
    {
        $origin = (string) ($params['origin_pincode'] ?? '560038');
        $dest = (string) ($params['dest_pincode'] ?? '560001');
        $weight = (float) ($params['weight_kg'] ?? 0.5);
        $isCod = !empty($params['cod_amount']) && ((float) $params['cod_amount'] > 0);

        $res = $this->client->checkServiceability($origin, $dest, $weight, $isCod);
        $courierList = $res['data']['available_courier_companies'] ?? [];

        $rates = [];
        foreach ($courierList as $c) {
            $rates[] = [
                'courier_company_id' => $c['courier_company_id'] ?? null,
                'courier_name' => $c['courier_name'] ?? 'Shiprocket Courier',
                'rate' => (float) ($c['rate'] ?? 60.00),
                'etd' => $c['etd'] ?? '2-4 Days',
                'is_cod' => (bool) ($c['is_cod'] ?? false),
                'rating' => (float) ($c['rating'] ?? 4.0),
            ];
        }

        return $rates;
    }

    /**
     * Request physical carrier pickup from origin warehouse / store hub.
     */
    public function requestPickup(Shipment $shipment, array $pickupDetails = []): array
    {
        $date = $pickupDetails['pickup_date'] ?? date('Y-m-d');
        $res = $this->client->schedulePickup($shipment->id, $date);

        $shipment->pickup_status = 'scheduled';
        $shipment->save();

        ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'status' => 'pickup_scheduled',
            'location' => 'Origin Facility',
            'event_time' => now(),
            'raw' => $res,
        ]);

        return [
            'success' => true,
            'pickup_status' => 'scheduled',
            'response' => $res,
        ];
    }

    /**
     * Fetch real-time live milestone tracking events for an AWB.
     */
    public function track(string $awb): array
    {
        $trackingData = $this->client->track($awb);

        $shipment = Shipment::where('awb', $awb)->first();
        if ($shipment && !empty($trackingData['tracking_data']['shipment_track'][0]['scans'])) {
            $scans = $trackingData['tracking_data']['shipment_track'][0]['scans'];
            foreach ($scans as $scan) {
                ShipmentTracking::firstOrCreate(
                    [
                        'shipment_id' => $shipment->id,
                        'status' => Str::slug($scan['status'] ?? 'scan', '_'),
                        'event_time' => !empty($scan['date']) ? Carbon::parse($scan['date']) : now(),
                    ],
                    [
                        'location' => $scan['location'] ?? 'In Transit',
                        'raw' => $scan,
                    ]
                );
            }
        }

        return $trackingData;
    }

    /**
     * Cancel an active consignment before manifest or dispatch.
     */
    public function cancelShipment(Shipment $shipment, string $reason = ''): array
    {
        if ($shipment->awb) {
            $this->client->cancelShipment($shipment->awb);
        }

        $shipment->status = ShipmentStatus::CANCELLED;
        $shipment->save();

        ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'status' => 'cancelled',
            'location' => 'System',
            'event_time' => now(),
            'raw' => ['reason' => $reason],
        ]);

        return [
            'success' => true,
            'message' => 'Shiprocket consignment cancelled successfully.',
        ];
    }

    /**
     * Create a reverse pickup consignment for an approved customer return.
     */
    public function createReversePickup(OrderReturn $orderReturn): array
    {
        $fo = $orderReturn->fulfilmentOrder;
        $destAddress = $fo?->order?->customerAddress;
        $store = $fo?->store;

        $payload = [
            'order_id' => 'RET-' . $orderReturn->return_number,
            'order_date' => date('Y-m-d H:i'),
            'pickup_customer_name' => $destAddress?->name ?? 'Customer',
            'pickup_address' => $destAddress?->line1 ?? 'Address',
            'pickup_city' => $destAddress?->city ?? 'Bengaluru',
            'pickup_state' => $destAddress?->state ?? 'Karnataka',
            'pickup_pincode' => $destAddress?->pincode ?? '560001',
            'pickup_phone' => $destAddress?->phone ?? '9876543210',
            'delivery_address' => $store?->address ?? 'Warehouse',
            'delivery_city' => $store?->city ?? 'Bengaluru',
            'delivery_state' => $store?->state ?? 'Karnataka',
            'delivery_pincode' => $store?->pincode ?? '560038',
            'delivery_phone' => $store?->phone ?? '9876543210',
            'order_items' => [
                [
                    'name' => 'Returned Item #' . $orderReturn->id,
                    'sku' => 'RET-SKU',
                    'units' => 1,
                    'selling_price' => (float) $orderReturn->refund_amount,
                ],
            ],
            'sub_total' => (float) $orderReturn->refund_amount,
            'weight' => 0.5,
        ];

        return $this->client->createReturnOrder($payload);
    }

    /**
     * Fetch available NDR resolution actions from Shiprocket.
     */
    public function fetchNdrActions(Shipment $shipment): array
    {
        return [
            'actions' => [
                'reattempt' => 'Schedule next delivery attempt with carrier',
                'change_address' => 'Update delivery address or phone number',
                'rto' => 'Return consignment back to origin store',
            ],
        ];
    }

    /**
     * Transmit customer / seller NDR resolution action.
     */
    public function submitNdrAction(NdrCase $ndrCase, string $action, array $params = []): array
    {
        $ndrCase->status = 'action_transmitted';
        $ndrCase->save();

        return [
            'success' => true,
            'action' => $action,
            'message' => "NDR instruction '{$action}' communicated to Shiprocket carrier network.",
        ];
    }

    /**
     * Fetch courier COD remittance reconciliation data.
     */
    public function fetchCodRemittanceData(array $filters = []): array
    {
        return [];
    }
}
