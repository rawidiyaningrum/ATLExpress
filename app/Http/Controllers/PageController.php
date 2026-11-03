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
        $seo = array_merge($seo, [
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
        ]);

        return view('about', compact('settings', 'services', 'seo'));
    }

    public function services()
    {
        $settings = $this->getSettings();
        $services = Service::active()->ordered()->get();

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => 'Layanan',
            'description' => 'Layanan pengiriman cargo darat, laut, udara, dan express ke seluruh Indonesia dengan tarif kompetitif dan tracking real-time.',
            'canonical' => route('services'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('services'), 'Layanan'],
                ]),
            ],
        ]);

        return view('services', compact('settings', 'services', 'seo'));
    }

    public function serviceDetail($slug)
    {
        $service = Service::active()->where('slug', $slug)->firstOrFail();
        $settings = $this->getSettings();

        $description = $service->meta_description
            ?: (SeoService::description($service->content) ?: (string) $service->short_description);

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => $service->meta_title ?: $service->title,
            'description' => $description,
            'canonical' => route('service.detail', $service->slug),
            'type' => 'website',
            'image' => $service->image_url ?: ($settings['og_image'] ?: asset('images/og-default.png')),
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $service->title,
                    'description' => $description,
                    'url' => route('service.detail', $service->slug),
                    'provider' => [
                        '@id' => SeoService::organizationId(),
                    ],
                ],
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('services'), 'Layanan'],
                    [route('service.detail', $service->slug), $service->title],
                ]),
            ],
        ]);

        return view('service-detail', compact('service', 'settings', 'seo'));
    }

    public function tracking()
    {
        $settings = $this->getSettings();
        $faq = $this->trackingFaq();

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => 'Cek Resi',
            'description' => 'Lacak status pengiriman barang Anda secara real-time dengan mudah di ATL Express menggunakan nomor resi.',
            'canonical' => route('tracking'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('tracking'), 'Cek Resi'],
                ]),
                SeoService::faqPage($faq),
            ],
        ]);

        return view('tracking', compact('settings', 'seo', 'faq'));
    }

    public function tariff()
    {
        $settings = $this->getSettings();
        $faq = $this->tariffFaq();

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => 'Cek Tarif',
            'description' => 'Cek tarif pengiriman cargo darat, laut, dan udara ke berbagai kota di Indonesia dengan sistem ATL Express.',
            'canonical' => route('tariff'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('tariff'), 'Cek Tarif'],
                ]),
                SeoService::faqPage($faq),
            ],
        ]);

        return view('tariff', compact('settings', 'seo', 'faq'));
    }

    /**
     * @return array<int, array{q: string, a: string}>
     */
    protected function trackingFaq(): array
    {
        return [
            [
                'q' => 'Bagaimana cara cek resi ATL Express?',
                'a' => 'Masukkan nomor resi pengiriman Anda pada kolom pencarian di halaman Cek Resi, lalu klik tombol Lacak. Status terbaru pengiriman akan langsung ditampilkan.',
            ],
            [
                'q' => 'Di mana saya bisa menemukan nomor resi?',
                'a' => 'Nomor resi tercantum pada bukti pengiriman (airway bill) atau invoice yang kami kirimkan melalui email maupun WhatsApp setelah barang Anda diproses.',
            ],
            [
                'q' => 'Apakah status pengiriman diperbarui secara real-time?',
                'a' => 'Ya. Setiap perubahan status, mulai dari barang dijemput, dalam perjalanan, hingga diterima, dicatat oleh kurir dan langsung tampil di halaman pelacakan.',
            ],
            [
                'q' => 'Nomor resi saya tidak ditemukan, apa yang harus dilakukan?',
                'a' => 'Pastikan nomor resi diketik dengan benar tanpa spasi. Jika masih tidak ditemukan, hubungi tim customer service kami melalui WhatsApp agar dibantu pengecekan.',
            ],
        ];
    }

    /**
     * @return array<int, array{q: string, a: string}>
     */
    protected function tariffFaq(): array
    {
        return [
            [
                'q' => 'Bagaimana cara cek tarif pengiriman?',
                'a' => 'Pilih kota asal dan kota tujuan, masukkan berat barang, lalu sistem akan menampilkan estimasi tarif pengiriman secara otomatis.',
            ],
            [
                'q' => 'Apa saja yang memengaruhi tarif pengiriman?',
                'a' => 'Tarif dipengaruhi oleh berat aktual atau berat volume, jarak dan rute tujuan, jenis layanan (darat, laut, udara, atau express), serta kebutuhan tambahan seperti packing kayu atau asuransi.',
            ],
            [
                'q' => 'Apakah tarif sudah termasuk asuransi?',
                'a' => 'Tarif dasar belum termasuk asuransi. Anda dapat menambahkan asuransi pengiriman apabila diperlukan pada saat proses pemesanan.',
            ],
            [
                'q' => 'Apakah ada harga khusus untuk pengiriman jumlah besar?',
                'a' => 'Ya. Untuk pengiriman rutin atau bervolume besar, hubungi tim kami untuk mendapatkan penawaran harga korporat yang lebih kompetitif.',
            ],
        ];
    }

    public function location()
    {
        $settings = $this->getSettings();

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => 'Lokasi & Profil Bisnis Resmi',
            'description' => 'Temukan lokasi kantor dan hub operasional ATL Express di Google Maps, Google Business Profile resmi, serta update kegiatan pengiriman di Instagram & TikTok.',
            'canonical' => route('location'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('location'), 'Lokasi & Profil'],
                ]),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'LocalBusiness',
                    'name' => $settings['company_name'] ?: 'ATL Express',
                    'image' => $settings['og_image'] ?: asset('images/logo-192.png'),
                    '@id' => route('location') . '#localbusiness',
                    'url' => route('location'),
                    'telephone' => $settings['phone'] ?: '+6285777079581',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $settings['address'] ?: 'Jakarta, Indonesia',
                        'addressLocality' => 'Jakarta',
                        'addressRegion' => 'DKI Jakarta',
                        'addressCountry' => 'ID',
                    ],
                    'openingHoursSpecification' => [
                        [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                            'opens' => '08:00',
                            'closes' => '18:00',
                        ],
                    ],
                    'sameAs' => array_values(array_filter([
                        $settings['google_business_url'] ?? '',
                        $settings['instagram'] ?? '',
                        $settings['tiktok'] ?? '',
                        $settings['facebook'] ?? '',
                        $settings['youtube'] ?? '',
                    ])),
                ],
            ],
        ]);

        return view('location', compact('settings', 'seo'));
    }

    public function contact()
    {
        $settings = $this->getSettings();

        $seo = $this->baseSeo($settings);
        $seo = array_merge($seo, [
            'title' => 'Kontak',
            'description' => 'Hubungi tim ATL Express untuk konsultasi kebutuhan cargo dan logistik Anda. Kami siap membantu 24 jam.',
            'canonical' => route('contact'),
            'json_ld' => [
                SeoService::breadcrumb([
                    [route('home'), 'Beranda'],
                    [route('contact'), 'Kontak'],
                ]),
            ],
        ]);

        return view('contact', compact('settings', 'seo'));
    }

    protected function baseSeo(array $settings): array
    {
        return [
            'title' => $settings['company_name'] ?: 'ATL Express',
            'append_name' => true,
            'description' => $settings['seo_description']
                ?: ($settings['about_text'] ?: 'Solusi cargo dan logistik terpercaya di Indonesia'),
            'keywords' => $settings['seo_keywords'] ?: '',
            'type' => 'website',
            'image' => $settings['og_image'] ?: asset('images/og-default.png'),
            'json_ld' => [],
        ];
    }

    protected function getSettings(): array
    {
        $keys = [
            'company_name', 'company_tagline', 'phone', 'email', 'address', 'website', 'whatsapp',
            'about_text', 'vision', 'mission',
            'facebook', 'instagram', 'youtube', 'tiktok',
            'google_maps_embed_url', 'google_business_url',
            'seo_title', 'seo_description', 'seo_keywords', 'og_image',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getValue($key, '');
        }

        return $settings;
    }
}
