<?php

namespace App\Filament\Resources\ShippingRequestResource\Pages;

use App\Filament\Resources\ShippingRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListShippingRequests extends ListRecords
{
    protected static string $resource = ShippingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}