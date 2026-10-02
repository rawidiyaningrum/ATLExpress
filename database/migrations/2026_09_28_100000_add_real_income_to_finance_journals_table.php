<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nominal kas yang benar-benar masuk rekening dicatat operator dari halaman
     * jurnal keuangan, terpisah dari nominal tagihan yang sudah ada di kolom
     * income. Dua angka itu dibedakan karena uang yang masuk rarely sama dengan
     * tagihan: ada potongan biaya transfer, sisa pembayaran, atau pembayaran
     * lebih.
     *
     * Kolomnya nullable supaya jurnal yang belum pernah dicatat bedanya dari
     * kas riil yang memang bernilai nol.
     */
    public function up(): void
    {
        Schema::table('finance_journals', function (Blueprint $table): void {
            $table->decimal('real_income', 12, 2)->nullable()->after('income');
        });

        // Jurnal kas yang sudah ada sebelumnya diasumsikan lunas utuh, karena
        // sampai sekarang hanya income yang tersedia dan nilainya disalin dari
        // total invoice. Memakai nilai itu membuat checkbox "sama dengan
        // nominal invoice" langsung tercentang dan angka kas masuk di dashboard
        // tidak berubah setelah migrasi.
        //
        // Nilainya ditulis literal, bukan dari konstanta model, supaya migrasi
        // tidak ikut berubah kalau model-nya nanti direname.
        DB::table('finance_journals')
            ->where('journal_type', 'receipt')
            ->whereNull('real_income')
            ->update(['real_income' => DB::raw('income')]);
    }

    public function down(): void
    {
        Schema::table('finance_journals', function (Blueprint $table): void {
            $table->dropColumn('real_income');
        });
    }
};
