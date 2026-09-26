<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ShipmentTrendChart extends ChartWidget
{
    protected const DAYS = 14;

    protected static ?string $heading = 'Tren Pengiriman';

    protected static ?string $description = 'Shipment dibuat per hari, 14 hari terakhir.';

    protected int|string|array $columnSpan = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = today()->subDays(self::DAYS - 1);

        $counts = Shipment::query()
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $labels = $values = [];

        for ($offset = 0; $offset < self::DAYS; $offset++) {
            $day = $start->copy()->addDays($offset);

            $labels[] = $day->format('d M');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Shipment',
                'data' => $values,
                'backgroundColor' => '#0D2053',
                'borderRadius' => 4,
                'maxBarThickness' => 32,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
