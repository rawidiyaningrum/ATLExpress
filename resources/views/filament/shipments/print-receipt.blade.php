<x-filament-panels::page>
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
        <div class="no-print flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kop Perusahaan:</span>
                <div class="inline-flex rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
                    <button
                        type="button"
                        wire:click="setCompany('atl')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $company === 'atl' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}"
                    >
                        <span>ATL Express</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setCompany('douze')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $company === 'douze' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-white' }}"
                    >
                        <span>PT Douze</span>
                    </button>
                </div>
            </div>

            <x-filament::button
                icon="heroicon-o-printer"
                x-on:click="window.print()"
            >
                Cetak
            </x-filament::button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-8 text-gray-900 shadow-sm print:border-0 print:p-0 print:shadow-none">
            <div class="border-b-2 border-gray-900 pb-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 text-left">
                        <img src="{{ $companyProfile['logo'] }}" alt="{{ $companyProfile['name'] }}" style="width: 100px; height: auto" class="rounded-lg object-contain">
                        <div>
                            <p class="text-2xl font-bold uppercase tracking-wide">{{ $companyProfile['full_name'] }}</p>
                            <p class="mt-1 text-xs leading-tight text-gray-600">
                                {!! nl2br(e($companyProfile['address'])) !!}
                            </p>
                            @if ($companyProfile['npwp'] !== '-' && !empty($companyProfile['npwp']))
                                <p class="mt-1 text-xs text-gray-600">
                                    <span class="font-semibold">NPWP:</span> {{ $companyProfile['npwp'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-xs uppercase tracking-widest text-gray-500">Nomor Kwitansi</p>
                        <p class="text-xl font-bold tracking-wide">{{ $kwitansi_no }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $receipt_date->translatedFormat('d F Y') }}
                        </p>
                    </div>
                </div>

                <p class="mt-4 text-center text-2xl font-bold uppercase tracking-widest">Kwitansi</p>
            </div>

            <table class="mt-6 w-full text-sm">
                <tbody>
                    <tr>
                        <td class="w-44 py-2 pr-4 align-top text-gray-600">Sudah terima dari</td>
                        <td class="w-4 py-2 pr-2 align-top">:</td>
                        <td class="py-2 align-top font-semibold">{{ $client_name }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 align-top text-gray-600">Uang sejumlah</td>
                        <td class="py-2 pr-2 align-top">:</td>
                        <td class="py-2 align-top">
                            <span class="inline-block bg-gray-100 px-2 py-1 rounded">{{ $amount_words }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 pr-4 align-top text-gray-600">Untuk Pembayaran</td>
                        <td class="py-2 pr-2 align-top">:</td>
                        <td class="py-2 align-top">
                            <div>{{ $payment_for }}</div>
                            <div class="mt-1">Informasi Penerima : {{ $receiver_name }}</div>
                            <div class="mt-3 border-b border-dotted border-gray-400"></div>
                            <div class="mt-3 border-b border-dotted border-gray-400"></div>
                            <div class="mt-3 border-b border-dotted border-gray-400"></div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-10 flex items-end justify-between">
                <div class="inline-flex items-center gap-2 border border-gray-900 px-4 py-2">
                    <span class="text-sm">Rp</span>
                    <span class="text-lg font-bold">{{ $amount_formatted }}</span>
                </div>
                <div class="text-right">
                    <p class="text-sm">{{ $city_date }}</p>
                    <div class="mt-6 h-16 w-40 border border-dashed border-gray-500 mx-auto"></div>
                    <p class="mt-2 text-sm">( {{ $signer_name }} - {{ $companyProfile['name'] }} )</p>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 pt-4 text-xs leading-relaxed text-gray-500">
                <p class="font-semibold uppercase tracking-wider text-gray-600">Catatan</p>
                <p class="mt-1">
                    Kwitansi ini merupakan bukti pembayaran yang sah apabila telah dibubuhi meterai / tanda tangan resmi dari {{ $companyProfile['name'] }}. Pembayaran yang sudah dilakukan tidak dapat dikembalikan atau dibatalkan sepihak (non-refundable), dan barang/jasa yang telah dibeli tidak dapat ditukar kecuali ada perjanjian sebelumnya. Mohon simpan bukti pembayaran ini untuk keperluan klaim atau konfirmasi di kemudian hari.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>