<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_estimate_armadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_estimate_id')->constrained()->cascadeOnDelete();
            $table->string('service_type');
            $table->string('service_label')->nullable();
            $table->decimal('price_per_kg', 12, 2)->default(0);
            $table->decimal('min_weight', 8, 2)->default(1);
            $table->string('estimated_days')->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_estimate_armadas');
    }
};
