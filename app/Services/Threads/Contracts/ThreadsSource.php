<?php

namespace App\Services\Threads\Contracts;

use App\Services\Threads\ThreadsException;
use App\Services\Threads\ThreadsSearchResult;

/**
 * Sumber data Threads. Implementasi resmi (API Meta) maupun fallback
 * (scraping) harus memenuhi kontrak ini supaya bisa dipertukarkan.
 */
interface ThreadsSource
{
    /**
     * Nama sumber, dipakai untuk kolom `source` pada tabel threads_posts.
     *
     * @return string 'api' atau 'scrape'
     */
    public function name(): string;

    /**
     * Apakah sumber ini siap dipakai (kredensial/konfigurasi tersedia).
     */
    public function isAvailable(): bool;

    /**
     * Cari postingan publik.
     *
     * @param  string  $query  keyword atau topic tag
     * @param  string  $mode  'keyword' atau 'tag'
     * @param  string  $searchType  'RECENT' atau 'TOP'
     * @return array<int, ThreadsSearchResult>
     *
     * @throws ThreadsException
     */
    public function search(string $query, string $mode = 'keyword', string $searchType = 'RECENT', int $limit = 25): array;
}
