<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingRateResource\Pages;
use App\Models\ShippingRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingRateResource extends Resource
{
    protected static ?string $model = ShippingRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Shipping Rate Details')->schema([
                Forms\Components\TextInput::make('origin_city')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination_city')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('service_type')
                    ->options([
                        'darat' => 'Darat',
                        'laut' => 'Laut',
                        'udara' => 'Udara',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('min_weight')
                    ->numeric()
                    ->required()
                    ->suffix('kg'),
                Forms\Components\TextInput::make('price_per_kg')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('estimated_days')
                    ->numeric()
                    ->required()
                    ->suffix('days'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('origin_city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination_city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'darat' => 'warning',
                        'laut' => 'info',
                        'udara' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('min_weight')
                    ->numeric()
                    ->suffix(' kg'),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->numeric('Rp ,')
                    ->prefix('Rp '),
                Tables\Columns\TextColumn::make('estimated_days')
                    ->suffix(' days'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
