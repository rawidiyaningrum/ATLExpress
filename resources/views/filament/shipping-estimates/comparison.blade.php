@php
    /** @var array<int, array<string, mixed>> $rows */
    /** @var bool $compact */
    $compact = $compact ?? false;
    $money = fn ($amount): string => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $price = fn ($amount): string => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $showPpn = collect($rows)->contains(fn (array $row): bool => (float) $row['ppn'] > 0);
    $showPph = collect($rows)->contains(fn (array $row): bool => (float) $row['pph'] > 0);
    $pad = $compact ? 'px-2 py-1' : 'px-3 py-2';
    $table = $compact
        ? 'w-auto border-collapse text-xs text-gray-900 dark:text-gray-100'
        : 'w-full border-collapse text-sm text-gray-900 dark:text-gray-100';
    $base = "border border-gray-200 {$pad} dark:border-gray-700";
@endphp

@if (empty($rows))
    <p class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
        Belum ada armada untuk dibandingkan. Pilih kota asal, kabupaten, dan kota tujuan terlebih dahulu.
    </p>
@else
    <div class="w-full overflow-x-auto">
        <table class="{{ $table }}">
            <thead>
                <tr class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <th class="{{ $base }}">Jenis Armada</th>
                    @unless ($compact)
                        <th class="{{ $base }} text-right">Tarif</th>
                    @endunless
                    <th class="{{ $base }} text-right">Ongkos Kirim</th>
                    <th class="{{ $base }} text-right">Biaya Lain</th>
                    <th class="{{ $base }} text-right">DPP</th>
                    @if ($showPpn)
                        <th class="{{ $base }} text-right">PPN 1,1%</th>
                    @endif
                    @if ($showPph)
                        <th class="{{ $base }} text-right">PPh 2%</th>
                    @endif
                    <th class="{{ $base }} text-right">Estimasi</th>
                    <th class="{{ $base }} text-right font-bold">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800/50">
                        <td class="{{ $base }} font-medium">
                            {{ $row['service_label'] }}
                        </td>
                        @unless ($compact)
                            <td class="{{ $base }} text-right">
                                @if ($row['is_final'])
                                    -
                                @else
                                    {{ $price($row['price_per_kg']) }} /kg
                                @endif
                            </td>
                        @endunless
                        <td class="{{ $base }} text-right">
                            {{ $money($row['shipping_cost']) }}
                        </td>
                        <td class="{{ $base }} text-right">
                            {{ $money($row['additional']) }}
                            @if ((float) $row['diskon'] > 0)
                                <span class="block text-xs text-danger-600 dark:text-danger-400">
                                    diskon -{{ $money($row['diskon']) }}
                                </span>
                            @endif
                        </td>
                        <td class="{{ $base }} text-right">
                            {{ $money($row['dpp']) }}
                        </td>
                        @if ($showPpn)
                            <td class="{{ $base }} text-right">
                                {{ $money($row['ppn']) }}
                            </td>
                        @endif
                        @if ($showPph)
                            <td class="{{ $base }} text-right">
                                -{{ $money($row['pph']) }}
                            </td>
                        @endif
                        <td class="{{ $base }} text-right">
                            {{ $row['estimated_days'] ? $row['estimated_days'] . ' hari' : '-' }}
                        </td>
                        <td class="{{ $base }} text-right font-bold">
                            {{ $money($row['total']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
