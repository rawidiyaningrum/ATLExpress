<?php

namespace Tests\Feature;

use App\Filament\Pages\ThreadsMonitor;
use App\Filament\Resources\ThreadsKeywordResource;
use App\Filament\Resources\ThreadsPostResource;
use App\Filament\Resources\ThreadsPostResource\Pages\ListThreadsPosts;
use App\Jobs\FetchThreadsCargoRequestsJob;
use App\Models\ThreadsKeyword;
use App\Models\ThreadsPost;
use App\Models\User;
use App\Services\Threads\ThreadsQueryQuota;
use Database\Seeders\ThreadsKeywordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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
        config(['threads.source' => 'auto']);

        Http::fake([
            '*graph.threads.com/*' => Http::response(['error' => ['message' => 'invalid token']], 401),
            'https://www.threads.com/search*' => Http::response($this->scrapePageHtml()),
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

        Http::assertSent(fn ($request): bool => str_starts_with(
            $request->url(),
            'https://www.threads.com/search?exact_match=false&q=cari+ekspedisi&filter=recent'
        ));
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

    public function test_scrape_source_works_without_api_credentials(): void
    {
        config([
            'threads.source' => 'auto',
            'threads.api.access_token' => '',
        ]);

        Http::fake([
            'https://www.threads.com/search*' => Http::response($this->scrapePageHtml()),
        ]);

        ThreadsKeyword::create([
            'keyword' => 'butuh ekspedisi',
            'mode' => ThreadsKeyword::MODE_KEYWORD,
            'is_active' => true,
        ]);

        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->assertSame(1, $summary['keywords']);
        $this->assertSame(1, $summary['created']);
        $this->assertSame('scrape', ThreadsPost::first()->source);
    }

    public function test_admin_can_clear_threads_search_results_from_monitor(): void
    {
        $admin = User::factory()->create();

        ThreadsPost::create(['threads_id' => '1', 'text' => 'post satu']);
        ThreadsPost::create(['threads_id' => '2', 'text' => 'post dua']);

        Livewire::actingAs($admin)
            ->test(ThreadsMonitor::class)
            ->assertActionVisible('clearSearchResults')
            ->callAction('clearSearchResults');

        $this->assertSame(0, ThreadsPost::count());
    }

    public function test_admin_can_clear_threads_search_results_from_list_page(): void
    {
        $admin = User::factory()->create();

        ThreadsPost::create(['threads_id' => '1', 'text' => 'post satu']);

        Livewire::actingAs($admin)
            ->test(ListThreadsPosts::class)
            ->assertActionVisible('clearSearchResults')
            ->callAction('clearSearchResults');

        $this->assertSame(0, ThreadsPost::count());
    }

    public function test_clear_search_action_is_visible_for_admin_atl(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();

        Livewire::actingAs($adminAtl)
            ->test(ThreadsMonitor::class)
            ->assertActionVisible('clearSearchResults');
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

    /**
     * HTML tiruan halaman pencarian Threads: JSON pencarian tertanam di
     * dalam <script type="application/json"> seperti respons asli.
     */
    private function scrapePageHtml(): string
    {
        $payload = [
            'data' => [
                'search_results' => [
                    ['node' => ['thread' => ['thread_items' => [[
                        'post' => [
                            'code' => 'abc123',
                            'caption' => ['text' => 'cari ekspedisi murah ke makassar'],
                            'user' => ['username' => 'sinta'],
                            'taken_at' => 1731000000,
                            'media_type' => 19,
                        ],
                    ]]]]],
                ],
            ],
        ];

        return '<html><body><script type="application/json">'.json_encode($payload).'</script></body></html>';
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
