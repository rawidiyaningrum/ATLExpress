<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keterangan jenis atau isi barang untuk dicetak pada airway bill.
     * Diisi saat pengisian shipment atau disalin dari pemesanan.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('item_type', 255)->nullable()->after('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });
    }
};
