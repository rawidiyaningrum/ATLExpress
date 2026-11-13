<?php

namespace App\Filament\Resources\ThreadsPostResource\Pages;

use App\Filament\Resources\ThreadsPostResource;
use App\Models\ThreadsPost;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListThreadsPosts extends ListRecords
{
    protected static string $resource = ThreadsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearSearchResults')
                ->label('Kosongkan Pencarian')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Kosongkan hasil pencarian Threads?')
                ->modalDescription('Semua postingan hasil pencarian akan dihapus permanen. Keyword dan pengaturan tetap tersimpan.')
                ->visible(fn (): bool => auth()->user()?->isAdminRole() ?? false)
                ->action(function (): void {
                    abort_unless(auth()->user()?->isAdminRole() ?? false, 403);

                    $deleted = ThreadsPost::query()->delete();

                    Notification::make()
                        ->title('Hasil pencarian dikosongkan')
                        ->body($deleted.' postingan dihapus. Keyword dan pengaturan tetap tersimpan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ThreadsPostResource\Widgets\ThreadsPostOverviewStats::class,
            ThreadsPostResource\Widgets\ThreadsTopKeywordsChart::class,
        ];
    }

    public function getTabs(): array
    {
        $newCount = ThreadsPost::query()->status(ThreadsPost::STATUS_NEW)->count();

        return [
            'all' => Tab::make('Semua'),

            'new' => Tab::make('Belum Dihubungi')
                ->modifyQueryUsing(fn ($query) => $query->status(ThreadsPost::STATUS_NEW))
                ->badge($newCount > 0 ? (string) $newCount : null)
                ->badgeColor('warning'),

            'contacted' => Tab::make('Sudah Dihubungi')
                ->modifyQueryUsing(fn ($query) => $query->status(ThreadsPost::STATUS_CONTACTED)),

            'won' => Tab::make('Jadi Pelanggan')
                ->modifyQueryUsing(fn ($query) => $query->status(ThreadsPost::STATUS_WON)),

            'ignored' => Tab::make('Diabaikan')
                ->modifyQueryUsing(fn ($query) => $query->status(ThreadsPost::STATUS_IGNORED)),
        ];
    }
}
