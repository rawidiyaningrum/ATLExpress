<x-filament-panels::page>
    {{-- Form Pencarian Rute --}}
    <form wire:submit.prevent="search" class="space-y-4">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-3">
            @if ($hasSearched)
                <button
                    type="button"
                    wire:click="resetSearch"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 px-4 py-2 text-sm shadow-sm"
                >
                    Reset
                </button>
            @endif

            <button
                type="submit"
                class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg bg-[#0D2053] hover:bg-opacity-90 text-white px-5 py-2 text-sm shadow-sm flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cek Tarif Trucking & Shipment</span>
            </button>
        </div>
    </form>

    {{-- Hasil Pencarian --}}
    @if ($hasSearched)
        <div class="mt-8 space-y-6">
            {{-- Banner Ringkasan Rute --}}
            <div class="p-4 rounded-xl bg-gradient-to-r from-[#0D2053] to-blue-900 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-md">
                <div>
                    <span class="text-xs uppercase tracking-wider text-blue-200 font-bold block">Rute yang Dicek</span>
                    <h2 class="text-xl font-extrabold flex items-center gap-2">
                        <span>{{ $searchOrigin }}</span>
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        <span>{{ $searchDestination }}</span>
                    </h2>
                    <p class="text-xs text-blue-100 mt-0.5">
                        Estimasi berat acuan: <strong class="text-amber-300">{{ number_format($searchWeight, 1, ',', '.') }} kg</strong>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 rounded-lg bg-white/10 text-xs font-semibold backdrop-blur">
                        🚚 <strong>{{ $vendorRoutes->count() }}</strong> Vendor Trucking
                    </span>
                    <span class="px-3 py-1.5 rounded-lg bg-white/10 text-xs font-semibold backdrop-blur">
                        📦 <strong>{{ $shippingRates->count() }}</strong> Tarif Ongkir
                    </span>
                </div>
            </div>

            {{-- Grid 2 Kolom Komparasi --}}
            <div class="grid lg:grid-cols-2 gap-6 items-start">
                {{-- PANEL KIRI: Tarif Trucking dari Menu Rute Vendor (Agen Pengiriman) --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-2">
                            <span class="p-2 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                            </span>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-base">Tarif Trucking Vendor</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Dari Menu Rute Vendor / Database Agen Pengiriman</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                            {{ $vendorRoutes->count() }} Pilihan
                        </span>
                    </div>

                    <div class="p-5 space-y-4">
                        @forelse ($vendorRoutes as $route)
                            @php
                                $agent = $route->agent;
                                $cleanPhone = preg_replace('/\D/', '', $agent->phone ?? '');
                                $waText = urlencode("Halo {$agent->name}, saya dari ATL Express ingin menanyakan ketersediaan armada {$route->fleet_type} untuk rute {$route->origin} ke {$route->destination}. Apakah unit tersedia?");
                                $totalEstimated = $route->rate_type === 'reguler' ? ($route->tariff * max((float) $searchWeight, (float) ($route->min_weight ?? 1))) : $route->tariff;
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 transition bg-white dark:bg-gray-800/80 shadow-sm">
                                {{-- Header Agen --}}
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-extrabold text-gray-900 dark:text-white text-base">{{ $agent->name ?? 'Vendor' }}</h4>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Aktif</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            📍 {{ $agent->location ?? '-' }}
                                            @if($agent->pic_name) &bull; PIC: <strong>{{ $agent->pic_name }}</strong> @endif
                                        </p>
                                    </div>

                                    @if ($cleanPhone)
                                        <a
                                            href="https://wa.me/{{ $cleanPhone }}?text={{ $waText }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-bold text-xs shadow transition shrink-0"
                                            title="Chat WhatsApp Vendor"
                                        >
                                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.98.54 1.761.802 2.796.803 3.183 0 5.768-2.587 5.769-5.766.001-3.182-2.585-5.77-5.769-5.77m-8.91 5.766c.002-4.887 3.978-8.861 8.879-8.861 4.898 0 8.878 3.974 8.879 8.861-.001 4.886-3.979 8.86-8.878 8.86-1.523 0-2.846-.376-4.047-1.071l-4.521 1.185 1.211-4.423c-.832-1.309-1.523-2.735-1.523-4.551"/></svg>
                                            <span>Chat Vendor</span>
                                        </a>
                                    @endif
                                </div>

                                {{-- Rincian Armada & Tarif --}}
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 text-xs mb-3">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Armada Trucking</span>
                                        <span class="font-extrabold text-blue-700 dark:text-blue-300 text-sm">{{ $route->fleet_type }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Jenis Tarif</span>
                                        @if ($route->rate_type === 'carter')
                                            <span class="inline-block px-2 py-0.5 rounded font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Carter / Borongan</span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 rounded font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Reguler per Kg</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Estimasi Waktu</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $route->estimated_days ?: '-' }}</span>
                                    </div>
                                </div>

                                {{-- Harga --}}
                                <div class="flex items-baseline justify-between border-t border-gray-100 dark:border-gray-700 pt-2.5">
                                    <div>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 block">
                                            @if ($route->rate_type === 'carter')
                                                Tarif Sewa Borongan Armada
                                            @else
                                                Tarif per Kg @if($route->min_weight) (Min. {{ $route->min_weight }} kg) @endif
                                            @endif
                                        </span>
                                        <span class="text-lg font-black text-gray-900 dark:text-white">
                                            Rp {{ number_format((float) $route->tariff, 0, ',', '.') }}
                                            @if($route->rate_type === 'reguler')<span class="text-xs text-gray-500 font-normal">/kg</span>@endif
                                        </span>
                                    </div>

                                    @if ($route->rate_type === 'reguler')
                                        <div class="text-right">
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block">Est. Total ({{ number_format($searchWeight, 0, ',', '.') }} kg)</span>
                                            <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                                Rp {{ number_format($totalEstimated, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                @if ($route->notes)
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 bg-gray-50 dark:bg-gray-700/20 p-2 rounded">
                                        ℹ️ {{ $route->notes }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 px-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
                                <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Belum ada rute vendor yang terdaftar</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">Belum ada vendor/agen pengiriman yang memiliki rute {{ $searchOrigin }} ke {{ $searchDestination }}.</p>
                                <a
                                    href="{{ \App\Filament\Resources\DeliveryAgentResource::getUrl('create') }}"
                                    class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-[#0D2053] text-white text-xs font-bold hover:bg-opacity-90 transition"
                                >
                                    + Tambah Rute Vendor Baru
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- PANEL KANAN: Tarif Ongkir & Data Menu Shipment (ATL Express) --}}
                <div class="space-y-6">
                    {{-- Sub-panel 1: Master Tarif Resmi Ongkir Shipment --}}
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Tarif Ongkir Shipment (ATL Express)</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Master Tarif Resmi / Cek Ongkir ke Pelanggan</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300">
                                {{ $shippingRates->count() }} Layanan
                            </span>
                        </div>

                        <div class="p-5">
                            @if ($shippingRates->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 uppercase tracking-wider text-[10px]">
                                            <tr>
                                                <th class="py-2">Layanan</th>
                                                <th class="py-2">Tarif / Kg</th>
                                                <th class="py-2">Min. Berat</th>
                                                <th class="py-2">Est. Hari</th>
                                                <th class="py-2 text-right">Est. Ongkir ({{ number_format($searchWeight, 0, ',', '.') }} kg)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($shippingRates as $rate)
                                                @php
                                                    $chargeableWeight = max((float) $searchWeight, (float) $rate->min_weight);
                                                    $estimatedCost = $chargeableWeight * (float) $rate->price_per_kg;
                                                @endphp
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                                    <td class="py-3 font-bold text-gray-900 dark:text-white">
                                                        <span class="capitalize">{{ $rate->service_type }}</span>
                                                        @if($rate->kabupaten_tujuan)
                                                            <span class="block text-[10px] text-gray-400 font-normal">Kab: {{ $rate->kabupaten_tujuan }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 font-semibold text-gray-800 dark:text-gray-200">
                                                        Rp {{ number_format((float) $rate->price_per_kg, 0, ',', '.') }}
                                                    </td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">
                                                        {{ number_format((float) $rate->min_weight, 0, ',', '.') }} kg
                                                    </td>
                                                    <td class="py-3 text-gray-600 dark:text-gray-300">
                                                        {{ $rate->estimated_days ?: '-' }}
                                                    </td>
                                                    <td class="py-3 text-right font-black text-blue-700 dark:text-blue-300">
                                                        Rp {{ number_format($estimatedCost, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-6 px-3 rounded-lg border border-dashed border-gray-200 dark:border-gray-700">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada tarif ongkir resmi terdaftar untuk rute ini di menu Manajemen Tarif.</p>
                                    <a
                                        href="{{ \App\Filament\Resources\ShippingRateResource::getUrl('create') }}"
                                        class="inline-flex items-center gap-1 mt-2 text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline"
                                    >
                                        + Input Tarif Ongkir Baru
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Sub-panel 2: Riwayat Pengiriman Terakhir dari Menu Shipment --}}
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                        <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-2">
                                <span class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </span>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Riwayat Resi / Shipment Terakhir</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Pengiriman aktual pada rute ini dari Menu Shipment</p>
                                </div>
                            </div>
                            <a
                                href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}"
                                class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline"
                            >
                                Buka Shipment &rarr;
                            </a>
                        </div>

                        <div class="p-5">
                            @if ($recentShipments->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach ($recentShipments as $shipment)
                                        @php
                                            $badgeColor = \App\Models\Shipment::STATUS_COLORS[$shipment->status] ?? 'gray';
                                        @endphp
                                        <div class="p-3 rounded-lg border border-gray-100 dark:border-gray-700/60 bg-gray-50/60 dark:bg-gray-700/20 flex items-center justify-between gap-3 text-xs">
                                            <div>
                                                <span class="font-extrabold text-gray-900 dark:text-white font-mono block">
                                                    {{ $shipment->awb_number }}
                                                </span>
                                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                                    {{ $shipment->created_at?->format('d M Y') }} &bull; Layanan: {{ ucfirst($shipment->service_type ?? 'Reguler') }} &bull; Berat: {{ number_format((float) $shipment->weight, 1, ',', '.') }} kg
                                                </span>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                                    @if($badgeColor === 'success') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                                    @elseif($badgeColor === 'warning') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                                                    @elseif($badgeColor === 'info') bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                                                    @else bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif
                                                ">
                                                    {{ \App\Models\Shipment::statusLabel($shipment->status) }}
                                                </span>
                                                @if ($shipment->final_tariff)
                                                    <span class="block font-bold text-gray-900 dark:text-white mt-1">
                                                        Rp {{ number_format((float) $shipment->final_tariff, 0, ',', '.') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-center text-xs text-gray-500 dark:text-gray-400 py-4">
                                    Belum ada transaksi pengiriman shipment yang tercatat untuk rute ini.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
