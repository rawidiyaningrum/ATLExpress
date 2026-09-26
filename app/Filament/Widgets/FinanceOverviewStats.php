<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithFinancePeriod;
use App\Models\FinanceJournal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverviewStats extends StatsOverviewWidget
{
    use InteractsWithFinancePeriod;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $totals = $this->financeTotals();
        $receivable = $this->financeOutstandingReceivable();
        $margin = $totals['income'] > 0
            ? FinanceJournal::percentage($totals['profit'], $totals['income'])
            : 0.0;

        return [
            Stat::make('Pendapatan', $this->rupiah($totals['income']))
                ->description($this->financePeriodLabel())
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Total Biaya', $this->rupiah($totals['total_expense']))
                ->description('Modal, opex, dan pajak')
                ->descriptionIcon('heroicon-m-receipt-percent'),
            Stat::make('Profit', $this->rupiah($totals['profit']))
                ->description('Pendapatan dikurangi total biaya')
                ->descriptionIcon($totals['profit'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totals['profit'] >= 0 ? 'success' : 'danger'),
            Stat::make('Margin', $this->percentage($margin))
                ->description('Profit dibagi pendapatan')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->color($margin >= 0 ? 'success' : 'danger'),
            Stat::make('Kas Masuk', $this->rupiah($this->financeCashReceived()))
                ->description('Invoice yang lunas pada '.$this->financePeriodLabel())
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),
            Stat::make('Piutang Belum Lunas', $this->rupiah($receivable['total']))
                ->description(sprintf(
                    '%d invoice tertagih, saldo saat ini dan tidak ikut difilter periode',
                    $receivable['count'],
                ))
                ->descriptionIcon('heroicon-m-clock')
                ->color($receivable['total'] > 0 ? 'warning' : 'gray'),
            Stat::make('Margin Rata-rata', $this->percentage($totals['average_percentage']))
                ->description('Rata-rata profit persen per jurnal')
                ->descriptionIcon('heroicon-m-chart-bar'),
            Stat::make('Jumlah Jurnal', $totals['entries'])
                ->description('Entri pendapatan pada periode ini')
                ->descriptionIcon('heroicon-m-document-text'),
        ];
    }

    protected function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    protected function percentage(float $value): string
    {
        return number_format($value, 2).'%';
    }
}
