<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    public function test_home_page_is_ok(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
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
}