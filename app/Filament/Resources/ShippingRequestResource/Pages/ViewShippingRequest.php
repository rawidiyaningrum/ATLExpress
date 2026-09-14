<?php

namespace App\Filament\Resources\ShippingRequestResource\Pages;

use App\Filament\Resources\ShippingRequestResource;
use App\Models\Shipment;
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
                ->modalHeading('Pindahkan ke Shipment')
                ->modalSubmitActionLabel('Pindahkan & Buat Pengiriman')
                ->action(function (array $data): void {
                    $record = $this->record;

                    $record->update([
                        'status' => 'shipped',
                        'final_tariff' => $data['final_tariff'],
                        'final_dimensions' => $data['final_dimensions'],
                        'final_weight' => $data['final_weight'],
                    ]);

                    Shipment::create([
                        'tracking_number' => ShippingRequestResource::generateTrackingNumber(),
                        'sender_name' => $record->name,
                        'receiver_name' => null,
                        'origin' => $record->origin,
                        'destination' => $record->destination,
                        'weight' => $data['final_weight'],
                        'status' => 'pending',
                        'shipping_request_id' => $record->id,
                        'final_tariff' => $data['final_tariff'],
                        'final_dimensions' => $data['final_dimensions'],
                    ]);
                })
                ->visible(fn () => $this->record->status === 'checking'),
        ];
    }
}