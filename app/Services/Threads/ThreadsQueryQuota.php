<?php

namespace App\Services\Threads;

use Illuminate\Support\Facades\Cache;

/**
 * Pengaman kuota endpoint keyword_search Meta (maksimum 2.200 query per 24 jam
 * per user). Penghitung disimpan per hari di cache.
 */
class ThreadsQueryQuota
{
    public function cap(): int
    {
        return max(0, (int) config('threads.query_cap_per_day', 2000));
    }

    public function used(): int
    {
        return (int) Cache::get($this->key(), 0);
    }

    public function remaining(): int
    {
        return max(0, $this->cap() - $this->used());
    }

    public function canRun(int $queries = 1): bool
    {
        return $this->remaining() >= max(1, $queries);
    }

    public function record(int $queries = 1): void
    {
        $queries = max(1, $queries);
        $key = $this->key();

        if (! Cache::has($key)) {
            Cache::put($key, 0, now()->endOfDay());
        }

        Cache::increment($key, $queries);
    }

    /**
     * Jumlah query yang boleh dipakai untuk satu keyword, dibatasi sisa kuota.
     */
    public function allowedFor(int $wanted): int
    {
        return max(0, min($wanted, $this->remaining()));
    }

    private function key(): string
    {
        return 'threads:quota:'.now()->toDateString();
    }
}
