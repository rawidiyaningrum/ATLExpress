<div>
    <form wire:submit="track" class="space-y-4">
        @error('tracking_number')
            <p class="text-accent text-sm font-medium">{{ $message }}</p>
        @enderror

        <div>
            <label for="tracking_number" class="block text-sm font-bold text-gray-700 mb-2">Nomor Resi</label>
            <div class="flex gap-2">
                <input
                    type="text"
                    id="tracking_number"
                    wire:model="tracking_number"
                    placeholder="Masukkan nomor resi Anda"
                    class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-gold focus:border-gold transition"
                >
                <button type="submit" class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl text-sm font-bold transition hover:bg-primary-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Lacak
                </button>
            </div>
        </div>
    </form>

    @if($error)
        <div class="mt-6 p-4 bg-red-50 border border-accent/20 text-accent rounded-xl text-sm font-medium">
            {{ $error }}
        </div>
    @endif

    @if($result)
        <div class="mt-8 border-t border-gray-100 pt-6">
            <div class="bg-primary/5 rounded-xl p-6 mb-6">
                <div class="flex flex-wrap justify-between gap-6">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Nomor Resi</p>
                        <p class="font-bold text-primary">{{ $result['tracking_number'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Status</p>
                        <span class="inline-block bg-gold/20 text-gold-600 px-3 py-1 rounded-full text-xs font-bold">{{ strtoupper($result['status']) }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Pengirim</p>
                        <p class="font-semibold text-primary text-sm">{{ $result['sender_name'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Penerima</p>
                        <p class="font-semibold text-primary text-sm">{{ $result['receiver_name'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Rute</p>
                        <p class="font-semibold text-primary text-sm">{{ $result['origin'] }} → {{ $result['destination'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Berat</p>
                        <p class="font-semibold text-primary text-sm">{{ $result['weight'] }} kg</p>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-primary mb-4">Riwayat Pengiriman</h4>
                <div class="space-y-0">
                    @foreach($result['logs'] as $index => $log)
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="w-4 h-4 rounded-full {{ $index === 0 ? 'bg-gold ring-4 ring-gold/20' : 'bg-primary/20' }}"></div>
                                @if(!$loop->last)
                                    <div class="w-px flex-1 bg-gray-200"></div>
                                @endif
                            </div>
                            <div class="pb-6">
                                <p class="font-semibold text-gray-800 text-sm">{{ $log['status_description'] }}</p>
                                <p class="text-gray-500 text-xs mt-1">{{ $log['location'] }} · {{ \Carbon\Carbon::parse($log['timestamp'])->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
