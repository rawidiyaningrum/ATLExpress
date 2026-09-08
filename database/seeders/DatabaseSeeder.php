<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CategorySeeder::class,
            ServiceSeeder::class,
            PostSeeder::class,
            ShipmentSeeder::class,
            ShippingRateSeeder::class,
            InquirySeeder::class,
        ]);
    }
}