<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinanceOverviewStats;
use App\Filament\Widgets\FinanceTrendChart;
use App\Filament\Widgets\RecentShipmentsTable;
use App\Filament\Widgets\ShipmentOverviewStats;
use App\Filament\Widgets\ShipmentStatusChart;
use App\Filament\Widgets\ShipmentTrendChart;
use App\Filament\Widgets\TopRouteChart;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $attributes = []): Shipment
    {
        return Shipment::create(array_merge([
            'awb_number' => 'AWB-DASH-'.Shipment::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 150000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ], $attributes));
    }

    public function test_dashboard_widgets_render(): void
    {
        $this->shipment();

        Livewire::actingAs(User::factory()->create())
            ->test(ShipmentOverviewStats::class)
            ->assertOk()
            ->assertSee('Total Shipment');

        Livewire::actingAs(User::factory()->create())
            ->test(ShipmentStatusChart::class)
            ->assertOk();

        Livewire::actingAs(User::factory()->create())
            ->test(ShipmentTrendChart::class)
            ->assertOk();

        Livewire::actingAs(User::factory()->create())
            ->test(TopRouteChart::class)
            ->assertOk();
    }

    public function test_overview_counts_follow_status_and_invoice_state(): void
    {
        $delivered = $this->shipment(['status' => Shipment::STATUS_DELIVERED]);
        $this->shipment(['status' => Shipment::STATUS_IN_TRANSIT]);
        $this->shipment(['status' => Shipment::STATUS_DRAFT]);
        $this->shipment(['status' => Shipment::STATUS_CANCELLED]);

        // Hanya shipment aktif tanpa invoice yang masuk hitungan.
        $delivered->invoices()->create([
            'invoice_number' => 'INV-DASH-1',
            'subtotal' => 150000,
            'tax' => 0,
            'total' => 150000,
            'status' => Invoice::STATUS_TERTAGIH,
        ]);

        $widget = Livewire::actingAs(User::factory()->create())
            ->test(ShipmentOverviewStats::class)
            ->assertOk();

        $stats = $this->statsFrom($widget->html());

        $this->assertSame(4, $stats['Total Shipment'], 'empat shipment dibuat');
        $this->assertSame(1, $stats[Shipment::STATUS_LABELS[Shipment::STATUS_DELIVERED]]);
        $this->assertSame(1, $stats[Shipment::STATUS_LABELS[Shipment::STATUS_IN_TRANSIT]]);
        $this->assertSame(1, $stats[Shipment::STATUS_LABELS[Shipment::STATUS_DRAFT]]);
        $this->assertSame(2, $stats['Belum Ada Invoice'], 'draft dan in transit tanpa invoice, cancelled tidak dihitung');
    }

    public function test_status_chart_labels_cover_every_status(): void
    {
        $this->shipment(['status' => Shipment::STATUS_PENDING]);

        $html = Livewire::actingAs(User::factory()->create())
            ->test(ShipmentStatusChart::class)
            ->assertOk()
            ->html();

        foreach (Shipment::STATUS_LABELS as $label) {
            $this->assertStringContainsString($label, $html, "label {$label} muncul di grafik");
        }
    }

    public function test_shipment_widgets_are_registered_on_the_default_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(Dashboard::getUrl())
            ->assertSuccessful();

        $widgets = Filament::getPanel('atlexpress-admin')->getWidgets();

        $this->assertContains(ShipmentOverviewStats::class, $widgets);
        $this->assertContains(ShipmentStatusChart::class, $widgets);
        $this->assertContains(ShipmentTrendChart::class, $widgets);
        $this->assertContains(TopRouteChart::class, $widgets);
        $this->assertContains(RecentShipmentsTable::class, $widgets);

        $this->assertNotContains(FinanceOverviewStats::class, $widgets, 'widget finance tidak ikut di dashboard pengiriman');
        $this->assertNotContains(FinanceTrendChart::class, $widgets);
    }

    /**
     * Nilai stat diambil dari markup widget, karena label dan nilainya berada
     * di elemen tabel yang sama.
     *
     * @return array<string, int>
     */
    private function statsFrom(string $html): array
    {
        preg_match_all(
            '/fi-wi-stats-overview-stat-label[^>]*>\s*(?<label>[^<]+?)\s*<\/span>.*?fi-wi-stats-overview-stat-value[^>]*>\s*(?<value>[^<]+?)\s*<\/div>/s',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        $stats = [];

        foreach ($matches as $match) {
            $stats[trim($match['label'])] = (int) trim($match['value']);
        }

        return $stats;
    }
}
