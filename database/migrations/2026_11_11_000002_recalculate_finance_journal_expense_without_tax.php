<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perhitungan pajak di jurnal keuangan tadinya menambahkan PPN invoice ke
     * total biaya, padahal pendapatan jurnal sudah memakai total invoice yang
     * memuat PPN. Akibatnya pajak terhitung dua kali dan profit menjadi terlalu
     * kecil.
     *
     * Migrasi ini menghitung ulang total biaya, profit, dan profit persen untuk
     * jurnal lama tanpa menambahkan pajak, supaya dashboard dan daftar jurnal
     * langsung memakai nilai yang benar tanpa harus menyimpan ulang tiap baris.
     */
    public function up(): void
    {
        $this->recalculate(includeTax: false);
    }

    public function down(): void
    {
        $this->recalculate(includeTax: true);
    }

    /**
     * Nilai ditulis lewat query builder, bukan model, supaya migrasi tidak ikut
     * berubah kalau model jurnalnya nanti direname. Rumusnya sengaja sama
     * persis dengan FinanceJournal::totalExpense() dan FinanceJournal::profit().
     */
    private function recalculate(bool $includeTax): void
    {
        DB::table('finance_journals')
            ->orderBy('id')
            ->chunkById(200, function ($journals) use ($includeTax): void {
                foreach ($journals as $journal) {
                    $income = (float) $journal->income;
                    $totalExpense = round(
                        max(0, (float) $journal->cost_of_goods)
                        + max(0, (float) $journal->operational_cost)
                        + ($includeTax ? max(0, (float) $journal->tax) : 0),
                        2,
                    );
                    $profit = round(
                        $income - $totalExpense - max(0, (float) $journal->investment_profit_sharing),
                        2,
                    );

                    DB::table('finance_journals')
                        ->where('id', $journal->id)
                        ->update([
                            'total_expense' => $totalExpense,
                            'profit' => $profit,
                            'profit_percentage' => $income > 0 ? round(($profit / $income) * 100, 2) : 0,
                        ]);
                }
            });
    }
};
