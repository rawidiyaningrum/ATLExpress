<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $urls[] = [
            'loc' => route('home'),
            'priority' => '1.0',
            'changefreq' => 'daily',
        ];

        foreach ([
            'about' => route('about'),
            'services' => route('services'),
            'tracking' => route('tracking'),
            'tariff' => route('tariff'),
            'posts.index' => route('posts.index'),
            'contact' => route('contact'),
        ] as $key => $url) {
            $urls[] = [
                'loc' => $url,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach (Service::active()->get() as $service) {
            $urls[] = [
                'loc' => route('service.detail', $service->slug),
                'lastmod' => $service->updated_at->toW3cString(),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach (Post::published()->get() as $post) {
            $urls[] = [
                'loc' => route('posts.show', $post->slug),
                'lastmod' => $post->updated_at->toW3cString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }
}