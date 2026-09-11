<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public ?string $generatedPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            $data['password'] = $this->generatedPassword = Str::random(12);
        }

        if (($data['as_invitation'] ?? false) === true) {
            $data['invited_at'] = now();
        }

        unset($data['as_invitation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->generatedPassword !== null) {
            Notification::make()
                ->title('User berhasil dibuat. Password sementara: ' . $this->generatedPassword)
                ->body('Salurkan password ini ke pengguna yang bersangkutan.')
                ->success()
                ->persistent()
                ->send();
        }
    }
}