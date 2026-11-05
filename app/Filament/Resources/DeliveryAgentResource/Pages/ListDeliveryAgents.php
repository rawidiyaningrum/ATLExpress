<?php

namespace App\Filament\Resources\DeliveryAgentResource\Pages;

use App\Filament\Resources\DeliveryAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryAgents extends ListRecords
{
    protected static string $resource = DeliveryAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Agen Baru'),
        ];
    }
}

