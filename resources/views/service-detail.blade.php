<x-layouts.app :settings="[]">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <a href="{{ route('services') }}" class="hover:text-gold transition">Layanan</a>
                <span class="mx-2">/</span>
                <span class="text-white">{{ $service->title }}</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">{{ $service->title }}</h1>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2">
                    @if($service->image)
                        <img src="{{ $service->image }}" alt="{{ $service->title }}" class="w-full h-80 object-cover rounded-2xl mb-8">
                    @else
                        <div class="w-full h-80 bg-gradient-to-br from-primary/20 to-gold/20 rounded-2xl flex items-center justify-center mb-8">
                            <svg class="w-24 h-24 text-primary/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    @endif
                    <div class="prose prose-lg max-w-none prose-headings:text-primary prose-a:text-gold-600">
                        {!! $service->content !!}
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-2xl p-8 sticky top-28">
                        <h3 class="font-bold text-primary text-lg mb-4">Layanan Lainnya</h3>
                        <div class="space-y-3">
                            @php
                                $otherServices = \App\Models\Service::active()->where('id', '!=', $service->id)->ordered()->get();
                            @endphp
                            @forelse($otherServices as $other)
                                <a href="{{ route('service.detail', $other->slug) }}" class="block px-4 py-3 bg-white rounded-xl text-sm font-medium text-gray-700 hover:text-gold-600 hover:bg-gold/5 transition border border-gray-100">
                                    {{ $other->title }}
                                </a>
                            @empty
                                <p class="text-gray-400 text-sm">Belum ada layanan lainnya.</p>
                            @endforelse
                        </div>

                        <div class="mt-8 p-6 bg-primary rounded-xl text-white">
                            <h4 class="font-bold mb-2">Butuh Bantuan?</h4>
                            <p class="text-white/70 text-sm mb-4">Hubungi kami untuk informasi lebih lanjut.</p>
                            <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 bg-gold text-primary-500 px-6 py-2.5 rounded-full text-sm font-bold transition hover:bg-gold-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Hubungi Kami
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-layouts.app>
