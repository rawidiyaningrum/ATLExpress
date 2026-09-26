<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wizard memisahkan panjang, lebar dan tinggi, dan memilih jenis layanan
     * yang menentukan tarif per kg.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('service_type', 20)->nullable()->after('destination');
            $table->decimal('dimension_length', 8, 2)->nullable()->after('weight');
            $table->decimal('dimension_width', 8, 2)->nullable()->after('dimension_length');
            $table->decimal('dimension_height', 8, 2)->nullable()->after('dimension_width');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('final_dimensions');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('final_dimensions', 100)->nullable()->after('weight');
        });

        DB::table('shipments')->update([
            'final_dimensions' => DB::raw(
                'CASE WHEN dimension_length IS NULL AND dimension_width IS NULL AND dimension_height IS NULL'
                .' THEN NULL'
                ." ELSE CAST(dimension_length AS TEXT) || 'x' || CAST(dimension_width AS TEXT) || 'x' || CAST(dimension_height AS TEXT)"
                .' END'
            ),
        ]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'service_type',
                'dimension_length',
                'dimension_width',
                'dimension_height',
            ]);
        });
    }
};
