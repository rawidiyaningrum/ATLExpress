<x-layouts.app :settings="$settings" :seo="$seo">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <span class="text-white">Tentang Kami</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">Tentang Kami</h1>
            <p class="mt-4 text-white/60 text-lg max-w-2xl">Mengenal lebih dekat ATL Express, partner logistik terpercaya Anda</p>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block bg-primary/10 text-primary text-sm font-bold px-4 py-1.5 rounded-full mb-4">Profil Perusahaan</span>
                    <h2 class="text-3xl font-extrabold text-primary mb-6">ATL Express</h2>
                    <p class="text-gray-600 leading-relaxed mb-6">
                        {{ $settings['about_text'] ?? 'ATL Express adalah perusahaan cargo dan logistik yang berkomitmen untuk memberikan layanan pengiriman barang terbaik di seluruh Indonesia. Dengan jaringan yang luas dan armada yang handal, kami siap menjadi solusi logistik terpercaya untuk bisnis Anda.' }}
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        Kami percaya bahwa setiap pengiriman memiliki nilai penting, oleh karena itu kami selalu berusaha memberikan pelayanan terbaik dengan standar operasional yang tinggi untuk memastikan barang Anda sampai dengan aman dan tepat waktu.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-2xl p-8 text-center">
                        <div class="text-4xl font-extrabold text-primary mb-2">10+</div>
                        <div class="text-gray-500 text-sm">Tahun Pengalaman</div>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-8 text-center">
                        <div class="text-4xl font-extrabold text-primary mb-2">50+</div>
                        <div class="text-gray-500 text-sm">Kota Tujuan</div>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-8 text-center">
                        <div class="text-4xl font-extrabold text-primary mb-2">100+</div>
                        <div class="text-gray-500 text-sm">Armada Unit</div>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-8 text-center">
                        <div class="text-4xl font-extrabold text-primary mb-2">5000+</div>
                        <div class="text-gray-500 text-sm">Pelanggan Puas</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12">
                <div class="bg-white rounded-2xl p-10 shadow-sm border border-gray-100">
                    <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-primary mb-4">Visi</h3>
                    <p class="text-gray-600 leading-relaxed">
                        {{ $settings['vision'] ?? 'Menjadi perusahaan logistik terdepan di Indonesia yang memberikan layanan berkualitas tinggi, inovatif, dan berkelanjutan untuk mendukung pertumbuhan ekonomi nasional.' }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl p-10 shadow-sm border border-gray-100">
                    <div class="w-14 h-14 bg-gold/20 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-2xl font-extrabold text-primary mb-4">Misi</h3>
                    <p class="text-gray-600 leading-relaxed">
                        {{ $settings['mission'] ?? 'Memberikan layanan logistik terbaik melalui inovasi teknologi, jaringan distribusi yang luas, dan tim profesional yang berkomitmen untuk kepuasan pelanggan.' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block bg-primary/10 text-primary text-sm font-bold px-4 py-1.5 rounded-full mb-4">Keunggulan</span>
                <h2 class="text-3xl font-extrabold text-primary">Keunggulan Armada Kami</h2>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Armada Terawat</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Semua armada kami dirawat secara berkala untuk memastikan keamanan dan kenyamanan pengiriman.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-gold/20 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Tepat Waktu</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Komitmen kami untuk mengirim barang tepat waktu sesuai jadwal yang telah ditentukan.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Harga Kompetitif</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Kami menawarkan tarif pengiriman yang kompetitif tanpa mengurangi kualitas layanan.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Tracking Real-time</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Lacak pengiriman Anda secara real-time melalui sistem kami yang terintegrasi.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-gold/20 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Tim Profesional</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Didukung oleh tim profesional yang berpengalaman di bidang logistik.</p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-8 border border-transparent hover:border-gold/30 hover:shadow-lg transition">
                    <div class="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-primary text-lg mb-2">Jangkauan Luas</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Jaringan pengiriman ke seluruh wilayah Indonesia dari Sabang sampai Merauke.</p>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
