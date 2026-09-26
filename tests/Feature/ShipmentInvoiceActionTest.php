<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipmentInvoice;
use App\Filament\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentInvoiceActionTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => 'AWB-AKSI-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'receiver_name' => 'Budi Santoso',
            'receiver_address' => 'Jl. Tujuan 9, Surabaya',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 5,
            'final_tariff' => 150000,
            'status' => 'in_transit',
        ], $attributes));
    }

    public function test_shipment_without_invoice_shows_buat_invoice_and_saves_a_draft(): void
    {
        $shipment = $this->shipment();

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->assertCanSeeTableRecords([$shipment])
            ->assertTableActionVisible('invoice', $shipment)
            ->assertSee('Buat Invoice');

        $page = Livewire::actingAs(User::factory()->create())
            ->test(CreateShipmentInvoice::class, ['record' => $shipment->getKey()])
            ->assertOk()
            ->assertSee('Digunakan otomatis: nama "Budi Santoso"')
            ->mountFormComponentAction('quickPpnAction', 'quickPpn')
            ->call('mountAction', 'saveDraft');

        $invoice = Invoice::sole();

        $this->assertSame('draft', $invoice->status);
        $this->assertStringStartsWith('INV_ATL_', $invoice->invoice_number);
        $this->assertSame('Budi Santoso', $invoice->billed_to_name, 'default dari data penerima');
        $this->assertSame(150000.0, (float) $invoice->shipping_cost, 'default dari tarif final');
        $this->assertSame(1, $invoice->items()->count(), 'baris PPN dari tombol cepat');
        $this->assertSame(16500.0, (float) $invoice->tax, 'PPN 11% dari tarif final');
        $this->assertSame(166500.0, (float) $invoice->total);

        $page->assertRedirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
    }

    public function test_billing_from_the_create_page_redirects_to_print(): void
    {
        $shipment = $this->shipment();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateShipmentInvoice::class, ['record' => $shipment->getKey()])
            ->fillForm([
                'invoice_billed_to_name' => 'PT Tempo billed',
                'invoice_shipping_cost' => 200000,
                'invoice_items' => [
                    ['description' => 'Packing kayu', 'type' => 'additional', 'dihitung_dari' => InvoiceService::BASIS_FINAL_TARIFF, 'quantity' => 2, 'unit_price' => 50000],
                ],
            ])
            ->call('mountAction', 'saveBilled')
            ->assertRedirect(route('filament.atlexpress-admin.resources.shipments.print-invoice', [
                'record' => $shipment,
            ]));

        $invoice = Invoice::sole();

        $this->assertSame(Invoice::STATUS_TERTAGIH, $invoice->status);
        $this->assertSame('PT Tempo billed', $invoice->billed_to_name);
        $this->assertSame(300000.0, (float) $invoice->subtotal, '200000 + 2 x 50000');

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.shipments.print-invoice', ['record' => $shipment]))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    public function test_saving_draft_from_the_create_page_opens_the_invoice_detail(): void
    {
        $shipment = $this->shipment();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateShipmentInvoice::class, ['record' => $shipment->getKey()])
            ->fillForm([
                'invoice_billed_to_name' => 'PT Tempo draft',
                'invoice_shipping_cost' => 200000,
                'invoice_items' => [],
            ])
            ->call('mountAction', 'saveDraft')
            ->assertRedirect(InvoiceResource::getUrl('view', ['record' => Invoice::sole()]));

        $this->assertSame(Invoice::STATUS_DRAFT, Invoice::sole()->status);
    }

    public function test_shipment_with_invoice_shows_invoice_and_links_to_the_detail(): void
    {
        $shipment = $this->shipment();
        $invoice = app(InvoiceService::class)->saveForShipment($shipment, [
            'billed_to_name' => 'Budi Santoso',
            'shipping_cost' => 150000,
            'items' => [],
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->assertTableActionVisible('invoice', $shipment)
            ->assertDontSee('Buat Invoice');

        $this->assertSame(
            InvoiceResource::getUrl('view', ['record' => $invoice]),
            ShipmentResource::invoiceActionUrl($shipment->fresh()),
            'invoice yang sudah ada membuka detailnya',
        );

        Livewire::actingAs(User::factory()->create())
            ->test(EditShipment::class, ['record' => $shipment->getKey()])
            ->assertActionVisible('invoice');
    }

    public function test_create_page_is_closed_when_an_invoice_already_exists(): void
    {
        $shipment = $this->shipment();
        app(InvoiceService::class)->saveForShipment($shipment, ['shipping_cost' => 150000]);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.shipments.create-invoice', ['record' => $shipment]))
            ->assertNotFound();
    }

    public function test_cancelled_shipment_cannot_get_a_new_invoice(): void
    {
        $shipment = $this->shipment(['status' => 'cancelled']);

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->assertTableActionHidden('invoice', $shipment);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.shipments.create-invoice', ['record' => $shipment]))
            ->assertNotFound();
    }
}
