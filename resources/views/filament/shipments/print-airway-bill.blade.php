<x-filament-panels::page>
    @php
        $awb = $shipment->awb_number;
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

            body {
                background: #fff !important;
            }
        }
    </style>

    <div class="space-y-6">
        <div class="no-print flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                Airway Bill
            </h2>

            <x-filament::button
                icon="heroicon-o-printer"
                x-on:click="window.print()"
            >
                Cetak
            </x-filament::button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-8 text-gray-900 shadow-sm print:border-0 print:p-0 print:shadow-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            <div class="flex items-start justify-between border-b-2 border-gray-900 pb-4 dark:border-gray-100">
                <div>
                    <p class="text-2xl font-bold uppercase tracking-wide">ATL Express</p>
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">
                        Airway Bill / Nota Pengiriman
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Nomor AWB</p>
                    <p class="text-xl font-bold tracking-wide">{{ $awb }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $shipment->created_at?->translatedFormat('d M Y H:i') }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 py-6">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                        Pengirim
                    </p>
                    <p class="font-semibold">{{ $shipment->sender_name }}</p>
                    @if ($shipment->sender_phone)
                        <p class="text-sm">{{ $shipment->sender_phone }}</p>
                    @endif
                    <p class="text-sm leading-relaxed">{{ $shipment->sender_address ?: '-' }}</p>
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                        Penerima
                    </p>
                    <p class="font-semibold">{{ $shipment->receiver_name ?: '-' }}</p>
                    @if ($shipment->receiver_phone)
                        <p class="text-sm">{{ $shipment->receiver_phone }}</p>
                    @endif
                    <p class="text-sm leading-relaxed">{{ $shipment->receiver_address ?: '-' }}</p>
                </div>
            </div>

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-y border-gray-300 text-left dark:border-gray-600">
                        <th class="py-2 pr-3 font-semibold uppercase tracking-wider text-xs">No. Tracking</th>
                        <th class="py-2 pr-3 font-semibold uppercase tracking-wider text-xs">Rute</th>
                        <th class="py-2 pr-3 font-semibold uppercase tracking-wider text-xs">Berat</th>
                        <th class="py-2 pr-3 font-semibold uppercase tracking-wider text-xs">Dimensi</th>
                        <th class="py-2 font-semibold uppercase tracking-wider text-xs">Tarif Final</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <td class="py-2 pr-3">{{ $shipment->tracking_number }}</td>
                        <td class="py-2 pr-3">{{ $shipment->origin }} &rarr; {{ $shipment->destination }}</td>
                        <td class="py-2 pr-3">{{ $shipment->weight ? $shipment->weight.' kg' : '-' }}</td>
                        <td class="py-2 pr-3">{{ $shipment->final_dimensions ?: '-' }}</td>
                        <td class="py-2">{{ $shipment->final_tariff ? 'Rp '.number_format((float) $shipment->final_tariff, 0, ',', '.') : '-' }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($logs->isNotEmpty())
                <div class="mt-6">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                        Riwayat
                    </p>
                    <ul class="space-y-1 text-sm">
                        @foreach ($logs as $log)
                            <li class="flex justify-between gap-4">
                                <span>{{ $log->status_description }}</span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $log->location ?: '-' }}
                                    &middot;
                                    {{ $log->timestamp?->translatedFormat('d M Y H:i') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-10 grid grid-cols-2 gap-8 text-sm">
                <div>
                    <p class="border-t border-gray-400 pt-2 text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 dark:border-gray-500">
                        Tanda tangan petugas
                    </p>
                </div>
                <div>
                    <p class="border-t border-gray-400 pt-2 text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 dark:border-gray-500">
                        Tanda tangan penerima
                    </p>
                </div>
            </div>

            <p class="mt-6 text-center text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                Barang yang diterima dalam kondisi baik telah diperiksa dan diterima conjuntamente.
                Klaim kerusakan atau kehilangan wajib disampaikan maksimal 7x24 jam setelah barang diterima.
            </p>
        </div>
    </div>
</x-filament-panels::page>
