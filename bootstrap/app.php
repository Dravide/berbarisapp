<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'role:Admin'])
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web', 'auth', 'role:Eventner'])
                ->prefix('eventner')
                ->group(base_path('routes/eventner.php'));

            // Host jasa Event Organizer (mis. eo.berbaris.app). Sama seperti
            // host entry: BUKAN tenant, jadi tanpa middleware 'subdomain'.
            //
            // WAJIB didaftarkan SEBELUM grup {subdomain}.{root}: grup tenant itu
            // juga punya rute '/', dan rute yang didaftarkan lebih dulu yang
            // menang. Tanpa urutan ini, beranda EO tertangkap tenant lalu
            // dijawab 404 "Event tidak ditemukan" — sementara halaman lain
            // (yang path-nya tidak ada di grup tenant) tetap 200, sehingga
            // gejalanya terlihat seperti "cuma beranda yang rusak".
            Route::middleware(['web'])
                ->domain(eo_host())
                ->group(base_path('routes/eo.php'));

            // Host laman unduh e-sertifikat (mis. e-sertifikat.berbaris.app).
            // Sama seperti host EO dan host entry: BUKAN tenant, jadi tanpa
            // middleware 'subdomain'.
            //
            // WAJIB didaftarkan SEBELUM grup {subdomain}.{root} — alasannya
            // persis sama dengan grup EO di atas, dan di sini lebih mudah
            // terlewat: "e-sertifikat" adalah label yang SAH sebagai subdomain
            // event, jadi kalau urutannya terbalik, beranda laman ini yang
            // kalah oleh event (atau 404 "Event tidak ditemukan") sementara
            // /unduh/{id} tetap 200 — gejala yang menyesatkan.
            //
            // Throttle 30/menit: NISN adalah nomor berurutan yang bisa ditebak,
            // jadi tanpa pembatasan laju seluruh daftar peserta bisa dipanen
            // satu skrip. 30 masih jauh di atas kebutuhan manusia.
            Route::middleware(['web', 'throttle:30,1'])
                ->domain(cert_host())
                ->group(base_path('routes/certificate.php'));

            Route::middleware(['web', 'subdomain'])
                ->domain('{subdomain}.' . parse_url(config('app.url'), PHP_URL_HOST))
                ->group(base_path('routes/subdomain.php'));

            // Host tetap platform untuk input nilai juri (mis. entry.berbaris.app).
            // Tidak memakai middleware 'subdomain' — host ini bukan tenant, jadi
            // tidak ada Eventner yang perlu di-resolve. Pencocokan domain hanya
            // memakai host, jadi skema (kalau ada di ENTRY_HOST) dibuang dulu.
            // throttle: token di path adalah satu-satunya gerbang, jadi batasi
            // laju percobaan token acak/scraping. 120/menit cukup longgar untuk
            // tablet juri (tiap ketukan nilai = satu request Livewire) tapi
            // menutup enumerasi.
            Route::middleware(['web', 'throttle:120,1'])
                ->domain(judge_entry_host())
                ->group(base_path('routes/entry.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhook/autogopay',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'subdomain' => \App\Http\Middleware\ResolveEventnerSubdomain::class,
        ]);
    })
    ->withExceptions(function (\Illuminate\Foundation\Configuration\Exceptions $exceptions): void {
        // Kode error publik (ER-XXXXXX): setiap error tak terduga dicatat ke
        // error_logs dan kodenya ditempel ke exception, lalu dibaca render()
        // untuk ditampilkan di halaman 500 — user cukup melaporkan kodenya.
        $exceptions->report(function (\Throwable $e): void {
            if (property_exists($e, 'errorCode') && $e->errorCode !== null) {
                return; // sudah dicatat — jangan dobel (mis. report ulang di job)
            }

            $log = app(\App\Support\RecordsErrorReport::class)->record($e);

            if ($log) {
                $e->errorCode = $log->code;
            }
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $code = property_exists($e, 'errorCode') ? $e->errorCode : null;

            if ($request->expectsJson()) {
                return $code
                    ? response()->json(['message' => $e->getMessage() ?: 'Server Error', 'error_code' => $code], 500)
                    : null;
            }

            // Halaman 500 khusus (dengan kode) hanya saat debug off; 404 dan
            // layar debug Laravel tetap memakai jalur bawaan.
            if ($code && ! config('app.debug')) {
                return response()->view('errors.500', ['errorCode' => $code], 500);
            }

            return null;
        });
    })
    ->create();
