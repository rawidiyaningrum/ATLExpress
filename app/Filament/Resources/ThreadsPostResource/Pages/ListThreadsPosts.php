<?php

namespace App\Filament\Resources\ThreadsPostResource\Pages;

use App\Filament\Resources\ThreadsPostResource;
use App\Models\ThreadsPost;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListThreadsPosts extends ListRecords
{
    protected static string $resource = ThreadsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [];
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
