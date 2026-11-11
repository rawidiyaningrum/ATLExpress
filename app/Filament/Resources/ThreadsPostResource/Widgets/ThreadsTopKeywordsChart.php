<?php

namespace App\Filament\Resources\ThreadsPostResource\Widgets;

use App\Models\ThreadsPost;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ThreadsTopKeywordsChart extends ChartWidget
{
    protected const LIMIT = 10;

    protected static ?string $heading = 'Keyword Paling Produktif';

    protected static ?string $description = 'Keyword yang paling banyak menemukan postingan permintaan kargo.';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = ThreadsPost::query()
            ->whereNotNull('matched_keyword')
            ->select('matched_keyword', DB::raw('count(*) as aggregate'))
            ->groupBy('matched_keyword')
            ->orderByDesc('aggregate')
            ->limit(self::LIMIT)
            ->get();

        return [
            'labels' => $rows->pluck('matched_keyword')->all(),
            'datasets' => [[
                'label' => 'Postingan',
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
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
