<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->decimal('final_tariff', 12, 2)->nullable();
            $table->string('final_dimensions')->nullable();
            $table->decimal('final_weight', 8, 2)->nullable();
        });

        DB::statement('ALTER TABLE shipping_requests DROP CONSTRAINT IF EXISTS shipping_requests_status_check');
        DB::statement("ALTER TABLE shipping_requests ADD CONSTRAINT shipping_requests_status_check CHECK (status IN ('new', 'contacted', 'checking', 'shipped', 'done'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shipping_requests DROP CONSTRAINT IF EXISTS shipping_requests_status_check');
        DB::statement("ALTER TABLE shipping_requests ADD CONSTRAINT shipping_requests_status_check CHECK (status IN ('new', 'contacted', 'done'))");

        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropColumn(['final_tariff', 'final_dimensions', 'final_weight']);
        });
    }
};