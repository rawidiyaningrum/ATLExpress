<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Models\Shipment;
use App\Services\ShipmentTrackingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateShipmentTracking extends CreateRecord
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected static ?string $title = 'Input Posisi Pengiriman';

    protected function handleRecordCreation(array $data): Model
    {
        $shipment = Shipment::findOrFail($data['shipment_id']);

        return app(ShipmentTrackingService::class)->recordStatus(
            $shipment,
            $data['status'],
            $data['location'] ?? null,
            auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
