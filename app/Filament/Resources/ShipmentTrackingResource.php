<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentTrackingResource\Pages;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Services\TariffCalculatorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = ShipmentLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Update Posisi Pengiriman';

    protected static ?string $modelLabel = 'Posisi Pengiriman';

    protected static ?string $pluralModelLabel = 'Posisi Pengiriman';

    /**
     * Admin dan tracker sama-sama boleh mencatat posisi, tetapi log bersifat
     * riwayat sehingga tidak bisa diubah. Penghapusan khusus admin.
     */
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
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
            Forms\Components\Select::make('shipment_id')
                ->label('Pengiriman (No. AWB)')
                ->relationship(
                    name: 'shipment',
                    titleAttribute: 'awb_number',
                    modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->whereIn('status', Shipment::ACTIVE_STATUSES)
                        ->orderByDesc('created_at')
                        ->orderByDesc('id'),
                )
                ->getOptionLabelFromRecordUsing(fn (Shipment $record): string => trim("{$record->awb_number} · ".static::shipmentSummary($record)))
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Hanya pengiriman berstatus in transit yang bisa dipilih, diurutkan dari yang terbaru.'),
            Forms\Components\Select::make('location')
                ->label('Lokasi (Kabupaten)')
                ->options(fn (): array => app(TariffCalculatorService::class)->getAllKabupatenOptions())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Status Pengiriman')
                ->options(Shipment::STATUS_LABELS)
                ->default(Shipment::STATUS_IN_TRANSIT)
                ->required(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shipment.awb_number')
                    ->label('No. AWB')
                    ->placeholder('-')
                    ->searchable()
                    ->copyable()
                    ->description(fn (ShipmentLog $record): ?string => static::shipmentSummary($record->shipment) ?: null),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Shipment::statusLabel($state))
                    ->color(fn (?string $state): string => Shipment::STATUS_COLORS[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tracker.name')
                    ->label('Diinput Oleh')
                    ->placeholder('-'),
            ])
            ->defaultSort('timestamp', 'desc')
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Ringkasan rute dan pengirim sebuah shipment, dipakai label dropdown dan
     * keterangan di bawah nomor AWB pada tabel.
     */
    public static function shipmentSummary(?Shipment $shipment): string
    {
        if ($shipment === null) {
            return '';
        }

        $route = trim("{$shipment->origin} → {$shipment->destination}");
        $sender = filled($shipment->sender_name) ? " · {$shipment->sender_name}" : '';

        return $route.$sender;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipmentTracking::route('/'),
            'create' => Pages\CreateShipmentTracking::route('/create'),
        ];
    }
}
