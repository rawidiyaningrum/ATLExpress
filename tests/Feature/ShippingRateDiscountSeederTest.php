<?php

namespace Tests\Feature;

use App\Models\ShippingRate;
use Database\Seeders\ShippingRateDiscountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingRateDiscountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_rounds_to_the_nearest_thousand(): void
    {
        $this->rate('Meulaboh', 'udara', 1, 60500);
        $this->rate('Meulaboh', 'darat', 50, 15000);
        $this->rate('BlangPidie', 'darat', 50, 13500);
        $this->rate('Singkil', 'darat', 50, 12750);

        $this->seed(ShippingRateDiscountSeeder::class);

        $this->assertSame('30000.00', $this->priceOf('Meulaboh', 'udara'));
        $this->assertSame('8000.00', $this->priceOf('Meulaboh', 'darat'));
        $this->assertSame('7000.00', $this->priceOf('BlangPidie', 'darat'));
        $this->assertSame('6000.00', $this->priceOf('Singkil', 'darat'));
    }

    public function test_free_and_broken_prices_are_not_turned_into_negative_values(): void
    {
        $this->rate('Meulaboh', 'udara', 1, 0);
        $this->rate('BlangPidie', 'udara', 1, -500);

        $this->seed(ShippingRateDiscountSeeder::class);

        $this->assertSame('0.00', $this->priceOf('Meulaboh', 'udara'));
        $this->assertSame('0.00', $this->priceOf('BlangPidie', 'udara'));
    }

    public function test_only_the_price_is_touched(): void
    {
        $rate = $this->rate('Meulaboh', 'darat', 50, 15000, 9);

        $this->seed(ShippingRateDiscountSeeder::class);

        $fresh = ShippingRate::query()->find($rate->id);

        $this->assertSame('8000.00', $fresh->price_per_kg);
        $this->assertSame('50.00', $fresh->min_weight);
        $this->assertSame(9, $fresh->estimated_days);
        $this->assertSame('Meulaboh', $fresh->destination_city);
        $this->assertSame('Jakarta', $fresh->origin_city);
    }

    public function test_running_it_again_stacks_the_discount(): void
    {
        $this->rate('Meulaboh', 'darat', 50, 16000);

        $this->seed(ShippingRateDiscountSeeder::class);
        $this->seed(ShippingRateDiscountSeeder::class);

        $this->assertSame('4000.00', $this->priceOf('Meulaboh', 'darat'));
    }

    public function test_discounted_price_helper_rounds_half_away_from_zero(): void
    {
        $this->assertSame(8000.0, ShippingRateDiscountSeeder::discountedPrice(15000));
        $this->assertSame(7000.0, ShippingRateDiscountSeeder::discountedPrice(13500));
        $this->assertSame(6000.0, ShippingRateDiscountSeeder::discountedPrice(12750));
        $this->assertSame(30000.0, ShippingRateDiscountSeeder::discountedPrice(60500));
        $this->assertSame(2000.0, ShippingRateDiscountSeeder::discountedPrice(3000));
        $this->assertSame(0.0, ShippingRateDiscountSeeder::discountedPrice(0));
    }

    public function test_an_empty_rate_table_is_reported_instead_of_silently_done(): void
    {
        $this->artisan('db:seed', ['--class' => ShippingRateDiscountSeeder::class])
            ->expectsOutputToContain('Tabel tarif kosong')
            ->assertSuccessful();

        $this->assertSame(0, ShippingRate::query()->count());
    }

    private function rate(string $city, string $serviceType, int $minWeight, float $price, int $days = 2): ShippingRate
    {
        return ShippingRate::create([
            'origin_city' => 'Jakarta',
            'destination_city' => $city,
            'service_type' => $serviceType,
            'min_weight' => $minWeight,
            'price_per_kg' => $price,
            'estimated_days' => $days,
        ]);
    }

    private function priceOf(string $city, string $serviceType): string
    {
        return ShippingRate::query()
            ->where('destination_city', $city)
            ->where('service_type', $serviceType)
            ->value('price_per_kg');
    }
}
