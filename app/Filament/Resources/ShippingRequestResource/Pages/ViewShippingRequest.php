<?php

namespace App\Filament\Resources\ShippingRequestResource\Pages;

use App\Filament\Resources\ShippingRequestResource;
use App\Models\ShippingRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingRequest extends ViewRecord
{
    protected static string $resource = ShippingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('contacted')
                ->label('Tandai Dihubungi')
                ->icon('heroicon-o-phone')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'contacted']))
                ->visible(fn () => $this->record->status === 'new'),
            Actions\Action::make('checking')
                ->label('Mulai Pengecekan / Visiting')
                ->icon('heroicon-o-magnifying-glass')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'checking']))
                ->visible(fn () => $this->record->status === 'contacted'),
            Actions\Action::make('moveToShipment')
                ->label('Pindahkan ke Shipment')
                ->icon('heroicon-o-truck')
                ->form(ShippingRequestResource::getShipmentTransferSchema())
                ->modalHeading('Input Tarif Akhir & AWB')
                ->modalSubmitActionLabel('Pindahkan & Buat Pengiriman')
                ->action(function (array $data): void {
                    ShippingRequestResource::transferToShipment($this->record, $data);
                })
                ->visible(fn () => $this->record->status === 'checking'),
        ];
    }
}