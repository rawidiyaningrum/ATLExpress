<?php

namespace App\Services\Threads;

use App\Services\Threads\Contracts\ThreadsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Sumber resmi Meta: Graph API `/keyword_search`.
 *
 * Memerlukan:
 * - Threads long-lived user access token (THREADS_ACCESS_TOKEN)
 * - permission `threads_basic` dan `threads_keyword_search`
 *
 * Selama app belum lolos review `threads_keyword_search`, endpoint hanya
 * mengembalikan postingan milik akun yang diautentikasi.
 */
class ThreadsApiSource implements ThreadsSource
{
    public function name(): string
    {
        return 'api';
    }

    public function isAvailable(): bool
    {
        return (bool) config('threads.enabled')
            && trim((string) config('threads.api.access_token')) !== '';
    }

    public function search(string $query, string $mode = 'keyword', string $searchType = 'RECENT', int $limit = 25): array
    {
        if (! $this->isAvailable()) {
            throw new ThreadsException('Threads API belum dikonfigurasi (THREADS_ACCESS_TOKEN kosong).');
        }

        $url = rtrim((string) config('threads.api.base_url'), '/').'/keyword_search';

        $params = [
            'q' => $query,
            'search_type' => strtoupper($searchType) === 'TOP' ? 'TOP' : 'RECENT',
            'search_mode' => $mode === 'tag' ? 'TAG' : 'KEYWORD',
            'limit' => max(1, min($limit, 100)),
            'fields' => implode(',', (array) config('threads.api.fields')),
            'access_token' => (string) config('threads.api.access_token'),
        ];

        $response = $this->request($url, $params);
        $data = (array) $response->json('data', []);

        $results = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }

            $result = ThreadsSearchResult::fromArray($item);

            if ($result !== null) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function request(string $url, array $params): Response
    {
        $maxAttempts = max(1, (int) config('threads.api.retries') + 1);
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = Http::timeout((int) config('threads.api.timeout'))
                    ->acceptJson()
                    ->get($url, $params);

                if ($response->successful()) {
                    return $response;
                }

                // Token kedaluwarsa / permission kurang tidak akan sembuh
                // dengan retry, jadi langsung dilaporkan.
                if (in_array($response->status(), [400, 401, 403], true)) {
                    throw new ThreadsException($this->errorMessage($response));
                }

                $lastError = 'HTTP '.$response->status().': '.$this->errorMessage($response);
                $this->backOff($attempt);
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
                $this->backOff($attempt);
            }
        }

        throw new ThreadsException('Gagal menghubungi Threads API: '.Str::limit((string) $lastError, 300));
    }

    private function errorMessage(Response $response): string
    {
        $message = (string) data_get((array) $response->json(), 'error.message', '');

        if ($message === '') {
            $message = Str::limit(strip_tags((string) $response->body()), 300);
        }

        return $message !== '' ? $message : 'HTTP '.$response->status();
    }

    private function backOff(int $attempt): void
    {
        $delay = (int) config('threads.api.retry_delay_ms') * $attempt;

        if ($delay > 0) {
            Sleep::for($delay)->milliseconds();
        }
    }
}
