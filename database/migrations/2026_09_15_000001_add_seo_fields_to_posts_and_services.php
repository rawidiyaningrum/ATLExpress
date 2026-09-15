<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('content');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('content');
            $table->string('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
