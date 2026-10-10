<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ShippingRateDiscountSeeder extends Seeder
{
    /**
     * Potongan harga yang diterapkan ke seluruh tarif yang sudah ada.
     */
    public const DISCOUNT_PERCENT = 50;

    /**
     * Harga akhir dibulatkan ke ribuan terdekat, bukan ke digit terakhir.
     */
    public const ROUNDING_STEP = 1000;

    /**
     * Jumlah baris yang ditulis per satu kelompok query.
     */
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $this->command->warn(sprintf(
            'Diskon %d%% akan diterapkan ke seluruh baris tarif. Seeder ini menumpuk, jadi jalankan hanya sekali.',
            self::DISCOUNT_PERCENT,
        ));

        $rates = ShippingRate::query()->get(['id', 'price_per_kg']);

        if ($rates->isEmpty()) {
            $this->command->error('Tabel tarif kosong, jalankan PricelistSeeder dulu.');

            return;
        }

        $before = (float) $rates->sum('price_per_kg');
        $updated = 0;

        $rates->chunk(self::CHUNK_SIZE)->each(function (Collection $chunk) use (&$updated): void {
            foreach ($chunk as $rate) {
                $rate->update([
                    'price_per_kg' => self::discountedPrice((float) $rate->price_per_kg),
                ]);

                $updated++;
            }
        });

        $this->command->info(sprintf(
            'Tarif didiskon: %d baris, total %.2f menjadi %.2f.',
            $updated,
            $before,
            (float) ShippingRate::query()->sum('price_per_kg'),
        ));
    }

    /**
     * Harga setelah diskon, dibulatkan ke ribuan terdekat.
     *
     * round() di PHP membulatkan setengah ke atas, jadi 6.750 menjadi 7.000
     * dan 6.375 menjadi 6.000. Harga nol dan negatif dikembalikan apa adanya
     * supaya tarif gratis tidak berubah dan data rusak tidak jadi negatif.
     */
    public static function discountedPrice(float $price): float
    {
        $factor = (100 - self::DISCOUNT_PERCENT) / 100;

        return max(0.0, round(($price * $factor) / self::ROUNDING_STEP) * self::ROUNDING_STEP);
    }
}
