<?php

namespace Tests\Feature;

use App\Filament\Pages\ProjectBoard;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectBoardTest extends TestCase
{
    use RefreshDatabase;

    private function history(Project $project, string $status, string $title, array $attributes = []): ProjectHistory
    {
        return ProjectHistory::create(array_merge([
            'project_id' => $project->getKey(),
            'title' => $title,
            'status' => $status,
            'update_date' => now()->toDateString(),
        ], $attributes));
    }

    public function test_every_role_can_open_the_board(): void
    {
        $users = [
            User::factory()->create(),
            User::factory()->adminAtl()->create(),
            User::factory()->tracker()->create(),
        ];

        foreach ($users as $user) {
            Livewire::actingAs($user)
                ->test(ProjectBoard::class)
                ->assertOk();
        }
    }

    public function test_board_shows_history_columns_and_cards(): void
    {
        $project = Project::factory()->create(['name' => 'Project Alpha']);
        $this->history($project, ProjectHistory::STATUS_PROGRESS, 'Cek Vendor');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->assertSee('To Do')
            ->assertSee('Progress')
            ->assertSee('Kendala')
            ->assertSee('Close')
            ->assertSee('Cek Vendor');
    }

    public function test_board_can_be_filtered_by_project(): void
    {
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $this->history($projectA, ProjectHistory::STATUS_TODO, 'Riwayat Alfa');
        $this->history($projectB, ProjectHistory::STATUS_TODO, 'Riwayat Beta');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->set('projectFilter', (string) $projectA->getKey())
            ->assertSee('Riwayat Alfa')
            ->assertDontSee('Riwayat Beta');
    }

    public function test_moving_a_card_updates_the_history_status(): void
    {
        $project = Project::factory()->create();
        $history = $this->history($project, ProjectHistory::STATUS_TODO, 'Pindah kartu');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('moveHistoryTo', $history->getKey(), ProjectHistory::STATUS_CLOSE)
            ->assertOk();

        $this->assertSame(ProjectHistory::STATUS_CLOSE, $history->refresh()->status);
    }

    public function test_creating_a_history_from_the_modal(): void
    {
        $project = Project::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('openCreate')
            ->set('formProjectId', (string) $project->getKey())
            ->set('formTitle', 'Follow up vendor')
            ->set('formStatus', ProjectHistory::STATUS_PROGRESS)
            ->set('formUpdateDate', now()->toDateString())
            ->call('saveHistory')
            ->assertHasNoErrors()
            ->assertSet('modalMode', null);

        $this->assertDatabaseHas('project_histories', [
            'project_id' => $project->getKey(),
            'title' => 'Follow up vendor',
            'status' => ProjectHistory::STATUS_PROGRESS,
        ]);
    }

    public function test_creating_a_history_requires_a_project_and_title(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('openCreate')
            ->set('formTitle', '')
            ->call('saveHistory')
            ->assertHasErrors(['formProjectId', 'formTitle']);
    }

    public function test_editing_a_history_from_the_modal(): void
    {
        $project = Project::factory()->create();
        $history = $this->history($project, ProjectHistory::STATUS_TODO, 'Judul lama');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('openEdit', $history->getKey())
            ->set('formStatus', ProjectHistory::STATUS_CLOSE)
            ->set('formTitle', 'Judul baru')
            ->call('saveHistory')
            ->assertHasNoErrors();

        $history->refresh();

        $this->assertSame('Judul baru', $history->title);
        $this->assertSame(ProjectHistory::STATUS_CLOSE, $history->status);
    }

    public function test_board_shows_only_the_latest_history_per_project_when_no_project_filter(): void
    {
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $this->history($projectA, ProjectHistory::STATUS_TODO, 'Riwayat lama A');
        $this->history($projectA, ProjectHistory::STATUS_PROGRESS, 'Riwayat terbaru A');
        $this->history($projectB, ProjectHistory::STATUS_CLOSE, 'Riwayat terbaru B');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->assertSee('Riwayat terbaru A')
            ->assertSee('Riwayat terbaru B')
            ->assertDontSee('Riwayat lama A');
    }

    public function test_selecting_a_project_shows_all_of_its_histories(): void
    {
        $project = Project::factory()->create();
        $this->history($project, ProjectHistory::STATUS_TODO, 'Riwayat lama');
        $this->history($project, ProjectHistory::STATUS_PROGRESS, 'Riwayat terbaru');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->set('projectFilter', (string) $project->getKey())
            ->assertSee('Riwayat lama')
            ->assertSee('Riwayat terbaru');
    }

    public function test_search_filter_matches_history_title(): void
    {
        $project = Project::factory()->create();
        $this->history($project, ProjectHistory::STATUS_TODO, 'Follow up vendor');
        $this->history($project, ProjectHistory::STATUS_TODO, 'Rapat internal');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->set('projectFilter', (string) $project->getKey())
            ->set('search', 'vendor')
            ->assertSee('Follow up vendor')
            ->assertDontSee('Rapat internal');
    }
}
