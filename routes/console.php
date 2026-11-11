<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Pemantau permintaan kargo dari Threads. Dijalankan bergilir tiap 30 menit
// dan tidak menumpuk bila eksekusi sebelumnya belum selesai.
Schedule::command('threads:fetch')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();
