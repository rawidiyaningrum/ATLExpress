<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyFullAdmins;
use App\Filament\Resources\InvestorResource\Pages;
use App\Models\Investor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvestorResource extends Resource
{
    use OnlyFullAdmins;

    protected static ?string $model = Investor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Investor';

    protected static ?string $modelLabel = 'Investor';

    protected static ?string $pluralModelLabel = 'Investor';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_active', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pribadi & Kontak')
                ->description('Identitas investor dan relasi dengan pemilik / perusahaan.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Investor')
                        ->placeholder('Contoh: Budi Santoso / PT Mitra Investama')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('ktp_number')
                        ->label('No. KTP')
                        ->placeholder('Contoh: 3174012345670001')
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('Nomor KTP / identitas investor (16 digit, tanpa spasi).'),

                    Forms\Components\TextInput::make('relation')
                        ->label('Relasi')
                        ->placeholder('Contoh: Rekan Bisnis / Keluarga / Sahabat / Mitra Kerja')
                        ->datalist([
                            'Rekan Bisnis',
                            'Keluarga',
                            'Sahabat',
                            'Mitra Kerja',
                            'Partner Strategis',
                            'Angel Investor',
                        ])
                        ->maxLength(100),

                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor Kontak / WhatsApp')
                        ->placeholder('Contoh: 0812-3456-7890')
                        ->tel()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('email')
                        ->label('Alamat Email')
                        ->placeholder('Contoh: investor@example.com')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('address')
                        ->label('Alamat Lengkap')
                        ->placeholder('Alamat domisili atau kantor investor...')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Informasi Rekening Bank')
                ->description('Rekening tujuan untuk transfer pencairan modal dan bagi hasil investasi.')
                ->schema([
                    Forms\Components\TextInput::make('bank_name')
                        ->label('Nama Bank')
                        ->placeholder('Contoh: BCA / Mandiri / BRI / BNI')
                        ->datalist(['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga', 'Permata'])
                        ->maxLength(50),

                    Forms\Components\TextInput::make('bank_account_number')
                        ->label('Nomor Rekening')
                        ->placeholder('Contoh: 1234567890')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('bank_account_name')
                        ->label('Nama Pemilik Rekening')
                        ->placeholder('Sesuai nama di buku tabungan')
                        ->maxLength(255),
                ])->columns(3),

            Forms\Components\Section::make('Status & Catatan')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Investor Aktif')
                        ->default(true)
                        ->helperText('Investor nonaktif tidak akan muncul di daftar pilihan investasi baru.'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Tambahan')
                        ->placeholder('Catatan perjanjian khusus, preferensi rute/project, dll...')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Investor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Investor $record): ?string => $record->relation ? 'Relasi: '.$record->relation : null),

                Tables\Columns\TextColumn::make('ktp_number')
                    ->label('No. KTP')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->icon('heroicon-m-identification')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('bank_account')
                    ->label('Rekening Bank')
                    ->state(function (Investor $record): string {
                        if (! $record->bank_name && ! $record->bank_account_number) {
                            return '-';
                        }

                        $parts = array_filter([
                            $record->bank_name,
                            $record->bank_account_number,
                            $record->bank_account_name ? 'a/n '.$record->bank_account_name : null,
                        ]);

                        return implode(' - ', $parts);
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('active_investments_count')
                    ->label('Investasi Berjalan')
                    ->counts([
                        'investments' => fn (Builder $query) => $query->whereIn('status', ['transferred', 'ongoing']),
                    ])
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Investor Aktif')
                    ->falseLabel('Investor Nonaktif'),

                Tables\Filters\SelectFilter::make('relation')
                    ->label('Relasi')
                    ->options(fn () => Investor::query()->whereNotNull('relation')->distinct()->pluck('relation', 'relation')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListInvestors::route('/'),
            'create' => Pages\CreateInvestor::route('/create'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
        ];
    }
}
