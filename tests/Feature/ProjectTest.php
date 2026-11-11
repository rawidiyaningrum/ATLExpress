<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_update_syncs_project_status_and_records_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);
        $user = User::factory()->create();

        app(ProjectService::class)->recordUpdate(
            project: $project,
            status: Project::STATUS_IN_PROGRESS,
            note: 'Mulai dikerjakan',
            progressPercent: 40,
            userId: $user->getKey(),
        );

        $project->refresh();

        $this->assertSame(Project::STATUS_IN_PROGRESS, $project->status, 'status project mengikuti update terbaru');
        $this->assertSame(40, $project->progress_percent);
        $this->assertSame(1, $project->statusUpdates()->count());

        $update = $project->statusUpdates()->first();
        $this->assertSame('Mulai dikerjakan', $update->note);
        $this->assertSame($user->getKey(), $update->created_by);
    }

    public function test_moving_across_columns_changes_status_and_logs_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);
        $user = User::factory()->create();

        app(ProjectService::class)->move($project, Project::STATUS_TODO, null, $user->getKey());

        $project->refresh();

        $this->assertSame(Project::STATUS_TODO, $project->status);
        $this->assertSame(1, $project->statusUpdates()->count());
        $this->assertStringContainsString('Dipindah ke', (string) $project->statusUpdates()->first()->note);
    }

    public function test_moving_within_the_same_column_does_not_log_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_TODO]);

        app(ProjectService::class)->move($project, Project::STATUS_TODO);

        $this->assertSame(0, $project->statusUpdates()->count());
    }

    public function test_move_before_orders_cards_inside_the_column(): void
    {
        $first = Project::factory()->create(['status' => Project::STATUS_TODO, 'sort_order' => 0]);
        $second = Project::factory()->create(['status' => Project::STATUS_TODO, 'sort_order' => 1]);
        $moved = Project::factory()->create(['status' => Project::STATUS_BACKLOG, 'sort_order' => 0]);

        app(ProjectService::class)->move($moved, Project::STATUS_TODO, $second->getKey());

        $this->assertSame(
            [$first->getKey(), $moved->getKey(), $second->getKey()],
            Project::query()->where('status', Project::STATUS_TODO)->orderBy('sort_order')->pluck('id')->all(),
        );
    }

    public function test_deleting_the_latest_update_restores_the_previous_status(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);
        $service = app(ProjectService::class);

        $service->recordUpdate($project, Project::STATUS_TODO, 'Awal');
        $service->recordUpdate($project, Project::STATUS_IN_PROGRESS, 'Jalan');

        $latest = $project->refresh()->statusUpdates()->first();
        $latest->delete();

        $this->assertSame(Project::STATUS_TODO, $project->refresh()->status);
    }

    public function test_has_update_on_checks_the_given_date(): void
    {
        $project = Project::factory()->create();

        $this->assertFalse($project->hasUpdateOn(now()));

        app(ProjectService::class)->recordUpdate($project, Project::STATUS_TODO, 'Cek hari ini');

        $this->assertTrue($project->refresh()->hasUpdateOn(now()));
        $this->assertFalse($project->hasUpdateOn(now()->subDay()));
    }
}
