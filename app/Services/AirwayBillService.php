<?php

namespace App\Services;

use App\Models\Shipment;
use App\Support\NumberGenerator;

class AirwayBillService
{
    /**
     * Menerbitkan nomor AWB, mengaktifkan pengiriman, dan mencatat riwayatnya.
     *
     * Idempoten: pengiriman yang sudah punya AWB atau sudah dibatalkan tidak
     * disentuh, sehingga aman dipanggil dari wizard maupun aksi halaman edit.
     */
    public function issue(Shipment $shipment): Shipment
    {
        if ($shipment->awb_number !== null || $shipment->status === Shipment::STATUS_CANCELLED) {
            return $shipment;
        }

        return app(NumberGenerator::class)->persistWithRetry(
            'awbNumber',
            function (string $awbNumber) use ($shipment): Shipment {
                $shipment->forceFill([
                    'awb_number' => $awbNumber,
                    'status' => Shipment::STATUS_IN_TRANSIT,
                ])->save();

                $shipment->logs()->create([
                    'status_description' => 'Nomor AWB diterbitkan, pengiriman aktif',
                    'location' => $shipment->origin,
                    'timestamp' => now(),
                ]);

                return $shipment;
            },
        );
    }
}
