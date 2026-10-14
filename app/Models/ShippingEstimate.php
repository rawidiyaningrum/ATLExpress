<?php

namespace App\Models;

use App\Services\ShippingEstimateService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estimasi pengiriman: penawaran tarif untuk satu rute, berisi perbandingan
 * ongkos kirim per jenis armada beserta biaya lain dan pajak. Estimasi tidak
 * memengaruhi stok atau keuangan; ia hanya dokumen penawaran yang bisa dicetak.
 */
class ShippingEstimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_number',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'origin',
        'kabupaten_tujuan',
        'destination',
        'weight',
        'item_type',
        'notes',
        'include_ppn',
        'include_pph',
        'user_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'include_ppn' => 'boolean',
        'include_pph' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function armadas(): HasMany
    {
        return $this->hasMany(ShippingEstimateArmada::class)->orderBy('position')->orderBy('id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ShippingEstimateCharge::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Rincian biaya dan pajak yang berlaku sama untuk semua armada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function chargeRows(): array
    {
        return $this->charges->map(fn (ShippingEstimateCharge $charge): array => [
            'description' => $charge->description,
            'type' => $charge->type,
            'basis' => $charge->basis,
            'quantity' => (int) $charge->quantity,
            'unit_price' => (float) $charge->unit_price,
        ])->all();
    }

    /**
     * Perbandingan tarif per armada, lengkap dengan biaya lain dan pajaknya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function comparison(): array
    {
        return app(ShippingEstimateService::class)->comparison($this);
    }
}
