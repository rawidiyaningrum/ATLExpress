<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Project';

    protected static ?string $modelLabel = 'project';

    protected static ?string $pluralModelLabel = 'project';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Semua role boleh melihat dan mengubah project. Penghapusan dibatasi
     * admin (penuh atau ATL) supaya tracker tidak menghapus data.
     */
    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView(Model $record): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->check();
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
            Forms\Components\Section::make('Informasi Project')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Project')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('pic_id')
                        ->label('PIC (Penanggung Jawab)')
                        ->relationship('pic', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('priority')
                        ->label('Prioritas')
                        ->options(Project::PRIORITY_LABELS)
                        ->default(Project::PRIORITY_NORMAL)
                        ->required(),

                    Forms\Components\TextInput::make('progress_percent')
                        ->label('Progress Keseluruhan')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('Opsional. Status pekerjaan per item ada di tab "Riwayat".'),

                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Jadwal')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')->label('Tanggal Mulai'),
                    Forms\Components\DatePicker::make('due_date')->label('Tenggat'),
                ])->columns(2),

            Forms\Components\Section::make('Kaitan Pengiriman')
                ->description('Opsional: hubungkan project dengan pengiriman yang sudah ada.')
                ->schema([
                    Forms\Components\Select::make('shipment_id')
                        ->label('Shipment')
                        ->relationship('shipment', 'awb_number')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Kosongkan kalau project berdiri sendiri.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Project')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Project $record): ?string => $record->description ? Str::limit($record->description, 60) : null),

                Tables\Columns\TextColumn::make('pic.name')
                    ->label('PIC')
                    ->searchable()
                    ->placeholder('Tanpa PIC'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Project::PRIORITY_LABELS[$state] ?? ucfirst((string) $state))
                    ->color(fn (?string $state): string => Project::PRIORITY_COLORS[$state] ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('histories_count')
                    ->label('Riwayat')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['pic'])
                ->withCount('histories')
                ->whereNull('archived_at'))
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options(Project::PRIORITY_LABELS),

                Tables\Filters\SelectFilter::make('pic_id')
                    ->label('PIC')
                    ->relationship('pic', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('belum_ada_riwayat')
                    ->label('Belum ada riwayat')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->doesntHave('histories')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdminRole() ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\HistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
