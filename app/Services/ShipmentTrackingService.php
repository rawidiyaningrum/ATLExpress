<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentLog;

class ShipmentTrackingService
{
    public function trackByNumber(string $trackingNumber): ?Shipment
    {
        return Shipment::with('logs')->where('tracking_number', $trackingNumber)->first();
    }

    public function getLatestStatus(string $trackingNumber): ?string
    {
        $shipment = $this->trackByNumber($trackingNumber);
        return $shipment?->status;
    }

    public function createShipment(array $data): Shipment
    {
        return Shipment::create($data);
    }

    public function addLog(Shipment $shipment, string $description, ?string $location = null): ShipmentLog
    {
        return $shipment->logs()->create([
            'status_description' => $description,
            'location' => $location,
            'timestamp' => now(),
        ]);
    }
}