<?php

use App\Services\KgbService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('kgb:deteksi', function (KgbService $kgb) {
    $n = $kgb->deteksiJatuhTempo();
    $this->info("{$n} usulan KGB baru dibuat.");
})->purpose('Deteksi pegawai yang KGB-nya jatuh tempo dan buat usulan otomatis');

// Jalan tiap hari pukul 01.00 WIB (aman diulang: usulan yang sudah ada tidak dibuat ganda).
// Di server: jadwalkan `php artisan schedule:run` tiap menit (cron / Task Scheduler).
Schedule::command('kgb:deteksi')->dailyAt('01:00');
