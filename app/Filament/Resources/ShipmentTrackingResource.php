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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class ShipmentTrackingResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Update Posisi Pengiriman';

    protected static ?string $modelLabel = 'Posisi Pengiriman';

    protected static ?string $pluralModelLabel = 'Posisi Pengiriman';

    /**
     * Admin dan tracker sama-sama boleh mencatat posisi, tetapi log bersifat
     * riwayat sehingga tidak bisa diubah. Penghapusan log khusus admin.
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
        return auth()->user()?->isAdminRole() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
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
                        ->orderByDesc('created_at')
                        ->orderByDesc('id'),
                )
                ->getOptionLabelFromRecordUsing(fn (Shipment $record): string => trim("{$record->awb_number} · ".static::shipmentSummary($record)))
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Diurutkan dari yang terbaru.'),
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
                Tables\Columns\TextColumn::make('awb_number')
                    ->label('No. AWB')
                    ->searchable()
                    ->copyable()
                    ->width('20%')
                    ->description(fn (Shipment $record): ?string => static::shipmentSummary($record) ?: null),
                Tables\Columns\TextColumn::make('tracking')
                    ->label('Tracking')
                    ->html()
                    ->getStateUsing(fn (Shipment $record): string => static::renderTracking($record))
                    ->wrap(false)
                    ->extraHeaderAttributes(['class' => 'w-full']),
            ])
            ->defaultSort('awb_number')
            ->actions([])
            ->bulkActions([]);
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

    public static function renderTracking(Shipment $record): string
    {
        $logs = $record->logs()
            ->with('tracker')
            ->orderBy('timestamp')
            ->get();

        if ($logs->isEmpty()) {
            return '-';
        }

        $lines = $logs->map(function (ShipmentLog $log) {
            $timestamp = $log->timestamp?->format('d M Y') ?? '-';
            $status = $log->status_label ?? Shipment::statusLabel($log->status);
            $location = $log->location ?? '-';
            $tracker = $log->tracker?->name ?? '-';

            $badge = Blade::render(
                '<x-filament::badge :color="$color" size="xs" class="inline-flex align-middle whitespace-nowrap">'.e($status).'</x-filament::badge>',
                ['color' => Shipment::STATUS_COLORS[$log->status] ?? 'gray']
            );

            try {
                $deleteUrl = route('filament.atlexpress-admin.resources.shipment-tracking.log.delete', ['log' => $log->id]);
            } catch (\Throwable $e) {
                $deleteUrl = '#';
            }

            $deleteLink = auth()->user()?->isAdminRole()
                ? ' <a href="'.$deleteUrl.'" class="text-danger-600 hover:text-danger-700 text-xs whitespace-nowrap" onclick="return confirm(\'Hapus log tracking ini?\')">hapus</a>'
                : '';

            return '<div class="whitespace-nowrap">'.$timestamp.' · '.$badge.' · '.e($location).' · ('.e($tracker).')'.$deleteLink.'</div>';
        });

        return '<div class="flex flex-col">'.$lines->implode('').'</div>';
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
