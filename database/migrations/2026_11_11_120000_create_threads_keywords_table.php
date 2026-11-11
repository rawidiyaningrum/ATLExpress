<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->string('mode', 20)->default('keyword')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('source', 20)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_hits')->default(0);
            $table->unsignedInteger('total_hits')->default(0);
            $table->timestamps();

            $table->unique(['keyword', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads_keywords');
    }
};
