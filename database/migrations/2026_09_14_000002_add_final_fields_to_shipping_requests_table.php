<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->decimal('final_tariff', 12, 2)->nullable();
            $table->string('final_dimensions')->nullable();
            $table->decimal('final_weight', 8, 2)->nullable();
        });

        $this->replaceStatusConstraint(['new', 'contacted', 'checking', 'shipped', 'done']);
    }

    public function down(): void
    {
        $this->replaceStatusConstraint(['new', 'contacted', 'done']);

        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropColumn(['final_tariff', 'final_dimensions', 'final_weight']);
        });
    }

    private function replaceStatusConstraint(array $allowed): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE shipping_requests DROP CONSTRAINT IF EXISTS shipping_requests_status_check');
            DB::statement(sprintf(
                'ALTER TABLE shipping_requests ADD CONSTRAINT shipping_requests_status_check CHECK (status IN (%s))',
                implode(', ', array_map(fn (string $status) => "'{$status}'", $allowed))
            ));

            return;
        }

        // On sqlite the enum compiles to an inline "varchar check (...)" that cannot be
        // dropped, so the column is recompiled from the full value list instead.
        Schema::table('shipping_requests', function (Blueprint $table) use ($allowed) {
            $table->enum('status', $allowed)->default('new')->change();
        });
    }
};