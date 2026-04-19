<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('login', App\Livewire\Auth\Login::class)->name('login');
});

Route::get('/event/{slug}', App\Livewire\Public\EventDetail::class)->name('event.detail');
Route::get('/event/{slug}/participant', App\Livewire\Public\EventParticipant::class)->name('event.participant');

// Voting Routes
Route::get('/event/{slug}/vote', App\Livewire\Public\EventVote::class)->name('event.vote');
Route::post('/webhook/xendit', [App\Http\Controllers\Webhook\XenditWebhookController::class, 'handle']);
Route::get('/event/{slug}/drawing', App\Livewire\Eventner\Drawing\Spin::class)->name('event.drawing.spin');
Route::get('/event/{slug}/drawing-results', App\Livewire\Eventner\Drawing\Results::class)->name('event.drawing.results');
Route::get('/reg/{token}', App\Livewire\Public\MagicLink\Registration::class)->name('magic.link');

Route::middleware(['auth:web'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', App\Livewire\Dashboard\Index::class)->name('dashboard');
});
