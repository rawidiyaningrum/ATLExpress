<?php

namespace App\Services;

use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Support\NumberGenerator;
use Illuminate\Support\Collection;

class InvoiceService
{
    public const TYPE_ADDITIONAL = 'additional';

    public const TYPE_DISCOUNT = 'discount';

    public const TYPE_TAX = 'tax';

    public const BASIS_FINAL_TARIFF = 'final_tariff';

    public const BASIS_PREVIOUS_ITEMS = 'previous_items';

    /**
     * @return array<string, string>
     */
    public function itemTypeLabels(): array
    {
        return [
            self::TYPE_ADDITIONAL => 'Biaya Tambahan',
            self::TYPE_DISCOUNT => 'Diskon / Potongan',
            self::TYPE_TAX => 'Pajak',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function itemTypeOptions(): array
    {
        return $this->itemTypeLabels();
    }

    /**
     * Dasar perhitungan nominal item.
     *
     * @return array<string, string>
     */
    public function itemBasisLabels(): array
    {
        return [
            self::BASIS_FINAL_TARIFF => 'Tarif final',
            self::BASIS_PREVIOUS_ITEMS => 'Subtotal item sebelumnya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function itemBasisOptions(): array
    {
        return $this->itemBasisLabels();
    }

    /**
     * Menghitung seluruh total dari ongkir dan item tambahan.
     *
     * Diskon dan pajak diperlakukan sebagai item bertipe, lalu digabung ke
     * kolom discount dan tax. Subtotal selalu ongkir + biaya tambahan, sehingga
     * pajak dihitung atas dasar yang tidak ikut terpengaruh diskon.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{shipping: float, additional: float, discount: float, tax: float, subtotal: float, total: float}
     */
    public function calculate(float $shippingCost, array $items): array
    {
        $additional = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($this->normaliseItems($items) as $item) {
            match ($item['type']) {
                self::TYPE_DISCOUNT => $discount += $item['line_total'],
                self::TYPE_TAX => $tax += $item['line_total'],
                default => $additional += $item['line_total'],
            };
        }

        $subtotal = $shippingCost + $additional;

        return [
            'shipping' => round($shippingCost, 2),
            'additional' => round($additional, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal - $discount + $tax, 2),
        ];
    }

    /**
     * Membersihkan repeater menjadi baris item yang siap disimpan.
     *
     * Baris tanpa deskripsi dibuang, karena sisa baris repeater yang dihapus
     * di UI masih sempat terkirim sebagai array kosong.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{description: string, type: string, basis: string, quantity: int, unit_price: float, line_total: float}>
     */
    public function normaliseItems(array $items): array
    {
        $types = array_keys($this->itemTypeLabels());
        $bases = array_keys($this->itemBasisLabels());

        return Collection::make($items)
            ->map(function (array $item) use ($types, $bases): ?array {
                $description = trim((string) ($item['description'] ?? ''));

                if ($description === '') {
                    return null;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = round(max(0, (float) ($item['unit_price'] ?? 0)), 2);
                $type = in_array($item['type'] ?? null, $types, true) ? $item['type'] : self::TYPE_ADDITIONAL;
                $basis = in_array($item['dihitung_dari'] ?? $item['basis'] ?? null, $bases, true)
                    ? ($item['dihitung_dari'] ?? $item['basis'])
                    : self::BASIS_FINAL_TARIFF;

                return [
                    'description' => $description,
                    'type' => $type,
                    'basis' => $basis,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($quantity * $unitPrice, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Menyimpan invoice milik sebuah shipment beserta itemnya.
     *
     * Satu shipment memakai satu invoice: baris yang sudah ada diperbarui,
     * item disinkronkan penuh karena repeater tidak menyisakan identifier
     * stabil per baris.
     *
     * @param  array<string, mixed>  $state
     */
    public function saveForShipment(Shipment $shipment, array $state, string $status = 'draft'): Invoice
    {
        abort_if(
            ! in_array($status, Invoice::STATUSES, true),
            422,
            "Status invoice '{$status}' tidak dikenal.",
        );

        $items = $this->normaliseItems((array) ($state['items'] ?? []));
        $shippingCost = round(max(0, (float) ($state['shipping_cost'] ?? 0)), 2);
        $totals = $this->calculate($shippingCost, $items);

        $invoice = $shipment->invoices()->orderByDesc('id')->first();

        // Penurunan status hanya boleh lewat transitionTo(), karena di sana
        // jurnal yang sudah tercatat ikut dibersihkan. Simpan form tidak boleh
        // diam-diam mengembalikan invoice yang sudah ditagihkan ke draft.
        if ($invoice?->isLocked() === true) {
            abort(422, sprintf(
                'Invoice %s berstatus %s dan tidak bisa diubah lagi.',
                $invoice->invoice_number,
                $invoice->statusLabel(),
            ));
        }

        $attributes = [
            'billed_to_name' => $state['billed_to_name'] ?? null,
            'billed_to_address' => $state['billed_to_address'] ?? null,
            'shipping_cost' => $totals['shipping'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'status' => $status,
        ];

        if ($invoice === null) {
            $invoice = app(NumberGenerator::class)->persistWithRetry(
                'invoiceNumber',
                fn (string $number): Invoice => Invoice::create(
                    $attributes + ['shipment_id' => $shipment->id, 'invoice_number' => $number],
                ),
            );
        } else {
            $invoice->fill($attributes + ['shipment_id' => $shipment->id])->save();
        }

        $invoice->items()->delete();
        $invoice->items()->createMany($items);

        $this->syncFinanceJournals($invoice->refresh());

        return $invoice->refresh();
    }

    /**
     * Memindahkan invoice ke status tujuan, lalu menyelaraskan jurnalnya.
     *
     * Hanya transisi yang diizinkan Invoice::STATUS_ACTIONS yang diterima.
     * Melompat dari draft ke lunas ditolak karena pendapatan dan penerimaan kas
     * harus tercatat pada dua titik yang berbeda.
     */
    public function transitionTo(Invoice $invoice, string $status): Invoice
    {
        abort_if(
            ! in_array($status, Invoice::STATUSES, true),
            422,
            "Status invoice '{$status}' tidak dikenal.",
        );

        abort_if(
            $status !== $invoice->status && ! $invoice->canTransitionTo($status),
            422,
            sprintf(
                'Invoice %s berstatus %s dan tidak bisa langsung menjadi %s.',
                $invoice->invoice_number,
                $invoice->statusLabel(),
                Invoice::statusLabelFor($status),
            ),
        );

        if ($status !== $invoice->status) {
            $invoice->update(['status' => $status]);
        }

        $this->syncFinanceJournals($invoice->refresh());

        return $invoice;
    }

    /**
     * Invoice sudah ditagihkan ke pelanggan, jadi pendapatannya diakui.
     */
    public function markBilled(Invoice $invoice): Invoice
    {
        return $this->transitionTo($invoice, Invoice::STATUS_TERTAGIH);
    }

    /**
     * Invoice sudah dibayar, jadi kas masuknya dicatat.
     */
    public function markPaid(Invoice $invoice): Invoice
    {
        return $this->transitionTo($invoice, Invoice::STATUS_LUNAS);
    }

    /**
     * Menyelaraskan jurnal keuangan dengan status invoice.
     *
     * Satu invoice punya dua jurnal dengan siklus hidup berbeda:
     *
     * - jurnal pendapatan dibuat saat invoice ditagihkan, dan hilang lagi
     *   kalau invoice dikembalikan ke draft;
     * - jurnal kas masuk dibuat saat invoice dilunasi, dan hilang lagi kalau
     *   invoice dikembalikan ke tertagih.
     *
     * Jurnal kas hanya mencatat penerimaan kas. Pendapatan dan pajaknya sudah
     * diakui di jurnal pendapatan, jadi keduanya tidak boleh ikut diulang di sini.
     */
    protected function syncFinanceJournals(Invoice $invoice): void
    {
        if ($invoice->status === Invoice::STATUS_DRAFT) {
            FinanceJournal::deleteForReference($invoice->invoice_number, $invoice->shipment_id);

            return;
        }

        FinanceJournal::syncFromTotals(
            $invoice->invoice_number,
            [
                'income' => (float) $invoice->total,
                'tax' => (float) $invoice->tax,
            ],
            $invoice->shipment_id,
            $invoice->created_at?->toDateString(),
            FinanceJournal::TYPE_REVENUE,
        );

        if ($invoice->status === Invoice::STATUS_LUNAS) {
            FinanceJournal::syncFromTotals(
                $invoice->invoice_number,
                [
                    'income' => (float) $invoice->total,
                    'tax' => 0,
                ],
                $invoice->shipment_id,
                now()->toDateString(),
                FinanceJournal::TYPE_RECEIPT,
            );

            return;
        }

        FinanceJournal::deleteForReference(
            $invoice->invoice_number,
            $invoice->shipment_id,
            FinanceJournal::TYPE_RECEIPT,
        );
    }
}
