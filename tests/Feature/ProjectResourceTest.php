<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\RelationManagers\HistoriesRelationManager;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_index_is_reachable_for_every_role(): void
    {
        $users = [
            User::factory()->create(),
            User::factory()->adminAtl()->create(),
            User::factory()->tracker()->create(),
        ];

        foreach ($users as $user) {
            $this->actingAs($user)
                ->get(ProjectResource::getUrl('index'))
                ->assertSuccessful();
        }
    }

    public function test_project_can_be_created(): void
    {
        $pic = User::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateProject::class)
            ->fillForm([
                'name' => 'Project Baru',
                'pic_id' => $pic->getKey(),
                'priority' => Project::PRIORITY_HIGH,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'Project Baru',
            'priority' => Project::PRIORITY_HIGH,
            'pic_id' => $pic->getKey(),
        ]);
    }

    public function test_relation_manager_lists_the_project_histories(): void
    {
        $project = Project::factory()->create();

        ProjectHistory::create([
            'project_id' => $project->getKey(),
            'title' => 'Riwayat vendor',
            'status' => ProjectHistory::STATUS_PROGRESS,
            'update_date' => now()->toDateString(),
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(HistoriesRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertOk()
            ->assertCanSeeTableRecords($project->histories()->get()->all())
            ->assertSee('Riwayat vendor');
    }
}
