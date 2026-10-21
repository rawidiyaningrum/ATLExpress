@php
    $money = fn ($amount): string => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $weightValue = $estimate->weight !== null ? (float) $estimate->weight : null;
    $weight = $weightValue === null
        ? null
        : (fmod($weightValue, 1.0) === 0.0
            ? number_format($weightValue, 0, ',', '.')
            : rtrim(rtrim(number_format($weightValue, 2, ',', '.'), '0'), ','));
    $additionalTotal = $estimate->charges->sum(fn ($charge) => $charge->type === \App\Services\InvoiceService::TYPE_DISKON
        ? -(float) $charge->line_total
        : (float) $charge->line_total);
    $integrity = strtoupper(substr(hash('sha256', implode('|', [
        $estimate->estimate_number,
        $estimate->created_at?->timestamp,
        (string) $estimate->weight,
        (string) $estimate->origin,
        (string) $estimate->destination,
    ])), 0, 10));
    $barcode = \App\Support\Code128::svg($estimate->estimate_number . ' ' . $integrity, 42, 1.0, 8);
@endphp

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

    <div class="space-y-4">
        <div class="no-print flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold tracking-tight text-gray-950">
                Estimasi {{ $estimate->estimate_number }}
            </h2>

            <x-filament::button
                icon="heroicon-o-printer"
                x-on:click="window.print()"
            >
                Cetak
            </x-filament::button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 text-gray-900 shadow-sm" style="position: relative; isolation: isolate; overflow: hidden;">
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: -1; pointer-events: none; overflow: hidden;">
                <span style="transform: rotate(-45deg); font-size: 7rem; line-height: 1; font-weight: 900; letter-spacing: 0.25em; text-transform: uppercase; white-space: nowrap; color: rgba(107, 114, 128, 0.14); user-select: none;">
                    ESTIMASI
                </span>
            </div>

            <div class="flex items-start justify-between border-b-2 border-gray-900 pb-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="ATL Express" style="width: 100px; height: auto" class="rounded-lg object-contain">
                    <div>
                        <p class="text-2xl font-bold uppercase tracking-wide">ATL Express</p>
                        <p class="text-xs uppercase tracking-widest text-gray-500">
                            Estimasi Pengiriman
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-xs uppercase tracking-widest text-gray-500">Nomor Estimasi</p>
                    <p class="text-xl font-bold tracking-wide">{{ $estimate->estimate_number }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $estimate->created_at?->translatedFormat('d M Y H:i') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        Estimasi ini bukan tagihan dan berlaku sebagai penawaran tarif.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 py-4">
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                        Pengirim
                    </p>
                    <p class="font-semibold">{{ $estimate->sender_name ?: '-' }}</p>
                    <p class="text-sm leading-relaxed">
                        {{ $estimate->sender_address ?: '-' }}
                    </p>
                    @if ($estimate->sender_phone)
                        <p class="text-sm text-gray-500">Telp: {{ $estimate->sender_phone }}</p>
                    @endif
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-500">
                        Penerima
                    </p>
                    <p class="font-semibold">{{ $estimate->receiver_name ?: '-' }}</p>
                    <p class="text-sm leading-relaxed">
                        {{ $estimate->receiver_address ?: '-' }}
                    </p>
                    @if ($estimate->receiver_phone)
                        <p class="text-sm text-gray-500">Telp: {{ $estimate->receiver_phone }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 border-y border-gray-200 py-4 text-sm">
                <div>
                    <p>
                        <span class="text-gray-500">Rute :</span>
                        <span class="font-semibold">
                            {{ $estimate->origin ?: '-' }} &rarr; {{ $estimate->destination ?: '-' }}
                        </span>
                    </p>
                    <p>
                        <span class="text-gray-500">Berat :</span>
                        <span class="font-semibold">{{ $weight ? $weight . ' kg' : '-' }}</span>
                    </p>
                </div>
                <div>
                    <p>
                        <span class="text-gray-500">Jenis/Isi Barang :</span>
                        <span class="font-semibold">{{ $estimate->item_type ?: '-' }}</span>
                    </p>
                    @if ($estimate->notes)
                        <p>
                            <span class="text-gray-500">Catatan :</span>
                            <span>{{ $estimate->notes }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <div class="pt-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-500">
                    Perbandingan Tarif per Jenis Armada
                </p>

                @include('filament.shipping-estimates.comparison', ['rows' => $rows, 'compact' => true])
            </div>

            @if ($estimate->charges->isNotEmpty())
                <div class="mt-4 max-w-md">
                    <p class="mb-1 font-semibold uppercase tracking-widest text-gray-500" style="font-size: 9px;">
                        Biaya Lainnya
                    </p>
                    <ul class="text-gray-700" style="font-size: 10px; line-height: 1.5;">
                        @foreach ($estimate->charges as $charge)
                            <li class="flex justify-between gap-4">
                                <span>
                                    {{ $charge->description }}
                                    @if ((int) $charge->quantity > 1)
                                        <span class="text-gray-500">({{ (int) $charge->quantity }} x)</span>
                                    @endif
                                </span>
                                <span class="font-medium {{ $charge->type === \App\Services\InvoiceService::TYPE_DISKON ? 'text-danger-600 ' : '' }}">
                                    {{ $charge->type === \App\Services\InvoiceService::TYPE_DISKON ? '-' : '' }}{{ $money($charge->line_total) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-1 flex justify-between gap-4 border-t border-gray-300 pt-1 font-semibold text-gray-900" style="font-size: 10px;">
                        <span>Total Biaya Lainnya</span>
                        <span>{{ $money($additionalTotal) }}</span>
                    </div>
                </div>
            @endif

            <div class="mt-10 max-w-3xl border-t border-gray-200 pt-3">
                <p class="leading-snug tracking-wide text-gray-400" style="font-size: 9px;">
                    Catatan: Dasar Pengenaan Pajak (DPP) = ongkos kirim + biaya lain. PPN dihitung 1,1% dari DPP;
                    PPh dipotong 2% dari DPP oleh pihak pembeli saat pembayaran. Nominal dapat berubah mengikuti
                    berat aktual barang dan tarif yang berlaku.
                </p>

                <p class="mt-1 font-medium leading-snug tracking-wide text-gray-500" style="font-size: 9px;">
                    Untuk adjustment (penyesuaian) estimasi, silakan diskusi dengan tim ATL Express.
                </p>
            </div>

            <div class="mt-8 flex flex-col items-center border-t border-gray-200 pt-4">
                @if ($barcode !== '')
                    <div>{!! $barcode !!}</div>
                @endif
                <p class="mt-1 text-center font-mono uppercase tracking-widest text-gray-500" style="font-size: 9px;">
                    {{ $estimate->estimate_number }} &middot; {{ $integrity }}
                </p>
                <p class="mt-1 text-center text-gray-500" style="font-size: 9px;">
                    Dokumen ini dicetak dari sistem ATL Express dan sah tanpa tanda tangan basah. Barcode di atas menjamin integritas dokumen.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
