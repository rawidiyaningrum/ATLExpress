<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Membatasi seluruh akses resource hanya untuk user dengan role admin penuh.
 * Admin ATL dan tracker tidak melihat menu maupun bisa membuka halaman ini.
 */
trait OnlyFullAdmins
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
