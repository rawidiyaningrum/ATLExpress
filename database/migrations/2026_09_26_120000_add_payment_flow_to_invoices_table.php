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

    public function up(): void
    {
        $this->relaxStatusConstraint();
        $this->retagFinalInvoicesAsBilled();
        $this->enforceNewStatuses();

        // Jurnal pendapatan dan jurnal kas masuk dipisahkan tipenya supaya
        // dashboard finance tidak menghitung pendapatan dua kali.
        Schema::table('finance_journals', function (Blueprint $table): void {
            $table->string('journal_type')->default('revenue')->after('reference_label')->index();
        });
    }

    public function down(): void
    {
        Schema::table('finance_journals', function (Blueprint $table): void {
            $table->dropIndex(['journal_type']);
            $table->dropColumn('journal_type');
        });

        // Keduanya diturunkan ke final, jadi constraint yang sedang berlaku
        // harus dilepas lebih dulu.
        $this->relaxStatusConstraint();

        // Di scheme lama tidak ada jalan dari lunas ke draft, jadi turunkan satu
        // langkah dulu ke tertagih, baru collapse kedua status itu ke final.
        DB::table('invoices')->where('status', 'lunas')->update(['status' => 'tertagih']);
        DB::table('invoices')->where('status', 'tertagih')->update(['status' => 'final']);

        $this->enforceOldStatuses();
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
     */
    private function enforceNewStatuses(): void
    {
        $allowed = ['draft', 'tertagih', 'lunas'];

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

    private function enforceOldStatuses(): void
    {
        $allowed = ['draft', 'final'];

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

    /**
     * Invoice yang sudah final berarti sudah ditagihkan, jadi jadi tertagih.
     */
    private function retagFinalInvoicesAsBilled(): void
    {
        DB::table('invoices')->where('status', 'final')->update(['status' => 'tertagih']);
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
