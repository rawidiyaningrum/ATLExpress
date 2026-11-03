<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'instagram'],
            [
                'value' => 'https://instagram.com/douzekargo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'instagram')
            ->update([
                'value' => 'https://instagram.com/atlexpress',
                'updated_at' => now(),
            ]);
    }
};

