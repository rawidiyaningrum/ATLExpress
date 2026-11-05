<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAgentRoute extends Model
{
    public const RATE_TYPE_CARTER = 'carter';
    public const RATE_TYPE_REGULER = 'reguler';

    public const RATE_TYPES = [
        self::RATE_TYPE_CARTER => 'Carter (Sewa Borongan / Rit)',
        self::RATE_TYPE_REGULER => 'Reguler (Tarif per Kg)',
    ];

    public const FLEET_TYPES = [
        'CDD' => 'CDD (Colt Diesel Double)',
        'CDDL' => 'CDDL (Colt Diesel Double Long)',
        'CDE' => 'CDE (Colt Diesel Engkel)',
        'Fuso Engkel' => 'Fuso Engkel',
        'Fuso Berat' => 'Fuso Berat',
        'Tronton Bak' => 'Tronton Bak',
        'Tronton Wingbox' => 'Tronton Wingbox',
        'Tronton Losbak' => 'Tronton Losbak',
        'Trailer 20 Feet' => 'Trailer 20 Feet',
        'Trailer 40 Feet' => 'Trailer 40 Feet',
        'Blind Van' => 'Blind Van',
        'Pickup' => 'Pickup',
    ];

    protected $fillable = [
        'delivery_agent_id',
        'origin',
        'destination',
        'fleet_type',
        'rate_type',
        'tariff',
        'min_weight',
        'estimated_days',
        'notes',
    ];

    protected $casts = [
        'tariff' => 'decimal:2',
        'min_weight' => 'decimal:2',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id');
    }

    public function scopeCarter(Builder $query): Builder
    {
        return $query->where('rate_type', self::RATE_TYPE_CARTER);
    }

    public function scopeReguler(Builder $query): Builder
    {
        return $query->where('rate_type', self::RATE_TYPE_REGULER);
    }
}

