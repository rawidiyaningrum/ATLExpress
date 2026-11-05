<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('phone', 50)->index();
            $table->text('location');
            $table->string('pic_name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_agent_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_agent_id')->constrained('delivery_agents')->cascadeOnDelete();
            $table->string('origin')->index();
            $table->string('destination')->index();
            $table->string('fleet_type', 100)->index(); // CDD, CDDL, Fuso, Tronton, dll
            $table->string('rate_type', 30)->default('carter')->index(); // carter, reguler
            $table->decimal('tariff', 14, 2);
            $table->decimal('min_weight', 8, 2)->nullable();
            $table->string('estimated_days', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['origin', 'destination', 'fleet_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_agent_routes');
        Schema::dropIfExists('delivery_agents');
    }
};

