<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingEstimateArmada extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_estimate_id',
        'service_type',
        'service_label',
        'price_per_kg',
        'final_tariff',
        'min_weight',
        'estimated_days',
        'shipping_cost',
        'position',
    ];

    protected $casts = [
        'price_per_kg' => 'decimal:2',
        'final_tariff' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(ShippingEstimate::class, 'shipping_estimate_id');
    }
}
