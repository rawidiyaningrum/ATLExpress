<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithFinancePeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinanceTrendChart extends ChartWidget
{
    use InteractsWithFinancePeriod;

    protected static bool $isDiscovered = false;

    /**
     * Di atas rentang ini, pengelompokan harian membuat grafik terlalu padat.
     */
    protected const DAILY_LIMIT_DAYS = 90;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$from, $to] = $this->financePeriod();

        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        $daily = abs($start->diffInDays($end)) <= self::DAILY_LIMIT_DAYS;

        // Pengelompokan tanggal dilakukan di PHP karena pemotongan bulan di SQL
        // berbeda tiap driver, sedangkan jurnal sudah dibatasi oleh filter
        // periode sehingga jumlah barisnya kecil.
        $key = fn ($date): string => $daily
            ? Carbon::parse($date)->toDateString()
            : Carbon::parse($date)->format('Y-m');

        $revenue = $this->financeRevenueQuery()
            ->get(['entry_date', 'income', 'profit'])
            ->groupBy(fn ($entry): string => $key($entry->entry_date));

        $receipts = $this->financeReceiptQuery()
            ->get(['entry_date', 'income'])
            ->groupBy(fn ($entry): string => $key($entry->entry_date));

        $labels = $income = $profit = $cash = [];
        $cursor = $daily ? $start->copy() : $start->copy()->startOfMonth();
        $last = $daily ? $end->copy() : $end->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last)) {
            $group = $revenue->get($key($cursor), collect());

            $labels[] = $daily ? $cursor->format('d M') : $cursor->translatedFormat('M Y');
            $income[] = (float) $group->sum('income');
            $profit[] = (float) $group->sum('profit');
            $cash[] = (float) $receipts->get($key($cursor), collect())->sum('income');

            $daily ? $cursor->addDay() : $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $income,
                    'borderColor' => '#0D2053',
                    'backgroundColor' => 'rgba(13, 32, 83, 0.08)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 0,
                ],
                [
                    'label' => 'Profit',
                    'data' => $profit,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 0,
                ],
                [
                    'label' => 'Kas Masuk',
                    'data' => $cash,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                    'fill' => false,
                    'borderDash' => [6, 4],
                    'tension' => 0.3,
                    'pointRadius' => 0,
                ],
            ],
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
