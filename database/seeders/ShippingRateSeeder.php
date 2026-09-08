<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['origin_city' => 'Jakarta', 'destination_city' => 'Surabaya', 'service_type' => 'darat', 'min_weight' => 1, 'price_per_kg' => 5000, 'estimated_days' => 2],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Surabaya', 'service_type' => 'laut', 'min_weight' => 1, 'price_per_kg' => 3000, 'estimated_days' => 5],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Medan', 'service_type' => 'darat', 'min_weight' => 1, 'price_per_kg' => 7000, 'estimated_days' => 4],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Medan', 'service_type' => 'udara', 'min_weight' => 1, 'price_per_kg' => 15000, 'estimated_days' => 1],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Makassar', 'service_type' => 'laut', 'min_weight' => 1, 'price_per_kg' => 4000, 'estimated_days' => 6],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Makassar', 'service_type' => 'udara', 'min_weight' => 1, 'price_per_kg' => 18000, 'estimated_days' => 1],
            ['origin_city' => 'Surabaya', 'destination_city' => 'Bali', 'service_type' => 'darat', 'min_weight' => 1, 'price_per_kg' => 3500, 'estimated_days' => 1],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Papua', 'service_type' => 'udara', 'min_weight' => 1, 'price_per_kg' => 22000, 'estimated_days' => 2],
            ['origin_city' => 'Jakarta', 'destination_city' => 'Papua', 'service_type' => 'laut', 'min_weight' => 1, 'price_per_kg' => 8000, 'estimated_days' => 10],
        ];

        foreach ($rates as $rate) {
            ShippingRate::updateOrCreate(
                $rate,
                $rate
            );
        }
    }
}