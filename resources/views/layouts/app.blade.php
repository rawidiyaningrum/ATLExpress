<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings['company_name'] ?? 'ATL Express' }} - {{ $settings['company_tagline'] ?? 'Cargo & Logistics' }}</title>
    <meta name="description" content="ATL Express - Solusi cargo dan logistik terpercaya di Indonesia">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">

    <header class="bg-primary text-white sticky top-0 z-50 shadow-lg" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="ATL Express" class="w-10 h-10 rounded-lg object-contain">
                    <div>
                        <span class="text-xl font-bold tracking-tight">ATL Express</span>
                        <span class="block text-xs text-gold font-medium -mt-0.5">Cargo & Logistics</span>
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('home') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Beranda</a>
                    <a href="{{ route('about') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('about') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Tentang Kami</a>
                    <a href="{{ route('services') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('services') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Layanan</a>
                    <a href="{{ route('tracking') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('tracking') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Cek Resi</a>
                    <a href="{{ route('posts.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('posts.*') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Berita</a>
                    <a href="{{ route('contact') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('contact') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Kontak</a>
                    <a href="{{ route('contact') }}" class="ml-4 inline-flex items-center gap-2 bg-gold text-primary-500 px-6 py-2.5 rounded-full text-sm font-bold transition hover:bg-gold-300 shadow-lg shadow-gold/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Hubungi Kami
                    </a>
                </nav>

                <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 rounded-lg hover:bg-white/10">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="lg:hidden bg-primary-600 border-t border-white/10">
            <div class="px-4 py-4 space-y-1">
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('home') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Beranda</a>
                <a href="{{ route('about') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('about') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Tentang Kami</a>
                <a href="{{ route('services') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('services') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Layanan</a>
                <a href="{{ route('tracking') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('tracking') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Cek Resi</a>
                <a href="{{ route('posts.index') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('posts.*') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Berita</a>
                <a href="{{ route('contact') }}" class="block px-4 py-3 rounded-lg text-sm font-medium transition hover:bg-white/10 {{ request()->routeIs('contact') ? 'bg-white/10 text-gold' : 'text-white/90' }}">Kontak</a>
                <a href="{{ route('contact') }}" class="block mt-3 text-center bg-gold text-primary-500 px-6 py-3 rounded-full text-sm font-bold">Hubungi Kami</a>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-primary text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('images/logo.png') }}" alt="ATL Express" class="w-10 h-10 rounded-lg object-contain">
                        <div>
                            <span class="text-xl font-bold">ATL Express</span>
                            <span class="block text-xs text-gold font-medium -mt-0.5">Cargo & Logistics</span>
                        </div>
                    </div>
                    <p class="text-white/60 text-sm leading-relaxed mb-6">Solusi cargo dan logistik terpercaya untuk pengiriman barang ke seluruh Indonesia dengan aman dan tepat waktu.</p>
                    <div class="flex gap-3">
                        <a href="{{ $settings['facebook'] ?? '#' }}" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-gold hover:text-primary transition" aria-label="Facebook">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="{{ $settings['instagram'] ?? '#' }}" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-gold hover:text-primary transition" aria-label="Instagram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                        </a>
                        <a href="{{ $settings['youtube'] ?? '#' }}" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-gold hover:text-primary transition" aria-label="YouTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Tautan Cepat</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('home') }}" class="text-white/60 hover:text-gold transition text-sm">Beranda</a></li>
                        <li><a href="{{ route('about') }}" class="text-white/60 hover:text-gold transition text-sm">Tentang Kami</a></li>
                        <li><a href="{{ route('services') }}" class="text-white/60 hover:text-gold transition text-sm">Layanan</a></li>
                        <li><a href="{{ route('tracking') }}" class="text-white/60 hover:text-gold transition text-sm">Cek Resi</a></li>
                        <li><a href="{{ route('tariff') }}" class="text-white/60 hover:text-gold transition text-sm">Cek Tarif</a></li>
                        <li><a href="{{ route('posts.index') }}" class="text-white/60 hover:text-gold transition text-sm">Berita</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Layanan</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('service.detail', 'pengiriman-darat') }}" class="text-white/60 hover:text-gold transition text-sm">Pengiriman Darat</a></li>
                        <li><a href="{{ route('service.detail', 'pengiriman-laut') }}" class="text-white/60 hover:text-gold transition text-sm">Pengiriman Laut</a></li>
                        <li><a href="{{ route('service.detail', 'pengiriman-udara') }}" class="text-white/60 hover:text-gold transition text-sm">Pengiriman Udara</a></li>
                        <li><a href="{{ route('service.detail', 'pengiriman-express') }}" class="text-white/60 hover:text-gold transition text-sm">Pengiriman Express</a></li>
                        <li><a href="{{ route('tariff') }}" class="text-white/60 hover:text-gold transition text-sm">Cek Tarif</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Hubungi Kami</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gold mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-white/60 text-sm">{{ $settings['address'] ?? 'Jl. Contoh No. 123, Jakarta, Indonesia' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span class="text-white/60 text-sm">{{ $settings['phone'] ?? '+62 21 1234 5678' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="text-white/60 text-sm">{{ $settings['email'] ?? 'info@atlexpress.co.id' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gold shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            <span class="text-white/60 text-sm">{{ $settings['whatsapp'] ?? '+62 812 3456 7890' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-white/40 text-sm">&copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'ATL Express' }}. Hak cipta dilindungi.</p>
                <div class="flex gap-6">
                    <a href="#" class="text-white/40 hover:text-white text-sm transition">Kebijakan Privasi</a>
                    <a href="#" class="text-white/40 hover:text-white text-sm transition">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
