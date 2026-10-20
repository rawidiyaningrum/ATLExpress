<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\AirwayBillService;
use Filament\Actions;
use Filament\Notifications\Notification;
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
                ->visible(fn (): bool => $this->record !== null && ShipmentResource::canManageInvoices() && ShipmentResource::canShowInvoiceAction($this->record))
                ->url(fn (): string => ShipmentResource::invoiceActionUrl($this->record)),
            Actions\Action::make('issueAwb')
                ->label('Terbitkan AWB')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn (): bool => $this->record instanceof Shipment
                    && $this->record->awb_number === null
                    && $this->record->status === Shipment::STATUS_DRAFT
                    && auth()->user()?->isAdminRole() === true)
                ->requiresConfirmation()
                ->modalDescription('Nomor AWB akan diterbitkan dan pengiriman menjadi aktif.')
                ->action(function (): void {
                    app(AirwayBillService::class)->issue($this->record);

                    Notification::make()
                        ->title('Airway bill dibuat')
                        ->body("Nomor AWB: {$this->record->awb_number}. Pengiriman berstatus aktif.")
                        ->success()
                        ->send();

                    $this->redirect(PrintAirwayBill::getUrl(['record' => $this->record]));
                }),
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
