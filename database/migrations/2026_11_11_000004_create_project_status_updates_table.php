<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('status');
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('progress_percent')->nullable();
            $table->date('update_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'update_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_status_updates');
    }
};
