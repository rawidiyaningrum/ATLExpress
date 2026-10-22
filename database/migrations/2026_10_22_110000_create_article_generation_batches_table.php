<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_generation_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('keyword');
            $table->string('category_slug')->default('berita');
            $table->string('method')->default('ai');
            $table->string('model')->nullable();
            $table->string('status')->default('queued');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('generated')->default(0);
            $table->unsignedInteger('fallback')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->decimal('estimated_cost_usd', 12, 6)->default(0);
            $table->decimal('actual_cost_usd', 12, 6)->default(0);
            $table->text('error')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_generation_batches');
    }
};
