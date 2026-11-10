<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_journals', function (Blueprint $table) {
            $table->decimal('investment_profit_sharing', 12, 2)->default(0)->after('tax');
        });
    }

    public function down(): void
    {
        Schema::table('finance_journals', function (Blueprint $table) {
            $table->dropColumn('investment_profit_sharing');
        });
    }
};
