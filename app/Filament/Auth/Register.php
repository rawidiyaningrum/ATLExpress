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
        $user = $this->getUserModel()
            ::where('email', $data['email'])
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.email' => 'Email tersebut belum diundang. Hubungi administrator untuk mendapatkan undangan.',
            ]);
        }

        if ($user->invited_at === null) {
            throw ValidationException::withMessages([
                'data.email' => 'Akun ini sudah terdaftar. Silakan login langsung.',
            ]);
        }

        $user->forceFill([
            'name' => $data['name'],
            'password' => $data['password'],
            'invited_at' => null,
        ])->save();

        return $user;
    }
}