<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\DeliveryAgentResource\Pages;
use App\Models\DeliveryAgent;
use App\Models\DeliveryAgentRoute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryAgentResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = DeliveryAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Agen Pengiriman';

    protected static ?string $modelLabel = 'Agen Pengiriman';

    protected static ?string $pluralModelLabel = 'Agen Pengiriman';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Agen Pengiriman')
                ->description('Data profil agen rekanan pengiriman kargo dan logistik.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Agen')
                        ->placeholder('Contoh: PT Mitra Kargo Nusantara / Agen Jaya Express')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor Kontak / WhatsApp')
                        ->placeholder('Contoh: 0812-3456-7890')
                        ->tel()
                        ->required()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('pic_name')
                        ->label('Nama PIC / Narahubung')
                        ->placeholder('Contoh: Bpk. Bambang')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email Agen')
                        ->email()
                        ->placeholder('agen@mitrakargo.com')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('location')
                        ->label('Lokasi / Alamat Agen')
                        ->placeholder('Contoh: Pergudangan Margomulyo Permai Blok C-12, Surabaya, Jawa Timur')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Agen aktif akan tersedia sebagai referensi penugasan pengiriman.'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Khusus')
                        ->placeholder('Catatan ketentuan pembayaran, jadwal keberangkatan, dll.')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Daftar Rute, Armada & Tarif Agen')
                ->description('Rincian rute asal-tujuan, jenis armada truk yang disediakan agen, serta tarif (carter borongan atau reguler per kg).')
                ->schema([
                    Forms\Components\Repeater::make('routes')
                        ->relationship('routes')
                        ->schema([
                            Forms\Components\TextInput::make('origin')
                                ->label('Rute Asal')
                                ->placeholder('Contoh: Jakarta / Surabaya')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('destination')
                                ->label('Rute Tujuan')
                                ->placeholder('Contoh: Denpasar / Makassar / Medan')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('fleet_type')
                                ->label('Jenis Armada')
                                ->options(DeliveryAgentRoute::FLEET_TYPES)
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('rate_type')
                                ->label('Jenis Tarif')
                                ->options(DeliveryAgentRoute::RATE_TYPES)
                                ->default(DeliveryAgentRoute::RATE_TYPE_CARTER)
                                ->required()
                                ->reactive(),

                            Forms\Components\TextInput::make('tariff')
                                ->label('Tarif (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->helperText(fn (Forms\Get $get) => $get('rate_type') === 'reguler' ? 'Tarif per Kg' : 'Tarif borongan / sewa armada')
                                ->required(),

                            Forms\Components\TextInput::make('min_weight')
                                ->label('Minimal Berat (Kg)')
                                ->numeric()
                                ->suffix('Kg')
                                ->placeholder('Contoh: 50')
                                ->visible(fn (Forms\Get $get) => $get('rate_type') === 'reguler'),

                            Forms\Components\TextInput::make('estimated_days')
                                ->label('Estimasi Pengiriman')
                                ->placeholder('Contoh: 1-2 Hari / 3-4 Hari')
                                ->maxLength(100),

                            Forms\Components\TextInput::make('notes')
                                ->label('Catatan Rute')
                                ->placeholder('Contoh: Termasuk bongkar muat / minimal muatan')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => 
                            (! empty($state['origin']) && ! empty($state['destination']))
                                ? "{$state['origin']} → {$state['destination']} (" . ($state['fleet_type'] ?? 'Truk') . ' - ' . ucfirst($state['rate_type'] ?? 'carter') . ')'
                                : null
                        )
                        ->addActionLabel('Tambah Rute & Tarif Armada')
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Agen')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->url(fn (DeliveryAgent $record): string => 'https://wa.me/' . preg_replace('/\D/', '', $record->phone), shouldOpenInNewTab: true)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (DeliveryAgent $record): string => $record->location),

                Tables\Columns\TextColumn::make('pic_name')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('routes_count')
                    ->label('Rute / Armada')
                    ->counts('routes')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua Agen')
                    ->trueLabel('Hanya Agen Aktif')
                    ->falseLabel('Hanya Agen Nonaktif'),

                Tables\Filters\Filter::make('has_carter_routes')
                    ->label('Punya Layanan Carter')
                    ->query(fn (Builder $query) => $query->whereHas('routes', fn ($q) => $q->where('rate_type', 'carter'))),

                Tables\Filters\Filter::make('has_reguler_routes')
                    ->label('Punya Layanan Reguler')
                    ->query(fn (Builder $query) => $query->whereHas('routes', fn ($q) => $q->where('rate_type', 'reguler'))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryAgents::route('/'),
            'create' => Pages\CreateDeliveryAgent::route('/create'),
            'view' => Pages\ViewDeliveryAgent::route('/{record}'),
            'edit' => Pages\EditDeliveryAgent::route('/{record}/edit'),
        ];
    }
}

