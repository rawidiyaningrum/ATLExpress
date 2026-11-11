<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusUpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusUpdates';

    protected static ?string $title = 'Riwayat Status';

    protected static ?string $modelLabel = 'riwayat status';

    protected static ?string $pluralModelLabel = 'riwayat status';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(Project::STATUS_LABELS)
                ->required()
                ->default(fn (): string => (string) $this->getOwnerRecord()->status),

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
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('update_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Project::statusLabel($state))
                    ->color(fn (?string $state): string => Project::STATUS_COLORS[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->wrap()
                    ->placeholder('-'),

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
                // Riwayat bersifat append-only: baris lama tidak diedit supaya
                // tidak menimpa status terkini. Penghapusan dibatasi admin.
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdminRole() ?? false),
            ])
            ->bulkActions([]);
    }
}
