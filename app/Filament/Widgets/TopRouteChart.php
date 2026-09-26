<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopRouteChart extends ChartWidget
{
    protected const LIMIT = 5;

    protected static ?string $heading = 'Rute Terbanyak';

    protected static ?string $description = 'Lima rute dengan shipment terbanyak.';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // Rute digabung di PHP, bukan lewat concat SQL, karena operator
        // penggabung string berbeda antara SQLite dan MySQL.
        $rows = Shipment::query()
            ->select('origin', 'destination', DB::raw('count(*) as aggregate'))
            ->groupBy('origin', 'destination')
            ->orderByDesc('aggregate')
            ->limit(self::LIMIT)
            ->get();

        return [
            'labels' => $rows
                ->map(fn (Shipment $shipment): string => "{$shipment->origin} → {$shipment->destination}")
                ->all(),
            'datasets' => [[
                'label' => 'Shipment',
                'data' => $rows->map(fn (Shipment $shipment): int => (int) $shipment->aggregate)->all(),
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
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
