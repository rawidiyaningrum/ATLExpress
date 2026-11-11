<?php

namespace Tests\Feature;

use App\Filament\Pages\ThreadsMonitor;
use App\Filament\Resources\ThreadsKeywordResource;
use App\Filament\Resources\ThreadsPostResource;
use App\Jobs\FetchThreadsCargoRequestsJob;
use App\Models\ThreadsKeyword;
use App\Models\ThreadsPost;
use App\Models\User;
use App\Services\Threads\ThreadsQueryQuota;
use Database\Seeders\ThreadsKeywordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThreadsCargoMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'threads.enabled' => true,
            'threads.source' => 'api',
            'threads.api.access_token' => 'test-token',
            'threads.api.retries' => 0,
            'threads.api.retry_delay_ms' => 0,
            'threads.query_cap_per_day' => 100,
            'threads.per_keyword_limit' => 25,
        ]);
    }

    public function test_api_source_stores_cargo_request_posts(): void
    {
        $this->fakeApi();

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
            'source' => 'seed',
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(1, $summary['created']);
        $this->assertSame(1, $summary['keywords']);

        $post = ThreadsPost::first();

        $this->assertNotNull($post);
        $this->assertSame('1', $post->threads_id);
        $this->assertSame('budi', $post->username);
        $this->assertSame('butuh ekspedisi', $post->matched_keyword);
        $this->assertSame('api', $post->source);
        $this->assertSame(ThreadsPost::STATUS_NEW, $post->status);
        $this->assertNotNull($post->posted_at);

        $this->assertSame(1, ThreadsKeyword::first()->last_hits);
        $this->assertSame(1, app(ThreadsQueryQuota::class)->used());
    }

    public function test_second_run_updates_instead_of_duplicating(): void
    {
        $this->fakeApi();

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        (new FetchThreadsCargoRequestsJob)->execute();
        $second = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(1, ThreadsPost::count());
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['updated']);
    }

    public function test_auto_source_falls_back_to_scrape_when_api_fails(): void
    {
        config([
            'threads.source' => 'auto',
            'threads.scrape.endpoint' => 'https://scraper.test/search',
        ]);

        Http::fake([
            '*graph.threads.com/*' => Http::response(['error' => ['message' => 'invalid token']], 401),
            'https://scraper.test/search' => Http::response([
                'data' => [[
                    'code' => 'abc123',
                    'caption' => ['text' => 'cari ekspedisi murah ke makassar'],
                    'user' => ['username' => 'sinta'],
                    'taken_at' => 1731000000,
                ]],
            ]),
        ]);

        ThreadsKeyword::create([
            'keyword' => 'cari ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(1, $summary['created']);

        $post = ThreadsPost::first();

        $this->assertNotNull($post);
        $this->assertSame('abc123', $post->threads_id);
        $this->assertSame('sinta', $post->username);
        $this->assertSame('scrape', $post->source);
        $this->assertStringContainsString('cari ekspedisi', (string) $post->text);
        $this->assertSame('https://www.threads.com/@sinta/post/abc123', $post->permalink);
    }

    public function test_daily_quota_cap_blocks_further_queries(): void
    {
        $this->fakeApi();

        config(['threads.query_cap_per_day' => 1]);

        app(ThreadsQueryQuota::class)->record(1);

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(0, $summary['keywords']);
        $this->assertSame(0, ThreadsPost::count());
        Http::assertNothingSent();
    }

    public function test_disabled_feature_does_nothing(): void
    {
        config(['threads.enabled' => false]);

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(0, $summary['keywords']);
        Http::assertNothingSent();
    }

    public function test_no_source_ready_by_default_without_credentials(): void
    {
        config([
            'threads.source' => 'auto',
            'threads.api.access_token' => '',
            'threads.scrape.endpoint' => null,
            'threads.scrape.doc_id' => null,
            'threads.scrape.allow_page' => false,
        ]);

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(0, $summary['keywords']);
        $this->assertSame(0, $summary['errors']);
        $this->assertSame(0, ThreadsPost::count());
        Http::assertNothingSent();
    }

    public function test_keyword_seeder_populates_curated_keywords(): void
    {
        $this->seed(ThreadsKeywordSeeder::class);

        $this->assertGreaterThan(0, ThreadsKeyword::count());
        $this->assertDatabaseHas('threads_keywords', [
            'keyword' => 'cargo murah',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
        ]);
        $this->assertDatabaseHas('threads_keywords', [
            'keyword' => 'ekspedisi',
            'mode' => ThreadsKeyword::MODE_TAG,
        ]);
    }

    public function test_admin_can_access_threads_pages(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(ThreadsPostResource::getUrl('index'))
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get(ThreadsKeywordResource::getUrl('index'))
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get(ThreadsMonitor::getUrl())
            ->assertSuccessful();
    }

    public function test_tracker_cannot_access_threads_pages(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)
            ->get(ThreadsPostResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs($tracker)
            ->get(ThreadsMonitor::getUrl())
            ->assertForbidden();
    }

    private function fakeApi(): void
    {
        Http::fake([
            '*graph.threads.com/*' => Http::response([
                'data' => [[
                    'id' => '1',
                    'text' => 'butuh ekspedisi ke makassar',
                    'permalink' => 'https://www.threads.com/@budi/post/1',
                    'timestamp' => '2026-11-10T05:42:03+0000',
                    'username' => 'budi',
                    'media_type' => 'TEXT',
                    'has_replies' => false,
                    'is_quote_post' => false,
                    'is_reply' => false,
                ]],
            ]),
        ]);
    }
}
