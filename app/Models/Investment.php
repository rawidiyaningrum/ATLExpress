<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    use HasFactory;

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_REIMBURSED = 'reimbursed';

    public const STATUSES = [
        self::STATUS_TRANSFERRED => 'Ditransfer (Transfered)',
        self::STATUS_ONGOING => 'Berjalan (Ongoing)',
        self::STATUS_REIMBURSED => 'Dikembalikan (Reimbursed)',
    ];

    public const STATUS_COLORS = [
        self::STATUS_TRANSFERRED => 'info',
        self::STATUS_ONGOING => 'warning',
        self::STATUS_REIMBURSED => 'success',
    ];

    public const PAYOUT_PENDING = 'pending';

    public const PAYOUT_PAID = 'paid';

    public const PAYOUT_STATUSES = [
        self::PAYOUT_PENDING => 'Belum Dicairkan',
        self::PAYOUT_PAID => 'Sudah Dicairkan',
    ];

    public const PAYOUT_COLORS = [
        self::PAYOUT_PENDING => 'warning',
        self::PAYOUT_PAID => 'success',
    ];

    protected $fillable = [
        'investor_id',
        'shipment_id',
        'project_name',
        'amount',
        'status',
        'investment_date',
        'profit_sharing_percentage',
        'profit_sharing_amount',
        'profit_sharing_date',
        'capital_payout_status',
        'capital_payout_date',
        'profit_payout_status',
        'profit_payout_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'profit_sharing_percentage' => 'decimal:2',
        'profit_sharing_amount' => 'decimal:2',
        'investment_date' => 'date',
        'profit_sharing_date' => 'date',
        'capital_payout_date' => 'date',
        'profit_payout_date' => 'date',
    ];

    protected static function booted(): void
    {
        // Auto-hitung nominal bagi hasil bila persentase & modal terisi dan belum diisi manual
        static::saving(function (self $investment): void {
            if ((float) $investment->profit_sharing_percentage > 0 && (float) $investment->amount > 0) {
                if ($investment->profit_sharing_amount === null || (float) $investment->profit_sharing_amount === 0.0) {
                    $investment->profit_sharing_amount = round(
                        (float) $investment->amount * ((float) $investment->profit_sharing_percentage / 100),
                        2
                    );
                }
            }
        });

        // Sinkronkan jurnal keuangan shipment saat investment dibuat atau diperbarui
        static::saved(function (self $investment): void {
            if ($investment->shipment_id) {
                static::syncShipmentJournal((int) $investment->shipment_id);
            }
        });

        // Sinkronkan jurnal keuangan shipment saat investment dihapus
        static::deleted(function (self $investment): void {
            if ($investment->shipment_id) {
                static::syncShipmentJournal((int) $investment->shipment_id);
            }
        });
    }

    /**
     * Memicu kalkulasi ulang jurnal pendapatan pada shipment terkait.
     */
    public static function syncShipmentJournal(int $shipmentId): void
    {
        $journal = FinanceJournal::query()
            ->where('shipment_id', $shipmentId)
            ->where('journal_type', FinanceJournal::TYPE_REVENUE)
            ->first();

        if ($journal instanceof FinanceJournal) {
            $journal->touch(); // triggers saving & profit recalculation
        }
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function capitalPayoutLabel(): string
    {
        return self::PAYOUT_STATUSES[$this->capital_payout_status] ?? ucfirst((string) $this->capital_payout_status);
    }

    public function profitPayoutLabel(): string
    {
        return self::PAYOUT_STATUSES[$this->profit_payout_status] ?? ucfirst((string) $this->profit_payout_status);
    }

    /**
     * Apakah bagi hasil masih merupakan hutang (belum dicairkan ke investor).
     */
    public function isProfitSharingUnpaid(): bool
    {
        return $this->profit_payout_status !== self::PAYOUT_PAID && (float) $this->profit_sharing_amount > 0;
    }

    /**
     * Apakah pokok/modal investasi masih belum dikembalikan ke investor.
     */
    public function isCapitalUnpaid(): bool
    {
        return $this->capital_payout_status !== self::PAYOUT_PAID && (float) $this->amount > 0;
    }
}
