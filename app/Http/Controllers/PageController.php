<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Setting;
use App\Services\SeoService;

class PageController extends Controller
{
    public function about()
    {
        $settings = $this->getSettings();
        $services = Service::active()->ordered()->get();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Tentang Kami',
            'description' => ($settings['about_text'] ?? '')
                ?: 'Mengenal lebih dekat ATL Express, partner logistik terpercaya Anda.',
            'canonical' => route('about'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('about'), 'Tentang Kami'],
                ]),
            ],
        ];

        return view('about', compact('settings', 'services', 'seo'));
    }

    public function services()
    {
        $settings = $this->getSettings();
        $services = Service::active()->ordered()->get();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Layanan',
            'description' => 'Layanan pengiriman cargo darat, laut, udara, dan express ke seluruh Indonesia dengan tarif kompetitif dan tracking real-time.',
            'canonical' => route('services'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('services'), 'Layanan'],
                ]),
            ],
        ];

        return view('services', compact('settings', 'services', 'seo'));
    }

    public function serviceDetail($slug)
    {
        $service = Service::where('slug', $slug)->firstOrFail();
        $settings = $this->getSettings();

        $description = $service->meta_description
            ?: (SeoService::description($service->content) ?: (string) $service->short_description);

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => $service->meta_title ?: $service->title,
            'description' => $description,
            'canonical' => route('service.detail', $service->slug),
            'type' => 'service',
            'image' => $service->image_url ?: ($settings['og_image'] ?: asset('images/logo.png')),
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $service->title,
                    'description' => $description,
                    'url' => route('service.detail', $service->slug),
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => $settings['company_name'] ?? 'ATL Express',
                        'url' => url('/'),
                    ],
                ],
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('services'), 'Layanan'],
                    [route('service.detail', $service->slug), $service->title],
                ]),
            ],
        ];

        return view('service-detail', compact('service', 'settings', 'seo'));
    }

    public function tracking()
    {
        $settings = $this->getSettings();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Cek Resi',
            'description' => 'Lacak status pengiriman barang Anda secara real-time dengan mudah di ATL Express menggunakan nomor resi.',
            'canonical' => route('tracking'),
        ];

        return view('tracking', compact('settings', 'seo'));
    }

    public function tariff()
    {
        $settings = $this->getSettings();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Cek Tarif',
            'description' => 'Cek tarif pengiriman cargo darat, laut, dan udara ke berbagai kota di Indonesia dengan sistem ATL Express.',
            'canonical' => route('tariff'),
        ];

        return view('tariff', compact('settings', 'seo'));
    }

    public function contact()
    {
        $settings = $this->getSettings();

        $seo = $this->baseSeo($settings);
        $seo += [
            'title' => 'Kontak',
            'description' => 'Hubungi tim ATL Express untuk konsultasi kebutuhan cargo dan logistik Anda. Kami siap membantu 24 jam.',
            'canonical' => route('contact'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('contact'), 'Kontak'],
                ]),
            ],
        ];

        return view('contact', compact('settings', 'seo'));
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
            'about_text',
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