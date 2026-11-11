<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\RelationManagers\StatusUpdatesRelationManager;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
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

    public function test_project_can_be_created_and_gets_an_initial_history_entry(): void
    {
        $pic = User::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateProject::class)
            ->fillForm([
                'name' => 'Project Baru',
                'pic_id' => $pic->getKey(),
                'status' => Project::STATUS_TODO,
                'priority' => Project::PRIORITY_HIGH,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'Project Baru')->sole();

        $this->assertSame(Project::STATUS_TODO, $project->status);
        $this->assertSame(1, $project->statusUpdates()->count(), 'riwayat awal dibuat otomatis');
    }

    public function test_changing_status_from_the_form_logs_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditProject::class, ['record' => $project->getRouteKey()])
            ->fillForm(['status' => Project::STATUS_DONE])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();

        $this->assertSame(Project::STATUS_DONE, $project->status);
        $this->assertSame(1, $project->statusUpdates()->count());
    }

    public function test_relation_manager_lists_the_status_history(): void
    {
        $project = Project::factory()->create(['status' => Project::STATUS_BACKLOG]);

        app(ProjectService::class)->recordUpdate(
            project: $project,
            status: Project::STATUS_IN_PROGRESS,
            note: 'Catatan riwayat',
            userId: User::factory()->create()->getKey(),
        );

        Livewire::actingAs(User::factory()->create())
            ->test(StatusUpdatesRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertOk()
            ->assertCanSeeTableRecords($project->statusUpdates()->get()->all())
            ->assertSee('Catatan riwayat');
    }
}
