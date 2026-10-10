<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PricelistSeeder extends Seeder
{
    /**
     * File CSV pricelist hasil ekspor, relatif terhadap database_path('seeders/data').
     */
    private const FILES = [
        'output_pricelist_darat.csv',
        'output_pricelist_udara.csv',
    ];

    /**
     * Jumlah baris yang ditulis per satu kelompok query.
     */
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $now = Carbon::now();
        $records = [];

        foreach (self::FILES as $filename) {
            $file = database_path('seeders/data/'.$filename);

            if (! is_file($file)) {
                $this->command->error('File pricelist tidak ditemukan: '.$file);

                continue;
            }

            $rows = array_map('str_getcsv', file($file));

            if ($rows === []) {
                continue;
            }

            $header = array_map(fn ($column): string => trim((string) $column), array_shift($rows));

            foreach ($rows as $row) {
                if (count($row) < 6) {
                    continue;
                }

                $data = array_combine($header, array_slice(array_pad($row, count($header), null), 0, count($header)));

                $originCity = trim((string) ($data['origin_city'] ?? ''));
                $kabupaten = $this->normalizeKabupaten((string) ($data['kabupaten_tujuan'] ?? ''));
                $destinationCity = trim((string) ($data['destination_city'] ?? ''));
                $serviceType = trim((string) ($data['service_type'] ?? ''));

                if ($originCity === '' || $destinationCity === '' || $serviceType === '') {
                    continue;
                }

                $minWeight = $this->parseWeight((string) ($data['min_weight'] ?? '0'));

                // Kunci unik rute + kabupaten + layanan + tier berat, supaya satu
                // rute bisa punya beberapa tier dan kota yang sama di dua
                // kabupaten tetap tersimpan terpisah. Baris duplikat dengan kunci
                // sama membuat baris terakhir menang.
                $records[$originCity.'|'.$kabupaten.'|'.$destinationCity.'|'.$serviceType.'|'.$minWeight] = [
                    'origin_city' => $originCity,
                    'kabupaten_tujuan' => $kabupaten === '' ? null : $kabupaten,
                    'destination_city' => $destinationCity,
                    'service_type' => $serviceType,
                    'min_weight' => $minWeight,
                    'price_per_kg' => (float) trim((string) ($data['price_per_kg'] ?? '0')),
                    'estimated_days' => $this->parseDays((string) ($data['estimated_days'] ?? '')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Bangun ulang seluruh tarif: hapus data lama, isi dengan CSV terbaru.
        ShippingRate::query()->delete();

        foreach (array_chunk(array_values($records), self::CHUNK_SIZE) as $chunk) {
            ShippingRate::insert($chunk);
        }

        $this->command->info('Pricelist seeded ulang: '.count($records).' tarif.');
    }

    /**
     * Rapikan prefiks wilayah agar konsisten untuk pencarian: varian "kab",
     * "Kab.", "Kab ", dan "Kab.Pinrang" semuanya menjadi "Kab ...". Begitu pula
     * "Kota." menjadi "Kota ...".
     */
    private function normalizeKabupaten(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/^kab\.?\s*/i', 'Kab ', $value);
        $value = preg_replace('/^kota\.?\s*/i', 'Kota ', $value);

        return trim((string) $value);
    }

    /**
     * "50 kg" -> 50.0
     */
    private function parseWeight(string $value): float
    {
        return (float) trim(str_ireplace('kg', '', $value));
    }

    /**
     * "8-9 HARI" -> "8-9". Rentang disimpan apa adanya sebagai teks.
     */
    private function parseDays(string $value): string
    {
        return trim(str_ireplace('HARI', '', $value));
    }
}
