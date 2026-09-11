<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ShippingRateSeeder extends Seeder
{
    private const ORIGIN_CITY = 'Jakarta';

    public function run(): void
    {
        $file = database_path('seeders/data/shipping_prices.csv');

        if (! is_file($file)) {
            $this->command->error('Price list file not found: ' . $file);
            return;
        }

        $rows = array_map('str_getcsv', file($file));
        array_shift($rows);

        $now = Carbon::now();
        $records = [];

        foreach ($rows as $row) {
            if (count($row) < 8) {
                continue;
            }

            [$province, $district, $city, $udaraPrice, $udaraDays, $landPrice, $landMin, $landDays] = $row;

            $city = trim($city);
            if ($city === '') {
                continue;
            }

            $records[] = [
                'origin_city' => self::ORIGIN_CITY,
                'destination_city' => $city,
                'service_type' => 'udara',
                'min_weight' => 1,
                'price_per_kg' => (float) $udaraPrice,
                'estimated_days' => (int) $udaraDays,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $records[] = [
                'origin_city' => self::ORIGIN_CITY,
                'destination_city' => $city,
                'service_type' => 'darat',
                'min_weight' => (int) $landMin,
                'price_per_kg' => (float) $landPrice,
                'estimated_days' => (int) $landDays,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ShippingRate::query()->delete();

        foreach (array_chunk($records, 500) as $chunk) {
            ShippingRate::insert($chunk);
        }

        $this->command->info('Shipping rates seeded: ' . count($records) . ' records.');
    }
}