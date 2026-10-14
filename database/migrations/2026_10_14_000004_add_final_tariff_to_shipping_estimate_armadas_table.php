<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_estimate_armadas', function (Blueprint $table) {
            // Tarif final dipakai layanan yang harganya borongan (mis. carter),
            // bukan hasil berat x tarif per kg.
            $table->decimal('final_tariff', 12, 2)->nullable()->after('price_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_estimate_armadas', function (Blueprint $table) {
            $table->dropColumn('final_tariff');
        });
    }
};
