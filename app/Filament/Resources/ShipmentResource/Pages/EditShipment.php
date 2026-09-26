<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('invoice')
                ->label(fn (): string => ShipmentResource::invoiceActionLabel($this->record))
                ->icon('heroicon-o-receipt-percent')
                ->color('gray')
                ->visible(fn (): bool => $this->record !== null && ShipmentResource::canShowInvoiceAction($this->record))
                ->url(fn (): string => ShipmentResource::invoiceActionUrl($this->record)),
            Actions\Action::make('printAwb')
                ->label('Cetak Airway Bill')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn (): bool => filled($this->record?->awb_number))
                ->url(fn (): string => PrintAirwayBill::getUrl(['record' => $this->record])),
            Actions\DeleteAction::make(),
        ];
    }
}
