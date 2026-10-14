<?php

namespace App\Services;

use App\Models\ShippingEstimate;
use App\Models\ShippingEstimateArmada;
use App\Support\NumberGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan dan penyimpanan estimasi pengiriman.
 *
 * Estimasi adalah penawaran tarif: satu rute dengan beberapa jenis armada,
 * biaya lain yang bersifat pilihan, dan pajak yang mengikuti aturan invoice.
 * Biaya lain disimpan sebagai baris tetap, sedangkan pajak dihitung ulang per
 * armada karena dasar pengenaannya berbeda-beda mengikuti ongkos kirim.
 */
class ShippingEstimateService
{
    /**
     * Layanan yang harganya borongan: ongkos kirim diisi langsung sebagai tarif
     * final, bukan dihitung dari berat x tarif per kg.
     *
     * @var array<int, string>
     */
    public const FINAL_TARIFF_SERVICES = ['carter'];

    /**
     * Biaya lain bawaan yang bisa ditambahkan cepat dari form.
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public function additionalCostPresets(): array
    {
        return [
            ['label' => 'Packing Kayu', 'amount' => 50000],
            ['label' => 'Asuransi', 'amount' => 100000],
            ['label' => 'Loading Tim', 'amount' => 75000],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function serviceLabels(): array
    {
        return app(TariffCalculatorService::class)->serviceTypes();
    }

    public function labelForService(string $serviceType): string
    {
        return $this->serviceLabels()[$serviceType] ?? ucfirst($serviceType);
    }

    /**
     * Ongkos kirim = berat x tarif per kg. Berat atau tarif nol menghasilkan nol.
     */
    public function shippingCost(float $weight, float $pricePerKg): float
    {
        if ($weight <= 0 || $pricePerKg <= 0) {
            return 0.0;
        }

        return round($weight * $pricePerKg, 2);
    }

    /**
     * Apakah layanan ini memakai tarif final borongan, bukan tarif per kg.
     */
    public function usesFinalTariff(string $serviceType): bool
    {
        return in_array($serviceType, self::FINAL_TARIFF_SERVICES, true);
    }

    /**
     * Menyimpan estimasi baru, lengkap dengan armada dan biaya lainnya.
     *
     * @param  array<string, mixed>  $state
     */
    public function make(array $state): ShippingEstimate
    {
        $attributes = $this->attributes($state);
        $armadas = $this->armadaRows($state);
        $charges = $this->chargeRows($state);

        $estimate = app(NumberGenerator::class)->persistWithRetry(
            'estimateNumber',
            function (string $number) use ($attributes, $armadas, $charges): ShippingEstimate {
                $estimate = ShippingEstimate::create($attributes + [
                    'estimate_number' => $number,
                    'user_id' => auth()->id(),
                ]);

                $this->replaceChildren($estimate, $armadas, $charges);

                return $estimate;
            },
        );

        return $estimate->refresh();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function update(ShippingEstimate $estimate, array $state): ShippingEstimate
    {
        return DB::transaction(function () use ($estimate, $state): ShippingEstimate {
            $estimate->fill($this->attributes($state))->save();

            $this->replaceChildren($estimate, $this->armadaRows($state), $this->chargeRows($state));

            return $estimate->refresh();
        });
    }

    /**
     * Perbandingan tarif milik estimasi yang sudah tersimpan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function comparison(ShippingEstimate $estimate): array
    {
        $charges = $estimate->chargeRows();
        $labels = $this->serviceLabels();
        $includePpn = (bool) $estimate->include_ppn;
        $includePph = (bool) $estimate->include_pph;

        return $estimate->armadas
            ->map(function (ShippingEstimateArmada $armada) use ($labels, $charges, $includePpn, $includePph): array {
                $isFinal = $this->usesFinalTariff((string) $armada->service_type);

                return $this->armadaBreakdown(
                    (string) $armada->service_type,
                    $armada->service_label ?: ($labels[$armada->service_type] ?? ucfirst((string) $armada->service_type)),
                    (float) $armada->price_per_kg,
                    $armada->estimated_days,
                    (float) $armada->shipping_cost,
                    $charges,
                    $includePpn,
                    $includePph,
                    $isFinal,
                    $isFinal ? (float) $armada->final_tariff : null,
                );
            })
            ->all();
    }

    /**
     * Perbandingan dari state form yang belum disimpan, untuk pratinjau live.
     *
     * @param  array<int, array<string, mixed>>  $armadas
     * @param  array<int, array<string, mixed>>  $charges
     * @return array<int, array<string, mixed>>
     */
    public function preview(float $weight, array $armadas, array $charges, bool $includePpn = true, bool $includePph = true): array
    {
        $chargeRows = $this->normaliseChargeRows($charges);
        $labels = $this->serviceLabels();
        $rows = [];

        foreach ($armadas as $armada) {
            $serviceType = trim((string) ($armada['service_type'] ?? ''));

            if ($serviceType === '') {
                continue;
            }

            $isFinal = $this->usesFinalTariff($serviceType);
            $price = $isFinal ? 0.0 : (float) ($armada['price_per_kg'] ?? 0);
            $finalTariff = $isFinal ? (float) ($armada['final_tariff'] ?? 0) : null;
            $shipping = $isFinal ? round((float) $finalTariff, 2) : $this->shippingCost($weight, $price);

            $rows[] = $this->armadaBreakdown(
                $serviceType,
                $labels[$serviceType] ?? ucfirst($serviceType),
                $price,
                $armada['estimated_days'] ?? null,
                $shipping,
                $chargeRows,
                $includePpn,
                $includePph,
                $isFinal,
                $finalTariff,
            );
        }

        return $rows;
    }

    /**
     * Rincian satu armada: ongkos kirim ditambah biaya lain, lalu PPN dan PPh.
     *
     * PPN dan PPh dihitung dari Dasar Pengenaan Pajak (DPP) armada tersebut,
     * sama seperti perhitungan invoice, sehingga nominalnya berbeda antar armada.
     *
     * @param  array<int, array<string, mixed>>  $charges
     * @return array<string, mixed>
     */
    protected function armadaBreakdown(
        string $serviceType,
        string $label,
        float $pricePerKg,
        ?string $estimatedDays,
        float $shippingCost,
        array $charges,
        bool $includePpn,
        bool $includePph,
        bool $isFinal = false,
        ?float $finalTariff = null,
    ): array {
        $totals = app(InvoiceService::class)->calculate($shippingCost, $charges);
        $dpp = $totals['subtotal'];
        $ppn = $includePpn ? round($dpp * InvoiceService::PPN_RATE, 2) : 0.0;
        $pph = $includePph ? round($dpp * InvoiceService::PPH_RATE, 2) : 0.0;

        return [
            'service_type' => $serviceType,
            'service_label' => $label,
            'is_final' => $isFinal,
            'price_per_kg' => round($pricePerKg, 2),
            'final_tariff' => $isFinal ? round((float) $finalTariff, 2) : null,
            'estimated_days' => $estimatedDays,
            'shipping_cost' => $totals['shipping'],
            'additional' => $totals['additional'],
            'diskon' => $totals['diskon'],
            'dpp' => $dpp,
            'ppn' => $ppn,
            'pph' => $pph,
            'total' => round($dpp - $pph + $ppn, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function attributes(array $state): array
    {
        $weight = $state['weight'] ?? null;

        return [
            'sender_name' => $state['sender_name'] ?? null,
            'sender_phone' => $state['sender_phone'] ?? null,
            'sender_address' => $state['sender_address'] ?? null,
            'receiver_name' => $state['receiver_name'] ?? null,
            'receiver_phone' => $state['receiver_phone'] ?? null,
            'receiver_address' => $state['receiver_address'] ?? null,
            'origin' => $state['origin'] ?? null,
            'kabupaten_tujuan' => $state['kabupaten_tujuan'] ?? null,
            'destination' => $state['destination'] ?? null,
            'weight' => ($weight === '' || $weight === null) ? null : $weight,
            'item_type' => $state['item_type'] ?? null,
            'notes' => $state['notes'] ?? null,
            'include_ppn' => (bool) ($state['include_ppn'] ?? true),
            'include_pph' => (bool) ($state['include_pph'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function armadaRows(array $state): array
    {
        $weight = (float) ($state['weight'] ?? 0);
        $labels = $this->serviceLabels();
        $rows = [];
        $position = 0;

        foreach ((array) ($state['armadas'] ?? []) as $armada) {
            $serviceType = trim((string) ($armada['service_type'] ?? ''));

            if ($serviceType === '') {
                continue;
            }

            $isFinal = $this->usesFinalTariff($serviceType);
            $price = $isFinal ? 0.0 : round((float) ($armada['price_per_kg'] ?? 0), 2);
            $finalTariff = $isFinal ? round((float) ($armada['final_tariff'] ?? 0), 2) : null;

            $rows[] = [
                'service_type' => $serviceType,
                'service_label' => $labels[$serviceType] ?? ucfirst($serviceType),
                'price_per_kg' => $price,
                'final_tariff' => $finalTariff,
                'min_weight' => round((float) ($armada['min_weight'] ?? 1), 2),
                'estimated_days' => $armada['estimated_days'] ?? null,
                'shipping_cost' => $isFinal ? $finalTariff : $this->shippingCost($weight, $price),
                'position' => $position++,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function chargeRows(array $state): array
    {
        $rows = [];

        foreach ($this->normaliseChargeRows((array) ($state['charges'] ?? [])) as $position => $charge) {
            $rows[] = $charge + ['position' => $position];
        }

        return $rows;
    }

    /**
     * Baris biaya lain yang sudah bersih dan siap dihitung.
     *
     * Memakai normalisasi invoice supaya perilakunya sama: baris tanpa
     * keterangan dibuang dan jenis yang tidak dikenal diperlakukan sebagai
     * biaya tambahan.
     *
     * @param  array<int, array<string, mixed>>  $charges
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseChargeRows(array $charges): array
    {
        return app(InvoiceService::class)->normaliseItems($charges);
    }

    /**
     * Menghapus lalu membuat ulang baris armada dan biaya milik estimasi.
     *
     * Repeater tidak menyisakan identifier stabil per baris, jadi cara paling
     * aman adalah menyinkronkan penuh.
     *
     * @param  array<int, array<string, mixed>>  $armadas
     * @param  array<int, array<string, mixed>>  $charges
     */
    protected function replaceChildren(ShippingEstimate $estimate, array $armadas, array $charges): void
    {
        $estimate->armadas()->delete();
        $estimate->charges()->delete();

        foreach ($armadas as $armada) {
            $estimate->armadas()->create($armada);
        }

        foreach ($charges as $charge) {
            $estimate->charges()->create($charge);
        }
    }
}
