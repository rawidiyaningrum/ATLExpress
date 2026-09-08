<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $berita = Category::where('slug', 'berita')->first();
        $tips = Category::where('slug', 'tips-tutorial')->first();
        $kabar = Category::where('slug', 'kabar-perusahaan')->first();

        $posts = [
            [
                'title' => 'ATL Express Buka Rute Pengiriman Baru ke Papua',
                'slug' => 'atl-express-buka-rute-pengiriman-baru-ke-papua',
                'category_id' => $berita?->id,
                'content' => 'ATL Express resmi membuka rute pengiriman baru ke Papua. Layanan ini hadir untuk memenuhi kebutuhan pengiriman barang bagi masyarakat dan pelaku usaha di wilayah timur Indonesia. Dengan rute baru ini, pengiriman ke Papua kini lebih cepat, aman, dan terjangkau melalui jalur laut maupun udara.',
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'title' => 'Tips Mengirim Barang Aman dan Cepat',
                'slug' => 'tips-mengirim-barang-aman-dan-cepat',
                'category_id' => $tips?->id,
                'content' => 'Agar barang Anda sampai dengan aman dan cepat, pastikan kemasan menggunakan material yang kokoh, berikan pelindung tambahan untuk barang yang mudah pecah, dan selalu lengkapi informasi alamat penerima dengan jelas. Gunakan layanan pelacakan (tracking) untuk memantau posisi kiriman Anda secara real-time hingga tiba di tujuan.',
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'title' => 'ATL Express Resmi Luncurkan Sistem Tracking Online',
                'slug' => 'atl-express-resmi-luncurkan-sistem-tracking-online',
                'category_id' => $kabar?->id,
                'content' => 'ATL Express dengan bangga meluncurkan sistem tracking online yang memungkinkan pelanggan memantau posisi kiriman secara real-time. Cukup masukkan nomor resi pada halaman tracking, Anda dapat melihat riwayat perjalanan barang mulai dari diterima, dalam perjalanan, hingga sampai di tujuan. Sistem ini merupakan wujud komitmen kami terhadap transparansi layanan.',
                'status' => 'published',
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => $post['slug']],
                $post
            );
        }
    }
}