<?php

declare(strict_types=1);

namespace App\Services\Cargo;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ShiprocketClient
{
    protected string $baseUrl;
    protected string $email;
    protected string $password;
    protected string $apiKey;
    protected bool $enabled;
    protected bool $fake;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (Setting::get('shiprocket_base_url') ?: config('services.shiprocket.base_url', 'https://apiv2.shiprocket.in/v1/external')), '/');
        $this->email = (string) (Setting::get('shiprocket_email') ?: config('services.shiprocket.email', ''));
        $this->password = (string) (Setting::get('shiprocket_password') ?: config('services.shiprocket.password', ''));
        $this->apiKey = (string) (Setting::get('shiprocket_api_key') ?: config('services.shiprocket.api_key', ''));

        $settingEnabled = Setting::get('shiprocket_enabled');
        $this->enabled = $settingEnabled !== null ? (bool) $settingEnabled : (bool) config('services.shiprocket.is_enabled', false);

        $settingFake = Setting::get('shiprocket_fake');
        $this->fake = $settingFake !== null ? (bool) $settingFake : (bool) config('services.shiprocket.fake', true);

        $this->timeout = (int) config('services.shiprocket.timeout', 20);
    }

    /**
     * Check if Shiprocket integration is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if fake / simulation mode is active.
     */
    public function isFakeMode(): bool
    {
        return $this->fake || (empty($this->apiKey) && (empty($this->email) || empty($this->password)));
    }

    /**
     * Override fake mode for testing.
     */
    public function setFakeMode(bool $fake): self
    {
        $this->fake = $fake;
        return $this;
    }

    /**
     * Get or generate a valid Shiprocket JWT Bearer Token.
     * Caches the token for 7 days (Shiprocket tokens are valid for 10 days).
     */
    public function authenticate(): string
    {
        // If a direct static API token/key is provided in settings, use it directly
        if (!empty($this->apiKey)) {
            return $this->apiKey;
        }

        if ($this->isFakeMode()) {
            return 'sr_fake_jwt_token_' . md5($this->email ?: 'allzone_sandbox');
        }

        if (empty($this->email) || empty($this->password)) {
            throw new RuntimeException('Shiprocket email and password are required for authentication.');
        }

        $cacheKey = 'shiprocket_bearer_token_' . md5($this->email);

        return Cache::remember($cacheKey, now()->addDays(7), function () {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/auth/login", [
                    'email' => $this->email,
                    'password' => $this->password,
                ]);

            if (!$response->successful()) {
                Log::error('Shiprocket authentication failed', [
                    'status' => $response->status(),
                    'error' => $response->json('message') ?? 'Unknown error',
                ]);

                throw new RuntimeException('Failed to authenticate with Shiprocket API: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
            }

            $token = $response->json('token');
            if (empty($token)) {
                throw new RuntimeException('Shiprocket authentication succeeded but no token was returned.');
            }

            return (string) $token;
        });
    }

    /**
     * Authenticated HTTP request helper.
     */
    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $token = $this->authenticate();

        return Http::timeout($this->timeout)
            ->acceptJson()
            ->withToken($token);
    }

    /**
     * Check courier serviceability and rates for origin/destination pincodes.
     *
     * @param string $originPincode
     * @param string $destPincode
     * @param float $weightKg
     * @param bool $isCod
     * @return array<string, mixed>
     */
    public function checkServiceability(string $originPincode, string $destPincode, float $weightKg, bool $isCod = false): array
    {
        if ($this->isFakeMode()) {
            $baseRate = max(40.0, round(45.0 + ($weightKg * 20.0), 2));
            return [
                'success' => true,
                'status' => 200,
                'data' => [
                    'available_courier_companies' => [
                        [
                            'courier_company_id' => 1,
                            'courier_name' => 'Delhivery Surface',
                            'rate' => $baseRate,
                            'etd' => '3-4 Days',
                            'is_cod' => $isCod,
                            'rating' => 4.4,
                        ],
                        [
                            'courier_company_id' => 2,
                            'courier_name' => 'Blue Dart Air',
                            'rate' => round($baseRate * 1.35, 2),
                            'etd' => '1-2 Days',
                            'is_cod' => $isCod,
                            'rating' => 4.7,
                        ],
                        [
                            'courier_company_id' => 3,
                            'courier_name' => 'DTDC Express',
                            'rate' => round($baseRate * 1.1, 2),
                            'etd' => '2-3 Days',
                            'is_cod' => $isCod,
                            'rating' => 4.2,
                        ],
                        [
                            'courier_company_id' => 4,
                            'courier_name' => 'Shadowfax Surface',
                            'rate' => round($baseRate * 0.95, 2),
                            'etd' => '3-5 Days',
                            'is_cod' => $isCod,
                            'rating' => 4.1,
                        ],
                    ],
                    'recommended_courier_company_id' => 1,
                ],
            ];
        }

        $params = [
            'pickup_postcode' => $originPincode,
            'delivery_postcode' => $destPincode,
            'weight' => max(0.1, $weightKg),
            'cod' => $isCod ? 1 : 0,
        ];

        $response = $this->request()->get("{$this->baseUrl}/courier/serviceability/", $params);

        if (!$response->successful()) {
            Log::error('Shiprocket serviceability query failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket serviceability check failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Create an adhoc / custom order consignment in Shiprocket.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        if ($this->isFakeMode()) {
            $orderId = rand(1000000, 9999999);
            $shipmentId = rand(1000000, 9999999);
            $awb = 'SR-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            return [
                'success' => true,
                'order_id' => $orderId,
                'shipment_id' => $shipmentId,
                'status' => 'NEW',
                'status_code' => 1,
                'onboarding_completed_now' => 0,
                'awb_code' => $awb,
                'courier_company_id' => 1,
                'courier_name' => 'Delhivery Surface',
                'label_url' => "https://shiprocket.co/label/{$shipmentId}.pdf",
                'raw' => [
                    'simulated' => true,
                    'order_id' => $orderId,
                    'shipment_id' => $shipmentId,
                ],
            ];
        }

        $response = $this->request()->post("{$this->baseUrl}/orders/create/adhoc", $payload);

        if (!$response->successful()) {
            Log::error('Shiprocket order creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket order creation failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Assign or generate Air Waybill (AWB) for a Shiprocket shipment.
     *
     * @param int|string $shipmentId
     * @param int|null $courierId
     * @return array<string, mixed>
     */
    public function generateAwb(int|string $shipmentId, ?int $courierId = null): array
    {
        if ($this->isFakeMode()) {
            $awb = 'SR-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            return [
                'success' => true,
                'response' => [
                    'data' => [
                        'awb_code' => $awb,
                        'courier_company_id' => $courierId ?: 1,
                        'courier_name' => 'Delhivery Surface',
                        'shipment_id' => $shipmentId,
                        'applied_weight' => 0.5,
                        'charges' => 65.00,
                    ],
                ],
                'awb_code' => $awb,
                'courier_name' => 'Delhivery Surface',
            ];
        }

        $payload = [
            'shipment_id' => $shipmentId,
        ];

        if ($courierId) {
            $payload['courier_id'] = $courierId;
        }

        $response = $this->request()->post("{$this->baseUrl}/courier/assign/awb", $payload);

        if (!$response->successful()) {
            Log::error('Shiprocket AWB generation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket AWB generation failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        $data = $response->json();
        $awbCode = $data['response']['data']['awb_code'] ?? null;
        $courierName = $data['response']['data']['courier_name'] ?? null;

        return [
            'success' => true,
            'response' => $data,
            'awb_code' => $awbCode,
            'courier_name' => $courierName,
        ];
    }

    /**
     * Generate printable PDF shipping label.
     *
     * @param int|string|array<int|string> $shipmentIds
     * @return array<string, mixed>
     */
    public function generateLabel(int|string|array $shipmentIds): array
    {
        $ids = is_array($shipmentIds) ? $shipmentIds : [$shipmentIds];

        if ($this->isFakeMode()) {
            $firstId = $ids[0] ?? rand(100000, 999999);
            return [
                'label_created' => 1,
                'label_url' => "https://shiprocket.co/label/{$firstId}.pdf",
                'not_created' => [],
            ];
        }

        $response = $this->request()->post("{$this->baseUrl}/courier/generate/label", [
            'shipment_id' => $ids,
        ]);

        if (!$response->successful()) {
            Log::error('Shiprocket label generation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket label generation failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Schedule carrier physical pickup from warehouse.
     *
     * @param int|string|array<int|string> $shipmentIds
     * @param string|null $pickupDate (Format: YYYY-MM-DD)
     * @return array<string, mixed>
     */
    public function schedulePickup(int|string|array $shipmentIds, ?string $pickupDate = null): array
    {
        $ids = is_array($shipmentIds) ? $shipmentIds : [$shipmentIds];
        $date = $pickupDate ?: date('Y-m-d');

        if ($this->isFakeMode()) {
            return [
                'pickup_status' => 1,
                'response' => [
                    'pickup_scheduled_date' => $date,
                    'pickup_token_number' => 'SR-PKP-' . strtoupper(Str::random(8)),
                    'status' => 'Scheduled',
                ],
            ];
        }

        $response = $this->request()->post("{$this->baseUrl}/courier/generate/pickup", [
            'shipment_id' => $ids,
            'pickup_date' => [$date],
        ]);

        if (!$response->successful()) {
            Log::error('Shiprocket pickup scheduling failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket pickup scheduling failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Track shipment milestone status by AWB.
     *
     * @param string $awb
     * @return array<string, mixed>
     */
    public function track(string $awb): array
    {
        if ($this->isFakeMode()) {
            return [
                'tracking_data' => [
                    'track_status' => 1,
                    'shipment_status' => 6, // In transit
                    'shipment_track' => [
                        [
                            'id' => 101,
                            'awb_code' => $awb,
                            'current_status' => 'In Transit',
                            'scans' => [
                                [
                                    'date' => now()->subDay()->toDateTimeString(),
                                    'status' => 'Manifest Created',
                                    'activity' => 'Consignment details received electronically',
                                    'location' => 'Bengaluru Hub',
                                ],
                                [
                                    'date' => now()->subHours(12)->toDateTimeString(),
                                    'status' => 'Picked Up',
                                    'activity' => 'Shipment picked up by courier',
                                    'location' => 'Bengaluru Hub',
                                ],
                                [
                                    'date' => now()->subHours(4)->toDateTimeString(),
                                    'status' => 'In Transit',
                                    'activity' => 'Bagged and departed transit sort facility',
                                    'location' => 'Regional Logistics Center',
                                ],
                            ],
                            'destination' => 'Destination City',
                            'origin' => 'Bengaluru',
                            'expected_date' => now()->addDays(2)->toDateString(),
                        ],
                    ],
                ],
            ];
        }

        $response = $this->request()->get("{$this->baseUrl}/courier/track/awb/{$awb}");

        if (!$response->successful()) {
            Log::error('Shiprocket track query failed', [
                'awb' => $awb,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket tracking query failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Cancel an active consignment in Shiprocket by AWB.
     *
     * @param string|array<string> $awbs
     * @return array<string, mixed>
     */
    public function cancelShipment(string|array $awbs): array
    {
        $awbList = is_array($awbs) ? $awbs : [$awbs];

        if ($this->isFakeMode()) {
            return [
                'status' => 200,
                'message' => 'Shipment cancelled successfully',
                'cancelled_awbs' => $awbList,
            ];
        }

        $response = $this->request()->post("{$this->baseUrl}/orders/cancel/shipment/awbs", [
            'awbs' => $awbList,
        ]);

        if (!$response->successful()) {
            Log::error('Shiprocket shipment cancellation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket shipment cancellation failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }

    /**
     * Create a customer reverse pickup return order in Shiprocket.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createReturnOrder(array $payload): array
    {
        if ($this->isFakeMode()) {
            $returnOrderId = rand(100000, 999999);
            $returnShipmentId = rand(100000, 999999);
            $returnAwb = 'SR-RET-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            return [
                'success' => true,
                'order_id' => $returnOrderId,
                'shipment_id' => $returnShipmentId,
                'status' => 'RETURN_INITIATED',
                'awb_code' => $returnAwb,
                'courier_name' => 'Delhivery Return Surface',
            ];
        }

        $response = $this->request()->post("{$this->baseUrl}/orders/create/return", $payload);

        if (!$response->successful()) {
            Log::error('Shiprocket return order creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new RuntimeException('Shiprocket return order creation failed: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }

        return $response->json();
    }
}
