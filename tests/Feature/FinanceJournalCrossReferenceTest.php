<?php

namespace Tests\Feature;

use App\Filament\Resources\FinanceJournalResource\Pages\ListFinanceJournals;
use App\Filament\Widgets\RecentFinanceJournalsTable;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceJournalCrossReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(): Shipment
    {
        return Shipment::create([
            'awb_number' => 'AWB-XREF-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);
    }

    private function invoice(Shipment $shipment): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-XREF-'.(Invoice::count() + 1),
            'shipment_id' => $shipment->id,
            'billed_to_name' => 'PT Penerima',
            'shipping_cost' => 150000,
            'subtotal' => 180000,
            'discount' => 0,
            'tax' => 19800,
            'total' => 199800,
            'status' => Invoice::STATUS_DRAFT,
        ]);
    }

    /**
     * @return array{FinanceJournal, FinanceJournal}
     */
    private function paidInvoiceJournals(): array
    {
        $invoice = $this->invoice($this->shipment());

        $service = app(InvoiceService::class);
        $service->markFinal($invoice);
        $service->markInProgress($invoice->refresh());
        $service->markPaid($invoice->refresh());

        return [
            FinanceJournal::query()->where('journal_type', FinanceJournal::TYPE_REVENUE)->sole(),
            FinanceJournal::query()->where('journal_type', FinanceJournal::TYPE_RECEIPT)->sole(),
        ];
    }

    public function test_paired_journals_reference_each_other(): void
    {
        [$revenue, $receipt] = $this->paidInvoiceJournals();

        $this->assertTrue($revenue->partnerJournal()->is($receipt), 'jurnal pendapatan menunjuk jurnal kasnya');
        $this->assertTrue($receipt->partnerJournal()->is($revenue), 'jurnal kas menunjuk jurnal pendapatannya');
    }

    public function test_revenue_placeholder_follows_invoice_status(): void
    {
        $invoice = $this->invoice($this->shipment());
        app(InvoiceService::class)->markFinal($invoice);

        $revenue = FinanceJournal::query()->where('journal_type', FinanceJournal::TYPE_REVENUE)->sole();

        $this->assertSame('Belum dibayar', $revenue->kasRealPlaceholder(), 'invoice final belum menerima kas');

        $revenue->linkedInvoice()->update(['status' => Invoice::STATUS_BERJALAN]);
        $this->assertSame('Menunggu pencairan', $revenue->refresh()->kasRealPlaceholder());

        $revenue->linkedInvoice()->update(['status' => Invoice::STATUS_LUNAS]);
        $this->assertSame('Sudah lunas', $revenue->refresh()->kasRealPlaceholder());

        $manual = FinanceJournal::create([
            'journal_type' => FinanceJournal::TYPE_REVENUE,
            'entry_date' => now()->toDateString(),
            'income' => 50000,
        ]);

        $this->assertSame('Belum dicatat', $manual->kasRealPlaceholder(), 'jurnal manual tanpa invoice tetap "Belum dicatat"');
    }

    public function test_lunas_revenue_row_shows_cash_reference_instead_of_belum_dicatat(): void
    {
        $this->paidInvoiceJournals();

        $html = Livewire::actingAs(User::factory()->create())
            ->test(RecentFinanceJournalsTable::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Sudah lunas', $html);
        $this->assertStringContainsString('kas tercatat di jurnal kas masuk', $html);
        $this->assertStringNotContainsString('Belum dicatat', $html, 'invoice lunas tidak boleh terlihat "Belum dicatat"');
        $this->assertStringContainsString('Pasangan jurnal pendapatan', $html, 'baris kas masuk menunjuk baris pendapatan');
    }

    public function test_final_revenue_row_shows_belum_dibayar(): void
    {
        $invoice = $this->invoice($this->shipment());
        app(InvoiceService::class)->markFinal($invoice);

        $html = Livewire::actingAs(User::factory()->create())
            ->test(RecentFinanceJournalsTable::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Belum dibayar', $html);
        $this->assertStringNotContainsString('Sudah lunas', $html);
    }

    public function test_manual_receipt_keeps_belum_dicatat_placeholder(): void
    {
        FinanceJournal::create([
            'journal_type' => FinanceJournal::TYPE_RECEIPT,
            'entry_date' => now()->toDateString(),
            'income' => 250000,
        ]);

        $html = Livewire::actingAs(User::factory()->create())
            ->test(RecentFinanceJournalsTable::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Belum dicatat', $html);
    }

    public function test_receipt_widget_row_shows_dash_for_revenue_only_columns(): void
    {
        $this->paidInvoiceJournals();

        $html = Livewire::actingAs(User::factory()->create())
            ->test(RecentFinanceJournalsTable::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Nominal tagihan: Rp 199.800', $html, 'baris kas masuk menjelaskan tagihan yang dibayar');
    }

    public function test_resource_receipt_line_hides_revenue_only_numbers(): void
    {
        $this->paidInvoiceJournals();

        $html = Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class)
            ->assertOk()
            ->html();

        $this->assertStringContainsString('Pendapatan: —', $html, 'baris kas masuk tidak mengulang pendapatan');
        $this->assertStringContainsString('Biaya: —', $html, 'biaya hidup di baris pendapatan, bukan di baris kas');
        $this->assertStringContainsString('Profit: —', $html);
        $this->assertStringContainsString('Pendapatan diakui', $html, 'baris kas masuk menunjuk tanggal pengakuan pendapatan');
    }
}
