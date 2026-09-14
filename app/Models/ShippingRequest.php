<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'pickup_address',
        'item_type',
        'weight',
        'dimensions',
        'notes',
        'origin',
        'destination',
        'service_type',
        'status',
        'final_tariff',
        'final_dimensions',
        'final_weight',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'final_tariff' => 'decimal:2',
        'final_weight' => 'decimal:2',
    ];

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}