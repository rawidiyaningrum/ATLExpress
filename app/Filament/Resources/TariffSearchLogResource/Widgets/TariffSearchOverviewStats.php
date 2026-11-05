<?php

namespace App\Filament\Resources\TariffSearchLogResource\Widgets;

use App\Models\TariffSearchLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TariffSearchOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pencarian Tarif';

    protected function getStats(): array
    {
        $totalSearches = TariffSearchLog::count();
        $todaySearches = TariffSearchLog::whereDate('created_at', today())->count();
        $noRatesCount = TariffSearchLog::where('results_count', 0)->count();

        $topRoute = TariffSearchLog::query()
            ->select('origin', 'destination', DB::raw('count(*) as aggregate'))
            ->groupBy('origin', 'destination')
            ->orderByDesc('aggregate')
            ->first();

        $topRouteLabel = $topRoute ? "{$topRoute->origin} → {$topRoute->destination}" : 'Belum ada data';
        $topRouteSub = $topRoute ? "{$topRoute->aggregate} kali dicari" : 'Menunggu pencarian';

        return [
            Stat::make('Total Pencarian', number_format($totalSearches, 0, ',', '.'))
                ->description('Sepanjang waktu')
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color('primary'),

            Stat::make('Pencarian Hari Ini', number_format($todaySearches, 0, ',', '.'))
                ->description('Aktivitas pengunjung hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Rute Terpopuler #1', $topRouteLabel)
                ->description($topRouteSub)
                ->descriptionIcon('heroicon-m-fire')
                ->color('success'),

            Stat::make('Belum Ada Tarif', number_format($noRatesCount, 0, ',', '.'))
                ->description('Peluang ekspansi rute baru')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($noRatesCount > 0 ? 'warning' : 'gray'),
        ];
    }
}

