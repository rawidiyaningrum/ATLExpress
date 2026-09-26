<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'awb_number',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'origin',
        'destination',
        'weight',
        'status',
        'shipping_request_id',
        'final_tariff',
        'final_dimensions',
        'price_per_kg',
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function financeJournals()
    {
        return $this->hasMany(FinanceJournal::class);
    }
}
