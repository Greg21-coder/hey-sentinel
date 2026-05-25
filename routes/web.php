<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredAccountController;
use App\Http\Controllers\Customer\CustomerAppController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\ExportController;
use App\Http\Controllers\Customer\FollowAppController;
use App\Http\Controllers\Customer\SavedSearchController;
use App\Http\Controllers\Customer\SettingsController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::get('/register', [RegisteredAccountController::class, 'create'])->name('register');
Route::post('/register', [RegisteredAccountController::class, 'store'])->name('register.store');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', CustomerDashboardController::class)->name('dashboard');
    Route::get('/apps', [CustomerAppController::class, 'index'])->name('apps.index');
    Route::get('/apps/{shopifyApp}', [CustomerAppController::class, 'show'])->name('apps.show');
    Route::post('/apps/{shopifyApp}/follow', [FollowAppController::class, 'store'])->name('apps.follow');
    Route::delete('/apps/{shopifyApp}/follow', [FollowAppController::class, 'destroy'])->name('apps.unfollow');
    Route::resource('saved-searches', SavedSearchController::class)->except(['show', 'create', 'edit']);
    Route::post('/export/apps', [ExportController::class, 'apps'])->name('export.apps');
    Route::post('/export/reviews/{shopifyApp}', [ExportController::class, 'reviews'])->name('export.reviews');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});
