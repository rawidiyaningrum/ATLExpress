<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OnlyAdmins;
use App\Models\DeliveryAgentRoute;
use App\Models\Shipment;
use App\Models\ShippingRate;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Trucking extends Page implements HasForms
{
    use InteractsWithForms;
    use OnlyAdmins;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Trucking';

    protected static ?string $title = 'Cek Tarif Trucking Vendor & Ongkir Shipment';

    protected static ?string $slug = 'trucking';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.trucking';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public ?array $data = [];

    public bool $hasSearched = false;

    public string $searchOrigin = '';

    public string $searchDestination = '';

    public float $searchWeight = 100;

    public Collection $vendorRoutes;

    public Collection $shippingRates;

    public Collection $recentShipments;

    public function mount(): void
    {
        $this->form->fill([
            'origin' => '',
            'destination' => '',
            'weight' => 100,
        ]);

        $this->vendorRoutes = collect();
        $this->shippingRates = collect();
        $this->recentShipments = collect();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pencarian Rute Trucking & Shipment')
                    ->description('Masukkan kota asal dan kota tujuan untuk mengecek tarif vendor trucking (armada CDD, CDDL, Fuso, Tronton) dan membandingkannya dengan tarif ongkir shipment ATL Express.')
                    ->schema([
                        Forms\Components\TextInput::make('origin')
                            ->label('Kota Asal')
                            ->placeholder('Contoh: Jakarta / Surabaya / Semarang')
                            ->datalist($this->getOriginSuggestions())
                            ->required(),

                        Forms\Components\TextInput::make('destination')
                            ->label('Kota Tujuan')
                            ->placeholder('Contoh: Denpasar / Makassar / Medan')
                            ->datalist($this->getDestinationSuggestions())
                            ->required(),

                        Forms\Components\TextInput::make('weight')
                            ->label('Estimasi Berat Muatan (Kg)')
                            ->numeric()
                            ->default(100)
                            ->suffix('Kg')
                            ->helperText('Digunakan untuk menghitung estimasi ongkir reguler'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function search(): void
    {
        $state = $this->form->getState();

        $this->searchOrigin = trim((string) ($state['origin'] ?? ''));
        $this->searchDestination = trim((string) ($state['destination'] ?? ''));
        $this->searchWeight = (float) ($state['weight'] ?? 100);

        if (empty($this->searchOrigin) || empty($this->searchDestination)) {
            return;
        }

        // 1. Ambil data rute vendor (agen pengiriman) yang aktif
        $this->vendorRoutes = DeliveryAgentRoute::with('agent')
            ->whereHas('agent', fn ($q) => $q->where('is_active', true))
            ->where(function ($q) {
                $q->where('origin', 'like', "%{$this->searchOrigin}%")
                    ->orWhere('origin', 'like', '%' . strtolower($this->searchOrigin) . '%');
            })
            ->where(function ($q) {
                $q->where('destination', 'like', "%{$this->searchDestination}%")
                    ->orWhere('destination', 'like', '%' . strtolower($this->searchDestination) . '%');
            })
            ->orderBy('tariff', 'asc')
            ->get();

        // 2. Ambil master tarif resmi shipment ATL Express
        $this->shippingRates = ShippingRate::query()
            ->where(function ($q) {
                $q->where('origin_city', 'like', "%{$this->searchOrigin}%")
                    ->orWhere('origin_city', 'like', '%' . strtolower($this->searchOrigin) . '%');
            })
            ->where(function ($q) {
                $q->where('destination_city', 'like', "%{$this->searchDestination}%")
                    ->orWhere('destination_city', 'like', '%' . strtolower($this->searchDestination) . '%')
                    ->orWhere('kabupaten_tujuan', 'like', "%{$this->searchDestination}%")
                    ->orWhere('kabupaten_tujuan', 'like', '%' . strtolower($this->searchDestination) . '%');
            })
            ->orderBy('price_per_kg', 'asc')
            ->get();

        // 3. Ambil riwayat shipment terakhir di rute ini dari menu Shipment
        $this->recentShipments = Shipment::query()
            ->where(function ($q) {
                $q->where('origin', 'like', "%{$this->searchOrigin}%")
                    ->orWhere('origin', 'like', '%' . strtolower($this->searchOrigin) . '%');
            })
            ->where(function ($q) {
                $q->where('destination', 'like', "%{$this->searchDestination}%")
                    ->orWhere('destination', 'like', '%' . strtolower($this->searchDestination) . '%')
                    ->orWhere('kabupaten_tujuan', 'like', "%{$this->searchDestination}%")
                    ->orWhere('kabupaten_tujuan', 'like', '%' . strtolower($this->searchDestination) . '%');
            })
            ->latest()
            ->limit(5)
            ->get();

        $this->hasSearched = true;
    }

    public function resetSearch(): void
    {
        $this->form->fill([
            'origin' => '',
            'destination' => '',
            'weight' => 100,
        ]);

        $this->hasSearched = false;
        $this->vendorRoutes = collect();
        $this->shippingRates = collect();
        $this->recentShipments = collect();
    }

    public function getOriginSuggestions(): array
    {
        try {
            $agentOrigins = DeliveryAgentRoute::distinct()->pluck('origin')->toArray();
            $rateOrigins = ShippingRate::distinct()->pluck('origin_city')->toArray();

            return array_values(array_unique(array_filter(array_merge($agentOrigins, $rateOrigins))));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getDestinationSuggestions(): array
    {
        try {
            $agentDestinations = DeliveryAgentRoute::distinct()->pluck('destination')->toArray();
            $rateDestinations = ShippingRate::distinct()->pluck('destination_city')->toArray();

            return array_values(array_unique(array_filter(array_merge($agentDestinations, $rateDestinations))));
        } catch (\Throwable $e) {
            return [];
        }
    }
}
