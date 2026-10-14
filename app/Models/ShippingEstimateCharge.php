<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingEstimateCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_estimate_id',
        'description',
        'type',
        'basis',
        'quantity',
        'unit_price',
        'line_total',
        'position',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(ShippingEstimate::class, 'shipping_estimate_id');
    }
}
