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

                            return sprintf(
                                'Digunakan otomatis: nama "%s", alamat "%s", ongkos kirim Rp %s. Isi atau ubah field di bawah bila perlu.',
                                $shipment?->receiver_name ?? '-',
                                $shipment?->receiver_address ?? '-',
                                $this->rupiah((float) ($shipment?->final_tariff ?? 0)),
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
                ->description('Baris kosong diabaikan. Baris tanpa deskripsi tidak ikut disimpan.')
                ->schema([
                    Forms\Components\Repeater::make('invoice_items')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('Keterangan')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\Select::make('dihitung_dari')
                                ->label('Dihitung dari')
                                ->options($service->itemBasisOptions())
                                ->default(InvoiceService::BASIS_FINAL_TARIFF)
                                ->helperText('Dasar nominal baris ini. Tidak mengubah harga satuan yang sudah terisi.')
                                ->live(),
                            Forms\Components\Select::make('type')
                                ->label('Jenis')
                                ->options($service->itemTypeOptions())
                                ->default(InvoiceService::TYPE_ADDITIONAL)
                                ->required()
                                ->live(),
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
                    Forms\Components\Select::make('invoice_basis')
                        ->label('Dihitung dari')
                        ->options($service->itemBasisOptions())
                        ->default(InvoiceService::BASIS_FINAL_TARIFF)
                        ->helperText('Dipakai tombol cepat di bawah, dan jadi default baris item yang baru.')
                        ->live(),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('quickPpn')
                            ->label('PPN 11%')
                            ->icon('heroicon-o-plus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'PPN 11%',
                                InvoiceService::TYPE_TAX,
                                $this->percentageOfBasis($get, 0.11),
                            )),
                        Forms\Components\Actions\Action::make('quickPph')
                            ->label('PPh 2%')
                            ->icon('heroicon-o-minus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'PPh 2%',
                                InvoiceService::TYPE_DISCOUNT,
                                $this->percentageOfBasis($get, 0.02),
                            )),
                        Forms\Components\Actions\Action::make('quickDiscount')
                            ->label('Diskon 5%')
                            ->icon('heroicon-o-plus')
                            ->action(fn (Forms\Get $get, Forms\Set $set) => $this->appendInvoiceItem(
                                $get,
                                $set,
                                'Diskon 5%',
                                InvoiceService::TYPE_DISCOUNT,
                                $this->percentageOfBasis($get, 0.05),
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
                ->description('Dihitung ulang otomatis dari ongkir dan item di atas.')
                ->schema([
                    Forms\Components\Placeholder::make('invoice_subtotal')
                        ->label('Subtotal')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['subtotal']);
                        }),
                    Forms\Components\Placeholder::make('invoice_discount_total')
                        ->label('Diskon')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['discount']);
                        }),
                    Forms\Components\Placeholder::make('invoice_tax_total')
                        ->label('Pajak')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['tax']);
                        }),
                    Forms\Components\Placeholder::make('invoice_grand_total')
                        ->label('Total Tagihan')
                        ->content(function (Forms\Get $get): string {
                            return 'Rp '.$this->rupiah($this->invoiceTotals($get)['total']);
                        }),
                ])
                ->columns(4),
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
                : $shipment?->receiver_name,
            'billed_to_address' => filled($state['invoice_billed_to_address'] ?? null)
                ? $state['invoice_billed_to_address']
                : $shipment?->receiver_address,
            'shipping_cost' => $shippingCost > 0
                ? $shippingCost
                : (float) ($shipment?->final_tariff ?? 0),
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
     * Ongkos invoice, memakai tarif final shipment selama field belum diisi.
     */
    protected function invoiceShippingCost(Forms\Get $get): float
    {
        $value = (float) $get('invoice_shipping_cost');

        if ($value > 0) {
            return $value;
        }

        return (float) ($this->invoiceShipment()?->final_tariff ?? 0);
    }

    /**
     * Persentase dari dasar yang dipilih di form invoice.
     *
     * "Tarif final" memakai ongkos kirim, sedangkan "Subtotal item sebelumnya"
     * memakai subtotal berjalan dari ongkir ditambah item sebelumnya. Karena
     * tombol cepat selalu menambah baris di akhir, keduanya sama-sama memakai
     * subtotal berjalan saat tombol ditekan.
     */
    protected function percentageOfBasis(Forms\Get $get, float $rate): float
    {
        $basis = (string) $get('invoice_basis');
        $value = match ($basis) {
            InvoiceService::BASIS_PREVIOUS_ITEMS => $this->invoiceTotals($get)['subtotal'],
            default => $this->invoiceShippingCost($get),
        };

        return round($value * $rate, 2);
    }

    protected function appendInvoiceItem(Forms\Get $get, Forms\Set $set, string $description, string $type, float $unitPrice): void
    {
        $items = (array) $get('invoice_items');

        $basis = (string) $get('invoice_basis');

        $items[] = [
            'description' => $description,
            'type' => $type,
            'dihitung_dari' => in_array($basis, [InvoiceService::BASIS_FINAL_TARIFF, InvoiceService::BASIS_PREVIOUS_ITEMS], true)
                ? $basis
                : InvoiceService::BASIS_FINAL_TARIFF,
            'quantity' => 1,
            'unit_price' => $unitPrice,
        ];

        $set('invoice_items', array_values($items));
    }
}
