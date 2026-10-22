<?php

namespace Tests\Feature;

use App\Filament\Pages\ArticleTemplateGenerator;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleSeoGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::create([
            'name' => 'Berita',
            'slug' => 'berita',
        ]);
    }

    public function test_generator_creates_unique_seo_per_article_within_batch(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
        ]));

        Livewire::test(ArticleTemplateGenerator::class)
            ->fillForm([
                'keyword' => 'ekspedisi kargo jakarta',
                'category_slug' => 'berita',
                'count' => 3,
                'published_at' => now(),
            ])
            ->call('generateArticles')
            ->assertHasNoFormErrors();

        $this->assertSame(3, Post::count());

        $posts = Post::query()->orderBy('id')->get();

        $titles = [];
        $descriptions = [];
        $keywords = [];

        foreach ($posts as $post) {
            $this->assertNotEmpty($post->meta_title);
            $this->assertLessThanOrEqual(60, mb_strlen($post->meta_title), 'meta_title tidak boleh > 60');
            $this->assertNotEmpty($post->meta_description);
            $this->assertLessThanOrEqual(155, mb_strlen($post->meta_description), 'meta_description tidak boleh > 155');
            $this->assertNotEmpty($post->meta_keywords);
            $this->assertStringContainsString('ekspedisi kargo jakarta', Str::lower($post->meta_keywords));

            $titles[] = $post->meta_title;
            $descriptions[] = $post->meta_description;
            $keywords[] = $post->meta_keywords;
        }

        $this->assertCount(3, array_unique($titles), 'meta title tiap artikel harus unik');
        $this->assertCount(3, array_unique($keywords), 'meta keywords tiap artikel harus unik');
    }

    public function test_manual_seo_overrides_are_applied_with_placeholders(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
        ]));

        Livewire::test(ArticleTemplateGenerator::class)
            ->fillForm([
                'keyword' => 'cargo darat',
                'category_slug' => 'berita',
                'count' => 1,
                'published_at' => now(),
                'meta_title' => '{keyword} | Custom {index}',
                'meta_description' => 'Deskripsi custom untuk {title}',
                'meta_keywords' => '{keyword}, custom keyword',
            ])
            ->call('generateArticles')
            ->assertHasNoFormErrors();

        $post = Post::first();

        $this->assertNotNull($post);
        $this->assertStringContainsString('cargo darat', Str::lower($post->meta_title));
        $this->assertStringContainsString('1', $post->meta_title);
        $this->assertSame('Deskripsi custom untuk '.$post->title, $post->meta_description);
        $this->assertSame('cargo darat, custom keyword', $post->meta_keywords);
    }

    public function test_post_detail_page_uses_per_article_meta_and_bugfix(): void
    {
        $category = Category::first();
        $post = Post::create([
            'title' => 'Tes Artikel SEO',
            'slug' => 'tes-artikel-seo',
            'category_id' => $category->id,
            'content' => '<p>Isi konten artikel untuk pengujian meta.</p>',
            'meta_title' => 'Judul Meta Khusus | ATL Express',
            'meta_description' => 'Deskripsi meta khusus untuk halaman artikel ini.',
            'meta_keywords' => 'ekspedisi, cargo, logistik',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertStatus(200);
        $response->assertSee('Judul Meta Khusus | ATL Express', false);
        $response->assertSee('Deskripsi meta khusus untuk halaman artikel ini.', false);
        $response->assertSee('ekspedisi, cargo, logistik', false);
        $response->assertSee('<meta name="keywords"', false);
    }
}
