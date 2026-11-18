<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Announcement;
use App\Models\Campaign;
use App\Models\FulfilmentOrder;
use App\Models\Inventory;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Seller;
use App\Models\SellerClaim;
use App\Models\SellerSettlement;
use App\Models\Shipment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\AnnouncementPublishedSellerNotification;
use App\Notifications\ClaimUpdatedSellerNotification;
use App\Notifications\DelayedShipmentAdminNotification;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\NewOrderAdminNotification;
use App\Notifications\NewOrderSellerNotification;
use App\Notifications\NewSellerRegisteredAdminNotification;
use App\Notifications\OrderCancelledSellerNotification;
use App\Notifications\PaymentFailedAdminNotification;
use App\Notifications\ProductModerationStatusNotification;
use App\Notifications\ProductSubmittedAdminNotification;
use App\Notifications\PromotionInvitationSellerNotification;
use App\Notifications\RefundRequestedAdminNotification;
use App\Notifications\ReturnOrClaimRequestedAdminNotification;
use App\Notifications\ReturnRequestedSellerNotification;
use App\Notifications\SecurityOrInventoryAlertAdminNotification;
use App\Notifications\SellerApprovalRequestedAdminNotification;
use App\Notifications\SettlementUpdatedSellerNotification;
use App\Notifications\ShipmentStatusUpdatedSellerNotification;
use App\Notifications\ShiprocketFailureAdminNotification;
use App\Notifications\SupportEscalatedAdminNotification;
use App\Notifications\SupportTicketReplySellerNotification;
use App\Notifications\UnifiedNotification;
use App\Services\Notification\Drivers\PushGatewayInterface;
use App\Services\Notification\Drivers\SmsGatewayInterface;
use App\Services\Notification\Drivers\WhatsAppGatewayInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch unified queued notification across eligible channels.
     *
     * @param User $user
     * @param string $eventKey
     * @param array<string, mixed> $variables
     * @param array<string>|null $overrideChannels
     */
    public function dispatch(
        User $user,
        string $eventKey,
        array $variables = [],
        ?array $overrideChannels = null
    ): void {
        if ($eventKey === 'promotional' && !$user->hasPromotionalConsent()) {
            Log::info("NotificationService: Skipped promotional notification for user #{$user->id} — no stored consent.");
            return;
        }

        $notification = new UnifiedNotification(
            eventKey: $eventKey,
            variables: $variables,
            overrideChannels: $overrideChannels
        );

        $user->notify($notification);

        // Also record database notification in notification_logs if database channel is active
        NotificationLog::create([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'event_key' => $eventKey,
            'channel' => 'database',
            'recipient' => $user->email,
            'subject' => $variables['subject'] ?? $eventKey,
            'content' => json_encode($variables),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Check if user was already notified for this event and reference within the given time window.
     * Prevents duplicate alerts from repeated webhook events or retried jobs.
     */
    public function hasRecentlyNotified(User $user, string $eventKey, string|int $referenceId, int $minutes = 60): bool
    {
        $cutoff = now()->subMinutes($minutes);

        return $user->notifications()
            ->where('created_at', '>=', $cutoff)
            ->where(function ($query) use ($eventKey, $referenceId) {
                $query->where('data->event_key', $eventKey)
                      ->where('data->reference_id', (string) $referenceId);
            })
            ->exists();
    }

    /**
     * Safely dispatch notification to a seller user, suppressing duplicate alerts.
     */
    public function notifySeller(User $sellerUser, Notification $notification): bool
    {
        if (isset($notification->eventKey) && isset($notification->referenceId)) {
            if ($this->hasRecentlyNotified($sellerUser, (string) $notification->eventKey, (string) $notification->referenceId)) {
                Log::info("NotificationService: Suppressed duplicate notification {$notification->eventKey} (Ref: {$notification->referenceId}) for user #{$sellerUser->id}");
                return false;
            }
        }

        $sellerUser->notify($notification);
        return true;
    }

    /**
     * Dispatch notification to platform administrators, avoiding duplicate alerts.
     */
    public function notifyAdmins(Notification $notification, ?string $permission = null): int
    {
        $admins = collect();

        if ($permission) {
            try {
                $admins = User::permission($permission)->get();
            } catch (\Throwable) {
                $admins = collect();
            }
        }

        if ($admins->isEmpty()) {
            try {
                $admins = User::role(['super_admin', 'admin', 'operations_admin', 'catalog_manager', 'finance_manager', 'seller_support'])->get();
            } catch (\Throwable) {
                $admins = collect();
            }
        }

        if ($admins->isEmpty()) {
            $admins = User::whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'admin']))->get();
        }

        if ($admins->isEmpty()) {
            $admins = User::where('email', 'like', '%admin%')->limit(5)->get();
        }

        $sentCount = 0;
        foreach ($admins as $admin) {
            if (isset($notification->eventKey) && isset($notification->referenceId)) {
                if ($this->hasRecentlyNotified($admin, (string) $notification->eventKey, (string) $notification->referenceId)) {
                    continue;
                }
            }
            $admin->notify($notification);
            $sentCount++;
        }

        return $sentCount;
    }

    /* -------------------------------------------------------------------------- */
    /*                         SELLER NOTIFICATION DISPATCHERS                    */
    /* -------------------------------------------------------------------------- */

    /**
     * Seller: New order package received.
     */
    public function notifySellerNewOrder(FulfilmentOrder $fo): bool
    {
        $sellerUser = $fo->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new NewOrderSellerNotification($fo));
    }

    /**
     * Seller: Order package cancelled.
     */
    public function notifySellerOrderCancelled(FulfilmentOrder $fo, ?string $reason = null): bool
    {
        $sellerUser = $fo->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new OrderCancelledSellerNotification($fo, $reason));
    }

    /**
     * Seller: Shipment status updated.
     */
    public function notifySellerShipmentStatus(Shipment $shipment, string $statusLabel, ?string $notes = null): bool
    {
        $sellerUser = $shipment->fulfilmentOrder?->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new ShipmentStatusUpdatedSellerNotification($shipment, $statusLabel, $notes));
    }

    /**
     * Seller: Customer return request lodged.
     */
    public function notifySellerReturnRequest(OrderReturn $return): bool
    {
        $sellerUser = $return->orderItem?->fulfilmentOrder?->seller?->user
            ?? $return->order?->fulfilmentOrders()->first()?->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new ReturnRequestedSellerNotification($return));
    }

    /**
     * Seller: Claim update (approved, rejected, under review).
     */
    public function notifySellerClaimUpdate(SellerClaim $claim, string $statusLabel, ?string $notes = null): bool
    {
        $sellerUser = $claim->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new ClaimUpdatedSellerNotification($claim, $statusLabel, $notes));
    }

    /**
     * Seller: Product catalog approval/rejection/status.
     */
    public function notifySellerProductModeration(Product $product, string $statusLabel, ?string $reason = null): bool
    {
        $sellerUser = $product->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new ProductModerationStatusNotification($product, $statusLabel, $reason));
    }

    /**
     * Seller: Low stock alert on variant/inventory.
     */
    public function notifySellerLowStock(Inventory $inventory): bool
    {
        $sellerUser = $inventory->variant?->product?->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new LowStockAlertNotification($inventory));
    }

    /**
     * Seller: Settlement/Payout update.
     */
    public function notifySellerSettlementUpdate(SellerSettlement $settlement, string $statusLabel, ?string $utr = null): bool
    {
        $sellerUser = $settlement->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new SettlementUpdatedSellerNotification($settlement, $statusLabel, $utr));
    }

    /**
     * Seller: Support ticket response.
     */
    public function notifySellerTicketReply(SupportTicket $ticket, string $responderName, string $messageExcerpt): bool
    {
        $sellerUser = $ticket->user ?? $ticket->seller?->user;
        if (!$sellerUser) return false;

        return $this->notifySeller($sellerUser, new SupportTicketReplySellerNotification($ticket, $responderName, $messageExcerpt));
    }

    /**
     * Seller: New published notice or announcement.
     */
    public function notifySellerAnnouncement(Announcement $announcement, ?Seller $seller = null): int
    {
        if ($seller && $seller->user) {
            return $this->notifySeller($seller->user, new AnnouncementPublishedSellerNotification($announcement)) ? 1 : 0;
        }

        $sellers = Seller::with('user')->whereIn('status', ['active', 'approved', \App\Enums\SellerStatus::APPROVED])->get();
        $dispatched = 0;
        foreach ($sellers as $s) {
            if ($s->user && $this->notifySeller($s->user, new AnnouncementPublishedSellerNotification($announcement))) {
                $dispatched++;
            }
        }
        return $dispatched;
    }

    /**
     * Seller: Promotion/campaign invitation.
     */
    public function notifySellerPromotionInvitation(Campaign $campaign, Seller $seller): bool
    {
        if (!$seller->user) return false;

        return $this->notifySeller($seller->user, new PromotionInvitationSellerNotification($campaign));
    }

    /* -------------------------------------------------------------------------- */
    /*                         ADMIN NOTIFICATION DISPATCHERS                     */
    /* -------------------------------------------------------------------------- */

    /**
     * Admin: New seller registration.
     */
    public function notifyAdminsNewSeller(Seller $seller): int
    {
        return $this->notifyAdmins(new NewSellerRegisteredAdminNotification($seller), 'sellers.view');
    }

    /**
     * Admin: Seller approval / KYC verification request.
     */
    public function notifyAdminsSellerApprovalRequest(Seller $seller): int
    {
        return $this->notifyAdmins(new SellerApprovalRequestedAdminNotification($seller), 'sellers.approve');
    }

    /**
     * Admin: New product submitted for catalog moderation/QC.
     */
    public function notifyAdminsProductSubmitted(Product $product): int
    {
        return $this->notifyAdmins(new ProductSubmittedAdminNotification($product), 'products.approve');
    }

    /**
     * Admin: High-value order or platform order created.
     */
    public function notifyAdminsNewOrder(Order $order): int
    {
        return $this->notifyAdmins(new NewOrderAdminNotification($order), 'orders.view');
    }

    /**
     * Admin: Online payment failure.
     */
    public function notifyAdminsPaymentFailed(Payment $payment, string $reason): int
    {
        return $this->notifyAdmins(new PaymentFailedAdminNotification($payment, $reason), 'payments.view');
    }

    /**
     * Admin: Refund request pending approval.
     */
    public function notifyAdminsRefundRequest(Refund $refund, ?string $reason = null): int
    {
        return $this->notifyAdmins(new RefundRequestedAdminNotification($refund, $reason), 'payments.refund');
    }

    /**
     * Admin: Return request or seller dispute claim lodged.
     */
    public function notifyAdminsReturnOrClaim(string $requestType, string $identifier, string $partyName, string $details, int $id): int
    {
        return $this->notifyAdmins(new ReturnOrClaimRequestedAdminNotification($requestType, $identifier, $partyName, $details, $id));
    }

    /**
     * Admin: Shiprocket logistics API or webhook failure.
     */
    public function notifyAdminsShiprocketFailure(string $operation, string $errorMessage, ?string $awbOrRef = null): int
    {
        return $this->notifyAdmins(new ShiprocketFailureAdminNotification($operation, $errorMessage, $awbOrRef), 'cargo.manage');
    }

    /**
     * Admin: Shipment SLA breach / delayed transit.
     */
    public function notifyAdminsDelayedShipment(Shipment $shipment, int $delayedHours): int
    {
        return $this->notifyAdmins(new DelayedShipmentAdminNotification($shipment, $delayedHours), 'cargo.view');
    }

    /**
     * Admin: Support ticket escalation.
     */
    public function notifyAdminsSupportEscalation(SupportTicket $ticket, string $escalationReason): int
    {
        return $this->notifyAdmins(new SupportEscalatedAdminNotification($ticket, $escalationReason), 'tickets.assign');
    }

    /**
     * Admin: Critical security or inventory alert.
     */
    public function notifyAdminsSecurityOrInventoryAlert(
        string $alertType,
        string $heading,
        string $details,
        ?string $targetUrl = null,
        array $context = []
    ): int {
        return $this->notifyAdmins(new SecurityOrInventoryAlertAdminNotification($alertType, $heading, $details, $targetUrl, $context));
    }

    /* -------------------------------------------------------------------------- */
    /*                         CUSTOMER / ORDER TRANSACTIONAL ALERTS              */
    /* -------------------------------------------------------------------------- */

    /**
     * Send Security OTP.
     */
    public function sendOtp(User $user, string $otp): void
    {
        $this->dispatch($user, 'otp', [
            'otp' => $otp,
            'name' => $user->name,
        ], ['mail', 'sms', 'whatsapp']);
    }

    /**
     * Send Order Confirmation.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        $user = $order->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'order_confirmed', [
            'order_number' => $order->order_number,
            'amount' => number_format((float) $order->total, 2),
            'name' => $user->name,
            'action_url' => route('orders.show', $order->order_number),
            'action_text' => 'Track Order',
        ]);
    }

    /**
     * Send Payment Confirmation.
     */
    public function sendPaymentConfirmation(Payment $payment): void
    {
        $order = $payment->order;
        $user = $order?->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'payment_confirmed', [
            'order_number' => $order->order_number,
            'amount' => number_format((float) $payment->amount, 2),
            'gateway' => $payment->gateway,
            'action_url' => route('orders.show', $order->order_number),
        ]);
    }

    /**
     * Send Packing in Progress update.
     */
    public function sendOrderPacking(FulfilmentOrder $fo): void
    {
        $user = $fo->order?->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'order_packing', [
            'order_number' => $fo->fulfilment_order_number,
            'store_name' => $fo->store?->name ?? 'Store Hub',
            'action_url' => route('orders.show', $fo->order->order_number),
        ]);
    }

    /**
     * Send Shipment / Tracking update.
     */
    public function sendShipmentUpdate(Shipment $shipment, string $status): void
    {
        $user = $shipment->fulfilmentOrder?->order?->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'shipment_update', [
            'awb_code' => $shipment->awb_number,
            'courier' => $shipment->courier_name,
            'status_label' => ucwords(str_replace('_', ' ', $status)),
            'action_url' => route('orders.track', [
                $shipment->fulfilmentOrder->order->order_number,
                $shipment->fulfilmentOrder->fulfilment_order_number,
            ]),
        ]);
    }

    /**
     * Send Out for Delivery Alert.
     */
    public function sendOutForDelivery(FulfilmentOrder $fo): void
    {
        $user = $fo->order?->customer?->user;
        if (!$user) return;

        $shipment = $fo->shipment;

        $this->dispatch($user, 'out_for_delivery', [
            'order_number' => $fo->fulfilment_order_number,
            'courier_name' => $shipment?->courier ?? 'Shiprocket Courier',
            'rider_name' => $shipment?->courier ?? 'Delivery Executive',
            'awb' => $shipment?->awb ?? '',
            'eta' => 'Today',
            'action_url' => route('orders.track', [$fo->order->order_number, $fo->fulfilment_order_number]),
            'action_text' => 'Track Shipment',
        ]);
    }

    /**
     * Send Delivered Confirmation.
     */
    public function sendDelivered(FulfilmentOrder $fo): void
    {
        $user = $fo->order?->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'delivered', [
            'order_number' => $fo->fulfilment_order_number,
            'action_url' => route('orders.show', $fo->order->order_number),
        ]);
    }

    /**
     * Send Return / Refund status update.
     */
    public function sendReturnRefund(OrderReturn $return, string $status): void
    {
        $user = $return->order?->customer?->user;
        if (!$user) return;

        $this->dispatch($user, 'return_refund', [
            'order_number' => $return->order->order_number,
            'status' => ucwords(str_replace('_', ' ', $status)),
            'amount' => number_format((float) $return->refund_amount, 2),
            'action_url' => route('orders.return.track', [$return->order->order_number, $return->return_number]),
        ]);
    }

    /**
     * Send New Order alert to merchant/seller.
     */
    public function sendSellerNewOrder(FulfilmentOrder $fo): void
    {
        $this->notifySellerNewOrder($fo);
    }

    /**
     * Send Low Stock alert to merchant.
     */
    public function sendLowStockAlert(ProductVariant $variant, int $remainingStock): void
    {
        $inventory = Inventory::where('variant_id', $variant->id)->first();
        if ($inventory) {
            $this->notifySellerLowStock($inventory);
        }
    }

    /**
     * Send Settlement Paid alert to merchant.
     */
    public function sendSettlementPaid(SellerSettlement $settlement, string $utr): void
    {
        $this->notifySellerSettlementUpdate($settlement, 'paid', $utr);
    }

    /**
     * Send Promotional notification — ONLY if user has stored consent.
     */
    public function sendPromotional(User $user, string $message, array $vars = []): bool
    {
        if (!$user->hasPromotionalConsent()) {
            return false;
        }

        $this->dispatch($user, 'promotional', array_merge([
            'message' => $message,
            'coupon' => $vars['coupon'] ?? 'SAVE20',
            'discount' => $vars['discount'] ?? '20%',
        ], $vars), ['mail', 'whatsapp', 'database']);

        return true;
    }

    /**
     * Retry a failed notification dispatch.
     */
    public function retryFailedLog(NotificationLog $log): bool
    {
        if (!$log->isFailed()) {
            return false;
        }

        try {
            $log->retry_count += 1;

            $success = true;
            $error = null;

            if ($log->channel === 'sms') {
                $driver = app(SmsGatewayInterface::class);
                $res = $driver->send($log->recipient, $log->content ?? '', ['event_key' => $log->event_key]);
                $success = (bool) ($res['success'] ?? false);
                $error = $res['error'] ?? null;
            } elseif ($log->channel === 'whatsapp') {
                $driver = app(WhatsAppGatewayInterface::class);
                $res = $driver->send($log->recipient, $log->content ?? '', ['event_key' => $log->event_key]);
                $success = (bool) ($res['success'] ?? false);
                $error = $res['error'] ?? null;
            } elseif ($log->channel === 'push') {
                $driver = app(PushGatewayInterface::class);
                $res = $driver->send($log->recipient, $log->event_key, $log->content ?? '', ['event_key' => $log->event_key]);
                $success = (bool) ($res['success'] ?? false);
                $error = $res['error'] ?? null;
            } elseif ($log->channel === 'mail') {
                try {
                    \Illuminate\Support\Facades\Mail::raw($log->content ?? '', function ($m) use ($log) {
                        $m->to($log->recipient)->subject($log->subject ?? ('Notification from ' . (function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE')));
                    });
                    $success = true;
                } catch (\Throwable $e) {
                    $success = false;
                    $error = $e->getMessage();
                }
            }

            if ($success) {
                $log->status = 'sent';
                $log->sent_at = now();
                $log->error_message = null;
            } else {
                $log->status = 'failed';
                $log->error_message = $error ?? 'Failed on retry';
            }
            $log->save();

            Log::info("NotificationService: Retried notification log #{$log->id} for {$log->recipient} ({$log->channel}) - status: {$log->status}");

            return $success;
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error_message = "Retry error: {$e->getMessage()}";
            $log->save();
            return false;
        }
    }
}
