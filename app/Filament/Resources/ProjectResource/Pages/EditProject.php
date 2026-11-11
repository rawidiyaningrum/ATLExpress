<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Services\ProjectService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Perubahan status dari form ikut dicatat ke riwayat supaya timeline tetap
     * lengkap, sama seperti perpindahan kartu di board.
     */
    protected function afterSave(): void
    {
        $project = $this->record;

        if (! $project->wasChanged('status')) {
            return;
        }

        app(ProjectService::class)->recordUpdate(
            project: $project,
            status: $project->status,
            note: 'Status diubah dari menu project.',
            progressPercent: $project->progress_percent,
            userId: auth()->id(),
        );
    }
}
