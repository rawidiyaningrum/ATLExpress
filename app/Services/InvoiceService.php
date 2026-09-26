<?php

namespace App\Services;

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
        $items = $this->normaliseItems((array) ($state['items'] ?? []));
        $shippingCost = round(max(0, (float) ($state['shipping_cost'] ?? 0)), 2);
        $totals = $this->calculate($shippingCost, $items);

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

        $invoice = $shipment->invoices()->orderByDesc('id')->first();

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

        return $invoice->refresh();
    }

    /**
     * Menandai invoice sebagai final saat langkah cetak dilewati.
     */
    public function finalise(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'final') {
            $invoice->update(['status' => 'final']);
        }

        return $invoice;
    }
}
