<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status dipindah dari project ke riwayat: dulu satu project punya satu
     * status, sekarang tiap baris riwayat punya status sendiri (to-do, progress,
     * kendala, close) dan menjadi kartu di board.
     *
     * Tabel project_status_updates di-rename jadi project_histories, lalu
     * ditambah judul dan urutan kartu. Kolom status/urutan project dibuang
     * karena project hanya jadi wadah.
     */
    public function up(): void
    {
        Schema::rename('project_status_updates', 'project_histories');

        Schema::table('project_histories', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('project_id');
        });

        Schema::table('project_histories', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('progress_percent');
            $table->index(['status', 'sort_order']);
        });

        // Status lama project dipetakan ke status riwayat yang baru.
        $statusMap = [
            'backlog' => 'todo',
            'todo' => 'todo',
            'in_progress' => 'progress',
            'blocked' => 'kendala',
            'done' => 'close',
        ];

        foreach ($statusMap as $old => $new) {
            DB::table('project_histories')->where('status', $old)->update(['status' => $new]);
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['status']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('status')->default('todo');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::table('project_histories', function (Blueprint $table): void {
            $table->dropIndex(['status', 'sort_order']);
        });

        Schema::table('project_histories', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });

        Schema::table('project_histories', function (Blueprint $table): void {
            $table->dropColumn('title');
        });

        Schema::rename('project_histories', 'project_status_updates');
    }
};
