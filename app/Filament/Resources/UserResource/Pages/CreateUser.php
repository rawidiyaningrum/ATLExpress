<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\UserInvitation;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public ?string $generatedPassword = null;

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? User::ROLE_ADMIN;
        unset($data['role']);

        $record = new ($this->getModel())($data);
        $record->role = $role;
        $record->save();

        return $record;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            $data['password'] = $this->generatedPassword = Str::random(12);
        }

        // Undangan: set invited_at; token & expiry diisi afterCreate via model.
        if (($data['as_invitation'] ?? false) === true) {
            $data['invited_at'] = now();
        }

        unset($data['as_invitation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Jika mode undangan: generate token dan kirim email undangan.
        if ($record->invited_at !== null && $record->invite_token === null) {
            $token     = $record->generateInviteToken();
            $inviteUrl = route('filament.atlexpress-admin.auth.register', [
                'token' => $token,
                'email' => $record->email,
            ]);
            $record->save();

            try {
                Mail::to($record->email)->send(new UserInvitation($record, $inviteUrl));

                Notification::make()
                    ->title('Undangan berhasil dikirim ke ' . $record->email)
                    ->body('Link pendaftaran berlaku 7 hari.')
                    ->success()
                    ->persistent()
                    ->send();
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Akun undangan dibuat, tetapi email gagal terkirim.')
                    ->body('Error: ' . $e->getMessage())
                    ->warning()
                    ->persistent()
                    ->send();
            }
        }

        // Jika mode langsung (bukan undangan) dan password di-generate otomatis.
        if ($this->generatedPassword !== null && $record->invited_at === null) {
            Notification::make()
                ->title('User berhasil dibuat. Password sementara: ' . $this->generatedPassword)
                ->body('Salurkan password ini ke pengguna yang bersangkutan.')
                ->success()
                ->persistent()
                ->send();
        }
    }
}
