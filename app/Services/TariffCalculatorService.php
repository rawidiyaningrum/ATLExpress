<?php

namespace App\Services;

use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TariffCalculatorService
{
    public function calculate(string $origin, string $kabupaten, string $destination, float $weight, ?string $serviceType = null): array
    {
        $query = $this->routeQuery($origin, $kabupaten, $destination);

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
     * Tarif untuk satu rute dan satu jenis layanan, dipilih secara deterministik
     * dari tabel shipping_rates.
     *
     * Bila berat di bawah min_weight tier yang terpilih, tier tersebut tetap
     * dikembalikan dengan meets_min_weight = false supaya wizard bisa memberi
     * peringatan alih-alih memblokir operator.
     *
     * @return array{rate_id: int, service_type: string, price_per_kg: float, min_weight: float, estimated_days: string, meets_min_weight: bool}|null
     */
    public function rateFor(string $origin, string $kabupaten, string $destination, string $serviceType, ?float $weight = null): ?array
    {
        $rates = $this->ratesFor($origin, $kabupaten, $destination, $serviceType);

        if ($rates->isEmpty()) {
            return null;
        }

        $rate = $this->tierFor($rates, $weight);

        return [
            'rate_id' => $rate->id,
            'service_type' => $rate->service_type,
            'price_per_kg' => (float) $rate->price_per_kg,
            'min_weight' => (float) $rate->min_weight,
            'estimated_days' => $rate->estimated_days,
            'meets_min_weight' => $weight === null || (float) $rate->min_weight <= $weight,
        ];
    }

    /**
     * Satu tarif per jenis layanan untuk sebuah rute, dipakai tabel
     * perbandingan di langkah 1 wizard.
     *
     * Setiap layanan menghasilkan tepat satu baris, yaitu tier yang sama dengan
     * yang dipilih rateFor() untuk layanan tersebut tanpa berat, sehingga angka
     * di tabel perbandingan dan di langkah 2 tidak pernah berbeda.
     *
     * @return array<string, array{service_type: string, service_label: string, price_per_kg: float, min_weight: float, estimated_days: string, rate_id: int}>
     */
    public function ratesForRoute(string $origin, string $kabupaten, string $destination): array
    {
        if ($origin === '' || $destination === '') {
            return [];
        }

        $rows = $this->routeQuery($origin, $kabupaten, $destination)
            ->orderByDesc('min_weight')
            ->orderBy('id')
            ->get()
            ->groupBy('service_type');

        $labels = $this->serviceTypes();

        return $rows
            ->map(function ($rates) use ($labels): array {
                $rate = $this->tierFor($rates, null);

                return [
                    'service_type' => (string) $rate->service_type,
                    'service_label' => $labels[(string) $rate->service_type] ?? ucfirst((string) $rate->service_type),
                    'price_per_kg' => (float) $rate->price_per_kg,
                    'min_weight' => (float) $rate->min_weight,
                    'estimated_days' => $rate->estimated_days,
                    'rate_id' => $rate->id,
                ];
            })
            ->all();
    }

    /**
     * Layanan yang benar-benar punya tarif untuk sebuah rute, sebagai
     * value => label untuk dropdown.
     *
     * Diambil dari data, bukan dari daftar tetap, supaya layanan tanpa tarif
     * tidak pernah ditawarkan dan layanan baru otomatis muncul begitu diseed.
     *
     * @return array<string, string>
     */
    public function availableServiceTypes(string $origin, string $kabupaten, string $destination): array
    {
        $labels = $this->serviceTypes();
        $available = [];

        foreach (array_keys($this->ratesForRoute($origin, $kabupaten, $destination)) as $serviceType) {
            $available[$serviceType] = $labels[$serviceType] ?? ucfirst($serviceType);
        }

        return $available;
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    protected function ratesFor(string $origin, string $kabupaten, string $destination, string $serviceType): Collection
    {
        return $this->routeQuery($origin, $kabupaten, $destination)
            ->where('service_type', $serviceType)
            ->orderByDesc('min_weight')
            ->orderBy('id')
            ->get();
    }

    /**
     * Query dasar untuk satu rute.
     *
     * Kabupaten bertindak sebagai penyaring tambahan. Bila kabupaten dipilih,
     * baris tanpa kabupaten (mis. tarif udara lama) tetap ikut karena dianggap
     * berlaku untuk semua kabupaten; kota yang sama di kabupaten lain justru
     * disaring keluar supaya harga tidak tertukar.
     */
    protected function routeQuery(string $origin, string $kabupaten, string $destination): Builder
    {
        $query = ShippingRate::where('origin_city', $origin)
            ->where('destination_city', $destination);

        if ($kabupaten !== '') {
            $query->where(function (Builder $inner) use ($kabupaten): void {
                $inner->where('kabupaten_tujuan', $kabupaten)
                    ->orWhereNull('kabupaten_tujuan');
            });
        }

        return $query;
    }

    /**
     * Memilih satu tier dari kandidat yang sudah terurut.
     *
     * Tanpa berat, memakai tier dengan min_weight terkecil sebagai tarif dasar.
     * Dengan berat, memakai tier tertinggi yang masih di bawah berat tersebut,
     * atau tier dasar bila semuanya lebih besar dari berat itu.
     *
     * Kandidat diurutkan min_weight menurun lalu id menaik. Urutan id itu
     * penting karena ada rute dengan dua baris ber-min_weight sama: tanpa itu
     * pilihan baris bergantung urutan basis data dan langkah 1 bisa menampilkan
     * harga yang berbeda dari langkah 2.
     *
     * @param  Collection<int, ShippingRate>  $rates
     */
    protected function tierFor(Collection $rates, ?float $weight): ShippingRate
    {
        $base = $rates->last();

        if ($weight === null) {
            return $base;
        }

        return $rates->first(fn (ShippingRate $candidate): bool => (float) $candidate->min_weight <= $weight)
            ?? $base;
    }

    /**
     * @return array<string, string>
     */
    public function serviceTypes(): array
    {
        return [
            'darat' => 'Darat',
            'laut' => 'Laut',
            'udara' => 'Udara',
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
        return $this->sortCities(ShippingRate::distinct()->pluck('origin_city')->all());
    }

    /**
     * Daftar kabupaten untuk sebuah kota asal.
     *
     * @return array<int, string>
     */
    public function getKabupatens(string $origin): array
    {
        return $this->sortCities(
            ShippingRate::where('origin_city', $origin)
                ->whereNotNull('kabupaten_tujuan')
                ->distinct()
                ->pluck('kabupaten_tujuan')
                ->all()
        );
    }

    /**
     * Seluruh kabupaten yang tersedia di seluruh pricelist, tanpa terikat
     * kota asal. Dipakai dropdown "Lokasi" pada input tracking.
     *
     * @return array<int, string>
     */
    public function getAllKabupatens(): array
    {
        return $this->sortCities(
            ShippingRate::whereNotNull('kabupaten_tujuan')
                ->distinct()
                ->pluck('kabupaten_tujuan')
                ->all()
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAllKabupatenOptions(): array
    {
        $kabupatens = $this->getAllKabupatens();

        return array_combine($kabupatens, $kabupatens);
    }

    public function getDestinations(string $origin, string $kabupaten = ''): array
    {
        $query = ShippingRate::where('origin_city', $origin);

        if ($kabupaten !== '') {
            $query->where('kabupaten_tujuan', $kabupaten);
        }

        return $this->sortCities($query->distinct()->pluck('destination_city')->all());
    }

    /**
     * Mengurutkan kota secara alfabetis tanpa membedakan huruf besar-kecil.
     *
     * Diurutkan di PHP, bukan lewat orderBy, karena collation SQLite dan
     * PostgreSQL berbeda: nama seperti "solo" dan "kab. Lebong" akan menduduki
     * posisi berbeda di tiap driver. Perbandingan sekunder memakai nama aslinya
     * supaya urutannya selalu deterministik.
     *
     * @param  array<int, string>  $cities
     * @return array<int, string>
     */
    private function sortCities(array $cities): array
    {
        usort($cities, fn (string $a, string $b): int => [mb_strtolower($a), $a] <=> [mb_strtolower($b), $b]);

        return array_values($cities);
    }

    /**
     * Opsi value => label untuk dropdown Filament.
     *
     * getOrigins() dan getDestinations() sengaja tetap mengembalikan list
     * biasa karena dipakai widget tarif publik yang relies on isi array sebagai
     * nilai option. Filament justru memakai KEY array sebagai nilai option, jadi
     * list biasa akan membuat nilainya jadi "0", "1", "2", dst.
     *
     * @return array<string, string>
     */
    public function getOriginOptions(): array
    {
        return array_combine($this->getOrigins(), $this->getOrigins());
    }

    /**
     * @return array<string, string>
     */
    public function getKabupatenOptions(string $origin): array
    {
        $kabupatens = $this->getKabupatens($origin);

        return array_combine($kabupatens, $kabupatens);
    }

    /**
     * @return array<string, string>
     */
    public function getDestinationOptions(string $origin, string $kabupaten = ''): array
    {
        $destinations = $this->getDestinations($origin, $kabupaten);

        return array_combine($destinations, $destinations);
    }

    /**
     * Kabupaten yang cocok dengan kata kunci di mana saja dalam nama.
     *
     * Dipakai pencarian bertipe "anywhere" di dropdown Filament: keyword
     * dicocokkan dengan lower(...) LIKE %keyword% supaya huruf besar-kecil
     * tidak berpengaruh dan kata kunci di tengah nama tetap ketemu.
     *
     * @return array<string, string>
     */
    public function searchKabupatenOptions(string $origin, string $search): array
    {
        if ($origin === '' || trim($search) === '') {
            return [];
        }

        $kabupatens = $this->sortCities(
            ShippingRate::where('origin_city', $origin)
                ->whereNotNull('kabupaten_tujuan')
                ->whereRaw('lower(kabupaten_tujuan) like ?', ['%'.mb_strtolower(trim($search)).'%'])
                ->distinct()
                ->pluck('kabupaten_tujuan')
                ->all()
        );

        return array_combine($kabupatens, $kabupatens);
    }

    /**
     * Kota tujuan di bawah sebuah kabupaten yang cocok dengan kata kunci.
     *
     * @return array<string, string>
     */
    public function searchDestinationOptions(string $origin, string $kabupaten, string $search): array
    {
        if ($origin === '' || trim($search) === '') {
            return [];
        }

        $query = ShippingRate::where('origin_city', $origin)
            ->whereRaw('lower(destination_city) like ?', ['%'.mb_strtolower(trim($search)).'%']);

        if ($kabupaten !== '') {
            $query->where('kabupaten_tujuan', $kabupaten);
        }

        $destinations = $this->sortCities($query->distinct()->pluck('destination_city')->all());

        return array_combine($destinations, $destinations);
    }
}
