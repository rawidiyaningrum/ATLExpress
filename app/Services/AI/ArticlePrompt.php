<?php

namespace App\Services\AI;

/**
 * Menyusun system instruction, prompt per artikel, dan skema JSON output.
 */
class ArticlePrompt
{
    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'meta_title' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'meta_keywords' => ['type' => 'string'],
                'lead' => ['type' => 'string'],
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'body' => ['type' => 'string'],
                            'bullets' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['heading', 'body', 'bullets'],
                    ],
                ],
                'tips_title' => ['type' => 'string'],
                'tips' => ['type' => 'array', 'items' => ['type' => 'string']],
                'cta_heading' => ['type' => 'string'],
                'cta_body' => ['type' => 'string'],
            ],
            'required' => [
                'title',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'lead',
                'sections',
                'tips_title',
                'tips',
                'cta_heading',
                'cta_body',
            ],
        ];
    }

    public function system(string $brand): string
    {
        return implode("\n", [
            'Kamu adalah editor SEO senior '.$brand.', perusahaan jasa cargo dan logistik di Indonesia (pengiriman darat, laut, dan udara) dengan situs atlexpress.biz.id.',
            '',
            'TUGAS',
            'Tulis satu artikel blog berbahasa Indonesia yang informatif, natural, dan siap tayang.',
            '',
            'ATURAN ISI',
            '1. Panjang artikel 700-900 kata dengan 4-6 bagian (section).',
            '2. Setiap bagian berisi judul, satu paragraf 60-110 kata, lalu 3-4 poin daftar.',
            '3. Buka dengan lead 2-3 kalimat yang langsung menjawab apa topik ini dan mengapa penting bagi pembaca.',
            '4. Tutup dengan 4-6 tips praktis (tips_title dan tips) dan satu blok ajakan (cta_heading dan cta_body).',
            '5. Gunakan kalimat lugas, hindari jargon teknis yang tidak perlu, dan jangan mengulang kata yang sama terlalu sering.',
            '6. Jangan mengarang angka ongkir, estimasi waktu tempuh, nomor regulasi, atau nama perusahaan selain '.$brand.'.',
            '7. Jangan menulis tautan, alamat email, atau nomor telepon. Tautan ditambahkan sistem secara otomatis.',
            '',
            'ATURAN SEO',
            '1. title: 45-70 karakter, spesifik, dan memuat kata kunci utama.',
            '2. meta_title: maksimal 60 karakter termasuk nama brand di akhir, contoh "Panduan Memilih Cargo Laut | '.$brand.'".',
            '3. meta_description: 120-155 karakter, memuat kata kunci utama dan ajakan bertindak, tidak menyalin meta_title.',
            '4. meta_keywords: maksimal 10 kata kunci Indonesia yang dipisah tanda koma, tanpa pengulangan.',
            '5. Semua isian ditulis sebagai teks polos tanpa tag HTML.',
            '',
            'Balas hanya dengan JSON sesuai skema yang diberikan.',
        ]);
    }

    /**
     * @param  array{angle: string, focus: string, variant: string}  $brief
     */
    public function user(string $keyword, string $categoryName, string $brand, array $brief): string
    {
        $lines = [
            'Topik utama: '.$keyword,
            'Kategori artikel: '.($categoryName !== '' ? $categoryName : 'umum'),
            'Merek: '.$brand,
            'Sudut pandang yang wajib dipakai: '.$brief['angle'],
            'Fokus isi: '.$brief['focus'],
        ];

        if ($brief['variant'] !== '') {
            $lines[] = 'Gaya penulisan: '.$brief['variant'];
        }

        $lines[] = 'Pastikan judul artikel berbeda dari judul artikel lain pada topik yang sama.';

        return implode("\n", $lines);
    }
}
