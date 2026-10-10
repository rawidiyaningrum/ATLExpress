<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentTrackingResource\Pages\CreateShipmentTracking;
use App\Livewire\TrackingWidget;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Models\User;
use App\Services\ShipmentTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ShipmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => 'AWB-TRK-1',
            'sender_name' => 'PT Kirim',
            'origin' => 'Jakarta',
            'kabupaten_tujuan' => 'Kota Surabaya',
            'destination' => 'Surabaya',
            'status' => Shipment::STATUS_IN_TRANSIT,
            'weight' => 2,
        ], $attributes));
    }

    public function test_tracker_records_a_position_and_the_shipment_status_is_synced(): void
    {
        $shipment = $this->shipment();
        $tracker = User::factory()->tracker()->create();

        Livewire::actingAs($tracker)
            ->test(CreateShipmentTracking::class)
            ->fillForm([
                'shipment_id' => $shipment->id,
                'location' => 'Kota Surabaya',
                'status' => Shipment::STATUS_DELIVERED,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $log = ShipmentLog::sole();

        $this->assertSame($shipment->id, $log->shipment_id);
        $this->assertSame(Shipment::STATUS_DELIVERED, $log->status);
        $this->assertSame(Shipment::STATUS_LABELS[Shipment::STATUS_DELIVERED], $log->status_description);
        $this->assertSame('Kota Surabaya', $log->location);
        $this->assertSame($tracker->id, $log->tracker_user_id);
        $this->assertNotNull($log->timestamp);

        $this->assertSame(Shipment::STATUS_DELIVERED, $shipment->fresh()->status);
    }

    public function test_position_cannot_be_recorded_for_an_inactive_shipment(): void
    {
        $shipment = $this->shipment([
            'awb_number' => 'AWB-TRK-2',
            'status' => Shipment::STATUS_DELIVERED,
        ]);
        $tracker = User::factory()->tracker()->create();

        $this->expectException(HttpException::class);

        app(ShipmentTrackingService::class)->recordStatus(
            $shipment,
            Shipment::STATUS_IN_TRANSIT,
            'Kota Surabaya',
            $tracker->id,
        );
    }

    public function test_shipment_dropdown_orders_active_shipments_and_shows_route_and_sender(): void
    {
        $older = $this->shipment(['awb_number' => 'AWB-OLD']);
        $newer = $this->shipment(['awb_number' => 'AWB-NEW', 'sender_name' => 'PT Kirim']);
        $inactive = $this->shipment(['awb_number' => 'AWB-OFF', 'status' => Shipment::STATUS_DELIVERED]);

        $options = Livewire::actingAs(User::factory()->tracker()->create())
            ->test(CreateShipmentTracking::class)
            ->instance()
            ->form
            ->getComponent(fn ($component): bool => str_ends_with((string) $component->getKey(), 'shipment_id'))
            ->getOptions();

        $this->assertSame([$newer->id, $older->id], array_keys($options));
        $this->assertArrayNotHasKey($inactive->id, $options);
        $this->assertStringContainsString('AWB-NEW', $options[$newer->id]);
        $this->assertStringContainsString('Jakarta → Surabaya', $options[$newer->id]);
        $this->assertStringContainsString('PT Kirim', $options[$newer->id]);
    }

    public function test_public_tracking_shows_the_recorded_position(): void
    {
        $shipment = $this->shipment(['awb_number' => 'AWB-TRK-3']);
        $tracker = User::factory()->tracker()->create();

        app(ShipmentTrackingService::class)
            ->recordStatus($shipment, Shipment::STATUS_DELIVERED, 'Kota Surabaya', $tracker->id);

        Livewire::test(TrackingWidget::class)
            ->set('awb_number', 'AWB-TRK-3')
            ->call('track')
            ->assertSee('Delivered')
            ->assertSee('Kota Surabaya');
    }
}
