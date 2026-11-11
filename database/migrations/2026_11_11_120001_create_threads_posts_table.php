<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads_posts', function (Blueprint $table) {
            $table->id();
            $table->string('threads_id')->unique();
            $table->string('username')->nullable()->index();
            $table->text('text')->nullable();
            $table->string('permalink', 500)->nullable();
            $table->string('media_type', 20)->nullable();
            $table->timestamp('posted_at')->nullable()->index();
            $table->string('matched_keyword')->nullable()->index();
            $table->string('search_mode', 20)->nullable();
            $table->string('source', 20)->default('api');
            $table->boolean('has_replies')->default(false);
            $table->boolean('is_reply')->default(false);
            $table->boolean('is_quote_post')->default(false);
            $table->json('raw')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads_posts');
    }
};
