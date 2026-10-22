<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArticleGenerationBatch extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'keyword',
        'category_slug',
        'method',
        'model',
        'status',
        'total',
        'processed',
        'generated',
        'fallback',
        'failed',
        'estimated_cost_usd',
        'actual_cost_usd',
        'error',
        'options',
    ];

    protected $casts = [
        'total' => 'integer',
        'processed' => 'integer',
        'generated' => 'integer',
        'fallback' => 'integer',
        'failed' => 'integer',
        'estimated_cost_usd' => 'float',
        'actual_cost_usd' => 'float',
        'options' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch): void {
            if (empty($batch->uuid)) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
