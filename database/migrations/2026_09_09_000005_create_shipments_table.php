<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->string('sender_name');
            $table->string('receiver_name');
            $table->string('origin');
            $table->string('destination');
            $table->decimal('weight', 8, 2);
            $table->enum('status', ['pending', 'in_transit', 'delivered'])->default('pending');
            $table->timestamps();
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
