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
            ArticleLayananSeeder::class,
            ArticleRuteSeeder::class,
            ArticleTipsSeeder::class,
            ArticleUsahaSeeder::class,
            ShipmentSeeder::class,
            PricelistSeeder::class,
            InquirySeeder::class,
        ]);
    }
}
