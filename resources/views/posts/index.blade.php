<x-layouts.app :settings="[]">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <span class="text-white">Berita</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold">Berita & Artikel</h1>
            <p class="mt-4 text-white/60 text-lg max-w-2xl">Update terbaru seputar ATL Express dan industri logistik</p>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($posts as $post)
                    <a href="{{ route('posts.show', $post->slug) }}" class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition border border-gray-100">
                        <div class="h-48 bg-gradient-to-br from-primary/20 to-gold/20 flex items-center justify-center">
                            @if($post->image)
                                <img src="{{ $post->image }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
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
                            <h2 class="font-bold text-primary group-hover:text-gold-600 transition mb-2 line-clamp-2 text-lg">{{ $post->title }}</h2>
                            <p class="text-gray-500 text-sm line-clamp-3">{{ Str::limit(strip_tags($post->content), 150) }}</p>
                            <span class="mt-4 inline-flex items-center gap-1 text-gold-600 font-bold text-sm group-hover:gap-2 transition-all">
                                Baca Artikel
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="md:col-span-2 lg:col-span-3 text-center py-20">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        <p class="text-gray-500">Belum ada artikel yang tersedia.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        </div>
    </section>

</x-layouts.app>
