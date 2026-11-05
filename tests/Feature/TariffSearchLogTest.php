<?php

namespace Tests\Feature;

use App\Filament\Resources\TariffSearchLogResource;
use App\Livewire\TariffWidget;
use App\Models\TariffSearchLog;
use App\Models\User;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TariffSearchLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariff_widget_search_creates_log_entry(): void
    {
        $this->seed(PricelistSeeder::class);

        Livewire::test(TariffWidget::class)
            ->set('origin', 'Jakarta')
            ->set('kabupaten', 'Kota Surabaya')
            ->set('destination', 'Surabaya')
            ->set('weight', 10)
            ->call('calculate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tariff_search_logs', [
            'origin' => 'Jakarta',
            'kabupaten' => 'Kota Surabaya',
            'destination' => 'Surabaya',
            'weight' => 10,
        ]);

        $log = TariffSearchLog::first();
        $this->assertNotNull($log);
        $this->assertGreaterThan(0, $log->results_count);
        $this->assertNotNull($log->min_price);
    }

    public function test_tariff_widget_search_handles_no_rates_found(): void
    {
        Livewire::test(TariffWidget::class)
            ->set('origin', 'Jakarta')
            ->set('kabupaten', 'Kota Fiktif')
            ->set('destination', 'Pulau Belum Terdaftar')
            ->set('weight', 5)
            ->call('calculate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tariff_search_logs', [
            'origin' => 'Jakarta',
            'kabupaten' => 'Kota Fiktif',
            'destination' => 'Pulau Belum Terdaftar',
            'weight' => 5,
            'results_count' => 0,
            'min_price' => null,
        ]);
    }

    public function test_admin_can_access_tariff_search_log_resource(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(TariffSearchLogResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_tracker_user_cannot_access_tariff_search_log_resource(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)
            ->get(TariffSearchLogResource::getUrl('index'))
            ->assertForbidden();
    }
}

