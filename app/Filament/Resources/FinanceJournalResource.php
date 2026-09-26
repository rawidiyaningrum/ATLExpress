<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceJournalResource\Pages;
use App\Models\FinanceJournal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinanceJournalResource extends Resource
{
    protected static ?string $model = FinanceJournal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Jurnal Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'jurnal keuangan';

    protected static ?string $pluralModelLabel = 'jurnal keuangan';

    protected static ?string $recordTitleAttribute = 'reference_label';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Periode & Referensi')
                ->schema([
                    Forms\Components\DatePicker::make('entry_date')
                        ->label('Tanggal Catat')
                        ->required()
                        ->default(now()),
                    Forms\Components\Select::make('shipment_id')
                        ->label('Shipment')
                        ->relationship('shipment', 'awb_number')
                        ->searchable()
                        ->preload()
                        ->helperText('Kosongkan untuk catatan yang tidak terkait shipment.'),
                    Forms\Components\Select::make('journal_type')
                        ->label('Tipe Jurnal')
                        ->options(FinanceJournal::TYPE_LABELS)
                        ->default(FinanceJournal::TYPE_REVENUE)
                        ->required()
                        ->helperText('Kas masuk dicatat otomatis saat invoice dilunasi.'),
                    Forms\Components\TextInput::make('reference_label')
                        ->label('Referensi')
                        ->maxLength(255)
                        ->helperText('Terisi otomatis dengan nomor invoice untuk jurnal dari invoice yang ditagihkan.'),
                ])->columns(2),
            Forms\Components\Section::make('Nilai')
                ->description('Total biaya, profit, dan profit persen dihitung ulang dari nominal di bawah saat disimpan.')
                ->schema([
                    Forms\Components\TextInput::make('income')
                        ->label('Pendapatan')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),
                    Forms\Components\TextInput::make('tax')
                        ->label('Pajak')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),
                    Forms\Components\TextInput::make('cost_of_goods')
                        ->label('Modal')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),
                    Forms\Components\TextInput::make('operational_cost')
                        ->label('Biaya Operasional')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),
                    Forms\Components\Placeholder::make('total_expense_preview')
                        ->label('Total Biaya')
                        ->content(fn (Forms\Get $get): string => static::rupiah(static::preview($get)['total_expense'])),
                    Forms\Components\Placeholder::make('profit_preview')
                        ->label('Profit')
                        ->content(fn (Forms\Get $get): string => static::rupiah(static::preview($get)['profit'])),
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_label')
                    ->label('Referensi')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->description(fn (FinanceJournal $record): ?string => $record->shipment
                        ? "{$record->shipment->awb_number} · {$record->shipment->origin} → {$record->shipment->destination}"
                        : null),
                Tables\Columns\TextColumn::make('journal_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (FinanceJournal $record): string => $record->typeLabel())
                    ->color(fn (string $state): string => FinanceJournal::TYPE_COLORS[$state] ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('income')
                    ->label('Pendapatan')
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
                Tables\Columns\TextColumn::make('cost_of_goods')
                    ->label('Modal')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('operational_cost')
                    ->label('Biaya Operasional')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tax')
                    ->label('Pajak')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('shipment'))
            ->defaultSort('entry_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('journal_type')
                    ->label('Tipe')
                    ->options(FinanceJournal::TYPE_LABELS),
                Tables\Filters\Filter::make('entry_date')
                    ->label('Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('entry_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('entry_date', '<=', $date)),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (FinanceJournal $record): bool => ! $record->isLinkedToBilledInvoice())
                    ->tooltip('Jurnal dari invoice yang sudah ditagihkan tidak dapat dihapus karena akan menghilangkan modal dan biaya operasionalnya.'),
            ])
            // Penghapusan massal sengaja tidak disediakan: kuncian "terkunci dari
            // invoice yang ditagihkan" hanya dijaga di action baris, jadi aksi
            // massal berisiko lolos dan menghilangkan modal serta opex.
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceJournals::route('/'),
            'create' => Pages\CreateFinanceJournal::route('/create'),
            'edit' => Pages\EditFinanceJournal::route('/{record}/edit'),
        ];
    }

    /**
     * Pratinjau nilai turunan sebelum disimpan, memakai perhitungan yang sama
     * dengan hook saving di model.
     *
     * @return array{total_expense: float, profit: float}
     */
    protected static function preview(Forms\Get $get): array
    {
        $totalExpense = FinanceJournal::totalExpense(
            (float) $get('cost_of_goods'),
            (float) $get('operational_cost'),
            (float) $get('tax'),
        );

        return [
            'total_expense' => $totalExpense,
            'profit' => FinanceJournal::profit((float) $get('income'), $totalExpense),
        ];
    }

    protected static function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
