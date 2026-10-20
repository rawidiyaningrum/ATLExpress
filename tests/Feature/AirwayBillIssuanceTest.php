<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Filament\Resources\ShipmentResource\Pages\EditShipment;
use App\Filament\Resources\ShipmentResource\Pages\PrintAirwayBill;
use App\Filament\Resources\ShipmentResource\Pages\PrintInvoice;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AirwayBillService;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AirwayBillIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private function draftShipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => null,
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_DRAFT,
        ], $attributes));
    }

    public function test_admin_atl_wizard_generates_airway_bill_and_redirects_to_print_page(): void
    {
        $this->seed(PricelistSeeder::class);

        $adminAtl = User::factory()->adminAtl()->create();

        $wizard = Livewire::actingAs($adminAtl)
            ->test(CreateShipment::class)
            ->fillForm([
                'sender_name' => 'PT Kirim Sejahtera',
                'receiver_name' => 'Budi Santoso',
                'receiver_address' => 'Jl. Tujuan 9, Surabaya',
                'origin' => 'Jakarta',
                'kabupaten_tujuan' => 'Kota Surabaya',
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
            ->call('create');

        $shipment = Shipment::firstOrFail();

        $this->assertNotNull($shipment->awb_number, 'AWB terbit walau langkah AWB adalah langkah terakhir bagi admin ATL');
        $this->assertStringStartsWith('AWB_ATL_', $shipment->awb_number);
        $this->assertSame(Shipment::STATUS_IN_TRANSIT, $shipment->status);
        $this->assertSame(1, $shipment->logs()->count(), 'riwayat penerbitan AWB tercatat');
        $this->assertStringContainsString('Nomor AWB diterbitkan', $shipment->logs()->sole()->status_description);

        $wizard->assertRedirect(PrintAirwayBill::getUrl(['record' => $shipment]));
    }

    public function test_full_admin_wizard_still_generates_airway_bill(): void
    {
        $this->seed(PricelistSeeder::class);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateShipment::class)
            ->fillForm([
                'sender_name' => 'PT Kirim Sejahtera',
                'receiver_name' => 'Budi Santoso',
                'receiver_address' => 'Jl. Tujuan 9, Surabaya',
                'origin' => 'Jakarta',
                'kabupaten_tujuan' => 'Kota Surabaya',
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

        $this->assertNotNull($shipment->awb_number, 'AWB tetap terbit di langkah 3 untuk full admin');
        $this->assertSame(Shipment::STATUS_IN_TRANSIT, $shipment->status);
    }

    public function test_edit_page_action_issues_airway_bill_for_a_draft(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();
        $shipment = $this->draftShipment();

        Livewire::actingAs($adminAtl)
            ->test(EditShipment::class, ['record' => $shipment->getKey()])
            ->callAction('issueAwb')
            ->assertRedirect(PrintAirwayBill::getUrl(['record' => $shipment]));

        $shipment->refresh();

        $this->assertStringStartsWith('AWB_ATL_', $shipment->awb_number, 'jalur pemulihan dari halaman edit menerbitkan AWB');
        $this->assertSame(Shipment::STATUS_IN_TRANSIT, $shipment->status);
        $this->assertSame(1, $shipment->logs()->count());
    }

    public function test_issue_awb_action_is_hidden_for_issued_cancelled_and_non_admin(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();

        $issued = $this->draftShipment();
        app(AirwayBillService::class)->issue($issued);

        $cancelled = $this->draftShipment(['status' => Shipment::STATUS_CANCELLED]);

        Livewire::actingAs($adminAtl)
            ->test(EditShipment::class, ['record' => $this->draftShipment()->getKey()])
            ->assertActionVisible('issueAwb');

        Livewire::actingAs($adminAtl)
            ->test(EditShipment::class, ['record' => $issued->getKey()])
            ->assertActionHidden('issueAwb');

        Livewire::actingAs($adminAtl)
            ->test(EditShipment::class, ['record' => $cancelled->getKey()])
            ->assertActionHidden('issueAwb');

        $this->actingAs(User::factory()->tracker()->create())
            ->get(ShipmentResource::getUrl('edit', ['record' => $this->draftShipment()]))
            ->assertForbidden();
    }

    public function test_airway_bill_service_is_idempotent(): void
    {
        $shipment = $this->draftShipment();
        $service = app(AirwayBillService::class);

        $service->issue($shipment);
        $firstAwb = $shipment->fresh()->awb_number;

        $this->assertNotNull($firstAwb);

        $service->issue($shipment->fresh());

        $this->assertSame($firstAwb, $shipment->fresh()->awb_number, 'penerbitan ulang tidak mengganti nomor');
        $this->assertSame(1, $shipment->logs()->count());
    }

    public function test_airway_bill_service_skips_cancelled_shipments(): void
    {
        $shipment = $this->draftShipment(['status' => Shipment::STATUS_CANCELLED]);

        app(AirwayBillService::class)->issue($shipment);

        $this->assertNull($shipment->fresh()->awb_number, 'pengiriman dibatalkan tidak diterbitkan AWB-nya');
    }

    public function test_print_pages_use_the_document_number_as_title(): void
    {
        $admin = User::factory()->create();

        $shipment = $this->draftShipment();
        app(AirwayBillService::class)->issue($shipment);

        $awbTitle = Livewire::actingAs($admin)
            ->test(PrintAirwayBill::class, ['record' => $shipment->getKey()])
            ->instance()
            ->getTitle();

        $this->assertSame($shipment->awb_number, $awbTitle, 'judul halaman cetak AWB = nomor AWB, sehingga nama file PDF mengikuti');

        $invoice = Invoice::create([
            'invoice_number' => 'INV-Print-'.(Invoice::count() + 1),
            'shipment_id' => $shipment->id,
            'billed_to_name' => 'PT Penerima',
            'shipping_cost' => 150000,
            'subtotal' => 150000,
            'discount' => 0,
            'tax' => 0,
            'total' => 150000,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $invoiceTitle = Livewire::actingAs($admin)
            ->test(PrintInvoice::class, ['record' => $shipment->getKey()])
            ->instance()
            ->getTitle();

        $this->assertSame($invoice->invoice_number, $invoiceTitle, 'judul halaman cetak invoice = nomor invoice');
    }
}
