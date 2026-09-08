<?php

namespace App\Livewire;

use App\Services\TariffCalculatorService;
use Livewire\Component;

class TariffWidget extends Component
{
    public $origin = '';
    public $destination = '';
    public $weight = 1;
    public $origins = [];
    public $destinations = [];
    public $results = [];

    protected TariffCalculatorService $tariffService;

    public function boot()
    {
        $this->tariffService = app(TariffCalculatorService::class);
        $this->origins = $this->tariffService->getOrigins();
    }

    public function updatedOrigin()
    {
        $this->destinations = $this->tariffService->getDestinations($this->origin);
        $this->destination = '';
    }

    public function calculate()
    {
        $this->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'weight' => 'required|numeric|min:0.5',
        ]);

        $this->results = $this->tariffService->calculate($this->origin, $this->destination, $this->weight);
    }

    public function render()
    {
        return view('livewire.tariff-widget');
    }
}
