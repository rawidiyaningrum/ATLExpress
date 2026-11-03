<x-layouts.app :settings="$settings" :seo="$seo">

    {{-- Hero Section --}}
    <section class="bg-primary text-white py-16 lg:py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-gold-400 rounded-full blur-2xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/60 mb-6 flex items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="text-white/40">/</span>
                <span class="text-white font-medium">Lokasi & Media Sosial</span>
            </nav>
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 text-gold text-xs font-semibold uppercase tracking-wider mb-4 border border-white/10">
                    <svg class="w-4 h-4 text-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    Kantor Pusat & Hub Logistik Resmi
                </div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
                    Kunjungi Kantor & Ikuti Aktivitas Kami
                </h1>
                <p class="mt-4 text-white/70 text-base sm:text-lg leading-relaxed">
                    Temukan lokasi kantor operasional ATL Express melalui Google Maps, pantau ulasan di Google Business Profile, serta saksikan dokumentasi pengiriman kargo harian kami di Instagram dan TikTok.
                </p>
            </div>
        </div>
    </section>

    {{-- Section 1: Google Business Profile & Google Maps --}}
    <section class="py-16 bg-gray-50 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-primary">Lokasi & Google Business Profile</h2>
                <p class="mt-2 text-gray-600 text-sm sm:text-base">Akses rute navigasi akurat menuju hub operasional kami dan lihat ulasan terverifikasi dari para pelanggan.</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-stretch">
                {{-- Card Google Business Profile --}}
                <div class="lg:col-span-5 flex flex-col justify-between bg-white rounded-2xl shadow-xl p-6 sm:p-8 border border-gray-100">
                    <div>
                        {{-- GBP Header --}}
                        <div class="flex items-start justify-between gap-4 pb-6 border-b border-gray-100">
                            <div>
                                <div class="flex items-center gap-2 text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    Terverifikasi di Google
                                </div>
                                <h3 class="text-xl sm:text-2xl font-black text-gray-900 leading-snug">
                                    {{ $settings['company_name'] ?: 'ATL Express' }}
                                </h3>
                                <p class="text-xs text-gray-500 font-medium">Jasa Pengiriman Kargo & Logistik Domestik</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                                <svg class="w-7 h-7 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Rating Google --}}
                        <div class="py-5 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-black text-gray-900">5.0</span>
                                <div>
                                    <div class="flex items-center text-amber-400">
                                        @for($i = 0; $i < 5; $i++)
                                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        @endfor
                                    </div>
                                    <span class="text-xs text-gray-500 font-medium">Berdasarkan ulasan terverifikasi di Google Maps</span>
                                </div>
                            </div>
                        </div>

                        {{-- Info Detail Lokasi & Kontak --}}
                        <div class="py-5 space-y-4 text-sm">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0 text-primary mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Alamat Hub / Kantor</span>
                                    <span class="text-gray-800 font-semibold">{{ $settings['address'] ?: 'Jakarta, Indonesia' }}</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center shrink-0 text-green-600 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Jam Operasional</span>
                                    <span class="text-gray-800 font-semibold">Senin – Sabtu: 08.00 – 18.00 WIB</span>
                                    <span class="block text-xs text-green-600 font-medium">Layanan Konsultasi & Tracking Online: 24 Jam</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gold/20 flex items-center justify-center shrink-0 text-gold-600 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak Langsung</span>
                                    <span class="text-gray-800 font-semibold">{{ $settings['phone'] ?: '+62 857-7707-9581' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $settings['email'] ?: 'cs.atlexpress@gmail.com' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Fasilitas Hub --}}
                        <div class="pt-4 pb-2 border-t border-gray-100">
                            <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Fasilitas Hub Logistik</span>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">✓ Area Bongkar Muat Fuso</span>
                                <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">✓ Timbangan Digital Presisi</span>
                                <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">✓ CCTV Keamanan 24 Jam</span>
                                <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">✓ Layanan Free Pick-Up</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Aksi GBP --}}
                    <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                        <a href="https://maps.google.com/?q={{ urlencode(($settings['company_name'] ?: 'ATL Express') . ' ' . ($settings['address'] ?: 'Jakarta')) }}" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-2 bg-[#4285F4] hover:bg-blue-600 text-white font-bold text-sm px-5 py-3 rounded-xl transition shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/></svg>
                            Petunjuk Arah (Maps)
                        </a>
                        <a href="https://search.google.com/local/writereview?placeid=ChIJ" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-sm px-5 py-3 rounded-xl transition">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Beri Ulasan
                        </a>
                    </div>
                </div>

                {{-- Embed Google Maps Interaktif --}}
                <div class="lg:col-span-7 bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 flex flex-col min-h-[480px]">
                    <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm font-bold text-gray-700">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            Peta Interaktif Hub Operasional
                        </div>
                        <span class="text-xs text-gray-500 font-medium">Buka layar penuh dengan zoom</span>
                    </div>
                    <div class="relative flex-1 w-full h-full min-h-[420px]">
                        <iframe
                            src="{{ $settings['google_maps_embed_url'] ?: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126920.28628095436!2d106.759478!3d-6.229728!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e94d436811%3A0x2cb6e0370b3b4!2sJakarta!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid' }}"
                            width="100%"
                            height="100%"
                            style="border:0; min-height: 420px;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Peta Lokasi ATL Express"
                            class="w-full h-full absolute inset-0">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Section 2: Media Sosial Resmi - Widget Instagram & TikTok --}}
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gold/10 text-gold-600 text-xs font-bold uppercase tracking-wider mb-2">
                    Dokumentasi Nyata & Aktivitas
                </div>
                <h2 class="text-3xl font-extrabold text-primary">Media Sosial Resmi Kami</h2>
                <p class="mt-2 text-gray-600 text-sm sm:text-base">Ikuti keseharian armada, tips pengepakan kargo aman, dan testimoni keberangkatan kargo di Instagram & TikTok resmi kami.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-10 items-start">
                {{-- WIDGET INSTAGRAM --}}
                <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden shadow-lg transition hover:shadow-xl">
                    {{-- Header Profil Instagram --}}
                    <div class="p-6 bg-white border-b border-gray-100 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full p-0.5 bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 shrink-0">
                                <div class="w-full h-full rounded-full bg-white p-0.5 flex items-center justify-center overflow-hidden">
                                    <img src="{{ asset('images/logo-192.png') }}" alt="Instagram ATL Express" class="w-full h-full object-contain rounded-full">
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h3 class="font-extrabold text-gray-900 text-base">@atlexpress</h3>
                                    <svg class="w-4 h-4 text-blue-500 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">ATL Express | Kargo & Logistik Nusantara</p>
                                <span class="text-[11px] text-gray-400">Darat • Laut • Udara • Project Cargo</span>
                            </div>
                        </div>
                        <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 via-rose-600 to-amber-500 text-white font-bold text-xs shadow-md hover:opacity-95 transition shrink-0">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                            Ikuti
                        </a>
                    </div>

                    {{-- Grid Postingan Instagram --}}
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-3">
                            {{-- Post 1 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-primary/20 block">
                                <img src="{{ asset('images/hero.jpg') }}" alt="Dokumentasi Armada ATL Express" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute inset-0 bg-primary/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2 text-white text-xs font-bold">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/></svg>
                                    <span>Muat Fuso</span>
                                </div>
                            </a>

                            {{-- Post 2 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-gradient-to-br from-primary to-primary-700 flex flex-col justify-end p-3 text-white">
                                <div class="absolute top-2 right-2">
                                    <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <span class="text-[10px] font-bold text-gold uppercase tracking-wider">Cargo Udara</span>
                                <span class="text-xs font-extrabold leading-tight">Pengiriman Cepat 1 Hari Sampai</span>
                            </a>

                            {{-- Post 3 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-gradient-to-br from-gray-900 to-primary-800 flex flex-col justify-end p-3 text-white">
                                <div class="absolute top-2 right-2">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <span class="text-[10px] font-bold text-blue-300 uppercase tracking-wider">Project Cargo</span>
                                <span class="text-xs font-extrabold leading-tight">Kirim Mesin & Material Pabrik</span>
                            </a>

                            {{-- Post 4 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-gradient-to-br from-gold/30 to-gold/80 flex flex-col justify-end p-3 text-primary">
                                <span class="text-[10px] font-bold uppercase tracking-wider">Tips Packing</span>
                                <span class="text-xs font-black leading-tight">Cara Packing Kayu Standar Aman</span>
                            </a>

                            {{-- Post 5 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-gradient-to-br from-blue-900 to-primary flex flex-col justify-end p-3 text-white">
                                <span class="text-[10px] font-bold text-gold uppercase tracking-wider">Rute Laut</span>
                                <span class="text-xs font-extrabold leading-tight">Kapal RoRo & Kontainer FCL/LCL</span>
                            </a>

                            {{-- Post 6 --}}
                            <a href="{{ $settings['instagram'] ?: 'https://instagram.com/atlexpress' }}" target="_blank" rel="noopener" class="group relative aspect-square rounded-xl overflow-hidden bg-primary flex flex-col items-center justify-center p-3 text-center text-white hover:bg-primary-600 transition">
                                <svg class="w-6 h-6 text-gold mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="text-xs font-bold">Lihat Semua di IG</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- WIDGET TIKTOK --}}
                <div class="bg-gray-50 rounded-2xl border border-gray-200 overflow-hidden shadow-lg transition hover:shadow-xl">
                    {{-- Header Profil TikTok --}}
                    <div class="p-6 bg-white border-b border-gray-100 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full p-0.5 bg-black shrink-0 flex items-center justify-center overflow-hidden">
                                <img src="{{ asset('images/logo-192.png') }}" alt="TikTok ATL Express" class="w-full h-full object-contain rounded-full">
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h3 class="font-extrabold text-gray-900 text-base">@atlexpress.id</h3>
                                    <span class="px-1.5 py-0.5 bg-black text-white text-[10px] font-bold rounded">Official</span>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">ATL Express Logistik</p>
                                <span class="text-[11px] text-gray-400">Video Operasional & Edukasi Cargo</span>
                            </div>
                        </div>
                        <a href="{{ $settings['tiktok'] ?: 'https://tiktok.com/@atlexpress' }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-black hover:bg-gray-800 text-white font-bold text-xs shadow-md transition shrink-0">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            Follow
                        </a>
                    </div>

                    {{-- Grid Video TikTok Card --}}
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            {{-- TikTok Video 1 --}}
                            <a href="{{ $settings['tiktok'] ?: 'https://tiktok.com/@atlexpress' }}" target="_blank" rel="noopener" class="group relative rounded-xl overflow-hidden bg-gray-900 aspect-[9/14] flex flex-col justify-between p-4 text-white shadow">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded bg-black/60 text-[10px] font-bold text-red-400 backdrop-blur flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                        Shorts
                                    </span>
                                    <div class="w-7 h-7 rounded-full bg-white/20 backdrop-blur flex items-center justify-center group-hover:scale-110 transition">
                                        <svg class="w-4 h-4 text-white fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold line-clamp-2 leading-snug group-hover:text-gold transition">
                                        Proses Muat Truk Fuso Wingbox Rute Jakarta - Surabaya 🚛
                                    </span>
                                    <span class="text-[11px] text-gray-400 mt-1 block">▶ 12.4K views</span>
                                </div>
                            </a>

                            {{-- TikTok Video 2 --}}
                            <a href="{{ $settings['tiktok'] ?: 'https://tiktok.com/@atlexpress' }}" target="_blank" rel="noopener" class="group relative rounded-xl overflow-hidden bg-gradient-to-b from-gray-800 to-black aspect-[9/14] flex flex-col justify-between p-4 text-white shadow">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded bg-black/60 text-[10px] font-bold text-blue-400 backdrop-blur flex items-center gap-1">
                                        Edukasi
                                    </span>
                                    <div class="w-7 h-7 rounded-full bg-white/20 backdrop-blur flex items-center justify-center group-hover:scale-110 transition">
                                        <svg class="w-4 h-4 text-white fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                                <div>
                                    <span class="block text-xs font-extrabold line-clamp-2 leading-snug group-hover:text-gold transition">
                                        Perbedaan Hitungan Berat Aktual vs Volume Kargo 📦
                                    </span>
                                    <span class="text-[11px] text-gray-400 mt-1 block">▶ 28.7K views</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Section 3: Call-To-Action Layanan Penjemputan --}}
    <section class="py-16 bg-primary text-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black mb-4">Ingin Barang Anda Dijemput di Lokasi?</h2>
            <p class="text-white/70 max-w-2xl mx-auto text-base mb-8">
                Kami menyediakan fasilitas <strong>Free Pick-up (Penjemputan Gratis)</strong> untuk area Jabodetabek. Hubungi customer service operasional kami sekarang.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['whatsapp'] ?: '6285777079581') }}?text={{ urlencode('Halo ATL Express, saya ingin konsultasi pengiriman barang dan jadwal pick-up.') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 bg-gold text-primary font-black px-8 py-4 rounded-full text-sm hover:bg-gold-300 transition shadow-lg shadow-gold/20">
                    <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    Jadwalkan Penjemputan (WhatsApp)
                </a>
                <a href="{{ route('tariff') }}" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-bold px-8 py-4 rounded-full text-sm transition border border-white/20">
                    Cek Tarif Pengiriman
                </a>
            </div>
        </div>
    </section>

</x-layouts.app>

