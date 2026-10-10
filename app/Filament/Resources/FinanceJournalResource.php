<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\FinanceJournalResource\Concerns\HasRealExpenseItems;
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
    use HasRealExpenseItems;
    use OnlyAdmins;

    protected static ?string $model = FinanceJournal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Jurnal Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'jurnal keuangan';

    protected static ?string $pluralModelLabel = 'jurnal keuangan';

    protected static ?string $recordTitleAttribute = 'reference_label';

    /**
     * State path checkbox "sama dengan nominal invoice".
     *
     * Nilainya tidak dikirim ke database karena status centang diturunkan dari
     * kolom real_income, bukan disimpan terpisah.
     */
    public const SAME_AS_INVOICE_STATE_PATH = 'same_as_invoice';

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
                        // Panel nilai disembunyikan atau ditampilkan mengikuti
                        // tipe ini, jadi perubahannya harus langsung dikirim
                        // ke server. Tanpa live() panelnya tidak ikut berubah
                        // saat operator mengganti tipe.
                        ->live()
                        ->helperText('Kas masuk dicatat otomatis saat invoice dilunasi.'),
                    Forms\Components\TextInput::make('reference_label')
                        ->label('Referensi')
                        ->maxLength(255)
                        ->helperText('Terisi otomatis dengan nomor invoice untuk jurnal dari invoice yang ditagihkan.'),
                ])->columns(2),
            Forms\Components\Section::make('Nilai')
                ->description('Nominal tagihan diambil dari total invoice, sedangkan nominal kas riil diisi manual karena bisa berbeda, misalnya ada potongan biaya transfer atau sisa pembayaran.')
                // Jurnal kas masuk hanya mencatat penerimaan kas. Jurnal
                // pendapatan tidak punya panel ini karena nominalnya sudah
                // diambil dari invoice, modalnya dari pengeluaran real per item,
                // dan biaya operasional serta catatannya tidak lagi dicatat
                // dari halaman jurnal.
                ->visible(fn (Forms\Get $get): bool => $get('journal_type') === FinanceJournal::TYPE_RECEIPT)
                ->schema([
                    Forms\Components\Placeholder::make('invoice_total_preview')
                        ->label('Nominal Invoice')
                        ->content(fn (?FinanceJournal $record): string => static::invoiceTotalLabel($record)),
                    Forms\Components\Checkbox::make(self::SAME_AS_INVOICE_STATE_PATH)
                        ->label('Sama dengan nominal invoice')
                        ->dehydrated(false)
                        ->helperText('Centang kalau seluruh nominal tagihan benar-benar masuk ke rekening.')
                        // Tanpa invoice tidak ada tagihan untuk dibandingkan,
                        // jadi centang tidak akan mengisi apa pun.
                        ->visible(fn (?FinanceJournal $record): bool => $record?->invoiceTotal() !== null)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, ?FinanceJournal $record, ?bool $state): void {
                            // Dicentang berarti kas riilnya sama dengan
                            // tagihan, jadi angka tidak perlu diketik. Melepas
                            // centangnya berarti operator mengisinya sendiri.
                            $set('real_income', $state ? $record?->invoiceTotal() : null);
                        }),
                    Forms\Components\TextInput::make('real_income')
                        ->label('Nominal Real Masuk Rekening')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required()
                        ->helperText('Uang yang benar-benar masuk ke rekening, boleh lebih kecil dari nominal invoice.'),
                ])->columns(2),
            static::realExpenseSection()
                ->visible(fn (?FinanceJournal $record): bool => static::journalHasLinkedInvoice($record)),
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
                Tables\Columns\TextColumn::make('real_income')
                    ->label('Kas Masuk Real')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('Belum dicatat')
                    ->description(fn (FinanceJournal $record): ?string => static::realIncomeDescription($record)),
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
                    ->label('Modal / Pengeluaran Real')
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
            // Default Filament hanya menampilkan ikon corong, sehingga filter
            // jenis jurnal sulit ditemukan. Tombol berlabel teks lebih jelas,
            // dan tipe yang aktif tetap muncul sebagai chip indikator di bawah
            // tabel sehingga tidak perlu membaca state filter untuk labelnya.
            ->filtersTriggerAction(fn (Tables\Actions\Action $action): Tables\Actions\Action => $action
                ->button()
                ->label('Filter')
                ->icon('heroicon-m-funnel'),
            )
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
     * Jurnal yang punya invoice hanya menampilkan modal dari pengeluaran real
     * per item, jadi field modal manual dan baris pengeluannya disembunyikan
     * kalau tidak ada invoice yang terkait.
     */
    public static function journalHasLinkedInvoice(?FinanceJournal $record): bool
    {
        return $record instanceof FinanceJournal
            && $record->isRevenue()
            && $record->linkedInvoice() !== null;
    }

    /**
     * Label total tagihan untuk panel nilai jurnal kas masuk.
     *
     * Jurnal kas yang dibuat manual tidak punya invoice sebagai pembanding,
     * jadi panelnya menjelaskan itu, bukan menampilkan angka nol yang disalah
     * baca sebagai tagihan gratis.
     */
    public static function invoiceTotalLabel(?FinanceJournal $record): string
    {
        $total = $record?->invoiceTotal();

        return $total === null ? 'Tidak ada invoice terkait' : static::rupiah($total);
    }

    /**
     * Keterangan selisih kas riil terhadap tagihan di bawah kolom tabel.
     *
     * Jurnal kas manual tidak punya pembanding, jadi selisihnya tidak
     * dihitung supaya selisih nol yang semu tidak muncul.
     */
    public static function realIncomeDescription(FinanceJournal $record): ?string
    {
        if ($record->real_income === null) {
            return 'Kas riil belum dicatat operator.';
        }

        $invoiceTotal = $record->invoiceTotal();

        if ($invoiceTotal === null) {
            return null;
        }

        $difference = round($invoiceTotal - (float) $record->real_income, 2);

        if ($difference === 0.0) {
            return 'Sama dengan nominal invoice.';
        }

        return sprintf('Selisih %s dari nominal invoice.', static::rupiah(abs($difference)));
    }

    protected static function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
