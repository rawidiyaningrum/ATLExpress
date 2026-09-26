<?php

namespace App\Filament\Widgets;

use App\Models\Shipment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentShipmentsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Shipment Terbaru')
            ->description('Sepuluh shipment terakhir, diklik untuk membuka detailnya.')
            ->query(Shipment::query()->with('latestInvoice'))
            ->columns([
                Tables\Columns\TextColumn::make('awb_number')
                    ->label('No. AWB')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('sender_name')
                    ->label('Pengirim')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('route')
                    ->label('Rute')
                    ->state(fn (Shipment $record): string => "{$record->origin} → {$record->destination}")
                    ->searchable(['origin', 'destination'])
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Shipment::STATUS_COLORS[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('final_tariff')
                    ->label('Tarif Final')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('latestInvoice.invoice_number')
                    ->label('Invoice')
                    ->placeholder('Belum ada')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}
