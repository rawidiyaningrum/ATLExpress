<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectStatusUpdate;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Board Project';

    protected static ?string $title = 'Board Project';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.project-board';

    /**
     * Semua role (admin, admin ATL, tracker) boleh melihat dan mengubah board.
     */
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    #[Url(as: 'pic', history: false)]
    public ?string $picFilter = null;

    #[Url(as: 'priority', history: false)]
    public ?string $priorityFilter = null;

    #[Url(as: 'q', history: false)]
    public string $search = '';

    #[Url(as: 'stale', history: false)]
    public bool $onlyStale = false;

    /**
     * Project yang sedang dibuka di modal detail.
     */
    public ?int $selectedProjectId = null;

    public ?string $updateStatus = null;

    public ?string $updateNote = null;

    public ?int $updateProgress = null;

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Project')
                ->icon('heroicon-o-plus')
                ->url(ProjectResource::getUrl('create')),
        ];
    }

    /**
     * Kolom board beserta kartu project di dalamnya, sudah difilter.
     *
     * @return array<string, array{key: string, label: string, hex: string, projects: Collection<int, Project>}>
     */
    #[Computed]
    public function columns(): array
    {
        $projects = $this->filteredProjects()->get();

        $updatedToday = ProjectStatusUpdate::query()
            ->whereDate('update_date', now()->toDateString())
            ->whereIn('project_id', $projects->pluck('id'))
            ->pluck('project_id')
            ->unique()
            ->all();

        $projects->each(fn (Project $project) => $project->setAttribute(
            'updated_today',
            in_array($project->getKey(), $updatedToday, true),
        ));

        return collect(Project::STATUSES)
            ->mapWithKeys(fn (string $status): array => [
                $status => [
                    'key' => $status,
                    'label' => Project::statusLabel($status),
                    'hex' => Project::STATUS_HEX_COLORS[$status] ?? '#9ca3af',
                    'projects' => $projects->where('status', $status)->values(),
                ],
            ])
            ->all();
    }

    #[Computed]
    public function selectedProject(): ?Project
    {
        if ($this->selectedProjectId === null) {
            return null;
        }

        return Project::query()
            ->with(['pic', 'shipment', 'statusUpdates.creator'])
            ->find($this->selectedProjectId);
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function picOptions(): Collection
    {
        return User::query()->orderBy('name')->pluck('name', 'id');
    }

    /**
     * @return array<string, string>
     */
    public function priorityOptions(): array
    {
        return Project::PRIORITY_LABELS;
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return Project::STATUS_LABELS;
    }

    public function openProject(int $projectId): void
    {
        $project = Project::query()->findOrFail($projectId);

        $this->selectedProjectId = $project->getKey();
        $this->updateStatus = $project->status;
        $this->updateProgress = $project->progress_percent;
        $this->updateNote = null;
        $this->resetValidation();

        unset($this->selectedProject);
    }

    public function closeProject(): void
    {
        $this->selectedProjectId = null;
        $this->updateStatus = null;
        $this->updateProgress = null;
        $this->updateNote = null;
        $this->resetValidation();
    }

    public function saveUpdate(): void
    {
        $project = $this->selectedProject;

        if (! $project instanceof Project) {
            return;
        }

        $validated = $this->validate([
            'updateStatus' => ['required', 'string', 'in:'.implode(',', Project::STATUSES)],
            'updateNote' => ['nullable', 'string', 'max:2000'],
            'updateProgress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        app(ProjectService::class)->recordUpdate(
            project: $project,
            status: $validated['updateStatus'],
            note: $validated['updateNote'] ?? null,
            progressPercent: $validated['updateProgress'] ?? null,
            userId: auth()->id(),
        );

        $this->updateNote = null;
        unset($this->selectedProject, $this->columns);

        Notification::make()
            ->title('Update status tersimpan')
            ->success()
            ->send();
    }

    /**
     * Drop kartu ke area kosong sebuah kolom: pindah ke kolom itu, urutan paling
     * akhir.
     */
    public function moveProjectTo(int $projectId, string $status): void
    {
        if (! in_array($status, Project::STATUSES, true)) {
            return;
        }

        $project = Project::query()->find($projectId);

        if (! $project instanceof Project) {
            return;
        }

        app(ProjectService::class)->move($project, $status, null, auth()->id());

        unset($this->columns);
    }

    /**
     * Drop kartu tepat di atas kartu lain: ikut kolom kartu tujuan dan disisipkan
     * sebelum kartu itu.
     */
    public function moveProjectBefore(int $projectId, int $beforeProjectId): void
    {
        if ($projectId === $beforeProjectId) {
            return;
        }

        $project = Project::query()->find($projectId);
        $before = Project::query()->find($beforeProjectId);

        if (! $project instanceof Project || ! $before instanceof Project) {
            return;
        }

        app(ProjectService::class)->move($project, $before->status, $before->getKey(), auth()->id());

        unset($this->columns);
    }

    public function resetFilters(): void
    {
        $this->picFilter = null;
        $this->priorityFilter = null;
        $this->search = '';
        $this->onlyStale = false;

        unset($this->columns);
    }

    /**
     * @return Builder<Project>
     */
    protected function filteredProjects(): Builder
    {
        return Project::query()
            ->with(['pic', 'latestUpdate'])
            ->notArchived()
            ->when($this->picFilter, fn (Builder $query, mixed $pic): Builder => $query->where('pic_id', (int) $pic))
            ->when($this->priorityFilter, fn (Builder $query, mixed $priority): Builder => $query->where('priority', $priority))
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->onlyStale, fn (Builder $query): Builder => $query->whereDoesntHave(
                'statusUpdates',
                fn (Builder $sub): Builder => $sub->whereDate('update_date', now()->toDateString()),
            ))
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
