<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource\Pages\PrintReceipt;
use App\Filament\Resources\ShipmentResource\Pages\PrintAirwayBill;
use App\Filament\Resources\ShipmentResource\Pages\PrintInvoice;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyPrintOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('npwp_atl', '01.234.567.8-012.000');
        Setting::set('bank_name_atl', 'Bank BCA');
        Setting::set('bank_account_number_atl', '604.3792.330');
        Setting::set('bank_account_name_atl', 'PT ANGKASA TRANS LOGISTIK');

        Setting::set('npwp_douze', '98.765.432.1-098.000');
        Setting::set('bank_name_douze', 'Bank Mandiri');
        Setting::set('bank_account_number_douze', '123-00-9876543-2');
        Setting::set('bank_account_name_douze', 'PT DOUZE NUSANTARA');
    }

    private function createShipmentWithInvoice(): array
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB-TEST-COMP-001',
            'sender_name' => 'PT Pengirim Mandiri',
            'sender_address' => 'Jl. Pengirim No 1, Jakarta',
            'receiver_name' => 'PT Penerima Sukses',
            'receiver_address' => 'Jl. Penerima No 2, Surabaya',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 10,
            'status' => 'in_transit',
        ]);

        $invoice = app(InvoiceService::class)->saveForShipment($shipment, [
            'billed_to_name' => 'PT Penerima Sukses',
            'billed_to_address' => 'Jl. Penerima No 2, Surabaya',
            'shipping_cost' => 250000,
        ], 'final');

        return [$shipment, $invoice];
    }

    public function test_airway_bill_defaults_to_atl_express(): void
    {
        [$shipment] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', ['record' => $shipment]))
            ->assertOk()
            ->assertSee('images/logo.png')
            ->assertSee('ATL Express')
            ->assertSee('01.234.567.8-012.000');
    }

    public function test_airway_bill_switches_to_douze_via_query_and_livewire(): void
    {
        [$shipment] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', [
                'record' => $shipment,
                'company' => 'douze',
            ]))
            ->assertOk()
            ->assertSee('images/logo_douze.jpeg')
            ->assertSee('PT Douze (Multimedia, Logistik, Organizer)')
            ->assertSee('98.765.432.1-098.000');

        Livewire::actingAs($user)
            ->test(PrintAirwayBill::class, ['record' => $shipment->getKey()])
            ->assertSet('company', 'atl')
            ->call('setCompany', 'douze')
            ->assertSet('company', 'douze')
            ->assertSee('logo_douze.jpeg')
            ->assertSee('PT Douze');
    }

    public function test_invoice_defaults_to_atl_express_with_bank_details(): void
    {
        [$shipment, $invoice] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-invoice', ['record' => $shipment]))
            ->assertOk()
            ->assertSee('images/logo.png')
            ->assertSee('ATL Express')
            ->assertSee('01.234.567.8-012.000')
            ->assertSee('Bank BCA')
            ->assertSee('604.3792.330')
            ->assertSee('PT ANGKASA TRANS LOGISTIK')
            ->assertSee('Mohon konfirmasi pembayaran ke rekening ATL Express.');
    }

    public function test_invoice_switches_to_douze_with_bank_details(): void
    {
        [$shipment, $invoice] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-invoice', [
                'record' => $shipment,
                'company' => 'douze',
            ]))
            ->assertOk()
            ->assertSee('images/logo_douze.jpeg')
            ->assertSee('PT Douze (Multimedia, Logistik, Organizer)')
            ->assertSee('98.765.432.1-098.000')
            ->assertSee('Bank Mandiri')
            ->assertSee('123-00-9876543-2')
            ->assertSee('PT DOUZE NUSANTARA')
            ->assertSee('Mohon konfirmasi pembayaran ke rekening PT Douze.');

        Livewire::actingAs($user)
            ->test(PrintInvoice::class, ['record' => $shipment->getKey()])
            ->assertSet('company', 'atl')
            ->call('setCompany', 'douze')
            ->assertSet('company', 'douze')
            ->assertSee('Bank Mandiri')
            ->assertSee('123-00-9876543-2');
    }

    public function test_kwitansi_defaults_to_atl_express_and_switches_to_douze(): void
    {
        [$shipment, $invoice] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.invoices.kwitansi', ['record' => $invoice]))
            ->assertOk()
            ->assertSee('images/logo.png')
            ->assertSee('ATL Express')
            ->assertSee('01.234.567.8-012.000');

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.invoices.kwitansi', [
                'record' => $invoice,
                'company' => 'douze',
            ]))
            ->assertOk()
            ->assertSee('images/logo_douze.jpeg')
            ->assertSee('PT Douze (Multimedia, Logistik, Organizer)')
            ->assertSee('98.765.432.1-098.000')
            ->assertSee('PT Douze');

        Livewire::actingAs($user)
            ->test(PrintReceipt::class, ['record' => $invoice->getKey()])
            ->assertSet('company', 'atl')
            ->call('setCompany', 'douze')
            ->assertSet('company', 'douze')
            ->assertSee('logo_douze.jpeg');
    }

    public function test_print_headers_show_the_company_address(): void
    {
        [$shipment, $invoice] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();
        $address = 'Graha Buana , Jl. Dr. Saharjo No.210A';

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', ['record' => $shipment]))
            ->assertOk()
            ->assertSee($address);

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-invoice', ['record' => $shipment]))
            ->assertOk()
            ->assertSee($address);

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.invoices.kwitansi', ['record' => $invoice]))
            ->assertOk()
            ->assertSee($address);
    }

    public function test_per_company_address_setting_overrides_the_default_in_print_header(): void
    {
        Setting::set('address_atl', 'Jl. Khusus ATL No. 5, Jakarta');
        [$shipment] = $this->createShipmentWithInvoice();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', ['record' => $shipment]))
            ->assertOk()
            ->assertSee('Jl. Khusus ATL No. 5, Jakarta');
    }
}
