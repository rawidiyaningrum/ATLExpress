<div>
    <form wire:submit="calculate" class="space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="origin" class="block text-sm font-bold text-gray-700 mb-2">Asal *</label>
                <select id="origin" wire:model.live="origin" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
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
                <select id="destination" wire:model="destination" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
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
                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition"
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

            <button
                type="button"
                wire:click="openBooking"
                class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-gold text-primary px-6 py-3.5 rounded-xl text-sm font-bold transition hover:bg-gold-300"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Pesan Sekarang
            </button>
        </div>
    @endif

    @if($showBooking)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-primary/80" wire:click="closeBooking"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <h4 class="font-bold text-primary">Pesan Sekarang</h4>
                    <button type="button" wire:click="closeBooking" class="p-2 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6">
                    @if($bookingSuccess)
                        <div class="text-center py-6">
                            <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h4 class="font-bold text-primary mb-2">Pesanan Terkirim!</h4>
                            <p class="text-gray-500 text-sm mb-6">Terima kasih, pesanan Anda sudah kami terima. Tim kami akan segera menghubungi Anda.</p>
                            <button type="button" wire:click="closeBooking" class="w-full inline-flex items-center justify-center bg-primary text-white px-6 py-3 rounded-xl text-sm font-bold transition hover:bg-primary-600">
                                Selesai
                            </button>
                        </div>
                    @else
                        <div class="bg-primary/5 rounded-xl p-4 mb-6">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold text-primary">{{ $origin }} → {{ $destination }}</span>
                                <span class="text-gray-500">{{ $weight }} kg</span>
                            </div>
                        </div>

                        <form wire:submit="submitBooking" class="space-y-4">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="booking_name" class="block text-sm font-bold text-gray-700 mb-2">Nama *</label>
                                    <input type="text" id="booking_name" wire:model="name" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                    @error('name')
                                        <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="booking_phone" class="block text-sm font-bold text-gray-700 mb-2">No. Telepon *</label>
                                    <input type="text" id="booking_phone" wire:model="phone" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                    @error('phone')
                                        <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label for="booking_email" class="block text-sm font-bold text-gray-700 mb-2">Email *</label>
                                <input type="email" id="booking_email" wire:model="email" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                @error('email')
                                    <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="booking_pickup_address" class="block text-sm font-bold text-gray-700 mb-2">Alamat Penjemputan *</label>
                                <textarea id="booking_pickup_address" wire:model="pickup_address" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition resize-none"></textarea>
                                @error('pickup_address')
                                    <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="booking_item_type" class="block text-sm font-bold text-gray-700 mb-2">Jenis Barang *</label>
                                    <input type="text" id="booking_item_type" wire:model="item_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                    @error('item_type')
                                        <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="booking_service_type" class="block text-sm font-bold text-gray-700 mb-2">Layanan</label>
                                    <select id="booking_service_type" wire:model="service_type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                        @foreach($results as $result)
                                            <option value="{{ $result['service_type'] }}">{{ $result['service_type'] }} (Rp {{ number_format($result['total_price'], 0, ',', '.') }})</option>
                                        @endforeach
                                    </select>
                                    @error('service_type')
                                        <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="booking_weight" class="block text-sm font-bold text-gray-700 mb-2">Berat (kg) *</label>
                                    <input type="number" id="booking_weight" wire:model="weight" step="0.5" min="0.5" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                    @error('weight')
                                        <p class="mt-1 text-accent text-xs">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="booking_dimensions" class="block text-sm font-bold text-gray-700 mb-2">Dimensi (PxLxT cm)</label>
                                    <input type="text" id="booking_dimensions" wire:model="dimensions" placeholder="Contoh: 50x40x30" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition">
                                </div>
                            </div>
                            <div>
                                <label for="booking_notes" class="block text-sm font-bold text-gray-700 mb-2">Keterangan</label>
                                <textarea id="booking_notes" wire:model="notes" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition resize-none"></textarea>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-6 py-3.5 rounded-xl text-sm font-bold transition hover:bg-primary-600">
                                Kirim Pesanan
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
