<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\UserInvitation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** Mode undangan yang dipilih admin; dibaca di afterSave. */
    private ?bool $inviteMode = null;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (array_key_exists('role', $data)) {
            $record->role = $data['role'];
            unset($data['role']);
        }

        $record->fill($data);
        $record->save();

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (array_key_exists('as_invitation', $data)) {
            $this->inviteMode = (bool) $data['as_invitation'];

            // Hanya set / hapus invited_at lewat fillable.
            // Token ditangani di afterSave() via forceFill.
            $data['invited_at'] = $this->inviteMode ? ($this->record->invited_at ?? now()) : null;

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

        if ($this->inviteMode === true) {
            // Regenerate token setiap kali admin mengaktifkan mode undangan.
            $record = $this->record;
            $token  = $record->generateInviteToken(); // sets invite_token, invite_expires_at, invited_at
            $record->forceFill([
                'invite_token'      => $record->invite_token,
                'invite_expires_at' => $record->invite_expires_at,
                'invited_at'        => $record->invited_at,
            ])->save();

            $inviteUrl = route('filament.atlexpress-admin.auth.register', [
                'token' => $token,
                'email' => $record->email,
            ]);

            try {
                Mail::to($record->email)->send(new UserInvitation($record, $inviteUrl));

                Notification::make()
                    ->title('Undangan dikirim ke ' . $record->email)
                    ->body('Link pendaftaran berlaku 7 hari.')
                    ->success()
                    ->persistent()
                    ->send();
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Undangan gagal dikirim via email.')
                    ->body('Error: ' . $e->getMessage() . ' — Salin link secara manual.')
                    ->warning()
                    ->persistent()
                    ->send();
            }
        } elseif ($this->inviteMode === false) {
            // Mode undangan dimatikan: hapus token.
            $this->record->forceFill([
                'invite_token'      => null,
                'invite_expires_at' => null,
            ])->save();
        }
    }
}
