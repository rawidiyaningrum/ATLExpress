<?php

namespace Tests\Feature;

use App\Services\TariffCalculatorService;
use Database\Seeders\PricelistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarterServiceAliasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PricelistSeeder::class);
    }

    public function test_carter_is_offered_whenever_darat_is_available(): void
    {
        $available = app(TariffCalculatorService::class)
            ->availableServiceTypes('Jakarta', 'Kota Surabaya', 'Surabaya');

        $this->assertArrayHasKey('darat', $available);
        $this->assertArrayHasKey('carter', $available);
        $this->assertSame('Carter', $available['carter']);
    }

    public function test_carter_rate_matches_darat(): void
    {
        $service = app(TariffCalculatorService::class);

        $darat = $service->rateFor('Jakarta', 'Kota Surabaya', 'Surabaya', 'darat', 5);
        $carter = $service->rateFor('Jakarta', 'Kota Surabaya', 'Surabaya', 'carter', 5);

        $this->assertNotNull($carter);
        $this->assertSame('carter', $carter['service_type']);
        $this->assertSame($darat['price_per_kg'], $carter['price_per_kg']);
        $this->assertSame($darat['min_weight'], $carter['min_weight']);
        $this->assertSame($darat['estimated_days'], $carter['estimated_days']);
    }
}
