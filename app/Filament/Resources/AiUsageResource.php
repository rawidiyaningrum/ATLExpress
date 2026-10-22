<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\AiUsageResource\Pages;
use App\Models\AiUsage;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class AiUsageResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = AiUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'Pemakaian AI';

    protected static ?string $modelLabel = 'Pemakaian AI';

    protected static ?string $pluralModelLabel = 'Pemakaian AI';

    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()?->isAdminRole() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->label('Kegunaan')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('prompt_tokens')
                    ->label('Token Input')
                    ->numeric(),
                Tables\Columns\TextColumn::make('output_tokens')
                    ->label('Token Output')
                    ->numeric(),
                Tables\Columns\TextColumn::make('estimated_cost_usd')
                    ->label('Biaya (USD)')
                    ->money('USD', 4)
                    ->summarize(Sum::make()->money('USD', 4)),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('error')
                    ->label('Keterangan')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiUsages::route('/'),
        ];
    }
}
