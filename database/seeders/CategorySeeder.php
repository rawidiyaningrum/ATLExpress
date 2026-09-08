<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'News', 'slug' => 'berita'],
            ['name' => 'Tips & Tutorial', 'slug' => 'tips-tutorial'],
            ['name' => 'Company News', 'slug' => 'kabar-perusahaan'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );
        }
    }
}