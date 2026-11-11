<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\ThreadsKeywordResource\Pages;
use App\Models\ThreadsKeyword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ThreadsKeywordResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = ThreadsKeyword::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Keyword Threads';

    protected static ?string $modelLabel = 'Keyword Threads';

    protected static ?string $pluralModelLabel = 'Keyword Threads';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->active()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Keyword')->schema([
                Forms\Components\TextInput::make('keyword')
                    ->label('Keyword / Tag')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Untuk topic tag, tulis tanpa tanda #. Kombinasi keyword + tipe harus unik.'),
                Forms\Components\Select::make('mode')
                    ->label('Tipe Pencarian')
                    ->options(ThreadsKeyword::modeOptions())
                    ->default(ThreadsKeyword::MODE_KEYWORD)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Hanya keyword aktif yang diambil saat fetch.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->label('Keyword / Tag')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('mode')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ThreadsKeyword::modeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === ThreadsKeyword::MODE_TAG ? 'info' : 'primary'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Terakhir Dijalankan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum pernah')
                    ->description(fn (ThreadsKeyword $record): ?string => $record->last_run_at?->diffForHumans())
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_hits')
                    ->label('Hasil Terakhir')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_hits')
                    ->label('Total Hasil')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
            ])
            ->defaultSort('is_active', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mode')
                    ->label('Tipe')
                    ->options(ThreadsKeyword::modeOptions()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'seed' => 'Kurasi Awal',
                        'ai' => 'Saran AI',
                        'manual' => 'Manual',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-pause-circle')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThreadsKeywords::route('/'),
            'create' => Pages\CreateThreadsKeyword::route('/create'),
            'edit' => Pages\EditThreadsKeyword::route('/{record}/edit'),
        ];
    }
}
