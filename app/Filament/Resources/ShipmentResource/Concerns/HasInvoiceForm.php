<?php

namespace App\Filament\Resources\ShipmentResource\Concerns;

use App\Models\Shipment;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Forms\Components\Component;

/**
 * Form invoice milik satu shipment, dipakai bersama oleh wizard dan halaman
 * "Buat Invoice".
 *
 * Nama field sengaja memakai awalan invoice_ dan seluruh closure membaca
 * Forms\Get relatif terhadap container tempat schema ini dipasang, sehingga
 * skema yang sama bisa dipakai di dalam Step wizard maupun di halaman biasa
 * tanpa perbedaan statePath.
 */
trait HasInvoiceForm
{
    /**
     * Shipment pemilik invoice. Host trait harus mengekspos $this->record.
     */
    protected function invoiceShipment(): ?Shipment
    {
        return $this->record;
    }

    protected function rupiah(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    /**
     * @return array<int, Component>
     */
    protected function invoiceFormSchema(): array
    {
        $service = app(InvoiceService::class);

        return [
            Forms\Components\Section::make('Data Invoice')
                ->description('Invoice dibuat otomatis dari data pengiriman, lalu bisa disesuaikan.')
                ->schema([
                    Forms\Components\Placeholder::make('invoice_defaults')
                        ->hiddenLabel()
                        ->content(function (): string {
                            $shipment = $this->invoiceShipment();
                            $breakdown = $this->shippingBreakdownLabel($shipment);

                            return sprintf(
                                'Digunakan otomatis: nama "%s", alamat "%s", ongkos kirim Rp %s (%s). Isi atau ubah field di bawah bila perlu.',
                                $shipment?->sender_name ?? '-',
                                $shipment?->sender_address ?? '-',
                                $this->rupiah($this->defaultShippingCost($shipment)),
                                $breakdown,
                            );
                        })
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('invoice_billed_to_name')
                        ->label('Nama Dicatat/tagih')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('invoice_billed_to_address')
                        ->label('Alamat Penagihan')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('invoice_shipping_cost')
                        ->label('Ongkos Kirim')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->helperText('Kosongkan untuk memakai tarif final pengiriman.')
                        ->live(onBlur: true),
                ])
                ->columns(2),
            Forms\Components\Section::make('Item Tambahan')
                ->description('Pilih Jenis PPN (11%) atau PPh (2%), nominal terisi otomatis dari subtotal sebelum pajak (DPP). Baris kosong diabaikan.')
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
                                    $this->autofillItemFromDpp($get, $set);
                                }),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->live(onBlur: true),
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Harga Satuan')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->default(0)
                                ->live(onBlur: true),
                            Forms\Components\Placeholder::make('line_total')
                                ->label('Jumlah Baris')
                                ->content(function (Forms\Get $get): string {
                                    $line = (int) $get('quantity') * (float) $get('unit_price');

                                    return 'Rp '.$this->rupiah($line);
                                }),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah item'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('quickPpn')
                            ->label('PPN 11%')
                            ->icon('heroicon-o-plus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'PPN 11%',
                                InvoiceService::TYPE_TAX,
                                $this->percentageOfBasis($get, InvoiceService::PPN_RATE),
                            )),
                        Forms\Components\Actions\Action::make('quickPph')
                            ->label('PPh 2%')
                            ->icon('heroicon-o-minus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'PPh 2%',
                                InvoiceService::TYPE_DISCOUNT,
                                $this->percentageOfBasis($get, InvoiceService::PPH_RATE),
                            )),
                        Forms\Components\Actions\Action::make('quickPacking')
                            ->label('Packing Kayu')
                            ->icon('heroicon-o-plus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'Packing kayu',
                                InvoiceService::TYPE_ADDITIONAL,
                                50000,
                            )),
                    ])
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Ringkasan')
                ->description('PPN dan PPh dihitung dari subtotal sebelum pajak (DPP).')
                ->schema([
                    Forms\Components\Placeholder::make('invoice_subtotal')
                        ->label('Subtotal Sebelum Pajak (DPP)')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['subtotal']);
                        }),
                    Forms\Components\Placeholder::make('invoice_tax_total')
                        ->label('PPN (11%)')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['tax']);
                        }),
                    Forms\Components\Placeholder::make('invoice_taxed_total')
                        ->label('Jumlah Tagihan Termasuk PPN')
                        ->content(function (Forms\Get $get): string {
                            $totals = $this->invoiceTotals($get);

                            return 'Rp '.$this->rupiah($totals['subtotal'] + $totals['tax']);
                        }),
                    Forms\Components\Placeholder::make('invoice_discount_total')
                        ->label('Potongan PPh (2%)')
                        ->content(function (Forms\Get $get): string {
                            $discount = $this->invoiceTotals($get)['discount'];

                            return $discount > 0 ? 'Rp -'.$this->rupiah($discount) : 'Rp 0';
                        }),
                    Forms\Components\Placeholder::make('invoice_grand_total')
                        ->label('Total Pembayaran Diterima / Dibayar')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['total']);
                        }),
                ])
                ->columns(3),
        ];
    }

    /**
     * Nilai tagihan yang dipakai form invoice.
     *
     * Field invoice tidak bisa memakai ->default() di wizard karena form dimuat
     * saat shipment masih draft tanpa nomor. Di luar wizard pun nilai efektifnya
     * tetap berasal dari data pengiriman, jadi logikanya sama.
     *
     * @param  array<string, mixed>  $state
     * @return array{billed_to_name: ?string, billed_to_address: ?string, shipping_cost: float}
     */
    protected function invoiceDefaultsFromShipment(array $state): array
    {
        $shippingCost = (float) ($state['invoice_shipping_cost'] ?? 0);
        $shipment = $this->invoiceShipment();

        return [
            'billed_to_name' => filled($state['invoice_billed_to_name'] ?? null)
                ? $state['invoice_billed_to_name']
                : $shipment?->sender_name,
            'billed_to_address' => filled($state['invoice_billed_to_address'] ?? null)
                ? $state['invoice_billed_to_address']
                : $shipment?->sender_address,
            'shipping_cost' => $shippingCost > 0
                ? $shippingCost
                : $this->defaultShippingCost($shipment),
        ];
    }

    /**
     * @return array{shipping: float, additional: float, discount: float, tax: float, subtotal: float, total: float}
     */
    protected function invoiceTotals(Forms\Get $get): array
    {
        return app(InvoiceService::class)->calculate(
            $this->invoiceShippingCost($get),
            (array) $get('invoice_items'),
        );
    }

    /**
     * Ongkos kirim bawaan invoice: berat (kg) x tarif per kilo.
     *
     * Bila berat atau tarif per kilo belum ada (misal shipment lama), memakai
     * tarif final sebagai cadangan.
     */
    protected function defaultShippingCost(?Shipment $shipment): float
    {
        if ($shipment === null) {
            return 0.0;
        }

        $weight = (float) $shipment->weight;
        $unit = (float) $shipment->price_per_kg;

        if ($weight > 0 && $unit > 0) {
            return round($weight * $unit, 2);
        }

        return (float) ($shipment->final_tariff ?? 0);
    }

    /**
     * Rincian ongkos kirim untuk placeholder form: "5 kg x Rp 20.000/kg".
     */
    protected function shippingBreakdownLabel(?Shipment $shipment): string
    {
        $weight = (float) ($shipment?->weight ?? 0);
        $unit = (float) ($shipment?->price_per_kg ?? 0);

        if ($weight <= 0 || $unit <= 0) {
            return '-';
        }

        $trimmed = trim(rtrim(rtrim(number_format($weight, 2, ',', '.'), '0'), '.'));

        return "{$trimmed} kg x Rp {$this->rupiah($unit)}/kg";
    }

    /**
     * Ongkos invoice, memakai berat x tarif per kilo selama field belum diisi.
     */
    protected function invoiceShippingCost(Forms\Get $get): float
    {
        $value = (float) $get('invoice_shipping_cost');

        if ($value > 0) {
            return $value;
        }

        return $this->defaultShippingCost($this->invoiceShipment());
    }

    /**
     * Persentase dihitung dari subtotal berjalan: ongkos kirim ditambah
     * biaya tambahan yang sudah tercatat sebelum baris ini ditambahkan.
     */
    protected function percentageOfBasis(Forms\Get $get, float $rate): float
    {
        return round($this->invoiceTotals($get)['subtotal'] * $rate, 2);
    }

    protected function appendInvoiceItem(Forms\Get $get, Forms\Set $set, string $description, string $type, float $unitPrice): void
    {
        $items = (array) $get('invoice_items');

        $items[] = [
            'description' => $description,
            'type' => $type,
            'quantity' => 1,
            'unit_price' => $unitPrice,
        ];

        $set('invoice_items', array_values($items));
    }

    /**
     * Mengisi harga satuan baris item otomatis saat jenis PPN (11%) atau PPh
     * (2%) dipilih, dihitung dari subtotal sebelum pajak (DPP).
     *
     * Panggilan berasal dari dalam baris repeater, jadi field form diakses
     * relatif dua tingkat ke atas (../../) menuju container invoice.
     */
    protected function autofillItemFromDpp(Forms\Get $get, Forms\Set $set): void
    {
        $service = app(InvoiceService::class);

        $shipping = (float) $get('../../invoice_shipping_cost');

        if ($shipping <= 0) {
            $shipping = $this->defaultShippingCost($this->invoiceShipment());
        }

        $dpp = $service->dpp($shipping, (array) $get('../../invoice_items'));
        $suggestion = $service->unitPriceSuggestionForType((string) $get('type'), $dpp);

        if ($suggestion !== null) {
            $set('unit_price', $suggestion);
        }
    }
}
