<?php

namespace App\Services\AI;

/**
 * Menyusun brief per artikel agar tiap artikel memakai sudut pandang berbeda
 * sehingga isi dalam satu batch tidak terlihat duplikat.
 */
class ArticleBrief
{
    /**
     * @var array<string, string>
     */
    private const ANGLES = [
        'panduan lengkap' => 'Panduan lengkap dari A sampai Z: apa itu, cara kerjanya, dan alur prosesnya',
        'cara memilih' => 'Checklist praktis memilih penyedia: apa yang perlu dicek dan kesalahan umum yang harus dihindari',
        'biaya dan penghematan' => 'Komponen biaya, faktor yang memengaruhi tarif, dan strategi menghemat ongkir',
        'proses pengiriman' => 'Alur kerja operasional: persiapan barang, administrasi, penjemputan, dan pelacakan',
        'dokumen dan persyaratan' => 'Dokumen yang dibutuhkan, persyaratan tiap jenis barang, dan hal yang sering terlewat',
        'perbandingan moda pengiriman' => 'Perbandingan moda darat, laut, dan udara dari sisi biaya, waktu tempuh, serta kesesuaian barang',
        'kemasan dan perlindungan barang' => 'Cara mengemas, menandai, dan melindungi barang bernilai atau mudah rusak',
        'tracking dan transparansi' => 'Cara memantau status kiriman, membaca informasi resi, dan apa yang perlu dicatat',
        'untuk UMKM dan seller online' => 'Kebutuhan UMKM, seller marketplace, dan pengiriman sampel kecil',
        'pengalaman dan simulasi pengiriman' => 'Simulasi pengiriman nyata: kronologi, kendala yang muncul, dan pembelajarannya',
        'pertanyaan yang sering muncul' => 'Format tanya jawab: jawaban atas pertanyaan yang paling sering diajukan pembaca',
        'kendala dan penyelesaian masalah' => 'Masalah yang sering terjadi di lapangan dan cara mengatasinya',
    ];

    /**
     * @var array<int, string>
     */
    private const VARIANTS = [
        'Ringkas dan fokus pada poin utama',
        'Lebih teknis dan mendalam',
        'Untuk pembaca pemula yang baru stumbled topik ini',
        'Dengan contoh kasus nyata',
        'Berbentuk daftar periksa yang bisa langsung dijalankan',
        'Berorientasi pada pertanyaan dan jawaban singkat',
    ];

    /**
     * @return array{angle: string, focus: string, variant: string}
     */
    public function forIndex(int $index): array
    {
        $index = max(1, $index);
        $angles = array_keys(self::ANGLES);
        $total = count($angles);

        $position = ($index - 1) % $total;
        $cycle = intdiv($index - 1, $total);

        $angle = $angles[$position];
        $variant = '';

        if ($cycle > 0) {
            $variant = self::VARIANTS[($cycle - 1) % count(self::VARIANTS)];
        }

        return [
            'angle' => $angle,
            'focus' => self::ANGLES[$angle],
            'variant' => $variant,
        ];
    }

    public function totalAngles(): int
    {
        return count(self::ANGLES);
    }
}
