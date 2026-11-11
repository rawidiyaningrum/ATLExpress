<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStatusUpdate extends Model
{
    use HasFactory;

    /**
     * Riwayat terbaru selalu menentukan status project, di mana pun barisnya
     * dibuat (board, form resource, atau relation manager). Dengan begitu tidak
     * ada jalur yang bisa meninggalkan status project tidak sinkron.
     *
     * Hook sengaja hanya jalan saat baris dibuat atau dihapus, bukan saat
     * diedit, supaya mengubah catatan lama tidak menimpa status terkini.
     */
    protected static function booted(): void
    {
        static::created(function (self $update): void {
            $update->syncProjectStatus();
        });

        static::deleted(function (self $update): void {
            $update->syncProjectStatusFromLatest();
        });
    }

    /**
     * Menyalin status (dan progress) baris ini ke project induknya.
     */
    protected function syncProjectStatus(): void
    {
        $project = $this->project;

        if (! $project instanceof Project) {
            return;
        }

        $project->status = $this->status;

        if ($this->progress_percent !== null) {
            $project->progress_percent = $this->progress_percent;
        }

        $project->saveQuietly();
    }

    /**
     * Setelah sebuah baris dihapus, status project dikembalikan ke riwayat
     * terbaru yang masih ada supaya tidak tertinggal di status yang sudah
     * dibatalkan.
     */
    protected function syncProjectStatusFromLatest(): void
    {
        $project = $this->project;

        if (! $project instanceof Project) {
            return;
        }

        $latest = $project->statusUpdates()->first();

        if ($latest instanceof self) {
            $project->status = $latest->status;
            $project->saveQuietly();
        }
    }

    protected $fillable = [
        'project_id',
        'status',
        'note',
        'progress_percent',
        'update_date',
        'created_by',
    ];

    protected $casts = [
        'update_date' => 'date',
        'progress_percent' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return Project::statusLabel($this->status);
    }
}
