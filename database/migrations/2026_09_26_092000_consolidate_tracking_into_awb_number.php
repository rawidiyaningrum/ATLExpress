<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor tracking dan nomor AWB disatukan menjadi satu nomor, yaitu
     * awb_number. Nilai tracking yang lama disalin dulu supaya shipment
     * existing tidak kehilangan identitasnya.
     */
    public function up(): void
    {
        DB::table('shipments')
            ->whereNull('awb_number')
            ->whereNotNull('tracking_number')
            ->update(['awb_number' => DB::raw('tracking_number')]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique(['tracking_number']);
            $table->dropIndex(['tracking_number']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->unique('tracking_number');
            $table->index('tracking_number');
        });

        DB::table('shipments')
            ->whereNull('tracking_number')
            ->update(['tracking_number' => DB::raw('awb_number')]);
    }
};
