<?php

use Illuminate\Support\Facades\Route;

Route::get('eventner', App\Livewire\Admin\Eventner\Index::class)->name('admin.eventner.index');
Route::get('eventner/{id}', App\Livewire\Admin\Eventner\Show::class)->name('admin.eventner.show');
Route::get('settings', App\Livewire\Admin\Setting\Index::class)->name('admin.settings.index');
