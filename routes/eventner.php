<?php

use Illuminate\Support\Facades\Route;

Route::get('dashboard', App\Livewire\Eventner\Dashboard::class)->name('eventner.dashboard');
Route::get('format-nilai', App\Livewire\Eventner\FormatNilai\Builder::class)->name('eventner.format-nilai.builder');
Route::get('format-nilai/pdf', [App\Http\Controllers\Eventner\FormatNilaiController::class, 'downloadPdf'])->name('eventner.format-nilai.pdf');
Route::get('judges', App\Livewire\Eventner\Judge\Index::class)->name('eventner.judges.index');
Route::get('competition-categories', App\Livewire\Eventner\CompetitionCategory\Index::class)->name('eventner.competition-categories.index');
Route::get('participants', App\Livewire\Eventner\Participant\Index::class)->name('eventner.participants.index');
Route::get('vote-results', App\Livewire\Eventner\VoteResults\Index::class)->name('eventner.vote-results.index');
Route::get('profile', App\Livewire\Eventner\Settings\Profile::class)->name('eventner.profile.index');
