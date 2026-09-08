<?php

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('markAsRead')
                ->label('Mark as Read')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_read' => true]))
                ->visible(fn () => !$this->record->is_read),
        ];
    }
}
