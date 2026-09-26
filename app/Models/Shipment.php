<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Urutan status shipment, dipakai form, tabel, dan widget dashboard.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_PENDING => 'warning',
        self::STATUS_IN_TRANSIT => 'info',
        self::STATUS_DELIVERED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    /**
     * Warna heksadesimal untuk grafik dashboard, karena Chart.js tidak
     * menerima nama warna badge Filament.
     *
     * @var array<string, string>
     */
    public const STATUS_CHART_COLORS = [
        self::STATUS_DRAFT => '#9ca3af',
        self::STATUS_PENDING => '#f59e0b',
        self::STATUS_IN_TRANSIT => '#3b82f6',
        self::STATUS_DELIVERED => '#10b981',
        self::STATUS_CANCELLED => '#ef4444',
    ];

    /**
     * Label status milik sebuah shipment, dipakai kolom tabel dan widget.
     */
    public static function statusLabel(?string $status): string
    {
        return $status === null ? '-' : (self::STATUS_LABELS[$status] ?? $status);
    }

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
