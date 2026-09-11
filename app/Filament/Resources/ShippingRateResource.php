<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingRateResource\Pages;
use App\Models\ShippingRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ShippingRateResource extends Resource
{
    protected static ?string $model = ShippingRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Manajemen Tarif';

    protected static ?string $modelLabel = 'Tarif';

    protected static ?string $pluralModelLabel = 'Tarif';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Tarif')->schema([
                Forms\Components\TextInput::make('origin_city')
                    ->label('Kota Asal')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination_city')
                    ->label('Kota Tujuan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        'darat' => 'Darat',
                        'laut' => 'Laut',
                        'udara' => 'Udara',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('min_weight')
                    ->label('Berat Minimum')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->suffix('kg'),
                Forms\Components\TextInput::make('price_per_kg')
                    ->label('Tarif per Kg')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('estimated_days')
                    ->label('Estimasi Pengiriman')
                    ->numeric()
                    ->required()
                    ->suffix('hari'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('origin_city')
                    ->label('Kota Asal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination_city')
                    ->label('Kota Tujuan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Layanan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'darat' => 'warning',
                        'laut' => 'info',
                        'udara' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('min_weight')
                    ->label('Min Berat')
                    ->numeric()
                    ->suffix(' kg')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label('Tarif per Kg')
                    ->numeric(decimalPlaces: 0)
                    ->prefix('Rp ')
                    ->sortable()
                    ->color(fn (ShippingRate $record) => $record->price_per_kg >= 100000 ? 'danger' : null),
                Tables\Columns\TextColumn::make('estimated_days')
                    ->label('Estimasi')
                    ->suffix(' hari')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options([
                        'darat' => 'Darat',
                        'laut' => 'Laut',
                        'udara' => 'Udara',
                    ]),
                SelectFilter::make('min_weight')
                    ->label('Min Berat')
                    ->options([
                        1 => '1 kg',
                        30 => '30 kg',
                        50 => '50 kg',
                        100 => '100 kg',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('adjustPrice')
                        ->label('Naikkan Tarif (%)')
                        ->icon('heroicon-m-arrow-trending-up')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Forms\Components\Select::make('percentage')
                                ->label('Persentase Kenaikan')
                                ->options([
                                    5 => '+5%',
                                    10 => '+10%',
                                    15 => '+15%',
                                    20 => '+20%',
                                    25 => '+25%',
                                ])
                                ->default(10)
                                ->required(),
                            Forms\Components\Select::make('service_type')
                                ->label('Terapkan hanya untuk layanan')
                                ->options([
                                    'darat' => 'Darat',
                                    'laut' => 'Laut',
                                    'udara' => 'Udara',
                                ])
                                ->placeholder('Semua layanan'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $factor = 1 + ((int) $data['percentage'] / 100);

                            $query = ShippingRate::whereIn('id', $records->pluck('id'));
                            if (filled($data['service_type'] ?? null)) {
                                $query->where('service_type', $data['service_type']);
                            }

                            foreach ($query->get() as $rate) {
                                $rate->update([
                                    'price_per_kg' => round($rate->price_per_kg * $factor),
                                ]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulkEdit')
                        ->label('Ubah Data Terpilih')
                        ->icon('heroicon-m-pencil-square')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Forms\Components\TextInput::make('price_per_kg')
                                ->label('Tarif per Kg (baru)')
                                ->numeric()
                                ->placeholder('Kosongkan jika tidak diubah'),
                            Forms\Components\TextInput::make('min_weight')
                                ->label('Berat Minimum kg (baru)')
                                ->numeric()
                                ->placeholder('Kosongkan jika tidak diubah'),
                            Forms\Components\TextInput::make('estimated_days')
                                ->label('Estimasi hari (baru)')
                                ->numeric()
                                ->placeholder('Kosongkan jika tidak diubah'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $updates = [];

                            if (filled($data['price_per_kg'] ?? null)) {
                                $updates['price_per_kg'] = $data['price_per_kg'];
                            }
                            if (filled($data['min_weight'] ?? null)) {
                                $updates['min_weight'] = $data['min_weight'];
                            }
                            if (filled($data['estimated_days'] ?? null)) {
                                $updates['estimated_days'] = (int) $data['estimated_days'];
                            }

                            if ($updates !== []) {
                                ShippingRate::whereIn('id', $records->pluck('id'))->update($updates);
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('destination_city')
            ->paginated([25, 50, 100, 250, 'all'])
            ->searchPlaceholder('Cari kota asal / tujuan...');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingRates::route('/'),
            'create' => Pages\CreateShippingRate::route('/create'),
            'edit' => Pages\EditShippingRate::route('/{record}/edit'),
        ];
    }
}