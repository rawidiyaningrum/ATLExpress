<?php

namespace App\Filament\Resources\ShippingEstimateResource\Pages;

use App\Filament\Resources\ShippingEstimateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingEstimates extends ListRecords
{
    protected static string $resource = ShippingEstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Estimasi'),
        ];
    }
}
