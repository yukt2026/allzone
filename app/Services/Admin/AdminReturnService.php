<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Enums\QCStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\ShipmentStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RtoCase;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Cargo\ShiprocketLogisticsProvider;
use App\Services\Commission\CommissionService;
use App\Services\Inventory\InventoryService;
use App\Services\Order\ReturnService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class AdminReturnService
{
    public function __construct(
        protected ReturnService $returnService,
        protected PaymentService $paymentService,
        protected CommissionService $commissionService,
        protected InventoryService $inventoryService,
        protected ShiprocketLogisticsProvider $logisticsProvider
    ) {}

    /**
     * Get platform-wide returns, RTO, and refund metrics.
     *
     * @return array<string, mixed>
     */
    public function getReturnMetrics(): array
    {
        $rtoShipments = Shipment::whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])->count()
            + RtoCase::count();

        $pendingInspection = OrderReturn::where(function (Builder $q) {
            $q->where('status', ReturnStatus::RECEIVED)
              ->orWhere('qc_status', QCStatus::PENDING);
        })->whereNotIn('status', [ReturnStatus::REQUESTED, ReturnStatus::REJECTED, ReturnStatus::REFUNDED, ReturnStatus::CANCELLED])->count();

        $refundPending = OrderReturn::where('status', ReturnStatus::QC_PASSED)
            ->whereNull('refunded_at')
            ->count();

        return [
            'total_returns' => OrderReturn::count(),
            'return_requests' => OrderReturn::where('status', ReturnStatus::REQUESTED)->count(),
            'approved_returns' => OrderReturn::whereIn('status', [
                ReturnStatus::APPROVED,
                ReturnStatus::REVERSE_PICKUP_SCHEDULED,
                ReturnStatus::PICKED_UP,
                ReturnStatus::RECEIVED,
                ReturnStatus::QC_PASSED,
            ])->count(),
            'rejected_returns' => OrderReturn::where('status', ReturnStatus::REJECTED)->count(),
            'pending_inspections' => $pendingInspection,
            'rto_shipments' => $rtoShipments,
            'refund_pending' => $refundPending,
            'refund_completed' => OrderReturn::where('status', ReturnStatus::REFUNDED)->count(),
            'total_refunded_amount' => (float) OrderReturn::where('status', ReturnStatus::REFUNDED)->sum('refund_amount'),
        ];
    }

    /**
     * Aggregate distribution of return reasons.
     *
     * @return Collection<int, object>
     */
    public function getReturnReasonsDistribution(): Collection
    {
        return OrderReturn::query()
            ->select('reason', DB::raw('count(*) as count'), DB::raw('sum(refund_amount) as total_amount'))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(8)
            ->get();
    }

    /**
     * Product-wise return trends (highest returns).
     *
     * @return Collection<int, object>
     */
    public function getProductReturnTrends(int $limit = 5): Collection
    {
        return DB::table('returns')
            ->join('order_items', 'returns.order_item_id', '=', 'order_items.id')
            ->select(
                'order_items.product_id',
                'order_items.title',
                'order_items.sku',
                DB::raw('count(returns.id) as return_count'),
                DB::raw('sum(returns.refund_amount) as total_refund_amount')
            )
            ->groupBy('order_items.product_id', 'order_items.title', 'order_items.sku')
            ->orderByDesc('return_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Build filtered returns query.
     *
     * @param array<string, mixed> $filters
     */
    public function buildReturnsQuery(array $filters = []): Builder
    {
        $query = OrderReturn::query()
            ->with([
                'orderItem.variant.product',
                'orderItem.fulfilmentOrder.seller',
                'orderItem.fulfilmentOrder.order.user',
                'orderItem.fulfilmentOrder.order.customer.user',
                'pickupShipment.tracking',
                'refund',
            ]);

        // Status filter tab
        $tab = $filters['tab'] ?? $filters['status'] ?? 'all';
        match ($tab) {
            'requested' => $query->where('status', ReturnStatus::REQUESTED),
            'approved' => $query->whereIn('status', [ReturnStatus::APPROVED, ReturnStatus::REVERSE_PICKUP_SCHEDULED, ReturnStatus::PICKED_UP]),
            'pending_inspection' => $query->where('status', ReturnStatus::RECEIVED)->where('qc_status', QCStatus::PENDING),
            'qc_passed' => $query->where('qc_status', QCStatus::PASSED),
            'refund_pending' => $query->where('status', ReturnStatus::QC_PASSED)->whereNull('refunded_at'),
            'refunded' => $query->where('status', ReturnStatus::REFUNDED),
            'rejected' => $query->where('status', ReturnStatus::REJECTED),
            default => null,
        };

        // QC Status filter
        if (!empty($filters['qc_status']) && $filters['qc_status'] !== 'all') {
            $query->where('qc_status', $filters['qc_status']);
        }

        // Seller filter
        if (!empty($filters['seller_id'])) {
            $sellerId = (int) $filters['seller_id'];
            $query->whereHas('orderItem.fulfilmentOrder', fn(Builder $foq) => $foq->where('seller_id', $sellerId));
        }

        // Search
        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('return_number', 'like', "%{$term}%")
                  ->orWhere('pickup_waybill', 'like', "%{$term}%")
                  ->orWhere('reason', 'like', "%{$term}%")
                  ->orWhereHas('orderItem', function (Builder $iq) use ($term) {
                      $iq->where('title', 'like', "%{$term}%")
                         ->orWhere('sku', 'like', "%{$term}%");
                  })
                  ->orWhereHas('orderItem.fulfilmentOrder.order', function (Builder $oq) use ($term) {
                      $oq->where('order_number', 'like', "%{$term}%")
                         ->orWhereHas('customer.user', function (Builder $uq) use ($term) {
                             $uq->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%")
                                ->orWhere('phone', 'like', "%{$term}%");
                         });
                  });
            });
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
     * Build RTO shipments query.
     */
    public function buildRtoQuery(): Builder
    {
        return Shipment::query()
            ->where(function (Builder $q) {
                $q->whereIn('status', [ShipmentStatus::RTO_INITIATED, ShipmentStatus::RTO_DELIVERED])
                  ->orWhereHas('rtoCases');
            })
            ->with([
                'fulfilmentOrder.order.customer.user',
                'fulfilmentOrder.seller',
                'rtoCases',
                'tracking',
            ])
            ->latest('id');
    }

    /**
     * Admin approve return request with mandatory notes and optional Shiprocket reverse pickup booking.
     */
    public function approveReturn(OrderReturn $return, string $notes, User $admin, bool $autoSchedulePickup = true): OrderReturn
    {
        $return = $this->returnService->adminOverride($return, 'approve', [
            'notes' => $notes,
            'auto_schedule_pickup' => $autoSchedulePickup,
        ], $admin);

        if ($autoSchedulePickup && !$return->pickup_waybill) {
            try {
                $this->returnService->scheduleReversePickup($return, $admin);
            } catch (\Throwable $e) {
                Log::warning("Shiprocket reverse pickup auto-booking failed for Return #{$return->return_number}: " . $e->getMessage());
            }
        }

        return $return;
    }

    /**
     * Admin reject return request with mandatory rejection remarks.
     */
    public function rejectReturn(OrderReturn $return, string $reason, User $admin): OrderReturn
    {
        return $this->returnService->adminOverride($return, 'reject', [
            'notes' => $reason,
        ], $admin);
    }

    /**
     * Request additional information / documentation from customer.
     */
    public function requestAdditionalInfo(OrderReturn $return, string $instructions, User $admin): OrderReturn
    {
        $timestamp = now()->format('d M Y, h:i A');
        $note = "[{$timestamp} by {$admin->name} - INFO REQUESTED]: {$instructions}";
        $return->admin_notes = ($return->admin_notes ? $return->admin_notes . "\n\n" : '') . $note;
        $return->save();

        if (function_exists('activity')) {
            activity('returns')
                ->performedOn($return)
                ->causedBy($admin)
                ->withProperties(['action' => 'request_info', 'instructions' => $instructions])
                ->log("Admin requested additional info for Return #{$return->return_number}: {$instructions}");
        }

        return $return;
    }

    /**
     * Record Quality Control inspection outcome with resellable flag.
     */
    public function recordQcOutcome(
        OrderReturn $return,
        string $outcome,
        ?string $qcNotes = null,
        bool $isResellable = true,
        ?User $admin = null
    ): OrderReturn {
        return $this->returnService->recordQcOutcome(
            $return,
            $outcome,
            $qcNotes,
            $isResellable,
            $admin
        );
    }

    /**
     * Advance / update return status directly with audit trail.
     */
    public function updateReturnStatus(OrderReturn $return, ReturnStatus $newStatus, string $note, User $admin): OrderReturn
    {
        $oldStatus = $return->status->value;
        $return->status = $newStatus;

        if ($newStatus === ReturnStatus::PICKED_UP && !$return->picked_up_at) {
            $return->picked_up_at = now();
        } elseif ($newStatus === ReturnStatus::RECEIVED && !$return->received_at) {
            $return->received_at = now();
        }

        $timestamp = now()->format('d M Y, h:i A');
        $return->admin_notes = ($return->admin_notes ? $return->admin_notes . "\n\n" : '') . "[{$timestamp} by {$admin->name} - STATUS UPDATED to {$newStatus->value}]: {$note}";
        $return->save();

        if (function_exists('activity')) {
            activity('returns')
                ->performedOn($return)
                ->causedBy($admin)
                ->withProperties([
                    'action' => 'status_update',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus->value,
                    'note' => $note,
                ])
                ->log("Admin #{$admin->id} updated Return #{$return->return_number} from {$oldStatus} to {$newStatus->value}");
        }

        return $return;
    }

    /**
     * Initiate eligible refund through Razorpay (Online) or verified Payout (COD/Manual).
     * Enforces strict financial idempotency and payment verification.
     *
     * @param array<string, mixed> $options
     */
    public function processRefund(OrderReturn $return, array $options = [], ?User $admin = null): OrderReturn
    {
        // 1. Idempotency Check: Prevent duplicate refunds
        if ($return->status === ReturnStatus::REFUNDED || $return->refund_id) {
            throw new RuntimeException("Refund has already been finalized for Return #{$return->return_number}. Duplicate refund prevented.");
        }

        $admin = $admin ?? auth()->user();
        $item = $return->orderItem;
        $fo = $item?->fulfilmentOrder;
        $order = $fo?->order;

        if (!$order) {
            throw new RuntimeException("Parent order record not found for Return #{$return->return_number}.");
        }

        $refundAmount = !empty($options['amount']) ? (float) $options['amount'] : (float) ($return->refund_amount ?? $item->total);
        $refundMode = $options['mode'] ?? $return->refund_mode ?? 'original';
        $payoutReference = $options['payout_reference'] ?? null;
        $adminNotes = $options['notes'] ?? 'Administrative refund execution';

        // 2. Payment verification
        $payment = $order->payments()->where('status', PaymentStatus::PAID)->latest('id')->first()
            ?? $order->payments()->latest('id')->first();

        $isCodOrder = ($order->payment_method?->value === 'cod') || ($payment && $payment->gateway === 'cod');

        return DB::transaction(function () use (
            $return,
            $order,
            $fo,
            $item,
            $payment,
            $refundAmount,
            $refundMode,
            $isCodOrder,
            $payoutReference,
            $adminNotes,
            $admin,
            $options
        ) {
            // 2. Determine refund execution route
            if ($refundMode === 'wallet') {
                // Credit to store wallet (Applicable for both COD and Prepaid orders)
                $user = $order->customer?->user ?? $order->user;
                if ($user) {
                    $newBalance = (float) $user->wallet_balance + $refundAmount;
                    $user->wallet_balance = $newBalance;
                    $user->save();

                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'type' => 'credit',
                        'amount' => $refundAmount,
                        'balance_after' => $newBalance,
                        'reference_type' => OrderReturn::class,
                        'reference_id' => $return->id,
                        'description' => "Store credit refund for Return #{$return->return_number}",
                    ]);
                }

                $refund = Refund::create([
                    'payment_id' => $payment?->id ?? 1,
                    'fulfilment_order_id' => $fo?->id,
                    'amount' => $refundAmount,
                    'reason' => "Return #{$return->return_number} (Wallet Credit)",
                    'status' => RefundStatus::PROCESSED,
                    'gateway_refund_id' => 'wallet_ref_' . strtoupper(uniqid()),
                    'raw_response' => ['mode' => 'wallet', 'admin_id' => $admin?->id],
                    'processed_at' => now(),
                ]);
            } elseif ($isCodOrder) {
                // COD Safety rule: COD refunds must follow a verified payout workflow, NEVER routed to Razorpay
                if ($refundMode === 'original' && empty($payoutReference) && empty($options['confirm_cod_payout'])) {
                    throw new InvalidArgumentException("COD refunds require a verified bank payout reference or wallet credit. COD payments cannot be refunded via Razorpay.");
                }

                // Manual bank transfer / NEFT / cash payout
                $ref = $payoutReference ?: ('cod_payout_' . strtoupper(uniqid()));
                $refund = Refund::create([
                    'payment_id' => $payment?->id ?? 1,
                    'fulfilment_order_id' => $fo?->id,
                    'amount' => $refundAmount,
                    'reason' => "Return #{$return->return_number} (Verified COD Payout: {$ref})",
                    'status' => RefundStatus::PROCESSED,
                    'gateway_refund_id' => $ref,
                    'raw_response' => [
                        'mode' => 'cod_verified_payout',
                        'payout_reference' => $ref,
                        'admin_id' => $admin?->id,
                    ],
                    'processed_at' => now(),
                ]);
            } else {
                // Online payment (Razorpay / Gateway)
                if (!$payment) {
                    throw new RuntimeException("Original payment record not found for Order #{$order->order_number}. Cannot initiate gateway refund.");
                }

                // Call PaymentService to hit Razorpay Refund API
                $refund = $this->paymentService->processRefund(
                    $payment,
                    $refundAmount,
                    "Return #{$return->return_number}: {$item?->title}",
                    $fo?->id
                );
            }

            // 3. Update Return Record
            $return->refund_id = $refund->id;
            $return->refund_amount = $refundAmount;
            $return->status = ReturnStatus::REFUNDED;
            $return->refunded_at = now();
            $return->admin_notes = ($return->admin_notes ? $return->admin_notes . "\n\n" : '') . "[REFUND ISSUED on " . now()->format('d M Y, h:i A') . " by {$admin?->name}]: Amount ₹{$refundAmount} via Refund #{$refund->id}" . ($adminNotes ? " - {$adminNotes}" : "");
            $return->save();

            // 4. Reverse Platform Commission
            if ($fo) {
                try {
                    $this->commissionService->recordReversal(
                        $fo,
                        $refundAmount,
                        "Return #{$return->return_number}: {$item?->title}"
                    );
                } catch (\Throwable $e) {
                    Log::warning("Commission reversal failed for Return #{$return->return_number}: " . $e->getMessage());
                }
            }

            // 5. Restore stock if resellable
            $isResellable = $options['is_resellable'] ?? $return->is_resellable;
            if ($isResellable && $item && $item->variant_id) {
                try {
                    $locationId = $fo?->inventory_location_id ?? $fo?->store?->location?->id ?? 1;
                    $inventory = Inventory::where('variant_id', $item->variant_id)
                        ->where('location_id', $locationId)
                        ->first() 
                        ?? Inventory::where('variant_id', $item->variant_id)->first();

                    if ($inventory) {
                        $this->inventoryService->restock(
                            $inventory,
                            (int) $item->qty,
                            "Restock from Return #{$return->return_number}",
                            $return,
                            $admin?->id
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning("Inventory restock failed for Return #{$return->return_number}: " . $e->getMessage());
                }
            }

            // 6. Audit Trail Logging
            if (function_exists('activity')) {
                activity('returns')
                    ->performedOn($return)
                    ->causedBy($admin)
                    ->withProperties([
                        'action' => 'process_refund',
                        'amount' => $refundAmount,
                        'refund_id' => $refund->id,
                        'gateway_refund_id' => $refund->gateway_refund_id,
                        'mode' => $isCodOrder ? 'cod_payout' : 'razorpay',
                    ])
                    ->log("Admin #{$admin?->id} issued refund of ₹{$refundAmount} for Return #{$return->return_number}. Refund ID: {$refund->id}");
            }

            return $return;
        });
    }
}
