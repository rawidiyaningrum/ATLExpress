<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\ListShipments;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentListTabsTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(string $status): Shipment
    {
        return Shipment::create([
            'awb_number' => 'AWB-TAB-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 100000,
            'status' => $status,
        ]);
    }

    public function test_shipment_list_shows_status_tabs_like_the_invoice_menu(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->assertSee('Semua')
            ->assertSee('Progress')
            ->assertSee('Delivered');
    }

    public function test_progress_tab_shows_only_in_progress_shipments(): void
    {
        $inTransit = $this->shipment(Shipment::STATUS_IN_TRANSIT);
        $pending = $this->shipment(Shipment::STATUS_PENDING);
        $delivered = $this->shipment(Shipment::STATUS_DELIVERED);
        $draft = $this->shipment(Shipment::STATUS_DRAFT);

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->set('activeTab', 'progress')
            ->assertCanSeeTableRecords([$inTransit, $pending])
            ->assertCanNotSeeTableRecords([$delivered, $draft]);
    }

    public function test_delivered_tab_shows_only_delivered_shipments(): void
    {
        $inTransit = $this->shipment(Shipment::STATUS_IN_TRANSIT);
        $delivered = $this->shipment(Shipment::STATUS_DELIVERED);

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            ->set('activeTab', 'delivered')
            ->assertCanSeeTableRecords([$delivered])
            ->assertCanNotSeeTableRecords([$inTransit]);
    }

    public function test_shipment_list_is_sorted_newest_first(): void
    {
        $older = $this->shipment(Shipment::STATUS_IN_TRANSIT);
        $older->created_at = now()->subDay();
        $older->save();

        $newer = $this->shipment(Shipment::STATUS_IN_TRANSIT);
        $newer->created_at = now();
        $newer->save();

        Livewire::actingAs(User::factory()->create())
            ->test(ListShipments::class)
            // Argumen kedua adalah flag $inOrder, bukan pesan kegagalan.
            ->assertCanSeeTableRecords([$newer, $older], true);
    }
}
