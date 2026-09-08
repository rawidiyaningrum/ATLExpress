<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Shipment Details')->schema([
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Shipment $record) => $record !== null),
                Forms\Components\TextInput::make('sender_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('receiver_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('origin')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->required()
                    ->suffix('kg'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receiver_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('origin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
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
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
}
