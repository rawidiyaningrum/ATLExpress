<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generator Konten AI
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk generator artikel berbasis AI (Gemini API). Kredensial
    | hanya boleh disimpan di .env server, jangan pernah di database atau UI.
    |
    */

    'enabled' => env('AI_ENABLED', true),

    'provider' => env('AI_PROVIDER', 'gemini'),

    'api_key' => env('AI_API_KEY'),

    'model' => env('AI_MODEL', 'gemini-3.1-flash-lite'),

    'base_url' => env('AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    'timeout' => (int) env('AI_TIMEOUT', 90),

    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 3000),

    'temperature' => (float) env('AI_TEMPERATURE', 0.85),

    /*
     * Gemini 3.x adalah thinking model. Budget 0 mematikan penalaran supaya
     * token tidak membengkak dan balasan cepat. Bila API menolak field ini,
     * client otomatis mencoba lagi tanpa field tersebut.
     */
    'thinking_budget' => (int) env('AI_THINKING_BUDGET', 0),

    'retries' => (int) env('AI_RETRIES', 2),

    'retry_delay_ms' => (int) env('AI_RETRY_DELAY_MS', 3000),

    /*
     * Pengaman biaya. Cap 0 berarti AI diblokir total (tidak ada pengeluaran).
     */
    'budget' => [
        'monthly_cap_usd' => (float) env('AI_MONTHLY_CAP_USD', 5),
        'run_cap_usd' => (float) env('AI_RUN_CAP_USD', 1),
        'max_articles_per_run' => (int) env('AI_MAX_ARTICLES_PER_RUN', 50),
    ],

    /*
     * Model yang bisa dipilih dari halaman generator.
     */
    'models' => [
        'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite — paling hemat (free tier)',
        'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite — hemat, sedikit lebih pintar',
        'gemini-3.8-flash' => 'Gemini 3.8 Flash — paling pintar (biaya lebih tinggi)',
    ],

    /*
     * Harga per 1 juta token (USD), dipakai untuk estimasi biaya di UI dan
     * pencatatan pemakaian. Model di luar daftar memakai harga default.
     */
    'prices' => [
        'gemini-3.1-flash-lite' => ['in' => 0.25, 'out' => 1.50],
        'gemini-flash-lite-latest' => ['in' => 0.25, 'out' => 1.50],
        'gemini-3.5-flash-lite' => ['in' => 0.30, 'out' => 2.50],
        'gemini-3.8-flash' => ['in' => 0.75, 'out' => 3.75],
    ],

    'default_price' => ['in' => 0.75, 'out' => 3.75],

];
