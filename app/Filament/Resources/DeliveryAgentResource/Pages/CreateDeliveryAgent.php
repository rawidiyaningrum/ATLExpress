<?php

namespace App\Filament\Resources\DeliveryAgentResource\Pages;

use App\Filament\Resources\DeliveryAgentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryAgent extends CreateRecord
{
    protected static string $resource = DeliveryAgentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

