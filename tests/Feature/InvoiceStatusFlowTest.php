<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\FinanceJournal;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InvoiceStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(): Shipment
    {
        return Shipment::create([
            'awb_number' => 'AWB-STATUS-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'receiver_name' => 'Budi Santoso',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 5,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);
    }

    private function invoice(array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'INV-STATUS-'.(Invoice::count() + 1),
            'shipment_id' => $this->shipment()->id,
            'billed_to_name' => 'Budi Santoso',
            'shipping_cost' => 150000,
            'subtotal' => 150000,
            'discount' => 0,
            'tax' => 16500,
            'total' => 166500,
            'status' => Invoice::STATUS_DRAFT,
        ], $attributes));
    }

    public function test_a_draft_invoice_can_become_final_and_create_a_revenue_journal(): void
    {
        $invoice = $this->invoice();
        $service = app(InvoiceService::class);

        $service->transitionTo($invoice, Invoice::STATUS_FINAL);

        $this->assertSame(Invoice::STATUS_FINAL, $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->berjalan_date);
        $this->assertNull($invoice->fresh()->lunas_date);
        $this->assertSame(1, FinanceJournal::count());
        $this->assertSame(
            FinanceJournal::TYPE_REVENUE,
            FinanceJournal::sole()->journal_type,
            'status final menghasilkan jurnal pendapatan',
        );
    }

    public function test_a_final_invoice_records_the_date_when_it_starts_running(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);

        $service->markInProgress($invoice->fresh(), '2026-10-11');

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_BERJALAN, $invoice->status);
        $this->assertSame('2026-10-11', $invoice->berjalan_date?->toDateString());
        $this->assertNull($invoice->lunas_date);
        $this->assertSame(1, FinanceJournal::count(), 'status berjalan tidak membuat jurnal kas');
        $this->assertSame(FinanceJournal::TYPE_REVENUE, FinanceJournal::sole()->journal_type);
    }

    public function test_a_running_invoice_records_the_paid_date_and_keeps_the_running_date(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh(), '2026-10-11');

        $service->markPaid($invoice->fresh(), '2026-10-20');

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_LUNAS, $invoice->status);
        $this->assertSame('2026-10-11', $invoice->berjalan_date?->toDateString());
        $this->assertSame('2026-10-20', $invoice->lunas_date?->toDateString());
        $this->assertSame(2, FinanceJournal::count());
        $this->assertSame(
            '2026-10-20',
            FinanceJournal::query()
                ->where('journal_type', FinanceJournal::TYPE_RECEIPT)
                ->sole()
                ->entry_date?->toDateString(),
        );
    }

    public function test_a_paid_invoice_can_only_step_back_to_running_and_clears_the_paid_date(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh(), '2026-10-11');
        $service->markPaid($invoice->fresh(), '2026-10-20');

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_BERJALAN);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_BERJALAN, $invoice->status);
        $this->assertSame('2026-10-11', $invoice->berjalan_date?->toDateString());
        $this->assertNull($invoice->lunas_date);
        $this->assertSame(1, FinanceJournal::count(), 'jurnal kas dihapus saat invoice kembali berjalan');
        $this->assertSame(FinanceJournal::TYPE_REVENUE, FinanceJournal::sole()->journal_type);
    }

    public function test_rolling_back_to_running_can_correct_the_running_date(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh(), '2026-10-11');
        $service->markPaid($invoice->fresh(), '2026-10-20');

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_BERJALAN, '2026-10-15');

        $invoice->refresh();
        $this->assertSame('2026-10-15', $invoice->berjalan_date?->toDateString());
        $this->assertNull($invoice->lunas_date);
    }

    public function test_a_running_invoice_can_step_back_to_final_and_clears_the_running_date(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh(), '2026-10-11');

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_FINAL);

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_FINAL, $invoice->status);
        $this->assertNull($invoice->berjalan_date);
        $this->assertNull($invoice->lunas_date);
        $this->assertSame(1, FinanceJournal::count());
    }

    public function test_a_draft_invoice_cannot_jump_straight_to_paid(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $this->expectException(HttpException::class);

        try {
            $service->transitionTo($invoice, Invoice::STATUS_LUNAS);
        } finally {
            $this->assertSame(Invoice::STATUS_DRAFT, $invoice->fresh()->status);
            $this->assertSame(0, FinanceJournal::count());
        }
    }

    public function test_a_paid_invoice_cannot_skip_the_running_step_when_stepping_back(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh());
        $service->markPaid($invoice->fresh());

        $this->expectException(HttpException::class);

        try {
            $service->transitionTo($invoice->fresh(), Invoice::STATUS_FINAL);
        } finally {
            $this->assertSame(Invoice::STATUS_LUNAS, $invoice->fresh()->status);
            $this->assertSame(2, FinanceJournal::count());
        }
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $this->expectException(HttpException::class);

        $service->transitionTo($invoice, 'unknown');
    }

    public function test_finalizing_twice_does_not_duplicate_the_revenue_journal(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $service->markFinal($invoice);
        $service->markFinal($invoice->fresh());
        $service->markFinal($invoice->fresh());

        $this->assertSame(1, FinanceJournal::count());
    }

    public function test_paying_twice_does_not_duplicate_the_cash_journal(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $service->markFinal($invoice);
        $service->markInProgress($invoice->fresh());
        $service->markPaid($invoice->fresh(), '2026-10-20');
        $service->markPaid($invoice->fresh(), '2026-10-25');

        $this->assertSame(2, FinanceJournal::count());
        $this->assertSame('2026-10-20', $invoice->fresh()->lunas_date?->toDateString());
    }

    public function test_a_final_invoice_cannot_be_silently_saved_back_as_draft(): void
    {
        $service = app(InvoiceService::class);
        $shipment = $this->shipment();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-LOCK-1',
            'shipment_id' => $shipment->id,
            'billed_to_name' => 'Budi Santoso',
            'shipping_cost' => 150000,
            'subtotal' => 150000,
            'discount' => 0,
            'tax' => 16500,
            'total' => 166500,
            'status' => Invoice::STATUS_FINAL,
        ]);

        try {
            $service->saveForShipment($shipment, [
                'billed_to_name' => 'Budi Santoso',
                'shipping_cost' => 150000,
                'items' => [],
            ], Invoice::STATUS_DRAFT);

            $this->fail('invoice final tidak boleh disimpan ulang sebagai draft');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertSame(Invoice::STATUS_FINAL, $invoice->fresh()->status);
    }

    public function test_only_the_next_step_is_offered_in_the_ui(): void
    {
        $draft = $this->invoice();
        $final = $this->invoice(['status' => Invoice::STATUS_FINAL]);
        $berjalan = $this->invoice(['status' => Invoice::STATUS_BERJALAN]);
        $lunas = $this->invoice(['status' => Invoice::STATUS_LUNAS]);

        $this->assertSame([Invoice::STATUS_FINAL], $draft->allowedTransitions());
        $this->assertSame(
            [Invoice::STATUS_BERJALAN, Invoice::STATUS_DRAFT],
            $final->allowedTransitions(),
        );
        $this->assertSame(
            [Invoice::STATUS_LUNAS, Invoice::STATUS_FINAL],
            $berjalan->allowedTransitions(),
        );
        $this->assertSame([Invoice::STATUS_BERJALAN], $lunas->allowedTransitions());

        $this->assertFalse($draft->isLocked());
        $this->assertTrue($final->isLocked());
        $this->assertTrue($berjalan->isLocked());
        $this->assertTrue($lunas->isLocked());
    }

    public function test_the_list_offers_only_the_transition_for_the_current_status(): void
    {
        $draft = $this->invoice();
        $final = $this->invoice(['status' => Invoice::STATUS_FINAL]);
        $berjalan = $this->invoice(['status' => Invoice::STATUS_BERJALAN]);
        $lunas = $this->invoice(['status' => Invoice::STATUS_LUNAS]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionVisible('status:draft:final', $draft)
            // Draft ke lunas adalah lompatan, jadi aksinya tidak pernah
            // didaftarkan sama sekali, bukan sekadar disembunyikan.
            ->assertTableActionDoesNotExist('status:draft:lunas')
            ->assertTableActionVisible('status:final:berjalan', $final)
            ->assertTableActionVisible('status:berjalan:lunas', $berjalan);

        // Aksi kembali hanya berlaku pada status tepat di atasnya.
        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionHidden('status:lunas:berjalan', $final)
            ->assertTableActionVisible('status:lunas:berjalan', $lunas);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionVisible('edit', $draft)
            ->assertTableActionHidden('edit', $lunas);
    }

    public function test_the_list_records_the_date_selected_from_the_status_popup(): void
    {
        $final = $this->invoice(['status' => Invoice::STATUS_FINAL]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->callTableAction('status:final:berjalan', $final, [
                'status_date' => '2026-10-12',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(Invoice::STATUS_BERJALAN, $final->fresh()->status);
        $this->assertSame('2026-10-12', $final->fresh()->berjalan_date?->toDateString());
    }

    public function test_the_detail_page_offers_finalizing_and_records_the_running_date(): void
    {
        $draft = $this->invoice();

        Livewire::actingAs(User::factory()->create())
            ->test(ViewInvoice::class, ['record' => $draft->getKey()])
            ->assertOk()
            ->assertActionVisible('status:draft:final');

        $final = $draft->fresh();
        $final->update(['status' => Invoice::STATUS_FINAL]);

        Livewire::actingAs(User::factory()->create())
            ->test(ViewInvoice::class, ['record' => $final->getKey()])
            ->callAction('status:final:berjalan', [
                'status_date' => '2026-10-13',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('2026-10-13', $final->fresh()->berjalan_date?->toDateString());

        $this->actingAs(User::factory()->create())
            ->get(InvoiceResource::getUrl('view', ['record' => $draft]))
            ->assertSuccessful();
    }

    public function test_a_draft_invoice_has_no_finance_journal(): void
    {
        $this->invoice();

        $this->assertSame(0, FinanceJournal::count());
    }
}
