<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Membatasi seluruh akses resource untuk user dengan role admin (admin penuh
 * maupun admin ATL). User tracker tidak melihat menu maupun bisa membuka
 * halaman admin.
 */
trait OnlyAdmins
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }
}
