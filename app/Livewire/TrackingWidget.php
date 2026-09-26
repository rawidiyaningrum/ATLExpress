<?php

namespace App\Livewire;

use App\Services\ShipmentTrackingService;
use Livewire\Component;

class TrackingWidget extends Component
{
    public $awb_number = '';

    public $result = null;

    public $error = '';

    protected ShipmentTrackingService $trackingService;

    public function boot()
    {
        $this->trackingService = app(ShipmentTrackingService::class);
    }

    public function track()
    {
        $this->validate(['awb_number' => 'required|string|min:5']);
        $this->result = $this->trackingService->trackByAwb($this->awb_number);
        $this->error = $this->result ? '' : 'Nomor resi tidak ditemukan.';
    }

    public function render()
    {
        return view('livewire.tracking-widget');
    }
}
