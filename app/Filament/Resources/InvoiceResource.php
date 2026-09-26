<?php

namespace App\Filament\Resources;

use App\Filament\Infolists\Components\InvoiceItemsTable;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\ShipmentResource\Pages\PrintInvoice as PrintShipmentInvoice;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Shipping';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'invoice';

    protected static ?string $pluralModelLabel = 'invoice';

    /**
     * Invoice hanya lahir dari wizard shipment, tidak dibuat manual.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $service = app(InvoiceService::class);

        return $form->schema([
            Forms\Components\Section::make('Invoice')
                ->description('Nomor invoice dan statusnya tidak dapat diubah dari sini.')
                ->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Nomor Invoice')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('status')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('billed_to_name')
                        ->label('Nama Dicatat/tagih')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('billed_to_address')
                        ->label('Alamat Penagihan')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('shipping_cost')
                        ->label('Ongkos Kirim')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),
                ])->columns(2),
            Forms\Components\Section::make('Item')
                ->description('Baris tanpa keterangan diabaikan. Nominal dan total dihitung ulang saat disimpan.')
                ->schema([
                    Forms\Components\Repeater::make('invoice_items')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('Keterangan')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\Select::make('type')
                                ->label('Jenis')
                                ->options($service->itemTypeOptions())
                                ->default(InvoiceService::TYPE_ADDITIONAL)
                                ->required(),
                            Forms\Components\Select::make('basis')
                                ->label('Dihitung dari')
                                ->options($service->itemBasisOptions())
                                ->default(InvoiceService::BASIS_FINAL_TARIFF)
                                ->helperText('Dasar nominal baris ini.'),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->default(0),
                            Forms\Components\Placeholder::make('line_total_preview')
                                ->label('Jumlah Baris')
                                ->content(function (Forms\Get $get): string {
                                    $line = (int) $get('quantity') * (float) $get('unit_price');

                                    return 'Rp '.number_format($line, 0, ',', '.');
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah item'),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Invoice')->schema([
                Infolists\Components\TextEntry::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->copyable(),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'final' => 'success',
                        default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('shipment.awb_number')
                    ->label('Nomor AWB')
                    ->placeholder('-')
                    ->copyable(),
                Infolists\Components\TextEntry::make('shipment')
                    ->label('Rute')
                    ->state(fn (Invoice $record): string => $record->shipment
                        ? "{$record->shipment->origin} → {$record->shipment->destination}"
                        : '-')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Dibuat')
                    ->dateTime(),
            ])->columns(2),
            Infolists\Components\Section::make('Penagihan')->schema([
                Infolists\Components\TextEntry::make('billed_to_name')
                    ->label('Nama Dicatat/tagih')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('billed_to_address')
                    ->label('Alamat Penagihan')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]),
            Infolists\Components\Section::make('Rincian')->schema([
                InvoiceItemsTable::make('items')
                    ->label('Rincian item')
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]),
            Infolists\Components\Section::make('Total')->schema([
                Infolists\Components\TextEntry::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('discount')
                    ->label('Diskon')
                    ->money('IDR')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('tax')
                    ->label('Pajak')
                    ->money('IDR')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('total')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('invoices.invoice_number', 'like', "%{$search}%")
                        ->orWhere('invoices.billed_to_name', 'like', "%{$search}%")
                        ->orWhereHas('shipment', fn (Builder $shipment): Builder => $shipment
                            ->where('awb_number', 'like', "%{$search}%")
                            ->orWhere('origin', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%"),
                        ),
                    )
                    ->sortable()
                    ->copyable()
                    ->formatStateUsing(fn (Invoice $record): HtmlString => new HtmlString(
                        '<span class="flex flex-wrap items-center gap-1.5">'
                        .'<span>'.e($record->invoice_number).'</span>'
                        .static::rowLink(static::getUrl('view', ['record' => $record]), 'lihat')
                        .($record->shipment_id !== null
                            ? static::rowLink(PrintShipmentInvoice::getUrl(['record' => $record->shipment_id]), 'cetak')
                            : '')
                        .'</span>',
                    ))
                    ->description(fn (Invoice $record): string => collect([
                        $record->shipment?->awb_number,
                        $record->shipment ? "{$record->shipment->origin} → {$record->shipment->destination}" : null,
                        $record->billed_to_name,
                    ])->filter()->implode(' · ') ?: '-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'final' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items')
                    ->placeholder('0'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('shipment'))
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'final' => 'Final',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Invoice $record): bool => $record->status !== 'final'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Invoice $record): bool => $record->status !== 'final'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(fn () => abort_if(
                            Invoice::query()
                                ->whereKey($this->selection)
                                ->where('status', '!=', 'draft')
                                ->exists(),
                            422,
                            'Invoice yang sudah final tidak dapat dihapus.'
                        )),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Link di dalam sel kolom Invoice, dengan tampilan badge seperti kolom
     * status. Markup ini dirakit manual karena state kolom dikembalikan
     * sebagai HtmlString, jadi nilai dari database tetap harus di-escape.
     */
    protected static function rowLink(string $url, string $label): string
    {
        return '<a'
            .' href="'.e($url).'"'
            .' class="fi-badge flex cursor-pointer items-center justify-center gap-x-1 gap-y-1 rounded-md px-2 py-1'
            .' text-xs font-medium tracking-tight ring-1 ring-inset transition'
            .' bg-gray-50 text-gray-600 ring-gray-600/10 hover:bg-gray-100 hover:text-gray-700'
            .' dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20 dark:hover:text-gray-300'
            .'">'
            .'<span class="grid"><span class="truncate">'.e($label).'</span></span>'
            .'</a>';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
