<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_city',
        'destination_city',
        'service_type',
        'min_weight',
        'price_per_kg',
        'estimated_days',
    ];

    protected $casts = [
        'min_weight' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'estimated_days' => 'integer',
    ];
}
