<?php

namespace Tests\Feature;

use App\Filament\Resources\DeliveryAgentResource;
use App\Filament\Resources\DeliveryAgentResource\Pages\CreateDeliveryAgent;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_agent_and_routes_relationship(): void
    {
        $agent = DeliveryAgent::create([
            'name' => 'PT Mitra Logistik Jaya',
            'phone' => '081234567890',
            'location' => 'Surabaya, Jawa Timur',
            'pic_name' => 'Bambang',
            'email' => 'agen@mitrajaya.com',
            'is_active' => true,
        ]);

        $route1 = $agent->routes()->create([
            'origin' => 'Surabaya',
            'destination' => 'Denpasar',
            'fleet_type' => 'CDD',
            'rate_type' => DeliveryAgentRoute::RATE_TYPE_CARTER,
            'tariff' => 3500000,
            'estimated_days' => '1-2 Hari',
        ]);

        $route2 = $agent->routes()->create([
            'origin' => 'Surabaya',
            'destination' => 'Makassar',
            'fleet_type' => 'CDDL',
            'rate_type' => DeliveryAgentRoute::RATE_TYPE_REGULER,
            'tariff' => 4500,
            'min_weight' => 50,
            'estimated_days' => '3-4 Hari',
        ]);

        $this->assertCount(2, $agent->fresh()->routes);
        $this->assertEquals('Surabaya', $route1->origin);
        $this->assertEquals('Denpasar', $route1->destination);
        $this->assertEquals(DeliveryAgentRoute::RATE_TYPE_CARTER, $route1->rate_type);
        $this->assertEquals(3500000, (float) $route1->tariff);

        $this->assertEquals(DeliveryAgentRoute::RATE_TYPE_REGULER, $route2->rate_type);
        $this->assertEquals(4500, (float) $route2->tariff);
    }

    public function test_admin_can_access_delivery_agent_resource(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(DeliveryAgentResource::getUrl('index'))
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get(DeliveryAgentResource::getUrl('create'))
            ->assertSuccessful();
    }

    public function test_tracker_cannot_access_delivery_agent_resource(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)
            ->get(DeliveryAgentResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs($tracker)
            ->get(DeliveryAgentResource::getUrl('create'))
            ->assertForbidden();
    }

    public function test_admin_can_create_delivery_agent_with_routes(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateDeliveryAgent::class)
            ->fillForm([
                'name' => 'Agen Express Nusantara',
                'phone' => '085712345678',
                'location' => 'Semarang, Jawa Tengah',
                'pic_name' => 'Suryo',
                'is_active' => true,
                'routes' => [
                    [
                        'origin' => 'Semarang',
                        'destination' => 'Jakarta',
                        'fleet_type' => 'CDD',
                        'rate_type' => 'carter',
                        'tariff' => 2800000,
                        'estimated_days' => '1 Hari',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('delivery_agents', [
            'name' => 'Agen Express Nusantara',
            'phone' => '085712345678',
        ]);

        $this->assertDatabaseHas('delivery_agent_routes', [
            'origin' => 'Semarang',
            'destination' => 'Jakarta',
            'fleet_type' => 'CDD',
            'rate_type' => 'carter',
            'tariff' => 2800000,
        ]);
    }
}

