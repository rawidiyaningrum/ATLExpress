<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\ProjectHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Riwayat';

    protected static ?string $modelLabel = 'riwayat';

    protected static ?string $pluralModelLabel = 'riwayat';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Judul Riwayat')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(ProjectHistory::STATUS_LABELS)
                ->default(ProjectHistory::STATUS_TODO)
                ->required(),

            Forms\Components\DatePicker::make('update_date')
                ->label('Tanggal')
                ->required()
                ->default(now()),

            Forms\Components\TextInput::make('progress_percent')
                ->label('Progress')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%'),

            Forms\Components\Textarea::make('note')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Riwayat')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ProjectHistory::statusLabel($state))
                    ->color(fn (?string $state): string => ProjectHistory::STATUS_COLORS[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('update_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->placeholder('-'),
            ])
            ->defaultSort('update_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Riwayat')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdminRole() ?? false),
            ])
            ->bulkActions([]);
    }
}
