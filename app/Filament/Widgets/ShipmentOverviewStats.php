<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShipmentOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pengiriman';

    protected ?string $description = 'Total, hari ini, dan status yang sedang berjalan.';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Shipment', Shipment::query()->count())
                ->description('Sepanjang waktu')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
            Stat::make('Hari Ini', Shipment::query()->whereDate('created_at', today())->count())
                ->description('Dibuat hari ini')
                ->descriptionIcon('heroicon-m-calendar-days'),
            Stat::make('Belum Ada Invoice', $this->countWithoutInvoice())
                ->description('Perlu diproses ke invoice')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
            Stat::make(Shipment::STATUS_LABELS[Shipment::STATUS_DRAFT], $this->countByStatus(Shipment::STATUS_DRAFT))
                ->description('Belum diproses')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('gray'),
            Stat::make(Shipment::STATUS_LABELS[Shipment::STATUS_IN_TRANSIT], $this->countByStatus(Shipment::STATUS_IN_TRANSIT))
                ->description('Dalam pengiriman')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('info'),
            Stat::make(Shipment::STATUS_LABELS[Shipment::STATUS_DELIVERED], $this->countByStatus(Shipment::STATUS_DELIVERED))
                ->description('Sudah diterima')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }

    protected function countByStatus(string $status): int
    {
        return Shipment::query()->where('status', $status)->count();
    }

    /**
     * Shipment yang belum punya invoice dan tidak dibatalkan, mengikuti aturan
     * yang sama dengan tombol invoice di tabel shipment.
     */
    protected function countWithoutInvoice(): int
    {
        return Shipment::query()
            ->where('status', '!=', Shipment::STATUS_CANCELLED)
            ->whereDoesntHave('invoices')
            ->count();
    }
}
