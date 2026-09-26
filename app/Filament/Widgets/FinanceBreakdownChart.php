<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithFinancePeriod;
use Filament\Widgets\ChartWidget;

class FinanceBreakdownChart extends ChartWidget
{
    use InteractsWithFinancePeriod;

    protected static bool $isDiscovered = false;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $totals = $this->financeTotals();

        return [
            'labels' => ['Modal', 'Biaya Operasional', 'Pajak'],
            'datasets' => [[
                'label' => 'Total Biaya',
                'data' => [
                    $totals['cost_of_goods'],
                    $totals['operational_cost'],
                    $totals['tax'],
                ],
                'backgroundColor' => ['#0D2053', '#3b82f6', '#f59e0b'],
                'borderRadius' => 4,
                'maxBarThickness' => 64,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
