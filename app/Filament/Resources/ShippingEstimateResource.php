<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Infolists\Components\EstimateComparisonTable;
use App\Filament\Resources\ShippingEstimateResource\Pages;
use App\Models\ShippingEstimate;
use App\Services\InvoiceService;
use App\Services\ShippingEstimateService;
use App\Services\TariffCalculatorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ShippingEstimateResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = ShippingEstimate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Estimasi Pengiriman';

    protected static ?string $modelLabel = 'Estimasi';

    protected static ?string $pluralModelLabel = 'Estimasi';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pengirim')
                ->schema([
                    Forms\Components\TextInput::make('sender_name')
                        ->label('Nama Pengirim')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('sender_phone')
                        ->label('Telepon Pengirim')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Textarea::make('sender_address')
                        ->label('Alamat Pengirim')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Data Penerima')
                ->schema([
                    Forms\Components\TextInput::make('receiver_name')
                        ->label('Nama Penerima')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('receiver_phone')
                        ->label('Telepon Penerima')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Textarea::make('receiver_address')
                        ->label('Alamat Penerima')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Rute & Barang')
                ->description('Rute diambil dari tabel tarif. Bila kota sudah dipilih, perbandingan armada terisi otomatis.')
                ->schema([
                    Forms\Components\Select::make('origin')
                        ->label('Kota Asal')
                        ->options(fn (): array => app(TariffCalculatorService::class)->getOriginOptions())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                            $set('kabupaten_tujuan', null);
                            $set('destination', null);
                            static::syncArmadas($get, $set);
                        }),
                    Forms\Components\Select::make('kabupaten_tujuan')
                        ->label('Kabupaten Tujuan')
                        ->options(fn (Forms\Get $get): array => app(TariffCalculatorService::class)->getKabupatenOptions((string) $get('origin')))
                        ->getSearchResultsUsing(fn (Forms\Get $get, string $search): array => app(TariffCalculatorService::class)->searchKabupatenOptions((string) $get('origin'), $search))
                        ->getOptionLabelUsing(fn ($value): ?string => blank($value) ? null : (string) $value)
                        ->searchable()
                        ->required()
                        ->disabled(fn (Forms\Get $get): bool => blank($get('origin')))
                        ->live()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                            $set('destination', null);
                            static::syncArmadas($get, $set);
                        }),
                    Forms\Components\Select::make('destination')
                        ->label('Kota Tujuan')
                        ->options(fn (Forms\Get $get): array => app(TariffCalculatorService::class)->getDestinationOptions((string) $get('origin'), (string) $get('kabupaten_tujuan')))
                        ->getSearchResultsUsing(fn (Forms\Get $get, string $search): array => app(TariffCalculatorService::class)->searchDestinationOptions((string) $get('origin'), (string) $get('kabupaten_tujuan'), $search))
                        ->getOptionLabelUsing(fn ($value): ?string => blank($value) ? null : (string) $value)
                        ->searchable()
                        ->required()
                        ->disabled(fn (Forms\Get $get): bool => blank($get('kabupaten_tujuan')))
                        ->live()
                        ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => static::syncArmadas($get, $set)),
                    Forms\Components\TextInput::make('weight')
                        ->label('Berat')
                        ->numeric()
                        ->minValue(0.01)
                        ->suffix('kg')
                        ->required()
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('item_type')
                        ->label('Jenis/Isi Barang')
                        ->placeholder('Contoh: Dokumen, sparepart elektronik')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(3),
            Forms\Components\Section::make('Perbandingan Armada')
                ->description('Tarif terisi otomatis dari rute yang dipilih; ongkos kirim = berat x tarif per kg. Carter memakai tarif final borongan, bukan per kg. Hapus armada yang tidak ingin dibandingkan.')
                ->schema([
                    Forms\Components\Repeater::make('armadas')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\Select::make('service_type')
                                ->label('Jenis Armada')
                                ->options(fn (): array => app(ShippingEstimateService::class)->serviceLabels())
                                ->required()
                                ->live(),
                            Forms\Components\TextInput::make('price_per_kg')
                                ->label('Tarif per Kg')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->visible(fn (Forms\Get $get): bool => ! app(ShippingEstimateService::class)->usesFinalTariff((string) $get('service_type')))
                                ->required(fn (Forms\Get $get): bool => ! app(ShippingEstimateService::class)->usesFinalTariff((string) $get('service_type')))
                                ->live(onBlur: true),
                            Forms\Components\TextInput::make('final_tariff')
                                ->label('Tarif Final')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->helperText('Ongkos kirim borongan untuk armada ini, tanpa dikali berat.')
                                ->visible(fn (Forms\Get $get): bool => app(ShippingEstimateService::class)->usesFinalTariff((string) $get('service_type')))
                                ->required(fn (Forms\Get $get): bool => app(ShippingEstimateService::class)->usesFinalTariff((string) $get('service_type')))
                                ->live(onBlur: true),
                            Forms\Components\TextInput::make('min_weight')
                                ->label('Berat Minimum')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('kg')
                                ->default(1),
                            Forms\Components\TextInput::make('estimated_days')
                                ->label('Estimasi')
                                ->suffix('hari')
                                ->placeholder('mis. 2-3'),
                            Forms\Components\Placeholder::make('ongkos_kirim')
                                ->label('Ongkos Kirim')
                                ->content(function (Forms\Get $get): string {
                                    $service = app(ShippingEstimateService::class);
                                    $cost = $service->usesFinalTariff((string) $get('service_type'))
                                        ? (float) $get('final_tariff')
                                        : $service->shippingCost((float) $get('../../weight'), (float) $get('price_per_kg'));

                                    return 'Rp '.number_format($cost, 0, ',', '.');
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah armada'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('muatTarif')
                            ->label('Muat Ulang Tarif Rute')
                            ->icon('heroicon-o-arrow-path')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => static::syncArmadas($get, $set)),
                    ])
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Biaya Lainnya')
                ->description('Biaya opsional yang berlaku untuk semua armada. Baris kosong diabaikan.')
                ->schema([
                    Forms\Components\Repeater::make('charges')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('Keterangan')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\Select::make('type')
                                ->label('Jenis')
                                ->options([
                                    InvoiceService::TYPE_ADDITIONAL => 'Biaya Tambahan',
                                    InvoiceService::TYPE_DISKON => 'Diskon',
                                ])
                                ->default(InvoiceService::TYPE_ADDITIONAL)
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->default(0)
                                ->live(onBlur: true),
                            Forms\Components\Placeholder::make('line_total')
                                ->label('Jumlah Baris')
                                ->content(function (Forms\Get $get): string {
                                    $line = (int) $get('quantity') * (float) $get('unit_price');

                                    return 'Rp '.number_format($line, 0, ',', '.');
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah biaya'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('quickPacking')
                            ->label('Packing Kayu')
                            ->icon('heroicon-o-plus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => static::appendCharge($get, $set, 'Packing Kayu', 50000)),
                        Forms\Components\Actions\Action::make('quickAsuransi')
                            ->label('Asuransi')
                            ->icon('heroicon-o-shield-check')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => static::appendCharge($get, $set, 'Asuransi', 100000)),
                        Forms\Components\Actions\Action::make('quickLoading')
                            ->label('Loading Tim')
                            ->icon('heroicon-o-truck')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => static::appendCharge($get, $set, 'Loading Tim', 75000)),
                    ])
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Pajak')
                ->description('Pajak mengikuti aturan invoice dan dihitung dari Dasar Pengenaan Pajak (DPP) tiap armada.')
                ->schema([
                    Forms\Components\Toggle::make('include_ppn')
                        ->label('Hitung PPN (1,1%)')
                        ->default(true)
                        ->live(),
                    Forms\Components\Toggle::make('include_pph')
                        ->label('Hitung PPh (2%)')
                        ->default(true)
                        ->live(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Ringkasan Perbandingan')
                ->description('Perkiraan total per jenis armada, termasuk biaya lain dan pajak. Untuk adjustment estimasi, silakan diskusi dengan tim ATL.')
                ->schema([
                    Forms\Components\Placeholder::make('comparison_preview')
                        ->hiddenLabel()
                        ->content(fn (Forms\Get $get): HtmlString => static::comparisonPreview($get))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informasi Estimasi')->schema([
                Infolists\Components\TextEntry::make('estimate_number')
                    ->label('Nomor Estimasi')
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('Dibuat oleh')
                    ->placeholder('-'),
            ])->columns(3),
            Infolists\Components\Section::make('Pengirim')->schema([
                Infolists\Components\TextEntry::make('sender_name')
                    ->label('Nama')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('sender_phone')
                    ->label('Telepon')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('sender_address')
                    ->label('Alamat')
                    ->placeholder('-'),
            ])->columns(3),
            Infolists\Components\Section::make('Penerima')->schema([
                Infolists\Components\TextEntry::make('receiver_name')
                    ->label('Nama')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('receiver_phone')
                    ->label('Telepon')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('receiver_address')
                    ->label('Alamat')
                    ->placeholder('-'),
            ])->columns(3),
            Infolists\Components\Section::make('Rute & Barang')->schema([
                Infolists\Components\TextEntry::make('origin')
                    ->label('Kota Asal')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('kabupaten_tujuan')
                    ->label('Kabupaten Tujuan')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('destination')
                    ->label('Kota Tujuan')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('weight')
                    ->label('Berat')
                    ->suffix(' kg')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('item_type')
                    ->label('Jenis/Isi Barang')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ])->columns(3),
            Infolists\Components\Section::make('Perbandingan Armada')
                ->description('Total tiap armada sudah termasuk biaya lain dan pajak. Untuk adjustment estimasi, silakan diskusi dengan tim ATL.')
                ->schema([
                    EstimateComparisonTable::make('comparison')
                        ->getStateUsing(fn (): array => []),
                ]),
            Infolists\Components\Section::make('Biaya Lainnya')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('charges')
                        ->hiddenLabel()
                        ->schema([
                            Infolists\Components\TextEntry::make('description')
                                ->label('Keterangan'),
                            Infolists\Components\TextEntry::make('type')
                                ->label('Jenis')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => $state === InvoiceService::TYPE_DISKON ? 'Diskon' : 'Biaya Tambahan')
                                ->color(fn (string $state): string => $state === InvoiceService::TYPE_DISKON ? 'danger' : 'gray'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('Jumlah'),
                            Infolists\Components\TextEntry::make('unit_price')
                                ->label('Harga Satuan')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('line_total')
                                ->label('Jumlah Baris')
                                ->money('IDR'),
                        ])
                        ->columns(5),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('estimate_number')
                    ->label('Nomor Estimasi')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('route')
                    ->label('Rute')
                    ->state(fn (ShippingEstimate $record): string => trim(sprintf(
                        '%s → %s',
                        $record->origin ?: '-',
                        $record->destination ?: '-',
                    )))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('origin', 'like', "%{$search}%")
                        ->orWhere('destination', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('sender_name')
                    ->label('Pengirim')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receiver_name')
                    ->label('Penerima')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Berat')
                    ->numeric()
                    ->suffix(' kg')
                    ->sortable(),
                Tables\Columns\TextColumn::make('armadas_count')
                    ->label('Armada')
                    ->counts('armadas')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('armadas'))
            ->recordUrl(fn (ShippingEstimate $record): string => static::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Lihat')
                        ->icon('heroicon-o-eye')
                        ->url(fn (ShippingEstimate $record): string => static::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('cetak')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (ShippingEstimate $record): string => Pages\PrintShippingEstimate::getUrl(['record' => $record]))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->label('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus'),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->searchPlaceholder('Cari nomor / rute / pengirim...');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingEstimates::route('/'),
            'create' => Pages\CreateShippingEstimate::route('/create'),
            'view' => Pages\ViewShippingEstimate::route('/{record}'),
            'edit' => Pages\EditShippingEstimate::route('/{record}/edit'),
            'print' => Pages\PrintShippingEstimate::route('/{record}/cetak'),
        ];
    }

    /**
     * Mengisi ulang daftar armada dari tabel tarif untuk rute yang dipilih.
     *
     * Semua jenis armada yang punya tarif untuk rute itu ikut dibandingkan.
     * Selama rute belum lengkap, daftar dikosongkan supaya tidak ada tarif rute
     * lama yang tertinggal.
     */
    protected static function syncArmadas(Forms\Get $get, Forms\Set $set): void
    {
        $origin = (string) $get('origin');
        $kabupaten = (string) $get('kabupaten_tujuan');
        $destination = (string) $get('destination');

        if ($origin === '' || $kabupaten === '' || $destination === '') {
            $set('armadas', []);

            return;
        }

        $service = app(ShippingEstimateService::class);
        $rates = app(TariffCalculatorService::class)->ratesForRoute($origin, $kabupaten, $destination);
        $rows = [];

        foreach ($rates as $rate) {
            $isFinal = $service->usesFinalTariff((string) $rate['service_type']);

            $rows[] = [
                'service_type' => $rate['service_type'],
                'price_per_kg' => $isFinal ? 0 : $rate['price_per_kg'],
                'final_tariff' => null,
                'min_weight' => $rate['min_weight'],
                'estimated_days' => $rate['estimated_days'],
            ];
        }

        $set('armadas', array_values($rows));
    }

    protected static function appendCharge(Forms\Get $get, Forms\Set $set, string $description, float $amount): void
    {
        $charges = (array) $get('charges');

        $charges[] = [
            'description' => $description,
            'type' => InvoiceService::TYPE_ADDITIONAL,
            'quantity' => 1,
            'unit_price' => $amount,
        ];

        $set('charges', array_values($charges));
    }

    protected static function comparisonPreview(Forms\Get $get): HtmlString
    {
        $rows = app(ShippingEstimateService::class)->preview(
            (float) $get('weight'),
            (array) $get('armadas'),
            (array) $get('charges'),
            (bool) $get('include_ppn'),
            (bool) $get('include_pph'),
        );

        return new HtmlString(
            view('filament.shipping-estimates.comparison', ['rows' => $rows])->render(),
        );
    }
}
