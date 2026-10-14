<?php

namespace Tests\Feature;

use App\Filament\Resources\ShippingEstimateResource\Pages\CreateShippingEstimate;
use App\Filament\Resources\ShippingEstimateResource\Pages\EditShippingEstimate;
use App\Filament\Resources\ShippingEstimateResource\Pages\ViewShippingEstimate;
use App\Models\ShippingEstimate;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ShippingEstimateService;
use App\Support\Code128;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingEstimateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PricelistSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function state(array $overrides = []): array
    {
        return array_merge([
            'sender_name' => 'PT Kirim Sejahtera',
            'sender_phone' => '021-1234567',
            'sender_address' => 'Jl. Industri No. 1, Jakarta',
            'receiver_name' => 'Budi Santoso',
            'receiver_phone' => '0811-2222',
            'receiver_address' => 'Jl. Tujuan No. 9, Surabaya',
            'origin' => 'Jakarta',
            'kabupaten_tujuan' => 'Kota Surabaya',
            'destination' => 'Surabaya',
            'weight' => 100,
            'item_type' => 'Sparepart mesin',
            'include_ppn' => true,
            'include_pph' => true,
            'armadas' => [
                ['service_type' => 'darat', 'price_per_kg' => 10000, 'min_weight' => 1, 'estimated_days' => '3-4'],
                ['service_type' => 'udara', 'price_per_kg' => 25000, 'min_weight' => 1, 'estimated_days' => '1'],
            ],
            'charges' => [
                ['description' => 'Packing Kayu', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 50000],
                ['description' => 'Asuransi', 'type' => 'additional', 'quantity' => 1, 'unit_price' => 100000],
            ],
        ], $overrides);
    }

    public function test_service_creates_numbered_estimate_with_armadas_and_charges(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());

        $this->assertMatchesRegularExpression('/^EST_ATL_\d{8}\d{3}$/', $estimate->estimate_number);
        $this->assertSame(2, $estimate->armadas()->count());
        $this->assertSame(2, $estimate->charges()->count());
        $this->assertSame(100.0, (float) $estimate->weight);
    }

    public function test_comparison_computes_ongkir_biaya_lain_and_taxes_per_armada(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());
        $rows = $estimate->comparison();

        $this->assertCount(2, $rows);

        $darat = $rows[0];
        $this->assertSame('darat', $darat['service_type']);
        $this->assertSame(1000000.0, $darat['shipping_cost'], '100 kg x Rp 10.000');
        $this->assertSame(150000.0, $darat['additional']);
        $this->assertSame(1150000.0, $darat['dpp']);
        $this->assertSame(12650.0, $darat['ppn'], '1,1% dari DPP');
        $this->assertSame(23000.0, $darat['pph'], '2% dari DPP');
        $this->assertSame(1139650.0, $darat['total'], 'DPP - PPh + PPN');

        $udara = $rows[1];
        $this->assertSame(2500000.0, $udara['shipping_cost'], '100 kg x Rp 25.000');
        $this->assertSame(2650000.0, $udara['dpp']);
        $this->assertSame(29150.0, $udara['ppn']);
        $this->assertSame(53000.0, $udara['pph']);
        $this->assertSame(2626150.0, $udara['total'], 'DPP - PPh + PPN');
    }

    public function test_tax_toggles_disable_ppn_and_pph(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state([
            'include_ppn' => false,
            'include_pph' => false,
        ]));

        foreach ($estimate->comparison() as $row) {
            $this->assertSame(0.0, $row['ppn']);
            $this->assertSame(0.0, $row['pph']);
            $this->assertSame($row['dpp'], $row['total']);
        }
    }

    public function test_diskon_charge_reduces_dpp_and_taxes(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state([
            'charges' => [
                ['description' => 'Diskon 5%', 'type' => InvoiceService::TYPE_DISKON, 'quantity' => 1, 'unit_price' => 50000],
            ],
        ]));

        $row = $estimate->comparison()[0];

        $this->assertSame(1000000.0, $row['shipping_cost']);
        $this->assertSame(50000.0, $row['diskon']);
        $this->assertSame(950000.0, $row['dpp'], 'ongkir - diskon');
    }

    public function test_carter_uses_final_tariff_without_per_kg_calculation(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state([
            'armadas' => [
                ['service_type' => 'carter', 'final_tariff' => 3500000, 'min_weight' => 1, 'estimated_days' => '1'],
            ],
        ]));

        $armada = $estimate->armadas()->first();

        $this->assertSame(0.0, (float) $armada->price_per_kg);
        $this->assertSame(3500000.0, (float) $armada->final_tariff);
        $this->assertSame(3500000.0, (float) $armada->shipping_cost, 'tarif final, bukan berat x tarif');

        $row = $estimate->comparison()[0];

        $this->assertTrue($row['is_final']);
        $this->assertSame(0.0, $row['price_per_kg']);
        $this->assertSame(3500000.0, $row['final_tariff']);
        $this->assertSame(3500000.0, $row['shipping_cost']);
        $this->assertSame(3650000.0, $row['dpp'], 'tarif final + biaya lain');

        $preview = app(ShippingEstimateService::class)->preview(
            100,
            [['service_type' => 'carter', 'final_tariff' => 3500000]],
            [],
            false,
            false,
        );

        $this->assertSame(3500000.0, $preview[0]['shipping_cost'], 'pratinjau juga memakai tarif final');
    }

    public function test_choosing_the_route_auto_populates_the_armadas(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateShippingEstimate::class)
            ->set('data.origin', 'Jakarta')
            ->set('data.kabupaten_tujuan', 'Kota Surabaya')
            ->set('data.destination', 'Surabaya')
            ->assertSet('data.armadas', function ($armadas): bool {
                $this->assertNotEmpty($armadas, 'armada terisi otomatis dari tabel tarif');
                $this->assertContains('darat', array_column((array) $armadas, 'service_type'));

                $carter = collect((array) $armadas)->firstWhere('service_type', 'carter');

                if ($carter !== null) {
                    $this->assertNull($carter['final_tariff'], 'carter menunggu tarif final dari operator');
                    $this->assertSame(0, (int) $carter['price_per_kg'], 'carter tidak memakai tarif per kg');
                }

                return true;
            });
    }

    public function test_create_page_generates_an_estimate(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateShippingEstimate::class)
            ->fillForm($this->state())
            ->call('create')
            ->assertHasNoFormErrors();

        $estimate = ShippingEstimate::query()->latest('id')->first();

        $this->assertNotNull($estimate);
        $this->assertSame('PT Kirim Sejahtera', $estimate->sender_name);
        $this->assertSame(2, $estimate->armadas()->count());
        $this->assertSame(2, $estimate->charges()->count());
    }

    public function test_create_page_saves_carter_final_tariff(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateShippingEstimate::class)
            ->fillForm($this->state([
                'armadas' => [
                    ['service_type' => 'carter', 'final_tariff' => 3500000, 'min_weight' => 1, 'estimated_days' => '1'],
                ],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $estimate = ShippingEstimate::query()->latest('id')->first();

        $this->assertNotNull($estimate);
        $this->assertSame(3500000.0, (float) $estimate->armadas()->first()->shipping_cost);
    }

    public function test_edit_page_loads_armadas_charges_and_breakdown(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());

        Livewire::actingAs(User::factory()->create())
            ->test(EditShippingEstimate::class, ['record' => $estimate->getKey()])
            ->assertOk()
            ->assertSet('data.armadas', fn ($value): bool => is_array($value) && count($value) === 2)
            ->assertSet('data.charges', fn ($value): bool => is_array($value) && count($value) === 2)
            ->assertSee('Rp 1.000.000');
    }

    public function test_view_page_renders_the_comparison(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());

        Livewire::actingAs(User::factory()->create())
            ->test(ViewShippingEstimate::class, ['record' => $estimate->getKey()])
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertSee('Darat')
            ->assertSee('Packing Kayu');
    }

    public function test_print_page_renders_the_comparison(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.atlexpress-admin.resources.shipping-estimates.print', ['record' => $estimate]))
            ->assertOk()
            ->assertSee($estimate->estimate_number)
            ->assertSee('Darat')
            ->assertSee('Udara')
            ->assertSee('Perbandingan Tarif per Jenis Armada')
            ->assertSee('diskusi dengan tim ATL Express')
            ->assertSee('ESTIMASI')
            ->assertSee('Total Biaya Lainnya')
            ->assertSee('100 kg')
            ->assertDontSee('Tanda tangan')
            ->assertDontSee('Kota Surabaya')
            ->assertSee('shape-rendering="crispEdges"', false);
    }

    public function test_barcode_svg_is_generated_for_a_code(): void
    {
        $svg = Code128::svg('EST_ATL_20261014001');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertSame('', Code128::svg(''));
    }

    public function test_tracker_cannot_access_estimates(): void
    {
        $estimate = app(ShippingEstimateService::class)->make($this->state());

        $this->actingAs(User::factory()->tracker()->create())
            ->get(route('filament.atlexpress-admin.resources.shipping-estimates.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->tracker()->create())
            ->get(route('filament.atlexpress-admin.resources.shipping-estimates.print', ['record' => $estimate]))
            ->assertForbidden();
    }
}
