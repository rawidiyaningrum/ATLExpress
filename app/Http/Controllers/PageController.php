<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Post;
use App\Models\Setting;

class PageController extends Controller
{
    public function about()
    {
        $settings = $this->getSettings();
        $services = Service::active()->ordered()->get();

        return view('about', compact('settings', 'services'));
    }

    public function services()
    {
        $services = Service::active()->ordered()->get();

        return view('services', compact('services'));
    }

    public function serviceDetail($slug)
    {
        $service = Service::where('slug', $slug)->firstOrFail();

        return view('service-detail', compact('service'));
    }

    public function tracking()
    {
        return view('tracking');
    }

    public function tariff()
    {
        return view('tariff');
    }

    public function contact()
    {
        $settings = $this->getSettings();

        return view('contact', compact('settings'));
    }

    protected function getSettings(): array
    {
        $keys = [
            'company_name', 'phone', 'email', 'address', 'whatsapp',
            'facebook', 'instagram', 'youtube',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, '');
        }

        return $settings;
    }
}
