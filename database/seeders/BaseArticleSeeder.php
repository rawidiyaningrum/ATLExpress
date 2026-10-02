<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class BaseArticleSeeder extends Seeder
{
    /**
     * Domain utama ATL Express, dipakai sebagai backlink pada seluruh artikel.
     */
    protected const SITE = 'https://atlexpress.biz.id';

    /**
     * Simpan kumpulan artikel ke tabel posts secara idempotent (berdasarkan slug).
     *
     * @param  array<int, array<string, mixed>>  $articles
     */
    protected function store(array $articles): void
    {
        $categories = Category::pluck('id', 'slug');

        foreach ($articles as $article) {
            $data = [
                'title' => $article['title'],
                'slug' => $article['slug'] ?? Str::slug($article['title']),
                'category_id' => $categories[$article['category'] ?? 'berita'] ?? null,
                'content' => $this->render($article),
                'meta_title' => $article['meta_title'] ?? Str::limit($article['title'], 60, ''),
                'meta_description' => $article['meta_description'] ?? Str::limit(strip_tags($article['lead']), 158, ''),
                'status' => $article['status'] ?? 'published',
                'published_at' => $this->publishedAt($article['days_ago'] ?? 0, $article['hour'] ?? 9),
            ];

            Post::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    /**
     * Susun HTML artikel: paragraf pembuka, heading, daftar poin, dan kotak ajakan.
     *
     * @param  array<string, mixed>  $article
     */
    private function render(array $article): string
    {
        $html = '<p>'.$article['lead'].'</p>';

        foreach ($article['sections'] as $section) {
            $html .= '<h2>'.$section[0].'</h2>';
            $html .= '<p>'.$section[1].'</p>';

            if (! empty($section[2])) {
                $html .= '<ul>';
                foreach ($section[2] as $item) {
                    $html .= '<li>'.$item.'</li>';
                }
                $html .= '</ul>';
            }
        }

        if (! empty($article['tips'])) {
            $html .= '<h2>'.($article['tips_title'] ?? 'Tips Praktis').'</h2><ul>';
            foreach ($article['tips'] as $tip) {
                $html .= '<li>'.$tip.'</li>';
            }
            $html .= '</ul>';
        }

        foreach ($article['ctas'] ?? [] as $cta) {
            $html .= '<div class="my-8 rounded-xl border border-primary/20 bg-primary/5 p-5">';
            $html .= '<p class="font-bold text-primary">'.$cta[0].'</p>';
            $html .= '<p class="mb-0">'.$cta[1].'</p>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Tanggal publikasi disebar mundur beberapa hari agar artikel terlihat hidup.
     */
    private function publishedAt(int $daysAgo, int $hour): Carbon
    {
        return now()->subDays($daysAgo)->setTime($hour, 0);
    }

    /**
     * Buat backlink ke halaman ATL Express.
     */
    public static function link(string $anchor, string $path = '/'): string
    {
        $url = self::SITE.'/'.ltrim($path, '/');

        return '<a href="'.$url.'">'.$anchor.'</a>';
    }
}
