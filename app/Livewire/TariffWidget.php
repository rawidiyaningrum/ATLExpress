<?php

namespace App\Livewire;

use App\Models\ShippingRequest;
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

    public $showBooking = false;
    public $bookingSuccess = false;

    public $name = '';
    public $phone = '';
    public $email = '';
    public $pickup_address = '';
    public $item_type = '';
    public $dimensions = '';
    public $notes = '';
    public $service_type = '';

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

    public function openBooking()
    {
        if (count($this->results) === 0) {
            return;
        }

        $this->service_type = $this->results[0]['service_type'] ?? '';
        $this->showBooking = true;
        $this->bookingSuccess = false;
    }

    public function closeBooking()
    {
        $this->showBooking = false;
    }

    public function submitBooking()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'pickup_address' => 'required|string',
            'item_type' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.5',
            'dimensions' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'service_type' => 'nullable|string|max:255',
        ]);

        ShippingRequest::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'pickup_address' => $this->pickup_address,
            'item_type' => $this->item_type,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'notes' => $this->notes,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'service_type' => $this->service_type,
            'status' => 'new',
        ]);

        $this->reset([
            'name',
            'phone',
            'email',
            'pickup_address',
            'item_type',
            'dimensions',
            'notes',
            'service_type',
        ]);

        $this->bookingSuccess = true;
    }

    public function render()
    {
        return view('livewire.tariff-widget');
    }
}