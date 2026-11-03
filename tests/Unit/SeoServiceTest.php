<?php

namespace Tests\Unit;

use App\Services\SeoService;
use PHPUnit\Framework\TestCase;

class SeoServiceTest extends TestCase
{
    public function test_short_text_is_returned_unchanged(): void
    {
        $this->assertSame('Pendek', SeoService::description('Pendek'));
    }

    public function test_long_text_without_spaces_is_truncated_not_emptied(): void
    {
        $result = SeoService::description(str_repeat('x', 200), 160);

        $this->assertSame(str_repeat('x', 160).'...', $result);
    }

    public function test_long_text_is_cut_on_word_boundary(): void
    {
        $result = SeoService::description(str_repeat('kata ', 50), 20);

        $this->assertStringEndsWith('...', $result);
        $this->assertLessThanOrEqual(23, mb_strlen($result));
        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_html_entities_are_decoded_and_tags_stripped(): void
    {
        $this->assertSame(
            'Halo & selamat datang',
            SeoService::description('<p>Halo &amp; selamat datang</p>')
        );
    }

    public function test_blank_content_returns_empty_string(): void
    {
        $this->assertSame('', SeoService::description('   '));
    }

    public function test_short_title_is_returned_unchanged(): void
    {
        $this->assertSame('Cek Resi', SeoService::title('Cek Resi', 60));
    }

    public function test_long_title_is_truncated_on_word_boundary(): void
    {
        $result = SeoService::title(
            'Panduan Lengkap Pengiriman Barang Cargo ke Seluruh Wilayah Indonesia dengan Aman dan Tepat Waktu',
            60
        );

        $this->assertLessThanOrEqual(60, mb_strlen($result));
        $this->assertStringEndsWith('…', $result);
        $this->assertStringNotContainsString('  ', $result);
    }
}
