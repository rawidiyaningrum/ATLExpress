<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Inquiry Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255)
                    ->email()
                    ->disabled(),
                Forms\Components\TextInput::make('phone')
                    ->nullable()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\TextInput::make('subject')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\Textarea::make('message')
                    ->nullable()
                    ->disabled(),
                Forms\Components\Toggle::make('is_read')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->label('Read'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(fn (Inquiry $record) => $record->update(['is_read' => true]))
                    ->visible(fn (Inquiry $record) => !$record->is_read),
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
            'index' => Pages\ListInquiries::route('/'),
            'view' => Pages\ViewInquiry::route('/{record}'),
        ];
    }
}
