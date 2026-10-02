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
            ->description('Entri pendapatan dan kas masuk pada periode berjalan, diklik untuk mencatat pengeluaran real per item invoice.')
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
                    ->description(fn (FinanceJournal $record): ?string => $record->shipment
                        ? Shipment::statusLabel($record->shipment->status).' · '.$record->shipment->origin.' → '.$record->shipment->destination
                        : null),
                Tables\Columns\TextColumn::make('income')
                    ->label('Pendapatan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('real_income')
                    ->label('Kas Masuk Real')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('Belum dicatat')
                    ->description(fn (FinanceJournal $record): ?string => FinanceJournalResource::realIncomeDescription($record)),
                Tables\Columns\TextColumn::make('cost_of_goods')
                    ->label('Pengeluaran Real')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (string $state): string => (float) $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('profit_percentage')
                    ->label('Profit %')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->defaultSort('entry_date', 'desc')
            ->paginated([5, 10, 25]);
    }
}
