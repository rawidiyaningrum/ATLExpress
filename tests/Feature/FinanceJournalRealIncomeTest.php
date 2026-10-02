<?php

namespace Tests\Feature;

use App\Filament\Resources\FinanceJournalResource;
use App\Filament\Resources\FinanceJournalResource\Pages\EditFinanceJournal;
use App\Filament\Widgets\FinanceOverviewStats;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceJournalRealIncomeTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(): Shipment
    {
        return Shipment::create([
            'awb_number' => 'AWB-KAS-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);
    }

    /**
     * Invoice yang sudah ditagihkan lalu dilunasi, beserta jurnal kas masuknya.
     *
     * Lunas harus lewat tertagih dulu, sama seperti alur aslinya, karena
     * pendapatan dan kas masuk dicatat pada dua titik yang berbeda.
     */
    private function paidInvoice(float $total = 199800): array
    {
        $shipment = $this->shipment();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-KAS-'.(Invoice::count() + 1),
            'shipment_id' => $shipment->id,
            'billed_to_name' => 'PT penerima',
            'shipping_cost' => 150000,
            'subtotal' => 180000,
            'discount' => 0,
            'tax' => 19800,
            'total' => $total,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $service = app(InvoiceService::class);
        $service->markBilled($invoice);
        $service->markPaid($invoice->refresh());

        $receipt = FinanceJournal::query()
            ->where('reference_label', $invoice->invoice_number)
            ->where('journal_type', FinanceJournal::TYPE_RECEIPT)
            ->sole();

        return [$invoice->refresh(), $receipt];
    }

    public function test_paying_an_invoice_starts_the_cash_journal_assuming_full_payment(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->assertSame(199800.0, (float) $receipt->real_income, 'kas baru diasumsikan masuk utuh');
        $this->assertTrue($receipt->realIncomeMatchesInvoice());
    }

    public function test_manual_cash_journal_without_invoice_has_no_invoice_total(): void
    {
        $manual = FinanceJournal::create([
            'journal_type' => FinanceJournal::TYPE_RECEIPT,
            'entry_date' => now()->toDateString(),
            'income' => 250000,
            'real_income' => 250000,
        ]);

        $this->assertNull($manual->invoiceTotal(), 'jurnal kas manual tidak punya invoice pembanding');
        $this->assertFalse($manual->realIncomeMatchesInvoice(), 'tanpa tagihan tidak ada yang bisa dicentang');
        $this->assertSame(
            'Tidak ada invoice terkait',
            FinanceJournalResource::invoiceTotalLabel($manual),
        );
    }

    public function test_cash_journal_without_invoice_hides_the_same_as_invoice_checkbox(): void
    {
        $manual = FinanceJournal::create([
            'journal_type' => FinanceJournal::TYPE_RECEIPT,
            'entry_date' => now()->toDateString(),
            'income' => 250000,
            'real_income' => 250000,
        ]);

        // Nominal riilnya tetap boleh diisi manual, tapi tidak ada tagihan yang
        // bisa dibandingkan jadi centang otomatis tidak berguna.
        $this->formFor($manual)
            ->assertFormFieldIsVisible('real_income')
            ->assertFormFieldIsHidden(FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH);
    }

    public function test_value_panel_is_only_rendered_for_cash_journals(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->formFor($receipt)
            ->assertFormFieldIsVisible('real_income')
            ->set('data.journal_type', FinanceJournal::TYPE_REVENUE)
            ->assertFormFieldIsHidden('real_income');

        $revenue = FinanceJournal::query()
            ->where('journal_type', FinanceJournal::TYPE_REVENUE)
            ->sole();

        $this->formFor($revenue)
            ->assertFormFieldIsHidden('real_income');
    }

    public function test_invoice_total_placeholder_shows_the_linked_invoice_total(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->assertSame('Rp 199.800', FinanceJournalResource::invoiceTotalLabel($receipt));
    }

    public function test_ticking_same_as_invoice_fills_the_real_amount_from_the_invoice(): void
    {
        [, $receipt] = $this->paidInvoice();
        $receipt->update(['real_income' => null]);

        $component = $this->formFor($receipt);

        $this->assertFalse($component->get('data')[FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH]);

        $component->set('data.'.FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH, true);

        $this->assertSame(199800.0, (float) $component->get('data')['real_income']);

        $component->set('data.'.FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH, false);

        $this->assertNull($component->get('data')['real_income'], 'melepas centang mengosongkan field');
    }

    public function test_same_as_invoice_is_derived_from_the_stored_amount(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->assertTrue(
            $this->formForWithRealIncome($receipt, 199800),
            'nominal riil sama dengan tagihan, jadi checkbox aktif',
        );

        $this->assertFalse(
            $this->formForWithRealIncome($receipt, 199000),
            'ada potongan biaya transfer, jadi checkbox lepas',
        );

        $this->assertFalse(
            $this->formForWithRealIncome($receipt, null),
            'belum ada nominal riil, jadi tidak ada yang dicentang',
        );
    }

    public function test_saving_stores_the_real_amount_without_touching_the_invoice_total(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->formFor($receipt)
            ->fillForm(['real_income' => 199000])
            ->call('save')
            ->assertHasNoFormErrors();

        $receipt->refresh();

        $this->assertSame(199000.0, (float) $receipt->real_income, 'kas riil tersimpan');
        $this->assertSame(199800.0, (float) $receipt->income, 'kolom tagihan tidak berubah');
    }

    public function test_saving_a_cash_journal_never_collects_a_cost_of_goods(): void
    {
        [, $receipt] = $this->paidInvoice();

        $this->formFor($receipt)
            ->fillForm(['real_income' => 199000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0.0, (float) $receipt->refresh()->cost_of_goods);
    }

    public function test_the_real_amount_survives_paying_the_invoice_again(): void
    {
        [$invoice, $receipt] = $this->paidInvoice();

        $receipt->update(['real_income' => 199000]);

        app(InvoiceService::class)->markPaid($invoice);

        $this->assertSame(199000.0, (float) $receipt->refresh()->real_income, 'input operator tidak ditimpa');
    }

    public function test_dashboard_cash_in_follows_the_real_amount(): void
    {
        [, $receipt] = $this->paidInvoice();

        $receipt->update(['real_income' => 150000]);

        $html = $this->financeStatsHtml();

        $this->assertStringContainsString('150.000', $html, 'kartu kas masuk memakai nominal riil');
    }

    public function test_dashboard_cash_in_falls_back_to_the_invoice_total(): void
    {
        [, $receipt] = $this->paidInvoice();

        $receipt->update(['real_income' => null]);

        $html = $this->financeStatsHtml();

        // Operator belum sempat mengisi formnya, jadi kartu kas masuk tetap
        // menampilkan tagihan daripada turun ke nol.
        $this->assertStringContainsString('199.800', $html, 'nominal yang belum dicatat memakai total invoice');
    }

    public function test_table_column_explains_the_difference_from_the_invoice(): void
    {
        [, $receipt] = $this->paidInvoice();

        $receipt->update(['real_income' => 199800]);
        $this->assertSame(
            'Sama dengan nominal invoice.',
            FinanceJournalResource::realIncomeDescription($receipt),
        );

        $receipt->update(['real_income' => 198000]);
        $this->assertSame(
            'Selisih Rp 1.800 dari nominal invoice.',
            FinanceJournalResource::realIncomeDescription($receipt),
        );

        $receipt->update(['real_income' => null]);
        $this->assertSame(
            'Kas riil belum dicatat operator.',
            FinanceJournalResource::realIncomeDescription($receipt),
        );
    }

    private function formFor(FinanceJournal $journal): Testable
    {
        return Livewire::actingAs(User::factory()->create())
            ->test(EditFinanceJournal::class, ['record' => $journal->getKey()]);
    }

    /**
     * Status centang pada form jurnal kas setelah nominal riilnya diganti.
     */
    private function formForWithRealIncome(FinanceJournal $journal, ?float $realIncome): bool
    {
        $journal->update(['real_income' => $realIncome]);

        return (bool) $this->formFor($journal)
            ->get('data')[FinanceJournalResource::SAME_AS_INVOICE_STATE_PATH];
    }

    private function financeStatsHtml(): string
    {
        return Livewire::actingAs(User::factory()->create())
            ->test(FinanceOverviewStats::class)
            ->assertOk()
            ->dispatch('finance-period-changed', from: now()->startOfMonth()->toDateString(), to: now()->endOfMonth()->toDateString())
            ->html();
    }
}
