<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Postingan Threads yang cocok dengan keyword permintaan kargo.
 */
class ThreadsPost extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_WON = 'won';

    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'threads_id',
        'username',
        'text',
        'permalink',
        'media_type',
        'posted_at',
        'matched_keyword',
        'search_mode',
        'source',
        'has_replies',
        'is_reply',
        'is_quote_post',
        'raw',
        'status',
        'notes',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'has_replies' => 'boolean',
        'is_reply' => 'boolean',
        'is_quote_post' => 'boolean',
        'raw' => 'array',
    ];

    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('posted_at')->orderByDesc('id');
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => 'Baru',
            self::STATUS_CONTACTED => 'Sudah Dihubungi',
            self::STATUS_WON => 'Jadi Pelanggan',
            self::STATUS_IGNORED => 'Diabaikan',
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_NEW => 'warning',
            self::STATUS_CONTACTED => 'info',
            self::STATUS_WON => 'success',
            self::STATUS_IGNORED => 'gray',
            default => 'gray',
        };
    }

    /**
     * URL postingan asli di Threads.
     */
    public function threadsUrl(): ?string
    {
        return $this->permalink ?: null;
    }
}
