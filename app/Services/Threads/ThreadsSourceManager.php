<?php

namespace App\Services\Threads;

use App\Services\Threads\Contracts\ThreadsSource;
use Illuminate\Support\Facades\Log;

/**
 * Menentukan sumber Threads yang dipakai sesuai konfigurasi `threads.source`.
 *
 * - `api`    : hanya API resmi
 * - `scrape` : hanya fallback scraping
 * - `auto`   : API resmi bila terkonfigurasi, sisanya scraping
 */
class ThreadsSourceManager
{
    public function __construct(
        private readonly ThreadsApiSource $api,
        private readonly ThreadsScrapeSource $scrape,
    ) {}

    /**
     * Daftar sumber yang dicoba berurutan (prioritas pertama di depan).
     *
     * @return array<int, ThreadsSource>
     */
    public function resolve(): array
    {
        return match (strtolower((string) config('threads.source', 'auto'))) {
            'api' => [$this->api],
            'scrape' => [$this->scrape],
            default => [$this->api, $this->scrape],
        };
    }

    /**
     * Label sumber aktif untuk ditampilkan di admin.
     */
    public function label(): string
    {
        return match (strtolower((string) config('threads.source', 'auto'))) {
            'api' => 'API resmi Meta',
            'scrape' => 'Fallback scraping',
            default => 'Auto (API lalu scraping)',
        };
    }

    /**
     * Jalankan pencarian dengan mekanisme fallback: coba setiap sumber yang
     * tersedia sampai ada yang berhasil.
     *
     * @return array{results: array<int, ThreadsSearchResult>, source: string|null, error: string|null}
     */
    public function search(string $query, string $mode, string $searchType, int $limit): array
    {
        $lastError = null;
        $attempted = false;

        foreach ($this->resolve() as $source) {
            if (! $source->isAvailable()) {
                continue;
            }

            $attempted = true;

            try {
                $results = $source->search($query, $mode, $searchType, $limit);

                return [
                    'results' => $results,
                    'source' => $source->name(),
                    'error' => null,
                ];
            } catch (ThreadsException $e) {
                $lastError = $e->getMessage();

                Log::warning('Sumber Threads gagal, mencoba berikutnya.', [
                    'source' => $source->name(),
                    'query' => $query,
                    'error' => $lastError,
                ]);
            }
        }

        return [
            'results' => [],
            'source' => null,
            'error' => $attempted
                ? ($lastError ?? 'Semua sumber Threads gagal.')
                : 'Tidak ada sumber Threads yang siap (periksa konfigurasi/kredensial).',
        ];
    }
}
