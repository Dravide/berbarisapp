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
    
    // Admin Routes
    Route::get('admin/eventner', App\Livewire\Admin\Eventner\Index::class)->name('admin.eventner.index');
    Route::get('admin/eventner/{id}', App\Livewire\Admin\Eventner\Show::class)->name('admin.eventner.show');
    Route::get('admin/settings', App\Livewire\Admin\Setting\Index::class)->name('admin.settings.index');

    // Eventner Routes
    Route::get('eventner/format-nilai', App\Livewire\Eventner\FormatNilai\Builder::class)->name('eventner.format-nilai.builder');
    Route::get('eventner/format-nilai/pdf', [App\Http\Controllers\Eventner\FormatNilaiController::class, 'downloadPdf'])->name('eventner.format-nilai.pdf');
    Route::get('eventner/judges', App\Livewire\Eventner\Judge\Index::class)->name('eventner.judges.index');
    Route::get('eventner/competition-categories', App\Livewire\Eventner\CompetitionCategory\Index::class)->name('eventner.competition-categories.index');
    Route::get('eventner/participants', App\Livewire\Eventner\Participant\Index::class)->name('eventner.participants.index');
    Route::get('eventner/vote-results', App\Livewire\Eventner\VoteResults\Index::class)->name('eventner.vote-results.index');
    Route::get('eventner/profile', App\Livewire\Eventner\Settings\Profile::class)->name('eventner.profile.index');
});
