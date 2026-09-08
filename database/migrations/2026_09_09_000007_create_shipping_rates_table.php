<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('origin_city');
            $table->string('destination_city');
            $table->enum('service_type', ['darat', 'laut', 'udara']);
            $table->decimal('min_weight', 8, 2)->default(1);
            $table->decimal('price_per_kg', 12, 2);
            $table->integer('estimated_days');
            $table->timestamps();
            $table->index('origin_city');
            $table->index('destination_city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
