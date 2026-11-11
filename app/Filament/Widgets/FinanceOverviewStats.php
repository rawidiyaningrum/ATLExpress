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
        $liabilities = $this->financeOutstandingInvestmentLiabilities();
        $margin = $totals['income'] > 0
            ? FinanceJournal::percentage($totals['profit'], $totals['income'])
            : 0.0;
        $efficiency = $totals['income'] > 0
            ? round(($totals['total_expense'] / $totals['income']) * 100, 2)
            : 0.0;

        return [
            Stat::make('Pendapatan', $this->rupiah($totals['income']))
                ->description($this->financePeriodLabel())
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Pengeluaran Real', $this->rupiah($totals['real_expense']))
                ->description('Modal dari pengeluaran real per item invoice')
                ->descriptionIcon('heroicon-m-calculator'),
            Stat::make('Total Biaya', $this->rupiah($totals['total_expense']))
                ->description('Modal dan biaya operasional')
                ->descriptionIcon('heroicon-m-receipt-percent'),
            Stat::make('Bagi Hasil Investor', $this->rupiah($totals['investment_profit_sharing']))
                ->description('Beban bagi hasil investor pada periode ini')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($totals['investment_profit_sharing'] > 0 ? 'warning' : 'gray'),
            Stat::make('Profit', $this->rupiah($totals['profit']))
                ->description('Pendapatan dikurangi biaya & bagi hasil investor')
                ->descriptionIcon($totals['profit'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totals['profit'] >= 0 ? 'success' : 'danger'),
            Stat::make('Margin', $this->percentage($margin))
                ->description('Profit dibagi pendapatan')
                ->descriptionIcon('heroicon-m-percent-badge')
                ->color($margin >= 0 ? 'success' : 'danger'),
            Stat::make('Efisiensi Biaya', $this->percentage($efficiency))
                ->description('Total biaya dibagi pendapatan')
                ->descriptionIcon('heroicon-m-scale')
                ->color($efficiency > 0 && $efficiency <= 100 ? 'success' : 'warning'),
            Stat::make('Kas Masuk', $this->rupiah($this->financeCashReceived()))
                ->description('Kas riil dari invoice lunas pada '.$this->financePeriodLabel())
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),
            Stat::make('Piutang Belum Lunas', $this->rupiah($receivable['total']))
                ->description(sprintf(
                    '%d invoice final/berjalan, saldo saat ini',
                    $receivable['count'],
                ))
                ->descriptionIcon('heroicon-m-clock')
                ->color($receivable['total'] > 0 ? 'warning' : 'gray'),
            Stat::make('Hutang Bagi Hasil', $this->rupiah($liabilities['profit_sharing_total']))
                ->description(sprintf(
                    '%d investasi belum dicairkan bagi hasilnya (saldo saat ini)',
                    $liabilities['profit_sharing_count'],
                ))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($liabilities['profit_sharing_total'] > 0 ? 'danger' : 'success'),
            Stat::make('Hutang Pokok Modal', $this->rupiah($liabilities['capital_total']))
                ->description(sprintf(
                    '%d investasi belum dikembalikan pokok modalnya',
                    $liabilities['capital_count'],
                ))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($liabilities['capital_total'] > 0 ? 'warning' : 'gray'),
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
