<?php

use Illuminate\Support\Facades\Route;

Route::get('dashboard', App\Livewire\Eventner\Dashboard::class)->name('eventner.dashboard');
Route::get('format-nilai', App\Livewire\Eventner\FormatNilai\Builder::class)->name('eventner.format-nilai.builder');
Route::get('format-nilai/pdf', [App\Http\Controllers\Eventner\FormatNilaiController::class, 'downloadPdf'])->name('eventner.format-nilai.pdf');
Route::get('judges', App\Livewire\Eventner\Judge\Index::class)->name('eventner.judges.index');
Route::get('competition-categories', App\Livewire\Eventner\CompetitionCategory\Index::class)->name('eventner.competition-categories.index');
Route::get('participants', App\Livewire\Eventner\Participant\Index::class)->name('eventner.participants.index');
Route::get('participants/{registration}/pdf', [App\Http\Controllers\Eventner\ParticipantController::class, 'downloadPdf'])->name('eventner.participants.pdf');
Route::get('vote-results', App\Livewire\Eventner\VoteResults\Index::class)->name('eventner.vote-results.index');
Route::get('vote-results/{registration}', App\Livewire\Eventner\VoteResults\Show::class)->name('eventner.vote-results.show');
Route::get('profile', App\Livewire\Eventner\Settings\Profile::class)->name('eventner.profile.index');
Route::get('score-recap', App\Livewire\Eventner\ScoreRecap\Index::class)->name('eventner.score-recap.index');
Route::get('scoring/csv', [App\Http\Controllers\Eventner\ScoringController::class, 'downloadCsv'])->name('eventner.scoring.csv');
Route::get('scoring/pdf-participant', [App\Http\Controllers\Eventner\ScoringController::class, 'downloadParticipantPdf'])->name('eventner.scoring.pdf-participant');
Route::get('scoring', App\Livewire\Eventner\Scoring\Index::class)->name('eventner.scoring.index');
Route::get('champion-categories', App\Livewire\Eventner\ChampionCategory\Index::class)->name('eventner.champion-categories.index');
Route::get('champion-categories/pdf', [App\Http\Controllers\Eventner\ChampionCategoryController::class, 'downloadPdf'])->name('eventner.champion-categories.pdf');
Route::get('tickets', App\Livewire\Eventner\Ticket\Index::class)->name('eventner.tickets.index');
Route::get('tickets/settings', App\Livewire\Eventner\Ticket\Settings::class)->name('eventner.tickets.settings');
Route::get('activity-log', App\Livewire\Eventner\ActivityLog\Index::class)->name('eventner.activity-log.index');
Route::get('drawing', App\Livewire\Eventner\Drawing\Index::class)->name('eventner.drawing.index');
Route::get('sponsors', App\Livewire\Eventner\Sponsor\Index::class)->name('eventner.sponsors.index');
Route::get('tenants', App\Livewire\Eventner\Tenant\Index::class)->name('eventner.tenants.index');
