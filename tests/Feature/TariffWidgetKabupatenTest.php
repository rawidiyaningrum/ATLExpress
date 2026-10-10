<?php

namespace Tests\Feature;

use App\Livewire\TariffWidget;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TariffWidgetKabupatenTest extends TestCase
{
    use RefreshDatabase;

    public function test_choosing_origin_loads_kabupaten_then_destinations(): void
    {
        $this->seed(PricelistSeeder::class);

        Livewire::test(TariffWidget::class)
            ->set('origin', 'Jakarta')
            ->assertSet('kabupaten', '')
            ->assertSet('destinations', [])
            ->assertSet('kabupatens', fn (array $kabupatens): bool => in_array('Kota Surabaya', $kabupatens, true))
            ->assertSee('Cari kabupaten', escape: false)
            ->set('kabupaten', 'Kota Surabaya')
            ->assertSet('destinations', fn (array $destinations): bool => in_array('Surabaya', $destinations, true))
            ->assertSee('Cari kota tujuan', escape: false);
    }

    public function test_same_city_in_two_kabupaten_uses_the_selected_price(): void
    {
        $this->seed(PricelistSeeder::class);

        Livewire::test(TariffWidget::class)
            ->set('origin', 'Jakarta')
            ->set('kabupaten', 'Kab Magelang')
            ->set('destination', 'Magelang')
            ->set('weight', 10)
            ->call('calculate')
            ->assertSet('results', fn (array $results): bool => collect($results)
                ->firstWhere('service_type', 'darat')['price_per_kg'] == 6800);
    }

    public function test_kabupaten_is_required_before_calculating(): void
    {
        $this->seed(PricelistSeeder::class);

        Livewire::test(TariffWidget::class)
            ->set('origin', 'Jakarta')
            ->set('destination', 'Surabaya')
            ->set('weight', 10)
            ->call('calculate')
            ->assertHasErrors('kabupaten');
    }
}
