<?php

declare(strict_types=1);

namespace App\Services\Cargo;

use App\Models\FulfilmentOrder;
use App\Models\NdrCase;
use App\Models\OrderReturn;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Cargo\Contracts\LogisticsProvider;
use InvalidArgumentException;

class LogisticsManager implements LogisticsProvider
{
    /** @var array<string, LogisticsProvider> */
    protected array $drivers = [];

    public function __construct(
        protected OneStallCargoProvider $cargoProvider,
        protected ShiprocketLogisticsProvider $shiprocketProvider
    ) {
        $this->drivers['cargo'] = $this->cargoProvider;
        $this->drivers['onestall_cargo'] = $this->cargoProvider;
        $this->drivers['allzone_cargo'] = $this->cargoProvider;
        $this->drivers['shiprocket'] = $this->shiprocketProvider;
    }

    /**
     * Get a specific driver or active default.
     */
    public function driver(?string $name = null): LogisticsProvider
    {
        $key = strtolower((string) ($name ?: $this->getDefaultDriver()));

        if (!isset($this->drivers[$key])) {
            return $this->shiprocketProvider;
        }

        return $this->drivers[$key];
    }

    /**
     * Get configured active shipping logistics provider.
     */
    public function getDefaultDriver(): string
    {
        return 'shiprocket';
    }

    /**
     * Resolve provider based on shipment record.
     */
    protected function resolveForShipment(Shipment $shipment): LogisticsProvider
    {
        if (strtolower((string) $shipment->provider) === 'shiprocket') {
            return $this->shiprocketProvider;
        }

        return $this->driver();
    }

    /* ==========================================================
       LogisticsProvider Interface Delegation
       ========================================================== */

    public function createShipment(FulfilmentOrder $fulfilmentOrder, array $options = []): array
    {
        $providerName = $options['provider'] ?? $this->getDefaultDriver();
        return $this->driver($providerName)->createShipment($fulfilmentOrder, $options);
    }

    public function generateAwb(Shipment $shipment): array
    {
        return $this->resolveForShipment($shipment)->generateAwb($shipment);
    }

    public function selectCourierRates(array $params): array
    {
        $providerName = $params['provider'] ?? $this->getDefaultDriver();
        return $this->driver($providerName)->selectCourierRates($params);
    }

    public function requestPickup(Shipment $shipment, array $pickupDetails = []): array
    {
        return $this->resolveForShipment($shipment)->requestPickup($shipment, $pickupDetails);
    }

    public function track(string $awb): array
    {
        $shipment = Shipment::where('awb', $awb)->first();
        if ($shipment) {
            return $this->resolveForShipment($shipment)->track($awb);
        }

        return $this->driver()->track($awb);
    }

    public function cancelShipment(Shipment $shipment, string $reason = ''): array
    {
        return $this->resolveForShipment($shipment)->cancelShipment($shipment, $reason);
    }

    public function createReversePickup(OrderReturn $orderReturn): array
    {
        return $this->driver()->createReversePickup($orderReturn);
    }

    public function fetchNdrActions(Shipment $shipment): array
    {
        return $this->resolveForShipment($shipment)->fetchNdrActions($shipment);
    }

    public function submitNdrAction(NdrCase $ndrCase, string $action, array $params = []): array
    {
        if ($ndrCase->shipment) {
            return $this->resolveForShipment($ndrCase->shipment)->submitNdrAction($ndrCase, $action, $params);
        }

        return $this->driver()->submitNdrAction($ndrCase, $action, $params);
    }

    public function fetchCodRemittanceData(array $filters = []): array
    {
        return $this->driver()->fetchCodRemittanceData($filters);
    }
}
