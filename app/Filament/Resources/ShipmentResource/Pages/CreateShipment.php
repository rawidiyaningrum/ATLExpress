<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Concerns\HasInvoiceForm;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\InvoiceService;
use App\Services\TariffCalculatorService;
use App\Support\NumberGenerator;
use Filament\Forms;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\Rule;

class CreateShipment extends CreateRecord
{
    use HasInvoiceForm;
    use HasWizard;

    protected static string $resource = ShipmentResource::class;

    /**
     * @return array<Step>
     */
    public function getSteps(): array
    {
        return [
            $this->detailStep(),
            $this->weightAndTariffStep(),
            $this->airwayBillStep(),
            $this->invoiceStep(),
            $this->printInvoiceStep(),
        ];
    }

    /**
     * Setiap langkah menyimpan progresnya sendiri, sehingga draft tetap utuh
     * walau staf menutup wizard di tengah jalan. Langkah pertama membuat
     * record; langkah berikutnya menempelkannya ke draft yang sama.
     */
    public function create(bool $another = false): void
    {
        if ($this->record === null) {
            parent::create($another);

            return;
        }

        $this->authorizeAccess();
        $this->beginDatabaseTransaction();

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');
            $this->callHook('beforeCreate');

            $this->record->update($this->shipmentAttributes($data));

            $this->callHook('afterCreate');

            $this->commitDatabaseTransaction();
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->getCreatedNotification()?->send();

        if ($another) {
            $this->redirect(ShipmentResource::getUrl('index'));

            return;
        }

        if ($this->currentInvoice() !== null) {
            $this->redirect(PrintInvoice::getUrl(['record' => $this->record]));

            return;
        }

        $this->redirect(ShipmentResource::getUrl('edit', ['record' => $this->record]));
    }

    protected function detailStep(): Step
    {
        return Step::make('Buat Pengiriman')
            ->description('Data pengirim, penerima, rute, dan layanan')
            ->icon('heroicon-o-identification')
            ->afterValidation(fn (Step $step) => $this->persistStep($step))
            ->schema([
                Forms\Components\Section::make('Pengirim')
                    ->schema([
                        Forms\Components\TextInput::make('sender_name')
                            ->label('Nama Pengirim')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sender_phone')
                            ->label('Telepon Pengirim')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\Textarea::make('sender_address')
                            ->label('Alamat Pengirim')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Penerima')
                    ->schema([
                        Forms\Components\TextInput::make('receiver_name')
                            ->label('Nama Penerima')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('receiver_phone')
                            ->label('Telepon Penerima')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\Textarea::make('receiver_address')
                            ->label('Alamat Penerima')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Barang')
                    ->description('Keterangan jenis atau isi barang, dicetak pada airway bill.')
                    ->schema([
                        Forms\Components\TextInput::make('item_type')
                            ->label('Jenis/Isi Barang')
                            ->placeholder('Contoh: Dokumen, sparepart elektronik')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Rute & Layanan')
                    ->description('Kota diambil dari tabel tarif. Pilih jenis layanan memakai tombol pada panel Informasi Tarif di bawah.')
                    ->schema([
                        Forms\Components\Select::make('origin')
                            ->label('Kota Asal')
                            ->options(fn (): array => app(TariffCalculatorService::class)->getOriginOptions())
                            ->searchable()
                            ->required()
                            ->rule(fn (): array => [Rule::in(array_keys(app(TariffCalculatorService::class)->getOriginOptions()))])
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $set('kabupaten_tujuan', null);
                                $set('destination', null);
                                $this->syncServiceType($get, $set);
                                $this->applyRate($get, $set);
                            }),
                        Forms\Components\Select::make('kabupaten_tujuan')
                            ->label('Kabupaten Tujuan')
                            ->options(fn (Forms\Get $get): array => app(TariffCalculatorService::class)->getKabupatenOptions((string) $get('origin')))
                            ->getSearchResultsUsing(fn (Forms\Get $get, string $search): array => app(TariffCalculatorService::class)->searchKabupatenOptions((string) $get('origin'), $search))
                            ->getOptionLabelUsing(fn ($value): ?string => blank($value) ? null : (string) $value)
                            ->searchable()
                            ->required()
                            ->disabled(fn (Forms\Get $get): bool => blank($get('origin')))
                            ->rule(fn (Forms\Get $get): array => [Rule::in(array_keys(app(TariffCalculatorService::class)->getKabupatenOptions((string) $get('origin'))))])
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $set('destination', null);
                                $this->syncServiceType($get, $set);
                                $this->applyRate($get, $set);
                            }),
                        Forms\Components\Select::make('destination')
                            ->label('Kota Tujuan')
                            ->options(fn (Forms\Get $get): array => app(TariffCalculatorService::class)->getDestinationOptions((string) $get('origin'), (string) $get('kabupaten_tujuan')))
                            ->getSearchResultsUsing(fn (Forms\Get $get, string $search): array => app(TariffCalculatorService::class)->searchDestinationOptions((string) $get('origin'), (string) $get('kabupaten_tujuan'), $search))
                            ->getOptionLabelUsing(fn ($value): ?string => blank($value) ? null : (string) $value)
                            ->searchable()
                            ->required()
                            ->disabled(fn (Forms\Get $get): bool => blank($get('kabupaten_tujuan')))
                            ->rule(fn (Forms\Get $get): array => [Rule::in(array_keys(app(TariffCalculatorService::class)->getDestinationOptions((string) $get('origin'), (string) $get('kabupaten_tujuan'))))])
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $this->syncServiceType($get, $set);
                                $this->applyRate($get, $set);
                            }),
                        Forms\Components\Hidden::make('service_type')
                            ->required()
                            ->rule(fn (Forms\Get $get): array => [Rule::in(array_keys($this->availableServices($get)))]),
                    ])
                    ->columns(3),
                $this->tariffComparisonSection(),
            ]);
    }

    /**
     * Perbandingan tarif untuk rute yang sedang dipilih.
     *
     * Muncul begitu asal dan tujuan terisi, jadi operator bisa membandingkan
     * sebelum memilih layanan; layanan yang dipakai ditandai setelah dipilih.
     *
     * Satu Fieldset per jenis layanan yang dikenali, disembunyikan bila layanan
     * itu tidak punya tarif untuk rute tersebut. Skemanya dibangun sekali saat
     * form dibuat, sedangkan visibilitas dihitung ulang tiap render, sehingga
     * layanan yang baru diseed otomatis ikut tampil tanpa mengubah kode.
     */
    protected function tariffComparisonSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Informasi Tarif')
            ->description('Perbandingan tarif per kg untuk rute ini. Pilih jenis layanan di atas untuk menandai yang dipakai; tarif final dihitung di langkah 2 setelah berat diketahui.')
            ->visible(fn (Forms\Get $get): bool => $this->routeIsKnown($get))
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema(
                        array_map(
                            fn (string $serviceType): Forms\Components\Fieldset => $this->serviceRateFieldset($serviceType),
                            array_keys(app(TariffCalculatorService::class)->serviceTypes()),
                        )
                    ),
            ]);
    }

    /**
     * Kartu tarif satu layanan, lengkap dengan tombol untuk memilihnya.
     *
     * Tombol menulis service_type lalu memanggil applyRate(), jadi tarif per kg
     * di langkah 2 ikut terisi seperti saat dropdown biasa berubah.
     */
    protected function serviceRateFieldset(string $serviceType): Forms\Components\Fieldset
    {
        $labels = app(TariffCalculatorService::class)->serviceTypes();
        $label = $labels[$serviceType] ?? ucfirst($serviceType);

        return Forms\Components\Fieldset::make($label)
            ->label(fn (Forms\Get $get): string => $get('service_type') === $serviceType
                ? $label.' — dipilih'
                : $label)
            ->hidden(fn (Forms\Get $get): bool => $this->rateForService($get, $serviceType) === null)
            ->schema([
                Forms\Components\Placeholder::make("{$serviceType}_price")
                    ->label('Tarif per kg')
                    ->content(function (Forms\Get $get) use ($serviceType): string {
                        $rate = $this->rateForService($get, $serviceType);

                        return 'Rp '.$this->rupiah((float) ($rate['price_per_kg'] ?? 0)).' / kg';
                    }),
                Forms\Components\Placeholder::make("{$serviceType}_min_weight")
                    ->label('Berat minimum')
                    ->content(function (Forms\Get $get) use ($serviceType): string {
                        $rate = $this->rateForService($get, $serviceType);

                        return $this->rupiah((float) ($rate['min_weight'] ?? 0)).' kg';
                    }),
                Forms\Components\Placeholder::make("{$serviceType}_estimated_days")
                    ->label('Estimasi tiba')
                    ->content(function (Forms\Get $get) use ($serviceType): string {
                        $rate = $this->rateForService($get, $serviceType);

                        return $rate === null ? '-' : $rate['estimated_days'].' hari';
                    }),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make("pilih_{$serviceType}")
                        ->label('Pilih')
                        ->icon('heroicon-o-check-circle')
                        ->disabled(fn (Forms\Get $get): bool => $get('service_type') === $serviceType)
                        ->color(fn (Forms\Get $get): string => $get('service_type') === $serviceType ? 'gray' : 'primary')
                        ->action(function (Forms\Get $get, Forms\Set $set) use ($serviceType): void {
                            $set('service_type', $serviceType);
                            $this->applyRate($get, $set);
                        }),
                ])
                    ->columnSpanFull(),
            ]);
    }

    protected function weightAndTariffStep(): Step
    {
        return Step::make('Berat & Tarif')
            ->description('Berat akhir dan tarif per kg')
            ->icon('heroicon-o-scale')
            ->afterValidation(fn (Step $step) => $this->persistStep($step))
            ->schema([
                Forms\Components\Section::make('Berat & Dimensi')
                    ->description('Panjang, lebar, dan tinggi diisi terpisah dalam cm.')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('kg')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                                $this->suggestPricePerKg($get, $set);
                                $set('final_tariff', $this->calculatedTotal($get));
                            }),
                        Forms\Components\TextInput::make('dimension_length')
                            ->label('Panjang')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('cm'),
                        Forms\Components\TextInput::make('dimension_width')
                            ->label('Lebar')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('cm'),
                        Forms\Components\TextInput::make('dimension_height')
                            ->label('Tinggi')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('cm'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Tarif')
                    ->description('Tarif per kg mengikuti rute dan jenis layanan yang dipilih pada langkah 1.')
                    ->schema([
                        Forms\Components\TextInput::make('price_per_kg')
                            ->label('Tarif per kg')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText(fn (Forms\Get $get): string => $this->tariffHint($get))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => $set('final_tariff', $this->calculatedTotal($get))),
                        Forms\Components\TextInput::make('final_tariff')
                            ->label('Tarif Final')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText('Terisi otomatis dari berat x tarif per kg, bisa disesuaikan bila ada biaya tambahan.'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Tarif untuk rute, layanan, dan berat saat ini, bila ada di tabel tarif.
     *
     * @return array<string, mixed>|null
     */
    protected function currentRate(Forms\Get $get): ?array
    {
        $origin = $get('origin');
        $kabupaten = $get('kabupaten_tujuan');
        $destination = $get('destination');
        $serviceType = $get('service_type');

        if (blank($origin) || blank($kabupaten) || blank($destination) || blank($serviceType)) {
            return null;
        }

        $weight = (float) $get('weight');

        return app(TariffCalculatorService::class)->rateFor(
            (string) $origin,
            (string) $kabupaten,
            (string) $destination,
            (string) $serviceType,
            $weight > 0 ? $weight : null,
        );
    }

    /**
     * Asal dan tujuan sudah dipilih, sehingga layanan yang tersedia untuk rute
     * itu sudah bisa diketahui.
     */
    protected function routeIsKnown(Forms\Get $get): bool
    {
        return filled($get('origin')) && filled($get('kabupaten_tujuan')) && filled($get('destination'));
    }

    /**
     * @return array<string, string>
     */
    protected function availableServices(Forms\Get $get): array
    {
        if (blank($get('origin')) || blank($get('kabupaten_tujuan')) || blank($get('destination'))) {
            return [];
        }

        return app(TariffCalculatorService::class)->availableServiceTypes(
            (string) $get('origin'),
            (string) $get('kabupaten_tujuan'),
            (string) $get('destination'),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function rateForService(Forms\Get $get, string $serviceType): ?array
    {
        if (blank($get('origin')) || blank($get('kabupaten_tujuan')) || blank($get('destination'))) {
            return null;
        }

        return app(TariffCalculatorService::class)->rateFor(
            (string) $get('origin'),
            (string) $get('kabupaten_tujuan'),
            (string) $get('destination'),
            $serviceType,
        );
    }

    /**
     * Mempertahankan layanan yang dipilih bila masih tersedia untuk rute baru,
     * atau mengosongkannya supaya operator tidak terikat layanan lama.
     */
    protected function syncServiceType(Forms\Get $get, Forms\Set $set): void
    {
        $current = $get('service_type');

        if (filled($current) && array_key_exists((string) $current, $this->availableServices($get))) {
            return;
        }

        $set('service_type', null);
    }

    /**
     * Memuat ulang tarif per kg dari tabel, karena rute atau layanan sengaja
     * diubah di langkah 1. Berhenti bila tidak ada tarif untuk kombinasi itu.
     */
    protected function applyRate(Forms\Get $get, Forms\Set $set): void
    {
        $rate = $this->currentRate($get);

        if ($rate === null) {
            return;
        }

        $set('price_per_kg', $rate['price_per_kg']);
        $set('final_tariff', $this->totalFor((float) $get('weight'), $rate['price_per_kg']));
    }

    protected function tariffHint(Forms\Get $get): string
    {
        if (blank($get('origin')) || blank($get('destination'))) {
            return 'Pilih kota asal dan tujuan pada langkah 1 untuk melihat tarif.';
        }

        if (blank($get('service_type'))) {
            return 'Pilih jenis layanan memakai tombol pada panel Informasi Tarif di langkah 1.';
        }

        $rate = $this->currentRate($get);

        if ($rate === null) {
            return 'Tidak ada tarif untuk rute dan layanan ini, isi tarif per kg secara manual.';
        }

        $hint = sprintf(
            'Tarif %s: Rp %s/kg, estimasi %s hari.',
            ucfirst((string) $rate['service_type']),
            number_format((float) $rate['price_per_kg'], 0, ',', '.'),
            $rate['estimated_days'],
        );

        if (! $rate['meets_min_weight']) {
            $hint .= sprintf(
                ' Perhatian: berat di bawah minimum %s kg untuk layanan ini.',
                number_format((float) $rate['min_weight'], 0, ',', '.'),
            );
        }

        return $hint;
    }

    /**
     * Mengisi tarif per kg dari tabel hanya bila staf belum mengisinya,
     * supaya tarif manual tidak ditimpa.
     */
    protected function suggestPricePerKg(Forms\Get $get, Forms\Set $set): void
    {
        if (filled($get('price_per_kg'))) {
            return;
        }

        $rate = $this->currentRate($get);

        if ($rate !== null) {
            $set('price_per_kg', $rate['price_per_kg']);
        }
    }

    protected function calculatedTotal(Forms\Get $get): ?float
    {
        return $this->totalFor((float) $get('weight'), (float) $get('price_per_kg'));
    }

    protected function totalFor(float $weight, float $pricePerKg): ?float
    {
        if ($weight <= 0 || $pricePerKg <= 0) {
            return null;
        }

        return round($weight * $pricePerKg, 2);
    }

    protected function airwayBillStep(): Step
    {
        return Step::make('Nomor AWB')
            ->description('Nomor airway bill dan cetak')
            ->icon('heroicon-o-document-text')
            ->afterValidation(function (Step $step): void {
                $this->persistStep($step);
                $this->issueAirwayBill();
            })
            ->schema([
                Forms\Components\Section::make('Ringkasan')
                    ->description('Nomor AWB diterbitkan saat meninggalkan langkah ini dan pengiriman menjadi aktif.')
                    ->schema([
                        Forms\Components\Placeholder::make('ringkasan')
                            ->hiddenLabel()
                            ->content(fn (): string => $this->airwayBillSummary()),
                    ]),
            ]);
    }

    /**
     * Meng menerbitkan nomor AWB, mengaktifkan pengiriman, dan mencatat riwayatnya.
     */
    protected function issueAirwayBill(): void
    {
        if ($this->record === null || $this->record->awb_number !== null) {
            return;
        }

        $this->record = app(NumberGenerator::class)->persistWithRetry(
            'awbNumber',
            function (string $awbNumber): Shipment {
                $shipment = $this->record;

                $shipment->forceFill([
                    'awb_number' => $awbNumber,
                    'status' => 'in_transit',
                ])->save();

                $shipment->logs()->create([
                    'status_description' => 'Nomor AWB diterbitkan, pengiriman aktif',
                    'location' => $shipment->origin,
                    'timestamp' => now(),
                ]);

                return $shipment;
            },
        );

        Notification::make()
            ->title('Airway bill dibuat')
            ->body("Nomor AWB: {$this->record->awb_number}. Pengiriman berstatus aktif.")
            ->success()
            ->send();
    }

    protected function airwayBillSummary(): string
    {
        if ($this->record === null) {
            return 'Selesaikan langkah sebelumnya terlebih dahulu.';
        }

        $awb = $this->record->awb_number
            ? "Nomor AWB {$this->record->awb_number}, pengiriman sudah aktif dan siap dicetak."
            : 'Nomor AWB belum dibuat, pengiriman masih berstatus draft.';

        return sprintf(
            '%s Pengiriman dari %s ke %s via %s, berat %s kg, tarif final %s.',
            $awb,
            $this->record->origin,
            $this->record->destination,
            $this->record->service_type !== null ? ucfirst((string) $this->record->service_type) : '-',
            $this->record->weight ?? '-',
            $this->record->final_tariff !== null
                ? 'Rp '.number_format((float) $this->record->final_tariff, 0, ',', '.')
                : '-',
        );
    }

    protected function invoiceStep(): Step
    {
        return Step::make('Invoice')
            ->description('Perhitungan dan item invoice')
            ->icon('heroicon-o-receipt-percent')
            ->afterValidation(function (Step $step): void {
                $this->persistInvoice($step);
            })
            ->schema($this->invoiceFormSchema());
    }

    protected function printInvoiceStep(): Step
    {
        return Step::make('Cetak Invoice')
            ->description('Pratinjau dan cetak invoice')
            ->icon('heroicon-o-printer')
            ->schema([
                Forms\Components\Section::make('Invoice')
                    ->description('Nomor invoice terbit saat langkah 4 disimpan. Invoice tetap berstatus draft sampai ditandai tertagih dari daftar atau halaman detail invoice.')
                    ->schema([
                        Forms\Components\Placeholder::make('invoice_summary')
                            ->hiddenLabel()
                            ->content(fn (): string => $this->invoiceSummary()),
                    ]),
                Forms\Components\Section::make('Cetak')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('cetakInvoice')
                                ->label('Buka halaman cetak')
                                ->icon('heroicon-o-printer')
                                ->url(fn (): string => PrintInvoice::getUrl(['record' => $this->record]))
                                ->visible(fn (): bool => $this->currentInvoice() !== null),
                        ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Menyimpan invoice milik draft yang sedang dikerjakan.
     *
     * Berbeda dengan persistStep(), state langkah ini tidak boleh masuk ke tabel
     * shipments: kuncinya berawalan invoice_ dan hanya milik invoice.
     */
    protected function persistInvoice(Step $step): void
    {
        if ($this->record === null) {
            // Tidak ada draft shipment lagi, jadi tidak ada yang boleh disimpan di sini.
            return;
        }

        $state = $step->getChildComponentContainer()->getState();

        app(InvoiceService::class)->saveForShipment($this->record, [
            ...$this->invoiceDefaultsFromShipment($state),
            'items' => $state['invoice_items'] ?? [],
        ]);
    }

    protected function currentInvoice(): ?Invoice
    {
        if ($this->record === null) {
            return null;
        }

        return $this->record->latestInvoice;
    }

    protected function invoiceSummary(): string
    {
        $invoice = $this->currentInvoice();

        if ($invoice === null) {
            return 'Invoice belum dibuat. Selesaikan langkah sebelumnya terlebih dahulu.';
        }

        $additional = $invoice->items()->where('type', InvoiceService::TYPE_ADDITIONAL)->count();
        $adjustments = $invoice->items()->where('type', '!=', InvoiceService::TYPE_ADDITIONAL)->count();

        return sprintf(
            'Invoice %s berstatus %s. Ongkos kirim Rp %s, %d biaya tambahan, %d penyesuaian diskon atau pajak, total tagihan Rp %s.',
            $invoice->invoice_number,
            $invoice->statusLabel(),
            $this->rupiah((float) $invoice->shipping_cost),
            $additional,
            $adjustments,
            $this->rupiah((float) $invoice->total),
        );
    }

    /**
     * Menyimpan state satu langkah ke draft yang sedang dikerjakan.
     */
    protected function persistStep(Step $step): void
    {
        $attributes = $this->shipmentAttributes($step->getChildComponentContainer()->getState());

        if ($this->record !== null) {
            $this->record->update($attributes);

            return;
        }

        $this->record = Shipment::create($attributes + ['status' => 'draft']);

        Notification::make()
            ->title('Draft pengiriman disimpan')
            ->body('Nomor AWB diterbitkan pada langkah 3.')
            ->success()
            ->send();
    }

    /**
     * Membuang kunci invoice_* dari state form.
     *
     * Kunci-kunci itu milik langkah invoice dan tidak punya kolom di tabel
     * shipments, jadi tidak boleh ikut tersimpan ke sana.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function shipmentAttributes(array $state): array
    {
        foreach (array_keys($state) as $key) {
            if (str_starts_with($key, 'invoice_')) {
                unset($state[$key]);
            }
        }

        return $state;
    }
}
