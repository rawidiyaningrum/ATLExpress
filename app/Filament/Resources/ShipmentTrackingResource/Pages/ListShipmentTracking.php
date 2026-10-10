<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListShipmentTracking extends ListRecords
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected static ?string $title = 'Update Posisi Pengiriman';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Input Posisi'),
        ];
    }

    /**
     * Shipment dan tracker di-eager load supaya kolom nomor AWB dan penginput
     * tidak memicu query per baris.
     */
    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['shipment', 'tracker']);
    }
}
