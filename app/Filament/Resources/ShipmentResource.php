<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Models\Shipment;
use App\Services\TariffCalculatorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 1;

    /**
     * Shipment hanya bisa dibaca oleh user tracker (read-only). Seluruh aksi
     * tulis tetap khusus admin.
     */
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Nomor')->schema([
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
                Forms\Components\Select::make('service_type')
                    ->label('Jenis Layanan')
                    ->options(fn (): array => app(TariffCalculatorService::class)->serviceTypes()),
                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->required(fn (?Shipment $record) => $record === null || $record->status !== 'draft')
                    ->suffix('kg'),
                Forms\Components\TextInput::make('dimension_length')
                    ->label('Panjang')
                    ->numeric()
                    ->suffix('cm')
                    ->nullable(),
                Forms\Components\TextInput::make('dimension_width')
                    ->label('Lebar')
                    ->numeric()
                    ->suffix('cm')
                    ->nullable(),
                Forms\Components\TextInput::make('dimension_height')
                    ->label('Tinggi')
                    ->numeric()
                    ->suffix('cm')
                    ->nullable(),
            ])->columns(2),
            Forms\Components\Section::make('Barang')->schema([
                Forms\Components\TextInput::make('item_type')
                    ->label('Jenis/Isi Barang')
                    ->placeholder('Contoh: Dokumen, sparepart elektronik')
                    ->maxLength(255),
            ]),
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
                    ->options(Shipment::STATUS_LABELS)
                    ->default(Shipment::STATUS_DRAFT)
                    ->required(),
            ])->columns(2),
        ]);
    }

    /**
     * Tujuan tombol invoice milik sebuah shipment.
     *
     * Shipment yang sudah punya invoice dibuka ke detailnya, sedangkan yang
     * belum punya invoice diarahkan ke form pembuatan invoice di luar wizard.
     */
    public static function invoiceActionUrl(Shipment $shipment): string
    {
        $invoice = $shipment->latestInvoice;

        return $invoice !== null
            ? InvoiceResource::getUrl('view', ['record' => $invoice])
            : static::getUrl('create-invoice', ['record' => $shipment]);
    }

    public static function invoiceActionLabel(Shipment $shipment): string
    {
        return $shipment->latestInvoice !== null ? 'Invoice' : 'Buat Invoice';
    }

    /**
     * Shipment batal tidak mendapat invoice baru, tapi invoice yang sudah
     * terbit tetap boleh dilihat dan dicetak.
     */
    public static function canShowInvoiceAction(Shipment $shipment): bool
    {
        return $shipment->latestInvoice !== null || $shipment->status !== 'cancelled';
    }

    /**
     * Apakah user yang sedang login boleh mengelola (menulis) shipment.
     */
    public static function canManage(?Shipment $shipment = null): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('awb_number')
                    ->label('No. AWB')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->copyable(),
                Tables\Columns\TextColumn::make('shippingRequest.name')
                    ->label('Dari Pemesanan')
                    ->placeholder('-')
                    ->searchable()
                    ->description(fn (Shipment $record): string => $record->shipping_request_id ? "#{$record->shipping_request_id}" : ''),
                Tables\Columns\TextColumn::make('sender_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receiver_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('origin')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Layanan')
                    ->badge()
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? ucfirst($state) : '-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Shipment::STATUS_COLORS[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('final_tariff')
                    ->label('Tarif Final')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensi')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('invoice')
                    ->label(fn (Shipment $record): string => static::invoiceActionLabel($record))
                    ->icon('heroicon-o-receipt-percent')
                    ->color('gray')
                    ->visible(fn (Shipment $record): bool => static::canManage($record) && static::canShowInvoiceAction($record))
                    ->url(fn (Shipment $record): string => static::invoiceActionUrl($record)),
                Tables\Actions\Action::make('printAwb')
                    ->label('Cetak AWB')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (Shipment $record): bool => filled($record->awb_number))
                    ->url(fn (Shipment $record): string => Pages\PrintAirwayBill::getUrl(['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Shipment $record): bool => static::canManage($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Shipment $record): bool => static::canManage($record)),
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
            'print-invoice' => Pages\PrintInvoice::route('/{record}/invoice'),
            'create-invoice' => Pages\CreateShipmentInvoice::route('/{record}/invoice/create'),
        ];
    }
}
