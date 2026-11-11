<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectHistory;

class ProjectService
{
    /**
     * Menambah satu baris riwayat pada sebuah project, diletakkan di paling
     * akhir kolom sesuai statusnya.
     */
    public function recordHistory(
        Project $project,
        string $title,
        string $status,
        ?string $note = null,
        ?int $progressPercent = null,
        ?\DateTimeInterface $date = null,
        ?int $userId = null,
    ): ProjectHistory {
        return $project->histories()->create([
            'title' => trim($title),
            'status' => $status,
            'note' => $this->normaliseNote($note),
            'progress_percent' => $this->normaliseProgress($progressPercent),
            'update_date' => ($date ?? now())->format('Y-m-d'),
            'sort_order' => $this->nextSortOrder($status),
            'created_by' => $userId,
        ]);
    }

    /**
     * Memperbarui isi riwayat. Kalau statusnya berubah, kartunya dipindah ke
     * paling akhir kolom yang baru.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateHistory(ProjectHistory $history, array $attributes): ProjectHistory
    {
        $statusChanged = array_key_exists('status', $attributes)
            && $attributes['status'] !== $history->status;

        if (array_key_exists('title', $attributes)) {
            $attributes['title'] = trim((string) $attributes['title']);
        }

        if (array_key_exists('note', $attributes)) {
            $attributes['note'] = $this->normaliseNote(
                $attributes['note'] !== null ? (string) $attributes['note'] : null,
            );
        }

        if (array_key_exists('progress_percent', $attributes)) {
            $attributes['progress_percent'] = $this->normaliseProgress(
                $attributes['progress_percent'] !== null ? (int) $attributes['progress_percent'] : null,
            );
        }

        if (($attributes['update_date'] ?? null) instanceof \DateTimeInterface) {
            $attributes['update_date'] = $attributes['update_date']->format('Y-m-d');
        }

        $history->fill($attributes)->save();

        if ($statusChanged) {
            $this->reorderColumn($history->status, $history->refresh(), null);
        }

        return $history->refresh();
    }

    /**
     * Memindahkan kartu riwayat antar kolom board (drag & drop) dan
     * mengurutkannya di dalam kolom tujuan.
     */
    public function moveHistory(ProjectHistory $history, string $status, ?int $beforeHistoryId = null): ProjectHistory
    {
        $history->forceFill(['status' => $status])->save();

        $this->reorderColumn($status, $history, $beforeHistoryId);

        return $history->refresh();
    }

    public function deleteHistory(ProjectHistory $history): void
    {
        $history->delete();
    }

    protected function nextSortOrder(string $status): int
    {
        return ((int) ProjectHistory::query()->where('status', $status)->max('sort_order')) + 1;
    }

    /**
     * Menyusun ulang `sort_order` kartu pada sebuah kolom, dengan kartu yang
     * dipindah diletakkan sebelum kartu penanda (atau di paling akhir).
     */
    protected function reorderColumn(string $status, ProjectHistory $history, ?int $beforeHistoryId): void
    {
        $ids = ProjectHistory::query()
            ->where('status', $status)
            ->whereKeyNot($history->getKey())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($beforeHistoryId !== null) {
            $index = array_search($beforeHistoryId, $ids, true);

            if ($index === false) {
                $ids[] = $history->getKey();
            } else {
                array_splice($ids, $index, 0, [$history->getKey()]);
            }
        } else {
            $ids[] = $history->getKey();
        }

        foreach ($ids as $position => $id) {
            ProjectHistory::query()->whereKey($id)->update(['sort_order' => $position]);
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
