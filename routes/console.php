<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

// Dua sinkronizer AutoGoPay memakai jadwal TERPISAH: withoutOverlapping()
// hanya mengunci per-jadwal (mutex-nya dari nama event), jadi command tiap
// menit dan job tiap lima menit bisa berjalan bersamaan dan menembak
// /qris/status dua kali untuk transaksi yang sama.
//
// Satu kunci bersama: command jalan tiap menit (jalur tanpa queue worker,
// juga menyelamatkan transaksi yang baru masuk), job jalan tiap lima menit
// (lebih berat — bersihkan pendaftaran nyangkut, pulihkan EXPIRED → PAID,
// kirim email tiket). Karena butuh kunci yang sama, keduanya tidak pernah
// beradu.
Schedule::command('payment:sync-pending')
    ->everyMinute()
    ->withoutOverlapping()
    ->description('Sinkron transaksi AutoGoPay (jalur tanpa queue worker)');

Schedule::job(new \App\Jobs\SyncPendingPayments)
    ->everyFiveMinutes()
    ->withoutOverlapping();
Schedule::command('eventner:trial-expiry-warning')->dailyAt('08:00')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
