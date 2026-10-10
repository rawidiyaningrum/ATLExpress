<?php

namespace App\Filament\Resources\TrackerResource\Pages;

use App\Filament\Resources\TrackerResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTracker extends EditRecord
{
    protected static string $resource = TrackerResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless($this->record->isTracker(), 404);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['role'] = User::ROLE_TRACKER;

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
