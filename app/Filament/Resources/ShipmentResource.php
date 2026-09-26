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
            Forms\Components\Section::make('Nomor')->schema([
                Forms\Components\TextInput::make('tracking_number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Shipment $record) => $record !== null),
                Forms\Components\TextInput::make('awb_number')
                    ->label('Nomor AWB')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Shipment $record) => $record !== null)
                    ->helperText('Dibuat otomatis pada langkah wizard, tidak dapat diubah.'),
                Forms\Components\Select::make('shipping_request_id')
                    ->label('Dari Pemesanan')
                    ->relationship('shippingRequest', 'name')
                    ->preload()
                    ->searchable(),
            ])->columns(2),
            Forms\Components\Section::make('Pengirim')->schema([
                Forms\Components\TextInput::make('sender_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sender_phone')
                    ->label('Telepon Pengirim')
                    ->tel()
                    ->maxLength(30)
                    ->nullable(),
                Forms\Components\Textarea::make('sender_address')
                    ->label('Alamat Pengirim')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Penerima')->schema([
                Forms\Components\TextInput::make('receiver_name')
                    ->label('Penerima')
                    ->maxLength(255)
                    ->nullable(),
                Forms\Components\TextInput::make('receiver_phone')
                    ->label('Telepon Penerima')
                    ->tel()
                    ->maxLength(30)
                    ->nullable(),
                Forms\Components\Textarea::make('receiver_address')
                    ->label('Alamat Penerima')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Rute & Berat')->schema([
                Forms\Components\TextInput::make('origin')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->required(fn (?Shipment $record) => $record === null || $record->status !== 'draft')
                    ->suffix('kg'),
                Forms\Components\Textarea::make('final_dimensions')
                    ->label('Dimensi Final')
                    ->placeholder('Contoh: 50x40x30')
                    ->nullable(),
            ])->columns(2),
            Forms\Components\Section::make('Tarif')->schema([
                Forms\Components\TextInput::make('price_per_kg')
                    ->label('Tarif per kg')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable(),
                Forms\Components\TextInput::make('final_tariff')
                    ->label('Tarif Final')
                    ->numeric()
                    ->prefix('Rp')
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
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
                Tables\Columns\TextColumn::make('awb_number')
                    ->label('No. AWB')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),
                Tables\Columns\TextColumn::make('shippingRequest.name')
                    ->label('Dari Pemesanan')
                    ->placeholder('-')
                    ->searchable()
                    ->description(fn (Shipment $record) => $record->shipping_request_id ? "#{$record->shipping_request_id}" : ''),
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
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'in_transit' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('final_tariff')
                    ->label('Tarif Final')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_dimensions')
                    ->label('Dimensi Final')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('printAwb')
                    ->label('Cetak AWB')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (Shipment $record): bool => filled($record->awb_number))
                    ->url(fn (Shipment $record): string => Pages\PrintAirwayBill::getUrl(['record' => $record])),
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
            'print-airway-bill' => Pages\PrintAirwayBill::route('/{record}/airway-bill'),
        ];
    }
}
