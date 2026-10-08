<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama constraint yang dibuat PostgreSQL untuk check constraint enum.
     */
    private const POSTGRES_TYPE_CHECK = 'invoice_items_type_check';

    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('diskon', 12, 2)->default(0)->after('discount');
        });

        $allowed = ['shipping', 'additional', 'discount', 'tax', 'diskon'];

        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS %s',
                self::POSTGRES_TYPE_CHECK,
            ));
        } else {
            Schema::table('invoice_items', function (Blueprint $table) use ($allowed): void {
                $table->enum('type', $allowed)->default('additional')->change();
            });
        }

        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoice_items ADD CONSTRAINT %s CHECK (type IN (%s))',
                self::POSTGRES_TYPE_CHECK,
                $this->quoteList($allowed),
            ));
        }
    }

    public function down(): void
    {
        $allowed = ['shipping', 'additional', 'discount', 'tax'];

        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoice_items DROP CONSTRAINT IF EXISTS %s',
                self::POSTGRES_TYPE_CHECK,
            ));
        } else {
            Schema::table('invoice_items', function (Blueprint $table) use ($allowed): void {
                $table->enum('type', $allowed)->default('additional')->change();
            });
        }

        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoice_items ADD CONSTRAINT %s CHECK (type IN (%s))',
                self::POSTGRES_TYPE_CHECK,
                $this->quoteList($allowed),
            ));
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('diskon');
        });
    }

    private function usesPostgresCheckConstraint(): bool
    {
        return DB::getDriverName() === 'pgsql';
    }

    /**
     * @param  array<int, string>  $values
     */
    private function quoteList(array $values): string
    {
        return implode(', ', array_map(
            fn (string $value): string => "'{$value}'",
            $values,
        ));
    }
};