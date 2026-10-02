<?php

namespace App\Filament\Resources\FinanceJournalResource\Pages;

use App\Filament\Resources\FinanceJournalResource;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use Filament\Resources\Pages\EditRecord;

class EditFinanceJournal extends EditRecord
{
    protected static string $resource = FinanceJournalResource::class;

    /**
     * Baris pengeluaran real dari form, ditahan supaya bisa ditulis ke invoice
     * setelah jurnal selesai disimpan. Pengulisan ditunda ke afterSave()
     * supaya kolom invoice tidak berubah kalau penyimpanan jurnal gagal.
     *
     * @var array<int|string, mixed>
     */
    private array $expenseRows = [];

    /**
     * Baris pengeluaran real diambil dari invoice yang terkait, jadi formnya
     * selalu urut sama dengan invoice cetak. Jurnal kas masuk tidak punya baris
     * ini, jadi state-nya tidak perlu diisi.
     *
     * Checkbox "sama dengan nominal invoice" juga diturunkan di sini, dari
     * nominal kas riil yang tersimpan, supaya tidak ada dua sumber kebenaran.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $journal = $this->record;

        if (! $journal instanceof FinanceJournal) {
            return $data;
        }

        $data[FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH] = $journal->realIncomeMatchesInvoice();

        if (! $journal->isRevenue()) {
            return $data;
        }

        $invoice = $this->linkedInvoice();

        if (! $invoice instanceof Invoice) {
            return $data;
        }

        return $data + [
            FinanceJournalResource::expenseItemsStatePath() => FinanceJournalResource::rowsFromInvoice($invoice),
        ];
    }

    /**
     * Nominal turunan tidak dikirim balik dari form, karena hook saving di
     * model sudah menghitung ulang dari modal, opex, pajak, dan pendapatan.
     *
     * Status centang "sama dengan nominal invoice" juga tidak dikirim, karena
     * field-nya sudah ditandai tidak didehidrasi dan statusnya diturunkan dari
     * nominal kas riil.
     *
     * Modal untuk jurnal dari invoice juga tidak dibaca dari form: field-nya
     * disembunyikan dan nilainya diambil dari jumlah pengeluaran real per item.
     * Jurnal kas masuk tidak punya baris pengeluaran real, jadi blok ini sengaja
     * hanya jalan untuk jurnal pendapatan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['total_expense'],
            $data['profit'],
            $data['profit_percentage'],
        );

        $rows = $data[FinanceJournalResource::expenseItemsStatePath()] ?? [];
        unset($data[FinanceJournalResource::expenseItemsStatePath()]);

        $this->expenseRows = [];

        if ($this->record?->isRevenue() && $this->linkedInvoice() instanceof Invoice) {
            $this->expenseRows = is_array($rows) ? $rows : [];
            $data['cost_of_goods'] = FinanceJournalResource::sumEditableRows($this->expenseRows);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $journal = $this->record;
        $invoice = $this->linkedInvoice();
        $rows = $this->expenseRows;

        $this->expenseRows = [];

        if (! $journal instanceof FinanceJournal || ! $journal->isRevenue() || ! $invoice instanceof Invoice) {
            return;
        }

        FinanceJournalResource::persistRows($invoice, $rows);
    }

    private function linkedInvoice(): ?Invoice
    {
        return $this->record?->linkedInvoice();
    }
}
