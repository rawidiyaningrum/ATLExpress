<?php

namespace Tests\Feature;

use App\Filament\Pages\Trucking;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentRoute;
use App\Models\Shipment;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TruckingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_admin_atl_can_access_trucking_page(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(Trucking::getUrl())->assertSuccessful();

        $adminAtl = User::factory()->adminAtl()->create();
        $this->actingAs($adminAtl)->get(Trucking::getUrl())->assertSuccessful();
    }

    public function test_tracker_cannot_access_trucking_page(): void
    {
        $tracker = User::factory()->tracker()->create();
        $this->actingAs($tracker)->get(Trucking::getUrl())->assertForbidden();
    }

    public function test_search_trucking_displays_vendor_routes_and_shipment_rates(): void
    {
        $admin = User::factory()->create();

        // 1. Buat data vendor agen dan rutenya
        $agent = DeliveryAgent::create([
            'name' => 'PT Mitra Trucking Utama',
            'phone' => '081299887766',
            'location' => 'Jakarta Barat',
            'pic_name' => 'Budi',
            'is_active' => true,
        ]);

        $agent->routes()->create([
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'fleet_type' => 'CDD',
            'rate_type' => 'carter',
            'tariff' => 4500000,
            'estimated_days' => '1-2 Hari',
            'notes' => 'Armada box siap jalan',
        ]);

        $agent->routes()->create([
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'fleet_type' => 'Tronton Wingbox',
            'rate_type' => 'carter',
            'tariff' => 9500000,
            'estimated_days' => '1 Hari',
        ]);

        // 2. Buat data master tarif ongkir shipment
        ShippingRate::create([
            'origin_city' => 'Jakarta',
            'kabupaten_tujuan' => 'Kota Surabaya',
            'destination_city' => 'Surabaya',
            'service_type' => 'darat',
            'min_weight' => 50,
            'price_per_kg' => 3500,
            'estimated_days' => '2-3 Hari',
        ]);

        // 3. Buat data transaksi shipment pada rute ini
        Shipment::create([
            'awb_number' => 'ATL-202611-TEST01',
            'sender_name' => 'Pengirim Test',
            'sender_phone' => '0812345678',
            'sender_address' => 'Jakarta',
            'receiver_name' => 'Penerima Test',
            'receiver_phone' => '0812345679',
            'receiver_address' => 'Surabaya',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'service_type' => 'darat',
            'weight' => 120,
            'final_tariff' => 420000,
            'status' => 'delivered',
        ]);

        // Test Livewire component
        Livewire::actingAs($admin)
            ->test(Trucking::class)
            ->fillForm([
                'origin' => 'Jakarta',
                'destination' => 'Surabaya',
                'weight' => 100,
            ])
            ->call('search')
            ->assertSee('PT Mitra Trucking Utama')
            ->assertSee('CDD')
            ->assertSee('Tronton Wingbox')
            ->assertSee('Rp 4.500.000')
            ->assertSee('Rp 9.500.000')
            ->assertSee('Rp 3.500')
            ->assertSee('ATL-202611-TEST01');
    }
}
