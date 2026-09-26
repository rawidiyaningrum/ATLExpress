<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('awb_number')->nullable()->unique()->after('tracking_number');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->text('sender_address')->nullable()->after('sender_phone');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->text('receiver_address')->nullable()->after('receiver_phone');
            $table->decimal('price_per_kg', 12, 2)->nullable()->after('final_tariff');
        });

        DB::statement("ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('draft', 'pending', 'in_transit', 'delivered', 'cancelled'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE shipments DROP CONSTRAINT IF EXISTS shipments_status_check");
        DB::statement("ALTER TABLE shipments ADD CONSTRAINT shipments_status_check CHECK (status IN ('pending', 'in_transit', 'delivered'))");

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique(['awb_number']);
            $table->dropColumn([
                'awb_number',
                'sender_phone',
                'sender_address',
                'receiver_phone',
                'receiver_address',
                'price_per_kg',
            ]);
        });
    }
};