<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->string('project_name')->nullable();
            $table->decimal('amount', 15, 2)->default(0)->comment('Nominal modal investasi');
            $table->string('status')->default('ongoing')->comment('transferred, ongoing, reimbursed');
            $table->date('investment_date')->nullable();
            $table->decimal('profit_sharing_percentage', 5, 2)->default(0)->comment('Persentase bagi hasil %');
            $table->decimal('profit_sharing_amount', 15, 2)->default(0)->comment('Nominal bagi hasil');
            $table->date('profit_sharing_date')->nullable()->comment('Tanggal jatuh tempo bagi hasil');
            $table->string('capital_payout_status')->default('pending')->comment('pending, paid');
            $table->date('capital_payout_date')->nullable()->comment('Tanggal pencairan modal');
            $table->string('profit_payout_status')->default('pending')->comment('pending, paid');
            $table->date('profit_payout_date')->nullable()->comment('Tanggal pencairan bagi hasil');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('investment_date');
            $table->index('profit_payout_status');
            $table->index('capital_payout_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
