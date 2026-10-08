<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirwayBillPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_airway_bill_shows_shipping_type_and_goods_description_without_tariff(): void
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB_ATL_20261008_000001',
            'sender_name' => 'PT Kirim Sejahtera',
            'receiver_name' => 'Budi Santoso',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => 'laut',
            'item_type' => 'Sparepart elektronik, 2 dus',
            'weight' => 5,
            'status' => 'in_transit',
            'final_tariff' => 150000,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', ['record' => $shipment]))
            ->assertOk()
            ->assertSee('Jenis Pengiriman')
            ->assertSee('Laut')
            ->assertSee('Jenis/Isi Barang')
            ->assertSee('Sparepart elektronik, 2 dus')
            ->assertDontSee('Tarif Final')
            ->assertDontSee('Rp 150.000');
    }

    public function test_airway_bill_falls_back_gracefully_when_optional_fields_are_empty(): void
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB_ATL_20261008_000002',
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => null,
            'item_type' => null,
            'status' => 'in_transit',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.atlexpress-admin.resources.shipments.print-airway-bill', ['record' => $shipment]))
            ->assertOk()
            ->assertSee('Jenis Pengiriman')
            ->assertSee('Jenis/Isi Barang');
    }
}
