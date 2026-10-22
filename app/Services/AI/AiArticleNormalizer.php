<?php

namespace App\Services\AI;

use App\Services\ArticleSeoGenerator;
use Database\Seeders\BaseArticleSeeder;
use Illuminate\Support\Str;

/**
 * Membersihkan balasan AI lalu memetakannya ke struktur artikel internal.
 *
 * Semua teks dibersihkan dari HTML karena render() menyusun HTML dari teks polos
 * secara langsung. Link pada blok CTA dibuat di sini, bukan oleh AI, supaya
 * model tidak bisa mengarang URL.
 */
class AiArticleNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalize(array $payload): array
    {
        $title = $this->text($payload['title'] ?? '', 200);
        $lead = $this->text($payload['lead'] ?? '', 1500);

        $sections = [];

        foreach ((array) ($payload['sections'] ?? []) as $section) {
            $section = is_array($section) ? $section : [];

            $heading = $this->text($section['heading'] ?? '', 160);
            $body = $this->text($section['body'] ?? '', 2500);

            if ($heading === '' || $body === '') {
                continue;
            }

            $bullets = [];

            foreach ((array) ($section['bullets'] ?? []) as $bullet) {
                $bullet = $this->text($bullet, 300);

                if ($bullet !== '') {
                    $bullets[] = $bullet;
                }
            }

            $sections[] = [$heading, $body, array_slice($bullets, 0, 6)];
        }

        $tips = [];

        foreach ((array) ($payload['tips'] ?? []) as $tip) {
            $tip = $this->text($tip, 300);

            if ($tip !== '') {
                $tips[] = $tip;
            }
        }

        $ctaHeading = $this->text($payload['cta_heading'] ?? '', 160);
        $ctaBody = $this->text($payload['cta_body'] ?? '', 800);

        if ($title === '' || $lead === '' || count($sections) < 2) {
            throw new AiException('Balasan AI tidak lengkap (judul, lead, atau bagian wajib hilang).');
        }

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'lead' => $lead,
            'sections' => $sections,
            'tips_title' => $this->text($payload['tips_title'] ?? '', 160) ?: 'Tips Praktis',
            'tips' => array_slice($tips, 0, 8),
            'ctas' => [[$ctaHeading ?: 'Hubungi Tim Kami', $this->withCtaLinks($ctaBody)]],
            'meta_title' => $this->metaTitle($this->text($payload['meta_title'] ?? '', 200)),
            'meta_description' => $this->metaDescription($this->text($payload['meta_description'] ?? '', 400)),
            'meta_keywords' => $this->metaKeywords($this->text($payload['meta_keywords'] ?? '', 400)),
        ];
    }

    private function withCtaLinks(string $body): string
    {
        $links = ' Hubungi '.BaseArticleSeeder::link('tim ATL Express', '/kontak')
            .' untuk penawaran terbaik, atau hitung estimasi di '.BaseArticleSeeder::link('halaman cek tarif', '/cek-tarif').'.';

        return trim($body).$links;
    }

    private function metaTitle(string $value): string
    {
        return Str::limit(trim($value), ArticleSeoGenerator::TITLE_LIMIT, '');
    }

    private function metaDescription(string $value): string
    {
        return Str::limit(trim($value), ArticleSeoGenerator::DESCRIPTION_LIMIT, '');
    }

    private function metaKeywords(string $value): string
    {
        $items = [];

        foreach (explode(',', $value) as $item) {
            $item = trim($item);

            if ($item === '' || in_array(mb_strtolower($item), array_map('mb_strtolower', $items), true)) {
                continue;
            }

            $items[] = $item;
        }

        return implode(', ', array_slice($items, 0, ArticleSeoGenerator::KEYWORD_LIMIT));
    }

    /**
     * Bersihkan teks polos: decode entitas lebih dulu, buang tag HTML, lalu
     * hilangkan tanda kurung sudut yang tersisa supaya aman disisipkan ke HTML.
     */
    private function text(mixed $value, int $maxLength): string
    {
        if (! is_string($value)) {
            return '';
        }

        $clean = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = strip_tags($clean);
        $clean = str_replace(['<', '>'], '', $clean);
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        return Str::limit($clean, $maxLength, '');
    }
}
