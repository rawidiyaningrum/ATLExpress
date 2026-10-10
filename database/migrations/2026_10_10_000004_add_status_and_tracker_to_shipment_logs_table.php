<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_logs', function (Blueprint $table): void {
            $table->string('status')->nullable()->after('shipment_id');
            $table->foreignId('tracker_user_id')->nullable()->after('location')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipment_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tracker_user_id');
            $table->dropColumn('status');
        });
    }
};
