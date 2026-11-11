<x-filament-panels::page>
    {{-- Status Konfigurasi --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status Fitur</div>
            <div class="mt-1 text-lg font-extrabold {{ $enabled ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                {{ $enabled ? 'Aktif' : 'Nonaktif' }}
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $enabled ? 'Pengambilan data berjalan sesuai jadwal.' : 'Set THREADS_ENABLED=true untuk mengaktifkan.' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sumber Data</div>
            <div class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white">{{ $sourceLabel }}</div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                API resmi: {{ $apiConfigured ? 'terkonfigurasi' : 'belum' }} &middot;
                Scraping: {{ $scrapeConfigured ? 'terkonfigurasi' : 'belum' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kuota Query Hari Ini</div>
            <div class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white">
                {{ number_format($quotaRemaining, 0, ',', '.') }} <span class="text-sm font-medium text-gray-500">/ {{ number_format($quotaCap, 0, ',', '.') }}</span>
            </div>
            <p class="mt-1 text-xs {{ $quotaRemaining > 0 ? 'text-gray-500 dark:text-gray-400' : 'text-danger-600 dark:text-danger-400' }}">
                Terpakai: {{ number_format($quotaUsed, 0, ',', '.') }} query
            </p>
        </div>
    </div>

    {{-- Ringkasan Data --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">Keyword Aktif</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($activeKeywords, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">dari {{ number_format($totalKeywords, 0, ',', '.') }} total</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Postingan</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalPosts, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($todayPosts, 0, ',', '.') }} hari ini</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">Belum Dihubungi</div>
            <div class="text-2xl font-black {{ $newPosts > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-900 dark:text-white' }}">{{ number_format($newPosts, 0, ',', '.') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">butuh tindak lanjut</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">Fetch Terakhir</div>
            <div class="text-sm font-bold text-gray-900 dark:text-white">
                {{ $lastRunAt ? \Illuminate\Support\Carbon::parse($lastRunAt)->format('d M Y H:i') : 'Belum pernah' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $lastRunAt ? \Illuminate\Support\Carbon::parse($lastRunAt)->diffForHumans() : 'Jalankan fetch pertama' }}
            </div>
        </div>
    </div>

    {{-- Saran Keyword AI --}}
    <x-filament::section
        heading="Saran Keyword dengan AI"
        description="Gunakan Gemini untuk mengusulkan variasi keyword. Keyword yang ditambahkan berstatus nonaktif sampai Anda mengaktifkannya."
    >
        @if (! $aiConfigured)
            <p class="text-sm text-danger-600 dark:text-danger-400">
                AI belum dikonfigurasi. Isi AI_API_KEY di .env server dan pastikan AI_ENABLED=true.
            </p>
        @else
            <div class="grid items-end gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tema (opsional)</label>
                    <input
                        type="text"
                        wire:model="seed"
                        placeholder="Contoh: pengiriman ke papua, pindahan rumah"
                        class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</label>
                    <input
                        type="number"
                        min="5"
                        max="40"
                        wire:model="suggestCount"
                        class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                    />
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button wire:click="suggestKeywords" icon="heroicon-m-sparkles" wire:loading.attr="disabled">
                    Buat Saran Keyword
                </x-filament::button>
            </div>

            @if (! empty($suggestions))
                <div class="mt-6">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ count($suggestions) }} kandidat keyword</span>
                        <div class="flex gap-2">
                            <x-filament::button size="sm" color="success" wire:click="storeSuggestions" icon="heroicon-m-plus">
                                Tambahkan Semua (Nonaktif)
                            </x-filament::button>
                            <x-filament::button size="sm" color="gray" wire:click="clearSuggestions" icon="heroicon-m-x-mark">
                                Bersihkan
                            </x-filament::button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($suggestions as $suggestion)
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                {{ $suggestion['keyword'] }}
                                <span class="text-[10px] uppercase text-gray-400">{{ $suggestion['mode'] }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>

    {{-- Petunjuk Operasional --}}
    <x-filament::section
        heading="Cara Kerja & Penjadwalan"
        description="Pengambilan data dijadwalkan otomatis tiap 30 menit, dan bisa dipicu manual lewat tombol Fetch Sekarang di atas."
    >
        <ul class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
            <li>Perintah manual dari terminal: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-700">php artisan threads:fetch</code> (tambahkan <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-700">--sync</code> untuk jalan langsung).</li>
            <li>Jadwal otomatis butuh <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-700">php artisan schedule:work</code> (atau cron setiap menit) dan queue worker aktif.</li>
            <li>Kuota endpoint keyword_search Meta maksimum 2.200 query / 24 jam. Fitur ini dibatasi {{ number_format($quotaCap, 0, ',', '.') }} query per hari sebagai pengaman.</li>
            <li>API resmi butuh App Review permission <strong>threads_keyword_search</strong> agar bisa mencari postingan publik.</li>
        </ul>
    </x-filament::section>

    {{-- Postingan Terbaru --}}
    <x-filament::section heading="Postingan Terbaru" :description="'Menampilkan '.count($recentPosts).' postingan terakhir.'">
        <div class="flex gap-3">
            <x-filament::button tag="a" size="sm" color="gray" :href="$agentUrl" icon="heroicon-m-list-bullet">
                Semua Permintaan
            </x-filament::button>
            <x-filament::button tag="a" size="sm" color="gray" :href="$keywordUrl" icon="heroicon-m-tag">
                Kelola Keyword
            </x-filament::button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Waktu</th>
                        <th class="px-3 py-2">Akun</th>
                        <th class="px-3 py-2">Isi</th>
                        <th class="px-3 py-2">Keyword</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($recentPosts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="whitespace-nowrap px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $post->posted_at?->format('d M Y H:i') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                {{ $post->username ? '@'.$post->username : '-' }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::limit((string) $post->text, 90) }}
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $post->matched_keyword ?? '-' }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-xs">
                                {{ \App\Models\ThreadsPost::statusLabel($post->status) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                @if ($post->permalink)
                                    <a href="{{ $post->permalink }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">Buka</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada postingan. Jalankan Fetch Sekarang untuk mulai memantau.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
