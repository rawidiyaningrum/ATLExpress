<?php

namespace App\Jobs;

use App\Models\ThreadsKeyword;
use App\Models\ThreadsPost;
use App\Services\Threads\ThreadsQueryQuota;
use App\Services\Threads\ThreadsSourceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Mengambil postingan permintaan kargo dari Threads untuk seluruh keyword
 * aktif, lalu menyimpannya (dedupe by threads_id).
 */
class FetchThreadsCargoRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('threads-fetch'))->expireAfter(900),
        ];
    }

    public function handle(ThreadsSourceManager $manager, ThreadsQueryQuota $quota): void
    {
        $this->execute($manager, $quota);
    }

    /**
     * Jalankan pengambilan data. Bisa dipanggil langsung (sinkron) dari
     * command atau halaman admin, atau lewat queue.
     *
     * @return array{keywords: int, found: int, created: int, updated: int, errors: int, skipped: int, quota_used: int, quota_remaining: int}
     */
    public function execute(?ThreadsSourceManager $manager = null, ?ThreadsQueryQuota $quota = null): array
    {
        $manager ??= app(ThreadsSourceManager::class);
        $quota ??= app(ThreadsQueryQuota::class);

        $summary = [
            'keywords' => 0,
            'found' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0,
            'quota_used' => 0,
            'quota_remaining' => $quota->remaining(),
        ];

        if (! config('threads.enabled')) {
            Log::info('FetchThreadsCargoRequestsJob dilewati: THREADS_ENABLED=false.');

            return $summary;
        }

        $sources = collect($manager->resolve())
            ->filter(fn ($source): bool => $source->isAvailable());

        if ($sources->isEmpty()) {
            Log::warning('Tidak ada sumber Threads yang siap; pengambilan data dilewati. Periksa THREADS_ACCESS_TOKEN atau konfigurasi scraping.', [
                'configured_sources' => collect($manager->resolve())->map(fn ($source): string => $source->name())->all(),
            ]);

            return $summary;
        }

        $limit = max(1, (int) config('threads.per_keyword_limit', 25));

        $keywords = ThreadsKeyword::query()
            ->active()
            ->orderBy('last_run_at')
            ->orderBy('id')
            ->get();

        foreach ($keywords as $keyword) {
            if (! $quota->canRun()) {
                Log::warning('Kuota query Threads harian habis, sisa keyword dilewati.', [
                    'remaining' => $quota->remaining(),
                ]);

                break;
            }

            $allowed = $quota->allowedFor($limit);

            if ($allowed < 1) {
                break;
            }

            $summary['keywords']++;

            $outcome = $manager->search($keyword->keyword, $keyword->mode, 'RECENT', $allowed);

            if ($outcome['error'] !== null) {
                $summary['errors']++;
                $keyword->recordRun(0, null);

                Log::warning('Pengambilan keyword Threads gagal.', [
                    'keyword' => $keyword->keyword,
                    'error' => $outcome['error'],
                ]);

                continue;
            }

            $quota->record(1);
            $summary['quota_used']++;

            $created = 0;
            $updated = 0;

            foreach ($outcome['results'] as $result) {
                $post = ThreadsPost::updateOrCreate(
                    ['threads_id' => $result->threadsId],
                    [
                        'username' => $result->username,
                        'text' => $result->text,
                        'permalink' => $result->permalink,
                        'media_type' => $result->mediaType,
                        'posted_at' => $result->postedAt,
                        'matched_keyword' => $keyword->keyword,
                        'search_mode' => $keyword->mode,
                        'source' => $outcome['source'],
                        'has_replies' => $result->hasReplies,
                        'is_reply' => $result->isReply,
                        'is_quote_post' => $result->isQuotePost,
                        'raw' => $result->raw,
                    ]
                );

                if ($post->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            $keyword->recordRun(count($outcome['results']), $outcome['source']);

            $summary['found'] += count($outcome['results']);
            $summary['created'] += $created;
            $summary['updated'] += $updated;
        }

        $summary['quota_remaining'] = $quota->remaining();

        return $summary;
    }
}
