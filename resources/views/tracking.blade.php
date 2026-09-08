<x-layouts.app :settings="[]">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <span class="text-white">Cek Resi</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">Cek Resi</h1>
            <p class="mt-4 text-white/60 text-lg max-w-2xl">Lacak pengiriman barang Anda secara real-time</p>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10">
                <livewire:tracking-widget />
            </div>
        </div>
    </section>

</x-layouts.app>
