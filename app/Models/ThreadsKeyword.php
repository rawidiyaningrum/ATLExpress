<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kata kunci pencarian Threads. Dikelola dari menu admin maupun ekspansi AI.
 */
class ThreadsKeyword extends Model
{
    public const MODE_KEYWORD = 'keyword';

    public const MODE_TAG = 'tag';

    protected $fillable = [
        'keyword',
        'mode',
        'is_active',
        'source',
        'last_run_at',
        'last_hits',
        'total_hits',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'last_hits' => 'integer',
        'total_hits' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeKeywordMode(Builder $query): Builder
    {
        return $query->where('mode', self::MODE_KEYWORD);
    }

    /**
     * @return array<string, string>
     */
    public static function modeOptions(): array
    {
        return [
            self::MODE_KEYWORD => 'Keyword',
            self::MODE_TAG => 'Topic Tag',
        ];
    }

    public function modeLabel(): string
    {
        return self::modeOptions()[$this->mode] ?? $this->mode;
    }

    /**
     * Catat hasil satu kali penjalanan keyword.
     */
    public function recordRun(int $hits, ?string $source = null): void
    {
        $this->last_run_at = now();
        $this->last_hits = $hits;
        $this->total_hits += $hits;

        if ($source !== null) {
            $this->source = $source;
        }

        $this->save();
    }
}
