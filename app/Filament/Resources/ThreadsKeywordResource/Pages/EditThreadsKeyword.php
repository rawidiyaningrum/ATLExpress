<?php

namespace App\Filament\Resources\ThreadsKeywordResource\Pages;

use App\Filament\Resources\ThreadsKeywordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditThreadsKeyword extends EditRecord
{
    protected static string $resource = ThreadsKeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['keyword'] = ltrim(trim((string) $data['keyword']), '#');

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
