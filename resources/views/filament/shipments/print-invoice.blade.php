<x-filament-panels::page>
    @php
        $money = fn ($amount): string => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    @endphp

    <style>
        @media print {
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .no-print {
                display: none !important;
            }

            .fi-main {
                padding: 0 !important;
            }

            html {
                color-scheme: light !important;
            }

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="space-y-6">
        <div class="no-print flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold tracking-tight text-gray-950">
                Invoice {{ $invoice->invoice_number }}
            </h2>

            <x-filament::button
                icon="heroicon-o-printer"
                x-on:click="window.print()"
            >
                Cetak
            </x-filament::button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-8 text-gray-900 shadow-sm">
            <div class="flex items-start justify-between border-b-2 border-gray-900 pb-4">
                <div class="flex items-start gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="ATL Express" style="width: 100px; height: auto" class="rounded-lg object-contain">
                    <div>
                        <p class="text-2xl font-bold uppercase tracking-wide">ATL Express</p>
                        <p class="text-xs uppercase tracking-widest text-gray-500">
                            Invoice / Tagihan Pengiriman
                        </p>
                        <p class="mt-1 text-xs leading-tight text-gray-600">
                            Graha Buana , Jl. Dr. Saharjo No.210A, RT.1/RW.4, Menteng Dalam, Kec. Tebet, <br>Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12870
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-xs uppercase tracking-widest text-gray-500">Nomor Invoice</p>
                    <p class="text-xl font-bold tracking-wide">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $invoice->created_at?->translatedFormat('d M Y') }}
                    </p>
                    <p class="mt-1 text-sm">
                        <span class="text-gray-500">Status :</span>
                        <span class="font-semibold">{{ ucfirst($invoice->status) }}</span>
                    </p>
                    <div class="mt-3 space-y-0.5 text-xs">
                        <p>
                            <span class="text-gray-500">Due Date :</span>
                            <span class="font-semibold">{{ $invoice->due_date?->translatedFormat('d M Y') ?: '-' }}</span>
                        </p>
                        <p>
                            <span class="text-gray-500">SPPB No :</span>
                            <span class="font-semibold">{{ $invoice->sppb_number ?: '-' }}</span>
                        </p>
                        <p>
                            <span class="text-gray-500">Packing List No :</span>
                            <span class="font-semibold">{{ $invoice->packing_list_number ?: '-' }}</span>
                        </p>
                        <p>
                            <span class="text-gray-500">Periode :</span>
                            <span class="font-semibold">{{ $invoice->period ?: '-' }}</span>
                        </p>
                        <p>
                            <span class="text-gray-500">No Account :</span>
                            <span class="font-semibold">{{ $invoice->account_number ?: '-' }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 py-6">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                        Ditagihkan kepada
                    </p>
                    <p class="font-semibold">{{ $invoice->billed_to_name ?: ($shipment->sender_name ?: '-') }}</p>
                    <p class="text-sm leading-relaxed">
                        {{ $invoice->billed_to_address ?: ($shipment->sender_address ?: '-') }}
                    </p>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                        Referensi Pengiriman
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Nomor :</span>
                        <span class="font-semibold">{{ $shipment->awb_number ?: '-' }}</span>
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Rute</span>
                        {{ $shipment->origin }} &rarr; {{ $shipment->destination }}
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Layanan</span>
                        {{ $shipment->service_type ? ucfirst($shipment->service_type) : '-' }}
                    </p>
                    <p class="text-sm">
                        <span class="text-gray-500">Berat</span>
                        {{ $shipment->weight ? $shipment->weight . ' kg' : '-' }}
                    </p>
                </div>
            </div>

            @include('filament.invoices.items-table', [
                'invoice' => $invoice,
                'money' => $money,
            ])

            <div class="mt-6 flex justify-end">
                <dl class="w-full max-w-sm space-y-1 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Ongkos Kirim + Biaya Lain</dt>
                        <dd class="font-medium">{{ $money((float) $invoice->subtotal + (float) $invoice->diskon) }}</dd>
                    </div>

                    @if ((float) $invoice->diskon > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Potongan Diskon</dt>
                            <dd class="font-medium">- {{ $money($invoice->diskon) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Dasar Pengenaan Pajak (DPP)</dt>
                        <dd class="font-medium">{{ $money($invoice->subtotal) }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">PPN (1,1%)</dt>
                        <dd class="font-medium">{{ $money($invoice->tax) }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="font-medium">Jumlah Tagihan Termasuk PPN</dt>
                        <dd class="font-medium">{{ $money((float) $invoice->subtotal + (float) $invoice->tax) }}</dd>
                    </div>

                    @if ((float) $invoice->discount > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Potongan PPh 23 (2%)</dt>
                            <dd class="font-medium">- {{ $money($invoice->discount) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between gap-4 border-t border-gray-900 pt-2 text-base">
                        <dt class="font-bold">Total Tagihan</dt>
                        <dd class="font-bold">{{ $money($invoice->total) }}</dd>
                    </div>
                </dl>
            </div>

            @if ((float) $invoice->discount > 0)
                <p class="mt-2 text-right text-[11px] leading-relaxed text-gray-500">
                    ( * ) PPh dipotong oleh pihak pembeli/pemberi kerja saat melakukan pembayaran, dan pembeli wajib
                    memberikan bukti potong PPh kepada pihak penagih.
                </p>
            @endif

            <div class="mt-6 rounded-lg border border-gray-300 bg-gray-50 p-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                    Account Bank
                </p>
                <div class="grid grid-cols-1 gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                    <p>
                        <span class="text-gray-500">Bank :</span>
                        <span class="font-semibold">Bank BCA</span>
                    </p>
                    <p>
                        <span class="text-gray-500">No. Rek :</span>
                        <span class="font-semibold">604.3792.330</span>
                    </p>
                    <p>
                        <span class="text-gray-500">A/N :</span>
                        <span class="font-semibold">PT ANGKASA TRANS LOGISTIK</span>
                    </p>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-8 text-sm">
                <div>
                    <p class="border-t border-gray-400 pt-2 text-xs uppercase tracking-widest text-gray-500">
                        Tanda tangan petugas
                    </p>
                </div>
                <div>
                    <p class="border-t border-gray-400 pt-2 text-xs uppercase tracking-widest text-gray-500">
                        Tanda tangan penerima
                    </p>
                </div>
            </div>

            <p class="mt-6 text-center text-[11px] leading-relaxed text-gray-500">
                Invoice ini sah tanpa tanda tangan basah dan dapat dicetak dari sistem.
                Mohon konfirmasi pembayaran ke rekening ATL Express.
            </p>
        </div>
    </div>
</x-filament-panels::page>
