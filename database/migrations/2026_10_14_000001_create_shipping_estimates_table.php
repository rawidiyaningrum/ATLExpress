<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_number')->unique();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('sender_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('receiver_address')->nullable();
            $table->string('origin')->nullable();
            $table->string('kabupaten_tujuan')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('item_type')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('include_ppn')->default(true);
            $table->boolean('include_pph')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('origin');
            $table->index('destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_estimates');
    }
};
