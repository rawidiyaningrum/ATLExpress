<?php

namespace Tests\Feature;

use App\Filament\Resources\FinanceJournalResource;
use App\Filament\Resources\FinanceJournalResource\Pages\EditFinanceJournal;
use App\Filament\Resources\InvoiceResource;
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

class FinanceJournalRealExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => 'AWB-EXP-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ], $attributes));
    }

    /**
     * Invoice berstatus lunas dengan satu ongkir, satu biaya tambahan, satu
     * diskon, dan satu pajak, lalu jurnal pendapatan yang sudah terbentuk.
     */
    private function billedInvoice(): array
    {
        $shipment = $this->shipment();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-EXP-'.(Invoice::count() + 1),
            'shipment_id' => $shipment->id,
            'billed_to_name' => 'PT penerima',
            'shipping_cost' => 150000,
            'subtotal' => 190000,
            'discount' => 10000,
            'tax' => 19800,
            'total' => 199800,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $additional = $invoice->items()->create([
            'description' => 'Packing kayu',
            'type' => 'additional',
            'basis' => 'final_tariff',
            'quantity' => 1,
            'unit_price' => 40000,
            'line_total' => 40000,
        ]);

        $discount = $invoice->items()->create([
            'description' => 'Diskon pelanggan',
            'type' => 'discount',
            'basis' => 'final_tariff',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        $tax = $invoice->items()->create([
            'description' => 'PPN 11%',
            'type' => 'tax',
            'basis' => 'final_tariff',
            'quantity' => 1,
            'unit_price' => 19800,
            'line_total' => 19800,
        ]);

        app(InvoiceService::class)->markBilled($invoice);

        return [
            $shipment,
            $invoice->refresh(),
            FinanceJournal::where('journal_type', FinanceJournal::TYPE_REVENUE)->sole(),
            ['additional' => $additional, 'discount' => $discount, 'tax' => $tax],
        ];
    }

    public function test_expense_rows_are_prefilled_from_the_linked_invoice_items(): void
    {
        [, $invoice] = $this->billedInvoice();

        $rows = FinanceJournalResource::rowsFromInvoice($invoice);

        $this->assertCount(4, $rows, 'ongkos kirim plus tiga item invoice');
        $this->assertSame('Ongkos kirim', $rows[0]['description'], 'baris pertama adalah ongkir kirim');
        $this->assertSame(150000.0, $rows[0]['line_amount'], 'modal baris ongkir mengikuti shipping_cost');
        $this->assertNull($rows[0]['invoice_item_id'], 'ongkos kirim tidak punya baris invoice_items');

        $this->assertSame('Packing kayu', $rows[1]['description']);
        $this->assertSame(40000.0, $rows[1]['line_amount']);
        $this->assertSame(FinanceJournalResource::EXPENSE_SOURCE_ADDITIONAL, $rows[1]['source']);

        $this->assertSame(FinanceJournalResource::EXPENSE_SOURCE_READ_ONLY, $rows[2]['source'], 'diskon tidak bisa diisi');
        $this->assertSame(FinanceJournalResource::EXPENSE_SOURCE_READ_ONLY, $rows[3]['source'], 'pajak tidak bisa diisi');
    }

    public function test_only_shipping_and_additional_rows_are_editable(): void
    {
        $this->assertTrue(FinanceJournalResource::rowIsEditable(FinanceJournalResource::EXPENSE_SOURCE_SHIPPING));
        $this->assertTrue(FinanceJournalResource::rowIsEditable(FinanceJournalResource::EXPENSE_SOURCE_ADDITIONAL));
        $this->assertFalse(FinanceJournalResource::rowIsEditable(FinanceJournalResource::EXPENSE_SOURCE_READ_ONLY));
    }

    public function test_saving_the_journal_writes_real_expense_and_sums_it_into_modal(): void
    {
        [, $invoice, $journal] = $this->billedInvoice();

        $this->withRealExpenses($this->journalForm($journal), 140000, 45000)
            ->call('save')
            ->assertHasNoFormErrors();

        $invoice->refresh();
        $journal->refresh();

        $this->assertSame(140000.0, (float) $invoice->shipping_real_expense, 'ongkos tersimpan di invoices');
        $this->assertSame(45000.0, (float) $invoice->items()->where('description', 'Packing kayu')->sole()->real_expense);
        $this->assertSame(185000.0, (float) $journal->cost_of_goods, 'modal jurnal adalah jumlah pengeluaran real');
        $this->assertSame(185000.0, $invoice->realExpenseTotal());
    }

    public function test_discount_and_tax_rows_never_reach_the_invoice_or_modal(): void
    {
        [, $invoice, $journal] = $this->billedInvoice();

        $component = $this->journalForm($journal);

        $readOnly = array_values(array_filter(
            $component->get('data')['expense_items'],
            fn (array $row) => $row['source'] === FinanceJournalResource::EXPENSE_SOURCE_READ_ONLY,
        ));

        $this->assertCount(2, $readOnly, 'diskon dan pajak tetap ditampilkan');
        $this->assertNull($readOnly[0]['real_expense'], 'baris read-only tidak punya pengeluaran real');
        $this->assertNull($readOnly[1]['real_expense'], 'baris read-only tidak punya pengeluaran real');

        $this->withRealExpenses($component, 140000, 45000)
            ->call('save')
            ->assertHasNoFormErrors();

        $invoice->refresh();
        $journal->refresh();

        $this->assertSame(0.0, (float) $invoice->items()->where('description', 'Diskon pelanggan')->sole()->real_expense);
        $this->assertSame(0.0, (float) $invoice->items()->where('description', 'PPN 11%')->sole()->real_expense);
        $this->assertSame(185000.0, (float) $journal->cost_of_goods, 'diskon dan pajak tidak masuk modal');
    }

    public function test_profit_and_percentage_follow_the_real_expense(): void
    {
        [, , $journal] = $this->billedInvoice();

        $this->withRealExpenses($this->journalForm($journal), 140000, 45000)
            ->call('save')
            ->assertHasNoFormErrors();

        $journal->refresh();

        // 199800 - (185000 modal + 0 opex + 19800 pajak) = -5000
        $this->assertSame(204800.0, (float) $journal->total_expense);
        $this->assertSame(-5000.0, (float) $journal->profit, 'pengeluaran real lebih besar dari pendapatan');
        $this->assertSame(-2.5, (float) $journal->profit_percentage);
    }

    public function test_same_as_modal_is_derived_from_the_stored_amount(): void
    {
        [, $invoice] = $this->billedInvoice();

        $invoice->update(['shipping_real_expense' => 150000]);
        $invoice->items()->where('description', 'Packing kayu')->update(['real_expense' => 40000]);
        $invoice->items()->where('description', 'Packing kayu')->update(['real_expense' => 35000]);

        $rows = FinanceJournalResource::rowsFromInvoice($invoice->refresh());

        $this->assertTrue($rows[0]['same_as_modal'], '150000 sama dengan modal ongkir, jadi checkbox aktif');
        $this->assertFalse($rows[1]['same_as_modal'], '35000 tidak sama dengan 40000, jadi checkbox lepas');
        $this->assertSame(35000.0, (float) $rows[1]['real_expense']);
    }

    public function test_filling_every_row_as_modal_skips_read_only_rows(): void
    {
        [, $invoice] = $this->billedInvoice();

        $filled = FinanceJournalResource::fillEveryRowAsModal(
            FinanceJournalResource::rowsFromInvoice($invoice),
        );

        $this->assertTrue($filled[0]['same_as_modal']);
        $this->assertSame(150000.0, $filled[0]['real_expense']);
        $this->assertTrue($filled[1]['same_as_modal']);
        $this->assertSame(40000.0, $filled[1]['real_expense']);
        $this->assertNull($filled[2]['real_expense'], 'baris diskon tidak ikut diisi');
        $this->assertNull($filled[3]['real_expense'], 'baris pajak tidak ikut diisi');
    }

    public function test_unrecorded_expenses_start_blank_instead_of_zero(): void
    {
        [, $invoice] = $this->billedInvoice();

        $rows = FinanceJournalResource::rowsFromInvoice($invoice);

        // Kolomnya default nol di database, tapi kalau form menampilkan nol
        // operator bisa menyimpan jurnal dengan modal nol tanpa sadar belum
        // mengisi apa pun, dan nol itu tidak bisa dibedakan dari biaya nol.
        $this->assertNull($rows[0]['real_expense'], 'ongkos yang belum dicatat tampil kosong');
        $this->assertNull($rows[1]['real_expense'], 'biaya tambahan yang belum dicatat tampil kosong');
        $this->assertFalse($rows[0]['same_as_modal']);
        $this->assertFalse($rows[1]['same_as_modal']);
    }

    public function test_ticking_same_as_modal_fills_and_unticking_clears_the_amount(): void
    {
        [, , $journal] = $this->billedInvoice();

        $component = $this->journalForm($journal);

        $row = $this->rowKey(
            $component->get('data')['expense_items'],
            FinanceJournalResource::EXPENSE_SOURCE_SHIPPING,
        );

        $component->set("data.expense_items.{$row}.same_as_modal", true);
        $this->assertSame(150000.0, (float) $component->get('data')['expense_items'][$row]['real_expense']);

        $component->set("data.expense_items.{$row}.same_as_modal", false);
        $this->assertNull($component->get('data')['expense_items'][$row]['real_expense'], 'melepas centang mengosongkan field');
    }

    public function test_fill_every_row_action_replaces_manual_amounts(): void
    {
        [, $invoice, $journal] = $this->billedInvoice();

        $component = $this->journalForm($journal);

        $rows = $component->get('data')['expense_items'];
        $shipping = $this->rowKey($rows, FinanceJournalResource::EXPENSE_SOURCE_SHIPPING);
        $additional = $this->rowKey($rows, FinanceJournalResource::EXPENSE_SOURCE_ADDITIONAL);

        $component->set("data.expense_items.{$shipping}.real_expense", 140000);

        // Dipanggil lewat method Livewire yang sama dengan yang dipakai tombol,
        // bukan helper testing, supaya tombol yang benar-benar dirender ikut
        // teruji. Section tanpa key() tidak punya aksi yang bisa dipasang.
        $component->call('mountFormComponentAction', 'pengeluaran-real-item-invoice', 'isiSemuaSamaDenganModal');

        $filled = $component->get('data')['expense_items'];

        $this->assertTrue($filled[$shipping]['same_as_modal']);
        $this->assertSame(150000.0, (float) $filled[$shipping]['real_expense'], 'aksi menimpa nominal manual');
        $this->assertTrue($filled[$additional]['same_as_modal']);
        $this->assertSame(40000.0, (float) $filled[$additional]['real_expense']);

        $component->call('save')->assertHasNoFormErrors();

        $invoice->refresh();

        $this->assertSame(150000.0, (float) $invoice->shipping_real_expense);
        $this->assertSame(40000.0, (float) $invoice->items()->where('description', 'Packing kayu')->sole()->real_expense);
        $this->assertSame(190000.0, (float) $journal->refresh()->cost_of_goods);
    }

    public function test_editable_rows_must_be_filled_before_saving(): void
    {
        [, , $journal] = $this->billedInvoice();

        $component = $this->journalForm($journal);

        $shipping = $this->rowKey(
            $component->get('data')['expense_items'],
            FinanceJournalResource::EXPENSE_SOURCE_SHIPPING,
        );
        $additional = $this->rowKey(
            $component->get('data')['expense_items'],
            FinanceJournalResource::EXPENSE_SOURCE_ADDITIONAL,
        );

        $component
            ->call('save')
            ->assertHasFormErrors([
                "expense_items.{$shipping}.real_expense",
                "expense_items.{$additional}.real_expense",
            ]);

        $this->assertSame(0.0, (float) $journal->refresh()->cost_of_goods, 'tidak ada yang tersimpan');
    }

    public function test_real_expense_section_is_hidden_for_receipt_journals(): void
    {
        [$shipment, $invoice] = $this->billedInvoice();

        $receipt = FinanceJournal::create([
            'shipment_id' => $shipment->id,
            'reference_label' => $invoice->invoice_number,
            'journal_type' => FinanceJournal::TYPE_RECEIPT,
            'entry_date' => now()->toDateString(),
            'income' => 199800,
        ]);

        $this->assertFalse(
            FinanceJournalResource::journalHasLinkedInvoice($receipt),
            'jurnal kas masuk hanya mencatat penerimaan kas',
        );
    }

    public function test_real_expense_section_is_hidden_for_manual_journals(): void
    {
        $standalone = FinanceJournal::create([
            'entry_date' => now()->toDateString(),
            'income' => 500000,
            'cost_of_goods' => 300000,
        ]);

        $this->assertFalse(
            FinanceJournalResource::journalHasLinkedInvoice($standalone),
            'jurnal manual tanpa invoice tidak punya baris pengeluaran real',
        );
    }

    public function test_re_billing_survives_the_removed_value_panel(): void
    {
        [, , $journal] = $this->billedInvoice();

        $journal->update(['cost_of_goods' => 100000, 'operational_cost' => 25000]);

        $this->withRealExpenses($this->journalForm($journal), 140000, 45000)
            ->call('save')
            ->assertHasNoFormErrors();

        $journal->refresh();

        // Panel nilai sudah tidak lagi memuat modal, opex, dan catatan, jadi
        // formnya tidak boleh menimpa nilai lama itu dengan kosong.
        $this->assertSame(185000.0, (float) $journal->cost_of_goods, 'modal mengikuti pengeluaran real');
        $this->assertSame(25000.0, (float) $journal->operational_cost, 'opex lama tidak dikosongkan form');
    }

    public function test_real_expense_survives_re_billing_the_invoice(): void
    {
        [, $invoice, $journal] = $this->billedInvoice();

        $invoice->update(['shipping_real_expense' => 140000]);
        $invoice->items()->where('description', 'Packing kayu')->update(['real_expense' => 45000]);

        $service = app(InvoiceService::class);

        // Kembali ke draft menghapus jurnal pendapatan beserta modalnya, lalu
        // ditagihkan ulang harus memuat kembali pengeluaran real dari invoice.
        $service->transitionTo($invoice->refresh(), Invoice::STATUS_DRAFT);
        $service->transitionTo($invoice->refresh(), Invoice::STATUS_TERTAGIH);

        $revenue = FinanceJournal::where('journal_type', FinanceJournal::TYPE_REVENUE)->sole();

        $this->assertSame(140000.0, (float) $invoice->refresh()->shipping_real_expense, 'ongkos real tidak hilang');
        $this->assertSame(185000.0, (float) $revenue->cost_of_goods, 'modal terisi ulang dari pengeluaran real');
    }

    public function test_re_billing_without_real_expense_keeps_the_handwritten_modal(): void
    {
        [, $invoice] = $this->billedInvoice();

        $journal = FinanceJournal::where('journal_type', FinanceJournal::TYPE_REVENUE)->sole();
        $journal->update(['cost_of_goods' => 100000, 'notes' => 'Modal dari supplier X']);

        app(InvoiceService::class)->transitionTo($invoice->refresh(), Invoice::STATUS_TERTAGIH);

        $journal->refresh();

        $this->assertSame(100000.0, (float) $journal->cost_of_goods, 'modal operator tidak ditimpa nol');
        $this->assertSame('Modal dari supplier X', $journal->notes);
    }

    public function test_invoice_real_expense_total_ignores_discount_and_tax_rows(): void
    {
        [, $invoice] = $this->billedInvoice();

        $invoice->update(['shipping_real_expense' => 140000]);
        $invoice->items()->update(['real_expense' => 45000]);

        $this->assertSame(185000.0, $invoice->realExpenseTotal(), 'diskon dan pajak tidak ikut modal');
    }

    public function test_dashboard_reports_the_real_expense_and_cost_ratio(): void
    {
        [, $invoice, $journal] = $this->billedInvoice();

        $invoice->update(['shipping_real_expense' => 140000]);
        $invoice->items()->where('description', 'Packing kayu')->update(['real_expense' => 45000]);
        $journal->update(['cost_of_goods' => 185000, 'operational_cost' => 0]);

        $html = Livewire::actingAs(User::factory()->create())
            ->test(FinanceOverviewStats::class)
            ->assertOk()
            ->dispatch('finance-period-changed', from: now()->startOfMonth()->toDateString(), to: now()->endOfMonth()->toDateString())
            ->html();

        $this->assertStringContainsString('Pengeluaran Real', $html, 'kartu pengeluaran real tampil di dashboard');
        $this->assertStringContainsString('Efisiensi Biaya', $html, 'kartu efisiensi biaya tampil di dashboard');
        $this->assertStringContainsString('185.000', $html, 'total pengeluaran real ikut dijumlahkan');
    }

    public function test_invoice_detail_page_links_to_the_journal_form(): void
    {
        [, $invoice] = $this->billedInvoice();

        $this->actingAs(User::factory()->create())
            ->get(InvoiceResource::getUrl('view', ['record' => $invoice]))
            ->assertSuccessful()
            ->assertSee('Catat Pengeluaran Real');
    }

    /**
     * Kunci baris repeater yang dirender Filament adalah UUID, bukan indeks,
     * jadi baris dicari lewat sumbernya.
     *
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function rowKey(array $rows, string $source): string
    {
        foreach ($rows as $key => $row) {
            if ($row['source'] === $source) {
                return $key;
            }
        }

        $this->fail("tidak ada baris dengan sumber [{$source}]");
    }

    private function journalForm(FinanceJournal $journal): Testable
    {
        return Livewire::actingAs(User::factory()->create())
            ->test(EditFinanceJournal::class, ['record' => $journal->getKey()]);
    }

    /**
     * Baris repeater diisi lewat state yang sedang dirender, bukan lewat
     * fillForm. fillForm menyatu state baru ke state yang sudah ada, jadi
     * barisnya tergandakan dan urutan penulisan menentukan hasil.
     */
    private function withRealExpenses(Testable $component, float $shipping, float $additional): Testable
    {
        $rows = $component->get('data')['expense_items'];

        $component->set(
            'data.expense_items.'.$this->rowKey($rows, FinanceJournalResource::EXPENSE_SOURCE_SHIPPING).'.real_expense',
            $shipping,
        );
        $component->set(
            'data.expense_items.'.$this->rowKey($rows, FinanceJournalResource::EXPENSE_SOURCE_ADDITIONAL).'.real_expense',
            $additional,
        );

        return $component;
    }
}
