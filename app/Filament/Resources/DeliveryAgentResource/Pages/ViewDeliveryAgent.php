<?php

namespace App\Filament\Resources\DeliveryAgentResource\Pages;

use App\Filament\Resources\DeliveryAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeliveryAgent extends ViewRecord
{
    protected static string $resource = DeliveryAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

