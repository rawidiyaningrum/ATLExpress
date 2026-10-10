<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->string('kabupaten_tujuan')->nullable()->after('origin_city');
            $table->index('kabupaten_tujuan');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table): void {
            $table->dropIndex(['kabupaten_tujuan']);
            $table->dropColumn('kabupaten_tujuan');
        });
    }
};
