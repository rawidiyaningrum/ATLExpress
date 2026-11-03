<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_xml_and_includes_content_lastmod(): void
    {
        Service::create([
            'title' => 'Pengiriman Darat',
            'short_description' => 'Layanan darat.',
            'content' => 'Konten layanan darat.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Post::create([
            'title' => 'Tips Mengirim Barang',
            'content' => 'Isi artikel.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('pengiriman-darat', false);
        $response->assertSee('tips-mengirim-barang', false);
        $response->assertSee('<lastmod>', false);
    }

    public function test_tracking_page_exposes_faq_schema(): void
    {
        $response = $this->get('/cek-resi');

        $response->assertStatus(200);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('Bagaimana cara cek resi ATL Express?', false);
    }

    public function test_tariff_page_exposes_faq_schema(): void
    {
        $response = $this->get('/cek-tarif');

        $response->assertStatus(200);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('Bagaimana cara cek tarif pengiriman?', false);
    }

    public function test_home_page_uses_local_hero_image(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('images/hero.jpg', false);
        $response->assertDontSee('pixabay.com', false);
    }

    public function test_layout_links_manifest_and_apple_touch_icon(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('apple-touch-icon', false);
    }

    public function test_fonts_are_self_hosted(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('fonts/inter-400.woff2', false);
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertDontSee('fonts.gstatic.com', false);
    }

    public function test_missing_page_returns_branded_404(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada-xyz');

        $response->assertStatus(404);
        $response->assertSee('Halaman Tidak Ditemukan', false);
        $response->assertSee('Kembali ke Beranda', false);
    }

    public function test_page_titles_are_reasonably_short_and_branded(): void
    {
        $html = $this->get('/cek-tarif')->getContent();

        preg_match('/<title>(.*?)<\/title>/s', $html, $matches);
        $title = html_entity_decode(trim($matches[1] ?? ''));

        $this->assertNotSame('', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertStringContainsString('ATL Express', $title);
    }

    public function test_paginated_posts_expose_prev_next_links(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Post::create([
                'title' => "Artikel SEO {$i}",
                'content' => 'Isi artikel.',
                'status' => 'published',
                'published_at' => now()->subMinutes($i),
            ]);
        }

        $this->get('/berita')->assertSee('rel="next"', false);
        $this->get('/berita?page=2')->assertSee('rel="prev"', false);
    }
}
