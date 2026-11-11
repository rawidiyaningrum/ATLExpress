<?php

namespace Database\Seeders;

use App\Models\ThreadsKeyword;
use Illuminate\Database\Seeder;

class ThreadsKeywordSeeder extends Seeder
{
    /**
     * Isi keyword kurasi awal untuk pemantauan permintaan kargo di Threads.
     * Idempotent: aman dijalankan berulang tanpa menduplikasi data.
     */
    public function run(): void
    {
        $keywords = (array) config('threads.default_keywords', []);
        $tags = (array) config('threads.default_tags', []);

        foreach ($keywords as $keyword) {
            ThreadsKeyword::updateOrCreate(
                ['keyword' => $keyword, 'mode' => ThreadsKeyword::MODE_KEYWORD],
                ['is_active' => true, 'source' => 'seed'],
            );
        }

        foreach ($tags as $tag) {
            ThreadsKeyword::updateOrCreate(
                ['keyword' => $tag, 'mode' => ThreadsKeyword::MODE_TAG],
                ['is_active' => true, 'source' => 'seed'],
            );
        }
    }
}
