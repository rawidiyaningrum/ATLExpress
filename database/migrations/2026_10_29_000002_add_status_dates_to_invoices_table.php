<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal saat invoice mulai berjalan dan saat invoice dinyatakan lunas.
     *
     * Keduanya terpisah dari tanggal dokumen invoice/kwitansi karena menandai
     * perpindahan status, bukan tanggal cetak.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('berjalan_date')->nullable()->after('receipt_date');
            $table->date('lunas_date')->nullable()->after('berjalan_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'berjalan_date',
                'lunas_date',
            ]);
        });
    }
};
