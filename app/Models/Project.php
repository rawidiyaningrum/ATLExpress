<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

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

    /**
     * Project hanya menjadi wadah pekerjaan; status tinggal di tiap riwayat.
     */
    protected $fillable = [
        'name',
        'description',
        'pic_id',
        'priority',
        'start_date',
        'due_date',
        'shipment_id',
        'progress_percent',
        'archived_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'archived_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

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

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(ProjectHistory::class)->latestOfMany();
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
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
