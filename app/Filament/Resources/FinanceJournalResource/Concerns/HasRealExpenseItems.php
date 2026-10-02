<?php

namespace App\Filament\Resources\FinanceJournalResource\Concerns;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Forms;

/**
 * Baris pengeluaran real pada form jurnal keuangan.
 *
 * Barisnya diambil dari invoice yang terkait: ongkos kirim lebih dulu, lalu
 * item invoice. Urutannya sama dengan invoice cetak supaya operator mudah
 * mencocokkan.
 *
 * Hanya ongkos kirim dan biaya tambahan yang punya kolom pengeluaran real.
 * Baris diskon dan pajak tetap ditampilkan supaya operator tahu item itu ada
 * di invoice, tapi keduanya read-only dan tidak ikut dijumlahkan ke modal:
 * diskon adalah pengurangan tagihan, sedangkan pajak sudah dibukukan lewat
 * kolom tax di jurnal keuangan sehingga ikut dihitung akan dobel.
 */
trait HasRealExpenseItems
{
    /**
     * Baris ongkos kirim, yang tidak punya baris di tabel invoice_items
     * karena nominalnya diambil dari invoices.shipping_cost.
     */
    public const EXPENSE_SOURCE_SHIPPING = 'shipping';

    /**
     * Baris item invoice bertipe biaya tambahan.
     */
    public const EXPENSE_SOURCE_ADDITIONAL = 'additional';

    /**
     * Baris informatif saja: diskon dan pajak.
     */
    public const EXPENSE_SOURCE_READ_ONLY = 'readonly';

    /**
     * State path repeater pengeluaran real.
     */
    public static function expenseItemsStatePath(): string
    {
        return 'expense_items';
    }

    public static function realExpenseSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Pengeluaran Real per Item Invoice')
            // Aksi form component hanya bisa dipasang kalau komponen induknya
            // punya key, tanpa itu tombolnya tidak dirender sama sekali.
            ->key('pengeluaran-real-item-invoice')
            ->description('Isi nominal yang benar-benar dibayar untuk tiap item. Baris diskon dan pajak hanya ditampilkan sebagai informasi. Centang "sama dengan modal" untuk memakai nominal invoice tanpa mengetik angka.')
            ->headerActions([
                Forms\Components\Actions\Action::make('isiSemuaSamaDenganModal')
                    ->label('Isi Semua Sama Dengan Modal')
                    ->icon('heroicon-m-check')
                    ->color('gray')
                    ->action(function (Forms\Get $get, Forms\Set $set): void {
                        $set(
                            static::expenseItemsStatePath(),
                            static::fillEveryRowAsModal($get(static::expenseItemsStatePath())),
                        );
                    }),
            ])
            ->schema([
                Forms\Components\Repeater::make(static::expenseItemsStatePath())
                    ->hiddenLabel()
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columns(4)
                    ->schema([
                        Forms\Components\Hidden::make('source'),
                        Forms\Components\Hidden::make('invoice_item_id'),
                        Forms\Components\TextInput::make('description')
                            ->label('Keterangan')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('line_amount')
                            ->label('Modal')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('Rp'),
                        Forms\Components\Checkbox::make('same_as_modal')
                            ->label('Sama dengan modal')
                            ->visible(fn (Forms\Get $get): bool => static::rowIsEditable($get('source')))
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?bool $state): void {
                                // Dicentang berarti pengeluaran riilnya sama
                                // dengan modal di invoice, jadi angka di field
                                // pengeluaran real tidak perlu diketik. Melepas
                                // centangnya berarti operator mengisinya sendiri.
                                $set('real_expense', $state ? $get('line_amount') : null);
                            }),
                        Forms\Components\TextInput::make('real_expense')
                            ->label('Pengeluaran Real')
                            ->visible(fn (Forms\Get $get): bool => static::rowIsEditable($get('source')))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true),
                    ]),
                Forms\Components\Placeholder::make('real_expense_total_preview')
                    ->label('Total Pengeluaran Real')
                    ->content(fn (Forms\Get $get): string => static::rupiah(
                        static::sumEditableRows($get(static::expenseItemsStatePath())),
                    )),
            ]);
    }

    /**
     * Baris yang punya kolom pengeluaran real.
     */
    public static function rowIsEditable(mixed $source): bool
    {
        return $source === self::EXPENSE_SOURCE_SHIPPING
            || $source === self::EXPENSE_SOURCE_ADDITIONAL;
    }

    /**
     * Menyusun baris form dari invoice yang terkait dengan jurnal.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function rowsFromInvoice(Invoice $invoice): array
    {
        $shippingCost = (float) $invoice->shipping_cost;
        $shippingRealExpense = static::recordedExpense($invoice->shipping_real_expense);

        $rows = [[
            'source' => self::EXPENSE_SOURCE_SHIPPING,
            'invoice_item_id' => null,
            'description' => 'Ongkos kirim',
            'line_amount' => $shippingCost,
            'same_as_modal' => static::expenseMatchesLineAmount($shippingRealExpense, $shippingCost),
            'real_expense' => $shippingRealExpense,
        ]];

        foreach ($invoice->items as $item) {
            $source = static::sourceForItemType($item->type);
            $editable = static::rowIsEditable($source);
            $lineTotal = (float) $item->line_total;
            $realExpense = static::recordedExpense($item->real_expense);

            $rows[] = [
                'source' => $source,
                'invoice_item_id' => $item->id,
                'description' => $item->description,
                'line_amount' => $lineTotal,
                'same_as_modal' => $editable && static::expenseMatchesLineAmount($realExpense, $lineTotal),
                'real_expense' => $editable ? $realExpense : null,
            ];
        }

        return $rows;
    }

    /**
     * Hanya biaya tambahan yang boleh diisi. Diskon dan pajak jadi read-only.
     */
    protected static function sourceForItemType(?string $type): string
    {
        return $type === 'additional'
            ? self::EXPENSE_SOURCE_ADDITIONAL
            : self::EXPENSE_SOURCE_READ_ONLY;
    }

    /**
     * Pengeluaran real yang belum pernah dicatat harus tampil kosong, bukan
     * nol. Kalau tampil nol, operator bisa menyimpan jurnal dengan modal nol
     * tanpa sadar datanya belum diisi, dan nol seperti itu tidak bisa
     * dibedakan dari pengeluaran riil yang memang bernilai nol.
     */
    protected static function recordedExpense(mixed $amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        $amount = round((float) $amount, 2);

        return $amount > 0 ? $amount : null;
    }

    /**
     * Status "sama dengan modal" diturunkan dari nominalnya, bukan disimpan
     * terpisah, supaya tidak ada dua sumber kebenaran untuk hal yang sama.
     */
    protected static function expenseMatchesLineAmount(mixed $realExpense, float $lineAmount): bool
    {
        if ($realExpense === null) {
            return false;
        }

        return round((float) $realExpense, 2) === round($lineAmount, 2);
    }

    /**
     * Centang dan isi semua baris yang boleh diedit dengan nilai modalnya.
     * Baris diskon dan pajak dibiarkan apa adanya.
     *
     * @param  array<int|string, mixed>  $rows
     * @return array<int|string, mixed>
     */
    public static function fillEveryRowAsModal(array $rows): array
    {
        $filled = [];

        foreach ($rows as $key => $row) {
            if (! is_array($row) || ! static::rowIsEditable($row['source'] ?? null)) {
                $filled[$key] = $row;

                continue;
            }

            $filled[$key] = array_merge($row, [
                'same_as_modal' => true,
                'real_expense' => $row['line_amount'] ?? null,
            ]);
        }

        return $filled;
    }

    /**
     * Jumlah pengeluaran real dari baris yang boleh diedit saja.
     */
    public static function sumEditableRows(mixed $rows): float
    {
        if (! is_array($rows)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($rows as $row) {
            if (! is_array($row) || ! static::rowIsEditable($row['source'] ?? null)) {
                continue;
            }

            $total += max(0, (float) ($row['real_expense'] ?? 0));
        }

        return round($total, 2);
    }

    /**
     * Menyimpan pengeluaran real baris per baris ke invoice.
     *
     * Baris ongkir kirim ditulis ke invoices, baris item ditulis ke
     * invoice_items berdasarkan id-nya. Baris diskon dan pajak dilewati, jadi
     * nilainya di invoice tetap apa adanya.
     *
     * @param  array<int|string, mixed>  $rows
     */
    public static function persistRows(Invoice $invoice, array $rows): void
    {
        $shippingRealExpense = (float) $invoice->shipping_real_expense;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $source = $row['source'] ?? null;

            if ($source === self::EXPENSE_SOURCE_SHIPPING) {
                $shippingRealExpense = max(0, (float) ($row['real_expense'] ?? 0));

                continue;
            }

            if ($source !== self::EXPENSE_SOURCE_ADDITIONAL) {
                continue;
            }

            $itemId = $row['invoice_item_id'] ?? null;

            if ($itemId === null) {
                continue;
            }

            InvoiceItem::query()
                ->where('invoice_id', $invoice->id)
                ->whereKey($itemId)
                ->update(['real_expense' => round(max(0, (float) ($row['real_expense'] ?? 0)), 2)]);
        }

        $invoice->update(['shipping_real_expense' => round($shippingRealExpense, 2)]);
    }

    protected static function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
