<?php

namespace App\Filament\Resources\TariffSearchLogResource\Widgets;

use App\Models\TariffSearchLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PopularTariffRoutesChart extends ChartWidget
{
    protected const LIMIT = 10;

    protected static ?string $heading = 'Resume Rute Paling Sering Dicari';

    protected static ?string $description = '10 rute asal ke tujuan yang paling banyak dicek oleh pengunjung website.';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = TariffSearchLog::query()
            ->select('origin', 'destination', DB::raw('count(*) as aggregate'))
            ->groupBy('origin', 'destination')
            ->orderByDesc('aggregate')
            ->limit(self::LIMIT)
            ->get();

        return [
            'labels' => $rows
                ->map(fn ($row): string => "{$row->origin} → {$row->destination}")
                ->all(),
            'datasets' => [[
                'label' => 'Jumlah Pencarian',
                'data' => $rows->map(fn ($row): int => (int) $row->aggregate)->all(),
                'backgroundColor' => '#0D2053',
                'borderRadius' => 4,
                'maxBarThickness' => 24,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}

