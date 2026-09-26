<?php

namespace App\Filament\Resources\FinanceJournalResource\Pages;

use App\Filament\Resources\FinanceJournalResource;
use Filament\Resources\Pages\EditRecord;

class EditFinanceJournal extends EditRecord
{
    protected static string $resource = FinanceJournalResource::class;

    /**
     * Nominal turunan tidak dikirim balik dari form, karena hook saving di
     * model sudah menghitung ulang dari modal, opex, pajak, dan pendapatan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['total_expense'],
            $data['profit'],
            $data['profit_percentage'],
        );

        return $data;
    }
}
