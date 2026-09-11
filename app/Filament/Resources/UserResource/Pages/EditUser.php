<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (array_key_exists('as_invitation', $data)) {
            $data['invited_at'] = ($data['as_invitation'] === true) ? now() : null;
            unset($data['as_invitation']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('password')) {
            Notification::make()
                ->title('Password berhasil diubah.')
                ->success()
                ->send();
        }
    }
}