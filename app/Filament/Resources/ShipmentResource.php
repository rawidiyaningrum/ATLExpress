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
     * Shipment bisa dibaca oleh user tracker (read-only), sedangkan aksi tulis
     * terbuka untuk admin penuh dan admin ATL.
     */
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
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
        return auth()->user()?->isAdminRole() ?? false;
    }

    /**
     * Apakah user boleh melihat dan mengelola invoice. Hanya admin penuh;
     * admin ATL tidak memiliki akses ke area invoice.
     */
    public static function canManageInvoices(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('compact_line1')
                        ->state(function (Shipment $record): string {
                            $awb = $record->awb_number ?: '-';
                            $sender = $record->sender_name ? \Illuminate\Support\Str::limit($record->sender_name, 30) : '-';
                            $receiver = $record->receiver_name ? \Illuminate\Support\Str::limit($record->receiver_name, 30) : '-';

                            return "AWB: {$awb} | Pengirim: {$sender} | Penerima: {$receiver}";
                        })
                        ->size('sm')
                        ->weight('medium')
                        ->searchable(['awb_number', 'sender_name', 'receiver_name']),

                    Tables\Columns\TextColumn::make('compact_line2')
                        ->state(function (Shipment $record): string {
                            $rute = trim(($record->origin ?: '-') . ' → ' . ($record->destination ?: '-'));
                            $layanan = $record->service_type ? ucfirst($record->service_type) : '-';

                            return "Rute: {$rute} | Layanan: {$layanan}";
                        })
                        ->size('xs')
                        ->color('gray')
                        ->searchable(['origin', 'destination', 'service_type']),

                    Tables\Columns\TextColumn::make('compact_line3')
                        ->state(function (Shipment $record): string {
                            $dimensi = $record->dimensions ?: '-';
                            $berat = $record->weight !== null ? $record->weight . ' kg' : '-';
                            $tarif = $record->final_tariff !== null ? 'Rp ' . number_format((float) $record->final_tariff, 0, ',', '.') : '-';

                            return "Dimensi: {$dimensi} | Berat: {$berat} | Tarif Final: {$tarif}";
                        })
                        ->size('xs')
                        ->color('gray'),
                ])
                    ->space(1),
            ])
            ->actions([
                Tables\Actions\Action::make('invoice')
                    ->label(fn (Shipment $record): string => static::invoiceActionLabel($record))
                    ->icon('heroicon-o-receipt-percent')
                    ->color('gray')
                    ->visible(fn (Shipment $record): bool => static::canManageInvoices() && static::canShowInvoiceAction($record))
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
