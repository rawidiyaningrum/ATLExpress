<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use App\Services\SeoService;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()->latest('published_at')->paginate(9);
        $settings = $this->getSettings();

        $canonical = $posts->currentPage() > 1
            ? url()->current() . '?page=' . $posts->currentPage()
            : route('posts.index');

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Berita & Artikel',
            'description' => 'Berita terbaru dan artikel seputar industry logistik, tips pengiriman, dan informasi ATL Express.',
            'canonical' => $canonical,
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('posts.index'), 'Berita'],
                ]),
            ],
        ];

        return view('posts.index', compact('posts', 'settings', 'seo'));
    }

    public function show($slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $settings = $this->getSettings();

        $title = $post->meta_title ?: $post->title;
        $description = $post->meta_description ?: SeoService::description($post->content);
        $published = $post->published_at ? $post->published_at->toIso8601String() : now()->toIso8601String();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => $title,
            'description' => $description,
            'canonical' => route('posts.show', $post->slug),
            'type' => 'article',
            'image' => $post->image ?: ($settings['og_image'] ?: asset('images/logo.png')),
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'NewsArticle',
                    'headline' => $post->title,
                    'description' => $description,
                    'image' => $post->image ?: asset('images/logo.png'),
                    'datePublished' => $published,
                    'dateModified' => $post->updated_at->toIso8601String(),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => $settings['company_name'] ?? 'ATL Express',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => $settings['company_name'] ?? 'ATL Express',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => asset('images/logo.png'),
                        ],
                    ],
                    'mainEntityOfPage' => route('posts.show', $post->slug),
                ],
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('posts.index'), 'Berita'],
                    [route('posts.show', $post->slug), $post->title],
                ]),
            ],
        ];

        return view('posts.show', compact('post', 'settings', 'seo'));
    }

    protected function baseSeo(array $settings): array
    {
        return [
            'title' => $settings['company_name'] ?? 'ATL Express',
            'append_name' => true,
            'description' => $settings['seo_description']
                ?? ($settings['about_text'] ?: 'Solusi cargo dan logistik terpercaya di Indonesia'),
            'keywords' => $settings['seo_keywords'] ?? '',
            'type' => 'website',
            'image' => $settings['og_image'] ?: asset('images/logo.png'),
            'json_ld' => [],
        ];
    }

    protected function getSettings(): array
    {
        $keys = [
            'company_name', 'company_tagline', 'phone', 'email', 'address', 'whatsapp',
            'facebook', 'instagram', 'youtube',
            'seo_title', 'seo_description', 'seo_keywords', 'og_image',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, '');
        }

        return $settings;
    }
}