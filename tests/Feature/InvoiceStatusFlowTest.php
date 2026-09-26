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

    public function test_a_draft_invoice_can_be_billed_and_paid(): void
    {
        $invoice = $this->invoice();
        $service = app(InvoiceService::class);

        $service->transitionTo($invoice, Invoice::STATUS_TERTAGIH);
        $this->assertSame(Invoice::STATUS_TERTAGIH, $invoice->fresh()->status);
        $this->assertSame(1, FinanceJournal::count());
        $this->assertSame(
            FinanceJournal::TYPE_REVENUE,
            FinanceJournal::sole()->journal_type,
            'menagih menghasilkan jurnal pendapatan',
        );

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_LUNAS);
        $this->assertSame(Invoice::STATUS_LUNAS, $invoice->fresh()->status);
        $this->assertSame(2, FinanceJournal::count());
    }

    public function test_a_paid_invoice_can_only_step_back_to_billed(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markBilled($invoice);
        $service->markPaid($invoice->fresh());

        $service->transitionTo($invoice->fresh(), Invoice::STATUS_TERTAGIH);

        $this->assertSame(Invoice::STATUS_TERTAGIH, $invoice->fresh()->status);
        $this->assertSame(1, FinanceJournal::count(), 'jurnal kas dihapus saat invoice ditagih ulang');
        $this->assertSame(
            FinanceJournal::TYPE_REVENUE,
            FinanceJournal::sole()->journal_type,
        );
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

    public function test_a_paid_invoice_cannot_skip_the_billed_step_when_stepping_back(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();
        $service->markBilled($invoice);
        $service->markPaid($invoice->fresh());

        $this->expectException(HttpException::class);

        try {
            $service->transitionTo($invoice->fresh(), Invoice::STATUS_DRAFT);
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

        $service->transitionTo($invoice, 'final');
    }

    public function test_billing_twice_does_not_duplicate_the_revenue_journal(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $service->markBilled($invoice);
        $service->markBilled($invoice->fresh());
        $service->markBilled($invoice->fresh());

        $this->assertSame(1, FinanceJournal::count());
    }

    public function test_paying_twice_does_not_duplicate_the_cash_journal(): void
    {
        $service = app(InvoiceService::class);
        $invoice = $this->invoice();

        $service->markBilled($invoice);
        $service->markPaid($invoice->fresh());
        $service->markPaid($invoice->fresh());

        $this->assertSame(2, FinanceJournal::count());
    }

    public function test_a_billed_invoice_cannot_be_silently_saved_back_as_draft(): void
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
            'status' => Invoice::STATUS_TERTAGIH,
        ]);

        try {
            $service->saveForShipment($shipment, [
                'billed_to_name' => 'Budi Santoso',
                'shipping_cost' => 150000,
                'items' => [],
            ], Invoice::STATUS_DRAFT);

            $this->fail('invoice tertagih tidak boleh disimpan ulang sebagai draft');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertSame(Invoice::STATUS_TERTAGIH, $invoice->fresh()->status);
    }

    public function test_only_the_next_step_is_offered_in_the_ui(): void
    {
        $draft = $this->invoice();
        $tertagih = $this->invoice(['status' => Invoice::STATUS_TERTAGIH]);
        $lunas = $this->invoice(['status' => Invoice::STATUS_LUNAS]);

        $this->assertSame([Invoice::STATUS_TERTAGIH], $draft->allowedTransitions());
        $this->assertSame(
            [Invoice::STATUS_LUNAS, Invoice::STATUS_DRAFT],
            $tertagih->allowedTransitions(),
        );
        $this->assertSame([Invoice::STATUS_TERTAGIH], $lunas->allowedTransitions());

        $this->assertFalse($draft->isLocked());
        $this->assertTrue($tertagih->isLocked());
        $this->assertTrue($lunas->isLocked());
    }

    public function test_the_list_offers_only_the_transition_for_the_current_status(): void
    {
        $draft = $this->invoice();
        $tertagih = $this->invoice(['status' => Invoice::STATUS_TERTAGIH]);
        $lunas = $this->invoice(['status' => Invoice::STATUS_LUNAS]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionVisible('status:draft:tertagih', $draft)
            // Draft ke lunas adalah lompatan, jadi aksinya tidak pernah
            // didaftarkan sama sekali, bukan sekadar disembunyikan.
            ->assertTableActionDoesNotExist('status:draft:lunas')
            ->assertTableActionVisible('status:tertagih:lunas', $tertagih);

        // Aksi kembali ke draft hanya berlaku pada invoice tertagih, bukan lunas.
        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionHidden('status:lunas:tertagih', $tertagih)
            ->assertTableActionVisible('status:lunas:tertagih', $lunas);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertOk()
            ->assertTableActionVisible('edit', $draft)
            ->assertTableActionHidden('edit', $lunas);
    }

    public function test_the_detail_page_offers_billing_for_a_draft_invoice(): void
    {
        $draft = $this->invoice();

        Livewire::actingAs(User::factory()->create())
            ->test(ViewInvoice::class, ['record' => $draft->getKey()])
            ->assertOk()
            ->assertActionVisible('status:draft:tertagih');

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
