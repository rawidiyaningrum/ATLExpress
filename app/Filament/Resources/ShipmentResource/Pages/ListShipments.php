<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Actions;
use Filament\Resources\Components\Tab;
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
     * Tab status terakhir, mengikuti pola tab status di menu invoice.
     *
     * Pengiriman yang sedang berjalan digabung ke satu tab "Progress" supaya
     * operator fokus ke pekerjaan aktif; draft dan cancelled tetap bisa dilihat
     * lewat tab "Semua".
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'progress' => Tab::make('Progress')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [
                    Shipment::STATUS_PENDING,
                    Shipment::STATUS_IN_TRANSIT,
                ])),
            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Shipment::STATUS_DELIVERED)),
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
