<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Database\Seeders\BaseArticleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ArticleTemplateGenerator extends BaseArticleSeeder
{
    public function __construct(private readonly ArticleSeoGenerator $seo)
    {
    }

    /**
     * Generate dan simpan artikel berbasis keyword langsung ter-publish.
     *
     * @param  array<string, mixed>  $options
     * @return array{generated: int, skipped: int, slugs: array<string>}
     */
    public function generate(string $keyword, string $categorySlug, int $count = 1, array $options = []): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return ['generated' => 0, 'skipped' => 0, 'slugs' => []];
        }

        $count = max(1, min(50, $count));

        $category = $this->resolveCategory($categorySlug);

        $articles = [];
        $slugs = [];

        for ($index = 1; $index <= $count; $index++) {
            $article = $this->buildArticle($keyword, $category?->slug ?? 'berita', $index);
            $slug = $article['slug'];

            if (Post::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $article = $this->applySeo($article, $keyword, (string) ($category?->name ?? ''), $index, $options);

            $articles[] = $article;
            $slugs[] = $slug;
        }

        if ($articles === []) {
            return ['generated' => 0, 'skipped' => $count, 'slugs' => []];
        }

        $articles = array_map(function (array $article) use ($options, $category) {
            $publishedAt = $options['published_at'] ?? null;

            if ($publishedAt instanceof Carbon) {
                $article['published_at'] = $publishedAt;
            } elseif (is_string($publishedAt) && $publishedAt !== '') {
                $article['published_at'] = Carbon::parse($publishedAt);
            } else {
                $article['published_at'] = Carbon::now()
                    ->subDays(random_int(0, 4))
                    ->setTime(random_int(8, 21), random_int(0, 59), random_int(0, 59));
            }

            $article['category'] = $category?->slug ?? ($article['category'] ?? 'berita');
            $article['status'] = 'published';

            return $article;
        }, $articles);

        $this->store($articles);

        return [
            'generated' => count($articles),
            'skipped' => $count - count($articles),
            'slugs' => $slugs,
        ];
    }

    /**
     * Susun pratinjau artikel pertama tanpa menyimpan ke database, dipakai
     * untuk preview SEO di halaman generator.
     *
     * @return array<string, mixed>
     */
    public function preview(string $keyword, string $categorySlug = 'berita'): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [
                'title' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
            ];
        }

        $category = $this->resolveCategory($categorySlug);
        $article = $this->buildArticle($keyword, $category?->slug ?? 'berita', 1);

        $article = $this->applySeo($article, $keyword, (string) ($category?->name ?? ''), 1, []);

        return [
            'title' => (string) $article['title'],
            'meta_title' => (string) $article['meta_title'],
            'meta_description' => (string) $article['meta_description'],
            'meta_keywords' => (string) $article['meta_keywords'],
        ];
    }

    /**
     * Isi metadata SEO artikel. Nilai manual dari form(admin) menang, sisanya
     * digenerate otomatis mengikuti isi artikel.
     *
     * @param  array<string, mixed>  $article
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function applySeo(array $article, string $keyword, string $categoryName, int $index, array $options): array
    {
        $seo = $this->seo->generate($keyword, $article, $categoryName, $index);

        return SeoOverrides::apply(array_merge($article, $seo), $options, $keyword, $index);
    }

    /**
     * Susun artikel versi template (tanpa AI) untuk satu index.
     *
     * Dipakai sebagai fallback ketika pemanggilan AI gagal.
     *
     * @return array<string, mixed>
     */
    public function fallbackArticle(string $keyword, string $categorySlug, int $index): array
    {
        $category = $this->resolveCategory($categorySlug);

        $article = $this->buildArticle($keyword, $category?->slug ?? 'berita', $index);

        return $this->applySeo($article, $keyword, (string) ($category?->name ?? ''), $index, []);
    }

    /**
     * Slug unik dari sebuah judul.
     */
    public function uniqueSlugFor(string $title): string
    {
        return $this->uniqueSlug(Str::slug($title));
    }

    /**
     * Simpan satu artikel sebagai published.
     */
    public function publishArticle(array $article, ?Carbon $publishedAt = null): Post
    {
        $article['category'] = $article['category'] ?? 'berita';
        $article['status'] = 'published';

        if ($publishedAt instanceof Carbon) {
            $article['published_at'] = $publishedAt;
        } else {
            $article['published_at'] = Carbon::now()
                ->subDays(random_int(0, 4))
                ->setTime(random_int(8, 21), random_int(0, 59));
        }

        $this->store([$article]);

        return Post::query()->where('slug', $article['slug'])->firstOrFail();
    }

    /**
     * Nama kategori untuk slug yang diberikan.
     */
    public function categoryName(string $categorySlug): string
    {
        return (string) ($this->resolveCategory($categorySlug)?->name ?? '');
    }

    private function resolveCategory(string $categorySlug): ?Category
    {
        return Category::query()->where('slug', $categorySlug)->first()
            ?? Category::query()->where('slug', 'berita')->first()
            ?? Category::query()->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildArticle(string $keyword, string $categorySlug, int $index): array
    {
        $keywordUc = Str::title($keyword);
        $keywordLower = Str::lower($keyword);
        $baseSlug = Str::slug($keyword);

        if ($index > 1) {
            $baseSlug = Str::slug($keyword.' '.$index);
        }

        $slug = $this->uniqueSlug($baseSlug);

        $titles = [
            'Jasa '.$keywordUc.' Terpercaya: Panduan Lengkap 2026',
            'Panduan Memilih Jasa '.$keywordUc.' untuk Bisnis Anda',
            'Tips Efisien Menggunakan Layanan '.$keywordUc.' Hemat Biaya',
            'Kelebihan dan Tips '.$keywordUc.' Tepat Sasaran',
            'Solusi '.$keywordUc.' Andal untuk Pengiriman Bisnis',
        ];

        $title = $titles[($index - 1) % count($titles)];

        $leads = [
            'Layanan '.$keywordLower.' menjadi solusi penting bagi pelaku usaha yang ingin mengirim barang dengan aman, tepat waktu, dan efisien. Artikel ini membahas panduan praktis mulai dari pemilihan penyedia hingga tips menghemat biaya.',
            'Memilih jasa '.$keywordLower.' yang tepat bisa berdampak besar pada kecepatan, biaya, dan kenyamanan pengiriman. Simak panduan lengkap berikut agar Anda terhindar dari kesalahan umum.',
            'Perkembangan kebutuhan logistik membuat layanan '.$keywordLower.' semakin dibutuhkan. Pelajari cara kerja, manfaat, dan tips memilih penyedia terpercaya untuk kebutuhan bisnis maupun pribadi.',
            'Agar pengiriman berjalan lancar, penting memahami seluk-beluk '.$keywordLower.'. Berikut penjelasan praktis yang bisa langsung Anda terapkan untuk hasil maksimal.',
            'Bagi pelaku usaha, efisiensi pengiriman jadi faktor kunci. Layanan '.$keywordLower.' yang tepat membantu menekan biaya sekaligus menjaga kepuasan pelanggan.',
        ];

        $lead = $leads[($index - 1) % count($leads)];

        $sections = [
            [
                'Apa Itu '.$keywordUc,
                ''.$keywordUc.' adalah layanan pengiriman yang dirancang untuk membantu mengangkut barang dari titik asal ke tujuan dengan penanganan terencana. Dengan perencanaan yang tepat, proses pengiriman bisa lebih aman, terukur, dan efisien sesuai kebutuhan Anda.',
                [
                    'Menyesuaikan moda pengiriman dengan jenis barang',
                    'Menjaga keamanan dan integritas muatan',
                    'Memberikan estimasi waktu dan biaya yang transparan',
                ],
            ],
            [
                'Manfaat Menggunakan Jasa '.$keywordUc,
                'Menggunakan penyedia '.$keywordLower.' profesional membantu menghemat waktu, tenaga, dan biaya dibanding mengurus pengiriman sendiri. Selain itu, koordinasi logistik jadi lebih mudah berkat dukungan tim berpengalaman.',
                [
                    'Biaya lebih efisien untuk pengiriman rutin',
                    'Penanganan aman untuk barang berharga atau sensitif',
                    'Tracking dan pelaporan status pengiriman',
                    'Dukungan dokumentasi dan konsultasi gratis',
                ],
            ],
            [
                'Cara Memilih Penyedia '.$keywordUc.' yang Tepat',
                'Sebelum memesan, pastikan penyedia '.$keywordLower.' memiliki pengalaman, jaringan luas, dan komunikasi yang jelas. Bandingkan penawaran, periksa reputasi, dan pastikan cakupan layanan sesuai kebutuhan Anda.',
                [
                    'Cek pengalaman dan testimoni pelanggan',
                    'Bandingkan estimasi biaya dan waktu tempuh',
                    'Pastikan ada layanan konsultasi dan tracking',
                    'Perhatikan transparansi biaya tanpa biaya tersembunyi',
                ],
            ],
            [
                'Tips Menghemat Biaya '.$keywordUc,
                'Dengan strategi tepat, Anda bisa menekan biaya '.$keywordLower.' tanpa mengorbankan keamanan. Konsolidasi kiriman, pemilihan moda yang sesuai, dan perencanaan jadwal jadi kunci penghematan.',
                [
                    'Konsolidasikan pengiriman untuk volume lebih besar',
                    'Pilih moda yang sesuai urgensi dan anggaran',
                    'Kirim lebih awal untuk dapat tarif lebih baik',
                    'Konsultasikan kebutuhan dengan tim ahli sejak awal',
                ],
            ],
        ];

        if ($index > 2) {
            $sections[] = [
                'Kapan Saat Tepat Menggunakan '.$keywordUc,
                'Gunakan layanan '.$keywordLower.' saat kebutuhan pengiriman bersifat rutin, membutuhkan penanganan khusus, atau ingin fokus pada bisnis tanpa repot urus logistik harian. Dengan perencanaan terjadwal, biaya bisa jauh lebih efisien.',
                [
                    'Untuk pengiriman proyek atau skala besar',
                    'Saat membutuhkan pengantaran terjadwal rutin',
                    'Untuk barang yang butuh penanganan khusus',
                ],
            ];
        }

        $tips = [
            'Siapkan data barang lengkap (berat, dimensi, tujuan) untuk estimasi akurat.',
            'Diskusikan kebutuhan khusus dengan tim '.$keywordLower.' sejak awal.',
            'Aktifkan pelacakan untuk memantau status kiriman.',
            'Manfaatkan konsultasi gratis untuk mendapat rekomendasi terbaik.',
            'Pertimbangkan asuransi untuk barang bernilai tinggi.',
        ];

        $ctas = [
            [
                'Konsultasikan Kebutuhan '.$keywordUc.' Anda',
                'Hubungi '.self::link('tim ATL Express', '/kontak').' untuk penawaran '.$keywordLower.' terbaik, atau hitung estimasi di '.self::link('halaman cek tarif', '/cek-tarif').'.',
            ],
        ];

        return [
            'title' => $title,
            'slug' => $slug,
            'category' => $categorySlug,
            'lead' => $lead,
            'sections' => $sections,
            'tips_title' => 'Tips Praktis '.$keywordUc,
            'tips' => $tips,
            'ctas' => $ctas,
            'days_ago' => 0,
            'hour' => (int) now()->format('H'),
        ];
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (Post::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;

            if ($counter > 50) {
                break;
            }
        }

        return $slug;
    }
}
