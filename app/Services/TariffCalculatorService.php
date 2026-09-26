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

    /**
     * Saran tarif untuk berat tertentu, dengan menghormati min_weight.
     *
     * calculate() sengaja tidak memfilter min_weight karena halaman tarif publik
     * menampilkan seluruh opsi layanan; untuk saran tarif di wizard, tier dengan
     * min_weight di atas berat pengiriman tidak boleh terpilih.
     *
     * @return array{recommended: array|null, options: array<int, array<string, mixed>>}
     */
    public function suggest(string $origin, string $destination, float $weight, ?string $serviceType = null): array
    {
        $query = ShippingRate::where('origin_city', $origin)
            ->where('destination_city', $destination)
            ->where('min_weight', '<=', $weight);

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        $rates = $query->orderByDesc('min_weight')
            ->orderBy('price_per_kg')
            ->get();

        $options = $rates->map(fn (ShippingRate $rate): array => [
            'rate_id' => $rate->id,
            'service_type' => $rate->service_type,
            'min_weight' => (float) $rate->min_weight,
            'price_per_kg' => (float) $rate->price_per_kg,
            'total_price' => $rate->price_per_kg * $weight,
            'estimated_days' => $rate->estimated_days,
        ])->all();

        return [
            'recommended' => $options[0] ?? null,
            'options' => $options,
        ];
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