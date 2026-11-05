<?php

namespace App\Filament\Resources\DeliveryAgentResource\Pages;

use App\Filament\Resources\DeliveryAgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryAgent extends EditRecord
{
    protected static string $resource = DeliveryAgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

