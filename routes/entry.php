<?php

use App\Livewire\Public\JudgeScoring\Index;
use App\Livewire\Public\PanitiaScoring\Index as PanitiaScoring;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Host Masuk Juri (tablet) & Entry Nilai Panitia
|--------------------------------------------------------------------------
|
| Route di host tetap platform (config app.entry_host, mis. entry.berbaris.app).
|
| /juri/{token}    — juri mengisi nilai sendiri. Token di path adalah
|                    satu-satunya gerbang; tanpa login, tanpa session.
| /panitia/{token} — petugas meja mengetik nilai dari lembar kertas juri.
|                    Token di path HANYA identitas event; izin mengetik dijaga
|                    PIN event (eventners.panitia_pin) yang diminta sekali lalu
|                    diingat di session. Link ber-token selalu bisa bocor lewat
|                    grup WhatsApp atau riwayat browser tablet bersama — PIN
|                    tidak ikut tercetak di QR.
|
| Route /juri/{token} di web.php tetap ada dan me-redirect 301 ke sini supaya
| QR/link yang sudah dicetak sebelumnya tetap berfungsi.
|
*/

Route::get('/juri/{token}', Index::class)->name('judge.scoring');

// Throttle lebih ketat dari grup entry (120/menit): halaman ini punya gerbang
// PIN, jadi laju percobaan tebakan harus dibatasi. Tiap ketukan nilai tetap
// satu request, dan 20/menit masih jauh di atas kecepatan mengetik manusia.
Route::get('/panitia/{token}', PanitiaScoring::class)
    ->middleware('throttle:20,1')
    ->name('panitia.scoring');

