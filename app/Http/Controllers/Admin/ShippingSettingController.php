<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Cargo\TrackingSyncJob;
use App\Models\FulfilmentOrder;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Admin\AdminShippingService;
use App\Services\Cargo\ShiprocketClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShippingSettingController extends Controller
{
    public function __construct(
        protected AdminShippingService $shippingService,
        protected ShiprocketClient $shiprocketClient
    ) {}

    /**
     * Display centralized Shiprocket shipping dashboard, registry, configuration, and tools.
     */
    public function index(Request $request): View
    {
        $activeTab = $request->query('view', 'registry'); // 'registry', 'pending_creation', 'config', 'serviceability', 'webhooks'
        $statusTab = $request->query('status', 'all');

        $metrics = $this->shippingService->getShippingMetrics();
        $tabCounts = $this->shippingService->getShipmentTabCounts();
        $sellers = Seller::orderBy('business_name')->get();

        // Shipments list with filters
        $shipments = $this->shippingService->buildShipmentsQuery($request->all())
            ->paginate(15)
            ->withQueryString();

        // Pending creation queue
        $pendingFulfilmentOrders = $activeTab === 'pending_creation' || $statusTab === 'creation_pending'
            ? $this->shippingService->buildCreationPendingQuery($request->all())->paginate(15)->withQueryString()
            : null;

        // Webhook logs
        $webhookLogs = $activeTab === 'webhooks'
            ? $this->shippingService->getWebhookLogs(25)
            : null;

        // Configuration settings
        $settings = [
            'shiprocket_enabled' => (bool) (Setting::get('shiprocket_enabled') ?? config('services.shiprocket.is_enabled', true)),
            'shiprocket_fake' => (bool) (Setting::get('shiprocket_fake') ?? config('services.shiprocket.fake', true)),
            'shiprocket_base_url' => Setting::get('shiprocket_base_url', config('services.shiprocket.base_url', 'https://apiv2.shiprocket.in/v1/external')),
            'shiprocket_email' => Setting::get('shiprocket_email', config('services.shiprocket.email', '')),
            'shiprocket_password_set' => !empty(Setting::get('shiprocket_password', config('services.shiprocket.password', ''))),
            'shiprocket_api_key' => Setting::get('shiprocket_api_key', config('services.shiprocket.api_key', '')),
            'shiprocket_webhook_token' => Setting::get('shiprocket_webhook_token', Setting::get('shiprocket_api_key', '')),
            'shiprocket_webhook_url' => url('/api/v1/webhooks/shiprocket'),
            // Package defaults
            'default_weight_kg' => (float) Setting::get('shipping_default_weight_kg', 0.5),
            'default_length_cm' => (float) Setting::get('shipping_default_length_cm', 15.0),
            'default_width_cm' => (float) Setting::get('shipping_default_width_cm', 10.0),
            'default_height_cm' => (float) Setting::get('shipping_default_height_cm', 5.0),
            'volumetric_divisor' => (int) Setting::get('shipping_volumetric_divisor', 5000),
            // Shipping charge rules
            'standard_base_fee' => (float) Setting::get('shipping_standard_base_fee', 40.00),
            'free_shipping_threshold' => (float) Setting::get('shipping_free_threshold', 999.00),
            'extra_kg_slab_fee' => (float) Setting::get('shipping_extra_kg_slab_fee', 30.00),
            'cod_max_limit' => (float) Setting::get('shipping_cod_max_limit', 5000.00),
            'rto_max_attempts' => (int) Setting::get('shipping_rto_max_attempts', 3),
            'auto_generate_awb' => (bool) Setting::get('shipping_auto_generate_awb', true),
        ];

        return view('admin.shipping.index', compact(
            'metrics',
            'tabCounts',
            'shipments',
            'pendingFulfilmentOrders',
            'webhookLogs',
            'settings',
            'sellers',
            'activeTab',
            'statusTab'
        ));
    }

    /**
     * Show single shipment details and tracking events.
     */
    public function showShipment(Shipment $shipment): JsonResponse|View
    {
        $shipment->load([
            'fulfilmentOrder.order.customer.user',
            'fulfilmentOrder.order.customerAddress',
            'fulfilmentOrder.seller',
            'fulfilmentOrder.store',
            'fulfilmentOrder.items.variant',
            'tracking',
            'ndrCases',
            'rtoCases',
            'codRemittance',
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'shipment' => $shipment,
            ]);
        }

        return view('admin.shipping.show', compact('shipment'));
    }

    /**
     * Create Shiprocket consignment for an eligible FulfilmentOrder.
     */
    public function createShipment(Request $request, FulfilmentOrder $fulfilmentOrder): RedirectResponse
    {
        $validated = $request->validate([
            'weight' => ['nullable', 'numeric', 'min:0.05'],
            'length' => ['nullable', 'numeric', 'min:1'],
            'breadth' => ['nullable', 'numeric', 'min:1'],
            'height' => ['nullable', 'numeric', 'min:1'],
            'courier_id' => ['nullable', 'integer'],
            'courier_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $result = $this->shippingService->createShipmentForOrder($fulfilmentOrder, $validated, Auth::user());

            $awb = $result['awb'] ?? 'Pending assignment';
            return back()->with('success', "Shiprocket consignment created successfully for package #{$fulfilmentOrder->fulfilment_order_number}! AWB: {$awb}");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to create Shiprocket consignment: " . $e->getMessage());
        }
    }

    /**
     * Live sync tracking for a specific consignment.
     */
    public function trackShipment(Shipment $shipment): RedirectResponse
    {
        try {
            $tracking = $this->shippingService->trackShipment($shipment);
            $latestStatus = $tracking['current_status'] ?? $shipment->status->label();

            return back()->with('success', "Live tracking updated for AWB {$shipment->awb}: {$latestStatus}");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to refresh tracking: " . $e->getMessage());
        }
    }

    /**
     * Cancel an active consignment in Shiprocket before courier pickup.
     */
    public function cancelShipment(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->shippingService->cancelShipment($shipment, $validated['reason'], Auth::user());

            return back()->with('success', "Shipment #{$shipment->shipment_number} (AWB: {$shipment->awb}) cancelled successfully.");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to cancel shipment: " . $e->getMessage());
        }
    }

    /**
     * Check real-time serviceability and courier rate quotes from Shiprocket API.
     */
    public function checkServiceability(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'pickup_pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'delivery_pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.05', 'max:100'],
            'is_cod' => ['nullable', 'boolean'],
        ]);

        $weightKg = (float) ($validated['weight_kg'] ?? 0.5);
        $isCod = (bool) ($validated['is_cod'] ?? false);

        try {
            $result = $this->shippingService->checkServiceability(
                $validated['pickup_pincode'],
                $validated['delivery_pincode'],
                $weightKg,
                $isCod
            );

            $couriers = $result['data']['available_courier_companies'] ?? [];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'pickup_pincode' => $validated['pickup_pincode'],
                    'delivery_pincode' => $validated['delivery_pincode'],
                    'weight_kg' => $weightKg,
                    'couriers_count' => count($couriers),
                    'couriers' => $couriers,
                ]);
            }

            return back()->with([
                'serviceability_results' => $couriers,
                'serviceability_query' => $validated,
                'success' => count($couriers) . " serviceable courier partner(s) found via Shiprocket for route {$validated['pickup_pincode']} -> {$validated['delivery_pincode']}.",
            ]);
        } catch (\Throwable $e) {
            $errMsg = "Serviceability check failed: " . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }
    }

    /**
     * Display failed shipment queue with retry action.
     */
    public function failedQueue(Request $request): View
    {
        $failedShipments = Shipment::query()
            ->where(function ($q) {
                $q->where('status', ShipmentStatus::FAILED)
                  ->orWhereNull('awb');
            })
            ->with(['fulfilmentOrder.seller', 'fulfilmentOrder.order'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.shipping.failed-queue', compact('failedShipments'));
    }

    /**
     * Retry failed shipment booking or AWB generation.
     */
    public function retryShipment(Shipment $shipment): RedirectResponse
    {
        try {
            $this->shippingService->retryShipment($shipment);

            return back()->with('success', "Shipment #{$shipment->shipment_number} retried successfully. AWB: {$shipment->awb}");
        } catch (\Throwable $e) {
            return back()->with('error', "Retry failed: " . $e->getMessage());
        }
    }

    /**
     * Trigger bulk tracking sync for all in-transit shipments.
     */
    public function syncAllTracking(): RedirectResponse
    {
        TrackingSyncJob::dispatch();

        return back()->with('success', 'Bulk logistics tracking synchronization job has been dispatched.');
    }

    /**
     * Update Shiprocket configuration, package dimensions, charge rules, and credentials.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shiprocket_enabled' => ['nullable', 'boolean'],
            'shiprocket_fake' => ['nullable', 'boolean'],
            'shiprocket_base_url' => ['nullable', 'string', 'max:255'],
            'shiprocket_email' => ['nullable', 'string', 'email', 'max:255'],
            'shiprocket_password' => ['nullable', 'string', 'max:255'],
            'shiprocket_api_key' => ['nullable', 'string', 'max:255'],
            'shiprocket_webhook_token' => ['nullable', 'string', 'max:255'],
            // Dimensions & weights
            'default_weight_kg' => ['required', 'numeric', 'min:0.01'],
            'default_length_cm' => ['nullable', 'numeric', 'min:1'],
            'default_width_cm' => ['nullable', 'numeric', 'min:1'],
            'default_height_cm' => ['nullable', 'numeric', 'min:1'],
            'volumetric_divisor' => ['nullable', 'integer', 'min:1000', 'max:10000'],
            // Charges & rules
            'standard_base_fee' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'extra_kg_slab_fee' => ['nullable', 'numeric', 'min:0'],
            'cod_max_limit' => ['required', 'numeric', 'min:0'],
            'rto_max_attempts' => ['required', 'integer', 'min:1', 'max:5'],
            'auto_generate_awb' => ['nullable', 'boolean'],
            'quick_max_radius_km' => ['nullable', 'numeric'],
            'courier_priorities' => ['nullable'],
        ]);

        // Credentials & toggles
        Setting::set('shiprocket_enabled', $request->boolean('shiprocket_enabled', true), 'shiprocket');
        Setting::set('shiprocket_fake', $request->boolean('shiprocket_fake', true), 'shiprocket');

        if (!empty($validated['shiprocket_base_url'])) {
            Setting::set('shiprocket_base_url', $validated['shiprocket_base_url'], 'shiprocket');
        }
        if (isset($validated['shiprocket_email'])) {
            Setting::set('shiprocket_email', $validated['shiprocket_email'], 'shiprocket');
        }

        // Only update password if a real value is provided (not empty and not masked placeholder)
        if (!empty($validated['shiprocket_password']) && !str_contains($validated['shiprocket_password'], '••••')) {
            Setting::set('shiprocket_password', $validated['shiprocket_password'], 'shiprocket');
        }

        if (isset($validated['shiprocket_api_key'])) {
            Setting::set('shiprocket_api_key', $validated['shiprocket_api_key'], 'shiprocket');
        }
        if (isset($validated['shiprocket_webhook_token'])) {
            Setting::set('shiprocket_webhook_token', $validated['shiprocket_webhook_token'], 'shiprocket');
        }

        // Package dimensions
        Setting::set('shipping_default_weight_kg', $validated['default_weight_kg'], 'shipping');
        Setting::set('shipping_default_length_cm', $validated['default_length_cm'] ?? 15.0, 'shipping');
        Setting::set('shipping_default_width_cm', $validated['default_width_cm'] ?? 10.0, 'shipping');
        Setting::set('shipping_default_height_cm', $validated['default_height_cm'] ?? 5.0, 'shipping');
        Setting::set('shipping_volumetric_divisor', $validated['volumetric_divisor'] ?? 5000, 'shipping');

        // Shipping charge rules
        Setting::set('shipping_standard_base_fee', $validated['standard_base_fee'], 'shipping');
        Setting::set('shipping_free_threshold', $validated['free_shipping_threshold'], 'shipping');
        Setting::set('shipping_extra_kg_slab_fee', $validated['extra_kg_slab_fee'] ?? 30.00, 'shipping');
        Setting::set('shipping_cod_max_limit', $validated['cod_max_limit'], 'shipping');
        Setting::set('shipping_rto_max_attempts', $validated['rto_max_attempts'], 'shipping');
        Setting::set('shipping_auto_generate_awb', $request->boolean('auto_generate_awb', true), 'shipping');

        if (isset($validated['quick_max_radius_km'])) {
            Setting::set('shipping_quick_max_radius_km', $validated['quick_max_radius_km'], 'shipping');
        }
        if (isset($validated['courier_priorities'])) {
            Setting::set('shipping_courier_priorities', (array) $validated['courier_priorities'], 'shipping');
        }

        // Cross-sync platform delivery rules
        Setting::set('standard_delivery_fee', $validated['standard_base_fee'], 'delivery');
        Setting::set('base_delivery_fee', $validated['standard_base_fee'], 'delivery');
        Setting::set('free_shipping_threshold', $validated['free_shipping_threshold'], 'delivery');
        Setting::set('free_delivery_threshold', $validated['free_shipping_threshold'], 'delivery');
        Setting::set('cod_max_limit_amount', $validated['cod_max_limit'], 'payment');

        if (function_exists('activity')) {
            activity('settings')
                ->causedBy(Auth::user())
                ->log("Admin updated Shiprocket shipping settings and rates");
        }

        return back()->with('success', 'Shiprocket logistics and shipping rules updated successfully.');
    }

    /**
     * Test Shiprocket API connectivity and authentication.
     */
    public function testShiprocket(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $token = $this->shiprocketClient->authenticate();
            $mode = $this->shiprocketClient->isFakeMode() ? 'Sandbox / Simulation' : 'Live Production';

            // Check sample serviceability
            $rates = $this->shiprocketClient->checkServiceability('560038', '560001', 0.5, false);
            $couriers = $rates['data']['available_courier_companies'] ?? [];
            $courierCount = count($couriers);

            $msg = "Shiprocket connection verified! Mode: {$mode}. Available couriers found: {$courierCount}. Auth token active.";

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $msg,
                    'mode' => $mode,
                    'couriers' => $couriers,
                ]);
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            $err = "Shiprocket connection test failed: " . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $err], 422);
            }
            return back()->with('error', $err);
        }
    }
}
