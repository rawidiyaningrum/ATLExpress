<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentLog;

class ShipmentTrackingService
{
    /**
     * Pelacakan publik memakai satu nomor yang sama dengan nomor AWB internally.
     */
    public function trackByAwb(string $awbNumber): ?Shipment
    {
        return Shipment::with('logs')->where('awb_number', $awbNumber)->first();
    }

    public function getLatestStatus(string $awbNumber): ?string
    {
        return $this->trackByAwb($awbNumber)?->status;
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
