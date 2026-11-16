<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\CODRemittanceStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\CodRemittance;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\NdrCase;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\RtoCase;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Services\Cargo\ShiprocketClient;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderStateMachine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    public function __construct(
        protected ShiprocketClient $shiprocketClient,
        protected OrderStateMachine $stateMachine,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Handle incoming webhook notifications from Shiprocket multi-carrier network.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $rawContent = $request->getContent();

        Log::info('Shiprocket webhook received', ['payload' => $payload]);

        $awb = $request->input('awb') ?? $request->input('awb_code') ?? $request->input('data.awb');
        $orderNumber = $request->input('order_id') ?? $request->input('data.order_id');
        $currentStatus = strtoupper(trim((string) ($request->input('current_status') ?? $request->input('status') ?? '')));
        $statusCode = (int) ($request->input('current_status_id') ?? $request->input('status_id') ?? 0);
        $location = (string) ($request->input('current_city') ?? $request->input('location') ?? 'In Transit Hub');
        $timestamp = !empty($request->input('current_timestamp')) ? Carbon::parse($request->input('current_timestamp')) : now();

        $shipment = Shipment::query()
            ->when($awb, fn($q) => $q->where('awb', $awb))
            ->when(!$awb && $orderNumber, function ($q) use ($orderNumber) {
                $q->whereHas('fulfilmentOrder', fn($foq) => $foq->where('fulfilment_order_number', $orderNumber));
            })
            ->with(['fulfilmentOrder.items.variant', 'fulfilmentOrder.order'])
            ->first();

        if (!$shipment) {
            Log::info("Shiprocket webhook: no matching shipment found for AWB: {$awb} / Ref: {$orderNumber}");
            return response()->json([
                'success' => true,
                'message' => 'Webhook received, but no matching shipment found locally.',
            ]);
        }

        DB::transaction(function () use ($shipment, $currentStatus, $statusCode, $location, $timestamp, $payload) {
            $fo = $shipment->fulfilmentOrder;
            $order = $fo?->order;

            // 1. Record milestone event in tracking log
            ShipmentTracking::create([
                'shipment_id' => $shipment->id,
                'status' => strtolower(str_replace(' ', '_', $currentStatus ?: "status_{$statusCode}")),
                'location' => $location,
                'event_time' => $timestamp,
                'raw' => $payload,
            ]);

            // 2. Map Shiprocket status to ALLZONE shipment & order status
            if ($statusCode === 19 || str_contains($currentStatus, 'PICKED UP')) {
                $shipment->status = ShipmentStatus::PICKED_UP;
                $shipment->pickup_status = 'completed';
                $shipment->save();

                if ($fo && strtolower((string) $fo->status) !== 'delivered') {
                    $this->advanceFulfilmentOrder($fo, 'picked_up', 'Picked up by Shiprocket carrier');
                }
            } elseif ($statusCode === 6 || str_contains($currentStatus, 'IN TRANSIT') || str_contains($currentStatus, 'SHIPPED')) {
                $shipment->status = ShipmentStatus::IN_TRANSIT;
                $shipment->save();

                if ($fo && strtolower((string) $fo->status) !== 'delivered') {
                    $this->advanceFulfilmentOrder($fo, 'in_transit', 'In transit with Shiprocket network');
                }
            } elseif ($statusCode === 17 || str_contains($currentStatus, 'OUT FOR DELIVERY')) {
                $shipment->status = ShipmentStatus::OUT_FOR_DELIVERY;
                $shipment->save();

                if ($fo && strtolower((string) $fo->status) !== 'delivered') {
                    $this->advanceFulfilmentOrder($fo, 'out_for_delivery', 'Shipment out for delivery to customer');
                }
            } elseif ($statusCode === 7 || str_contains($currentStatus, 'DELIVERED')) {
                $shipment->status = ShipmentStatus::DELIVERED;
                $shipment->save();

                if ($fo && strtolower((string) $fo->status) !== 'delivered') {
                    $this->advanceFulfilmentOrder($fo, 'delivered', 'Delivered successfully by Shiprocket');
                }

                // Handle COD Collection
                $isCod = ($order?->payment_method === PaymentMethod::COD)
                    || ($order?->payment_method?->value === 'cod')
                    || ($shipment->cod_amount > 0);

                if ($isCod && $fo) {
                    CodRemittance::firstOrCreate(
                        ['shipment_id' => $shipment->id],
                        [
                            'seller_id' => $fo->seller_id,
                            'amount' => $shipment->cod_amount > 0 ? (float) $shipment->cod_amount : (float) ($fo->total ?? $fo->total_amount),
                            'status' => CODRemittanceStatus::COLLECTED,
                            'collected_at' => $timestamp,
                            'reference' => 'SR-COD-' . ($shipment->awb ?: $shipment->id),
                        ]
                    );
                }
            } elseif ($statusCode === 42 || str_contains($currentStatus, 'UNDELIVERED') || str_contains($currentStatus, 'NDR')) {
                $shipment->status = ShipmentStatus::NDR_RAISED;
                $shipment->save();

                NdrCase::updateOrCreate(
                    ['shipment_id' => $shipment->id],
                    [
                        'reason' => $payload['reason'] ?? 'Customer unavailable / delivery attempted',
                        'attempts' => (int) ($payload['attempt'] ?? 1),
                        'status' => 'pending',
                    ]
                );
            } elseif ($statusCode === 9 || str_contains($currentStatus, 'RTO INITIATED') || str_contains($currentStatus, 'RTO')) {
                $shipment->status = ShipmentStatus::RTO_INITIATED;
                $shipment->save();

                RtoCase::firstOrCreate(
                    ['shipment_id' => $shipment->id],
                    [
                        'reason' => $payload['reason'] ?? 'Shiprocket delivery failed; RTO initiated',
                        'status' => 'initiated',
                        'initiated_at' => $timestamp,
                    ]
                );
            } elseif ($statusCode === 10 || str_contains($currentStatus, 'RTO DELIVERED')) {
                $shipment->status = ShipmentStatus::RTO_DELIVERED;
                $shipment->save();

                $rto = RtoCase::where('shipment_id', $shipment->id)->first();
                if ($rto) {
                    $rto->status = 'delivered_to_origin';
                    $rto->delivered_at = $timestamp;
                    $rto->save();
                }

                // Restock returned inventory
                if ($fo) {
                    foreach ($fo->items as $item) {
                        if ($item->variant_id) {
                            $inv = Inventory::where('variant_id', $item->variant_id)
                                ->where('location_id', $fo->inventory_location_id ?? 1)
                                ->first()
                                ?? Inventory::where('variant_id', $item->variant_id)->first();

                            if ($inv) {
                                $this->inventoryService->restock(
                                    $inv,
                                    (int) $item->qty,
                                    "Shiprocket RTO return for package #{$fo->fulfilment_order_number}"
                                );
                            }
                        }
                    }
                }
            } elseif ($statusCode === 8 || str_contains($currentStatus, 'CANCELED') || str_contains($currentStatus, 'CANCELLED')) {
                $shipment->status = ShipmentStatus::CANCELLED;
                $shipment->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Shiprocket tracking webhook processed successfully.',
        ]);
    }

    /**
     * Advance fulfilment order and parent order status.
     */
    protected function advanceFulfilmentOrder(FulfilmentOrder $fo, string $step, string $note): void
    {
        $statusEnum = match ($step) {
            'picked_up' => \App\Enums\OrderStatus::PROCESSING,
            'in_transit' => \App\Enums\OrderStatus::PROCESSING,
            'out_for_delivery' => \App\Enums\OrderStatus::OUT_FOR_DELIVERY,
            'delivered' => \App\Enums\OrderStatus::DELIVERED,
            default => null,
        };

        if ($statusEnum) {
            $fo->status = $statusEnum;
            $fo->saveQuietly();

            OrderStatusHistory::create([
                'orderable_type' => FulfilmentOrder::class,
                'orderable_id' => $fo->id,
                'from_status' => 'logistics_update',
                'to_status' => $statusEnum->value,
                'note' => $note,
                'changed_by' => null,
            ]);

            // If all packages delivered, mark parent order delivered
            if ($statusEnum === \App\Enums\OrderStatus::DELIVERED) {
                $order = $fo->order;
                if ($order) {
                    $allDelivered = $order->fulfilmentOrders()
                        ->where('status', '!=', \App\Enums\OrderStatus::DELIVERED)
                        ->doesntExist();

                    if ($allDelivered) {
                        $order->status = \App\Enums\OrderStatus::DELIVERED;

                        $methodStr = strtolower((string) ($order->payment_method?->value ?? $order->payment_method));
                        $pStatusStr = strtolower((string) ($order->payment_status?->value ?? $order->payment_status));

                        if (in_array($methodStr, ['cod', 'cash_on_delivery'], true) && in_array($pStatusStr, ['pending', 'cod_pending'], true)) {
                            $order->payment_status = \App\Enums\PaymentStatus::PAID;

                            $existingPayment = $order->payments()->first();
                            if ($existingPayment) {
                                $existingPayment->status = \App\Enums\PaymentStatus::PAID;
                                $existingPayment->verified_at = now();
                                $existingPayment->save();
                            } else {
                                $order->payments()->create([
                                    'gateway' => 'cod',
                                    'method' => 'cod',
                                    'amount' => $order->grand_total,
                                    'currency' => 'INR',
                                    'status' => \App\Enums\PaymentStatus::PAID,
                                    'verified_at' => now(),
                                    'gateway_payment_id' => 'COD-COLLECTED-' . $order->order_number,
                                ]);
                            }
                        }

                        $order->save();

                        OrderStatusHistory::create([
                            'orderable_type' => Order::class,
                            'orderable_id' => $order->id,
                            'from_status' => 'processing',
                            'to_status' => \App\Enums\OrderStatus::DELIVERED->value,
                            'note' => 'All consignments delivered successfully to customer.',
                            'changed_by' => null,
                        ]);
                    }
                }
            }
        }
    }
}
