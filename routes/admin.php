<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ExportController;

Route::get('dashboard', App\Livewire\Admin\Dashboard::class)->name('admin.dashboard');
Route::get('revenue', App\Livewire\Admin\RevenueDashboard::class)->name('admin.revenue');
Route::get('eventner/pending', App\Livewire\Admin\Eventner\Pending::class)->name('admin.eventner.pending');
Route::get('eventner', App\Livewire\Admin\Eventner\Index::class)->name('admin.eventner.index');
Route::get('eventner/{id}', App\Livewire\Admin\Eventner\Show::class)->name('admin.eventner.show');
Route::get('eventner/{id}/penilaian', App\Livewire\Admin\Eventner\Penilaian::class)->name('admin.eventner.penilaian');
Route::get('eventner/{id}/struktur', App\Livewire\Admin\Eventner\Struktur::class)->name('admin.eventner.struktur');
Route::get('eventner/{id}/modul', App\Livewire\Admin\Eventner\Modul::class)->name('admin.eventner.modul');
Route::get('users', App\Livewire\Admin\User\Index::class)->name('admin.users.index');
Route::get('schools', App\Livewire\Admin\School\Index::class)->name('admin.schools.index');
Route::get('schools/{npsn}', App\Livewire\Admin\School\Show::class)->name('admin.schools.show');
Route::get('schools/{npsn}/edit', App\Livewire\Admin\School\Edit::class)->name('admin.schools.edit');
Route::get('settings', App\Livewire\Admin\Setting\Index::class)->name('admin.settings.index');
Route::get('settings/landing-page', App\Livewire\Admin\Setting\LandingPage::class)->name('admin.settings.landing-page');
Route::get('pricing-settings', App\Livewire\Admin\PricingSettings::class)->name('admin.pricing-settings');
Route::get('landing-partners', App\Livewire\Admin\LandingPartnerIndex::class)->name('admin.landing-partners');
Route::get('audit-log', App\Livewire\Admin\AuditLog::class)->name('admin.audit-log');

// Ekspor CSV (pola eventner.tickets.csv)
Route::get('exports/eventners', [ExportController::class, 'eventners'])->name('admin.exports.eventners');
Route::get('exports/registrations', [ExportController::class, 'registrations'])->name('admin.exports.registrations');
Route::get('exports/transactions', [ExportController::class, 'transactions'])->name('admin.exports.transactions');
