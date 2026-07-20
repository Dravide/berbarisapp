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
Route::get('/hasil', App\Livewire\Public\EventResult::class)->name('subdomain.results');
Route::get('/vote', App\Livewire\Public\EventVote::class)->name('subdomain.vote');
Route::get('/tiket', App\Livewire\Public\EventTicket::class)->name('subdomain.ticket');
Route::get('/daftar', App\Livewire\Public\Registration\Create::class)->name('subdomain.register');
Route::get('/drawing', App\Livewire\Eventner\Drawing\Spin::class)->name('subdomain.drawing.spin');
Route::get('/hasil-drawing', App\Livewire\Eventner\Drawing\Results::class)->name('subdomain.drawing.results');
Route::get('/overlay', App\Livewire\Public\LivestreamOverlay::class)->name('subdomain.overlay');
Route::get('/juknis', [App\Http\Controllers\Public\PublicJuknisController::class, 'downloadJuknis'])->name('subdomain.juknis');
Route::get('/scan/{token}', App\Livewire\Public\Checkin\Scan::class)->name('subdomain.checkin.scan');
