<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $services = Service::active()->get();
        $posts = Post::published()->get();

        // Halaman statis tidak punya timestamp sendiri. Pakai waktu update
        // konten terbaru (layanan, artikel, atau setting) sebagai lastmod.
        $lastmod = collect()
            ->merge($services->pluck('updated_at'))
            ->merge($posts->pluck('updated_at'))
            ->merge(Setting::query()->pluck('updated_at'))
            ->filter()
            ->max()
            ?->toW3cString();

        $urls = [];

        $urls[] = [
            'loc' => route('home'),
            'lastmod' => $lastmod,
            'priority' => '1.0',
            'changefreq' => 'daily',
        ];

        foreach ([
            route('about'),
            route('services'),
            route('tracking'),
            route('tariff'),
            route('posts.index'),
            route('contact'),
        ] as $url) {
            $urls[] = [
                'loc' => $url,
                'lastmod' => $lastmod,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach ($services as $service) {
            $urls[] = [
                'loc' => route('service.detail', $service->slug),
                'lastmod' => $service->updated_at->toW3cString(),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('posts.show', $post->slug),
                'lastmod' => $post->updated_at->toW3cString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap', compact('urls'))->render();

        return response($xml)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
