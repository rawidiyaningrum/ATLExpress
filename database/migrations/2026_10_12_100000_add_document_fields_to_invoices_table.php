<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dokumen pendukung invoice yang dicetak di bawah nomor invoice dan diisi
     * operator saat membuat atau mengedit invoice. Semuanya boleh kosong,
     * karena tidak semua tagihan punya SPPB, packing list, atau periode.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('billed_to_address');
            $table->string('sppb_number')->nullable()->after('due_date');
            $table->string('packing_list_number')->nullable()->after('sppb_number');
            $table->string('period')->nullable()->after('packing_list_number');
            $table->string('account_number')->nullable()->after('period');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'due_date',
                'sppb_number',
                'packing_list_number',
                'period',
                'account_number',
            ]);
        });
    }
};
