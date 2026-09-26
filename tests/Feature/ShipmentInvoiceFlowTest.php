<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\ShippingRateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_create_print_an_invoice_from_the_wizard(): void
    {
        $this->seed(ShippingRateSeeder::class);

        $wizard = Livewire::actingAs(User::factory()->create())
            ->test(CreateShipment::class)
            ->fillForm([
                'sender_name' => 'PT Kirim Sejahtera',
                'sender_phone' => '081200000001',
                'sender_address' => 'Jl. Gudang 1, Jakarta',
                'receiver_name' => 'Budi Santoso',
                'receiver_phone' => '081300000001',
                'receiver_address' => 'Jl. Tujuan 9, Surabaya',
                'origin' => 'Jakarta',
                'destination' => 'Surabaya',
            ])
            ->mountFormComponentAction('pilih_daratAction', 'pilih_darat')
            ->goToWizardStep(2)
            ->fillForm([
                'weight' => 5,
                'dimension_length' => 30,
                'dimension_width' => 20,
                'dimension_height' => 15,
            ])
            ->goToWizardStep(3)
            ->goToWizardStep(4);

        $shipment = Shipment::firstOrFail();
        $tariff = (float) $shipment->final_tariff;

        $this->assertNotNull($shipment->awb_number, 'AWB terbit di langkah 3');
        $this->assertSame('in_transit', $shipment->status);
        $this->assertSame('darat', $shipment->service_type);
        $this->assertGreaterThan(0, $tariff, 'tarif final terisi di langkah 2');

        // Langkah 4: operator menambah PPN, lalu meninggalkan langkah tersebut.
        $wizard->mountFormComponentAction('quickPpnAction', 'quickPpn')
            ->goToWizardStep(5);

        $ppn = round($tariff * 0.11, 2);

        $invoice = Invoice::sole();

        $this->assertStringStartsWith('INV_ATL_', $invoice->invoice_number);
        $this->assertSame('draft', $invoice->status, 'invoice masih draft sebelum(create)');
        $this->assertSame('Budi Santoso', $invoice->billed_to_name, 'billed-to default dari penerima');
        $this->assertSame('Jl. Tujuan 9, Surabaya', $invoice->billed_to_address);
        $this->assertSame($tariff, (float) $invoice->shipping_cost, 'ongkos default dari tarif final');
        $this->assertSame($tariff, (float) $invoice->subtotal);
        $this->assertSame($ppn, (float) $invoice->tax);
        $this->assertSame($tariff + $ppn, (float) $invoice->total);

        // Kunci invoice_* tidak boleh pernah menyentuh tabel shipments.
        $this->assertSame([], array_values(array_intersect(
            array_keys($shipment->getAttributes()),
            ['invoice_billed_to_name', 'invoice_billed_to_address', 'invoice_shipping_cost', 'invoice_items'],
        )));

        // Simpan ulang langkah 4 harus memperbarui invoice yang sama.
        $wizard->goToWizardStep(4)
            ->mountFormComponentAction('quickPackingAction', 'quickPacking')
            ->goToWizardStep(5);

        $this->assertSame(1, Invoice::count(), 'invoice yang sama dipakai ulang');
        $this->assertSame(2, $invoice->fresh()->items()->count());

        // Selesaikan wizard.
        $wizard->call('create');

        $this->assertSame('final', $invoice->fresh()->status, 'invoice final setelah create');

        $printUrl = route('filament.atlexpress-admin.resources.shipments.print-invoice', [
            'record' => $shipment,
        ]);

        $wizard->assertRedirect($printUrl);

        $invoice = $invoice->fresh();

        $this->assertSame(2, $invoice->items()->count());
        $this->assertSame($tariff + 50000, (float) $invoice->subtotal, 'ongkos + packing kayu');
        $this->assertSame($ppn, (float) $invoice->tax, 'PPN tetap 11% dari ongkos awal');
        $this->assertSame($tariff + 50000 + $ppn, (float) $invoice->total);

        $this->actingAs(User::factory()->create())
            ->get($printUrl)
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee(number_format((float) $invoice->total, 0, ',', '.'));
    }

    public function test_quick_buttons_follow_the_selected_basis(): void
    {
        $this->seed(ShippingRateSeeder::class);

        $wizard = Livewire::actingAs(User::factory()->create())
            ->test(CreateShipment::class)
            ->fillForm([
                'sender_name' => 'PT Kirim Sejahtera',
                'receiver_name' => 'Budi Santoso',
                'receiver_address' => 'Jl. Tujuan 9, Surabaya',
                'origin' => 'Jakarta',
                'destination' => 'Surabaya',
            ])
            ->mountFormComponentAction('pilih_daratAction', 'pilih_darat')
            ->goToWizardStep(2)
            ->fillForm([
                'weight' => 5,
                'dimension_length' => 30,
                'dimension_width' => 20,
                'dimension_height' => 15,
            ])
            ->goToWizardStep(3)
            ->goToWizardStep(4);

        $tariff = (float) Shipment::firstOrFail()->final_tariff;

        // Default: PPN dihitung dari tarif final.
        $wizard->mountFormComponentAction('quickPpnAction', 'quickPpn');
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * 0.11, 2));
        $wizard->assertFormSet(fn (array $state): bool => $state['invoice_items'][0]['dihitung_dari']
            === InvoiceService::BASIS_FINAL_TARIFF);

        // Ganti basis ke subtotal item sebelumnya, lalu tambah packing kayu.
        $wizard->fillForm(['invoice_basis' => InvoiceService::BASIS_PREVIOUS_ITEMS])
            ->mountFormComponentAction('quickPackingAction', 'quickPacking');

        $subtotal = $tariff + 50000;

        $wizard->mountFormComponentAction('quickDiscountAction', 'quickDiscount');
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][2]['unit_price']
            === round($subtotal * 0.05, 2));
        $wizard->assertFormSet(fn (array $state): bool => $state['invoice_items'][2]['dihitung_dari']
            === InvoiceService::BASIS_PREVIOUS_ITEMS);

        // Basis per baris bisa diganti manual dan ikut tersimpan.
        $wizard->fillForm([
            'invoice_items' => [
                ['description' => 'Biaya layanan', 'type' => 'additional', 'dihitung_dari' => InvoiceService::BASIS_PREVIOUS_ITEMS, 'quantity' => 1, 'unit_price' => 25000],
            ],
        ])->goToWizardStep(5);

        $item = Invoice::sole()->items()->sole();

        $this->assertSame('Biaya layanan', $item->description);
        $this->assertSame(InvoiceService::BASIS_PREVIOUS_ITEMS, $item->basis);
    }

    public function test_invoice_calculation_keeps_discount_and_tax_out_of_the_subtotal(): void
    {
        $totals = app(InvoiceService::class)->calculate(100000, [
            ['description' => 'Packing kayu', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 50000],
            ['description' => 'Diskon 5%', 'type' => 'discount', 'quantity' => 1, 'unit_price' => 5000],
            ['description' => 'PPN 11%', 'type' => 'tax', 'quantity' => 1, 'unit_price' => 11000],
            ['description' => '', 'type' => 'tax', 'quantity' => 1, 'unit_price' => 99999],
        ]);

        $this->assertSame(150000.0, $totals['subtotal'], 'hanya ongkir dan additional');
        $this->assertSame(5000.0, $totals['discount']);
        $this->assertSame(11000.0, $totals['tax'], 'baris tanpa deskripsi diabaikan');
        $this->assertSame(156000.0, $totals['total'], '150000 - 5000 + 11000');
    }
}
