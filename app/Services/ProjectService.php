<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectStatusUpdate;

class ProjectService
{
    /**
     * Mencatat update status project. Riwayat boleh ditambah berkali-kali,
     * tidak dibatasi satu per hari.
     *
     * Status project tidak diset di sini: hook di ProjectStatusUpdate yang
     * menyelaraskannya, supaya jalur lain (mis. relation manager) ikut
     * konsisten.
     */
    public function recordUpdate(
        Project $project,
        string $status,
        ?string $note = null,
        ?int $progressPercent = null,
        ?\DateTimeInterface $date = null,
        ?int $userId = null,
    ): ProjectStatusUpdate {
        return $project->statusUpdates()->create([
            'status' => $status,
            'note' => $this->normaliseNote($note),
            'progress_percent' => $this->normaliseProgress($progressPercent),
            'update_date' => ($date ?? now())->format('Y-m-d'),
            'created_by' => $userId,
        ]);
    }

    /**
     * Memindahkan project ke kolom lain (drag & drop board) dan mengurutkannya
     * di dalam kolom tujuan.
     *
     * Kalau kolomnya berubah, perubahan itu ikut dicatat ke riwayat supaya
     * timeline tetap lengkap. Menukar urutan di kolom yang sama tidak dicatat.
     */
    public function move(
        Project $project,
        string $status,
        ?int $beforeProjectId = null,
        ?int $userId = null,
    ): Project {
        if ($project->status !== $status) {
            $this->recordUpdate(
                project: $project,
                status: $status,
                note: 'Dipindah ke '.Project::statusLabel($status).'.',
                userId: $userId,
            );
        }

        $this->reorderColumn($status, $project, $beforeProjectId);

        return $project->refresh();
    }

    /**
     * Menyusun ulang `sort_order` kartu pada sebuah kolom, dengan project yang
     * baru dipindah diletakkan sebelum kartu penanda (atau di paling akhir).
     */
    protected function reorderColumn(string $status, Project $project, ?int $beforeProjectId): void
    {
        $ids = Project::query()
            ->where('status', $status)
            ->whereKeyNot($project->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($beforeProjectId !== null) {
            $index = array_search($beforeProjectId, $ids, true);

            if ($index === false) {
                $ids[] = $project->getKey();
            } else {
                array_splice($ids, $index, 0, [$project->getKey()]);
            }
        } else {
            $ids[] = $project->getKey();
        }

        foreach ($ids as $position => $id) {
            Project::query()->whereKey($id)->update(['sort_order' => $position]);
        }
    }

    protected function normaliseNote(?string $note): ?string
    {
        $note = $note !== null ? trim($note) : null;

        return $note === '' ? null : $note;
    }

    protected function normaliseProgress(?int $progressPercent): ?int
    {
        if ($progressPercent === null) {
            return null;
        }

        return max(0, min(100, $progressPercent));
    }
}
