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
    private const POSTGRES_STATUS_CHECK = 'invoices_status_check';

    /**
     * Status tertagih diganti menjadi final, lalu status berjalan ditambahkan
     * di antara final dan lunas.
     */
    public function up(): void
    {
        $this->relaxStatusConstraint();

        DB::table('invoices')->where('status', 'tertagih')->update(['status' => 'final']);

        $this->enforceStatuses(['draft', 'final', 'berjalan', 'lunas']);
    }

    public function down(): void
    {
        $this->relaxStatusConstraint();

        DB::table('invoices')->where('status', 'final')->update(['status' => 'tertagih']);
        DB::table('invoices')->where('status', 'berjalan')->update(['status' => 'tertagih']);

        $this->enforceStatuses(['draft', 'tertagih', 'lunas']);
    }

    /**
     * Melepas check constraint enum supaya nilai baru bisa masuk.
     *
     * Kolom invoices.status sudah bertipe varchar, jadi hanya constraint-nya
     * yang perlu dilepas. PostgreSQL bisa drop constraint langsung, sedangkan
     * SQLite harus membangun ulang tabel lewat change().
     */
    private function relaxStatusConstraint(): void
    {
        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoices DROP CONSTRAINT IF EXISTS %s',
                self::POSTGRES_STATUS_CHECK,
            ));

            return;
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Menulis ulang check constraint dengan daftar status yang berlaku.
     *
     * @param  array<int, string>  $allowed
     */
    private function enforceStatuses(array $allowed): void
    {
        if ($this->usesPostgresCheckConstraint()) {
            DB::statement(sprintf(
                'ALTER TABLE invoices ADD CONSTRAINT %s CHECK (status IN (%s))',
                self::POSTGRES_STATUS_CHECK,
                $this->quoteList($allowed),
            ));

            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($allowed): void {
            $table->enum('status', $allowed)->default('draft')->change();
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
