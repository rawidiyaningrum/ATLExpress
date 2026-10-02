<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceJournal extends Model
{
    use HasFactory;

    /**
     * Jurnal pendapatan: pengakuan pendapatan saat invoice ditagihkan.
     */
    public const TYPE_REVENUE = 'revenue';

    /**
     * Jurnal kas: penerimaan kas saat invoice dilunasi.
     */
    public const TYPE_RECEIPT = 'receipt';

    /**
     * @var array<int, string>
     */
    public const TYPES = [
        self::TYPE_REVENUE,
        self::TYPE_RECEIPT,
    ];

    /**
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        self::TYPE_REVENUE => 'Pendapatan',
        self::TYPE_RECEIPT => 'Kas Masuk',
    ];

    /**
     * @var array<string, string>
     */
    public const TYPE_COLORS = [
        self::TYPE_REVENUE => 'info',
        self::TYPE_RECEIPT => 'success',
    ];

    protected $fillable = [
        'shipment_id',
        'reference_label',
        'journal_type',
        'entry_date',
        'income',
        'real_income',
        'cost_of_goods',
        'operational_cost',
        'tax',
        'total_expense',
        'profit',
        'profit_percentage',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'income' => 'decimal:2',
        'real_income' => 'decimal:2',
        'cost_of_goods' => 'decimal:2',
        'operational_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'profit' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
    ];

    /**
     * Total biaya, profit, dan profit persen selalu diturunkan dari modal,
     * biaya operasional, pajak, dan pendapatan supaya tidak bisa diisi manual
     * dengan angka yang tidak konsisten.
     */
    protected static function booted(): void
    {
        static::saving(function (self $journal): void {
            $journal->total_expense = static::totalExpense(
                (float) $journal->cost_of_goods,
                (float) $journal->operational_cost,
                (float) $journal->tax,
            );

            $journal->profit = static::profit((float) $journal->income, $journal->total_expense);
            $journal->profit_percentage = static::percentage($journal->profit, (float) $journal->income);
        });
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * @var Invoice|null
     */
    private $resolvedLinkedInvoice;

    private bool $linkedInvoiceResolved = false;

    /**
     * Invoice asal jurnal ini, kalau ada.
     *
     * Jurnal dan invoice tidak punya foreign key langsung. Keduanya dihubungkan
     * lewat shipment_id dan reference_label, yang berisi nomor invoice, sama
     * seperti yang dipakai InvoiceService saat menyelaraskan jurnal.
     *
     * Hasilnya disimpan di memori karena form jurnal memakainya dari beberapa
     * closure sekaligus pada satu render.
     */
    public function linkedInvoice(): ?Invoice
    {
        if ($this->linkedInvoiceResolved) {
            return $this->resolvedLinkedInvoice;
        }

        $this->linkedInvoiceResolved = true;

        if ($this->reference_label === null) {
            return $this->resolvedLinkedInvoice = null;
        }

        return $this->resolvedLinkedInvoice = Invoice::query()
            ->where('invoice_number', $this->reference_label)
            ->when(
                $this->shipment_id !== null,
                fn ($query) => $query->where('shipment_id', $this->shipment_id),
            )
            ->latest('id')
            ->first();
    }

    public function isReceipt(): bool
    {
        return $this->journal_type === self::TYPE_RECEIPT;
    }

    public function isRevenue(): bool
    {
        return ! $this->isReceipt();
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->journal_type] ?? (string) $this->journal_type;
    }

    /**
     * Nominal tagihan yang menjadi pembanding kas masuk, yaitu total invoice
     * yang menjadi asal jurnal ini.
     *
     * Jurnal kas yang dibuat manual tidak punya invoice, jadi null.
     */
    public function invoiceTotal(): ?float
    {
        $invoice = $this->linkedInvoice();

        return $invoice instanceof Invoice ? (float) $invoice->total : null;
    }

    /**
     * Status "sama dengan nominal invoice" diturunkan dari nominal kas yang
     * tersimpan, bukan disimpan terpisah, supaya tidak ada dua sumber
     * kebenaran untuk hal yang sama.
     *
     * Nominal yang belum pernah dicatat dianggap belum sama, supaya operator
     * memeriksa ulang nominal invoice daripada menerima centang yang bukan
     * miliknya.
     */
    public function realIncomeMatchesInvoice(): bool
    {
        $invoiceTotal = $this->invoiceTotal();

        if ($this->real_income === null || $invoiceTotal === null) {
            return false;
        }

        return round((float) $this->real_income, 2) === round($invoiceTotal, 2);
    }

    /**
     * Total biaya dari modal, biaya operasional, dan pajak.
     */
    public static function totalExpense(float $costOfGoods, float $operationalCost, float $tax): float
    {
        return round(max(0, $costOfGoods) + max(0, $operationalCost) + max(0, $tax), 2);
    }

    /**
     * Pendapatan dikurangi total biaya. Karena pendapatan sudah termasuk pajak
     * dan pajak ikut masuk total biaya, hasilnya adalah profit bersih pajak.
     */
    public static function profit(float $income, float $totalExpense): float
    {
        return round($income - $totalExpense, 2);
    }

    public static function percentage(float $profit, float $income): float
    {
        return $income > 0 ? round(($profit / $income) * 100, 2) : 0.0;
    }

    /**
     * Membuat jurnal baru untuk sebuah referensi.
     *
     * @param  array<string, mixed>  $totals
     */
    public static function createFromTotals(string $reference, array $totals, ?int $shipmentId = null, ?string $entryDate = null, string $type = self::TYPE_REVENUE): self
    {
        return static::create([
            'shipment_id' => $shipmentId,
            'reference_label' => $reference,
            'journal_type' => $type,
            'entry_date' => $entryDate ?? now()->toDateString(),
            'income' => (float) ($totals['income'] ?? 0),
            'cost_of_goods' => (float) ($totals['cost_of_goods'] ?? 0),
            'operational_cost' => (float) ($totals['operational_cost'] ?? 0),
            'tax' => (float) ($totals['tax'] ?? 0),
            'notes' => $totals['notes'] ?? null,
        ]);
    }

    /**
     * Menyelaraskan jurnal milik sebuah referensi tanpa membuat duplikat.
     *
     * Pendapatan dan pajak yang ditimpa karena keduanya berasal dari invoice.
     * Modal, biaya operasional, dan catatan yang sudah diisi operator tetap
     * dipertahankan saat invoice ditagihkan ulang.
     *
     * Tipe jurnal ikut jadi bagian kunci pencarian: satu invoice punya dua
     * jurnal, jurnal pendapatan dan jurnal kas masuk, dan keduanya tidak boleh
     * saling menimpa.
     *
     * cost_of_goods hanya ditimpa kalauTOTAL sengaja menyertakan key itu.
     * Jurnal yang modalnya diisi operator, misalnya jurnal manual tanpa
     * invoice, tidak mengirim key itu supaya nilainya tidak tertimpa.
     *
     * @param  array<string, mixed>  $totals
     */
    public static function syncFromTotals(string $reference, array $totals, ?int $shipmentId = null, ?string $entryDate = null, string $type = self::TYPE_REVENUE): self
    {
        $journal = static::query()->firstOrNew([
            'shipment_id' => $shipmentId,
            'reference_label' => $reference,
            'journal_type' => $type,
        ]);

        $journal->income = (float) ($totals['income'] ?? 0);
        $journal->tax = (float) ($totals['tax'] ?? 0);
        $journal->entry_date = $entryDate ?? $journal->entry_date ?? now()->toDateString();

        if (array_key_exists('cost_of_goods', $totals)) {
            $journal->cost_of_goods = (float) $totals['cost_of_goods'];
        }

        $journal->save();

        return $journal->refresh();
    }

    /**
     * Menghapus seluruh jurnal milik sebuah referensi, atau hanya satu tipenya.
     */
    public static function deleteForReference(string $reference, ?int $shipmentId = null, ?string $type = null): int
    {
        return static::query()
            ->where('shipment_id', $shipmentId)
            ->where('reference_label', $reference)
            ->when($type !== null, fn ($query) => $query->where('journal_type', $type))
            ->delete();
    }

    /**
     * Jurnal yang berasal dari invoice yang sudah ditagihkan tidak boleh dihapus,
     * karena modal dan biaya operasional di dalamnya akan hilang dan invoice
     * yang sudah ditagihkan tidak dapat diedit lagi.
     */
    public function isLinkedToBilledInvoice(): bool
    {
        if ($this->shipment_id === null) {
            return false;
        }

        return Invoice::query()
            ->where('shipment_id', $this->shipment_id)
            ->whereIn('status', [Invoice::STATUS_TERTAGIH, Invoice::STATUS_LUNAS])
            ->exists();
    }
}
