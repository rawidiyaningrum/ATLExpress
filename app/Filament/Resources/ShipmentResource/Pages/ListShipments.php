<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Invoice terakhir di-eager load supaya tombol invoice di setiap baris
     * tidak memicu query satu per shipment.
     */
    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('latestInvoice');
    }
}
