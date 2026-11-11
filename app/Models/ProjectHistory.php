<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris riwayat pekerjaan pada sebuah project. Status tinggal di sini,
 * bukan di project, dan baris inilah yang tampil sebagai kartu di board.
 */
class ProjectHistory extends Model
{
    use HasFactory;

    public const STATUS_TODO = 'todo';

    public const STATUS_PROGRESS = 'progress';

    public const STATUS_KENDALA = 'kendala';

    public const STATUS_CLOSE = 'close';

    /**
     * Urutan kolom board dari kiri ke kanan.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_TODO,
        self::STATUS_PROGRESS,
        self::STATUS_KENDALA,
        self::STATUS_CLOSE,
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_TODO => 'To Do',
        self::STATUS_PROGRESS => 'Progress',
        self::STATUS_KENDALA => 'Kendala',
        self::STATUS_CLOSE => 'Close',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        self::STATUS_TODO => 'gray',
        self::STATUS_PROGRESS => 'info',
        self::STATUS_KENDALA => 'danger',
        self::STATUS_CLOSE => 'success',
    ];

    /**
     * Warna heksadesimal untuk aksen kolom board.
     *
     * @var array<string, string>
     */
    public const STATUS_HEX_COLORS = [
        self::STATUS_TODO => '#9ca3af',
        self::STATUS_PROGRESS => '#3b82f6',
        self::STATUS_KENDALA => '#ef4444',
        self::STATUS_CLOSE => '#10b981',
    ];

    protected $fillable = [
        'project_id',
        'title',
        'status',
        'note',
        'progress_percent',
        'update_date',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'update_date' => 'date',
        'progress_percent' => 'integer',
        'sort_order' => 'integer',
    ];

    public static function statusLabel(?string $status): string
    {
        return $status === null ? '-' : (self::STATUS_LABELS[$status] ?? $status);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
