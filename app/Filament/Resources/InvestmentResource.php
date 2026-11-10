<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyFullAdmins;
use App\Filament\Resources\InvestmentResource\Pages;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentResource extends Resource
{
    use OnlyFullAdmins;

    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Investasi';

    protected static ?string $modelLabel = 'Investasi';

    protected static ?string $pluralModelLabel = 'Investasi';

    public static function getNavigationBadge(): ?string
    {
        $ongoingCount = static::getModel()::whereIn('status', [Investment::STATUS_TRANSFERRED, Investment::STATUS_ONGOING])->count();

        return $ongoingCount > 0 ? (string) $ongoingCount : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pihak & Alokasi Investasi')
                ->description('Pilih investor dan tujuan alokasi dana investasi (pengiriman / proyek).')
                ->schema([
                    Forms\Components\Select::make('investor_id')
                        ->label('Investor')
                        ->relationship('investor', 'name', fn ($query) => $query->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required()->label('Nama Investor'),
                            Forms\Components\TextInput::make('relation')->label('Relasi'),
                            Forms\Components\TextInput::make('phone')->tel()->label('Nomor Kontak / WA'),
                        ]),

                    Forms\Components\Select::make('shipment_id')
                        ->label('Shipment / Resi (AWB)')
                        ->relationship('shipment', 'awb_number')
                        ->getOptionLabelFromRecordUsing(fn (Shipment $record) => "{$record->awb_number} ({$record->origin} → {$record->destination})")
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (?int $state, Set $set, Get $get) {
                            if ($state) {
                                $shipment = Shipment::with(['latestInvoice', 'financeJournals'])->find($state);
                                if ($shipment instanceof Shipment) {
                                    $costModal = $shipment->getCostModal();
                                    // Hanya isi modal otomatis jika field amount belum pernah diisi atau 0
                                    $currentAmount = (float) $get('amount');
                                    if ($costModal > 0 && ($currentAmount === 0.0 || $currentAmount === null)) {
                                        $set('amount', $costModal);

                                        // Auto-calculate bagi hasil bila persentase sudah ada
                                        $percentage = (float) $get('profit_sharing_percentage');
                                        if ($percentage > 0) {
                                            $set('profit_sharing_amount', round($costModal * ($percentage / 100), 2));
                                        }
                                    }
                                }
                            }
                        })
                        ->helperText('Memilih shipment akan otomatis menarik estimasi cost modal pengiriman.'),

                    Forms\Components\TextInput::make('project_name')
                        ->label('Nama Proyek / Catatan Rute')
                        ->placeholder('Contoh: Ekspedisi Mesin Pabrik Surabaya - Bali')
                        ->maxLength(255)
                        ->helperText('Wajib diisi jika investasi tidak terkait nomor AWB tertentu.'),
                ])->columns(3),

            Forms\Components\Section::make('Nominal Modal & Kesepakatan Bagi Hasil')
                ->description('Nominal modal dan perhitungan persentase bagi hasil untuk investor.')
                ->schema([
                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal Investasi (Modal)')
                        ->prefix('Rp')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            $amount = (float) $state;
                            $percentage = (float) $get('profit_sharing_percentage');
                            if ($amount > 0 && $percentage > 0) {
                                $set('profit_sharing_amount', round($amount * ($percentage / 100), 2));
                            }
                        })
                        ->helperText('Otomatis terisi dari cost modal shipment namun dapat disesuaikan bebas.'),

                    Forms\Components\TextInput::make('profit_sharing_percentage')
                        ->label('Persentase Bagi Hasil')
                        ->suffix('%')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            $percentage = (float) $state;
                            $amount = (float) $get('amount');
                            if ($amount > 0 && $percentage > 0) {
                                $set('profit_sharing_amount', round($amount * ($percentage / 100), 2));
                            }
                        })
                        ->helperText('Contoh: 10, 15, atau 20'),

                    Forms\Components\TextInput::make('profit_sharing_amount')
                        ->label('Nominal Bagi Hasil')
                        ->prefix('Rp')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->helperText('Dihitung otomatis (Modal × Persentase) dan dapat diedit.'),

                    Forms\Components\DatePicker::make('investment_date')
                        ->label('Tanggal Investasi')
                        ->default(now())
                        ->required(),

                    Forms\Components\DatePicker::make('profit_sharing_date')
                        ->label('Tanggal Bagi Hasil / Jatuh Tempo')
                        ->helperText('Tanggal kesepakatan pembagian keuntungan.'),
                ])->columns(3),

            Forms\Components\Section::make('Status & Pencairan Dana')
                ->description('Status perjalanan investasi serta status pencairan pokok dan bagi hasil.')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status Investasi')
                        ->options(Investment::STATUSES)
                        ->default(Investment::STATUS_ONGOING)
                        ->required(),

                    Forms\Components\Fieldset::make('Pencairan Pokok Modal')
                        ->schema([
                            Forms\Components\Select::make('capital_payout_status')
                                ->label('Status Modal')
                                ->options(Investment::PAYOUT_STATUSES)
                                ->default(Investment::PAYOUT_PENDING)
                                ->required(),
                            Forms\Components\DatePicker::make('capital_payout_date')
                                ->label('Tanggal Pencairan Modal'),
                        ])->columns(2),

                    Forms\Components\Fieldset::make('Pencairan Bagi Hasil (Hutang Investor)')
                        ->schema([
                            Forms\Components\Select::make('profit_payout_status')
                                ->label('Status Bagi Hasil')
                                ->options(Investment::PAYOUT_STATUSES)
                                ->default(Investment::PAYOUT_PENDING)
                                ->required()
                                ->helperText('Status "Belum Dicairkan" akan masuk ke daftar Hutang Bagi Hasil.'),
                            Forms\Components\DatePicker::make('profit_payout_date')
                                ->label('Tanggal Pencairan Bagi Hasil'),
                        ])->columns(2),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Investasi')
                        ->placeholder('Catatan nomor bukti transfer, klausul pengembalian modal, dsb...')
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('investor.name')
                    ->label('Investor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Investment $record): ?string => $record->investor?->phone ? 'WA: '.$record->investor->phone : null),

                Tables\Columns\TextColumn::make('shipment_or_project')
                    ->label('Alokasi / AWB')
                    ->state(function (Investment $record): string {
                        if ($record->shipment) {
                            return $record->shipment->awb_number.' ('.$record->shipment->origin.' → '.$record->shipment->destination.')';
                        }

                        return $record->project_name ?: 'Proyek Umum';
                    })
                    ->searchable(['project_name'])
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Modal Investasi')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('profit_sharing_summary')
                    ->label('Bagi Hasil')
                    ->state(function (Investment $record): string {
                        $persen = number_format((float) $record->profit_sharing_percentage, 1, ',', '.').'%';
                        $nominal = 'Rp '.number_format((float) $record->profit_sharing_amount, 0, ',', '.');

                        return "{$persen} ({$nominal})";
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => Investment::STATUS_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => Investment::STATUSES[$state] ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('profit_payout_status')
                    ->label('Bagi Hasil')
                    ->badge()
                    ->color(fn (string $state): string => Investment::PAYOUT_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === Investment::PAYOUT_PENDING ? 'Hutang (Pending)' : 'Lunas')
                    ->description(fn (Investment $record): ?string => $record->profit_payout_date?->format('d M Y')),

                Tables\Columns\TextColumn::make('capital_payout_status')
                    ->label('Modal')
                    ->badge()
                    ->color(fn (string $state): string => Investment::PAYOUT_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === Investment::PAYOUT_PENDING ? 'Belum Cair' : 'Cair')
                    ->description(fn (Investment $record): ?string => $record->capital_payout_date?->format('d M Y')),

                Tables\Columns\TextColumn::make('investment_date')
                    ->label('Tgl Invest')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('investment_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('investor_id')
                    ->label('Investor')
                    ->relationship('investor', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Investasi')
                    ->options(Investment::STATUSES),

                Tables\Filters\SelectFilter::make('profit_payout_status')
                    ->label('Status Bagi Hasil (Hutang)')
                    ->options([
                        Investment::PAYOUT_PENDING => 'Hutang (Belum Dicairkan)',
                        Investment::PAYOUT_PAID => 'Sudah Dicairkan (Lunas)',
                    ]),

                Tables\Filters\SelectFilter::make('capital_payout_status')
                    ->label('Status Modal')
                    ->options(Investment::PAYOUT_STATUSES),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('mark_profit_paid')
                        ->label('Cairkan Bagi Hasil')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn (Investment $record): bool => $record->profit_payout_status !== Investment::PAYOUT_PAID)
                        ->requiresConfirmation()
                        ->modalHeading('Cairkan Bagi Hasil ke Investor')
                        ->modalDescription(fn (Investment $record): string => "Konfirmasi pencairan bagi hasil sebesar Rp ".number_format((float) $record->profit_sharing_amount, 0, ',', '.')." ke {$record->investor?->name}?")
                        ->action(function (Investment $record): void {
                            $record->update([
                                'profit_payout_status' => Investment::PAYOUT_PAID,
                                'profit_payout_date' => now()->toDateString(),
                            ]);
                        }),
                    Tables\Actions\Action::make('mark_capital_reimbursed')
                        ->label('Cairkan / Kembalikan Modal')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn (Investment $record): bool => $record->capital_payout_status !== Investment::PAYOUT_PAID)
                        ->requiresConfirmation()
                        ->modalHeading('Kembalikan Pokok Modal ke Investor')
                        ->modalDescription(fn (Investment $record): string => "Konfirmasi pengembalian pokok modal sebesar Rp ".number_format((float) $record->amount, 0, ',', '.')." ke {$record->investor?->name}?")
                        ->action(function (Investment $record): void {
                            $record->update([
                                'capital_payout_status' => Investment::PAYOUT_PAID,
                                'capital_payout_date' => now()->toDateString(),
                                'status' => Investment::STATUS_REIMBURSED,
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'edit' => Pages\EditInvestment::route('/{record}/edit'),
        ];
    }
}
