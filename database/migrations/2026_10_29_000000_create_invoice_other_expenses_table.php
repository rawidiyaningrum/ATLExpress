<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengeluaran real yang tidak berasal dari baris invoice.
     *
     * Nominalnya tidak masuk ke total tagihan invoice, tetapi ikut dihitung
     * sebagai modal di jurnal pendapatan. Tabel terpisah dipakai supaya
     * invoice_items tetap hanya berisi item yang ditagihkan ke pelanggan.
     */
    public function up(): void
    {
        Schema::create('invoice_other_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_other_expenses');
    }
};
