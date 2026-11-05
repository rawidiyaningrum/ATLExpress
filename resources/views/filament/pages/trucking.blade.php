<x-filament-panels::page>
    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
                type="button"
                wire:click="$set('activeTab', 'compare')"
                class="py-3 px-2 border-b-2 font-bold text-sm inline-flex items-center gap-2 transition duration-150 {{ $activeTab === 'compare' ? 'border-[#0D2053] text-[#0D2053] dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Cek & Komparasi Rute</span>
            </button>

            <button
                type="button"
                wire:click="$set('activeTab', 'trayek')"
                class="py-3 px-2 border-b-2 font-bold text-sm inline-flex items-center gap-2 transition duration-150 {{ $activeTab === 'trayek' ? 'border-[#0D2053] text-[#0D2053] dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>List Trayek Trucking</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-[#0D2053] dark:bg-blue-900/40 dark:text-blue-300">
                    Tabel Rute Vendor
                </span>
            </button>
        </nav>
    </div>

    {{-- TAB 1: CEK & KOMPARASI RUTE --}}
    @if ($activeTab === 'compare')
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

        {{-- Hasil Pencarian Komparasi --}}
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
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                                <span>{{ $agent->phone }}</span>
                                            </a>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 text-xs">
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Jenis Armada</span>
                                            <strong class="font-bold text-gray-900 dark:text-white uppercase">{{ $route->fleet_type }}</strong>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Tipe Tarif</span>
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $route->rate_type === 'carter' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
                                                {{ ucfirst($route->rate_type) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Estimasi Waktu</span>
                                            <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $route->estimated_time ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Tarif Rute</span>
                                            <strong class="text-amber-600 dark:text-amber-400 font-extrabold text-sm">
                                                Rp {{ number_format($route->tariff, 0, ',', '.') }}
                                                <span class="text-[10px] font-normal text-gray-500">{{ $route->rate_type === 'reguler' ? '/kg' : '/trip' }}</span>
                                            </strong>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Min. Berat</span>
                                            <span class="text-gray-800 dark:text-gray-200">{{ $route->min_weight ? $route->min_weight.' kg' : '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Total (Est. {{ (float)$searchWeight }}kg)</span>
                                            <strong class="text-gray-900 dark:text-white font-bold">
                                                Rp {{ number_format($totalEstimated, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>

                                    @if($route->notes)
                                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 italic">
                                            Catatan: {{ $route->notes }}
                                        </p>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-10 px-4">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum Ada Vendor Trucking untuk Rute Ini</h4>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Tambahkan tarif vendor di menu <a href="{{ \App\Filament\Resources\DeliveryAgentResource::getUrl('index') }}" class="text-blue-600 dark:text-blue-400 underline font-bold">Database Agen Pengiriman</a>.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- PANEL KANAN: Tarif Ongkir dari Menu Shipment & Master Tarif --}}
                    <div class="space-y-6">
                        {{-- Sub-panel 1: Master Tarif Ongkir ATL Express --}}
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
                                <div class="flex items-center gap-2">
                                    <span class="p-2 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </span>
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Tarif Ongkir Shipment (ATL Express)</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Tarif jual ke customer dari Master Tarif</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300">
                                    {{ $shippingRates->count() }} Tarif Terdaftar
                                </span>
                            </div>

                            <div class="p-5 space-y-4">
                                @forelse ($shippingRates as $rate)
                                    @php
                                        $weightEffective = max((float) $searchWeight, (float) ($rate->min_weight ?? 1));
                                        $calculatedTotal = $rate->price_per_kg * $weightEffective;
                                    @endphp
                                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 shadow-sm">
                                        <div class="flex items-start justify-between gap-3 mb-2">
                                            <div>
                                                <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                                    {{ $rate->destination_city }} {{ $rate->kabupaten_tujuan ? '('.$rate->kabupaten_tujuan.')' : '' }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    Asal: {{ $rate->origin_city ?? 'Jakarta' }} &bull; Layanan: <strong class="text-blue-600 dark:text-blue-400">{{ ucfirst($rate->service_type ?? 'Cargo Darat') }}</strong>
                                                </p>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                Estimasi: {{ $rate->estimated_delivery ?? '2-4 Hari' }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-3 gap-2 p-2.5 rounded-lg bg-blue-50/50 dark:bg-blue-950/20 text-xs mt-3">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Tarif per Kg</span>
                                                <strong class="text-blue-700 dark:text-blue-300 text-sm font-extrabold">
                                                    Rp {{ number_format($rate->price_per_kg, 0, ',', '.') }}
                                                </strong>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Min. Berat</span>
                                                <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $rate->min_weight ?? 1 }} kg</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-[11px]">Estimasi Total ({{ $weightEffective }}kg)</span>
                                                <strong class="text-gray-900 dark:text-white font-bold">
                                                    Rp {{ number_format($calculatedTotal, 0, ',', '.') }}
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-6 px-4">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Belum ada tarif ongkir resmi ATL Express untuk rute ini.
                                        </p>
                                    </div>
                                @endforelse
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

    {{-- TAB 2: LIST TRAYEK TRUCKING --}}
    @elseif ($activeTab === 'trayek')
        <div class="space-y-6">
            {{-- Panel Input Asal Kota & Filter --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                <div class="mb-4">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0D2053] dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.488V5.512a2 2 0 011.553-1.954L9 2m0 18l6 3m-6-3V2m6 21l5.447-2.724A2 2 0 0021 18.488V8.512a2 2 0 00-1.553-1.954L15 4m0 17V4m0 0L9 2"/></svg>
                        <span>Cari List Trayek Vendor Berdasarkan Kota Asal</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Pilih atau ketik kota asal untuk melihat seluruh vendor yang melayani rute dari kota tersebut beserta tujuan, kontak, armada, dan tarifnya.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-4 items-end">
                    {{-- Input Asal Kota --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Asal Kota
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                list="trayek-origins-list"
                                wire:model.live.debounce.300ms="trayekOrigin"
                                placeholder="Ketik atau pilih asal kota..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-4 py-2.5 text-sm shadow-sm focus:border-[#0D2053] focus:ring-[#0D2053]"
                            />
                            <datalist id="trayek-origins-list">
                                @foreach ($this->getOriginSuggestions() as $originSuggest)
                                    <option value="{{ $originSuggest }}"></option>
                                @endforeach
                            </datalist>

                            @if (!empty($trayekOrigin))
                                <button
                                    type="button"
                                    wire:click="$set('trayekOrigin', '')"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                    title="Hapus filter"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>

                        {{-- Quick Origin Pills --}}
                        @php
                            $quickOrigins = collect($this->getOriginSuggestions())->take(6);
                        @endphp
                        @if ($quickOrigins->isNotEmpty())
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Pilihan cepat:</span>
                                @foreach ($quickOrigins as $quickCity)
                                    <button
                                        type="button"
                                        wire:click="selectTrayekOrigin('{{ $quickCity }}')"
                                        class="px-2 py-0.5 rounded text-xs font-semibold transition {{ strtolower($trayekOrigin) === strtolower($quickCity) ? 'bg-[#0D2053] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}"
                                    >
                                        {{ $quickCity }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Search Filter Dalam Tabel --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                            Saring Tabel Trayek
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="trayekSearch"
                                placeholder="Cari tujuan / vendor / armada..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm shadow-sm focus:border-[#0D2053] focus:ring-[#0D2053]"
                            />
                            @if (!empty($trayekSearch))
                                <button
                                    type="button"
                                    wire:click="$set('trayekSearch', '')"
                                    class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel List Trayek --}}
            @php
                $trayekRoutes = $this->getTrayekRoutes();
            @endphp

            @if (trim($trayekOrigin) === '')
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">Silakan Masukkan / Pilih Asal Kota</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                        Ketik nama kota asal di atas (misal: "Jakarta") untuk menampilkan daftar trayek vendor yang melayani rute dari kota tersebut.
                    </p>
                </div>
            @else
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
                    {{-- Header Hasil Trayek --}}
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="p-2 rounded-lg bg-blue-500/10 text-[#0D2053] dark:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </span>
                            <div>
                                <h4 class="font-extrabold text-gray-900 dark:text-white text-base">
                                    Trayek dari Kota: <span class="text-[#0D2053] dark:text-blue-400 underline">{{ $trayekOrigin }}</span>
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Klik judul kolom <strong>Tujuan</strong> atau <strong>Kontak</strong> untuk mengurutkan (sort).
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-[#0D2053] dark:bg-blue-900/40 dark:text-blue-300">
                                Total: {{ $trayekRoutes->count() }} Rute
                            </span>
                            <a
                                href="{{ \App\Filament\Resources\DeliveryAgentResource::getUrl('index') }}"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                <span>Kelola Vendor</span> &rarr;
                            </a>
                        </div>
                    </div>

                    {{-- Tabel Trayek --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                <tr>
                                    <th scope="col" class="px-4 py-3.5 text-center w-12">
                                        No
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" wire:click="sortBy('vendor')" title="Klik untuk mengurutkan vendor">
                                        <div class="flex items-center gap-1.5">
                                            <span>Nama Vendor</span>
                                            @if ($trayekSortField === 'vendor')
                                                <span class="text-[#0D2053] dark:text-blue-400 font-bold">{{ $trayekSortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" wire:click="sortBy('phone')" title="Klik untuk mengurutkan kontak">
                                        <div class="flex items-center gap-1.5">
                                            <span>Kontak</span>
                                            @if ($trayekSortField === 'phone')
                                                <span class="text-[#0D2053] dark:text-blue-400 font-bold">{{ $trayekSortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" wire:click="sortBy('destination')" title="Klik untuk mengurutkan tujuan">
                                        <div class="flex items-center gap-1.5">
                                            <span>Tujuan</span>
                                            @if ($trayekSortField === 'destination')
                                                <span class="text-[#0D2053] dark:text-blue-400 font-bold">{{ $trayekSortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" wire:click="sortBy('fleet_type')" title="Klik untuk mengurutkan armada">
                                        <div class="flex items-center gap-1.5">
                                            <span>Armada</span>
                                            @if ($trayekSortField === 'fleet_type')
                                                <span class="text-[#0D2053] dark:text-blue-400 font-bold">{{ $trayekSortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" wire:click="sortBy('tariff')" title="Klik untuk mengurutkan tarif">
                                        <div class="flex items-center gap-1.5">
                                            <span>Tarif</span>
                                            @if ($trayekSortField === 'tariff')
                                                <span class="text-[#0D2053] dark:text-blue-400 font-bold">{{ $trayekSortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @else
                                                <span class="text-gray-400">↕</span>
                                            @endif
                                        </div>
                                    </th>
                                    <th scope="col" class="px-4 py-3.5 text-center">
                                        Aksi / WhatsApp
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                @forelse ($trayekRoutes as $route)
                                    @php
                                        $agent = $route->agent;
                                        $cleanPhone = preg_replace('/\D/', '', $agent->phone ?? '');
                                        $waText = urlencode("Halo {$agent->name}, saya dari ATL Express ingin menanyakan info trayek rute {$route->origin} ke {$route->destination} dengan armada {$route->fleet_type}. Apakah unit tersedia?");
                                    @endphp
                                    <tr class="hover:bg-blue-50/40 dark:hover:bg-gray-700/40 transition">
                                        <td class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                            <div class="flex flex-col">
                                                <span>{{ $agent->name ?? '-' }}</span>
                                                @if ($agent->location)
                                                    <span class="text-[11px] font-normal text-gray-500 dark:text-gray-400">
                                                        📍 {{ $agent->location }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-xs text-gray-800 dark:text-gray-200">
                                            <div class="flex flex-col">
                                                <span class="font-mono font-semibold">{{ $agent->phone ?? '-' }}</span>
                                                @if ($agent->pic_name)
                                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                        PIC: {{ $agent->pic_name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 font-semibold text-gray-900 dark:text-white">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-gray-400">{{ $route->origin }} &rarr;</span>
                                                <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-[#0D2053] dark:text-blue-300 font-bold text-xs">
                                                    {{ $route->destination }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-bold uppercase tracking-wider bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ $route->fleet_type }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 font-extrabold text-amber-600 dark:text-amber-400">
                                            <div class="flex flex-col">
                                                <span>Rp {{ number_format($route->tariff, 0, ',', '.') }}</span>
                                                <span class="text-[10px] font-normal text-gray-500 uppercase">
                                                    {{ $route->rate_type }} {{ $route->rate_type === 'reguler' ? '/ kg' : '/ trip' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            @if ($cleanPhone)
                                                <a
                                                    href="https://wa.me/{{ $cleanPhone }}?text={{ $waText }}"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white font-bold text-xs shadow-sm transition"
                                                    title="Hubungi via WhatsApp"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                                    <span>Chat Vendor</span>
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Tanpa Kontak</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                                            Tidak ditemukan trayek vendor untuk kota asal <strong>"{{ $trayekOrigin }}"</strong>
                                            @if (!empty($trayekSearch)) dengan kata kunci "{{ $trayekSearch }}" @endif.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
