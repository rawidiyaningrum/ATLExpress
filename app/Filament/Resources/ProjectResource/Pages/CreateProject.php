<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Services\ProjectService;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Baris riwayat pertama dicatat otomatis supaya timeline project tidak
     * kosong saat baru dibuat.
     */
    protected function afterCreate(): void
    {
        $project = $this->record;

        app(ProjectService::class)->recordUpdate(
            project: $project,
            status: $project->status,
            note: 'Project dibuat.',
            progressPercent: $project->progress_percent,
            userId: auth()->id(),
        );
    }
}
