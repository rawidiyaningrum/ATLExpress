<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->decimal('initial_tariff', 12, 2)->nullable()->after('service_type');
            $table->string('awb_number')->nullable()->unique()->after('final_tariff');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropColumn(['initial_tariff', 'awb_number']);
        });
    }
};