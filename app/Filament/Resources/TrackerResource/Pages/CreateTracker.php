<?php

namespace App\Filament\Resources\TrackerResource\Pages;

use App\Filament\Resources\TrackerResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTracker extends CreateRecord
{
    protected static string $resource = TrackerResource::class;

    public ?string $generatedPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = User::ROLE_TRACKER;

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
                ->title('User tracker berhasil dibuat. Password sementara: '.$this->generatedPassword)
                ->body('Salurkan password ini ke tracker yang bersangkutan.')
                ->success()
                ->persistent()
                ->send();
        }
    }
}
