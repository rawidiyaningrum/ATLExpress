<?php

namespace App\Services;

use App\Models\ShippingRate;

class TariffCalculatorService
{
    public function calculate(string $origin, string $destination, float $weight, ?string $serviceType = null): array
    {
        $query = ShippingRate::where('origin_city', $origin)
            ->where('destination_city', $destination);

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        $rates = $query->get();

        $results = $rates->map(function ($rate) use ($weight) {
            $totalPrice = $rate->price_per_kg * $weight;
            return [
                'service_type' => $rate->service_type,
                'price_per_kg' => $rate->price_per_kg,
                'total_price' => $totalPrice,
                'estimated_days' => $rate->estimated_days,
            ];
        });

        return $results->toArray();
    }

    public function getAvailableCities(): array
    {
        $origins = ShippingRate::distinct()->pluck('origin_city')->toArray();
        $destinations = ShippingRate::distinct()->pluck('destination_city')->toArray();
        return array_unique(array_merge($origins, $destinations));
    }

    public function getOrigins(): array
    {
        return ShippingRate::distinct()->pluck('origin_city')->toArray();
    }

    public function getDestinations(string $origin): array
    {
        return ShippingRate::where('origin_city', $origin)->distinct()->pluck('destination_city')->toArray();
    }
}