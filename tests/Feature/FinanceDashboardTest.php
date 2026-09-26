<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceDashboard;
use App\Filament\Resources\FinanceJournalResource;
use App\Filament\Widgets\FinanceBreakdownChart;
use App\Filament\Widgets\FinanceOverviewStats;
use App\Filament\Widgets\FinanceTrendChart;
use App\Filament\Widgets\RecentFinanceJournalsTable;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => 'AWB-FIN-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ], $attributes));
    }

    private function invoice(Shipment $shipment, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'INV-FIN-'.(Invoice::count() + 1),
            'shipment_id' => $shipment->id,
            'shipping_cost' => 150000,
            'subtotal' => 150000,
            'discount' => 0,
            'tax' => 16500,
            'total' => 166500,
            'status' => 'draft',
        ], $attributes));
    }

    public function test_billing_an_invoice_creates_a_revenue_journal(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);

        $this->assertSame(0, FinanceJournal::count(), 'invoice draft tidak menghasilkan jurnal');

        app(InvoiceService::class)->markBilled($invoice);

        $journal = FinanceJournal::sole();

        $this->assertSame(FinanceJournal::TYPE_REVENUE, $journal->journal_type);
        $this->assertSame($invoice->invoice_number, $journal->reference_label);
        $this->assertSame($shipment->id, $journal->shipment_id);
        $this->assertSame(166500.0, (float) $journal->income, 'pendapatan memakai total invoice termasuk pajak');
        $this->assertSame(16500.0, (float) $journal->tax);
        $this->assertSame(0.0, (float) $journal->cost_of_goods, 'modal diisi operator nanti');
        $this->assertSame(16500.0, (float) $journal->total_expense, 'pajak masuk total biaya');
        $this->assertSame(150000.0, (float) $journal->profit, 'profit bersih pajak');
        $this->assertSame(90.09, (float) $journal->profit_percentage);
    }

    public function test_paying_an_invoice_adds_a_cash_journal_without_touching_the_revenue_journal(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        $service = app(InvoiceService::class);

        $service->markBilled($invoice);
        $revenue = FinanceJournal::where('journal_type', FinanceJournal::TYPE_REVENUE)->sole();

        $revenue->update([
            'cost_of_goods' => 100000,
            'operational_cost' => 5000,
            'notes' => 'Modal dari supplier X',
        ]);

        $service->markPaid($invoice->refresh());

        $this->assertSame(2, FinanceJournal::count(), 'invoice lunas punya jurnal pendapatan dan kas');

        $receipt = FinanceJournal::where('journal_type', FinanceJournal::TYPE_RECEIPT)->sole();

        $this->assertSame($invoice->invoice_number, $receipt->reference_label);
        $this->assertSame(166500.0, (float) $receipt->income, 'kas masuk sebesar tagihan');
        $this->assertSame(0.0, (float) $receipt->tax, 'pajak hanya dibukukan sekali di jurnal pendapatan');
        $this->assertSame(0.0, (float) $receipt->cost_of_goods, 'modal tidak diduplikasi di jurnal kas');
        $this->assertSame(0.0, (float) $receipt->operational_cost);

        $revenue->refresh();

        $this->assertSame(166500.0, (float) $revenue->income);
        $this->assertSame(100000.0, (float) $revenue->cost_of_goods, 'modal operator tidak ditimpa');
        $this->assertSame(5000.0, (float) $revenue->operational_cost);
        $this->assertSame('Modal dari supplier X', $revenue->notes);
    }

    public function test_re_billing_updates_the_revenue_journal_without_touching_manual_costs(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        $service = app(InvoiceService::class);

        $service->markBilled($invoice);

        $journal = FinanceJournal::sole();
        $journal->update([
            'cost_of_goods' => 100000,
            'operational_cost' => 5000,
            'notes' => 'Modal dari supplier X',
        ]);

        $service->markBilled($invoice->refresh());
        $invoice->update(['total' => 200000]);
        $service->markBilled($invoice->refresh());

        $this->assertSame(1, FinanceJournal::count(), 'tidak boleh membuat jurnal ganda');

        $journal->refresh();

        $this->assertSame(200000.0, (float) $journal->income, 'pendapatan mengikuti invoice terbaru');
        $this->assertSame(100000.0, (float) $journal->cost_of_goods, 'modal operator tidak ditimpa');
        $this->assertSame(5000.0, (float) $journal->operational_cost, 'opex operator tidak ditimpa');
        $this->assertSame('Modal dari supplier X', $journal->notes);
        $this->assertSame(121500.0, (float) $journal->total_expense);
        $this->assertSame(78500.0, (float) $journal->profit);
    }

    public function test_stepping_back_through_the_statuses_removes_the_journals(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        $service = app(InvoiceService::class);

        $service->markBilled($invoice);
        $service->markPaid($invoice->refresh());
        $this->assertSame(2, FinanceJournal::count());

        // Penurunan status hanya boleh lewat transisi satu langkah, dan
        // transisi itulah yang harus membersihkan jurnal.
        $service->transitionTo($invoice->fresh(), Invoice::STATUS_TERTAGIH);
        $this->assertSame(1, FinanceJournal::count(), 'kembali ke tertagih menghapus jurnal kas');

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_DRAFT);
        $this->assertSame(0, FinanceJournal::count(), 'kembali ke draft menghapus jurnal pendapatan');
    }

    public function test_cash_journal_is_not_counted_as_revenue(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        $service = app(InvoiceService::class);

        $service->markBilled($invoice);
        $service->markPaid($invoice->refresh());

        $html = $this->financeStatsHtml(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        // Invoice hanya berlabel 166.500; kalau jurnal kas ikut dijumlahkan
        // sebagai pendapatan, angkanya jadi 333.000.
        $this->assertStringContainsString('166.500', $html, 'pendapatan tidak boleh dobel dengan kas masuk');
        $this->assertStringNotContainsString('333.000', $html, 'jurnal kas masuk bukan pendapatan');
    }

    public function test_outstanding_receivable_ignores_the_period_filter(): void
    {
        $tertagih = $this->invoice($this->shipment(), [
            'status' => Invoice::STATUS_TERTAGIH,
            'total' => 400000,
        ]);
        $lunas = $this->invoice($this->shipment(), [
            'status' => Invoice::STATUS_LUNAS,
            'total' => 900000,
        ]);
        $this->invoice($this->shipment(), ['total' => 700000]);

        $html = $this->financeStatsHtml(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        $this->assertStringContainsString('400.000', $html, 'piutang = invoice tertagih yang belum dibayar');
        $this->assertStringNotContainsString('900.000', $html, 'invoice lunas bukan piutang');
        $this->assertStringNotContainsString('700.000', $html, 'invoice draft bukan piutang');
        $this->assertSame(Invoice::STATUS_TERTAGIH, $tertagih->status);
        $this->assertSame(Invoice::STATUS_LUNAS, $lunas->status);
    }

    public function test_finance_widgets_only_count_the_selected_period(): void
    {
        $this->journalAt(now()->startOfMonth(), 100000);
        $this->journalAt(now()->startOfMonth()->addDays(2), 200000);
        $this->journalAt(now()->subMonths(2)->startOfMonth(), 900000);

        $thisMonth = $this->financeStatsHtml(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        $this->assertStringContainsString('300.000', $thisMonth, 'hanya dua jurnal bulan ini yang dijumlahkan');
        $this->assertStringNotContainsString('900.000', $thisMonth, 'jurnal dua bulan lalu tidak ikut');

        $thisYear = $this->financeStatsHtml(
            now()->startOfYear()->toDateString(),
            now()->endOfYear()->toDateString(),
        );

        $this->assertStringContainsString('1.200.000', $thisYear, 'seluruh jurnal tahun ini dijumlahkan');
    }

    public function test_finance_dashboard_defaults_to_the_current_month(): void
    {
        $this->journalAt(now()->startOfMonth(), 100000);
        $this->journalAt(now()->subMonths(2)->startOfMonth(), 900000);

        Livewire::actingAs(User::factory()->create())
            ->test(FinanceDashboard::class)
            ->assertOk()
            ->assertSet('period', 'this_month')
            ->assertSet('from', now()->startOfMonth()->toDateString())
            ->assertSet('to', now()->endOfMonth()->toDateString());
    }

    public function test_choosing_a_preset_fills_the_dates_and_broadcasts_the_period(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(FinanceDashboard::class)
            ->assertOk()
            ->set('filter.period', 'this_year')
            ->assertSet('from', now()->startOfYear()->toDateString())
            ->assertSet('to', now()->endOfYear()->toDateString())
            ->assertDispatched('finance-period-changed');

        Livewire::actingAs(User::factory()->create())
            ->test(FinanceDashboard::class)
            ->assertOk()
            ->set('filter.period', 'last_month')
            ->assertSet('from', now()->subMonthNoOverflow()->startOfMonth()->toDateString())
            ->assertSet('to', now()->subMonthNoOverflow()->endOfMonth()->toDateString());
    }

    public function test_typing_a_date_switches_the_period_to_custom(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(FinanceDashboard::class)
            ->assertOk()
            ->set('filter.from', now()->subMonthNoOverflow()->startOfMonth()->toDateString())
            ->assertSet('period', 'custom')
            ->assertSet('from', now()->subMonthNoOverflow()->startOfMonth()->toDateString());
    }

    public function test_period_is_restored_from_the_query_string(): void
    {
        $from = now()->startOfYear()->toDateString();
        $to = now()->endOfYear()->toDateString();

        Livewire::actingAs(User::factory()->create())
            ->withQueryParams(['period' => 'custom', 'from' => $from, 'to' => $to])
            ->test(FinanceDashboard::class)
            ->assertOk()
            ->assertSet('period', 'custom')
            ->assertSet('from', $from)
            ->assertSet('to', $to);
    }

    public function test_widgets_receive_the_period_from_the_page(): void
    {
        $this->journalAt(now()->startOfMonth(), 100000);
        $this->journalAt(now()->subMonths(2)->startOfMonth(), 900000);

        $widget = Livewire::actingAs(User::factory()->create())
            ->test(FinanceOverviewStats::class)
            ->assertOk()
            ->dispatch('finance-period-changed', from: now()->subMonths(2)->startOfMonth()->toDateString(), to: now()->subMonths(2)->endOfMonth()->toDateString());

        $this->assertStringContainsString('900.000', $widget->html(), 'pendapatan bulan lalu masuk setelah event');
    }

    public function test_finance_widgets_render(): void
    {
        $this->journalAt(now(), 100000);

        foreach ([
            FinanceOverviewStats::class,
            FinanceTrendChart::class,
            FinanceBreakdownChart::class,
            RecentFinanceJournalsTable::class,
        ] as $widget) {
            Livewire::actingAs(User::factory()->create())
                ->test($widget)
                ->assertOk();
        }
    }

    public function test_finance_dashboard_page_is_reachable(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(FinanceDashboard::getUrl())
            ->assertSuccessful()
            ->assertSee('Periode');
    }

    public function test_finance_widgets_are_not_registered_on_the_shipment_dashboard(): void
    {
        $widgets = Filament::getPanel('atlexpress-admin')->getWidgets();

        $this->assertNotContains(FinanceOverviewStats::class, $widgets);
        $this->assertNotContains(FinanceTrendChart::class, $widgets);
        $this->assertNotContains(FinanceBreakdownChart::class, $widgets);
        $this->assertNotContains(RecentFinanceJournalsTable::class, $widgets);
    }

    public function test_journal_from_a_billed_invoice_cannot_be_deleted(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        app(InvoiceService::class)->markBilled($invoice);

        $journal = FinanceJournal::sole();

        $this->assertTrue($journal->isLinkedToBilledInvoice());

        $standalone = FinanceJournal::create(['entry_date' => now()->toDateString(), 'income' => 50000]);

        $this->assertFalse($standalone->isLinkedToBilledInvoice());
    }

    public function test_journal_resource_pages_render(): void
    {
        $shipment = $this->shipment();
        $invoice = $this->invoice($shipment);
        app(InvoiceService::class)->markBilled($invoice);

        $this->journalAt(now(), 75000);

        $this->actingAs(User::factory()->create());

        $this->get(FinanceJournalResource::getUrl('index'))->assertSuccessful();
        $this->get(FinanceJournalResource::getUrl('create'))->assertSuccessful();
        $this->get(FinanceJournalResource::getUrl('edit', ['record' => FinanceJournal::first()]))
            ->assertSuccessful();
    }

    public function test_journal_derives_expense_profit_and_percentage_on_save(): void
    {
        $journal = FinanceJournal::create([
            'entry_date' => now()->toDateString(),
            'income' => 500000,
            'cost_of_goods' => 300000,
            'operational_cost' => 20000,
            'tax' => 50000,
        ]);

        $this->assertSame(370000.0, (float) $journal->total_expense);
        $this->assertSame(130000.0, (float) $journal->profit);
        $this->assertSame(26.0, (float) $journal->profit_percentage);
    }

    private function journalAt(\DateTimeInterface $date, float $income): FinanceJournal
    {
        return FinanceJournal::create([
            'entry_date' => $date->format('Y-m-d'),
            'reference_label' => 'REF-'.FinanceJournal::count(),
            'income' => $income,
            'cost_of_goods' => $income * 0.5,
            'tax' => 0,
        ]);
    }

    /**
     * Widget statistik dengan periode yang dikirim lewat event, supaya angka
     * yang dihitung trait terverifikasi lewat markup yang benar-benar dirender.
     */
    private function financeStatsHtml(string $from, string $to): string
    {
        return Livewire::actingAs(User::factory()->create())
            ->test(FinanceOverviewStats::class)
            ->assertOk()
            ->dispatch('finance-period-changed', from: $from, to: $to)
            ->html();
    }
}
