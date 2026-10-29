<?php

use App\Http\Controllers\Public\ESertifikatController;
use App\Livewire\Public\ESertifikat\Cari;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laman E-Sertifikat (mis. e-sertifikat.berbaris.app)
|--------------------------------------------------------------------------
|
| Satu alamat tetap untuk mengunduh e-sertifikat lintas event: peserta
| memasukkan NISN, sistem menampilkan daftar lomba yang pernah ia ikuti
| beserta tombol unduh per baris.
|
| Grup ini BUKAN tenant — sama seperti host entry dan host EO — jadi tanpa
| middleware 'subdomain'. Kalau ia ikut grup {subdomain}.{root},
| ResolveEventnerSubdomain akan mencari event bernama "e-sertifikat" lalu
| menjawab 404 untuk semua halaman di sini.
|
| Karena itu pula grup ini WAJIB didaftarkan SEBELUM grup {subdomain}.{root}
| di bootstrap/app.php: grup tenant juga punya rute '/', dan rute yang
| didaftarkan lebih dulu yang menang. Urutan terbalik = beranda 404 sementara
| /unduh/{registration} tetap 200, dan gejalanya jadi menyesatkan.
|
| Subdomain "e-sertifikat" dicadangkan di Eventner\Settings\Profile::
| reservedSubdomain() supaya penyelenggara tidak bisa mengklaimnya.
|
| Throttle 30/menit adalah pagar UTAMA halaman ini, bukan hiasan: NISN adalah
| nomor berurutan yang bisa ditebak, dan tanpa pembatasan laju seluruh daftar
| peserta satu event bisa dipanen dengan satu skrip. 30 masih jauh di atas
| kebutuhan manusia — satu pencarian, beberapa unduhan.
|
*/

Route::get('/', Cari::class)->name('cert.home');

// Unduh satu berkas sertifikat. Registrasi & kategorinya ada di URL, tapi itu
// BUKAN kredensialnya: izinnya diperiksa dari daftar registrasi yang dibuka
// hasil pencarian NISN di session (lihat ESertifikatController). Angka di URL
// yang bisa ditebak tidak membuka apa pun tanpa pencarian yang sah lebih dulu.
Route::get('/unduh/{registration}', [ESertifikatController::class, 'unduh'])->name('cert.unduh');
