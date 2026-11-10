<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'relation',
        'phone',
        'email',
        'address',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function totalActiveInvestmentAmount(): float
    {
        return (float) $this->investments()
            ->whereIn('status', [Investment::STATUS_TRANSFERRED, Investment::STATUS_ONGOING])
            ->sum('amount');
    }
}
