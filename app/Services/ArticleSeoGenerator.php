<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Menyusun meta title, meta description, dan meta keywords untuk satu artikel.
 *
 * Nilai yang dihasilkan selalu mengikuti isi artikel (judul, lead, heading
 * seksi) sehingga setiap artikel punya metadata sendiri, bukan salinan.
 */
class ArticleSeoGenerator
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    public const KEYWORD_LIMIT = 10;

    public const KEYWORD_ITEM_LIMIT = 40;

    /**
     * Modifier per index supaya meta title dan keywords tiap artikel dalam satu
     * batch tidak kembar, walau keyword utamanya sama.
     */
    private const MODIFIERS = [
        1 => 'panduan',
        2 => 'cara memilih',
        3 => 'tips',
        4 => 'kelebihan',
        5 => 'solusi',
    ];

    /**
     * Kata umum yang dipakai untuk memotong keyphrase dari judul artikel.
     */
    private const STOP_WORDS = [
        'ada', 'adalah', 'agar', 'akan', 'alternatif', 'andy', 'awal', 'bagi', 'barang',
        'baru', 'banyak', 'bisa', 'biaya', 'caranya', 'cara', 'cek', 'dan', 'dalam',
        'dari', 'di', 'efektif', 'efisien', 'hemat', 'hingga', 'atau', 'ia', 'ingin',
        'ini', 'itu', 'jadi', 'jasa', 'jika', 'kami', 'kapan', 'kebutuhan', 'kenapa',
        'kepada', 'kita', 'layanan', 'lebih', 'macam', 'memilih', 'mulai', 'muncul',
        'pada', 'panduan', 'paling', 'para', 'penting', 'praktis', 'sebelum', 'seperti',
        'sepanjang', 'solusi', 'sudah', 'supaya', 'tahun', 'tentang', 'terbaik',
        'terbukti', 'terlihat', 'terlaku', 'termurah', 'terpercaya', 'tidak', 'tutorial',
        'untuk', 'usahawan', 'waktu', 'yaitu', 'yang',
    ];

    /**
     * @param  array<string, mixed>  $article  hasil build artikel (title, lead, sections, tips_title)
     * @return array{meta_title: string, meta_description: string, meta_keywords: string}
     */
    public function generate(string $keyword, array $article, string $categoryName = '', int $index = 1): array
    {
        $keyword = trim($keyword);
        $keywordLower = Str::lower($keyword);
        $keywordUc = Str::title($keyword);
        $brand = $this->brand();

        return [
            'meta_title' => $this->metaTitle($keywordUc, $keywordLower, (string) ($article['title'] ?? ''), $brand, $index),
            'meta_description' => $this->metaDescription($keywordUc, $keywordLower, $article, $brand),
            'meta_keywords' => $this->metaKeywords($keywordLower, $article, $categoryName, $index),
        ];
    }

    /**
     * Meta title maksimal 60 karakter termasuk nama brand, dipotong di batas kata.
     */
    private function metaTitle(string $keywordUc, string $keywordLower, string $title, string $brand, int $index): string
    {
        $suffix = $brand !== '' ? ' | '.$brand : '';
        $budget = max(12, self::TITLE_LIMIT - mb_strlen($suffix));

        $withoutYear = $this->stripYear($title);
        $beforeColon = trim(Str::before($withoutYear, ':'));
        $modifier = self::MODIFIERS[$index] ?? self::MODIFIERS[1];

        $candidates = array_filter([
            $beforeColon,
            $withoutYear,
            $modifier.' '.$keywordLower,
            'jasa '.$keywordLower,
            $keywordUc,
        ]);

        foreach ($candidates as $candidate) {
            $candidate = $this->trimTo($candidate, $budget);

            if ($candidate !== '') {
                return $candidate.$suffix;
            }
        }

        return $this->trimTo($keywordUc, $budget).$suffix;
    }

    /**
     * Meta description maksimal 155 karakter, keyword tetap muncul di awal.
     */
    private function metaDescription(string $keywordUc, string $keywordLower, array $article, string $brand): string
    {
        $lead = $this->plainText((string) ($article['lead'] ?? ''));

        if ($lead === '') {
            $lead = 'Panduan praktis untuk '.($keywordLower !== '' ? $keywordLower.' ' : '').'pengiriman barang yang aman dan efisien.';
        }

        if ($keywordLower !== '' && ! Str::contains(Str::lower($lead), $keywordLower)) {
            $lead = $keywordUc.': '.$lead;
        }

        $cta = $brand !== '' ? ' Pelajari di '.$brand.'.' : '';
        $description = $this->trimTo($lead, self::DESCRIPTION_LIMIT);

        if ($cta !== '' && mb_strlen($description.$cta) <= self::DESCRIPTION_LIMIT) {
            $description .= $cta;
        }

        return $description;
    }

    /**
     * Meta keywords: daftar singkat, unik, dan berasal dari isi artikel.
     */
    private function metaKeywords(string $keywordLower, array $article, string $categoryName, int $index): string
    {
        $modifier = self::MODIFIERS[$index] ?? self::MODIFIERS[1];
        $title = $this->stripYear((string) ($article['title'] ?? ''));

        $items = [];

        if ($keywordLower !== '') {
            $items[] = $keywordLower;
            $items[] = $modifier.' '.$keywordLower;
            $items[] = 'jasa '.$keywordLower;
        }

        $titlePhrase = trim(Str::before($title, ':'));
        if ($titlePhrase !== '') {
            $items[] = $titlePhrase;
        }

        $items[] = 'atl express';

        foreach ($this->sectionHeadings($article) as $heading) {
            $items[] = $heading;
        }

        $items[] = (string) ($article['tips_title'] ?? '');

        if (trim($categoryName) !== '') {
            $items[] = trim($categoryName);
        }

        $items[] = 'ongkos kirim';
        $items[] = 'jasa cargo';

        $keywords = [];

        foreach ($items as $item) {
            $item = Str::lower($this->plainText((string) $item));
            $item = $this->trimTo($item, self::KEYWORD_ITEM_LIMIT);

            if ($item === '' || in_array($item, $keywords, true)) {
                continue;
            }

            $keywords[] = $item;

            if (count($keywords) >= self::KEYWORD_LIMIT) {
                break;
            }
        }

        return implode(', ', $keywords);
    }

    /**
     * @param  array<string, mixed>  $article
     * @return array<int, string>
     */
    private function sectionHeadings(array $article): array
    {
        $headings = [];

        foreach (($article['sections'] ?? []) as $section) {
            $heading = is_array($section) ? (string) ($section[0] ?? '') : (string) $section;

            if (trim($heading) !== '') {
                $headings[] = $heading;
            }
        }

        return $headings;
    }

    /**
     * Kata kunci inti artikel, dipakai saat keyword utama tidak diketahui
     * (misalnya artikel dari seeder statis).
     */
    public function deriveKeyphrase(string $title): string
    {
        $title = $this->stripYear($title);
        $title = trim(Str::before($title, ':'));

        if ($title === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $title) ?: [];
        $runs = [];
        $current = [];

        foreach ($words as $word) {
            $normalized = Str::lower(trim($word, ".,:;!?"));

            if ($normalized === '' || in_array($normalized, self::STOP_WORDS, true)) {
                if ($current !== []) {
                    $runs[] = $current;
                    $current = [];
                }

                continue;
            }

            $current[] = $normalized;
        }

        if ($current !== []) {
            $runs[] = $current;
        }

        if ($runs === []) {
            return Str::lower(trim($title));
        }

        usort($runs, fn (array $a, array $b): int => count($b) <=> count($a));

        return implode(' ', array_slice($runs[0], 0, 4));
    }

    private function brand(): string
    {
        return trim((string) (Setting::getValue('company_name') ?: 'ATL Express'));
    }

    private function stripYear(string $value): string
    {
        return trim((string) preg_replace('/\s*20\d{2}\s*$/u', '', trim($value)));
    }

    private function plainText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
    }

    private function trimTo(string $text, int $limit): string
    {
        $text = $this->plainText($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,:;-");
    }
}
