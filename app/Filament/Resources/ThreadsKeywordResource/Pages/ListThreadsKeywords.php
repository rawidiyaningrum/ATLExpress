<?php

namespace App\Filament\Resources\ThreadsKeywordResource\Pages;

use App\Filament\Resources\ThreadsKeywordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListThreadsKeywords extends ListRecords
{
    protected static string $resource = ThreadsKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
