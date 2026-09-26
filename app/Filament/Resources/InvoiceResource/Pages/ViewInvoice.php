<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ShipmentResource\Pages\PrintInvoice as PrintShipmentInvoice;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => PrintShipmentInvoice::getUrl([
                    'record' => $this->record->shipment_id,
                ])),
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status !== 'final'),
        ];
    }
}
