<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'status_description',
        'location',
        'tracker_user_id',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function tracker()
    {
        return $this->belongsTo(User::class, 'tracker_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return Shipment::statusLabel($this->status);
    }
}
