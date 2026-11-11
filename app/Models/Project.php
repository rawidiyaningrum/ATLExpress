<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Project extends Model
{
    use HasFactory;

    public const STATUS_BACKLOG = 'backlog';

    public const STATUS_TODO = 'todo';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_DONE = 'done';

    /**
     * Urutan kolom board dari kiri ke kanan.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_BACKLOG,
        self::STATUS_TODO,
        self::STATUS_IN_PROGRESS,
        self::STATUS_BLOCKED,
        self::STATUS_DONE,
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_BACKLOG => 'Backlog',
        self::STATUS_TODO => 'To Do',
        self::STATUS_IN_PROGRESS => 'On Progress',
        self::STATUS_BLOCKED => 'Kendala',
        self::STATUS_DONE => 'Selesai',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        self::STATUS_BACKLOG => 'gray',
        self::STATUS_TODO => 'warning',
        self::STATUS_IN_PROGRESS => 'info',
        self::STATUS_BLOCKED => 'danger',
        self::STATUS_DONE => 'success',
    ];

    /**
     * Warna heksadesimal untuk aksen kolom board dan grafik.
     *
     * @var array<string, string>
     */
    public const STATUS_HEX_COLORS = [
        self::STATUS_BACKLOG => '#9ca3af',
        self::STATUS_TODO => '#f59e0b',
        self::STATUS_IN_PROGRESS => '#3b82f6',
        self::STATUS_BLOCKED => '#ef4444',
        self::STATUS_DONE => '#10b981',
    ];

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * @var array<int, string>
     */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    /**
     * @var array<string, string>
     */
    public const PRIORITY_LABELS = [
        self::PRIORITY_LOW => 'Rendah',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'Tinggi',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    /**
     * @var array<string, string>
     */
    public const PRIORITY_COLORS = [
        self::PRIORITY_LOW => 'gray',
        self::PRIORITY_NORMAL => 'info',
        self::PRIORITY_HIGH => 'warning',
        self::PRIORITY_URGENT => 'danger',
    ];

    protected $fillable = [
        'name',
        'description',
        'pic_id',
        'status',
        'priority',
        'start_date',
        'due_date',
        'shipment_id',
        'progress_percent',
        'sort_order',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'archived_at' => 'datetime',
        'progress_percent' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Label status yang dipakai tabel, board, dan riwayat.
     */
    public static function statusLabel(?string $status): string
    {
        return $status === null ? '-' : (self::STATUS_LABELS[$status] ?? $status);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Riwayat update status, terbaru lebih dulu untuk timeline.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(ProjectStatusUpdate::class)
            ->orderByDesc('update_date')
            ->orderByDesc('id');
    }

    public function latestUpdate(): HasOne
    {
        return $this->hasOne(ProjectStatusUpdate::class)->latestOfMany();
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst((string) $this->priority);
    }

    /**
     * Apakah project ini sudah punya update status pada tanggal tertentu.
     */
    public function hasUpdateOn(\DateTimeInterface|string $date): bool
    {
        $dateString = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : $date;

        return $this->statusUpdates()
            ->whereDate('update_date', $dateString)
            ->exists();
    }

    /**
     * Jumlah hari sejak update terakhir, null kalau belum pernah diupdate.
     */
    public function daysSinceLastUpdate(): ?int
    {
        $lastDate = $this->statusUpdates()->max('update_date');

        if ($lastDate === null) {
            return null;
        }

        return (int) Carbon::parse($lastDate)->startOfDay()->diffInDays(now()->startOfDay());
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
