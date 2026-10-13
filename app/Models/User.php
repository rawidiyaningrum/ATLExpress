<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ADMIN_ATL = 'admin_atl';

    public const ROLE_TRACKER = 'tracker';

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_ADMIN_ATL => 'Admin ATL',
        self::ROLE_TRACKER => 'Tracker',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'invited_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAdminAtl(): bool
    {
        return $this->role === self::ROLE_ADMIN_ATL;
    }

    /**
     * Admin penuh maupun admin ATL. Dipakai untuk hak akses umum, sedangkan
     * area khusus (invoice, jurnal keuangan, dashboard finance, manajemen admin)
     * tetap memakai isAdmin().
     */
    public function isAdminRole(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_ADMIN_ATL;
    }

    public function isTracker(): bool
    {
        return $this->role === self::ROLE_TRACKER;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) $this->role);
    }

    public function scopeTrackers($query)
    {
        return $query->where('role', self::ROLE_TRACKER);
    }
}
