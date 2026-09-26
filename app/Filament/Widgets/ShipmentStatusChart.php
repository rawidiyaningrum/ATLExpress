<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ShipmentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Pengiriman';

    protected static ?string $description = 'Semua shipment berdasarkan status saat ini.';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Shipment::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'labels' => array_map(
                fn (string $status): string => Shipment::STATUS_LABELS[$status] ?? $status,
                Shipment::STATUSES,
            ),
            'datasets' => [[
                'label' => 'Shipment',
                'data' => array_map(
                    fn (string $status): int => (int) ($counts[$status] ?? 0),
                    Shipment::STATUSES,
                ),
                'backgroundColor' => array_map(
                    fn (string $status): string => Shipment::STATUS_CHART_COLORS[$status] ?? '#9ca3af',
                    Shipment::STATUSES,
                ),
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
