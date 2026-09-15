<x-layouts.app :settings="$settings" :seo="$seo">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <span class="text-white">Layanan</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">Layanan Kami</h1>
            <p class="mt-4 text-white/60 text-lg max-w-2xl">Pilihan layanan pengiriman terbaik untuk kebutuhan logistik Anda</p>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($services as $service)
                    <a href="{{ route('service.detail', $service->slug) }}" class="group bg-gray-50 rounded-2xl overflow-hidden hover:shadow-xl transition border border-transparent hover:border-gold/30">
                        <div class="h-48 bg-gradient-to-br from-primary/20 to-gold/20 flex items-center justify-center">
                            @if($service->image)
                                <img src="{{ $service->image }}" alt="{{ $service->title }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-16 h-16 text-primary/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            @endif
                        </div>
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-primary mb-3 group-hover:text-gold-600 transition">{{ $service->title }}</h3>
                            <p class="text-gray-500 text-sm leading-relaxed mb-4">{{ $service->short_description }}</p>
                            <span class="inline-flex items-center gap-1 text-gold-600 font-bold text-sm group-hover:gap-2 transition-all">
                                Pelajari Lebih Lanjut
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @empty
                    @foreach([
                        ['title' => 'Pengiriman Darat', 'desc' => 'Layanan pengiriman barang melalui jalur darat ke seluruh wilayah Indonesia dengan armada truk dan kendaraan yang terawat.'],
                        ['title' => 'Pengiriman Laut', 'desc' => 'Layanan pengiriman barang melalui jalur laut untuk pengiriman dalam jumlah besar ke berbagai pulau.'],
                        ['title' => 'Pengiriman Udara', 'desc' => 'Layanan pengiriman barang melalui jalur udara untuk pengiriman yang membutuhkan kecepatan tinggi.'],
                        ['title' => 'Pengiriman Express', 'desc' => 'Layanan pengiriman cepat untuk barang urgent yang harus sampai dalam waktu singkat.'],
                        ['title' => 'Warehousing', 'desc' => 'Layanan penyimpanan barang di gudang kami yang aman dan terkontrol.'],
                        ['title' => 'Freight Forwarding', 'desc' => 'Layanan forwarding untuk pengiriman internasional dan domestik skala besar.'],
                    ] as $item)
                        <div class="bg-gray-50 rounded-2xl overflow-hidden border border-gray-100">
                            <div class="h-48 bg-gradient-to-br from-primary/10 to-gold/10 flex items-center justify-center">
                                <svg class="w-16 h-16 text-primary/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <div class="p-8">
                                <h3 class="text-xl font-bold text-primary mb-3">{{ $item['title'] }}</h3>
                                <p class="text-gray-500 text-sm leading-relaxed">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

</x-layouts.app>
