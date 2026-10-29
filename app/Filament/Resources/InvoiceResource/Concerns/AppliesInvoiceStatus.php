<?php

namespace App\Filament\Resources\InvoiceResource\Concerns;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
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

    /**
     * Input tanggal yang muncul hanya saat tujuan transisi membutuhkannya.
     *
     * @return array<int, Forms\Components\DatePicker>
     */
    public static function statusDateForm(string $status): array
    {
        $field = Invoice::dateFieldForStatus($status);

        if ($field === null) {
            return [];
        }

        return [
            Forms\Components\DatePicker::make('status_date')
                ->label(Invoice::dateLabelForStatus($status))
                ->default(fn (): string => now()->toDateString())
                ->required(),
        ];
    }

    /**
     * Tanggal awal popup: tanggal yang sudah tercatat, atau hari ini.
     *
     * @return array<string, string>
     */
    public static function statusDateFormData(Invoice $record, string $status): array
    {
        $field = Invoice::dateFieldForStatus($status);

        if ($field === null) {
            return [];
        }

        return [
            'status_date' => ($record->{$field} ?? null)?->toDateString() ?? now()->toDateString(),
        ];
    }

    public static function applyInvoiceStatus(Invoice $record, string $status, ?string $date = null): void
    {
        app(InvoiceService::class)->transitionTo($record, $status, $date);

        Notification::make()
            ->title(sprintf('Invoice %s kini berstatus %s.', $record->invoice_number, $record->statusLabel()))
            ->body('Jurnal keuangan invoice sudah disesuaikan dengan status barunya.')
            ->success()
            ->send();
    }
}
