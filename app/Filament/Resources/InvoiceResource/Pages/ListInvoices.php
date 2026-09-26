<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Invoice lahir dari wizard shipment, jadi tidak ada tombol buat di sini.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->hidden(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            ...collect(Invoice::STATUSES)
                ->mapWithKeys(fn (string $status): array => [
                    $status => Tab::make(Invoice::STATUS_LABELS[$status])
                        ->modifyQueryUsing(fn ($query) => $query->where('status', $status)),
                ])
                ->all(),
        ];
    }
}
