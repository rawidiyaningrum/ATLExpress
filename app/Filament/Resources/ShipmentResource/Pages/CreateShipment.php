<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
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
        }
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
            ->schema([
                Forms\Components\Placeholder::make('berat_tarif')
                    ->content('Berat dan tarif final diisi pada langkah ini.'),
            ]);
    }

    protected function airwayBillStep(): Step
    {
        return Step::make('Nomor AWB')
            ->description('Nomor airway bill dan cetak')
            ->icon('heroicon-o-document-text')
            ->schema([
                Forms\Components\Placeholder::make('awb')
                    ->content('Nomor AWB dibuat pada langkah ini.'),
            ]);
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
