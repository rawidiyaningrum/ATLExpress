<?php

namespace App\Filament\Resources\FinanceJournalResource\Pages;

use App\Filament\Resources\FinanceJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinanceJournals extends ListRecords
{
    protected static string $resource = FinanceJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Jurnal'),
        ];
    }
}
