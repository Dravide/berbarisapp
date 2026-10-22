<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Host Event Organizer (eo.berbaris.app)
|--------------------------------------------------------------------------
|
| Halaman jasa penyelenggaraan lomba — profil tim, lingkup kerja, alur,
| event, tim, sponsor, dan formulir pengajuan. Isinya BUKAN satu event
| tertentu, jadi grup ini sengaja tidak memakai middleware 'subdomain':
| tidak ada Eventner yang perlu di-resolve di host ini.
|
| Alasan yang sama dipakai grup host entry (routes/entry.php). Kalau grup ini
| ikut ke {subdomain}.{root}, ResolveEventnerSubdomain akan mencari event
| bernama "eo" lalu menjawab 404 untuk semua halaman di sini.
|
| Selain itu grup ini WAJIB didaftarkan SEBELUM grup {subdomain}.{root} di
| bootstrap/app.php: grup tenant juga punya rute '/', dan rute yang lebih dulu
| didaftarkan yang menang. Kalau urutannya terbalik, hanya beranda yang 404 —
| path lain di bawah ini tidak ada padanannya di grup tenant sehingga tetap
| 200, dan gejalanya jadi menyesatkan.
|
| Subdomain "eo" dicadangkan di Eventner\Settings\Profile::reservedSubdomain()
| supaya penyelenggara tidak bisa mengklaimnya.
|
*/

Route::get('/', App\Livewire\Public\Eo\Beranda::class)->name('eo.beranda');
Route::get('/layanan', App\Livewire\Public\Eo\Layanan::class)->name('eo.layanan');
Route::get('/alur', App\Livewire\Public\Eo\Alur::class)->name('eo.alur');
Route::get('/event', App\Livewire\Public\Eo\Event::class)->name('eo.event');
Route::get('/tim', App\Livewire\Public\Eo\Team::class)->name('eo.tim');
Route::get('/sponsor', App\Livewire\Public\Eo\Sponsor::class)->name('eo.sponsor');
Route::get('/ajukan', App\Livewire\Public\Eo\Ajukan::class)->name('eo.ajukan');
