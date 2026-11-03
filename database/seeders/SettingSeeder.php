<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name' => 'ATL Express',
            'company_tagline' => 'Cargo & Logistics',
            'phone' => '+62 857-7707-9581',
            'phone_contact' => 'Herinsa',
            'email' => 'cs.atlexpress@gmail.com',
            'website' => 'atlexpress.biz.id',
            'address' => 'Jakarta, Indonesia',
            'whatsapp' => '6285777079581',
            'hero_title' => 'ERA BARU LAYANAN KARGO & LOGISTIK',
            'hero_subtitle' => 'Cepat, Transparan, Tepat Waktu — Kini Lebih Mudah dengan Sistem Terpadu.',
            'about_text' => 'ATL Express adalah perusahaan cargo dan logistik yang menyediakan layanan pengiriman barang terpercaya di seluruh Indonesia. Dengan jaringan yang luas dan armada yang lengkap, kami memastikan setiap kiriman sampai dengan aman dan tepat waktu.',
            'vision' => 'Menjadi perusahaan logistik terdepan di Indonesia yang menghadirkan layanan cargo terpercaya, cepat, dan terjangkau bagi seluruh masyarakat.',
            'mission' => 'Menyediakan layanan pengiriman yang cepat dan aman.|Memiliki jangkauan pengiriman ke seluruh pelosok Indonesia.|Menghadirkan teknologi tracking real-time untuk transparansi pengiriman.|Memberikan pelayanan pelanggan terbaik dengan harga kompetitif.',
            'facebook' => 'https://facebook.com/atlexpress',
            'instagram' => 'https://instagram.com/douzekargo',
            'youtube' => 'https://youtube.com/atlexpress',
            'tiktok' => 'https://tiktok.com/@atlexpress',
            'google_maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.28628095436!2d106.759478!3d-6.229728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e94d436811%3A0x2cb6e0370b3b4!2sJakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid',
            'google_business_url' => 'https://maps.google.com/?q=ATL+Express+Jakarta',
            'seo_title' => 'Jasa Cargo & Logistik Terpercaya | ATL Express',
            'seo_description' => 'ATL Express melayani jasa pengiriman cargo darat, laut, dan udara ke seluruh Indonesia. Cek tarif dan lacak pengiriman secara online, aman dan transparan.',
            'seo_keywords' => 'cargo jakarta, jasa ekspedisi, pengiriman barang, cargo laut, cargo udara, ekspedisi murah, logistik indonesia, atl express',
            'og_image' => '',
            'receipt_signer_name' => 'Harimsa S.H',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
