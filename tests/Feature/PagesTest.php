<?php

namespace Tests\Feature;

use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_home_page_is_ok(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-N13TSG0WJ0', false);
        $response->assertSee("gtag('config', 'G-N13TSG0WJ0');", false);
    }

    public function test_home_page_exposes_open_graph_image_metadata(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('images/og-default.png', false);
        $response->assertSee('property="og:image:width"', false);
        $response->assertSee('property="og:image:height"', false);
    }

    public function test_about_page_is_ok(): void
    {
        $response = $this->get('/tentang-kami');
        $response->assertStatus(200);
    }

    public function test_services_page_is_ok(): void
    {
        $response = $this->get('/layanan');
        $response->assertStatus(200);
    }

    public function test_tracking_page_is_ok(): void
    {
        $response = $this->get('/cek-resi');
        $response->assertStatus(200);
    }

    public function test_tariff_page_is_ok(): void
    {
        $response = $this->get('/cek-tarif');
        $response->assertStatus(200);
    }

    public function test_posts_index_page_is_ok(): void
    {
        $response = $this->get('/berita');
        $response->assertStatus(200);
    }

    public function test_contact_page_is_ok(): void
    {
        $response = $this->get('/kontak');
        $response->assertStatus(200);
    }

    public function test_location_page_is_ok(): void
    {
        $response = $this->get('/lokasi');
        $response->assertStatus(200);
        $response->assertSee('Google Maps', false);
        $response->assertSee('Google Business Profile', false);
        $response->assertSee('Instagram', false);
        $response->assertSee('douzekargo', false);
        $response->assertSee('TikTok', false);
    }
}
