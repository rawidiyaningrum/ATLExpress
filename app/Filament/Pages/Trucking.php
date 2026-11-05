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

    public string $activeTab = 'compare'; // 'compare' atau 'trayek'

    // Properti untuk Tab 1: Cek & Komparasi Rute
    public bool $hasSearched = false;

    public string $searchOrigin = '';

    public string $searchDestination = '';

    public float $searchWeight = 100;

    public Collection $vendorRoutes;

    public Collection $shippingRates;

    public Collection $recentShipments;

    // Properti untuk Tab 2: List Trayek Trucking
    public string $trayekOrigin = '';

    public string $trayekSearch = '';

    public string $trayekSortField = 'destination';

    public string $trayekSortDirection = 'asc';

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

    public function sortBy(string $field): void
    {
        if ($this->trayekSortField === $field) {
            $this->trayekSortDirection = $this->trayekSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trayekSortField = $field;
            $this->trayekSortDirection = 'asc';
        }
    }

    public function selectTrayekOrigin(string $origin): void
    {
        $this->trayekOrigin = $origin;
    }

    public function getTrayekRoutes()
    {
        if (trim($this->trayekOrigin) === '') {
            return collect();
        }

        $origin = trim($this->trayekOrigin);

        $query = DeliveryAgentRoute::query()
            ->with('agent')
            ->whereHas('agent', fn ($q) => $q->where('is_active', true))
            ->where(function ($q) use ($origin) {
                $q->where('origin', 'like', "%{$origin}%")
                    ->orWhere('origin', 'like', '%' . strtolower($origin) . '%');
            });

        if (! empty($this->trayekSearch)) {
            $search = trim($this->trayekSearch);
            $query->where(function ($q) use ($search) {
                $q->where('destination', 'like', "%{$search}%")
                    ->orWhere('fleet_type', 'like', "%{$search}%")
                    ->orWhereHas('agent', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->trayekSortField === 'destination') {
            $query->orderBy('destination', $this->trayekSortDirection);
        } elseif ($this->trayekSortField === 'phone') {
            $query->join('delivery_agents', 'delivery_agent_routes.delivery_agent_id', '=', 'delivery_agents.id')
                ->select('delivery_agent_routes.*')
                ->orderBy('delivery_agents.phone', $this->trayekSortDirection);
        } elseif ($this->trayekSortField === 'vendor') {
            $query->join('delivery_agents', 'delivery_agent_routes.delivery_agent_id', '=', 'delivery_agents.id')
                ->select('delivery_agent_routes.*')
                ->orderBy('delivery_agents.name', $this->trayekSortDirection);
        } elseif ($this->trayekSortField === 'tariff') {
            $query->orderBy('tariff', $this->trayekSortDirection);
        } elseif ($this->trayekSortField === 'fleet_type') {
            $query->orderBy('fleet_type', $this->trayekSortDirection);
        } else {
            $query->orderBy('destination', 'asc');
        }

        return $query->get();
    }
}
