<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Models\FinanceJournal;
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
        $this->assertSame('PT Kirim Sejahtera', $invoice->billed_to_name, 'billed-to default dari pengirim');
        $this->assertSame('Jl. Gudang 1, Jakarta', $invoice->billed_to_address);
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

        $this->assertSame(
            Invoice::STATUS_DRAFT,
            $invoice->fresh()->status,
            'wizard berhenti di draft, penagihan dilakukan manual dari daftar invoice',
        );
        $this->assertSame(0, FinanceJournal::count(), 'invoice draft belum menghasilkan jurnal');

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

    public function test_quick_buttons_compute_percentages_from_the_running_subtotal(): void
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

        // PPN 11% dihitung dari ongkos kirim (berat x ongkir per kilo).
        $wizard->mountFormComponentAction('quickPpnAction', 'quickPpn');
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * 0.11, 2));

        // Tambah packing, subtotal berjalan naik; PPh 2% ikut subtotal itu.
        $wizard->mountFormComponentAction('quickPackingAction', 'quickPacking');

        $subtotal = $tariff + 50000;

        $wizard->mountFormComponentAction('quickPphAction', 'quickPph');
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][2]['unit_price']
            === round($subtotal * 0.02, 2));

        // Diskon 5% juga ikut subtotal berjalan, dipakai untuk potongan biasa.
        $wizard->mountFormComponentAction('quickDiscountAction', 'quickDiscount');
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][3]['unit_price']
            === round($subtotal * 0.05, 2));

        // Baris isi manual tetap tersimpan tanpa basis.
        $wizard->fillForm([
            'invoice_items' => [
                ['description' => 'Biaya layanan', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 25000],
            ],
        ])->goToWizardStep(5);

        $item = Invoice::sole()->items()->sole();

        $this->assertSame('Biaya layanan', $item->description);
    }

    public function test_selecting_ppn_or_pph_item_type_fills_the_price_from_the_dpp(): void
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
            ->goToWizardStep(4)
            ->fillForm([
                'invoice_items' => [
                    ['description' => 'Isi otomatis', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 0],
                ],
            ]);

        $tariff = (float) Shipment::firstOrFail()->final_tariff;

        // PPN 11% dari DPP (ongkos kirim, belum ada biaya tambahan lain).
        $wizard->set('data.invoice_items.0.type', InvoiceService::TYPE_TAX);
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * InvoiceService::PPN_RATE, 2));

        // PPh 2% dari DPP yang sama.
        $wizard->set('data.invoice_items.0.type', InvoiceService::TYPE_DISCOUNT);
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * InvoiceService::PPH_RATE, 2));

        // Biaya tambahan tidak punya nominal otomatis: harga terakhir bertahan.
        $wizard->set('data.invoice_items.0.type', InvoiceService::TYPE_ADDITIONAL);
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * InvoiceService::PPH_RATE, 2));

        // Diskon juga diisi manual, nominal tidak terpengaruh pemilihan jenis.
        $wizard->set('data.invoice_items.0.type', InvoiceService::TYPE_DISKON);
        $wizard->assertFormSet(fn (array $state): bool => (float) $state['invoice_items'][0]['unit_price']
            === round($tariff * InvoiceService::PPH_RATE, 2));
    }

    public function test_invoice_calculation_keeps_discount_and_tax_out_of_the_subtotal(): void
    {
        $totals = app(InvoiceService::class)->calculate(100000, [
            ['description' => 'Packing kayu', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 50000],
            ['description' => 'Potongan PPh', 'type' => 'discount', 'quantity' => 1, 'unit_price' => 5000],
            ['description' => 'Diskon kerjasama', 'type' => 'diskon', 'quantity' => 1, 'unit_price' => 20000],
            ['description' => 'PPN 11%', 'type' => 'tax', 'quantity' => 1, 'unit_price' => 11000],
            ['description' => '', 'type' => 'tax', 'quantity' => 1, 'unit_price' => 99999],
        ]);

        $this->assertSame(150000.0, $totals['subtotal'], 'hanya ongkir dan additional');
        $this->assertSame(5000.0, $totals['discount']);
        $this->assertSame(20000.0, $totals['diskon']);
        $this->assertSame(11000.0, $totals['tax'], 'baris tanpa deskripsi diabaikan');
        $this->assertSame(136000.0, $totals['total'], '150000 - 5000 - 20000 + 11000');
    }
}
