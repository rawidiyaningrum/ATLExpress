<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariff_search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('origin')->index();
            $table->string('kabupaten')->nullable()->index();
            $table->string('destination')->index();
            $table->decimal('weight', 8, 2)->default(1);
            $table->unsignedSmallInteger('results_count')->default(0)->index();
            $table->decimal('min_price', 14, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['origin', 'destination']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_search_logs');
    }
};

