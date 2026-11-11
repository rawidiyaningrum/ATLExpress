<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\ThreadsPostResource\Pages;
use App\Filament\Resources\ThreadsPostResource\Widgets;
use App\Models\ShippingRequest;
use App\Models\ThreadsPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class ThreadsPostResource extends Resource
{
    use OnlyAdmins;

    protected static ?string $model = ThreadsPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Permintaan Kargo Threads';

    protected static ?string $modelLabel = 'Permintaan Kargo Threads';

    protected static ?string $pluralModelLabel = 'Permintaan Kargo Threads';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->status(ThreadsPost::STATUS_NEW)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Field yang harus dilengkapi admin saat mengubah postingan Threads
     * menjadi Permintaan Pengiriman. Sebagian sudah terisi dari isi postingan.
     */
    public static function conversionForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nama Pemesan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('No. Telepon / WhatsApp')
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('origin')
                ->label('Kota Asal')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('destination')
                ->label('Kota Tujuan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('item_type')
                ->label('Jenis Barang')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('weight')
                ->label('Berat (kg)')
                ->numeric()
                ->required()
                ->minValue(0.1),
            Forms\Components\Textarea::make('pickup_address')
                ->label('Alamat Penjemputan')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->rows(4)
                ->columnSpanFull(),
        ];
    }

    /**
     * Buat Permintaan Pengiriman dari postingan Threads lalu tandai
     * postingan sebagai sudah dihubungi.
     *
     * @param  array<string, mixed>  $data
     */
    public static function convertToShippingRequest(ThreadsPost $record, array $data): ShippingRequest
    {
        $request = ShippingRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'origin' => $data['origin'],
            'destination' => $data['destination'],
            'item_type' => $data['item_type'],
            'weight' => $data['weight'],
            'pickup_address' => $data['pickup_address'],
            'notes' => $data['notes'] ?? null,
            'status' => 'new',
        ]);

        $record->update(['status' => ThreadsPost::STATUS_CONTACTED]);

        return $request;
    }

    /**
     * Nilai awal form konversi, diambil dari isi postingan Threads.
     *
     * @return array<string, mixed>
     */
    public static function conversionDefaults(ThreadsPost $record): array
    {
        return [
            'name' => $record->username ? '@'.$record->username : '',
            'item_type' => self::guessItemType((string) $record->text),
            'notes' => trim((string) $record->text)."\n\nSumber: ".($record->permalink ?? '-'),
        ];
    }

    private static function guessItemType(string $text): string
    {
        $text = strtolower($text);

        return match (true) {
            str_contains($text, 'motor') => 'Motor',
            str_contains($text, 'mobil') => 'Mobil',
            str_contains($text, 'furniture'), str_contains($text, 'perabot') => 'Furniture',
            str_contains($text, 'pindah') => 'Barang Pindahan',
            default => 'Barang Umum',
        };
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Postingan Threads')->schema([
                Forms\Components\Placeholder::make('username')->label('Akun')
                    ->content(fn (ThreadsPost $record): string => $record->username ? '@'.$record->username : '-'),
                Forms\Components\Placeholder::make('posted_at')->label('Waktu Posting')
                    ->content(fn (ThreadsPost $record): string => $record->posted_at?->format('d M Y H:i') ?? '-'),
                Forms\Components\Placeholder::make('matched_keyword')->label('Keyword Cocok')
                    ->content(fn (ThreadsPost $record): string => $record->matched_keyword ?? '-'),
                Forms\Components\Placeholder::make('text')->label('Isi Postingan')
                    ->columnSpanFull()
                    ->content(fn (ThreadsPost $record): string => $record->text ?? '-'),
                Forms\Components\Placeholder::make('permalink')->label('Tautan')
                    ->columnSpanFull()
                    ->content(fn (ThreadsPost $record) => $record->permalink
                        ? new HtmlString('<a class="text-primary-600 underline" target="_blank" rel="noopener" href="'.e($record->permalink).'">'.e($record->permalink).'</a>')
                        : '-'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->description(fn (ThreadsPost $record): ?string => $record->posted_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('username')
                    ->label('Akun')
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '-')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('text')
                    ->label('Isi')
                    ->limit(90)
                    ->wrap()
                    ->tooltip(fn (ThreadsPost $record): ?string => $record->text)
                    ->searchable(),

                Tables\Columns\TextColumn::make('matched_keyword')
                    ->label('Keyword')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'api' ? 'API' : 'Scrape')
                    ->color(fn (string $state): string => $state === 'api' ? 'success' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ThreadsPost::statusLabel($state))
                    ->color(fn (string $state): string => ThreadsPost::statusColor($state)),

                Tables\Columns\IconColumn::make('has_replies')
                    ->label('Ada Balasan')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('permalink')
                    ->label('Tautan')
                    ->url(fn (ThreadsPost $record): ?string => $record->permalink, shouldOpenInNewTab: true)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->formatStateUsing(fn (): string => 'Buka')
                    ->color('info'),
            ])
            ->defaultSort('posted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ThreadsPost::statusOptions()),

                Tables\Filters\SelectFilter::make('matched_keyword')
                    ->label('Keyword')
                    ->options(fn () => ThreadsPost::query()
                        ->whereNotNull('matched_keyword')
                        ->distinct()
                        ->orderBy('matched_keyword')
                        ->pluck('matched_keyword', 'matched_keyword')
                        ->all()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'api' => 'API resmi',
                        'scrape' => 'Scraping',
                    ]),

                Tables\Filters\TernaryFilter::make('has_replies')
                    ->label('Ada Balasan'),

                Tables\Filters\Filter::make('posted_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('posted_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('posted_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('bukaThreads')
                    ->label('Buka di Threads')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ThreadsPost $record): ?string => $record->permalink)
                    ->openUrlInNewTab()
                    ->visible(fn (ThreadsPost $record): bool => ! empty($record->permalink)),

                Tables\Actions\Action::make('contacted')
                    ->label('Tandai Dihubungi')
                    ->icon('heroicon-o-phone')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (ThreadsPost $record) => $record->update(['status' => ThreadsPost::STATUS_CONTACTED]))
                    ->visible(fn (ThreadsPost $record): bool => $record->status === ThreadsPost::STATUS_NEW),

                Tables\Actions\Action::make('jadikanPermintaan')
                    ->label('Jadikan Permintaan Pengiriman')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->modalHeading('Buat Permintaan Pengiriman')
                    ->modalDescription(fn (ThreadsPost $record): string => 'Dari postingan @'.($record->username ?? '-').'. Lengkapi data yang belum tersedia.')
                    ->form(self::conversionForm())
                    ->fillForm(fn (ThreadsPost $record): array => self::conversionDefaults($record))
                    ->action(function (array $data, ThreadsPost $record): void {
                        $request = self::convertToShippingRequest($record, $data);

                        Notification::make()
                            ->title('Permintaan pengiriman dibuat')
                            ->body('Postingan ditandai sudah dihubungi.')
                            ->success()
                            ->actions([
                                NotificationAction::make('buka')
                                    ->label('Buka Permintaan')
                                    ->url(ShippingRequestResource::getUrl('index'))
                                    ->button(),
                            ])
                            ->send();
                    })
                    ->visible(fn (ThreadsPost $record): bool => $record->status !== ThreadsPost::STATUS_WON),

                Tables\Actions\Action::make('won')
                    ->label('Jadi Pelanggan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (ThreadsPost $record) => $record->update(['status' => ThreadsPost::STATUS_WON]))
                    ->visible(fn (ThreadsPost $record): bool => $record->status !== ThreadsPost::STATUS_WON),

                Tables\Actions\Action::make('ignored')
                    ->label('Diabaikan')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (ThreadsPost $record) => $record->update(['status' => ThreadsPost::STATUS_IGNORED]))
                    ->visible(fn (ThreadsPost $record): bool => $record->status !== ThreadsPost::STATUS_IGNORED),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('contacted')
                        ->label('Tandai Dihubungi')
                        ->icon('heroicon-o-phone')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => ThreadsPost::STATUS_CONTACTED]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('ignored')
                        ->label('Diabaikan')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => ThreadsPost::STATUS_IGNORED]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\ThreadsPostOverviewStats::class,
            Widgets\ThreadsTopKeywordsChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThreadsPosts::route('/'),
            'view' => Pages\ViewThreadsPost::route('/{record}'),
        ];
    }
}
