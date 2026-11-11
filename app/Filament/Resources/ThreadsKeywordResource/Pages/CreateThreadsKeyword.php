<?php

namespace App\Filament\Resources\ThreadsKeywordResource\Pages;

use App\Filament\Resources\ThreadsKeywordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThreadsKeyword extends CreateRecord
{
    protected static string $resource = ThreadsKeywordResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source'] = 'manual';
        $data['keyword'] = ltrim(trim((string) $data['keyword']), '#');

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
