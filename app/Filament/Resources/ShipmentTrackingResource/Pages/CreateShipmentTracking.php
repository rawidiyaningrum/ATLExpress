<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Pages;

use App\Filament\Resources\ShipmentTrackingResource;
use App\Models\Shipment;
use App\Services\ShipmentTrackingService;
use App\Services\TariffCalculatorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CreateShipmentTracking extends CreateRecord
{
    protected static string $resource = ShipmentTrackingResource::class;

    protected static ?string $title = 'Input Posisi Pengiriman';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('shipment_id')
                    ->label('Pengiriman (No. AWB)')
                    ->options(function (): array {
                        return Shipment::query()
                            ->orderByDesc('created_at')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Shipment $record): array => [$record->id => trim("{$record->awb_number} · ".ShipmentTrackingResource::shipmentSummary($record))])
                            ->all();
                    })
                    ->searchable()
                    ->required()
                    ->helperText('Diurutkan dari yang terbaru.'),
                Forms\Components\Select::make('location')
                    ->label('Lokasi (Kabupaten)')
                    ->options(fn (): array => app(TariffCalculatorService::class)->getAllKabupatenOptions())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status Pengiriman')
                    ->options(Shipment::STATUS_LABELS)
                    ->default(Shipment::STATUS_IN_TRANSIT)
                    ->required(),
            ])
            ->columns(1);
    }

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
