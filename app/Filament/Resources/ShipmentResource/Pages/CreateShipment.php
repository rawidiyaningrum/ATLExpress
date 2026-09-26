<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\TariffCalculatorService;
use App\Support\NumberGenerator;
use Filament\Forms;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;

class CreateShipment extends CreateRecord
{
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

            $this->record->update($data);

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

        $this->redirect(ShipmentResource::getUrl('edit', ['record' => $this->record]));
    }

    protected function detailStep(): Step
    {
        return Step::make('Buat Pengiriman')
            ->description('Data pengirim, penerima, dan rute')
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
                Forms\Components\Section::make('Rute')
                    ->schema([
                        Forms\Components\TextInput::make('origin')
                            ->label('Kota Asal')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('destination')
                            ->label('Kota Tujuan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
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
                        Forms\Components\Textarea::make('final_dimensions')
                            ->label('Dimensi')
                            ->placeholder('Contoh: 50x40x30')
                            ->rows(1),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Tarif')
                    ->description('Tarif saran diambil dari tabel ShippingRate untuk rute di langkah 1.')
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
     * Saran tarif yang cocok untuk rute dan berat saat ini, bila ada.
     *
     * @return array<string, mixed>|null
     */
    protected function suggestedRate(Forms\Get $get): ?array
    {
        $weight = (float) $get('weight');

        if ($weight <= 0 || blank($get('origin')) || blank($get('destination'))) {
            return null;
        }

        return app(TariffCalculatorService::class)
            ->suggest((string) $get('origin'), (string) $get('destination'), $weight)['recommended'];
    }

    protected function tariffHint(Forms\Get $get): string
    {
        if (blank($get('origin')) || blank($get('destination'))) {
            return 'Isi kota asal dan tujuan pada langkah 1 untuk melihat saran tarif.';
        }

        $rate = $this->suggestedRate($get);

        if ($rate === null) {
            return (float) $get('weight') > 0
                ? 'Tidak ada tarif yang cocok untuk berat ini, isi tarif per kg secara manual.'
                : 'Masukkan berat terlebih dahulu untuk melihat saran tarif.';
        }

        return sprintf(
            'Saran: %s — Rp %s/kg, estimasi %s hari.',
            $rate['service_type'],
            number_format((float) $rate['price_per_kg'], 0, ',', '.'),
            $rate['estimated_days'],
        );
    }

    /**
     * Mengisi tarif per kg dari saran tabel hanya bila staf belum mengisinya,
     * supaya tarif manual tidak ditimpa.
     */
    protected function suggestPricePerKg(Forms\Get $get, Forms\Set $set): void
    {
        if (filled($get('price_per_kg'))) {
            return;
        }

        $rate = $this->suggestedRate($get);

        if ($rate !== null) {
            $set('price_per_kg', $rate['price_per_kg']);
        }
    }

    protected function calculatedTotal(Forms\Get $get): ?float
    {
        $weight = (float) $get('weight');
        $pricePerKg = (float) $get('price_per_kg');

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
            '%s Pengiriman %s dari %s ke %s, berat %s kg, tarif final %s.',
            $awb,
            $this->record->tracking_number,
            $this->record->origin,
            $this->record->destination,
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
            ->schema([
                Forms\Components\Placeholder::make('invoice')
                    ->content('Item invoice diisi pada langkah ini.'),
            ]);
    }

    protected function printInvoiceStep(): Step
    {
        return Step::make('Cetak Invoice')
            ->description('Pratinjau dan cetak invoice')
            ->icon('heroicon-o-printer')
            ->schema([
                Forms\Components\Placeholder::make('cetak')
                    ->content('Invoice dicetak pada langkah ini.'),
            ]);
    }

    /**
     * Menyimpan state satu langkah ke draft yang sedang dikerjakan.
     */
    protected function persistStep(Step $step): void
    {
        $attributes = $step->getChildComponentContainer()->getState();

        if ($this->record !== null) {
            $this->record->update($attributes);

            return;
        }

        $this->record = app(NumberGenerator::class)->persistWithRetry(
            'trackingNumber',
            fn (string $trackingNumber): Shipment => Shipment::create($attributes + [
                'tracking_number' => $trackingNumber,
                'status' => 'draft',
            ]),
        );

        Notification::make()
            ->title('Draft pengiriman disimpan')
            ->body("Nomor tracking: {$this->record->tracking_number}")
            ->success()
            ->send();
    }
}
