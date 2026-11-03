<x-layouts.app :settings="$settings" :seo="$seo">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <span class="text-white">Cek Tarif</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">Cek Tarif</h1>
            <p class="mt-4 text-white/60 text-lg max-w-2xl">Cek tarif pengiriman ke berbagai tujuan</p>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 md:p-10">
                <livewire:tariff-widget />
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-extrabold text-primary mb-4">Cara Cek Tarif Pengiriman Cargo</h2>
            <p class="text-gray-600 leading-relaxed mb-6">
                Halaman Cek Tarif membantu Anda memperkirakan biaya pengiriman sebelum melakukan pemesanan.
                Estimasi dihitung berdasarkan kota asal, kota tujuan, dan berat barang, sehingga Anda dapat
                merencanakan anggaran logistik dengan lebih mudah dan transparan.
            </p>
            <ol class="space-y-2 text-gray-600 list-decimal list-inside">
                <li>Pilih kota asal dan kota tujuan pengiriman.</li>
                <li>Masukkan berat barang dalam kilogram.</li>
                <li>Sistem menampilkan estimasi tarif secara otomatis.</li>
                <li>Hubungi tim kami untuk konfirmasi tarif final dan pemesanan.</li>
            </ol>

            <h2 class="text-2xl font-extrabold text-primary mt-12 mb-6">Pertanyaan yang Sering Diajukan</h2>
            <div class="space-y-4">
                @foreach($faq as $item)
                    <details class="group bg-gray-50 rounded-xl border border-gray-100 p-5">
                        <summary class="cursor-pointer font-bold text-primary list-none flex items-center justify-between gap-4">
                            {{ $item['q'] }}
                            <svg class="w-5 h-5 shrink-0 text-gold-600 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <p class="text-gray-600 mt-3 text-sm leading-relaxed">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.app>
