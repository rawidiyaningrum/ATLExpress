<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\OnlyFullAdmins;
use App\Filament\Infolists\Components\InvoiceItemsTable;
use App\Filament\Resources\InvoiceResource\Concerns\AppliesInvoiceStatus;
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
    use AppliesInvoiceStatus;
    use OnlyFullAdmins;

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
                        ->label('Status')
                        ->formatStateUsing(fn (?string $state): string => Invoice::statusLabelFor($state))
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
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                            foreach (app(InvoiceService::class)->derivedUnitPrices(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            ) as $index => $price) {
                                $set("invoice_items.{$index}.unit_price", $price);
                            }
                        }),
                ])->columns(2),
            Forms\Components\Section::make('Informasi Administrasi')
                ->description('Dokumen pendukung yang tampil di bawah nomor invoice. Boleh dikosongkan.')
                ->schema([
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date'),
                    Forms\Components\TextInput::make('sppb_number')
                        ->label('SPPB No')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('packing_list_number')
                        ->label('Packing List No')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('period')
                        ->label('Periode')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('account_number')
                        ->label('No Account')
                        ->maxLength(255),
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
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                    $type = (string) $get('type');
                                    $rate = match ($type) {
                                        InvoiceService::TYPE_TAX => InvoiceService::PPN_RATE,
                                        InvoiceService::TYPE_DISCOUNT => InvoiceService::PPH_RATE,
                                        default => null,
                                    };

                                    if ($rate === null) {
                                        return;
                                    }

                                    $dpp = app(InvoiceService::class)->dpp(
                                        (float) $get('../../shipping_cost'),
                                        (array) $get('../../invoice_items'),
                                    );

                                    $set('unit_price', round($dpp * $rate, 2));
                                }),
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
            Forms\Components\Section::make('Ringkasan')
                ->description('PPN (1,1%) dan PPh (2%) dihitung dari Dasar Pengenaan Pajak (DPP) = ongkos kirim + biaya lain - diskon.')
                ->schema([
                    Forms\Components\Placeholder::make('summary_gross')
                        ->label('Ongkos Kirim + Biaya Lain')
                        ->content(function (Forms\Get $get): string {
                            $totals = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            );

                            return 'Rp '.number_format($totals['subtotal'] + $totals['diskon'], 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_diskon')
                        ->label('Potongan Diskon')
                        ->content(function (Forms\Get $get): string {
                            $diskon = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            )['diskon'];

                            return 'Rp -'.number_format($diskon, 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_subtotal')
                        ->label('Dasar Pengenaan Pajak (DPP)')
                        ->content(function (Forms\Get $get): string {
                            $totals = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            );

                            return 'Rp '.number_format($totals['subtotal'], 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_tax')
                        ->label('PPN (1,1%)')
                        ->content(function (Forms\Get $get): string {
                            $tax = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            )['tax'];

                            return 'Rp '.number_format($tax, 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_taxed')
                        ->label('Jumlah Tagihan Termasuk PPN')
                        ->content(function (Forms\Get $get): string {
                            $totals = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            );

                            return 'Rp '.number_format($totals['subtotal'] + $totals['tax'], 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_discount')
                        ->label('Potongan PPh (2%)')
                        ->content(function (Forms\Get $get): string {
                            $discount = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            )['discount'];

                            return 'Rp -'.number_format($discount, 0, ',', '.');
                        }),
                    Forms\Components\Placeholder::make('summary_total')
                        ->label('Total Tagihan')
                        ->content(function (Forms\Get $get): string {
                            $total = app(InvoiceService::class)->calculate(
                                (float) $get('shipping_cost'),
                                (array) $get('invoice_items'),
                            )['total'];

                            return 'Rp '.number_format($total, 0, ',', '.');
                        }),
                ])
                ->columns(3),
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
                    ->formatStateUsing(fn (Invoice $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => Invoice::STATUS_COLORS[$state] ?? 'gray'),
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
                Infolists\Components\TextEntry::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('sppb_number')
                    ->label('SPPB No')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('packing_list_number')
                    ->label('Packing List No')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('period')
                    ->label('Periode')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('account_number')
                    ->label('No Account')
                    ->placeholder('-'),
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
                Infolists\Components\TextEntry::make('gross')
                    ->label('Ongkos Kirim + Biaya Lain')
                    ->state(fn (Invoice $record): float => (float) $record->subtotal + (float) $record->diskon)
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('diskon')
                    ->label('Potongan Diskon')
                    ->money('IDR')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('subtotal')
                    ->label('Dasar Pengenaan Pajak (DPP)')
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('tax')
                    ->label('PPN (1,1%)')
                    ->money('IDR')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('with_tax')
                    ->label('Jumlah Tagihan Termasuk PPN')
                    ->state(fn (Invoice $record): float => (float) $record->subtotal + (float) $record->tax)
                    ->money('IDR'),
                Infolists\Components\TextEntry::make('discount')
                    ->label('Potongan PPh (2%)')
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
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('invoice_line1')
                        ->state(function (Invoice $record): string {
                            $invoice = $record->invoice_number ?: '-';
                            $awb = $record->shipment?->awb_number ?: '-';
                            $rute = $record->shipment ? "{$record->shipment->origin} → {$record->shipment->destination}" : '-';

                            return "Invoice: {$invoice} | AWB: {$awb} | Rute: {$rute}";
                        })
                        ->size('sm')
                        ->weight('medium')
                        ->searchable(query: fn (Builder $query, string $search): Builder => $query
                            ->where('invoices.invoice_number', 'like', "%{$search}%")
                            ->orWhereHas('shipment', fn (Builder $shipment): Builder => $shipment
                                ->where('awb_number', 'like', "%{$search}%")
                                ->orWhere('origin', 'like', "%{$search}%")
                                ->orWhere('destination', 'like', "%{$search}%"),
                            ),
                        ),

                    Tables\Columns\TextColumn::make('invoice_line2')
                        ->state(function (Invoice $record): string {
                            $billed = $record->billed_to_name ? \Illuminate\Support\Str::limit($record->billed_to_name, 40) : '-';
                            $status = $record->statusLabel();
                            $items = $record->items_count ?? $record->items()->count();

                            return "Tagihan ke: {$billed} | Status: {$status} | Item: {$items}";
                        })
                        ->size('xs')
                        ->color('gray')
                        ->searchable(['billed_to_name']),

                    Tables\Columns\TextColumn::make('invoice_line3')
                        ->state(function (Invoice $record): string {
                            $total = $record->total !== null ? 'Rp ' . number_format((float) $record->total, 0, ',', '.') : '-';
                            $created = $record->created_at ? $record->created_at->format('d M Y') : '-';

                            return "Total: {$total} | Dibuat: {$created}";
                        })
                        ->size('xs')
                        ->color('gray'),
                ])
                    ->space(1),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('shipment'))
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(Invoice::STATUS_LABELS),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('Lihat')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Invoice $record): string => static::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('cetak')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn (Invoice $record): bool => $record->shipment_id !== null)
                        ->url(fn (Invoice $record): string => PrintShipmentInvoice::getUrl(['record' => $record->shipment_id])),
                    ...static::statusTransitionActions(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Invoice $record): bool => $record->isDraft()),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (Invoice $record): bool => $record->isDraft()),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->color('gray'),
            ])
            // Penghapusan massal sengaja tidak disediakan: penghapusan invoice
            // yang sudah ditagihkan bisa meninggalkan jurnal yatim, sedangkan
            // penjaga di action baris hanya bekerja untuk satu record.
            ->bulkActions([]);
    }

    /**
     * Aksi pemindahan status di baris tabel invoice.
     *
     * Definisi diambil dari Invoice::STATUS_ACTIONS sehingga daftar invoice dan
     * halaman detailnya offering transisi yang sama persis, termasuk larangan
     * mundur lebih dari satu langkah.
     *
     * Nama aksi memuat status asal dan tujuan, bukan hanya tujuan. Tanpa itu,
     * "ke tertagih" yang dibuat dari draft akan menimpa definisi "ke tertagih"
     * dari lunas, karena registry Filament mengunci aksi berdasarkan nama.
     *
     * @return array<int, Tables\Actions\Action>
     */
    protected static function statusTransitionActions(): array
    {
        return collect(Invoice::STATUSES)
            ->flatMap(fn (string $from): array => collect(Invoice::STATUS_ACTIONS[$from] ?? [])
                ->map(fn (array $definition, string $to): Tables\Actions\Action => Tables\Actions\Action::make("status:{$from}:{$to}")
                    ->label($definition['label'])
                    ->icon($definition['icon'])
                    ->color($definition['color'])
                    ->requiresConfirmation()
                    ->modalHeading($definition['label'])
                    ->modalDescription($definition['description'])
                    ->modalSubmitActionLabel($definition['label'])
                    ->visible(fn (Invoice $record): bool => $record->status === $from)
                    ->action(fn (Invoice $record) => static::applyInvoiceStatus($record, $to)),
                )
                ->values()
                ->all(),
            )
            ->all();
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
