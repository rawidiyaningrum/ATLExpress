<?php

namespace App\Console\Commands;

use App\Jobs\FetchThreadsCargoRequestsJob;
use Illuminate\Console\Command;

class FetchThreadsRequests extends Command
{
    protected $signature = 'threads:fetch {--sync : Jalankan langsung tanpa queue}';

    protected $description = 'Ambil postingan permintaan kargo dari Threads untuk keyword aktif';

    public function handle(): int
    {
        if (! config('threads.enabled')) {
            $this->warn('THREADS_ENABLED=false, perintah dilewati.');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            return $this->runSync();
        }

        FetchThreadsCargoRequestsJob::dispatch();

        $this->info('Job FetchThreadsCargoRequestsJob dikirim ke queue.');

        return self::SUCCESS;
    }

    private function runSync(): int
    {
        $summary = (new FetchThreadsCargoRequestsJob)->execute();

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Keyword diproses', $summary['keywords']],
                ['Postingan ditemukan', $summary['found']],
                ['Baru disimpan', $summary['created']],
                ['Sudah ada (update)', $summary['updated']],
                ['Gagal', $summary['errors']],
                ['Sisa kuota harian', $summary['quota_remaining']],
            ],
        );

        if ($summary['keywords'] === 0) {
            $this->warn('Tidak ada keyword yang diproses. Periksa keyword aktif dan konfigurasi sumber Threads (token API atau scraping).');

            return self::SUCCESS;
        }

        return $summary['errors'] > 0 && $summary['found'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}
