<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingRequestResource\Pages;
use App\Models\Shipment;
use App\Models\ShippingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingRequestResource extends Resource
{
    protected static ?string $model = ShippingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pemesanan';

    protected static ?string $modelLabel = 'Pemesanan';

    protected static ?string $pluralModelLabel = 'Pemesanan';

    public static function getStatusOptions(): array
    {
        return [
            'new' => 'Baru',
            'contacted' => 'Sudah Dihubungi',
            'checking' => 'Pengecekan Barang / Visiting',
            'shipped' => 'Pengiriman',
        ];
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'new' => 'warning',
            'contacted' => 'info',
            'checking' => 'primary',
            'shipped' => 'success',
            default => 'gray',
        };
    }

    public static function getShipmentTransferSchema(): array
    {
        return [
            Forms\Components\TextInput::make('final_weight')
                ->label('Berat Final (kg)')
                ->numeric()
                ->required()
                ->minValue(0.5),
            Forms\Components\TextInput::make('final_dimensions')
                ->label('Dimensi Final (PxLxT cm)')
                ->required()
                ->maxLength(255)
                ->placeholder('Contoh: 50x40x30'),
            Forms\Components\TextInput::make('final_tariff')
                ->label('Tarif Final (Rp)')
                ->numeric()
                ->required()
                ->minValue(0),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pemesan')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->disabled(),
            ])->columns(3),
            Forms\Components\Section::make('Detail Pengiriman')->schema([
                Forms\Components\TextInput::make('origin')
                    ->label('Asal')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('destination')
                    ->label('Tujuan')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('service_type')
                    ->label('Layanan')
                    ->disabled(),
                Forms\Components\TextInput::make('item_type')
                    ->label('Jenis Barang')
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('weight')
                    ->label('Berat')
                    ->numeric()
                    ->required()
                    ->suffix('kg')
                    ->disabled(),
                Forms\Components\TextInput::make('dimensions')
                    ->label('Dimensi')
                    ->placeholder('-')
                    ->disabled(),
                Forms\Components\Textarea::make('pickup_address')
                    ->label('Alamat Penjemputan')
                    ->required()
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->disabled(),
            ])->columns(3),
            Forms\Components\Section::make('Data Final (Admin)')->schema([
                Forms\Components\TextInput::make('final_tariff')
                    ->label('Tarif Final (Rp)')
                    ->numeric()
                    ->placeholder('-')
                    ->disabled(),
                Forms\Components\TextInput::make('final_dimensions')
                    ->label('Dimensi Final')
                    ->placeholder('-')
                    ->disabled(),
                Forms\Components\TextInput::make('final_weight')
                    ->label('Berat Final (kg)')
                    ->numeric()
                    ->placeholder('-')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(self::getStatusOptions())
                    ->disabled(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Jenis Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('origin')
                    ->label('Asal')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Berat')
                    ->suffix(' kg'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => self::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => self::getStatusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('contacted')
                    ->label('Tandai Dihubungi')
                    ->icon('heroicon-o-phone')
                    ->requiresConfirmation()
                    ->action(fn (ShippingRequest $record) => $record->update(['status' => 'contacted']))
                    ->visible(fn (ShippingRequest $record) => $record->status === 'new'),
                Tables\Actions\Action::make('checking')
                    ->label('Mulai Pengecekan / Visiting')
                    ->icon('heroicon-o-magnifying-glass')
                    ->requiresConfirmation()
                    ->action(fn (ShippingRequest $record) => $record->update(['status' => 'checking']))
                    ->visible(fn (ShippingRequest $record) => $record->status === 'contacted'),
                Tables\Actions\Action::make('moveToShipment')
                    ->label('Pindahkan ke Shipment')
                    ->icon('heroicon-o-truck')
                    ->form(self::getShipmentTransferSchema())
                    ->modalHeading('Pindahkan ke Shipment')
                    ->modalSubmitActionLabel('Pindahkan & Buat Pengiriman')
                    ->action(function (array $data, ShippingRequest $record): void {
                        $record->update([
                            'status' => 'shipped',
                            'final_tariff' => $data['final_tariff'],
                            'final_dimensions' => $data['final_dimensions'],
                            'final_weight' => $data['final_weight'],
                        ]);

                        $weight = $data['final_weight'];

                        $shipment = Shipment::create([
                            'tracking_number' => self::generateTrackingNumber(),
                            'sender_name' => $record->name,
                            'receiver_name' => null,
                            'origin' => $record->origin,
                            'destination' => $record->destination,
                            'weight' => $weight,
                            'status' => 'pending',
                            'shipping_request_id' => $record->id,
                            'final_tariff' => $data['final_tariff'],
                            'final_dimensions' => $data['final_dimensions'],
                        ]);
                    })
                    ->visible(fn (ShippingRequest $record) => $record->status === 'checking'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function generateTrackingNumber(): string
    {
        $prefix = 'ATL-' . date('Y') . '-';
        $last = Shipment::where('tracking_number', 'like', $prefix . '%')
            ->orderByDesc('tracking_number')
            ->value('tracking_number');

        $next = $last ? ((int) substr($last, -6)) + 1 : 1;

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingRequests::route('/'),
            'view' => Pages\ViewShippingRequest::route('/{record}'),
        ];
    }
}