<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'awb_number',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'origin',
        'destination',
        'service_type',
        'weight',
        'status',
        'shipping_request_id',
        'final_tariff',
        'price_per_kg',
        'dimension_length',
        'dimension_width',
        'dimension_height',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
        'final_tariff' => 'decimal:2',
        'dimension_length' => 'decimal:2',
        'dimension_width' => 'decimal:2',
        'dimension_height' => 'decimal:2',
    ];

    public function logs()
    {
        return $this->hasMany(ShipmentLog::class);
    }

    /**
     * Panjang x lebar x tinggi dalam cm, atau null bila dimensi belum lengkap.
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->dimension_length === null || $this->dimension_width === null || $this->dimension_height === null) {
            return null;
        }

        return sprintf(
            '%s x %s x %s',
            rtrim(rtrim((string) $this->dimension_length, '0'), '.'),
            rtrim(rtrim((string) $this->dimension_width, '0'), '.'),
            rtrim(rtrim((string) $this->dimension_height, '0'), '.'),
        );
    }

    public function shippingRequest()
    {
        return $this->belongsTo(ShippingRequest::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Invoice terbaru milik shipment ini.
     *
     * Satu shipment idealnya hanya punya satu invoice, jadi yang terbaru yang
     * dipakai. Relasi ini bisa di-eager load saat menampilkan daftar shipment.
     */
    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany('id');
    }

    public function financeJournals()
    {
        return $this->hasMany(FinanceJournal::class);
    }
}
