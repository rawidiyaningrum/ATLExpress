<?php

namespace App\Services\Threads;

use App\Services\Threads\Contracts\ThreadsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fallback pengambilan data dengan cara scraping.
 *
 * PENTING: jalur ini rapuh (struktur internal Threads sering berubah) dan
 * berisiko melanggar Terms of Service. Gunakan hanya sebagai sementara
 * sebelum API resmi `threads_keyword_search` disetujui Meta.
 *
 * Tiga mode, dipilih otomatis sesuai konfigurasi:
 * 1. `endpoint` — POST ke scraper sendiri / layanan pihak ketiga.
 * 2. `doc_id`   — memakai endpoint GraphQL internal Threads.
 * 3. halaman    — mengambil HTML halaman pencarian publik lalu mengekstrak
 *                 JSON yang tertanam (best-effort).
 */
class ThreadsScrapeSource implements ThreadsSource
{
    public function name(): string
    {
        return 'scrape';
    }

    public function isAvailable(): bool
    {
        return (bool) config('threads.enabled') && (
            trim((string) config('threads.scrape.endpoint')) !== ''
            || trim((string) config('threads.scrape.doc_id')) !== ''
            || (bool) config('threads.scrape.allow_page')
        );
    }

    public function search(string $query, string $mode = 'keyword', string $searchType = 'RECENT', int $limit = 25): array
    {
        $endpoint = trim((string) config('threads.scrape.endpoint'));
        $docId = trim((string) config('threads.scrape.doc_id'));

        try {
            $items = match (true) {
                $endpoint !== '' => $this->searchViaEndpoint($endpoint, $query, $mode, $searchType, $limit),
                $docId !== '' => $this->searchViaGraphql($docId, $query, $mode, $searchType, $limit),
                (bool) config('threads.scrape.allow_page') => $this->searchViaPage($query, $limit),
                default => throw new ThreadsException('Fallback scraping belum dikonfigurasi. Isi THREADS_SCRAPE_ENDPOINT atau THREADS_SCRAPE_DOC_ID, atau set THREADS_SCRAPE_ALLOW_PAGE=true untuk scraping halaman publik.'),
            };
        } catch (ConnectionException $e) {
            throw new ThreadsException('Scraper Threads tidak dapat dihubungi: '.Str::limit($e->getMessage(), 200));
        }

        return $this->mapResults($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchViaEndpoint(string $endpoint, string $query, string $mode, string $searchType, int $limit): array
    {
        $response = Http::timeout((int) config('threads.scrape.timeout'))
            ->acceptJson()
            ->retry(max(1, (int) config('threads.scrape.retries')), 500, throw: false)
            ->post($endpoint, [
                'query' => $query,
                'mode' => $mode,
                'search_type' => strtoupper($searchType),
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            throw new ThreadsException('Endpoint scraper menolak permintaan (HTTP '.$response->status().').');
        }

        return (array) $response->json('data', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchViaGraphql(string $docId, string $query, string $mode, string $searchType, int $limit): array
    {
        $base = rtrim((string) config('threads.scrape.base_url'), '/');

        $variables = [
            'query' => $query,
            'first' => $limit,
            'search_type' => $mode === 'tag' ? 'TAG' : 'KEYWORD',
        ];

        $response = Http::timeout((int) config('threads.scrape.timeout'))
            ->acceptJson()
            ->withHeaders($this->browserHeaders())
            ->retry(max(1, (int) config('threads.scrape.retries')), 500, throw: false)
            ->asForm()
            ->post($base.'/api/graphql', [
                'doc_id' => $docId,
                'variables' => json_encode($variables),
            ]);

        if ($response->failed()) {
            throw new ThreadsException('GraphQL Threads menolak permintaan (HTTP '.$response->status().').');
        }

        $decoded = (array) $response->json();
        $items = [];

        $this->collectPosts($decoded, $items);

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchViaPage(string $query, int $limit): array
    {
        $base = rtrim((string) config('threads.scrape.base_url'), '/');

        $response = Http::timeout((int) config('threads.scrape.timeout'))
            ->withHeaders($this->browserHeaders())
            ->retry(max(1, (int) config('threads.scrape.retries')), 500, throw: false)
            ->get($base.'/search', [
                'q' => $query,
                'serp_type' => 'default',
            ]);

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
            throw new ThreadsException('Scraping halaman tidak menemukan data. Isi THREADS_SCRAPE_ENDPOINT atau THREADS_SCRAPE_DOC_ID, atau gunakan API resmi.');
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
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept' => 'text/html,application/json,application/xhtml+xml',
            'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
            'X-IG-App-ID' => (string) config('threads.scrape.app_id'),
        ];
    }
}
