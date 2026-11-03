@php
    $keys = [
        'company_name', 'company_tagline', 'phone', 'email', 'address', 'website', 'whatsapp',
        'about_text', 'facebook', 'instagram', 'youtube',
        'seo_title', 'seo_description', 'seo_keywords', 'og_image',
    ];

    $settings = array_fill_keys($keys, '');

    try {
        foreach ($keys as $key) {
            $settings[$key] = \App\Models\Setting::getValue($key, '');
        }
    } catch (\Throwable $e) {
        // Biarkan default kosong bila DB tidak tersedia.
    }

    $seo = [
        'title' => 'Halaman Tidak Ditemukan',
        'description' => 'Halaman yang Anda cari tidak ditemukan. Kembali ke beranda atau jelajahi layanan cargo dan logistik ATL Express.',
        'canonical' => url('/'),
        'robots' => 'noindex, follow',
    ];
@endphp

<x-layouts.app :settings="$settings" :seo="$seo">

    <section class="py-24 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-7xl sm:text-8xl font-extrabold text-primary">404</p>
            <h1 class="mt-4 text-3xl font-extrabold text-primary">Halaman Tidak Ditemukan</h1>
            <p class="mt-4 text-gray-600 leading-relaxed">
                Maaf, halaman yang Anda cari tidak tersedia atau sudah dipindahkan.
                Silakan gunakan tautan di bawah untuk melanjutkan.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-full text-sm font-bold transition hover:bg-primary-600">
                    Kembali ke Beranda
                </a>
                <a href="{{ route('services') }}" class="inline-flex items-center gap-2 border-2 border-primary/20 text-primary px-6 py-3 rounded-full text-sm font-bold transition hover:border-primary/40">
                    Lihat Layanan
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 bg-gold text-primary-500 px-6 py-3 rounded-full text-sm font-bold transition hover:bg-gold-300">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>

</x-layouts.app>
