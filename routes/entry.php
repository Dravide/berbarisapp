<?php

use App\Livewire\Public\JudgeScoring\Index;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Host Masuk Juri (tablet)
|--------------------------------------------------------------------------
|
| Route di host tetap platform (config app.entry_host, mis. entry.berbaris.app).
| Dipakai juri untuk mengisi nilai selama lomba berlangsung. Token di path
| adalah satu-satunya gerbang — tanpa login, tanpa session.
|
| Route /juri/{token} di web.php tetap ada dan me-redirect 301 ke sini supaya
| QR/link yang sudah dicetak sebelumnya tetap berfungsi.
|
*/

Route::get('/juri/{token}', Index::class)->name('judge.scoring');
