<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_TERTAGIH = 'tertagih';

    public const STATUS_LUNAS = 'lunas';

    /**
     * Urutan alur invoice: draft -> tertagih -> lunas.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_TERTAGIH,
        self::STATUS_LUNAS,
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_TERTAGIH => 'Tertagih',
        self::STATUS_LUNAS => 'Lunas',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_TERTAGIH => 'warning',
        self::STATUS_LUNAS => 'success',
    ];

    /**
     * Transisi status yang diizinkan, dikelompokkan per status sekarang.
     *
     * Kunci luar adalah status sekarang, kunci dalam adalah status tujuan.
     * Mundur hanya boleh satu langkah: lunas tidak bisa langsung kembali ke
     * draft, karena jurnal kasnya sudah pernah tercatat.
     *
     * @var array<string, array<string, array{label: string, icon: string, color: string, description: string}>>
     */
    public const STATUS_ACTIONS = [
        self::STATUS_DRAFT => [
            self::STATUS_TERTAGIH => [
                'label' => 'Tandai Tertagih',
                'icon' => 'heroicon-o-document-check',
                'color' => 'warning',
                'description' => 'Invoice akan terkunci dan jurnal pendapatan dibuat. Nominal dan item invoice tidak bisa diubah lagi.',
            ],
        ],
        self::STATUS_TERTAGIH => [
            self::STATUS_LUNAS => [
                'label' => 'Tandai Lunas',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
                'description' => 'Jurnal kas masuk akan dicatat untuk invoice ini. Pendapatan tidak dihitung dua kali.',
            ],
            self::STATUS_DRAFT => [
                'label' => 'Kembalikan ke Draft',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'color' => 'gray',
                'description' => 'Invoice bisa diedit lagi dan jurnal pendapatan dihapus, termasuk modal dan biaya operasional yang sudah diisi.',
            ],
        ],
        self::STATUS_LUNAS => [
            self::STATUS_TERTAGIH => [
                'label' => 'Kembalikan ke Tertagih',
                'icon' => 'heroicon-o-arrow-uturn-left',
                'color' => 'warning',
                'description' => 'Jurnal kas masuk akan dihapus. Jurnal pendapatan tetap tersimpan.',
            ],
        ],
    ];

    protected $fillable = [
        'invoice_number',
        'shipment_id',
        'billed_to_name',
        'billed_to_address',
        'shipping_cost',
        'subtotal',
        'discount',
        'tax',
        'total',
        'status',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function hasAdditionalItems(): bool
    {
        return $this->items()->where('type', '!=', 'shipping')->exists();
    }

    /**
     * Invoice draft masih boleh diubah nominal dan itemnya.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Invoice yang sudah ditagihkan ke pelanggan, apa pun sudah dibayar atau belum.
     */
    public function isBilled(): bool
    {
        return in_array($this->status, [self::STATUS_TERTAGIH, self::STATUS_LUNAS], true);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }

    /**
     * Status setelah invoice ceases ditagih, jadi isinya tidak bisa diedit lagi.
     */
    public function isLocked(): bool
    {
        return ! $this->isDraft();
    }

    /**
     * Status tujuan yang boleh dipilih dari status sekarang.
     *
     * @return array<int, string>
     */
    public function allowedTransitions(): array
    {
        return array_keys(self::STATUS_ACTIONS[$this->status] ?? []);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    /**
     * Label status milik sebuah invoice, untuk kolom tabel dan badge.
     */
    public static function statusLabelFor(?string $status): string
    {
        if ($status === null) {
            return '-';
        }

        return self::STATUS_LABELS[$status] ?? $status;
    }
}
