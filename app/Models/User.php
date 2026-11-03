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
        'invite_token',
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
            'invite_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ADMIN_ATL,
            self::ROLE_TRACKER,
        ], true);
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

    /**
     * Generate token undangan acak, simpan ke model (belum disave ke DB).
     * Kembalikan token plain-text agar bisa dimasukkan ke URL email.
     */
    public function generateInviteToken(int $expiryDays = 7): string
    {
        $token = bin2hex(random_bytes(32)); // 64 karakter hex

        $this->invite_token     = $token;
        $this->invite_expires_at = now()->addDays($expiryDays);
        $this->invited_at        = now();

        return $token;
    }
}
