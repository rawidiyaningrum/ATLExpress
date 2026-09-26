{{-- Tabel rincian biaya yang dipakai bersama oleh halaman detail invoice
     (App\Filament\Infolists\Components\InvoiceItemsTable) dan halaman cetak
     invoice, supaya keduanya selalu tampil sama.

     Parameter:
     - $invoice         App\Models\Invoice
     - $itemBasisLabels array label basis item dari InvoiceService
     - $money           opsional, closure format nominal. --}}
@php
    $money ??= fn ($amount): string => 'Rp ' . number_format((float) $amount, 0, ',', '.');
@endphp

<table class="w-full border-collapse text-sm">
    <thead>
        <tr class="border-y border-gray-300 text-left dark:border-gray-600">
            <th class="py-2 pe-3 font-semibold text-xs">Keterangan</th>
            <th class="py-2 pe-3 font-semibold text-xs">Jenis</th>
            <th class="py-2 pe-3 font-semibold text-xs">Dihitung dari</th>
            <th class="py-2 pe-3 text-right font-semibold text-xs">Jumlah</th>
            <th class="py-2 pe-3 text-right font-semibold text-xs">Harga Satuan</th>
            <th class="py-2 text-right font-semibold text-xs">Jumlah Baris</th>
        </tr>
    </thead>
    <tbody>
        <tr class="border-b border-gray-200 dark:border-gray-700">
            <td class="py-2 pe-3">Ongkos kirim</td>
            <td class="py-2 pe-3">Pengiriman</td>
            <td class="py-2 pe-3">Tarif final</td>
            <td class="py-2 pe-3 text-right">1</td>
            <td class="py-2 pe-3 text-right">{{ $money($invoice->shipping_cost) }}</td>
            <td class="py-2 text-right font-semibold">{{ $money($invoice->shipping_cost) }}</td>
        </tr>

        @forelse ($invoice->items as $item)
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="py-2 pe-3">{{ $item->description }}</td>
                <td class="py-2 pe-3">{{ ucfirst($item->type) }}</td>
                <td class="py-2 pe-3">{{ $itemBasisLabels[$item->basis] ?? $item->basis }}</td>
                <td class="py-2 pe-3 text-right">{{ $item->quantity }}</td>
                <td class="py-2 pe-3 text-right">{{ $money($item->unit_price) }}</td>
                <td class="py-2 text-right font-semibold">{{ $money($item->line_total) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-3 text-center text-gray-500 dark:text-gray-400">
                    Tidak ada biaya tambahan.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
