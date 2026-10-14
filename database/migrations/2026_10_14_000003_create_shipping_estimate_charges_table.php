<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_estimate_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_estimate_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('type')->default('additional');
            $table->string('basis')->default('final_tariff');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_estimate_charges');
    }
};
