<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TariffSearchLog extends Model
{
    protected $fillable = [
        'origin',
        'kabupaten',
        'destination',
        'weight',
        'results_count',
        'min_price',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'min_price' => 'decimal:2',
        'results_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('results_count', '>', 0);
    }

    public function scopeNotFound(Builder $query): Builder
    {
        return $query->where('results_count', 0);
    }

    /**
     * Catat aktivitas pencarian tarif oleh pengunjung.
     * Dilengkapi deduplikasi 3 detik per IP dan rute agar tidak tercatat ganda akibat double-click.
     *
     * @param array<int, array{service_type: string, price_per_kg: float, total_price: float, estimated_days: string}> $results
     */
    public static function record(string $origin, ?string $kabupaten, string $destination, float $weight, array $results = []): ?self
    {
        $origin = trim($origin);
        $kabupaten = $kabupaten ? trim($kabupaten) : null;
        $destination = trim($destination);

        $ip = request()?->ip() ?? '127.0.0.1';
        $lockKey = 'tariff_search_log:' . md5("{$ip}:{$origin}:{$kabupaten}:{$destination}:{$weight}");

        // Jika pencarian identik terjadi dalam 3 detik terakhir oleh IP yang sama, abaikan deduplikasi
        if (! Cache::add($lockKey, 1, now()->addSeconds(3))) {
            return null;
        }

        try {
            $resultsCount = count($results);
            $minPrice = null;

            if ($resultsCount > 0) {
                $prices = array_column($results, 'total_price');
                $minPrice = ! empty($prices) ? min($prices) : null;
            }

            return static::create([
                'origin' => $origin,
                'kabupaten' => $kabupaten,
                'destination' => $destination,
                'weight' => $weight,
                'results_count' => $resultsCount,
                'min_price' => $minPrice,
                'ip_address' => $ip,
                'user_agent' => request()?->userAgent(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}

