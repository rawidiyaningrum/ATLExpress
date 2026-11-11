<?php

namespace Tests\Feature;

use App\Filament\Pages\ProjectBoard;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectBoardTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_board_renders_columns_and_cards(): void
    {
        Project::factory()->create([
            'name' => 'Proyek Alpha',
            'status' => Project::STATUS_IN_PROGRESS,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->assertSee('Backlog')
            ->assertSee('On Progress')
            ->assertSee('Selesai')
            ->assertSee('Proyek Alpha');
    }

    public function test_moving_a_card_updates_status_and_records_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('moveProjectTo', $project->getKey(), Project::STATUS_TODO)
            ->assertOk();

        $project->refresh();

        $this->assertSame(Project::STATUS_TODO, $project->status);
        $this->assertSame(1, $project->statusUpdates()->count());
    }

    public function test_saving_an_update_from_the_modal_records_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('openProject', $project->getKey())
            ->set('updateStatus', Project::STATUS_IN_PROGRESS)
            ->set('updateNote', 'Sudah mulai dikerjakan')
            ->set('updateProgress', 30)
            ->call('saveUpdate')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertSame(Project::STATUS_IN_PROGRESS, $project->status);
        $this->assertSame(30, $project->progress_percent);
        $this->assertSame('Sudah mulai dikerjakan', $project->statusUpdates()->first()->note);
    }

    public function test_saving_an_update_requires_a_valid_status(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->call('openProject', $project->getKey())
            ->set('updateStatus', 'bukan-status')
            ->call('saveUpdate')
            ->assertHasErrors('updateStatus');
    }

    public function test_only_stale_filter_hides_projects_updated_today(): void
    {
        $fresh = Project::factory()->create(['name' => 'Fresh Project']);
        Project::factory()->create(['name' => 'Stale Project']);

        app(ProjectService::class)->recordUpdate($fresh, Project::STATUS_IN_PROGRESS, 'Update hari ini');

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->set('onlyStale', true)
            ->assertSee('Stale Project')
            ->assertDontSee('Fresh Project');
    }

    public function test_search_filter_matches_project_name(): void
    {
        Project::factory()->create(['name' => 'Ekspedisi Mesin Pabrik']);
        Project::factory()->create(['name' => 'Renovasi Gudang']);

        Livewire::actingAs(User::factory()->create())
            ->test(ProjectBoard::class)
            ->set('search', 'Mesin')
            ->assertSee('Ekspedisi Mesin Pabrik')
            ->assertDontSee('Renovasi Gudang');
    }
}
