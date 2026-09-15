<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Post;
use App\Models\Setting;
use App\Services\SeoService;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::active()->ordered()->get();
        $posts = Post::published()->latest('published_at')->limit(3)->get();
        $settings = $this->getSettings();

        $companyName = $settings['company_name'] ?? 'ATL Express';
        $title = $settings['seo_title'] ?? $companyName;
        $description = $settings['seo_description']
            ?? ($settings['about_text'] ?: "Solusi cargo dan logistik terpercaya di Indonesia.");

        $seo = [
            'title' => $title,
            'append_name' => false,
            'description' => $description,
            'keywords' => $settings['seo_keywords'] ?? '',
            'canonical' => route('home'),
            'type' => 'website',
            'image' => $settings['og_image'] ?: asset('images/logo.png'),
            'json_ld' => [
                SeoService::localBusiness($settings),
                SeoService::webPage($title, $description, route('home')),
            ],
        ];

        return view('home', compact('services', 'posts', 'settings', 'seo'));
    }

    protected function getSettings(): array
    {
        $keys = [
            'company_name', 'company_tagline', 'phone', 'email', 'address', 'whatsapp',
            'hero_title', 'hero_subtitle', 'about_text', 'vision', 'mission',
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