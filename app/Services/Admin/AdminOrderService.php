<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Order\OrderStateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOrderService
{
    public function __construct(
        protected OrderStateMachine $stateMachine
    ) {}

    /**
     * Build filtered order query supporting all 10 tabs, searches, and multi-vendor criteria.
     *
     * @param array<string, mixed> $filters
     */
    public function buildOrdersQuery(array $filters = []): Builder
    {
        $query = Order::query()
            ->with([
                'customer.user',
                'fulfilmentOrders.store',
                'fulfilmentOrders.seller.user',
                'fulfilmentOrders.items.variant',
                'fulfilmentOrders.shipments.tracking',
                'fulfilmentOrders.items.returns',
                'payments',
            ])
            ->latest('id');

        // 1. Status Tabs Filter (10 Tabs)
        $tab = $filters['status'] ?? ($filters['tab'] ?? 'all');
        $this->applyTabFilter($query, (string) $tab);

        // 2. Search Filter
        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhere('id', $term)
                    ->orWhere('address_snapshot', 'like', "%{$term}%")
                    ->orWhereHas('customer.user', function (Builder $uq) use ($term) {
                        $uq->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%");
                    })
                    ->orWhereHas('fulfilmentOrders', function (Builder $foq) use ($term) {
                        $foq->where('fulfilment_order_number', 'like', "%{$term}%")
                            ->orWhereHas('shipments', function (Builder $sq) use ($term) {
                                $sq->where('awb', 'like', "%{$term}%")
                                    ->orWhere('courier', 'like', "%{$term}%");
                            })
                            ->orWhereHas('items', function (Builder $iq) use ($term) {
                                $iq->where('title', 'like', "%{$term}%")
                                    ->orWhere('sku', 'like', "%{$term}%");
                            });
                    });
            });
        }

        // 3. Seller Filter
        if (!empty($filters['seller_id'])) {
            $sellerId = (int) $filters['seller_id'];
            $query->whereHas('fulfilmentOrders', function (Builder $foq) use ($sellerId) {
                $foq->where('seller_id', $sellerId);
            });
        }

        // 4. Customer Filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        // 5. Payment Status Filter
        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        // 6. Payment Method Filter
        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->where('payment_method', $filters['payment_method']);
        }

        // 7. Fulfilment Type Filter (Quick Commerce vs Standard)
        if (!empty($filters['fulfilment_type']) && $filters['fulfilment_type'] !== 'all') {
            $ft = $filters['fulfilment_type'];
            $query->whereHas('fulfilmentOrders', function (Builder $foq) use ($ft) {
                $foq->where('fulfilment_type', $ft);
            });
        }

        // 8. Date Range Filter
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

        // 9. Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'total_desc' => $query->orderBy('total', 'desc'),
            'total_asc' => $query->orderBy('total', 'asc'),
            default => $query->latest('id'),
        };

        return $query;
    }

    /**
     * Apply tab-specific status and relationship filters to query.
     */
    protected function applyTabFilter(Builder $query, string $tab): void
    {
        match ($tab) {
            'pending' => $query->whereIn('status', [OrderStatus::PENDING, OrderStatus::PLACED]),
            'processing' => $query->whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PROCESSING]),
            'ready_to_ship' => $query->where(function (Builder $sub) {
                $sub->where('status', OrderStatus::READY_FOR_PICKUP)
                    ->orWhereHas('fulfilmentOrders', fn(Builder $foq) => $foq->whereIn('status', ['ready', 'ready_to_ship', 'packed']));
            }),
            'shipped' => $query->where(function (Builder $sub) {
                $sub->where('status', OrderStatus::OUT_FOR_DELIVERY)
                    ->orWhereHas('fulfilmentOrders.shipments', fn(Builder $sq) => $sq->whereIn('status', ['dispatched', 'in_transit', 'picked_up', 'out_for_delivery']));
            }),
            'delivered' => $query->where('status', OrderStatus::DELIVERED),
            'cancelled' => $query->where('status', OrderStatus::CANCELLED),
            'return_requested' => $query->whereHas('fulfilmentOrders.items.returns', function (Builder $rq) {
                $rq->whereIn('status', ['requested', 'approved', 'reverse_pickup_scheduled', 'pickup_scheduled', 'picked_up']);
            }),
            'returned' => $query->where(function (Builder $sub) {
                $sub->where('status', OrderStatus::REFUNDED)
                    ->orWhereHas('fulfilmentOrders.items.returns', fn(Builder $rq) => $rq->whereIn('status', ['received', 'qc_passed', 'refunded']));
            }),
            'rto' => $query->where(function (Builder $sub) {
                $sub->whereHas('fulfilmentOrders.shipments.rtoCases')
                    ->orWhereHas('fulfilmentOrders.shipments', fn(Builder $sq) => $sq->where('status', 'rto'));
            }),
            default => null, // 'all' tab does not filter status
        };
    }

    /**
     * Calculate dynamic live counters for all 10 order tabs.
     *
     * @return array<string, int>
     */
    public function getOrderTabCounts(): array
    {
        return [
            'all' => Order::count(),
            'pending' => Order::whereIn('status', [OrderStatus::PENDING, OrderStatus::PLACED])->count(),
            'processing' => Order::whereIn('status', [OrderStatus::CONFIRMED, OrderStatus::PROCESSING])->count(),
            'ready_to_ship' => Order::where(function (Builder $sub) {
                $sub->where('status', OrderStatus::READY_FOR_PICKUP)
                    ->orWhereHas('fulfilmentOrders', fn($foq) => $foq->whereIn('status', ['ready', 'ready_to_ship', 'packed']));
            })->count(),
            'shipped' => Order::where(function (Builder $sub) {
                $sub->where('status', OrderStatus::OUT_FOR_DELIVERY)
                    ->orWhereHas('fulfilmentOrders.shipments', fn($sq) => $sq->whereIn('status', ['dispatched', 'in_transit', 'picked_up', 'out_for_delivery']));
            })->count(),
            'delivered' => Order::where('status', OrderStatus::DELIVERED)->count(),
            'cancelled' => Order::where('status', OrderStatus::CANCELLED)->count(),
            'return_requested' => Order::whereHas('fulfilmentOrders.items.returns', function ($rq) {
                $rq->whereIn('status', ['requested', 'approved', 'reverse_pickup_scheduled', 'pickup_scheduled', 'picked_up']);
            })->count(),
            'returned' => Order::where(function (Builder $sub) {
                $sub->where('status', OrderStatus::REFUNDED)
                    ->orWhereHas('fulfilmentOrders.items.returns', fn($rq) => $rq->whereIn('status', ['received', 'qc_passed', 'refunded']));
            })->count(),
            'rto' => Order::where(function (Builder $sub) {
                $sub->whereHas('fulfilmentOrders.shipments.rtoCases')
                    ->orWhereHas('fulfilmentOrders.shipments', fn($sq) => $sq->where('status', 'rto'));
            })->count(),
        ];
    }

    /**
     * Get overview metric statistics for orders registry header.
     *
     * @return array<string, mixed>
     */
    public function getOrderMetrics(): array
    {
        return [
            'total_orders' => Order::count(),
            'total_revenue' => (float) Order::where('payment_status', PaymentStatus::PAID)->sum('total'),
            'processing' => Order::whereIn('status', [OrderStatus::PLACED, OrderStatus::CONFIRMED, OrderStatus::PROCESSING])->count(),
            'delivered' => Order::where('status', OrderStatus::DELIVERED)->count(),
            'cancelled' => Order::where('status', OrderStatus::CANCELLED)->count(),
            'quick_orders' => FulfilmentOrder::where('fulfilment_type', FulfilmentType::QUICK)->count(),
            'returns_count' => OrderReturn::whereIn('status', ['requested', 'approved', 'received', 'qc_passed', 'refunded'])->count(),
        ];
    }

    /**
     * Admin cancel full order with mandatory reason and audit entry.
     */
    public function cancelOrder(Order $order, string $reason, User $admin): void
    {
        DB::transaction(function () use ($order, $reason, $admin) {
            $fromStatus = $order->status->value;
            $order->status = OrderStatus::CANCELLED;
            $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . "[" . now()->format('d M Y, h:i A') . " by {$admin->name} - CANCELLED]: {$reason}";
            $order->save();

            // Cancel all sub-orders that are not delivered
            foreach ($order->fulfilmentOrders as $fo) {
                if ($fo->status !== 'delivered') {
                    $fo->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                }
            }

            OrderStatusHistory::create([
                'orderable_type' => Order::class,
                'orderable_id' => $order->id,
                'from_status' => $fromStatus,
                'to_status' => OrderStatus::CANCELLED->value,
                'note' => "[ADMIN CANCELLATION]: {$reason}",
                'changed_by' => $admin->id,
            ]);

            activity()
                ->performedOn($order)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'cancel_order',
                    'reason' => $reason,
                    'old_status' => $fromStatus,
                    'new_status' => OrderStatus::CANCELLED->value,
                ])
                ->log("Admin #{$admin->id} cancelled Order #{$order->order_number}. Reason: {$reason}");
        });
    }

    /**
     * Admin cancel individual multi-vendor sub-order package.
     */
    public function cancelSubOrder(FulfilmentOrder $fo, string $reason, User $admin): void
    {
        DB::transaction(function () use ($fo, $reason, $admin) {
            $fromStatus = $fo->status;
            $fo->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            OrderStatusHistory::create([
                'orderable_type' => FulfilmentOrder::class,
                'orderable_id' => $fo->id,
                'from_status' => $fromStatus,
                'to_status' => 'cancelled',
                'note' => "[SUB-ORDER CANCELLATION]: {$reason}",
                'changed_by' => $admin->id,
            ]);

            // If all sub-orders of the order are now cancelled, cancel parent order too
            $order = $fo->order;
            if ($order && $order->fulfilmentOrders()->where('status', '!=', 'cancelled')->count() === 0) {
                $order->update(['status' => OrderStatus::CANCELLED]);
            }

            activity()
                ->performedOn($fo)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'cancel_sub_order',
                    'reason' => $reason,
                    'old_status' => $fromStatus,
                    'new_status' => 'cancelled',
                ])
                ->log("Admin #{$admin->id} cancelled Sub-Order Package #{$fo->fulfilment_order_number}. Reason: {$reason}");
        });
    }

    /**
     * Record dispute resolution on an order.
     */
    public function resolveDispute(Order $order, string $resolution, User $admin): void
    {
        DB::transaction(function () use ($order, $resolution, $admin) {
            $timestamp = now()->format('d M Y, h:i A');
            $newNote = "[{$timestamp} by {$admin->name} - DISPUTE RESOLVED]: {$resolution}";
            $order->notes = ($order->notes ? $order->notes . "\n\n" : '') . $newNote;
            $order->save();

            OrderStatusHistory::create([
                'orderable_type' => Order::class,
                'orderable_id' => $order->id,
                'from_status' => $order->status->value,
                'to_status' => $order->status->value,
                'note' => "[DISPUTE RESOLUTION]: {$resolution}",
                'changed_by' => $admin->id,
            ]);

            activity()
                ->performedOn($order)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'resolve_dispute',
                    'resolution' => $resolution,
                ])
                ->log("Admin #{$admin->id} recorded dispute resolution for Order #{$order->order_number}: {$resolution}");
        });
    }

    /**
     * Export filtered orders report to CSV.
     *
     * @param array<string, mixed> $filters
     */
    public function exportOrdersCsv(array $filters = []): StreamedResponse
    {
        $query = $this->buildOrdersQuery($filters);
        $fileName = 'allzone_orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Order Number',
                'Sub-Orders Count',
                'Date Placed',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Delivery Address',
                'City',
                'State',
                'Pincode',
                'Seller Names',
                'Subtotal (INR)',
                'Discount (INR)',
                'Delivery Fee (INR)',
                'Taxes (INR)',
                'Total (INR)',
                'Payment Method',
                'Payment Status',
                'Order Status',
                'Fulfilment Modes',
                'AWB Tracking Codes',
            ]);

            $query->chunk(150, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    $addr = $order->address_snapshot ?? [];
                    $sellers = $order->fulfilmentOrders->map(fn($fo) => $fo->seller?->business_name)->filter()->unique()->implode('; ');
                    $modes = $order->fulfilmentOrders->map(fn($fo) => is_object($fo->fulfilment_type) ? $fo->fulfilment_type->value : $fo->fulfilment_type)->unique()->implode(', ');
                    $awbs = $order->fulfilmentOrders->flatMap->shipments->pluck('awb')->filter()->unique()->implode(', ');

                    fputcsv($handle, [
                        $order->order_number,
                        $order->fulfilmentOrders->count(),
                        $order->created_at->format('Y-m-d H:i:s'),
                        $addr['name'] ?? $order->customer?->user?->name ?? 'Guest',
                        $order->customer?->user?->email ?? 'N/A',
                        $addr['phone'] ?? $order->customer?->user?->phone ?? 'N/A',
                        trim(($addr['line1'] ?? '') . ' ' . ($addr['line2'] ?? '')),
                        $addr['city'] ?? 'N/A',
                        $addr['state'] ?? 'N/A',
                        $addr['pincode'] ?? 'N/A',
                        $sellers ?: 'Direct',
                        number_format((float) $order->subtotal, 2, '.', ''),
                        number_format((float) $order->discount, 2, '.', ''),
                        number_format((float) $order->delivery_fee, 2, '.', ''),
                        number_format((float) $order->tax, 2, '.', ''),
                        number_format((float) $order->total, 2, '.', ''),
                        $order->payment_method?->value ?? (string) $order->payment_method,
                        $order->payment_status?->value ?? (string) $order->payment_status,
                        $order->status?->value ?? (string) $order->status,
                        $modes ?: 'standard',
                        $awbs ?: 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
