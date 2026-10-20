<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\FinanceJournalResource;
use App\Filament\Widgets\Concerns\InteractsWithFinancePeriod;
use App\Models\FinanceJournal;
use App\Models\Shipment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentFinanceJournalsTable extends TableWidget
{
    use InteractsWithFinancePeriod;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Jurnal Terbaru')
            ->description('Entri pendapatan dan kas masuk pada periode berjalan. Satu invoice menghasilkan dua baris yang saling menunjuk: pendapatan diakui saat ditagih, kas tercatat saat dilunasi.')
            ->query($this->financeQuery()->with('shipment'))
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (FinanceJournal $record): string => $record->typeLabel())
                    ->color(fn (string $state): string => FinanceJournal::TYPE_COLORS[$state] ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_label')
                    ->label('Referensi')
                    ->placeholder('-')
                    ->url(fn (FinanceJournal $record): ?string => $record->partnerJournal() !== null
                        ? FinanceJournalResource::getUrl('edit', ['record' => $record->partnerJournal()])
                        : null)
                    ->description(fn (FinanceJournal $record): ?string => implode(' · ', array_filter([
                        $record->shipment
                            ? Shipment::statusLabel($record->shipment->status).' · '.$record->shipment->origin.' → '.$record->shipment->destination
                            : null,
                        $record->relatedReferenceDescription(),
                    ]))),
                Tables\Columns\TextColumn::make('income')
                    ->label('Pendapatan')
                    ->money('IDR')
                    ->sortable()
                    // State sengaja bukan null supaya deskripsi "Nominal
                    // tagihan" tetap dirender; formatter money mengembalikan
                    // teks non-angka apa adanya.
                    ->state(fn (FinanceJournal $record) => $record->isReceipt() ? '—' : $record->income)
                    ->placeholder('—')
                    ->description(fn (FinanceJournal $record): ?string => $record->isReceipt() && $record->invoiceTotal() !== null
                        ? 'Nominal tagihan: Rp '.number_format($record->invoiceTotal(), 0, ',', '.')
                        : null),
                Tables\Columns\TextColumn::make('real_income')
                    ->label('Kas Masuk Real')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn (FinanceJournal $record) => $record->isRevenue() ? null : $record->real_income)
                    ->placeholder(fn (FinanceJournal $record): string => $record->kasRealPlaceholder())
                    ->description(fn (FinanceJournal $record): ?string => FinanceJournalResource::realIncomeDescription($record)),
                Tables\Columns\TextColumn::make('cost_of_goods')
                    ->label('Pengeluaran Real')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn (FinanceJournal $record) => $record->isRevenue() ? $record->cost_of_goods : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn (FinanceJournal $record) => $record->isRevenue() ? $record->total_expense : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->sortable()
                    ->state(fn (FinanceJournal $record) => $record->isRevenue() ? $record->profit : null)
                    ->placeholder('—')
                    ->color(fn (?string $state): ?string => $state === null ? null : ((float) $state >= 0 ? 'success' : 'danger')),
                Tables\Columns\TextColumn::make('profit_percentage')
                    ->label('Profit %')
                    ->suffix('%')
                    ->sortable()
                    ->state(fn (FinanceJournal $record) => $record->isRevenue() ? $record->profit_percentage : null)
                    ->placeholder('—'),
            ])
            ->defaultSort('entry_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
