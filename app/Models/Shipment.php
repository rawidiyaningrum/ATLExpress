<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'sender_name',
        'receiver_name',
        'origin',
        'destination',
        'weight',
        'status',
        'shipping_request_id',
        'final_tariff',
        'final_dimensions',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function logs()
    {
        return $this->hasMany(ShipmentLog::class);
    }

    public function shippingRequest()
    {
        return $this->belongsTo(ShippingRequest::class);
    }
}
