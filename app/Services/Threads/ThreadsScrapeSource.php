<?php

namespace App\Services\Threads;

use App\Services\Threads\Contracts\ThreadsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fallback pengambilan data dengan cara membuka halaman pencarian publik
 * Threads lalu mengekstrak hasil yang sudah dirender server.
 *
 * Alur:
 * 1. Buka https://www.threads.com/search?exact_match=false&q={query}&filter=recent.
 * 2. Ambil HTML halaman dengan User-Agent crawler agar Threads merender
 *    hasil pencarian di sisi server (bukan SPA kosong).
 * 3. Ekstrak JSON yang tertanam di dalam <script type="application/json">.
 * 4. Kumpulkan node postingan dan normalisasi lewat ThreadsSearchResult.
 *
 * PENTING: jalur ini rapuh (struktur internal Threads sering berubah) dan
 * berisiko melanggar Terms of Service. Gunakan hanya sebagai sementara
 * sebelum API resmi `threads_keyword_search` disetujui Meta.
 */
class ThreadsScrapeSource implements ThreadsSource
{
    public function name(): string
    {
        return 'scrape';
    }

    public function isAvailable(): bool
    {
        return (bool) config('threads.enabled');
    }

    public function search(string $query, string $mode = 'keyword', string $searchType = 'RECENT', int $limit = 25): array
    {
        try {
            $items = $this->searchViaPage($query, $limit);
        } catch (ConnectionException $e) {
            throw new ThreadsException('Halaman pencarian Threads tidak dapat dihubungi: '.Str::limit($e->getMessage(), 200));
        }

        return $this->mapResults($items);
    }

    /**
     * Buka halaman pencarian publik lalu ambil postingan dari JSON yang
     * tertanam di HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchViaPage(string $query, int $limit): array
    {
        $base = rtrim((string) config('threads.scrape.base_url', 'https://www.threads.com'), '/');
        $url = $base.'/search?'.http_build_query([
            'exact_match' => 'false',
            'q' => $query,
            'filter' => 'recent',
        ]);

        $response = Http::timeout((int) config('threads.scrape.timeout'))
            ->withHeaders($this->browserHeaders())
            ->retry(max(1, (int) config('threads.scrape.retries')), 500, throw: false)
            ->get($url);

        if ($response->failed()) {
            throw new ThreadsException('Halaman pencarian Threads menolak permintaan (HTTP '.$response->status().').');
        }

        $items = [];

        if (preg_match_all('/<script[^>]*type="application\/json"[^>]*>(.*?)<\/script>/s', (string) $response->body(), $matches)) {
            foreach ($matches[1] as $json) {
                $decoded = json_decode(html_entity_decode($json), true);

                if (is_array($decoded)) {
                    $this->collectPosts($decoded, $items);
                }
            }
        }

        if ($items === []) {
            throw new ThreadsException('Scraping halaman pencarian tidak menemukan data. Struktur halaman Threads mungkin berubah.');
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * Telusuri struktur JSON apa pun dan kumpulkan node yang mirip post.
     *
     * @param  array<int, array<string, mixed>>  $out
     */
    private function collectPosts(mixed $node, array &$out): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['code']) && (isset($node['caption']) || isset($node['text']) || isset($node['user']))) {
            $out[] = $node;
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectPosts($value, $out);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ThreadsSearchResult>
     */
    private function mapResults(array $items): array
    {
        $results = [];
        $seen = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $result = ThreadsSearchResult::fromArray($item);

            if ($result === null || isset($seen[$result->threadsId])) {
                continue;
            }

            $seen[$result->threadsId] = true;
            $results[] = $result;
        }

        return $results;
    }

    /**
     * @return array<string, string>
     */
    private function browserHeaders(): array
    {
        return [
            'User-Agent' => (string) config(
                'threads.scrape.user_agent',
                'Mozilla/5.0 (compatible; Bingbot/2.0; +http://www.bing.com/bingbot.htm)'
            ),
            'Accept' => 'text/html,application/xhtml+xml',
            'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
        ];
    }
}
