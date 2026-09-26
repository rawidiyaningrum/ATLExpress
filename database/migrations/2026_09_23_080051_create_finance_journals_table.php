<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_label')->nullable();
            $table->date('entry_date')->nullable();
            $table->decimal('income', 12, 2)->default(0);
            $table->decimal('cost_of_goods', 12, 2)->default(0);
            $table->decimal('operational_cost', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total_expense', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->decimal('profit_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_journals');
    }
};