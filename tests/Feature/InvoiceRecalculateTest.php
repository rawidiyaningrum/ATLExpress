<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceRecalculateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Invoice dengan ongkir, biaya tambahan, diskon, PPN, dan PPh.
     *
     * Nominal PPN/PPh sengaja masih memakai DPP lama supaya bisa dipastikan
     * tombol hitung ulang menyesuaikannya dengan DPP terbaru.
     */
    private function invoiceWithItems(): Invoice
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB-RECALC-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'receiver_name' => 'Budi Santoso',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 5,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        return app(InvoiceService::class)->saveForShipment($shipment, [
            'billed_to_name' => 'Budi Santoso',
            'billed_to_address' => 'Jl. Tujuan 9, Surabaya',
            'shipping_cost' => 200000,
            'items' => [
                [
                    'description' => 'Packing kayu',
                    'type' => 'additional',
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
                [
                    'description' => 'Diskon kerjasama',
                    'type' => 'diskon',
                    'quantity' => 1,
                    'unit_price' => 20000,
                ],
                [
                    'description' => 'PPN 1,1%',
                    'type' => 'tax',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
                [
                    'description' => 'PPh 2%',
                    'type' => 'discount',
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ], 'draft');
    }

    /**
     * Nominal invoice yang usang, seolah dihitung sebelum data berubah.
     */
    private function staleTotals(Invoice $invoice): void
    {
        $invoice->update([
            'subtotal' => 0,
            'discount' => 0,
            'diskon' => 0,
            'tax' => 0,
            'total' => 0,
        ]);
    }

    public function test_recalculating_updates_derived_amounts_and_finance_journals(): void
    {
        $invoice = $this->invoiceWithItems();
        $invoice->update(['shipping_real_expense' => 150000]);
        $invoice->items()->where('description', 'Packing kayu')->update(['real_expense' => 40000]);

        $this->staleTotals($invoice);

        app(InvoiceService::class)->markFinal($invoice->fresh());

        $invoice = app(InvoiceService::class)->recalculate($invoice->fresh());

        // DPP = 200.000 ongkir + 100.000 biaya tambahan - 20.000 diskon.
        $this->assertSame(280000.0, (float) $invoice->subtotal);
        $this->assertSame(20000.0, (float) $invoice->diskon);
        $this->assertSame(3080.0, (float) $invoice->tax, 'PPN 1,1% dari DPP terbaru');
        $this->assertSame(5600.0, (float) $invoice->discount, 'PPh 2% dari DPP terbaru');
        $this->assertSame(277480.0, (float) $invoice->total, '280.000 - 5.600 + 3.080');

        $this->assertSame(
            3080.0,
            (float) $invoice->items()->where('description', 'PPN 1,1%')->sole()->unit_price,
            'nominal baris PPN ikut diperbarui',
        );
        $this->assertSame(
            5600.0,
            (float) $invoice->items()->where('description', 'PPh 2%')->sole()->unit_price,
            'nominal baris PPh ikut diperbarui',
        );
        $this->assertSame(
            100000.0,
            (float) $invoice->items()->where('description', 'Packing kayu')->sole()->line_total,
            'baris biaya tambahan dihitung ulang dari jumlah x harga satuan',
        );
        $this->assertSame(
            40000.0,
            (float) $invoice->items()->where('description', 'Packing kayu')->sole()->real_expense,
            'pengeluaran real item tidak hilang',
        );

        $journal = FinanceJournal::query()->where('journal_type', FinanceJournal::TYPE_REVENUE)->sole();

        $this->assertSame(277480.0, (float) $journal->income, 'jurnal pendapatan mengikuti total baru');
        $this->assertSame(3080.0, (float) $journal->tax, 'pajak jurnal mengikuti PPN baru');
        $this->assertSame(190000.0, (float) $journal->cost_of_goods, 'modal dari ongkir dan item real tetap');
    }

    public function test_recalculate_action_is_available_from_the_invoice_list(): void
    {
        $invoice = $this->invoiceWithItems();
        $this->staleTotals($invoice);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertTableActionVisible('recalculate', $invoice)
            ->callTableAction('recalculate', $invoice)
            ->assertHasNoTableActionErrors();

        $invoice->refresh();

        $this->assertSame(280000.0, (float) $invoice->subtotal);
        $this->assertSame(3080.0, (float) $invoice->tax);
        $this->assertSame(277480.0, (float) $invoice->total);
    }

    public function test_recalculating_a_paid_invoice_updates_the_receipt_without_overwriting_real_income(): void
    {
        $invoice = $this->invoiceWithItems();
        $service = app(InvoiceService::class);

        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh(), '2026-10-11');
        $service->markPaid($invoice->fresh(), '2026-10-20');

        $receipt = FinanceJournal::query()->where('journal_type', FinanceJournal::TYPE_RECEIPT)->sole();
        $receipt->update(['real_income' => 1000]);

        $this->staleTotals($invoice);

        $invoice = $service->recalculate($invoice->fresh());
        $receipt->refresh();

        $this->assertSame(Invoice::STATUS_LUNAS, $invoice->status, 'status invoice tidak berubah');
        $this->assertSame(277480.0, (float) $invoice->total);
        $this->assertSame(277480.0, (float) $receipt->income, 'jurnal kas mengikuti total baru');
        $this->assertSame(1000.0, (float) $receipt->real_income, 'kas riil yang sudah dicatat tidak ditimpa');
    }
}
