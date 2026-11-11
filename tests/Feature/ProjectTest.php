<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function history(Project $project, string $status, int $sortOrder = 0, string $title = 'Riwayat'): ProjectHistory
    {
        return ProjectHistory::create([
            'project_id' => $project->getKey(),
            'title' => $title,
            'status' => $status,
            'update_date' => now()->toDateString(),
            'sort_order' => $sortOrder,
        ]);
    }

    public function test_record_history_creates_a_card_with_the_given_status(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $history = app(ProjectService::class)->recordHistory(
            project: $project,
            title: 'Survei lokasi',
            status: ProjectHistory::STATUS_PROGRESS,
            note: 'Sudah di lokasi',
            progressPercent: 40,
            userId: $user->getKey(),
        );

        $this->assertSame(ProjectHistory::STATUS_PROGRESS, $history->status);
        $this->assertSame('Survei lokasi', $history->title);
        $this->assertSame(40, $history->progress_percent);
        $this->assertSame($user->getKey(), $history->created_by);
        $this->assertSame($project->getKey(), $history->project_id);
    }

    public function test_history_status_is_independent_from_the_project(): void
    {
        $project = Project::factory()->create();

        $todo = $this->history($project, ProjectHistory::STATUS_TODO);
        $close = $this->history($project, ProjectHistory::STATUS_CLOSE, 1);

        $this->assertSame(ProjectHistory::STATUS_TODO, $todo->status);
        $this->assertSame(ProjectHistory::STATUS_CLOSE, $close->status);
        $this->assertSame(2, $project->histories()->count());
    }

    public function test_move_history_changes_status(): void
    {
        $project = Project::factory()->create();
        $history = $this->history($project, ProjectHistory::STATUS_TODO);

        app(ProjectService::class)->moveHistory($history, ProjectHistory::STATUS_CLOSE);

        $this->assertSame(ProjectHistory::STATUS_CLOSE, $history->refresh()->status);
    }

    public function test_move_history_before_orders_cards_inside_the_column(): void
    {
        $project = Project::factory()->create();

        $first = $this->history($project, ProjectHistory::STATUS_PROGRESS, 0, 'Pertama');
        $second = $this->history($project, ProjectHistory::STATUS_PROGRESS, 1, 'Kedua');
        $moved = $this->history($project, ProjectHistory::STATUS_TODO, 0, 'Dipindah');

        app(ProjectService::class)->moveHistory($moved, ProjectHistory::STATUS_PROGRESS, $second->getKey());

        $this->assertSame(
            [$first->getKey(), $moved->getKey(), $second->getKey()],
            ProjectHistory::query()
                ->where('status', ProjectHistory::STATUS_PROGRESS)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all(),
        );
    }

    public function test_update_history_changes_fields_and_moves_to_end_when_status_changes(): void
    {
        $project = Project::factory()->create();

        $existing = $this->history($project, ProjectHistory::STATUS_CLOSE, 0, 'Sudah ada');
        $history = $this->history($project, ProjectHistory::STATUS_TODO, 0, 'Judul lama');

        app(ProjectService::class)->updateHistory($history, [
            'title' => 'Judul baru',
            'status' => ProjectHistory::STATUS_CLOSE,
            'note' => 'Catatan baru',
            'progress_percent' => 75,
        ]);

        $history->refresh();

        $this->assertSame('Judul baru', $history->title);
        $this->assertSame(ProjectHistory::STATUS_CLOSE, $history->status);
        $this->assertSame(75, $history->progress_percent);

        $order = ProjectHistory::query()
            ->where('status', ProjectHistory::STATUS_CLOSE)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $this->assertSame([$existing->getKey(), $history->getKey()], $order, 'kartu pindah ke akhir kolom baru');
    }

    public function test_delete_history_removes_it_from_the_project(): void
    {
        $project = Project::factory()->create();
        $history = $this->history($project, ProjectHistory::STATUS_TODO);

        app(ProjectService::class)->deleteHistory($history);

        $this->assertSame(0, $project->histories()->count());
    }
}
