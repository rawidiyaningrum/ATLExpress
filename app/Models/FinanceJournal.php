<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceJournal extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'reference_label',
        'entry_date',
        'income',
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
        'cost_of_goods' => 'decimal:2',
        'operational_cost' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'profit' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public static function createFromTotals(string $reference, array $totals, ?int $shipmentId = null, ?string $entryDate = null): self
    {
        $income = (float) ($totals['income'] ?? 0);
        $costOfGoods = (float) ($totals['cost_of_goods'] ?? 0);
        $operational = (float) ($totals['operational_cost'] ?? 0);
        $tax = (float) ($totals['tax'] ?? 0);

        $totalExpense = $costOfGoods + $operational + $tax;
        $profit = $income - $totalExpense;

        return static::create([
            'shipment_id' => $shipmentId,
            'reference_label' => $reference,
            'entry_date' => $entryDate ?? now()->toDateString(),
            'income' => $income,
            'cost_of_goods' => $costOfGoods,
            'operational_cost' => $operational,
            'tax' => $tax,
            'total_expense' => $totalExpense,
            'profit' => $profit,
            'profit_percentage' => $income > 0 ? round(($profit / $income) * 100, 2) : 0,
        ]);
    }
}