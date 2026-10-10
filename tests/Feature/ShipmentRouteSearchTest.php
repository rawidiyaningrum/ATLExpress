<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Models\User;
use App\Services\TariffCalculatorService;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentRouteSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PricelistSeeder::class);
    }

    public function test_kabupaten_search_matches_keyword_anywhere_and_case_insensitively(): void
    {
        $results = app(TariffCalculatorService::class)->searchKabupatenOptions('Jakarta', 'gelang');

        $this->assertArrayHasKey('Kab Magelang', $results);
        $this->assertArrayHasKey('Kota Magelang', $results);

        $this->assertSame(
            $results,
            app(TariffCalculatorService::class)->searchKabupatenOptions('Jakarta', 'MAGELANG'),
            'pencarian tidak boleh peduli huruf besar-kecil',
        );
    }

    public function test_destination_search_is_scoped_to_the_selected_kabupaten(): void
    {
        $service = app(TariffCalculatorService::class);

        $this->assertArrayHasKey('Surabaya', $service->searchDestinationOptions('Jakarta', 'Kota Surabaya', 'sura'));
        $this->assertSame([], $service->searchDestinationOptions('Jakarta', 'Kota Surabaya', 'magelang'));
    }

    public function test_wizard_select_returns_server_side_search_results(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(CreateShipment::class)
            ->fillForm(['origin' => 'Jakarta']);

        $results = collect($component->instance()->getFormSelectSearchResults('data.kabupaten_tujuan', 'kepulauan'))
            ->pluck('value')
            ->all();

        $this->assertContains('Kab kepulauan Sangihe', $results);
    }
}
