<?php

use App\Http\Controllers\AttachmentDownloadController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('settings', 'settings.index')
    ->middleware(['auth', 'verified', 'can:manage-settings'])
    ->name('settings');

Volt::route('categories', 'categories.index')
    ->middleware(['auth', 'verified', 'can:manage-catalog'])
    ->name('categories.index');

Volt::route('locations', 'locations.index')
    ->middleware(['auth', 'verified', 'can:manage-catalog'])
    ->name('locations.index');

Volt::route('assets', 'assets.index')
    ->middleware(['auth', 'verified'])
    ->name('assets.index');

Volt::route('assignments', 'assignments.index')
    ->middleware(['auth', 'verified'])
    ->name('assignments.index');

Volt::route('maintenance', 'maintenance.index')
    ->middleware(['auth', 'verified'])
    ->name('maintenance.index');

Route::get('attachments/{attachment}', AttachmentDownloadController::class)
    ->middleware('auth')
    ->name('attachments.download');

Volt::route('assets/create', 'assets.form')
    ->middleware(['auth', 'verified', 'can:manage-assets'])
    ->name('assets.create');

Volt::route('assets/{asset}/edit', 'assets.form')
    ->middleware(['auth', 'verified', 'can:manage-assets'])
    ->name('assets.edit');

Volt::route('assets/{asset}', 'assets.show')
    ->middleware(['auth', 'verified'])
    ->name('assets.show');

require __DIR__.'/auth.php';
