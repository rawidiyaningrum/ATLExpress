<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal yang tercetak pada dokumen invoice dan kwitansi.
     *
     * Keduanya boleh kosong supaya invoice lama tetap memakai tanggal buat
     * untuk invoice dan tanggal cetak untuk kwitansi.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('invoice_date')->nullable()->after('due_date');
            $table->date('receipt_date')->nullable()->after('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_date',
                'receipt_date',
            ]);
        });
    }
};
