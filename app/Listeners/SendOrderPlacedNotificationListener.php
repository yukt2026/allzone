<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\OrderPlacedEvent;
use App\Models\Order;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderPlacedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event and notify customer, admins, and merchants.
     */
    public function handle(OrderPlaced|OrderPlacedEvent $event): void
    {
        $order = null;

        if ($event instanceof OrderPlaced) {
            $order = $event->order;
        } elseif ($event instanceof OrderPlacedEvent) {
            $order = Order::with(['fulfilmentOrders.seller.user', 'customer.user'])->find($event->orderId);
        }

        if (!$order) {
            return;
        }

        try {
            // 1. Notify Customer
            $this->notificationService->sendOrderConfirmation($order);

            // 2. Notify Platform Admins
            $this->notificationService->notifyAdminsNewOrder($order);

            // 3. Notify Each Merchant Package Owner
            foreach ($order->fulfilmentOrders as $fo) {
                $this->notificationService->notifySellerNewOrder($fo);
            }
        } catch (\Throwable $e) {
            Log::error("SendOrderPlacedNotificationListener: Failed to dispatch notifications for Order #{$order->id}: " . $e->getMessage());
        }
    }
}
