<div>
    <form wire:submit="calculate" class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="origin" class="block text-sm font-bold text-gray-700 mb-2">Asal *</label>
                <select id="origin" wire:model.live="origin" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-gold focus:border-gold transition">
                    <option value="">Pilih Kota Asal</option>
                    @foreach($origins as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
                @error('origin')
                    <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="destination" class="block text-sm font-bold text-gray-700 mb-2">Tujuan *</label>
                <select id="destination" wire:model="destination" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-gold focus:border-gold transition">
                    <option value="">Pilih Kota Tujuan</option>
                    @foreach($destinations as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
                @error('destination')
                    <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <label for="weight" class="block text-sm font-bold text-gray-700 mb-2">Berat (kg) *</label>
            <input
                type="number"
                id="weight"
                wire:model="weight"
                step="0.5"
                min="0.5"
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-gold focus:border-gold transition"
            >
            @error('weight')
                <p class="mt-1 text-accent text-xs">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-6 py-3.5 rounded-xl text-sm font-bold transition hover:bg-primary-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1c0 .002.002.002.002.002H12m-4 0c-1.11 0-2.08-.402-2.599-1M20 12a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>
            Hitung Tarif
        </button>
    </form>

    @if(count($results) > 0)
        <div class="mt-8">
            <h4 class="font-bold text-primary mb-4">Hasil Perhitungan</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider rounded-tl-xl">Layanan</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Tarif/kg</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Estimasi</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider rounded-tr-xl">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800 text-sm">{{ $result['service_type'] }}</td>
                                <td class="px-4 py-3 text-gray-600 text-sm">Rp {{ number_format($result['price_per_kg'], 0, ',', '.') }}/kg</td>
                                <td class="px-4 py-3 text-gray-600 text-sm">{{ $result['estimated_days'] }} hari</td>
                                <td class="px-4 py-3 font-bold text-primary text-sm">Rp {{ number_format($result['total_price'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400 mt-4">*Harga di atas adalah estimasi dan dapat berubah sewaktu-waktu. Hubungi kami untuk harga pasti.</p>
        </div>
    @endif
</div>
