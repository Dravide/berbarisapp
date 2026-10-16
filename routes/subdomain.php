<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain Public Event Routes
|--------------------------------------------------------------------------
|
| These routes are accessed via subdomain (e.g., kejurcabcianjur.berbaris.app).
| Middleware ResolveEventnerSubdomain resolves the eventner from the subdomain
| and binds it to app('current_eventner').
|
*/

Route::get('/', App\Livewire\Public\EventDetail::class)->name('subdomain.detail');
Route::get('/peserta', App\Livewire\Public\EventParticipant::class)->name('subdomain.participant');
Route::get('/rundown', App\Livewire\Public\EventRundown::class)->name('subdomain.rundown');
// event_url() menyusun /denah untuk event ber-subdomain; tanpa baris ini
// tautan denah di nav publik 404.
Route::get('/denah', App\Livewire\Public\EventLayout::class)->name('subdomain.denah');
Route::get('/denah/{layout}', App\Livewire\Public\EventLayout::class)->name('subdomain.denah.satu');
Route::get('/hasil', App\Livewire\Public\EventResult::class)->name('subdomain.results');
Route::get('/hasil/{registration}', App\Livewire\Public\EventResultDetail::class)->name('subdomain.results.detail');
Route::get('/vote', App\Livewire\Public\EventVote::class)->name('subdomain.vote');
Route::get('/tiket', App\Livewire\Public\EventTicket::class)->name('subdomain.ticket');
// Unduh tiket PDF dari subdomain. event_url() menyusun /tiket/{orderCode}/pdf
// untuk event ber-subdomain (dan MailyService memakainya di email tiket), jadi
// tanpa baris ini tombolnya menunjuk ke 404 — hanya kelihatan di produksi.
Route::get('/tiket/{orderCode}/pdf', [App\Http\Controllers\Public\TicketPdfController::class, 'downloadFromSubdomain'])->name('subdomain.ticket.pdf');
Route::get('/daftar', App\Livewire\Public\Registration\Create::class)->name('subdomain.register');
Route::get('/drawing', App\Livewire\Eventner\Drawing\Spin::class)->name('subdomain.drawing.spin');
Route::get('/hasil-drawing', App\Livewire\Eventner\Drawing\Results::class)->name('subdomain.drawing.results');
Route::get('/overlay', App\Livewire\Public\LivestreamOverlay::class)->name('subdomain.overlay');
Route::get('/videotron', App\Livewire\Public\VideotronDisplay::class)->name('subdomain.videotron');
Route::get('/juknis', [App\Http\Controllers\Public\PublicJuknisController::class, 'downloadJuknis'])->name('subdomain.juknis');
// Unduh dokumen unggahan panitia di host subdomain — event_url() menyusun
// /dokumen/{id} untuk event ber-subdomain, jadi tanpa baris ini tautannya 404.
Route::get('/dokumen/{document}', [App\Http\Controllers\Public\PublicJuknisController::class, 'downloadDocumentFromSubdomain'])->name('subdomain.documents.download');
Route::get('/scan/{token}', App\Livewire\Public\Checkin\Scan::class)->name('subdomain.checkin.scan');
