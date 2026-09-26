<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Berat dan tarif baru dikumpulkan pada langkah 2 wizard, jadi draft yang
     * dibuat pada langkah 1 belum memilikinya.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->nullable(false)->change();
        });
    }
};
