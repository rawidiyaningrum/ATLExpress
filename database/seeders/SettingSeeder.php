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
            'instagram' => 'https://instagram.com/atlexpress',
            'youtube' => 'https://youtube.com/atlexpress',
            'seo_title' => 'Jasa Cargo & Logistik Terpercaya | ATL Express',
            'seo_description' => 'ATL Express melayani jasa pengiriman cargo darat, laut, dan udara ke seluruh Indonesia. Cek tarif dan lacak pengiriman secara online, aman dan transparan.',
            'seo_keywords' => 'cargo jakarta, jasa ekspedisi, pengiriman barang, cargo laut, cargo udara, ekspedisi murah, logistik indonesia, atl express',
            'og_image' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}