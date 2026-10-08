<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceMenuTest extends TestCase
{
    use RefreshDatabase;

    private function draftInvoice(array $overrides = [], ?string $awbNumber = null): Invoice
    {
        $shipment = Shipment::create([
            'awb_number' => $awbNumber ?? 'AWB-MENU-'.Shipment::count().'-'.Invoice::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'receiver_name' => 'Budi Santoso',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 5,
            'status' => 'in_transit',
        ]);

        return app(InvoiceService::class)->saveForShipment($shipment, array_merge([
            'billed_to_name' => 'Budi Santoso',
            'billed_to_address' => 'Jl. Tujuan 9, Surabaya',
            'shipping_cost' => 120000,
            'items' => [
                [
                    'description' => 'Packing kayu',
                    'type' => 'additional',
                    'basis' => InvoiceService::BASIS_PREVIOUS_ITEMS,
                    'quantity' => 2,
                    'unit_price' => 50000,
                ],
            ],
        ], $overrides), 'draft');
    }

    public function test_invoice_menu_lists_and_filters_invoices(): void
    {
        $draft = $this->draftInvoice([], 'AWB-MENU-0001');
        $tertagih = $this->draftInvoice(['billed_to_name' => 'Siti Aminah'], 'AWB-MENU-0002');
        $tertagih->update(['status' => Invoice::STATUS_TERTAGIH]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->assertActionHidden('create')
            ->assertCanSeeTableRecords([$draft, $tertagih])
            ->assertSee($draft->invoice_number)
            ->assertSee('AWB-MENU-0001')
            ->assertSee('Jakarta')
            ->assertSee('Siti Aminah');

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->set('activeTab', Invoice::STATUS_TERTAGIH)
            ->assertCanSeeTableRecords([$tertagih])
            ->assertCanNotSeeTableRecords([$draft], 'tab Tertagih tidak memuat invoice draft');

        Livewire::actingAs(User::factory()->create())
            ->test(ListInvoices::class)
            ->set('activeTab', Invoice::STATUS_DRAFT)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$tertagih], 'tab Draft tidak memuat invoice tertagih');
    }

    public function test_invoice_detail_shows_items_totals_and_print_link(): void
    {
        $invoice = $this->draftInvoice();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ViewInvoice::class, ['record' => $invoice->getKey()])
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Packing kayu')
            ->assertSee(number_format((float) $invoice->total, 0, ',', '.'));

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.invoices.view', ['record' => $invoice]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-invoice', [
                'record' => $invoice->shipment_id,
            ]))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_editing_a_draft_invoice_recalculates_totals(): void
    {
        $invoice = $this->draftInvoice();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->fillForm(['shipping_cost' => 200000, 'invoice_items' => []])
            ->fillForm([
                'shipping_cost' => 200000,
                'invoice_items' => [
                    [
                        'description' => 'Packing kayu',
                        'type' => 'additional',
                        'quantity' => 2,
                        'unit_price' => 50000,
                    ],
                    [
                        'description' => 'Diskon 10%',
                        'type' => 'discount',
                        'quantity' => 1,
                        'unit_price' => 30000,
                    ],
                    [
                        'description' => 'PPN 11%',
                        'type' => 'tax',
                        'quantity' => 1,
                        'unit_price' => 22000,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $invoice->refresh();

        $this->assertSame(200000.0, (float) $invoice->shipping_cost, 'ongkos kirim sesuai isian form');
        $this->assertSame(300000.0, (float) $invoice->subtotal, '200000 + 2 x 50000');
        $this->assertSame(30000.0, (float) $invoice->discount);
        $this->assertSame(22000.0, (float) $invoice->tax);
        $this->assertSame(292000.0, (float) $invoice->total, '300000 - 30000 + 22000');
        $this->assertSame(3, $invoice->items()->count());
        $this->assertSame('Packing kayu', $invoice->items()->first()->description);
    }

    public function test_billed_invoice_is_locked_against_editing(): void
    {
        $invoice = $this->draftInvoice();
        $invoice->update(['status' => Invoice::STATUS_TERTAGIH]);

        Livewire::actingAs(User::factory()->create())
            ->test(ViewInvoice::class, ['record' => $invoice->getKey()])
            ->assertActionHidden('edit');

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.invoices.edit', ['record' => $invoice]))
            ->assertForbidden();
    }

    public function test_paid_invoice_is_locked_against_editing(): void
    {
        $invoice = $this->draftInvoice();
        $invoice->update(['status' => Invoice::STATUS_LUNAS]);

        Livewire::actingAs(User::factory()->create())
            ->test(ViewInvoice::class, ['record' => $invoice->getKey()])
            ->assertActionHidden('edit');

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.invoices.edit', ['record' => $invoice]))
            ->assertForbidden();
    }
}
