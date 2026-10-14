<?php

namespace App\Filament\Resources\ShippingEstimateResource\Pages;

use App\Filament\Resources\ShippingEstimateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShippingEstimate extends ViewRecord
{
    protected static string $resource = ShippingEstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak')
                ->label('Cetak Estimasi')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => PrintShippingEstimate::getUrl(['record' => $this->record]))
                ->openUrlInNewTab(),
            Actions\EditAction::make()
                ->label('Edit'),
            Actions\DeleteAction::make()
                ->label('Hapus'),
        ];
    }
}
