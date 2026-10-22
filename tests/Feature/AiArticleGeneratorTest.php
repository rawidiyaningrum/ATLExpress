<?php

namespace Tests\Feature;

use App\Filament\Pages\ArticleTemplateGenerator;
use App\Filament\Resources\AiUsageResource;
use App\Filament\Resources\AiUsageResource\Pages\ListAiUsages;
use App\Models\AiUsage;
use App\Models\ArticleGenerationBatch;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\AiArticleGenerator;
use App\Services\AI\AiBatchRunner;
use App\Services\AI\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AiArticleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.enabled' => true,
            'ai.api_key' => 'test-key',
            'ai.model' => 'gemini-3.1-flash-lite',
            'ai.budget.monthly_cap_usd' => 5,
            'ai.budget.run_cap_usd' => 1,
            'ai.budget.max_articles_per_run' => 50,
            'ai.retry_delay_ms' => 0,
        ]);

        Category::create([
            'name' => 'Berita',
            'slug' => 'berita',
        ]);
    }

    public function test_ai_generates_article_with_seo_metadata(): void
    {
        $this->fakeGemini();

        $result = app(AiArticleGenerator::class)->generate(
            'ekspedisi kargo jakarta',
            'berita',
            'Berita',
            1,
        );

        $article = $result['article'];

        $this->assertSame('Panduan Memilih Jasa Ekspedisi Kargo Jakarta', $article['title']);
        $this->assertLessThanOrEqual(60, mb_strlen($article['meta_title']));
        $this->assertLessThanOrEqual(155, mb_strlen($article['meta_description']));
        $this->assertCount(2, $article['sections']);
        $this->assertStringContainsString('ATL Express', $article['ctas'][0][1]);
        $this->assertStringContainsString('atlexpress.biz.id/kontak', $article['ctas'][0][1]);
    }

    public function test_html_in_ai_output_is_stripped(): void
    {
        $this->fakeGemini([
            'lead' => '<script>alert(1)</script> Lead yang bersih.',
            'sections' => [
                [
                    'heading' => 'Bagian <b>Pertama</b>',
                    'body' => 'Isi <script>alert(2)</script> paragraf.',
                    'bullets' => ['Poin <i>satu</i>', 'Poin dua'],
                ],
                [
                    'heading' => 'Bagian Kedua',
                    'body' => 'Paragraf kedua yang aman.',
                    'bullets' => ['Poin tiga'],
                ],
            ],
        ]);

        $result = app(AiArticleGenerator::class)->generate('cargo darat', 'berita', 'Berita', 1);
        $article = $result['article'];

        $this->assertStringNotContainsString('<script>', $article['lead']);
        $this->assertStringNotContainsString('<b>', $article['sections'][0][0]);
        $this->assertStringNotContainsString('<i>', $article['sections'][0][2][0]);
        $this->assertStringContainsString('Lead yang bersih.', $article['lead']);
    }

    public function test_batch_publishes_article_and_logs_usage(): void
    {
        $this->fakeGemini();

        $result = app(AiBatchRunner::class)->dispatch('ekspedisi kargo', 'berita', 1);

        $this->assertTrue($result['allowed']);
        $this->assertGreaterThan(0, $result['estimated_cost']);

        $post = Post::query()->first();

        $this->assertNotNull($post);
        $this->assertSame('Panduan Memilih Jasa Ekspedisi Kargo Jakarta', $post->title);
        $this->assertSame('published', $post->status);
        $this->assertStringContainsString('<h2>', (string) $post->content);

        $usage = AiUsage::query()->first();

        $this->assertNotNull($usage);
        $this->assertSame('success', $usage->status);
        $this->assertSame(1200, $usage->prompt_tokens);
        $this->assertSame(1800, $usage->output_tokens);
        $this->assertGreaterThan(0, $usage->estimated_cost_usd);

        $batch = ArticleGenerationBatch::query()->first();

        $this->assertSame(ArticleGenerationBatch::STATUS_COMPLETED, $batch->status);
        $this->assertSame(1, $batch->generated);
        $this->assertSame(0, $batch->fallback);
    }

    public function test_ai_failure_falls_back_to_template_article(): void
    {
        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $result = app(AiBatchRunner::class)->dispatch('ekspedisi kargo', 'berita', 1);

        $this->assertTrue($result['allowed']);

        $post = Post::query()->first();

        $this->assertNotNull($post, 'fallback template harus tetap membuat artikel');
        $this->assertNotSame('Panduan Memilih Jasa Ekspedisi Kargo Jakarta', $post->title);

        $batch = ArticleGenerationBatch::query()->first();

        $this->assertSame(0, $batch->generated);
        $this->assertSame(1, $batch->fallback);
        $this->assertSame(ArticleGenerationBatch::STATUS_COMPLETED, $batch->status);

        $this->assertSame('failed', AiUsage::query()->first()->status);
    }

    public function test_budget_cap_zero_blocks_ai_generation(): void
    {
        config(['ai.budget.monthly_cap_usd' => 0]);

        $this->fakeGemini();

        $result = app(AiBatchRunner::class)->dispatch('ekspedisi kargo', 'berita', 1);

        $this->assertFalse($result['allowed']);
        $this->assertSame(0, Post::query()->count());

        Http::assertNothingSent();

        $batch = ArticleGenerationBatch::query()->first();

        $this->assertSame(ArticleGenerationBatch::STATUS_FAILED, $batch->status);
    }

    public function test_ai_disabled_blocks_generation(): void
    {
        config(['ai.enabled' => false]);

        $this->assertFalse(app(GeminiClient::class)->isConfigured());
    }

    public function test_generator_page_renders_ai_source_option(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
        ]));

        Livewire::test(ArticleTemplateGenerator::class)
            ->assertOk()
            ->fillForm(['content_source' => 'ai'])
            ->assertSee('Generate dengan AI');
    }

    public function test_ai_usage_list_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
        ]));

        AiUsage::create([
            'provider' => 'gemini',
            'model' => 'gemini-3.1-flash-lite',
            'purpose' => 'article',
            'prompt_tokens' => 100,
            'output_tokens' => 200,
            'estimated_cost_usd' => 0.000325,
            'status' => 'success',
        ]);

        Livewire::test(ListAiUsages::class)
            ->assertOk()
            ->assertCanSeeTableRecords(AiUsage::all());
    }

    public function test_ai_usage_list_page_hidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'user',
        ]));

        $this->assertFalse(AiUsageResource::canViewAny());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeGemini(array $overrides = []): void
    {
        $payload = array_merge([
            'title' => 'Panduan Memilih Jasa Ekspedisi Kargo Jakarta',
            'meta_title' => 'Panduan Ekspedisi Kargo Jakarta | ATL Express',
            'meta_description' => 'Panduan lengkap memilih jasa ekspedisi kargo jakarta beserta tips Agar ongkos kirim lebih hemat dan aman. Baca di ATL Express.',
            'meta_keywords' => 'ekspedisi kargo jakarta, ongkos kirim, jasa cargo, ekspedisi murah',
            'lead' => 'Mengirim barang ke luar kota Jakarta butuh ekspedisi yang bisa diandalkan. Panduan ini membahas cara memilih jasa kargo yang tepat untuk bisnis Anda.',
            'sections' => [
                [
                    'heading' => 'Apa Itu Ekspedisi Kargo',
                    'body' => 'Kargo adalah pengiriman barang dalam jumlah besar yang membutuhkan penanganan khusus. Pian.',
                    'bullets' => ['Cek reputasi penyedia', 'Bandingkan estimasi biaya'],
                ],
                [
                    'heading' => 'Cara Memilih Penyedia',
                    'body' => 'Pilih penyedia yang punya pengalaman, jaringan luas, dan komunikasi jelas agar pengiriman berjalan lancar.',
                    'bullets' => ['Periksa testimoni pelanggan', 'Pastikan ada tracking'],
                ],
            ],
            'tips_title' => 'Tips Praktis',
            'tips' => ['Siapkan data barang lengkap', 'Cek ongkos kirim sebelum kirim'],
            'cta_heading' => 'Konsultasikan Kebutuhan Anda',
            'cta_body' => 'Tim kami siap membantu menyusun layanan yang sesuai dengan kebutuhan pengiriman Anda.',
        ], $overrides);

        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($payload)]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 1200,
                    'candidatesTokenCount' => 1800,
                    'totalTokenCount' => 3000,
                ],
            ]),
        ]);
    }
}
