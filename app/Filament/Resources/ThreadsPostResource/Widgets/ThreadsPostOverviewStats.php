<?php

namespace App\Filament\Resources\ThreadsPostResource\Widgets;

use App\Models\ThreadsPost;
use App\Services\Threads\ThreadsQueryQuota;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ThreadsPostOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Permintaan Kargo Threads';

    protected function getStats(): array
    {
        $total = ThreadsPost::count();
        $today = ThreadsPost::query()->whereDate('posted_at', today())->count();
        $lastWeek = ThreadsPost::query()->where('posted_at', '>=', now()->subDays(7))->count();
        $new = ThreadsPost::query()->status(ThreadsPost::STATUS_NEW)->count();
        $won = ThreadsPost::query()->status(ThreadsPost::STATUS_WON)->count();

        $quota = app(ThreadsQueryQuota::class);

        return [
            Stat::make('Total Postingan', number_format($total, 0, ',', '.'))
                ->description('Sepanjang waktu')
                ->descriptionIcon('heroicon-m-at-symbol')
                ->color('primary'),

            Stat::make('Hari Ini', number_format($today, 0, ',', '.'))
                ->description(number_format($lastWeek, 0, ',', '.').' dalam 7 hari terakhir')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Belum Dihubungi', number_format($new, 0, ',', '.'))
                ->description('Butuh tindak lanjut')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($new > 0 ? 'warning' : 'gray'),

            Stat::make('Jadi Pelanggan', number_format($won, 0, ',', '.'))
                ->description('Dikonversi dari Threads')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Sisa Kuota Query', number_format($quota->remaining(), 0, ',', '.'))
                ->description('dari '.number_format($quota->cap(), 0, ',', '.').' query / hari')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($quota->remaining() > 0 ? 'primary' : 'danger'),
        ];
    }
}
