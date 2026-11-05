<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\TariffSearchLogResource\Pages;
use App\Filament\Resources\TariffSearchLogResource\Widgets;
use App\Models\TariffSearchLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TariffSearchLogResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = TariffSearchLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Log Pencarian Tarif';

    protected static ?string $modelLabel = 'Log Pencarian Tarif';

    protected static ?string $pluralModelLabel = 'Log Pencarian Tarif';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Log Pencarian')->schema([
                Forms\Components\TextInput::make('origin')->label('Kota Asal'),
                Forms\Components\TextInput::make('kabupaten')->label('Kabupaten Tujuan'),
                Forms\Components\TextInput::make('destination')->label('Kecamatan / Tujuan'),
                Forms\Components\TextInput::make('weight')->label('Berat (kg)'),
                Forms\Components\TextInput::make('results_count')->label('Jumlah Hasil'),
                Forms\Components\TextInput::make('min_price')->label('Tarif Termurah'),
                Forms\Components\TextInput::make('ip_address')->label('IP Address'),
                Forms\Components\Textarea::make('user_agent')->label('User Agent'),
                Forms\Components\DateTimePicker::make('created_at')->label('Waktu Pencarian'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->description(fn (TariffSearchLog $record) => $record->created_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('origin')
                    ->label('Kota Asal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('kabupaten')
                    ->label('Kabupaten')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('destination')
                    ->label('Kecamatan / Tujuan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Berat')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1, ',', '.') . ' kg')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('results_count')
                    ->label('Status Tarif')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "Ditemukan ({$state})" : 'Belum Ada Tarif')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_price')
                    ->label('Tarif Termurah')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format((float) $state, 0, ',', '.') : '-')
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Pengunjung')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('origin')
                    ->label('Kota Asal')
                    ->options(fn () => TariffSearchLog::query()
                        ->distinct()
                        ->pluck('origin', 'origin')
                        ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('has_results')
                    ->label('Ketersediaan Tarif')
                    ->placeholder('Semua')
                    ->trueLabel('Ada Tarif')
                    ->falseLabel('Belum Ada Tarif')
                    ->queries(
                        true: fn ($query) => $query->where('results_count', '>', 0),
                        false: fn ($query) => $query->where('results_count', 0),
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\TariffSearchOverviewStats::class,
            Widgets\PopularTariffRoutesChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffSearchLogs::route('/'),
        ];
    }
}

