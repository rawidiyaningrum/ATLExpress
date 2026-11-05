<?php

namespace App\Filament\Resources\TariffSearchLogResource\Pages;

use App\Filament\Resources\TariffSearchLogResource;
use App\Models\TariffSearchLog;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTariffSearchLogs extends ListRecords
{
    protected static string $resource = TariffSearchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TariffSearchLogResource\Widgets\TariffSearchOverviewStats::class,
            TariffSearchLogResource\Widgets\PopularTariffRoutesChart::class,
        ];
    }

    public function getTabs(): array
    {
        $todayCount = TariffSearchLog::whereDate('created_at', today())->count();
        $noRatesCount = TariffSearchLog::where('results_count', 0)->count();

        return [
            'all' => Tab::make('Semua Log'),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn ($query) => $query->whereDate('created_at', today()))
                ->badge($todayCount > 0 ? (string) $todayCount : null)
                ->badgeColor('info'),

            'not_found' => Tab::make('Belum Ada Tarif')
                ->modifyQueryUsing(fn ($query) => $query->where('results_count', 0))
                ->badge($noRatesCount > 0 ? (string) $noRatesCount : null)
                ->badgeColor('warning'),

            'successful' => Tab::make('Tarif Ditemukan')
                ->modifyQueryUsing(fn ($query) => $query->where('results_count', '>', 0)),
        ];
    }
}

