<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Services\QuickCommerce\QuickCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickCommerceController extends Controller
{
    public function __construct(
        protected QuickCommerceService $quickService
    ) {}

    /**
     * Quick Commerce Discovery Portal (/quick) - Unified to Electronics Catalog.
     */
    public function index(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('search');
    }

    /**
     * API: Nearby Quick Commerce Stores with dynamic ETA.
     */
    public function nearbyStoresApi(Request $request): JsonResponse
    {
        $lat = (float) $request->query('lat', setting('default_lat', 12.9716));
        $lng = (float) $request->query('lng', setting('default_lng', 77.5946));
        $radius = $request->has('radius') ? (float) $request->query('radius') : null;

        $stores = $this->quickService->getNearbyQuickStores($lat, $lng, $radius);

        return response()->json([
            'status' => 'success',
            'count' => $stores->count(),
            'data' => $stores,
        ]);
    }

    /**
     * Live Customer Order Tracking Page (/orders/{order}/live-tracking) - Redirects to Shiprocket tracking.
     */
    public function liveTracking(Request $request, Order $order): \Illuminate\Http\RedirectResponse
    {
        $fo = $order->fulfilmentOrders()->first();
        if ($fo) {
            return redirect()->route('orders.track', [$order->order_number, $fo->fulfilment_order_number]);
        }
        return redirect()->route('orders.show', $order->order_number);
    }

    /**
     * API: Real-time Telemetry & Order Status for Map Live Tracking.
     */
    public function liveLocationApi(Request $request, Order $order): JsonResponse
    {
        $quickFulfilment = $order->fulfilmentOrders()
            ->with(['store.location', 'shipment.tracking'])
            ->first();

        if (!$quickFulfilment) {
            return response()->json(['error' => 'No active fulfilment package found'], 404);
        }

        $store = $quickFulfilment->store;
        $storeLoc = $store?->location;
        $shipment = $quickFulfilment->shipment;
        $address = $order->customerAddress;

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'package_status' => $quickFulfilment->status,
            'estimated_delivery_at' => $quickFulfilment->estimated_delivery_at?->toIso8601String(),
            'store' => [
                'name' => setting('name', 'ALLZONE') . ' Fulfillment Hub',
                'lat' => $storeLoc?->lat ? (float) $storeLoc->lat : 12.9716,
                'lng' => $storeLoc?->lng ? (float) $storeLoc->lng : 77.5946,
                'address' => $storeLoc?->address ?? '',
            ],
            'customer' => [
                'lat' => $address?->lat ? (float) $address->lat : 12.9352,
                'lng' => $address?->lng ? (float) $address->lng : 77.6245,
                'address' => $address?->line1 ?? '',
            ],
            'courier' => $shipment ? [
                'name' => $shipment->courier ?? 'Shiprocket Courier',
                'awb' => $shipment->awb,
                'status' => $shipment->status?->value ?? (string) $shipment->status,
            ] : null,
        ]);
    }
}
