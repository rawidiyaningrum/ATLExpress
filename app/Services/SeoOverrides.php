<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Menerapkan nilai SEO manual dari form di atas nilai hasil generate.
 *
 * Nilai kosong berarti pakai hasil generate. Nilai yang diisi menang dan
 * placeholder {keyword}, {title}, {index} diganti isinya.
 */
class SeoOverrides
{
    /**
     * @param  array<string, mixed>  $article
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function apply(array $article, array $options, string $keyword, int $index): array
    {
        $replacements = [
            '{keyword}' => Str::lower($keyword),
            '{title}' => (string) ($article['title'] ?? ''),
            '{index}' => (string) $index,
        ];

        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $custom = trim((string) ($options[$field] ?? ''));

            if ($custom === '') {
                continue;
            }

            $article[$field] = trim(strtr($custom, $replacements));
        }

        return $article;
    }
}
