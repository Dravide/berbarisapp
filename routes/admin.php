<?php

use Illuminate\Support\Facades\Route;

Route::get('dashboard', App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
Route::get('revenue', App\Livewire\Admin\RevenueDashboard::class)->name('admin.revenue');
Route::get('eventner/pending', App\Livewire\Admin\Eventner\Pending::class)->name('admin.eventner.pending');
Route::get('eventner', App\Livewire\Admin\Eventner\Index::class)->name('admin.eventner.index');
Route::get('eventner/{id}', App\Livewire\Admin\Eventner\Show::class)->name('admin.eventner.show');
Route::get('users', App\Livewire\Admin\User\Index::class)->name('admin.users.index');
Route::get('schools', App\Livewire\Admin\School\Index::class)->name('admin.schools.index');
Route::get('schools/{npsn}', App\Livewire\Admin\School\Show::class)->name('admin.schools.show');
Route::get('schools/{npsn}/edit', App\Livewire\Admin\School\Edit::class)->name('admin.schools.edit');
Route::get('settings', App\Livewire\Admin\Setting\Index::class)->name('admin.settings.index');
Route::get('settings/landing-page', App\Livewire\Admin\Setting\LandingPage::class)->name('admin.settings.landing-page');
