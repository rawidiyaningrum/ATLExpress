<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengeluaran real dicatat operator dari halaman jurnal keuangan, satu
     * nominal untuk setiap baris invoice. Nilai modalnya sudah ada di invoice,
     * jadi yang disimpan di sini hanya nominal riil yang dibayar.
     *
     * Baris ongkos kirim tidak punya baris di invoice_items karena disintesis
     * dari invoices.shipping_cost, jadi nominalnya butuh kolom sendiri.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->decimal('real_expense', 12, 2)->default(0)->after('line_total');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('shipping_real_expense', 12, 2)->default(0)->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropColumn('real_expense');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('shipping_real_expense');
        });
    }
};
