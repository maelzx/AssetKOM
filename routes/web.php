<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\LabelController;
use App\Models\Asset;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('reports', 'reports.index')
    ->middleware(['auth', 'verified', 'can:view-reports'])
    ->name('reports.index');

Volt::route('activity', 'activity.index')
    ->middleware(['auth', 'verified', 'can:view-reports'])
    ->name('activity.index');

Route::middleware(['auth', 'verified', 'can:view-reports'])->group(function (): void {
    Route::get('exports/assets', [ExportController::class, 'assets'])->name('exports.assets');
    Route::get('exports/assignments', [ExportController::class, 'assignments'])->name('exports.assignments');
    Route::get('exports/maintenance', [ExportController::class, 'maintenance'])->name('exports.maintenance');
});

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

Volt::route('labels', 'labels.index')
    ->middleware(['auth', 'verified'])
    ->name('labels.index');

Route::middleware('auth')->group(function (): void {
    Route::get('a/{asset:asset_tag}', fn (Asset $asset) => redirect()->route('assets.show', $asset))
        ->name('scan.show');

    Route::get('assets/{asset}/label', [LabelController::class, 'single'])->name('labels.single');
    Route::get('labels/pdf', [LabelController::class, 'bulk'])->name('labels.bulk');
});

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
