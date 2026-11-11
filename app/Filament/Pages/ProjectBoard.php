<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Board Riwayat';

    protected static ?string $title = 'Board Riwayat Project';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.project-board';

    /**
     * Semua role (admin, admin ATL, tracker) boleh melihat dan mengubah board.
     */
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    #[Url(as: 'project', history: false)]
    public ?string $projectFilter = null;

    #[Url(as: 'pic', history: false)]
    public ?string $picFilter = null;

    #[Url(as: 'q', history: false)]
    public string $search = '';

    /**
     * Modal tambah/ubah riwayat. Null berarti modal tertutup.
     */
    public ?string $modalMode = null;

    public ?int $editingHistoryId = null;

    public ?string $formProjectId = null;

    public string $formTitle = '';

    public ?string $formStatus = null;

    public ?string $formNote = null;

    public ?int $formProgress = null;

    public ?string $formUpdateDate = null;

    public function getHeaderActions(): array
    {
        return [
            Action::make('manageProjects')
                ->label('Kelola Project')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(ProjectResource::getUrl('index')),
        ];
    }

    /**
     * Kolom board berisi riwayat, bukan project. Status tinggal di tiap riwayat.
     *
     * @return array<string, array{key: string, label: string, hex: string, histories: Collection<int, ProjectHistory>}>
     */
    #[Computed]
    public function columns(): array
    {
        $histories = $this->filteredHistories()->get();

        return collect(ProjectHistory::STATUSES)
            ->mapWithKeys(fn (string $status): array => [
                $status => [
                    'key' => $status,
                    'label' => ProjectHistory::statusLabel($status),
                    'hex' => ProjectHistory::STATUS_HEX_COLORS[$status] ?? '#9ca3af',
                    'histories' => $histories->where('status', $status)->values(),
                ],
            ])
            ->all();
    }

    /**
     * @return Collection<int|string, string>
     */
    #[Computed]
    public function projectOptions(): Collection
    {
        return Project::query()->orderBy('name')->pluck('name', 'id');
    }

    /**
     * @return Collection<int|string, string>
     */
    #[Computed]
    public function picOptions(): Collection
    {
        return User::query()->orderBy('name')->pluck('name', 'id');
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return ProjectHistory::STATUS_LABELS;
    }

    public function openCreate(): void
    {
        $this->modalMode = 'create';
        $this->editingHistoryId = null;
        $this->formProjectId = $this->projectFilter;
        $this->formTitle = '';
        $this->formStatus = ProjectHistory::STATUS_TODO;
        $this->formNote = null;
        $this->formProgress = null;
        $this->formUpdateDate = now()->toDateString();
        $this->resetValidation();
    }

    public function openEdit(int $historyId): void
    {
        $history = ProjectHistory::query()->findOrFail($historyId);

        $this->modalMode = 'edit';
        $this->editingHistoryId = $history->getKey();
        $this->formProjectId = $history->project_id !== null ? (string) $history->project_id : null;
        $this->formTitle = (string) $history->title;
        $this->formStatus = $history->status;
        $this->formNote = $history->note;
        $this->formProgress = $history->progress_percent;
        $this->formUpdateDate = $history->update_date?->toDateString();
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->modalMode = null;
        $this->editingHistoryId = null;
        $this->resetValidation();
    }

    public function saveHistory(): void
    {
        $validated = $this->validate([
            'formProjectId' => ['required', 'integer', 'exists:projects,id'],
            'formTitle' => ['required', 'string', 'max:255'],
            'formStatus' => ['required', 'string', 'in:'.implode(',', ProjectHistory::STATUSES)],
            'formUpdateDate' => ['required', 'date'],
            'formProgress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'formNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $service = app(ProjectService::class);

        if ($this->modalMode === 'edit' && $this->editingHistoryId !== null) {
            $history = ProjectHistory::query()->findOrFail($this->editingHistoryId);

            $service->updateHistory($history, [
                'status' => $validated['formStatus'],
                'title' => $validated['formTitle'],
                'note' => $validated['formNote'] ?? null,
                'progress_percent' => $validated['formProgress'] ?? null,
                'update_date' => $validated['formUpdateDate'],
            ]);
        } else {
            $project = Project::query()->findOrFail($validated['formProjectId']);

            $service->recordHistory(
                project: $project,
                title: $validated['formTitle'],
                status: $validated['formStatus'],
                note: $validated['formNote'] ?? null,
                progressPercent: $validated['formProgress'] ?? null,
                date: Carbon::parse($validated['formUpdateDate']),
                userId: auth()->id(),
            );
        }

        unset($this->columns);

        Notification::make()
            ->title('Riwayat tersimpan')
            ->success()
            ->send();

        $this->closeModal();
    }

    public function deleteHistory(): void
    {
        if ($this->editingHistoryId === null) {
            return;
        }

        $history = ProjectHistory::query()->findOrFail($this->editingHistoryId);

        app(ProjectService::class)->deleteHistory($history);

        unset($this->columns);

        Notification::make()
            ->title('Riwayat dihapus')
            ->success()
            ->send();

        $this->closeModal();
    }

    /**
     * Drop kartu ke area kosong sebuah kolom: pindah ke kolom itu, urutan paling
     * akhir.
     */
    public function moveHistoryTo(int $historyId, string $status): void
    {
        if (! in_array($status, ProjectHistory::STATUSES, true)) {
            return;
        }

        $history = ProjectHistory::query()->find($historyId);

        if (! $history instanceof ProjectHistory) {
            return;
        }

        app(ProjectService::class)->moveHistory($history, $status, null);

        unset($this->columns);
    }

    /**
     * Drop kartu tepat di atas kartu lain: ikut kolom kartu tujuan dan disisipkan
     * sebelum kartu itu.
     */
    public function moveHistoryBefore(int $historyId, int $beforeHistoryId): void
    {
        if ($historyId === $beforeHistoryId) {
            return;
        }

        $history = ProjectHistory::query()->find($historyId);
        $before = ProjectHistory::query()->find($beforeHistoryId);

        if (! $history instanceof ProjectHistory || ! $before instanceof ProjectHistory) {
            return;
        }

        app(ProjectService::class)->moveHistory($history, $before->status, $before->getKey());

        unset($this->columns);
    }

    public function resetFilters(): void
    {
        $this->projectFilter = null;
        $this->picFilter = null;
        $this->search = '';

        unset($this->columns);
    }

    /**
     * @return Builder<ProjectHistory>
     */
    protected function filteredHistories(): Builder
    {
        $query = ProjectHistory::query()
            ->with(['project.pic', 'creator'])
            ->when(filled($this->projectFilter), fn (Builder $query): Builder => $query->where('project_id', (int) $this->projectFilter))
            ->when(filled($this->picFilter), fn (Builder $query): Builder => $query->whereHas(
                'project',
                fn (Builder $sub): Builder => $sub->where('pic_id', (int) $this->picFilter),
            ))
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%'.trim($this->search).'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('title', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            });

        // Tanpa filter project, board jadi ringkasan: cukup tampilkan riwayat
        // terakhir tiap project supaya seluruh project terlihat tanpa scroll.
        if (blank($this->projectFilter)) {
            $query->whereIn('id', ProjectHistory::query()
                ->selectRaw('MAX(id)')
                ->groupBy('project_id'));
        }

        return $query->orderBy('sort_order')->orderBy('id');
    }
}
