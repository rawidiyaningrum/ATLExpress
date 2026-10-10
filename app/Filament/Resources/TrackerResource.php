<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\TrackerResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrackerResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Input User Tracker';

    protected static ?string $modelLabel = 'User Tracker';

    protected static ?string $pluralModelLabel = 'User Tracker';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Akun')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->helperText('Kosongkan untuk password acak otomatis.')
                    ->dehydrated(fn ($state) => filled($state)),
            ])->columns(3),
            Forms\Components\Section::make('Mode Pendaftaran')->schema([
                Forms\Components\Toggle::make('as_invitation')
                    ->label('Buat sebagai undangan')
                    ->helperText('Tracker akan melengkapi pendaftaran sendiri di '.url('admin/register').' menggunakan email ini.')
                    ->default(fn (?Model $record) => filled($record?->invited_at)),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (User $record) => filled($record->invited_at) ? 'Undangan' : 'Aktif')
                    ->badge()
                    ->color(fn (User $record) => filled($record->invited_at) ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('invited_at')
                    ->label('Diundang')
                    ->dateTime()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', User::ROLE_TRACKER);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackers::route('/'),
            'create' => Pages\CreateTracker::route('/create'),
            'edit' => Pages\EditTracker::route('/{record}/edit'),
        ];
    }
}
