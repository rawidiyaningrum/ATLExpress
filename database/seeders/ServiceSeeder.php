<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'title' => 'Pengiriman Darat',
                'slug' => 'pengiriman-darat',
                'icon' => 'truck',
                'short_description' => 'Pengiriman barang via darat dengan jangkauan luas ke seluruh kota di Indonesia',
                'content' => 'Layanan pengiriman darat ATL Express menghubungkan seluruh kota di Indonesia melalui jaringan transportasi darat yang luas dan terintegrasi. Dengan armada truk yang terawat dan sistem distribusi yang efisien, kami melayani pengiriman barang dalam jumlah besar maupun kecil dengan biaya yang terjangkau. Jaringan mitra di berbagai kota memastikan setiap kiriman tiba tepat waktu dan dalam kondisi aman.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Pengiriman Laut',
                'slug' => 'pengiriman-laut',
                'icon' => 'ship',
                'short_description' => 'Pengiriman cargo via laut untuk barang berukuran besar dan dalam jumlah banyak',
                'content' => 'Untuk kebutuhan pengiriman barang berukuran besar, berat, atau dalam jumlah banyak, layanan pengiriman laut ATL Express adalah pilihan yang tepat. Kami bekerja sama dengan pelayaran terpercaya untuk mengangkut cargo antar pulau di seluruh Nusantara. Dengan jadwal keberangkatan yang rutin dan penanganan bongkar muat yang profesional, kiriman Anda ditangani dengan standar keamanan tinggi dengan harga yang lebih hemat.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Pengiriman Udara',
                'slug' => 'pengiriman-udara',
                'icon' => 'plane',
                'short_description' => 'Pengiriman express via udara untuk kebutuhan pengiriman cepat dan prioritas',
                'content' => 'Layanan pengiriman udara ATL Express memberikan solusi tercepat untuk pengiriman barang yang mendesak dan prioritas. Dengan rute penerbangan yang mencakup kota-kota besar di Indonesia, kami memastikan kiriman Anda sampai dalam hitungan hari bahkan hari yang sama. Cocok untuk dokumen, barang bernilai tinggi, produk segar, dan kebutuhan logistik yang sensitif terhadap waktu.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Door-to-Door Service',
                'slug' => 'door-to-door-service',
                'icon' => 'home',
                'short_description' => 'Layanan penjemputan dan pengantaran langsung ke alamat tujuan',
                'content' => 'Nikmati kemudahan pengiriman dengan layanan Door-to-Door ATL Express. Kurir kami akan menjemput barang Anda langsung dari alamat pengirim dan mengantarkannya sampai ke pintu penerima. Anda tidak perlu repot datang ke kantor cabang, cukup hubungi kami dan barang Anda akan kami urus dari awal hingga akhir dengan pelacakan yang transparan.',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                $service
            );
        }
    }
}