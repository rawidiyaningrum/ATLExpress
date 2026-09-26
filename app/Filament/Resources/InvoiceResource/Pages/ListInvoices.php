<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
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
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'draft')),
            'final' => Tab::make('Final')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'final')),
        ];
    }
}
