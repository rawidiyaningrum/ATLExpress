<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Post;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::active()->ordered()->get();
        $posts = Post::published()->latest('published_at')->limit(3)->get();
        $settings = $this->getSettings();

        return view('home', compact('services', 'posts', 'settings'));
    }

    protected function getSettings(): array
    {
        $keys = [
            'company_name', 'company_tagline', 'phone', 'email', 'address', 'whatsapp',
            'hero_title', 'hero_subtitle', 'about_text', 'vision', 'mission',
            'facebook', 'instagram', 'youtube',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, '');
        }

        return $settings;
    }
}
