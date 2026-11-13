<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pemantau Permintaan Kargo dari Threads
    |--------------------------------------------------------------------------
    |
    | Fitur ini mencari postingan publik di Threads yang berisi permintaan
    | jasa pengiriman kargo. Kredensial HANYA boleh ditaruh di .env server,
    | jangan pernah dimasukkan ke database atau UI.
    |
    */

    'enabled' => env('THREADS_ENABLED', true),

    /*
     * Jalur pengambilan data:
     * - api    : hanya API resmi Meta (butuh token + approval threads_keyword_search)
     * - scrape : hanya fallback scraping halaman publik Threads
     * - auto   : pakai API bila terkonfigurasi, jika tidak/gagal -> scraping
     */
    'source' => env('THREADS_SOURCE', 'auto'),

    'api' => [
        'base_url' => env('THREADS_API_BASE_URL', 'https://graph.threads.com/v1.0'),
        'access_token' => env('THREADS_ACCESS_TOKEN'),
        'app_secret' => env('THREADS_APP_SECRET'),
        'timeout' => (int) env('THREADS_API_TIMEOUT', 30),
        'retries' => (int) env('THREADS_API_RETRIES', 2),
        'retry_delay_ms' => (int) env('THREADS_API_RETRY_DELAY_MS', 1500),

        /*
         * Field yang diminta ke endpoint keyword_search. Field `owner` tidak
         * diizinkan Meta dan sengaja tidak dipakai.
         */
        'fields' => [
            'id',
            'text',
            'media_type',
            'permalink',
            'timestamp',
            'username',
            'has_replies',
            'is_quote_post',
            'is_reply',
        ],
    ],

    /*
     * Fallback scraping. Rapuh dan berisiko melanggar ToS: pakai hanya
     * sebagai jalur sementara sebelum API resmi disetujui Meta.
     *
     * Scraper membuka halaman pencarian publik
     * `{base_url}/search?exact_match=false&q={query}&filter=recent` lalu
     * mengekstrak hasil yang dirender server. User-Agent crawler dipakai
     * agar Threads mengirim HTML berisi data pencarian, bukan halaman SPA
     * kosong.
     */
    'scrape' => [
        'base_url' => env('THREADS_SCRAPE_BASE_URL', 'https://www.threads.com'),
        'timeout' => (int) env('THREADS_SCRAPE_TIMEOUT', 30),
        'retries' => (int) env('THREADS_SCRAPE_RETRIES', 1),
        'user_agent' => env(
            'THREADS_SCRAPE_USER_AGENT',
            'Mozilla/5.0 (compatible; Bingbot/2.0; +http://www.bing.com/bingbot.htm)'
        ),
    ],

    /*
     * Kuota endpoint keyword_search Meta adalah maksimum 2.200 query per 24
     * jam per user (lintas app). Nilai default dibuat lebih rendah sebagai
     * pengaman.
     */
    'query_cap_per_day' => (int) env('THREADS_QUERY_CAP_PER_DAY', 2000),

    'per_keyword_limit' => (int) env('THREADS_PER_KEYWORD_LIMIT', 25),

    /*
     * Keyword kurasi awal. Dipakai ThreadsKeywordSeeder supaya instalasi
     * pertama langsung punya kata kunci yang relevan. Bisa ditambah/diubah
     * dari menu admin maupun lewat ekspansi AI.
     */
    'default_keywords' => [
        'cari ekspedisi',
        'butuh ekspedisi',
        'rekomendasi ekspedisi',
        'jasa ekspedisi',
        'ekspedisi murah',
        'ekspedisi terpercaya',
        'jasa kargo',
        'cargo murah',
        'jasa pengiriman barang',
        'jasa kirim barang',
        'kirim barang murah',
        'ongkir murah',
        'kirim paket murah',
        'jasa logistik',
        'pengiriman antar pulau',
        'jasa pindahan',
        'jasa pindahan rumah',
        'kirim motor',
        'kirim mobil',
        'cargo udara',
        'cargo laut',
        'sewa truk',
        'muat barang',
        'kirim furniture',
        'ekspedisi jakarta',
        'cargo surabaya',
        'cargo makassar',
        'kargo balikpapan',
        'ekspedisi medan',
        'ekspedisi samarinda',
        'cargo banjarmasin',
        'ekspedisi manado',
        'kirim ke jayapura',
    ],

    /*
     * Topic tag yang dicari dengan search_mode=TAG.
     */
    'default_tags' => [
        'ekspedisi',
        'cargo',
        'kirimbarang',
        'jasaekspedisi',
        'logistik',
        'pindahan',
    ],

];
