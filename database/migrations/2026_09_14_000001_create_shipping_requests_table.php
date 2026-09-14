<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->text('pickup_address');
            $table->string('item_type');
            $table->decimal('weight', 8, 2);
            $table->string('dimensions')->nullable();
            $table->text('notes')->nullable();
            $table->string('origin');
            $table->string('destination');
            $table->string('service_type')->nullable();
            $table->enum('status', ['new', 'contacted', 'done'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_requests');
    }
};