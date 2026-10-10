<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentLog;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    /**
     * Mencatat posisi/status pengiriman dari user tracker dan menyinkronkan
     * status shipment dengan log terbaru.
     */
    public function recordStatus(Shipment $shipment, string $status, ?string $location = null, ?int $trackerUserId = null): ShipmentLog
    {
        if (! $shipment->isActive()) {
            throw new HttpException(403, 'Pengiriman tidak aktif, posisi tidak bisa diinput.');
        }

        $label = Shipment::statusLabel($status);

        $log = $shipment->logs()->create([
            'status' => $status,
            'status_description' => $label,
            'location' => $location,
            'tracker_user_id' => $trackerUserId,
            'timestamp' => now(),
        ]);

        $shipment->update(['status' => $status]);

        return $log;
    }
}
