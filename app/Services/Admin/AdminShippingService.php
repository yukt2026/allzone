<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ShipmentStatus;
use App\Models\FulfilmentOrder;
use App\Models\NdrCase;
use App\Models\RtoCase;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\ShipmentWebhookLog;
use App\Models\User;
use App\Services\Cargo\ShiprocketClient;
use App\Services\Cargo\ShiprocketLogisticsProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AdminShippingService
{
    public function __construct(
        protected ShiprocketClient $client,
        protected ShiprocketLogisticsProvider $provider
    ) {}

    /**
     * Get real-time platform shipment metrics across all stages.
     *
     * @return array<string, mixed>
     */
    public function getShippingMetrics(): array
    {
        $creationPending = FulfilmentOrder::query()
            ->whereIn('status', ['confirmed', 'processing', 'packed', 'ready', 'ready_to_ship'])
            ->whereDoesntHave('shipments', function (Builder $sq) {
                $sq->whereNotNull('awb')
                   ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED]);
            })
            ->count();

        $delayed = Shipment::query()
            ->whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])
            ->whereHas('fulfilmentOrder', function (Builder $foq) {
                $foq->whereNotNull('estimated_delivery_at')
                    ->where('estimated_delivery_at', '<', now());
            })
            ->count();

        $failedDelivery = Shipment::query()
            ->where(function (Builder $q) {
                $q->whereIn('status', [ShipmentStatus::FAILED, ShipmentStatus::NDR_RAISED])
                  ->orWhereHas('ndrCases', fn(Builder $nq) => $nq->where('status', 'pending'));
            })
            ->count();

        $rto = Shipment::query()
            ->where(function (Builder $q) {
                $q->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                  ->orWhereHas('rtoCases');
            })
            ->count();

        return [
            'total_shipments' => Shipment::count(),
            'creation_pending' => $creationPending,
            'pickup_pending' => Shipment::where(function (Builder $q) {
                $q->where('status', ShipmentStatus::MANIFEST_CREATED)
                  ->orWhere('pickup_status', 'pending');
            })->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::DELIVERED])->count(),
            'picked_up' => Shipment::where('status', ShipmentStatus::PICKED_UP)->count(),
            'in_transit' => Shipment::whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])->count(),
            'delivered' => Shipment::where('status', ShipmentStatus::DELIVERED)->count(),
            'delayed' => $delayed,
            'failed_delivery' => $failedDelivery,
            'rto' => $rto,
            'cancelled' => Shipment::where('status', ShipmentStatus::CANCELLED)->count(),
            'total_charges' => (float) Shipment::sum('charges'),
        ];
    }

    /**
     * Get dynamic counter badges for shipment tabs.
     *
     * @return array<string, int>
     */
    public function getShipmentTabCounts(): array
    {
        $metrics = $this->getShippingMetrics();

        return [
            'all' => $metrics['total_shipments'],
            'creation_pending' => $metrics['creation_pending'],
            'pickup_pending' => $metrics['pickup_pending'],
            'picked_up' => $metrics['picked_up'],
            'in_transit' => $metrics['in_transit'],
            'delivered' => $metrics['delivered'],
            'delayed' => $metrics['delayed'],
            'failed_delivery' => $metrics['failed_delivery'],
            'rto' => $metrics['rto'],
            'cancelled' => $metrics['cancelled'],
        ];
    }

    /**
     * Build filtered query for shipments registry.
     *
     * @param array<string, mixed> $filters
     */
    public function buildShipmentsQuery(array $filters = []): Builder
    {
        $query = Shipment::query()
            ->with([
                'fulfilmentOrder.order.customer.user',
                'fulfilmentOrder.seller',
                'fulfilmentOrder.store',
                'tracking' => fn($q) => $q->latest('event_time')->limit(3),
                'rtoCases',
                'ndrCases',
            ]);

        // Status Tab
        $tab = $filters['tab'] ?? $filters['status'] ?? 'all';
        match ($tab) {
            'pickup_pending' => $query->where(function (Builder $q) {
                $q->where('status', ShipmentStatus::MANIFEST_CREATED)
                  ->orWhere('pickup_status', 'pending');
            })->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::DELIVERED]),
            'picked_up' => $query->where('status', ShipmentStatus::PICKED_UP),
            'in_transit' => $query->whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY]),
            'delivered' => $query->where('status', ShipmentStatus::DELIVERED),
            'delayed' => $query->whereIn('status', [ShipmentStatus::IN_TRANSIT, ShipmentStatus::OUT_FOR_DELIVERY])
                ->whereHas('fulfilmentOrder', fn(Builder $foq) => $foq->whereNotNull('estimated_delivery_at')->where('estimated_delivery_at', '<', now())),
            'failed_delivery' => $query->where(function (Builder $q) {
                $q->whereIn('status', [ShipmentStatus::FAILED, ShipmentStatus::NDR_RAISED])
                  ->orWhereHas('ndrCases', fn(Builder $nq) => $nq->where('status', 'pending'));
            }),
            'rto' => $query->where(function (Builder $q) {
                $q->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                  ->orWhereHas('rtoCases');
            }),
            'cancelled' => $query->where('status', ShipmentStatus::CANCELLED),
            default => null,
        };

        // Search
        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('awb', 'like', "%{$term}%")
                  ->orWhere('shipment_number', 'like', "%{$term}%")
                  ->orWhere('courier', 'like', "%{$term}%")
                  ->orWhereHas('fulfilmentOrder', function (Builder $foq) use ($term) {
                      $foq->where('fulfilment_order_number', 'like', "%{$term}%")
                          ->orWhereHas('order', function (Builder $oq) use ($term) {
                              $oq->where('order_number', 'like', "%{$term}%")
                                 ->orWhereHas('customer.user', function (Builder $uq) use ($term) {
                                     $uq->where('name', 'like', "%{$term}%")
                                        ->orWhere('email', 'like', "%{$term}%")
                                        ->orWhere('phone', 'like', "%{$term}%");
                                 });
                          });
                  });
            });
        }

        // Seller
        if (!empty($filters['seller_id'])) {
            $sellerId = (int) $filters['seller_id'];
            $query->whereHas('fulfilmentOrder', fn(Builder $foq) => $foq->where('seller_id', $sellerId));
        }

        // Courier
        if (!empty($filters['courier'])) {
            $query->where('courier', 'like', "%{$filters['courier']}%");
        }

        // Date range
        if (!empty($filters['date_range']) && $filters['date_range'] !== 'all') {
            match ($filters['date_range']) {
                'today' => $query->whereDate('created_at', today()),
                'yesterday' => $query->whereDate('created_at', today()->subDay()),
                'week' => $query->where('created_at', '>=', now()->subDays(7)),
                'month' => $query->where('created_at', '>=', now()->startOfMonth()),
                default => null,
            };
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->latest('id');
    }

    /**
     * Build query for fulfilment orders awaiting shipment creation.
     *
     * @param array<string, mixed> $filters
     */
    public function buildCreationPendingQuery(array $filters = []): Builder
    {
        $query = FulfilmentOrder::query()
            ->whereIn('status', ['confirmed', 'processing', 'packed', 'ready', 'ready_to_ship'])
            ->whereDoesntHave('shipments', function (Builder $sq) {
                $sq->whereNotNull('awb')
                   ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED]);
            })
            ->with([
                'order.customer.user',
                'order.customerAddress',
                'seller.defaultPickupLocation',
                'store',
                'items.variant',
            ]);

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('fulfilment_order_number', 'like', "%{$term}%")
                  ->orWhereHas('order', fn(Builder $oq) => $oq->where('order_number', 'like', "%{$term}%"));
            });
        }

        if (!empty($filters['seller_id'])) {
            $query->where('seller_id', (int) $filters['seller_id']);
        }

        return $query->latest('id');
    }

    /**
     * Admin create shipment consignment via Shiprocket for an eligible order package.
     * Enforces idempotency to avoid duplicate consignments.
     */
    public function createShipmentForOrder(FulfilmentOrder $fo, array $options = [], ?User $admin = null): array
    {
        // 1. Idempotency & Eligibility Check
        $activeShipment = Shipment::where('fulfilment_order_id', $fo->id)
            ->whereNotNull('awb')
            ->whereNotIn('status', [ShipmentStatus::CANCELLED, ShipmentStatus::FAILED])
            ->first();

        if ($activeShipment) {
            throw new RuntimeException("Consignment already exists for package #{$fo->fulfilment_order_number} with AWB {$activeShipment->awb}. Duplicate creation prevented.");
        }

        // 2. Delegate to Shiprocket logistics provider
        $result = $this->provider->createShipment($fo, $options);

        // 3. Activity logging
        if ($admin && function_exists('activity')) {
            activity('shipping')
                ->performedOn($fo)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'create_shipment',
                    'awb' => $result['awb'] ?? null,
                    'courier' => $result['shipment']->courier ?? null,
                ])
                ->log("Admin #{$admin->id} created Shiprocket shipment for package #{$fo->fulfilment_order_number}. AWB: " . ($result['awb'] ?? 'Pending'));
        }

        return $result;
    }

    /**
     * Live sync tracking milestone events from Shiprocket API for a shipment.
     */
    public function trackShipment(Shipment $shipment): array
    {
        return $this->provider->track($shipment);
    }

    /**
     * Retry failed shipment booking or AWB generation.
     */
    public function retryShipment(Shipment $shipment): array
    {
        if (!$shipment->awb) {
            $this->provider->generateAwb($shipment);
        }

        if ($shipment->status === ShipmentStatus::FAILED) {
            $shipment->status = ShipmentStatus::MANIFEST_CREATED;
            $shipment->save();
        }

        return [
            'success' => true,
            'shipment' => $shipment,
            'awb' => $shipment->awb,
        ];
    }

    /**
     * Cancel an active consignment in Shiprocket before courier pickup.
     */
    public function cancelShipment(Shipment $shipment, string $reason, ?User $admin = null): array
    {
        if ($shipment->status === ShipmentStatus::DELIVERED) {
            throw new RuntimeException("Delivered shipment #{$shipment->shipment_number} cannot be cancelled.");
        }

        if ($shipment->status === ShipmentStatus::CANCELLED) {
            return ['success' => true, 'message' => 'Shipment is already cancelled.'];
        }

        $result = $this->provider->cancelShipment($shipment, $reason);

        if ($admin && function_exists('activity')) {
            activity('shipping')
                ->performedOn($shipment)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'cancel_shipment',
                    'reason' => $reason,
                    'awb' => $shipment->awb,
                ])
                ->log("Admin #{$admin->id} cancelled Shiprocket consignment #{$shipment->shipment_number}. Reason: {$reason}");
        }

        return $result;
    }

    /**
     * Query Shiprocket API for live serviceability, available couriers, delivery SLAs, and rates.
     */
    public function checkServiceability(string $pickupPincode, string $deliveryPincode, float $weightKg = 0.5, bool $isCod = false): array
    {
        return $this->client->checkServiceability($pickupPincode, $deliveryPincode, $weightKg, $isCod);
    }

    /**
     * Retrieve recent webhook audit logs.
     *
     * @return LengthAwarePaginator
     */
    public function getWebhookLogs(int $perPage = 20): LengthAwarePaginator
    {
        return ShipmentWebhookLog::query()
            ->latest('id')
            ->paginate($perPage);
    }
}
