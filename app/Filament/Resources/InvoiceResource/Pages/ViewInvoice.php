<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Concerns\AppliesInvoiceStatus;
use App\Filament\Resources\ShipmentResource\Pages\PrintInvoice as PrintShipmentInvoice;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    use AppliesInvoiceStatus;

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
            ...$this->statusHeaderActions(),
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->isDraft()),
        ];
    }

    /**
     * Tombol pemindahan status yang sah untuk invoice yang sedang dibuka.
     *
     * Nama aksi memakai status asal dan tujuan, sama seperti di tabel daftar,
     * supaya satu transisi punya nama yang sama di kedua tempat.
     *
     * @return array<int, Actions\Action>
     */
    protected function statusHeaderActions(): array
    {
        $invoice = $this->record;

        if (! $invoice instanceof Invoice) {
            return [];
        }

        $from = $invoice->status;

        return collect(static::statusActionsFor($invoice))
            ->map(fn (array $definition, string $status): Actions\Action => Actions\Action::make("status:{$from}:{$status}")
                ->label($definition['label'])
                ->icon($definition['icon'])
                ->color($definition['color'])
                ->requiresConfirmation()
                ->modalHeading($definition['label'])
                ->modalDescription($definition['description'])
                ->modalSubmitActionLabel($definition['label'])
                ->action(fn () => static::applyInvoiceStatus($invoice, $status)),
            )
            ->values()
            ->all();
    }
}
