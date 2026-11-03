<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as FilamentRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class Register extends FilamentRegister
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->helperText('Gunakan email yang telah diundang oleh administrator.')
            ->email()
            ->required()
            ->maxLength(255);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function handleRegistration(array $data): Model
    {
        // Token dikirim lewat query string saat user membuka link undangan.
        $token = request()->query('token');

        $user = $this->getUserModel()
            ::where('email', $data['email'])
            ->first();

        // Pastikan akun memang sedang menunggu undangan.
        if (! $user || $user->invited_at === null) {
            throw ValidationException::withMessages([
                'data.email' => $user
                    ? 'Akun ini sudah terdaftar. Silakan login langsung.'
                    : 'Email tersebut belum diundang. Hubungi administrator untuk mendapatkan undangan.',
            ]);
        }

        // Validasi token: harus ada, cocok, dan belum kedaluwarsa.
        if (
            blank($token)
            || blank($user->invite_token)
            || ! hash_equals((string) $user->invite_token, (string) $token)
        ) {
            throw ValidationException::withMessages([
                'data.email' => 'Link undangan tidak valid. Minta administrator untuk mengirim ulang undangan.',
            ]);
        }

        if ($user->invite_expires_at !== null && $user->invite_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'data.email' => 'Link undangan sudah kedaluwarsa. Minta administrator untuk mengirim ulang undangan.',
            ]);
        }

        // Selesaikan pendaftaran dan hapus token agar tidak bisa dipakai ulang.
        $user->forceFill([
            'name'             => $data['name'],
            'password'         => $data['password'],
            'invited_at'       => null,
            'invite_token'     => null,
            'invite_expires_at' => null,
        ])->save();

        return $user;
    }
}
