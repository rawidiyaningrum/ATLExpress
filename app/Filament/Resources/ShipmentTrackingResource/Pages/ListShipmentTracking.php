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
     * Eager load logs dan tracker supaya render tracking efisien.
     * Urut berdasarkan tracking terakhir yang diinput.
     */
    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['logs.tracker'])
            ->whereHas('logs')
            ->withMax('logs as last_tracking_at', 'timestamp')
            ->orderByDesc('last_tracking_at')
            ->orderByDesc('awb_number');
    }
}
