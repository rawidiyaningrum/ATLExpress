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
            Forms\Components\Placeholder::make('initial_tariff')->label('Tarif Saat Pemesanan')
                ->content(fn (ShippingRequest $record): string => $record->initial_tariff !== null
                    ? 'Rp ' . number_format((float) $record->initial_tariff, 0, ',', '.')
                    : '-'),
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
            Forms\Components\TextInput::make('awb_number')
                ->label('Nomor AWB')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Kosongkan bila "Generate otomatis" aktif. Nomor AWB wajib unik.'),
            Forms\Components\Toggle::make('generate_awb')
                ->label('Generate otomatis AWB')
                ->default(true),
        ];
    }

    public static function transferToShipment(ShippingRequest $record, array $data): void
    {
        if (! empty($data['generate_awb']) && blank($data['awb_number'])) {
            $data['awb_number'] = self::generateAwb();
        }

        $record->update([
            'status' => 'shipped',
            'final_tariff' => $data['final_tariff'],
            'final_dimensions' => $data['final_dimensions'],
            'final_weight' => $data['final_weight'],
            'awb_number' => $data['awb_number'],
        ]);

        Shipment::create([
            'tracking_number' => self::generateTrackingNumber(),
            'sender_name' => $record->name,
            'receiver_name' => null,
            'origin' => $record->origin,
            'destination' => $record->destination,
            'weight' => $data['final_weight'],
            'status' => 'pending',
            'shipping_request_id' => $record->id,
            'final_tariff' => $data['final_tariff'],
            'final_dimensions' => $data['final_dimensions'],
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Pemesan')->schema([
                Forms\Components\Placeholder::make('name')->label('Nama')
                    ->content(fn (ShippingRequest $record): string => $record->name),
                Forms\Components\Placeholder::make('phone')->label('No. Telepon')
                    ->content(fn (ShippingRequest $record): string => $record->phone),
                Forms\Components\Placeholder::make('email')->label('Email')
                    ->content(fn (ShippingRequest $record): string => $record->email),
            ])->columns(3),
            Forms\Components\Section::make('Detail Pengiriman')->schema([
                Forms\Components\Placeholder::make('origin')->label('Asal')
                    ->content(fn (ShippingRequest $record): string => $record->origin),
                Forms\Components\Placeholder::make('destination')->label('Tujuan')
                    ->content(fn (ShippingRequest $record): string => $record->destination),
                Forms\Components\Placeholder::make('service_type')->label('Layanan')
                    ->content(fn (ShippingRequest $record): string => $record->service_type ?? '-'),
                Forms\Components\Placeholder::make('item_type')->label('Jenis Barang')
                    ->content(fn (ShippingRequest $record): string => $record->item_type),
                Forms\Components\Placeholder::make('weight')->label('Berat')
                    ->content(fn (ShippingRequest $record): string => $record->weight . ' kg'),
                Forms\Components\Placeholder::make('dimensions')->label('Dimensi')
                    ->content(fn (ShippingRequest $record): string => $record->dimensions ?? '-'),
                Forms\Components\Placeholder::make('pickup_address')->label('Alamat Penjemputan')
                    ->columnSpan(2)
                    ->content(fn (ShippingRequest $record): string => $record->pickup_address),
                Forms\Components\Placeholder::make('notes')->label('Keterangan')
                    ->columnSpan(2)
                    ->content(fn (ShippingRequest $record): string => $record->notes ?? '-'),
            ])->columns(3),
            Forms\Components\Section::make('Data Final (Admin)')->schema([
                Forms\Components\Placeholder::make('initial_tariff')->label('Tarif Saat Pemesanan')
                    ->content(fn (ShippingRequest $record): string => $record->initial_tariff !== null
                        ? 'Rp ' . number_format((float) $record->initial_tariff, 0, ',', '.')
                        : '-'),
                Forms\Components\Placeholder::make('final_tariff')->label('Tarif Final (Rp)')
                    ->content(fn (ShippingRequest $record): string => $record->final_tariff !== null
                        ? 'Rp ' . number_format((float) $record->final_tariff, 0, ',', '.')
                        : '-'),
                Forms\Components\Placeholder::make('final_dimensions')->label('Dimensi Final')
                    ->content(fn (ShippingRequest $record): string => $record->final_dimensions ?? '-'),
                Forms\Components\Placeholder::make('final_weight')->label('Berat Final (kg)')
                    ->content(fn (ShippingRequest $record): string => $record->final_weight ?? '-'),
                Forms\Components\Placeholder::make('awb_number')->label('Nomor AWB')
                    ->content(fn (ShippingRequest $record): string => $record->awb_number ?? '-'),
                Forms\Components\Placeholder::make('status')->label('Status')
                    ->content(fn (ShippingRequest $record): string => self::getStatusOptions()[$record->status] ?? $record->status),
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
                Tables\Columns\TextColumn::make('awb_number')
                    ->label('AWB')
                    ->searchable()
                    ->placeholder('-'),
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
                    ->modalHeading('Input Tarif Akhir & AWB')
                    ->modalSubmitActionLabel('Pindahkan & Buat Pengiriman')
                    ->action(function (array $data, ShippingRequest $record): void {
                        self::transferToShipment($record, $data);
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

    public static function generateAwb(): string
    {
        $prefix = 'ATL-' . date('Y') . '-';
        $last = ShippingRequest::where('awb_number', 'like', $prefix . '%')
            ->orderByDesc('awb_number')
            ->value('awb_number');

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