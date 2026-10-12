<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sebelum tipe 'diskon' diperkenalkan, potongan diskon tercatat sebagai
     * tipe 'discount' (yang kini berarti PPh 2%) sehingga tidak pernah
     * mengurangi DPP. Migrasi ini memindahkan baris diskon lama ke tipe
     * 'diskon' dan menghitung ulang subtotal/total invoice terdampak.
     */
    public function up(): void
    {
        $this->reclassify('discount', 'diskon');
    }

    public function down(): void
    {
        $this->reclassify('diskon', 'discount');
    }

    /**
     * Pindahkan tipe item diskon lama, lalu selaraskan total invoice.
     */
    private function reclassify(string $from, string $to): void
    {
        $invoiceIds = DB::table('invoice_items')
            ->where('type', $from)
            ->whereRaw('LOWER(description) LIKE ?', ['%disc%'])
            ->whereRaw('LOWER(description) NOT LIKE ?', ['%pph%'])
            ->distinct()
            ->pluck('invoice_id');

        if ($invoiceIds->isEmpty()) {
            return;
        }

        DB::table('invoice_items')
            ->where('type', $from)
            ->whereIn('invoice_id', $invoiceIds)
            ->whereRaw('LOWER(description) LIKE ?', ['%disc%'])
            ->whereRaw('LOWER(description) NOT LIKE ?', ['%pph%'])
            ->update(['type' => $to]);

        foreach ($invoiceIds as $invoiceId) {
            $this->syncInvoiceTotals((int) $invoiceId);
        }
    }

    /**
     * Hitung ulang kolom total invoice dari item yang tersimpan.
     *
     * Rumusnya sama dengan InvoiceService::calculate():
     * DPP (subtotal) = ongkos kirim + biaya tambahan - diskon.
     */
    private function syncInvoiceTotals(int $invoiceId): void
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();

        if ($invoice === null) {
            return;
        }

        $sums = DB::table('invoice_items')
            ->where('invoice_id', $invoiceId)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'additional' THEN line_total END), 0) AS additional")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'discount' THEN line_total END), 0) AS discount")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'diskon' THEN line_total END), 0) AS diskon")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'tax' THEN line_total END), 0) AS tax")
            ->first();

        $shipping = (float) $invoice->shipping_cost;
        $additional = (float) $sums->additional;
        $discount = (float) $sums->discount;
        $diskon = (float) $sums->diskon;
        $tax = (float) $sums->tax;

        $subtotal = round($shipping + $additional - $diskon, 2);
        $total = round($subtotal - $discount + $tax, 2);

        DB::table('invoices')->where('id', $invoiceId)->update([
            'subtotal' => $subtotal,
            'discount' => round($discount, 2),
            'diskon' => round($diskon, 2),
            'tax' => round($tax, 2),
            'total' => $total,
        ]);
    }
};
