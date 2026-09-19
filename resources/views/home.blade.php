<x-layouts.app :settings="$settings" :seo="$seo">

    <section class="text-white relative overflow-hidden bg-cover bg-center bg-no-repeat" style="background-image: url('https://cdn.pixabay.com/photo/2016/01/25/15/11/euro-pallets-1160806_1280.jpg');">
        <div class="absolute inset-0 bg-primary/75"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl lg:text-5xl font-extrabold leading-tight mb-6">
                        {{ $settings['hero_title'] ?? 'Solusi Cargo & Logistik Terpercaya' }}
                    </h1>
                    <p class="text-lg text-white/70 mb-8 leading-relaxed">
                        {{ $settings['hero_subtitle'] ?? 'Kirim barang Anda ke seluruh Indonesia dengan aman, cepat, dan harga terjangkau. ATL Express siap melayani kebutuhan logistik Anda.' }}
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 bg-gold text-primary-500 px-8 py-3.5 rounded-full font-bold text-sm transition hover:bg-gold-300 shadow-lg shadow-gold/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            Hubungi Kami
                        </a>
                        <a href="{{ route('services') }}" class="inline-flex items-center gap-2 border-2 border-white/30 text-white px-8 py-3.5 rounded-full font-bold text-sm transition hover:bg-white/10">
                            Lihat Layanan
                        </a>
                    </div>
                </div>

                <div x-data="{ activeTab: 'tracking' }" class="bg-white rounded-2xl shadow-2xl p-8">
                    <div class="flex gap-2 mb-6 bg-gray-100 rounded-xl p-1">
                        <button @click="activeTab = 'tracking'" :class="activeTab === 'tracking' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 py-2.5 px-4 rounded-lg text-sm font-bold transition">Cek Resi</button>
                        <button @click="activeTab = 'tariff'" :class="activeTab === 'tariff' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="flex-1 py-2.5 px-4 rounded-lg text-sm font-bold transition">Cek Tarif</button>
                    </div>

                    <div x-show="activeTab === 'tracking'">
                        <livewire:tracking-widget />
                    </div>
                    <div x-show="activeTab === 'tariff'">
                        <livewire:tariff-widget />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block bg-primary/10 text-primary text-sm font-bold px-4 py-1.5 rounded-full mb-4">Layanan Kami</span>
                <h2 class="text-3xl lg:text-4xl font-extrabold text-primary">Solusi Logistik Lengkap</h2>
                <p class="mt-3 text-gray-500 max-w-2xl mx-auto">Kami menyediakan berbagai layanan pengiriman untuk memenuhi kebutuhan bisnis dan personal Anda</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @forelse($services as $service)
                    <a href="{{ route('service.detail', $service->slug) }}" class="group bg-gray-50 rounded-2xl p-8 transition hover:shadow-xl hover:-translate-y-1 border border-transparent hover:border-gold/30">
                        <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-gold/20 transition">
                            <svg class="w-7 h-7 text-primary group-hover:text-gold transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-primary mb-3">{{ $service->title }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed mb-4">{{ $service->short_description }}</p>
                        <span class="inline-flex items-center gap-1 text-gold-600 font-bold text-sm group-hover:gap-2 transition-all">
                            Selengkapnya
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                @empty
                    @foreach(['Pengiriman Darat', 'Pengiriman Laut', 'Pengiriman Udara', 'Pengiriman Express'] as $i => $title)
                        <div class="bg-gray-50 rounded-2xl p-8 border border-transparent">
                            <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($i === 0)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1h2m10 1l2-1V8a1 1 0 00-1-1h-4"/>
                                    @elseif($i === 1)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    @elseif($i === 2)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    @endif
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-primary mb-3">{{ $title }}</h3>
                            <p class="text-gray-500 text-sm leading-relaxed">Layanan pengiriman {{ strtolower($title) }} untuk seluruh wilayah Indonesia.</p>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="py-16 bg-primary relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-10 w-64 h-64 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-8 bg-white/5 rounded-2xl border border-white/10">
                    <svg class="w-12 h-12 text-gold mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <h3 class="text-gold font-bold mb-2">Telepon</h3>
                    <p class="text-white/70 text-sm">{{ $settings['phone'] ?? '+62 21 1234 5678' }}</p>
                </div>
                <div class="text-center p-8 bg-white/5 rounded-2xl border border-white/10">
                    <svg class="w-12 h-12 text-gold mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <h3 class="text-gold font-bold mb-2">Email</h3>
                    <p class="text-white/70 text-sm">{{ $settings['email'] ?? 'info@atlexpress.co.id' }}</p>
                </div>
                <div class="text-center p-8 bg-white/5 rounded-2xl border border-white/10">
                    <svg class="w-12 h-12 text-gold mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <h3 class="text-gold font-bold mb-2">Website</h3>
                    <p class="text-white/70 text-sm">www.atlexpress.co.id</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block bg-gold/20 text-primary text-sm font-bold px-4 py-1.5 rounded-full mb-4">Berita Terkini</span>
                <h2 class="text-3xl lg:text-4xl font-extrabold text-primary">Informasi & Artikel</h2>
                <p class="mt-3 text-gray-500 max-w-2xl mx-auto">Ikuti berita dan update terbaru dari ATL Express</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                @forelse($posts as $post)
                    <a href="{{ route('posts.show', $post->slug) }}" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition border border-gray-100">
                        <div class="h-48 bg-gradient-to-br from-primary/20 to-gold/20 flex items-center justify-center">
                            @if($post->image)
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-16 h-16 text-primary/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                            @endif
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                @if($post->category)
                                    <span class="inline-block bg-primary/10 text-primary text-xs font-bold px-2.5 py-1 rounded-full">{{ $post->category->name }}</span>
                                @endif
                                <span class="text-gray-400 text-xs">{{ $post->published_at->format('d M Y') }}</span>
                            </div>
                            <h3 class="font-bold text-primary group-hover:text-gold-600 transition mb-2 line-clamp-2">{{ $post->title }}</h3>
                            <p class="text-gray-500 text-sm line-clamp-2">{{ Str::limit(strip_tags($post->content), 100) }}</p>
                        </div>
                    </a>
                @empty
                    @foreach([1, 2, 3] as $i)
                        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                            <div class="h-48 bg-gradient-to-br from-primary/10 to-gold/10 flex items-center justify-center">
                                <svg class="w-16 h-16 text-primary/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                            </div>
                            <div class="p-6">
                                <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
                                <div class="h-5 bg-gray-200 rounded w-3/4 mb-2"></div>
                                <div class="h-4 bg-gray-100 rounded w-full"></div>
                            </div>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

</x-layouts.app>
