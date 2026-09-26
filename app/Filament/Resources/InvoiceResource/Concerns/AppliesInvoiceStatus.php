<?php

namespace App\Filament\Resources\InvoiceResource\Concerns;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Notifications\Notification;

/**
 * Pemindahan status invoice dipakai bersama oleh tabel daftar invoice dan
 * halaman detail invoice, jadi daftar, tombol, dan notifikasinya konsisten.
 */
trait AppliesInvoiceStatus
{
    /**
     * Definisi aksi untuk status tujuan yang diizinkan dari sebuah invoice.
     *
     * @return array<string, array{label: string, icon: string, color: string, description: string}>
     */
    public static function statusActionsFor(Invoice $record): array
    {
        return Invoice::STATUS_ACTIONS[$record->status] ?? [];
    }

    public static function applyInvoiceStatus(Invoice $record, string $status): void
    {
        app(InvoiceService::class)->transitionTo($record, $status);

        Notification::make()
            ->title(sprintf('Invoice %s kini berstatus %s.', $record->invoice_number, $record->statusLabel()))
            ->body('Jurnal keuangan invoice sudah disesuaikan dengan status barunya.')
            ->success()
            ->send();
    }
}
