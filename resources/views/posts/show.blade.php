<x-layouts.app :settings="[]">

    <section class="bg-primary text-white py-20 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-gold rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="text-sm text-white/50 mb-6">
                <a href="{{ route('home') }}" class="hover:text-gold transition">Beranda</a>
                <span class="mx-2">/</span>
                <a href="{{ route('posts.index') }}" class="hover:text-gold transition">Berita</a>
                <span class="mx-2">/</span>
                <span class="text-white">{{ Str::limit($post->title, 40) }}</span>
            </nav>
            <h1 class="text-4xl lg:text-5xl font-extrabold max-w-4xl">{{ $post->title }}</h1>
            <div class="flex items-center gap-4 mt-6">
                @if($post->category)
                    <span class="inline-block bg-gold/20 text-gold text-xs font-bold px-3 py-1.5 rounded-full">{{ $post->category->name }}</span>
                @endif
                <span class="text-white/60 text-sm">{{ $post->published_at->format('d M Y') }}</span>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($post->image)
                <img src="{{ $post->image }}" alt="{{ $post->title }}" class="w-full h-96 object-cover rounded-2xl mb-10">
            @else
                <div class="w-full h-72 bg-gradient-to-br from-primary/20 to-gold/20 rounded-2xl flex items-center justify-center mb-10">
                    <svg class="w-20 h-20 text-primary/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
            @endif

            <article class="prose prose-lg max-w-none prose-headings:text-primary">
                {!! $post->content !!}
            </article>

            <div class="mt-12 pt-8 border-t border-gray-200">
                <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:gap-3 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                    Kembali ke Berita
                </a>
            </div>
        </div>
    </section>

</x-layouts.app>
